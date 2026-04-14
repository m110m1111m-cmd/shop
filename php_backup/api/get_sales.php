<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

$sql = "SELECT s.id, s.total_amount, s.payment_method, s.sale_time, sh.cashier_name 
        FROM sales s
        LEFT JOIN shifts sh ON s.shift_id = sh.id
        WHERE 1=1";
$params = [];

if ($start_date) {
    $sql .= " AND DATE(s.sale_time) >= ?";
    $params[] = $start_date;
}
if ($end_date) {
    $sql .= " AND DATE(s.sale_time) <= ?";
    $params[] = $end_date;
}

$sql .= " ORDER BY s.sale_time DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $sales = $stmt->fetchAll();

    echo json_encode(['success' => true, 'data' => $sales]);
} catch (\PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
