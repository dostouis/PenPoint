<?php
// models/TransactionModel.php
// All database queries related to transactions

require_once __DIR__ . '/../config/database.php';

class TransactionModel {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    // Check if a transaction key was already used
    public function exists($transaction_key) {
        $stmt = $this->conn->prepare("
            SELECT id FROM transactions WHERE transaction_key = ?
        ");
        $stmt->bind_param("s", $transaction_key);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        
        return $exists;
    }
    
    // Save a new transaction
    public function create($machine_id, $transaction_key, $idcard, $points, $item_count = 1) {
        // Check for duplicate first
        if ($this->exists($transaction_key)) {
            return ["success" => false, "error" => "duplicate transaction"];
        }
        
        $stmt = $this->conn->prepare("
            INSERT INTO transactions (machine_id, transaction_key, idcard, received_points, item_count, transaction_date) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("sssii", $machine_id, $transaction_key, $idcard, $points, $item_count);
        $success = $stmt->execute();
        $insertId = $stmt->insert_id;
        $stmt->close();
        
        if ($success) {
            return ["success" => true, "id" => $insertId];
        } else {
            return ["success" => false, "error" => "failed to save transaction"];
        }
    }
    
    // Create PenPoint transaction with slot, product, and status
    public function logDispense($machine_id, $txn_key, $idcard, $product_id, $product_name, $slot, $amount, $payment_method, $dispense_status, $ir_verified, $reference_no) {
        $stmt = $this->conn->prepare("
            INSERT INTO transactions (transaction_key, machine_id, idcard, product_id, product_name, slot_number, amount, payment_method, dispense_status, ir_sensor_verified, reference_no, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        if (!$stmt) return false;
        $stmt->bind_param("sssisidssis", $txn_key, $machine_id, $idcard, $product_id, $product_name, $slot, $amount, $payment_method, $dispense_status, $ir_verified, $reference_no);
        $res = $stmt->execute();
        $stmt->close();
        return $res;
    }

    // Update status (e.g. REFUNDED)
    public function updateStatus($transaction_key, $status) {
        $stmt = $this->conn->prepare("UPDATE transactions SET dispense_status = ? WHERE transaction_key = ?");
        if (!$stmt) return false;
        $stmt->bind_param("ss", $status, $transaction_key);
        $res = $stmt->execute();
        $stmt->close();
        return $res;
    }

    // Search transactions by reference_no, transaction_key, or idcard
    public function searchOrders($query, $limit = 20) {
        $query = trim($query);
        $likeQuery = "%{$query}%";
        $stmt = $this->conn->prepare("
            SELECT 
                t.*,
                DATE_FORMAT(t.created_at, '%b %d, %Y • %h:%i %p') as formatted_date
            FROM transactions t
            WHERE t.reference_no = ? 
               OR t.transaction_key = ? 
               OR t.idcard = ?
               OR t.product_name LIKE ?
            ORDER BY t.created_at DESC
            LIMIT ?
        ");
        if (!$stmt) return [];
        $stmt->bind_param("ssssi", $query, $query, $query, $likeQuery, $limit);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($r = $res->fetch_assoc()) {
            $rows[] = $r;
        }
        $stmt->close();
        return $rows;
    }

    // Get latest transactions for a guest/student
    public function getRecentReceipts($limit = 6) {
        $stmt = $this->conn->prepare("
            SELECT 
                t.*,
                DATE_FORMAT(t.created_at, '%b %d, %Y • %h:%i %p') as formatted_date
            FROM transactions t
            ORDER BY t.created_at DESC
            LIMIT ?
        ");
        if (!$stmt) return [];
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($r = $res->fetch_assoc()) {
            $rows[] = $r;
        }
        $stmt->close();
        return $rows;
    }

    // Get all transactions
    public function getAllTransactions($limit = 100) {
        $limit = (int)$limit;
        if ($limit < 1) $limit = 100;
        if ($limit > 1000) $limit = 1000;
        
        $stmt = $this->conn->prepare("
            SELECT 
                t.*,
                DATE_FORMAT(t.created_at, '%Y-%m-%d %H:%i:%s') as transaction_date,
                u.fname,
                u.lname
            FROM transactions t
            LEFT JOIN users u ON t.idcard = u.idcard
            ORDER BY t.created_at DESC 
            LIMIT ?
        ");
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $txns = [];
        while ($row = $result->fetch_assoc()) {
            $txns[] = $row;
        }
        
        $stmt->close();
        return $txns;
    }
    
    // Get transaction count
    public function getCount() {
        $result = $this->conn->query("SELECT COUNT(*) as count FROM transactions");
        $row = $result->fetch_assoc();
        return $row['count'];
    }
    
    public function close() {
        // DO NOT CLOSE - connection is shared
        $this->conn = null;
    }
}
?>