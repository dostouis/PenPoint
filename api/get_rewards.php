<?php
// api/get_rewards.php
// Returns available rewards

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../config/database.php';

try {
    $conn = getConnection();
    
    $result = $conn->query("
        SELECT 
            id, reward_name, description, 
            points_required, stock, is_active,
            created_at
        FROM rewards 
        ORDER BY points_required ASC
    ");
    
    $rewards = [];
    while ($row = $result->fetch_assoc()) {
        $rewards[] = $row;
    }
    
    echo json_encode($rewards, JSON_PRETTY_PRINT);
    // DO NOT CLOSE - connection is shared
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Failed to fetch rewards: " . $e->getMessage()]);
}
?>