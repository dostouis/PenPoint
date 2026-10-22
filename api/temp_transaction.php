<?php
// api/temp_transaction.php
// ESP32 endpoint for temporary transactions
// Actions: start, update, status, commit, cancel

header('Content-Type: text/plain');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/TempTransactionModel.php';
require_once __DIR__ . '/../models/MachineModel.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/TransactionModel.php';
require_once __DIR__ . '/../models/ActivityLogModel.php';

// Get parameters
$action = trim($_GET['action'] ?? $_POST['action'] ?? '');
$carddata = trim($_GET['carddata'] ?? $_POST['carddata'] ?? '');
$machine_id = trim($_GET['machine_id'] ?? $_POST['machine_id'] ?? '');
$bottle_count = (int)($_GET['bottle_count'] ?? $_POST['bottle_count'] ?? 0);
$transaction_key = trim($_GET['transaction_key'] ?? $_POST['transaction_key'] ?? '');
$status = trim($_GET['status'] ?? $_POST['status'] ?? '');

// Validate machine
if ($machine_id) {
    $machineModel = new MachineModel();
    $auth = $machineModel->authenticate($machine_id);
    if (!$auth['success']) {
        die("ERROR:machine_invalid");
    }
}

$tempModel = new TempTransactionModel();
$logModel = new ActivityLogModel();

// Handle different actions
switch ($action) {
    case 'start':
        // Start a new temporary transaction
        if (!$carddata || !$machine_id) {
            die("ERROR:missing_params");
        }
        
        // Check if user exists
        $userModel = new UserModel();
        $user = $userModel->findByCard($carddata);
        if (!$user) {
            $userModel->createUser($carddata);
            $logModel->addLog('registration', $carddata, $machine_id, 'User auto-created via temp transaction');
        }
        
        // Check for existing pending transaction
        $existing = $tempModel->getPending($carddata, $machine_id);
        if ($existing) {
            // Return existing session
            echo "PENDING|" . $existing['id'] . "|" . $existing['bottle_count'] . "|" . $existing['points_to_add'] . "|" . $existing['transaction_key'];
            exit;
        }
        
        // Create new temp transaction
        $points = $bottle_count * POINTS_PER_BOTTLE;
        $txn_key = $transaction_key ?: $machine_id . '_' . $carddata . '_' . time();
        
        $id = $tempModel->create($machine_id, $carddata, $bottle_count, $points, $txn_key);
        
        if ($id) {
            $logModel->addLog('scan', $carddata, $machine_id, "Temp transaction started: $bottle_count bottles, $points points");
            echo "STARTED|" . $id . "|" . $bottle_count . "|" . $points . "|" . $txn_key;
        } else {
            echo "ERROR:create_failed";
        }
        break;
        
    case 'update':
        // Update bottle count for existing temp transaction
        if (!$carddata || !$machine_id) {
            die("ERROR:missing_params");
        }
        
        $existing = $tempModel->getPending($carddata, $machine_id);
        if (!$existing) {
            die("ERROR:no_pending_session");
        }
        
        $new_bottle_count = $existing['bottle_count'] + $bottle_count;
        $new_points = $new_bottle_count * POINTS_PER_BOTTLE;
        
        $updated = $tempModel->updateBottleCount($existing['id'], $new_bottle_count, $new_points);
        
        if ($updated) {
            $logModel->addLog('scan', $carddata, $machine_id, "Temp transaction updated: +$bottle_count bottles, total $new_bottle_count");
            echo "UPDATED|" . $existing['id'] . "|" . $new_bottle_count . "|" . $new_points;
        } else {
            echo "ERROR:update_failed";
        }
        break;
        
    case 'status':
        // Get status of current temp transaction
        if (!$carddata || !$machine_id) {
            die("ERROR:missing_params");
        }
        
        $existing = $tempModel->getPending($carddata, $machine_id);
        if ($existing) {
            echo "PENDING|" . $existing['id'] . "|" . $existing['bottle_count'] . "|" . $existing['points_to_add'] . "|" . $existing['status'];
        } else {
            echo "NONE|No pending transaction";
        }
        break;
        
    case 'commit':
        // Commit temp transaction to permanent transactions table
        if (!$carddata || !$machine_id) {
            die("ERROR:missing_params");
        }
        
        $existing = $tempModel->getPending($carddata, $machine_id);
        if (!$existing) {
            die("ERROR:no_pending_session");
        }
        
        $conn = getConnection();
        $conn->begin_transaction();
        
        try {
            // Create permanent transaction
            $txnModel = new TransactionModel();
            $final_txn_key = $transaction_key ?: $existing['transaction_key'] . '_final';
            
            // Check for duplicate
            if ($txnModel->exists($final_txn_key)) {
                throw new Exception("duplicate_transaction");
            }
            
            // Save to transactions table
            $txnResult = $txnModel->create(
                $machine_id, 
                $final_txn_key, 
                $carddata, 
                $existing['points_to_add'], 
                $existing['bottle_count']
            );
            
            if (!$txnResult['success']) {
                throw new Exception("txn_failed");
            }
            
            // Add points to user
            $userModel = new UserModel();
            $added = $userModel->addPoints($carddata, $existing['points_to_add'], $existing['bottle_count']);
            
            if (!$added) {
                throw new Exception("points_failed");
            }
            
            // Update temp transaction status to 'done'
            $tempModel->updateStatus($existing['id'], 'done');
            
            $conn->commit();
            
            // Get updated user info
            $updatedUser = $userModel->findByCard($carddata);
            $newBalance = $updatedUser['current_points'] ?? 0;
            
            // Log activity
            $logModel->addLog('transaction', $carddata, $machine_id, 
                "Temp transaction committed: {$existing['bottle_count']} bottles, {$existing['points_to_add']} points", 
                $existing['points_to_add']
            );
            
            echo "COMMITTED|" . $existing['points_to_add'] . "|" . $newBalance . "|" . $existing['bottle_count'];
            
        } catch (Exception $e) {
            $conn->rollback();
            $logModel->addLog('error', $carddata, $machine_id, "Temp transaction commit failed: " . $e->getMessage());
            echo "ERROR:" . $e->getMessage();
        }
        break;
        
    case 'cancel':
        // Cancel temp transaction
        if (!$carddata || !$machine_id) {
            die("ERROR:missing_params");
        }
        
        $existing = $tempModel->getPending($carddata, $machine_id);
        if (!$existing) {
            die("ERROR:no_pending_session");
        }
        
        $cancelled = $tempModel->updateStatus($existing['id'], 'cancelled');
        
        if ($cancelled) {
            $logModel->addLog('scan', $carddata, $machine_id, "Temp transaction cancelled: {$existing['bottle_count']} bottles discarded");
            echo "CANCELLED|" . $existing['id'];
        } else {
            echo "ERROR:cancel_failed";
        }
        break;
        
    case 'unchanged':
        // Mark as unchanged (no commit, but keep record)
        if (!$carddata || !$machine_id) {
            die("ERROR:missing_params");
        }
        
        $existing = $tempModel->getPending($carddata, $machine_id);
        if (!$existing) {
            die("ERROR:no_pending_session");
        }
        
        $updated = $tempModel->updateStatus($existing['id'], 'unchanged');
        
        if ($updated) {
            $logModel->addLog('scan', $carddata, $machine_id, "Temp transaction marked unchanged: {$existing['bottle_count']} bottles");
            echo "UNCHANGED|" . $existing['id'];
        } else {
            echo "ERROR:update_failed";
        }
        break;
        
    default:
        echo "ERROR:invalid_action";
        echo "\nValid actions: start, update, status, commit, cancel, unchanged";
        break;
}
?>