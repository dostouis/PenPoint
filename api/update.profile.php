<?php
// api/update_profile.php
// Update user's name and/or user_type

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/ActivityLogModel.php';

$carddata  = trim($_GET['carddata']   ?? $_POST['carddata']   ?? '');
$fname     = trim($_GET['fname']      ?? $_POST['fname']      ?? '');
$mname     = trim($_GET['mname']      ?? $_POST['mname']      ?? '');
$lname     = trim($_GET['lname']      ?? $_POST['lname']      ?? '');
$user_type = trim($_GET['user_type']  ?? $_POST['user_type']  ?? '');

$logModel = new ActivityLogModel();

if ($carddata === '') {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "carddata is required"]);
    exit;
}

$validTypes = ['student', 'faculty', 'worker', 'outsider', ''];
if (!in_array($user_type, $validTypes)) {
    http_response_code(400);
    echo json_encode(["success" => false, "error" => "Invalid user type"]);
    exit;
}

try {
    $conn = getConnection();

    // Check user exists
    $check = $conn->prepare("SELECT id, fname, lname FROM users WHERE idcard = ?");
    $check->bind_param("s", $carddata);
    $check->execute();
    $res = $check->get_result();
    
    if ($res->num_rows === 0) {
        http_response_code(404);
        echo json_encode(["success" => false, "error" => "User not found"]);
        $logModel->addLog('error', $carddata, null, 'Profile update failed: User not found');
        exit;
    }
    $oldUser = $res->fetch_assoc();
    $check->close();

    // Build update — user_type can be NULL if empty string passed
    $typeVal = $user_type !== '' ? $user_type : null;

    $stmt = $conn->prepare("
        UPDATE users
        SET fname = ?, mname = ?, lname = ?, user_type = ?
        WHERE idcard = ?
    ");
    $stmt->bind_param("sssss", $fname, $mname, $lname, $typeVal, $carddata);
    $ok = $stmt->execute();
    $stmt->close();

    if ($ok) {
        // ✅ ADD ACTIVITY LOG HERE
        $changes = [];
        if ($oldUser['fname'] !== $fname) $changes[] = "name changed";
        if ($user_type) $changes[] = "type set to $user_type";
        $changeDesc = !empty($changes) ? implode(', ', $changes) : 'profile accessed';
        
        $logModel->addLog('profile_update', $carddata, null, "Profile updated: $fname $lname ($changeDesc)");
        
        echo json_encode(["success" => true, "message" => "Profile updated"]);
    } else {
        throw new Exception("Update failed");
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
    $logModel->addLog('error', $carddata, null, 'Profile update error: ' . $e->getMessage());
}
?>