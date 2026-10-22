<?php
// models/UserModel.php
// All database queries related to users

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';

class UserModel {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    // Check if a card exists
    public function findByCard($idcard) {
        $stmt = $this->conn->prepare("
            SELECT 
                id, idcard, fname, mname, lname, 
                current_points, total_points_earned, total_items_recycled,
                created_at
            FROM users 
            WHERE idcard = ?
        ");
        $stmt->bind_param("s", $idcard);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        return $user;
    }
    
    // Create a new user account with just the card ID
    public function createUser($idcard) {
        $stmt = $this->conn->prepare("
            INSERT INTO users (idcard, current_points, total_points_earned, total_items_recycled) 
            VALUES (?, 0, 0, 0)
        ");
        $stmt->bind_param("s", $idcard);
        $success = $stmt->execute();
        $insertId = $stmt->insert_id;
        $stmt->close();
        
        return $success ? $insertId : false;
    }
    
    // Add points to a user and update totals
    public function addPoints($idcard, $points, $bottleCount = 1) {
        $this->conn->begin_transaction();
        
        try {
            // Update current points and totals
            $stmt = $this->conn->prepare("
                UPDATE users 
                SET 
                    current_points = current_points + ?,
                    total_points_earned = total_points_earned + ?,
                    total_items_recycled = total_items_recycled + ?
                WHERE idcard = ?
            ");
            $stmt->bind_param("iiis", $points, $points, $bottleCount, $idcard);
            $success = $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();
            
            if (!$success || $affected === 0) {
                throw new Exception("Failed to update user points");
            }
            
            $this->conn->commit();
            return true;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Error adding points: " . $e->getMessage());
            return false;
        }
    }
    
    // Deduct points (for redemptions)
    public function deductPoints($idcard, $points) {
        $stmt = $this->conn->prepare("
            UPDATE users 
            SET current_points = current_points - ? 
            WHERE idcard = ? AND current_points >= ?
        ");
        $stmt->bind_param("isi", $points, $idcard, $points);
        $success = $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        
        return $success && $affected > 0;
    }
    
    // Update user name
    public function updateName($idcard, $fullName) {
        $parts = preg_split('/\s+/', trim($fullName));
        $fname = $parts[0] ?? '';
        $lname = count($parts) > 1 ? $parts[count($parts) - 1] : '';
        $mname = count($parts) > 2 ? $parts[1] : '';
        
        $stmt = $this->conn->prepare("
            UPDATE users 
            SET fname = ?, mname = ?, lname = ? 
            WHERE idcard = ?
        ");
        $stmt->bind_param("ssss", $fname, $mname, $lname, $idcard);
        $success = $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();
        
        return $success;
    }
    
    // Get all users with stats
    public function getAllUsers($orderBy = 'total_points_earned DESC') {
        $validOrders = ['total_points_earned DESC', 'current_points DESC', 'created_at DESC', 'total_items_recycled DESC'];
        if (!in_array($orderBy, $validOrders)) {
            $orderBy = 'total_points_earned DESC';
        }
        
        $result = $this->conn->query("
            SELECT 
                id, idcard, fname, mname, lname,
                current_points, 
                COALESCE(total_points_earned, 0) as total_points_earned,
                COALESCE(total_items_recycled, 0) as total_items_recycled,
                created_at
            FROM users 
            ORDER BY {$orderBy}
        ");
        
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $row['full_name'] = trim($row['fname'] . ' ' . ($row['mname'] ? $row['mname'] . ' ' : '') . $row['lname']);
            $users[] = $row;
        }
        
        return $users;
    }
    
    // Get statistics
    public function getStats() {
        $result = $this->conn->query("
            SELECT 
                COUNT(*) as total_users,
                COALESCE(SUM(current_points), 0) as total_current_points,
                COALESCE(SUM(total_points_earned), 0) as total_points_earned,
                COALESCE(SUM(total_items_recycled), 0) as total_bottles
            FROM users
        ");
        
        return $result->fetch_assoc();
    }
    
    public function close() {
        // DO NOT CLOSE - connection is shared
        $this->conn = null;
    }
}
?>