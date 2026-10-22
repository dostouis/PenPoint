<?php
// api/add_reward.php
// Add a new reward

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');

require_once __DIR__ . '/../config/database.php';

$name = trim($_GET['name'] ?? $_POST['name'] ?? '');
$pts = (int)($_GET['pts'] ?? $_POST['pts'] ?? 0);
$stock = (int)($_GET['stock'] ?? $_POST['stock'] ?? 0);
$desc = trim($_GET['desc'] ?? $_POST['desc'] ?? '');

if ($name === '' || $pts <= 0) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Name and points are required"]);
    exit;
}

try {
    $conn = getConnection();
    
    $stmt = $conn->prepare("
        INSERT INTO rewards (reward_name, points_required, stock, description, is_active, created_at) 
        VALUES (?, ?, ?, ?, 1, NOW())
    ");
    $stmt->bind_param("siis", $name, $pts, $stock, $desc);
    $ok = $stmt->execute();
    $newId = $stmt->insert_id;
    $stmt->close();
    // DO NOT CLOSE - connection is shared
    
    if (!$ok) {
        throw new Exception("Failed to add reward");
    }
    
    echo json_encode(["success" => true, "id" => $newId]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>