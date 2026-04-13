<?php
// api/staff.php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../db.php';

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

// Ensure action is authorized (Admin only)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($action === 'get_staff_list') {
    try {
        $stmt = $pdo->query("SELECT id, username, role, full_name, phone, address, salary, join_date, is_active, created_at FROM users ORDER BY created_at DESC");
        $staff = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $staff]);
    } catch (\PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

} elseif ($action === 'save_staff') {
    $id = $_POST['id'] ?? null;
    $username = trim($_POST['username'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $salary = $_POST['salary'] ?? 0;
    $join_date = $_POST['join_date'] ?? date('Y-m-d');
    $role = $_POST['role'] ?? 'cashier';
    $password = $_POST['password'] ?? '';
    $is_active = $_POST['is_active'] ?? 1;

    if (empty($username) || empty($full_name)) {
        echo json_encode(['success' => false, 'message' => 'Username and Full Name are required']);
        exit;
    }

    try {
        if ($id) {
            // Update
            $sql = "UPDATE users SET username = ?, full_name = ?, phone = ?, address = ?, salary = ?, join_date = ?, role = ?, is_active = ?";
            $params = [$username, $full_name, $phone, $address, $salary, $join_date, $role, $is_active];
            
            if (!empty($password)) {
                $sql .= ", password = ?";
                $params[] = password_hash($password, PASSWORD_DEFAULT);
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $id;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            echo json_encode(['success' => true, 'message' => 'Staff updated successfully']);
        } else {
            // Create
            if (empty($password)) {
                echo json_encode(['success' => false, 'message' => 'Password is required for new accounts']);
                exit;
            }
            
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, phone, address, salary, join_date, role, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$username, $hashed_password, $full_name, $phone, $address, $salary, $join_date, $role, $is_active]);
            echo json_encode(['success' => true, 'message' => 'Staff created successfully']);
        }
    } catch (\PDOException $e) {
        if ($e->getCode() == 23000) {
            echo json_encode(['success' => false, 'message' => 'Username already exists']);
        } else {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

} elseif ($action === 'delete_staff') {
    $id = $_POST['id'] ?? 0;
    
    if ($id == $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete your own account']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Staff deleted successfully']);
    } catch (\PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
