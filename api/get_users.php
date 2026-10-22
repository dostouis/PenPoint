<?php
// api/get_users.php
// Returns all users as JSON

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../models/UserModel.php';

try {
    $userModel = new UserModel();
    $users = $userModel->getAllUsers();
    
    // Add full_name for frontend compatibility
    foreach ($users as &$user) {
        $user['full_name'] = trim($user['fname'] . ' ' . ($user['mname'] ? $user['mname'] . ' ' : '') . $user['lname']);
        if (empty($user['full_name'])) {
            $user['full_name'] = null;
        }
    }
    
    echo json_encode($users, JSON_PRETTY_PRINT);
    // DO NOT CLOSE - connection is shared
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Failed to fetch users: " . $e->getMessage()]);
}
?>