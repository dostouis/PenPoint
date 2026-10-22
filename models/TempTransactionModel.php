<?php
// models/TempTransactionModel.php
// Handle temporary transaction database operations

require_once __DIR__ . '/../config/database.php';

class TempTransactionModel {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    /**
     * Create a new temporary transaction
     */
    public function create($machine_id, $idcard, $bottle_count, $points_to_add, $transaction_key = null) {
        if (!$transaction_key) {
            $transaction_key = $machine_id . '_' . $idcard . '_' . time();
        }
        
        $stmt = $this->conn->prepare("
            INSERT INTO temp_transactions (machine_id, idcard, bottle_count, points_to_add, transaction_key, session_start, status) 
            VALUES (?, ?, ?, ?, ?, NOW(), 'pending')
        ");
        $stmt->bind_param("ssiis", $machine_id, $idcard, $bottle_count, $points_to_add, $transaction_key);
        $success = $stmt->execute();
        $insertId = $stmt->insert_id;
        $stmt->close();
        
        return $success ? $insertId : false;
    }
    
    /**
     * Get pending transaction for a card/machine
     */
    public function getPending($idcard, $machine_id) {
        $stmt = $this->conn->prepare("
            SELECT * FROM temp_transactions 
            WHERE idcard = ? AND machine_id = ? AND status = 'pending'
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->bind_param("ss", $idcard, $machine_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row;
    }
    
    /**
     * Update bottle count for pending transaction
     */
    public function updateBottleCount($id, $bottle_count, $points_to_add) {
        $stmt = $this->conn->prepare("
            UPDATE temp_transactions 
            SET bottle_count = ?, points_to_add = ?, updated_at = NOW()
            WHERE id = ? AND status = 'pending'
        ");
        $stmt->bind_param("iii", $bottle_count, $points_to_add, $id);
        $success = $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        
        return $success && $affected > 0;
    }
    
    /**
     * Update transaction status
     */
    public function updateStatus($id, $status) {
        $validStatuses = ['pending', 'done', 'unchanged', 'cancelled'];
        if (!in_array($status, $validStatuses)) {
            return false;
        }
        
        $stmt = $this->conn->prepare("
            UPDATE temp_transactions 
            SET status = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param("si", $status, $id);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }
    
    /**
     * Get transaction by ID
     */
    public function getById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM temp_transactions WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row;
    }
    
    /**
     * Get transaction by transaction_key
     */
    public function getByKey($transaction_key) {
        $stmt = $this->conn->prepare("SELECT * FROM temp_transactions WHERE transaction_key = ?");
        $stmt->bind_param("s", $transaction_key);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row;
    }
    
    /**
     * Get all pending transactions for a machine
     */
    public function getPendingByMachine($machine_id) {
        $stmt = $this->conn->prepare("
            SELECT t.*, u.fname, u.lname 
            FROM temp_transactions t
            LEFT JOIN users u ON t.idcard = u.idcard
            WHERE t.machine_id = ? AND t.status = 'pending'
            ORDER BY t.created_at DESC
        ");
        $stmt->bind_param("s", $machine_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();
        
        return $rows;
    }
    
    /**
     * Get all transactions with filters
     */
    public function getAll($limit = 100, $offset = 0, $status = null, $machine_id = null) {
        $sql = "
            SELECT t.*, u.fname, u.lname 
            FROM temp_transactions t
            LEFT JOIN users u ON t.idcard = u.idcard
            WHERE 1=1
        ";
        
        if ($status) {
            $sql .= " AND t.status = '" . $this->conn->real_escape_string($status) . "'";
        }
        
        if ($machine_id) {
            $sql .= " AND t.machine_id = '" . $this->conn->real_escape_string($machine_id) . "'";
        }
        
        $sql .= " ORDER BY t.created_at DESC LIMIT " . intval($limit) . " OFFSET " . intval($offset);
        
        $result = $this->conn->query($sql);
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        
        return $rows;
    }
    
    /**
     * Get count of transactions
     */
    public function getCount($status = null, $machine_id = null) {
        $sql = "SELECT COUNT(*) as count FROM temp_transactions WHERE 1=1";
        
        if ($status) {
            $sql .= " AND status = '" . $this->conn->real_escape_string($status) . "'";
        }
        
        if ($machine_id) {
            $sql .= " AND machine_id = '" . $this->conn->real_escape_string($machine_id) . "'";
        }
        
        $result = $this->conn->query($sql);
        $row = $result->fetch_assoc();
        return $row['count'];
    }
    
    public function close() {
        $this->conn = null;
    }
}
?>