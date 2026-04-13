<?php
// api/inventory.php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../db.php';
require_once '../includes/logger.php';

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

// Ensure action is authorized if not 'get_product' (POS uses get_product)
if ($action !== 'get_product' && !isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($action === 'get_product') {
    $barcode = $_GET['barcode'] ?? '';
    
    if (empty($barcode)) {
        echo json_encode(['success' => false, 'message' => 'Barcode is required']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id, barcode, name, unit_name, selling_price, image_path FROM products WHERE barcode = ? LIMIT 1");
    $stmt->execute([$barcode]);
    $product = $stmt->fetch();

    if ($product) {
        echo json_encode([
            'success' => true,
            'data' => [
                'id' => (int)$product['id'],
                'barcode' => $product['barcode'],
                'name' => $product['name'],
                'unit_name' => $product['unit_name'],
                'selling_price' => (float)$product['selling_price'],
                'image_path' => $product['image_path']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
    }

} elseif ($action === 'get_all_products') {
    $stmt = $pdo->query("
        SELECT p.id, p.barcode, p.name, c.name as category_name, p.unit_name, 
               p.selling_price, i.stock_qty, i.avg_cost_price, p.image_path
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN inventory i ON p.id = i.product_id
        ORDER BY p.id DESC
    ");
    $products = $stmt->fetchAll();
    echo json_encode(['success' => true, 'data' => $products]);

} elseif ($action === 'get_categories') {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
    $categories = $stmt->fetchAll();
    echo json_encode(['success' => true, 'data' => $categories]);

} elseif ($action === 'add_product') {
    if ($_SESSION['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Admin only']);
        exit;
    }

    $barcode = trim($_POST['barcode'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $category_id = $_POST['category_id'] ?? 1; // Default category if none selected
    $unit_name = trim($_POST['unit_name'] ?? '');
    $selling_price = $_POST['selling_price'] ?? 0;
    
    $cost_price = $_POST['cost_price'] ?? 0;
    $stock_qty = $_POST['stock_qty'] ?? 0;
    $alert_threshold = $_POST['alert_threshold'] ?? 0;

    if (empty($name) || empty($unit_name)) {
        echo json_encode(['success' => false, 'message' => 'Name and Unit are required.']);
        exit;
    }

    // Generate random barcode if empty
    if (empty($barcode)) {
        $barcode = time() . rand(100, 999);
    }

    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['image']['tmp_name'];
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $file_name = time() . '_' . rand(1000, 9999) . '.' . $ext;
        $target_dir = '../uploads/products/';
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        if (move_uploaded_file($tmp_name, $target_dir . $file_name)) {
            $image_path = 'uploads/products/' . $file_name;
        }
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO products (barcode, name, category_id, unit_name, selling_price, image_path) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$barcode, $name, $category_id, $unit_name, $selling_price, $image_path]);
        $product_id = $pdo->lastInsertId();

        $stmtInv = $pdo->prepare("INSERT INTO inventory (product_id, stock_qty, alert_threshold, avg_cost_price) VALUES (?, ?, ?, ?)");
        $stmtInv->execute([$product_id, $stock_qty, $alert_threshold, $cost_price]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Product added successfully']);
    } catch (\PDOException $e) {
        $pdo->rollBack();
        // Check for duplicate barcode
        if ($e->getCode() == 23000) {
            echo json_encode(['success' => false, 'message' => 'Error: Barcode already exists.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }

} elseif ($action === 'receive_stock') {
    if ($_SESSION['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Admin only']);
        exit;
    }

    $product_id = $_POST['product_id'] ?? 0;
    $add_qty = $_POST['add_qty'] ?? 0;

    if ($product_id && $add_qty > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE inventory SET stock_qty = stock_qty + ? WHERE product_id = ?");
            $stmt->execute([$add_qty, $product_id]);
            echo json_encode(['success' => true, 'message' => 'Stock updated successfully']);
        } catch (\Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid product or quantity']);
    }

} elseif ($action === 'update_product') {
    if ($_SESSION['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Admin only']);
        exit;
    }

    $id = $_POST['id'] ?? 0;
    $selling_price = $_POST['selling_price'] ?? 0;
    $cost_price = $_POST['cost_price'] ?? 0;
    $name = trim($_POST['name'] ?? '');

    if ($id && $selling_price >= 0 && $name !== '') {
        try {
            $pdo->beginTransaction();
            
            // Handle image upload
            $image_sql = "";
            $image_params = [];
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                // Get old image to delete
                $stmt_old = $pdo->prepare("SELECT image_path FROM products WHERE id = ?");
                $stmt_old->execute([$id]);
                $old_path = $stmt_old->fetchColumn();
                if ($old_path && file_exists('../' . $old_path)) {
                    unlink('../' . $old_path);
                }

                $tmp_name = $_FILES['image']['tmp_name'];
                $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $file_name = time() . '_' . rand(1000, 9999) . '.' . $ext;
                $target_dir = '../uploads/products/';
                if (move_uploaded_file($tmp_name, $target_dir . $file_name)) {
                    $image_sql = ", image_path = ?";
                    $image_params[] = 'uploads/products/' . $file_name;
                }
            }

            $sql = "UPDATE products SET selling_price = ?, name = ?" . $image_sql . " WHERE id = ?";
            $params = array_merge([$selling_price, $name], $image_params, [$id]);
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $stmt2->execute([$cost_price, $id]);
            $pdo->commit();
            
            logAction('UPDATE_PRODUCT', "Updated product: $name (ID: $id)");

            echo json_encode(['success' => true, 'message' => 'ອັບເດດສິນຄ້າສຳເລັດແລ້ວ!']);
        } catch (\PDOException $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid data provided']);
    }

} elseif ($action === 'delete_product') {
    if ($_SESSION['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Admin only']);
        exit;
    }

    $id = $_POST['id'] ?? 0;

    if ($id) {
        try {
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);
            logAction('DELETE_PRODUCT', "Deleted product ID: $id");
            echo json_encode(['success' => true, 'message' => 'ລຶບສິນຄ້າສຳເລັດແລ້ວ!']);
        } catch (\PDOException $e) {
            if ($e->getCode() == 23000) {
                echo json_encode(['success' => false, 'message' => 'ບໍ່ສາມາດລຶບສິນຄ້ານີ້ໄດ້ ເນື່ອງຈາກເຄີຍມີການຂາຍສະກົດຢູ່ໃນປະຫວັດບິນແລ້ວ!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            }
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    }

} elseif ($action === 'search_products') {
    $query = $_GET['query'] ?? '';
    if (empty($query)) {
        echo json_encode(['success' => true, 'data' => []]);
        exit;
    }

    $q = "%$query%";
    $stmt = $pdo->prepare("
        SELECT id, barcode, name, unit_name, selling_price, image_path 
        FROM products 
        WHERE barcode LIKE ? OR name LIKE ? 
        LIMIT 10
    ");
    $stmt->execute([$q, $q]);
    $results = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'data' => $results]);

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
