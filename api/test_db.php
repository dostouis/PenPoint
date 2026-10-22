<?php
// api/test_db.php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

try {
    $conn = getConnection();
    echo json_encode([
        "success" => true,
        "message" => "Database connected!",
        "host" => DB_HOST,
        "database" => DB_NAME
    ]);
    // DO NOT CLOSE - connection is shared
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
?>