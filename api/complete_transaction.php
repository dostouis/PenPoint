<?php
// api/complete_transaction.php
// Called by ESP/Arduino after bottles are processed
// Required GET params: carddata, machine_id, bottle_count

header('Content-Type: text/plain');
header('Access-Control-Allow-Origin: *');
header('X-API-Version: 1.0.0');

// Prevent PHP errors from breaking the ESP response format
error_reporting(0);
ini_set('display_errors', 0);

// Set reasonable timeout for ESP
set_time_limit(10);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/MachineModel.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/TransactionModel.php';
require_once __DIR__ . '/../models/ActivityLogModel.php';

// Get parameters
$carddata = trim($_GET['carddata'] ?? '');
$machine_id = trim($_GET['machine_id'] ?? '');
$bottle_count = (int)($_GET['bottle_count'] ?? $_GET['bottles'] ?? 0);
$transaction_key = trim($_GET['transaction_key'] ?? $_GET['txn_key'] ?? '');

// Simple logging for debugging
$logFile = __DIR__ . '/../logs/transactions.log';
if (!is_dir(dirname($logFile))) {
    mkdir(dirname($logFile), 0755, true);
}

// Validate required fields
if (!$carddata || !$machine_id || $bottle_count <= 0) {
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - ERROR: Missing params - Card: $carddata, Machine: $machine_id, Bottles: $bottle_count\n", FILE_APPEND);
    die("ERROR:missing_params");
}

// Generate transaction key if not provided
if (!$transaction_key) {
    $transaction_key = $machine_id . '_' . $carddata . '_' . time() . '_' . rand(1000, 9999);
}

// Log the request
file_put_contents($logFile, date('Y-m-d H:i:s') . " - REQUEST: Card: $carddata, Machine: $machine_id, Bottles: $bottle_count, Key: $transaction_key\n", FILE_APPEND);

$conn = null;
$logModel = new ActivityLogModel();

try {
    // Get database connection
    $conn = getConnection();
    
    // Start transaction for atomicity
    $conn->begin_transaction();
    
    // Authenticate machine
    $machineModel = new MachineModel();
    $auth = $machineModel->authenticate($machine_id);
    
    if (!$auth['success']) {
        $logModel->addLog('error', $carddata, $machine_id, 'Machine authentication failed: ' . $auth['error']);
        throw new Exception("machine_invalid");
    }
    
    // Calculate points
    $points = $bottle_count * POINTS_PER_BOTTLE;
    
    // Create transaction record
    $txnModel = new TransactionModel();
    
    // Check for duplicate transaction
    if ($txnModel->exists($transaction_key)) {
        // Rollback and return duplicate message (ESP should handle this gracefully)
        $conn->rollback();
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - DUPLICATE: $transaction_key\n", FILE_APPEND);
        $logModel->addLog('error', $carddata, $machine_id, "Duplicate transaction attempted: $transaction_key");
        die("DUPLICATE:already_processed");
    }
    
    // Save transaction
    $txnResult = $txnModel->create($machine_id, $transaction_key, $carddata, $points, $bottle_count);
    
    if (!$txnResult['success']) {
        throw new Exception("txn_failed");
    }
    
    // Add points to user
    $userModel = new UserModel();
    $user = $userModel->findByCard($carddata);
    
    if (!$user) {
        // Auto-create user if they don't exist
        $created = $userModel->createUser($carddata);
        if (!$created) {
            throw new Exception("user_creation_failed");
        }
        $user = $userModel->findByCard($carddata);
        $logModel->addLog('registration', $carddata, $machine_id, 'User auto-created during transaction');
    }
    
    $added = $userModel->addPoints($carddata, $points, $bottle_count);
    
    if (!$added) {
        throw new Exception("points_failed");
    }
    
    // Get updated user info
    $updatedUser = $userModel->findByCard($carddata);
    $newBalance = $updatedUser['current_points'] ?? 0;
    $totalEarned = $updatedUser['total_points_earned'] ?? 0;
    $totalBottles = $updatedUser['total_items_recycled'] ?? 0;
    
    // Commit transaction - everything succeeded
    $conn->commit();
    
    // Log success to file
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - SUCCESS: Card: $carddata, Points: $points, New Balance: $newBalance\n", FILE_APPEND);
    
    // Log activity
    $logModel->addLog('transaction', $carddata, $machine_id, "Recycled $bottle_count bottles, earned $points points", $points);
    
    // Format: SUCCESS|points_added|new_balance|total_earned|bottles_recycled
    echo "SUCCESS|" . $points . "|" . $newBalance . "|" . $totalEarned . "|" . $totalBottles;
    
} catch (Exception $e) {
    // Rollback on any error
    if ($conn) {
        $conn->rollback();
    }
    
    // Log error to file
    $errorMsg = $e->getMessage();
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - ERROR: $errorMsg - Card: $carddata\n", FILE_APPEND);
    
    // Log activity error
    $logModel->addLog('error', $carddata, $machine_id, "Transaction failed: $errorMsg");
    
    // Return ESP-friendly error
    echo "ERROR:" . $errorMsg;
    
}
// DO NOT CLOSE CONNECTIONS - PHP will handle cleanup automatically
?>