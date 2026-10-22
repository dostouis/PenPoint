<?php
// api/update_user_name.php
// Update user's name

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');

require_once __DIR__ . '/../models/UserModel.php';

$carddata = trim($_GET['carddata'] ?? $_POST['carddata'] ?? '');
$name = trim($_GET['name'] ?? $_POST['name'] ?? '');

if ($carddata === '' || $name === '') {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "carddata and name are required"]);
    exit;
}

try {
    $userModel = new UserModel();
    
    // Check if user exists
    $user = $userModel->findByCard($carddata);
    if (!$user) {
        http_response_code(404);
        echo json_encode(["success" => false, "error" => "User not found"]);
        // DO NOT CLOSE - connection is shared
        exit;
    }
    
    // Update name
    $updated = $userModel->updateName($carddata, $name);
    
    if ($updated) {
        echo json_encode(["success" => true, "message" => "Name updated successfully"]);
    } else {
        echo json_encode(["success" => false, "error" => "Failed to update name"]);
    }
    
    // DO NOT CLOSE - connection is shared
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>