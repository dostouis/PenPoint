<?php
// api/get_transactions.php
// Returns transactions as JSON

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../models/TransactionModel.php';

try {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
    
    // Validate limit
    if ($limit < 1) $limit = 100;
    if ($limit > 1000) $limit = 1000;
    
    $txnModel = new TransactionModel();
    $txns = $txnModel->getAllTransactions($limit);
    
    echo json_encode($txns, JSON_PRETTY_PRINT);
    // DO NOT CLOSE - connection is shared
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Failed to fetch transactions: " . $e->getMessage()]);
}
?>