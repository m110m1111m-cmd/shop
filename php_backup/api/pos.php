<?php
// api/pos.php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../db.php';

$action = $_GET['action'] ?? '';

if ($action === 'checkout') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || empty($data['items'])) {
        echo json_encode(['success' => false, 'message' => 'Cart is empty']);
        exit;
    }

    $payment_method = $data['payment_method'] ?? 'cash';
    $total_amount = $data['total_amount'] ?? 0;
    $discount_amount = $data['discount_amount'] ?? 0;
    $vat_amount = $data['vat_amount'] ?? 0;
    
    try {
        $pdo->beginTransaction();

        // 1. Get current active shift
        $shift_id = null; // Mock

        // 2. Insert Sale
        $stmtSale = $pdo->prepare("INSERT INTO sales (shift_id, total_amount, discount_amount, vat_amount, payment_method) VALUES (?, ?, ?, ?, ?)");
        $stmtSale->execute([$shift_id, $total_amount, $discount_amount, $vat_amount, $payment_method]);
        $sale_id = $pdo->lastInsertId();

        // 3. Insert Sale Details & Deduct Stock
        $stmtDetail = $pdo->prepare("INSERT INTO sale_details (sale_id, product_id, qty, price, discount_amount, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
        
        $stmtInv = $pdo->prepare("UPDATE inventory SET stock_qty = stock_qty - ? WHERE product_id = ?");
        
        // Check for conversions to deduct child units
        $stmtConv = $pdo->prepare("SELECT child_product_id, quantity FROM product_conversions WHERE parent_product_id = ?");

        foreach ($data['items'] as $item) {
            $product_id = $item['id'];
            $qty = $item['qty'];
            $price = $item['selling_price'];
            $item_discount = $item['discount_amount'] ?? 0;
            $subtotal = ($qty * $price) - $item_discount;

            // Insert details
            $stmtDetail->execute([$sale_id, $product_id, $qty, $price, $item_discount, $subtotal]);

            // Stock deduction Logic
            // First check if this product has child products it converts to (e.g. Pack -> Bottle)
            $stmtConv->execute([$product_id]);
            $conversions = $stmtConv->fetchAll();

            if (count($conversions) > 0) {
                // Deduct from child product inventory instead
                foreach ($conversions as $conv) {
                    $deductQty = $qty * $conv['quantity'];
                    $stmtInv->execute([$deductQty, $conv['child_product_id']]);
                }
            } else {
                // Regular deduction
                $stmtInv->execute([$qty, $product_id]);
            }
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'sale_id' => $sale_id]);

    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
