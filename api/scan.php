<?php
// api/scan.php - RFID Scanner Endpoint
// Called when a student scans their ID card

require_once '../config/config.php';
require_once '../config/database.php';

header('Content-Type: application/json');

$conn = getConnection();

// Get card UID from scanner (sent via POST or GET)
$carddata = $_POST['carddata'] ?? $_GET['carddata'] ?? null;
$machine_id = $_POST['machine_id'] ?? $_GET['machine_id'] ?? 'M001';

if (!$carddata) {
    echo json_encode(['success' => false, 'error' => 'No card detected']);
    exit;
}

// Look up the user
$stmt = $conn->prepare("SELECT idcard, fname, lname, current_points FROM users WHERE idcard = ?");
$stmt->bind_param("s", $carddata);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        'success' => false, 
        'error' => 'Card not registered',
        'carddata' => $carddata
    ]);
    exit;
}

$user = $result->fetch_assoc();

// Card is valid - return user info
echo json_encode([
    'success' => true,
    'carddata' => $carddata,
    'user' => [
        'name' => trim($user['fname'] . ' ' . $user['lname']),
        'points' => $user['current_points']
    ],
    'message' => 'Card accepted'
]);

// DO NOT CLOSE - connection is shared
?>