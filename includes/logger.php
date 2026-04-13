<?php
// includes/logger.php

if (!function_exists('logAction')) {
    function logAction($action, $description = null) {
        global $pdo;
        
        // Ensure $pdo is available
        if (!isset($pdo)) {
            require_once __DIR__ . '/../db.php';
        }

        $user_id = $_SESSION['user_id'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        try {
            $stmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $action, $description, $ip]);
        } catch (\Exception $e) {
            // Silently fail if logging fails to not break the main flow
            error_log("Logging failed: " . $e->getMessage());
        }
    }
}
