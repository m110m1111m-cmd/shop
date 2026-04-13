<?php
// api/backup.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized");
}

require_once '../db.php';

$tables = [];
$result = $pdo->query("SHOW TABLES");
while ($row = $result->fetch(PDO::FETCH_NUM)) {
    $tables[] = $row[0];
}

$return = "-- Retail POS Backup\n";
$return .= "-- Date: " . date('Y-m-d H:i:s') . "\n\n";
$return .= "SET FOREIGN_KEY_CHECKS=0;\n";
$return .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
$return .= "SET time_zone = \"+00:00\";\n\n";

foreach ($tables as $table) {
    // Drop table if exists
    $return .= "DROP TABLE IF EXISTS `$table`;\n";
    
    // Create table structure
    $res = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM);
    $return .= $res[1] . ";\n\n";
    
    // Get table data
    $result = $pdo->query("SELECT * FROM `$table` ");
    $num_fields = $result->columnCount();
    
    while ($row = $result->fetch(PDO::FETCH_NUM)) {
        $return .= "INSERT INTO `$table` VALUES(";
        for ($j = 0; $j < $num_fields; $j++) {
            if (isset($row[$j])) {
                $row[$j] = addslashes($row[$j]);
                $row[$j] = str_replace("\n", "\\n", $row[$j]);
                $return .= '"' . $row[$j] . '"';
            } else {
                $return .= 'NULL';
            }
            if ($j < ($num_fields - 1)) {
                $return .= ',';
            }
        }
        $return .= ");\n";
    }
    $return .= "\n\n\n";
}

$return .= "SET FOREIGN_KEY_CHECKS=1;";

// Log the backup action
require_once '../includes/logger.php';
logAction('DATABASE_BACKUP', 'User downloaded a full database backup');

// Download file
$filename = 'db_backup_' . date('Y_m_d_H_i_s') . '.sql';
header('Content-Type: application/octet-stream');
header("Content-Transfer-Encoding: Binary");
header("Content-disposition: attachment; filename=\"" . $filename . "\"");
echo $return;
exit;
