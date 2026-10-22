<?php
// api/validatecarddata.php
// Called by Arduino when a card is tapped
// Required GET params: carddata, machine_id

header('Content-Type: text/plain');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

require_once __DIR__ . '/../models/MachineModel.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/ActivityLogModel.php';

// Get parameters
$carddata = trim($_GET['carddata'] ?? '');
$machine_id = trim($_GET['machine_id'] ?? '');

// Validate required fields
if (!$carddata || !$machine_id) {
    echo "ERROR: Missing parameters (carddata and machine_id required)";
    exit;
}

try {
    // Authenticate machine
    $machineModel = new MachineModel();
    $auth = $machineModel->authenticate($machine_id);
    // DO NOT CLOSE - connection is shared
    
    if (!$auth['success']) {
        // Log failed machine auth
        $logModel = new ActivityLogModel();
        $logModel->addLog('error', $carddata, $machine_id, 'Machine authentication failed: ' . $auth['error']);
        echo "ERROR: " . $auth['error'];
        exit;
    }
    
    // Check if card exists
    $userModel = new UserModel();
    $user = $userModel->findByCard($carddata);
    
    // Log the scan
    $logModel = new ActivityLogModel();
    
    if ($user) {
        // Card found - return points and name
        $name = trim(($user['fname'] ?? '') . ' ' . ($user['lname'] ?? ''));
        if (empty($name)) $name = 'User';
        
        // Log successful scan
        $logModel->addLog('scan', $carddata, $machine_id, 'Card validated: existing user - ' . $name);
        
        echo "OK|" . ($user['current_points'] ?? 0) . "|" . $name;
    } else {
        // Card not found - register new account
        $created = $userModel->createUser($carddata);
        if ($created) {
            // Log new user creation
            $logModel->addLog('registration', $carddata, $machine_id, 'New card registered automatically via scan');
            echo "NEW|Account created successfully, please tap again";
        } else {
            $logModel->addLog('error', $carddata, $machine_id, 'Failed to create account');
            echo "ERROR: Failed to create account";
        }
    }
    
    // DO NOT CLOSE - connection is shared
    
} catch (Exception $e) {
    // Log error
    if (isset($logModel)) {
        $logModel->addLog('error', $carddata, $machine_id, 'Exception: ' . $e->getMessage());
    }
    echo "ERROR: " . $e->getMessage();
}
?>