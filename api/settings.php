<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../db.php';
require_once '../includes/logger.php';

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

// Only Admins can UPDATE settings, but all users can VIEW for system info
function isAdmin() {
    return isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin';
}

function isLogged() {
    return isset($_SESSION['user_id']);
}

if ($action === 'get_shop_info') {
    if (!isLogged()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    try {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
        $results = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        echo json_encode(['success' => true, 'data' => $results]);
    } catch (\Throwable $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;

} elseif ($action === 'get_logs') {
    if (!isAdmin()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    try {
        $stmt = $pdo->query("
            SELECT l.*, u.username 
            FROM system_logs l 
            LEFT JOIN users u ON l.user_id = u.id 
            ORDER BY l.created_at DESC 
            LIMIT 50
        ");
        $logs = $stmt->fetchAll();
        echo json_encode(['success' => true, 'data' => $logs]);
    } catch (\Throwable $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;

} elseif ($action === 'update_shop_info') {
    if (!isAdmin()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    try {
        $pdo->beginTransaction();
        
        $keys = [
            'shop_name', 'shop_address', 'shop_phone', 'low_stock_enabled',
            'currency_code', 'decimal_places', 'vat_rate', 'vat_type',
            'bank_name', 'bank_account_name', 'bank_account_number',
            'receipt_header', 'receipt_footer', 'receipt_paper_size'
        ];

        $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) 
                               ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");

        foreach ($keys as $key) {
            if (isset($_POST[$key])) {
                $val = $_POST[$key];
                $stmt->execute([$key, $val]);
                
                // Extra logic: Update currency_symbol if currency_code changes
                if ($key === 'currency_code') {
                    $symbols = ['LAK' => '₭', 'THB' => '฿', 'USD' => '$'];
                    $symbol = $symbols[$val] ?? '₭';
                    $stmt->execute(['currency_symbol', $symbol]);
                }
            }
        }
        
        // Handle file uploads (Logo and QR Code)
        $files = ['shop_logo' => 'logo_', 'qr_code' => 'qr_'];
        foreach ($files as $field => $prefix) {
            if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                $tmp_name = $_FILES[$field]['tmp_name'];
                $ext = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION);
                $file_name = $prefix . time() . '.' . $ext;
                $target_dir = '../uploads/';
                if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
                
                if (move_uploaded_file($tmp_name, $target_dir . $file_name)) {
                    $path = 'uploads/' . $file_name;
                    $stmt->execute([$field, $path]);
                }
            }
        }
        
        $pdo->commit();
        logAction('UPDATE_SETTINGS', 'Updated system configurations');
        echo json_encode(['success' => true, 'message' => 'Settings updated successfully']);
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>
