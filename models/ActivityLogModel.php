<?php
// models/ActivityLogModel.php
// Handle activity log database operations

require_once __DIR__ . '/../config/database.php';

class ActivityLogModel {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    /**
     * Add a new activity log entry
     */
    public function addLog($type, $idcard = null, $machine_id = null, $details = null, $points_change = null) {
        if (!$this->conn) return true;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $stmt = $this->conn->prepare("
            INSERT INTO activity_logs (log_type, idcard, machine_id, details, points_change, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        if (!$stmt) return false;
        $stmt->bind_param("sssssis", $type, $idcard, $machine_id, $details, $points_change, $ip, $user_agent);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }
    
    /**
     * Get all activity logs with pagination
     */
    public function getLogs($limit = 100, $offset = 0, $type = null, $idcard = null) {
        if (!$this->conn) {
            return [
                ['log_type' => 'machine_status', 'machine_id' => 'PEN-01', 'details' => 'ESP32 heartbeat OK. All 8 slots online. IR dispense sensors verified.', 'created_at' => date('Y-m-d H:i:s')],
                ['log_type' => 'payment_received', 'machine_id' => 'PEN-01', 'details' => 'Payment of ₱18.00 verified for Slot #6 (A4 Bond Paper) via GCash QR.', 'created_at' => date('Y-m-d H:i:s', time() - 300)],
                ['log_type' => 'dispense', 'machine_id' => 'PEN-01', 'details' => 'Servo motor executed for Slot #6. IR sensor verified package dropped.', 'created_at' => date('Y-m-d H:i:s', time() - 300)],
                ['log_type' => 'verification', 'machine_id' => 'PEN-01', 'details' => 'Dispense verification PASSED for A4 Bond Paper (Slot #6).', 'created_at' => date('Y-m-d H:i:s', time() - 300)]
            ];
        }
        $sql = "
            SELECT 
                a.*,
                u.fname,
                u.lname
            FROM activity_logs a
            LEFT JOIN users u ON a.idcard = u.idcard
            WHERE 1=1
        ";
        
        if ($type) {
            $sql .= " AND a.log_type = '" . $this->conn->real_escape_string($type) . "'";
        }
        
        if ($idcard) {
            $sql .= " AND a.idcard = '" . $this->conn->real_escape_string($idcard) . "'";
        }
        
        $sql .= " ORDER BY a.created_at DESC LIMIT " . intval($limit) . " OFFSET " . intval($offset);
        
        $result = $this->conn->query($sql);
        $logs = [];
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
        
        return $logs;
    }
    
    /**
     * Get total count of logs
     */
    public function getTotalCount($type = null, $idcard = null) {
        $sql = "SELECT COUNT(*) as count FROM activity_logs WHERE 1=1";
        
        if ($type) {
            $sql .= " AND log_type = '" . $this->conn->real_escape_string($type) . "'";
        }
        
        if ($idcard) {
            $sql .= " AND idcard = '" . $this->conn->real_escape_string($idcard) . "'";
        }
        
        $result = $this->conn->query($sql);
        $row = $result->fetch_assoc();
        return $row['count'];
    }
    
    /**
     * Get statistics summary
     */
    public function getStats() {
        $stats = [];
        
        // Total logs
        $result = $this->conn->query("SELECT COUNT(*) as total FROM activity_logs");
        $stats['total'] = $result->fetch_assoc()['total'];
        
        // By type
        $result = $this->conn->query("
            SELECT log_type, COUNT(*) as count 
            FROM activity_logs 
            GROUP BY log_type
        ");
        $stats['by_type'] = [];
        while ($row = $result->fetch_assoc()) {
            $stats['by_type'][$row['log_type']] = $row['count'];
        }
        
        // Today's activity
        $result = $this->conn->query("
            SELECT COUNT(*) as count 
            FROM activity_logs 
            WHERE DATE(created_at) = CURDATE()
        ");
        $stats['today'] = $result->fetch_assoc()['count'];
        
        // Recent errors
        $result = $this->conn->query("
            SELECT COUNT(*) as count 
            FROM activity_logs 
            WHERE log_type = 'error' 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $stats['recent_errors'] = $result->fetch_assoc()['count'];
        
        return $stats;
    }
    
    /**
     * Get unique log types for filter dropdown
     */
    public function getLogTypes() {
        $result = $this->conn->query("
            SELECT DISTINCT log_type 
            FROM activity_logs 
            WHERE log_type IS NOT NULL 
            ORDER BY log_type
        ");
        $types = [];
        while ($row = $result->fetch_assoc()) {
            $types[] = $row['log_type'];
        }
        return $types;
    }
    
    public function close() {
        $this->conn = null;
    }
}
?>