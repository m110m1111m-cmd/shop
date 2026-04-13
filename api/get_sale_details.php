<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$sale_id = $_GET['sale_id'] ?? 0;

if (!$sale_id) {
    echo json_encode(['success' => false, 'message' => 'Sale ID is required']);
    exit;
}

$sql = "SELECT sd.qty, sd.price, sd.subtotal, p.name as product_name, p.unit_name
        FROM sale_details sd
        JOIN products p ON sd.product_id = p.id
        WHERE sd.sale_id = ?";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$sale_id]);
    $details = $stmt->fetchAll();

    echo json_encode(['success' => true, 'data' => $details]);
} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
