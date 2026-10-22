<?php
// models/MachineModel.php
// All database queries related to machines

require_once __DIR__ . '/../config/database.php';

class MachineModel {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    // Check if machine_id is valid and active
    public function authenticate($machine_id) {
        $stmt = $this->conn->prepare("
            SELECT id, machine_id, status, location 
            FROM machines 
            WHERE machine_id = ?
        ");
        $stmt->bind_param("s", $machine_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return ["success" => false, "error" => "machine not found"];
        }
        
        $machine = $result->fetch_assoc();
        $stmt->close();
        
        if (strtoupper($machine['status']) !== 'ACTIVE') {
            return ["success" => false, "error" => "machine is " . strtolower($machine['status'])];
        }
        
        return ["success" => true, "machine" => $machine];
    }
    
    // Get all machines
    public function getAllMachines() {
        if (!$this->conn) {
            return [
                ['machine_id' => 'PEN-01', 'location' => 'Engineering Hallway (Near Room 302)', 'status' => 'ACTIVE'],
                ['machine_id' => 'PEN-02', 'location' => 'Main University Library (2nd Floor)', 'status' => 'ACTIVE'],
                ['machine_id' => 'PEN-03', 'location' => 'Science & Technology Complex', 'status' => 'ACTIVE'],
                ['machine_id' => 'PEN-04', 'location' => 'Senior High School Building Lobby', 'status' => 'MAINTENANCE']
            ];
        }
        $result = $this->conn->query("
            SELECT 
                id, machine_id, location, status,
                created_at,
                CASE 
                    WHEN status = 'ACTIVE' THEN 1 
                    ELSE 0 
                END as is_active
            FROM machines 
            ORDER BY created_at DESC
        ");
        
        $machines = [];
        while ($row = $result->fetch_assoc()) {
            $machines[] = $row;
        }
        
        return $machines;
    }
    
    // Update machine status
    public function updateStatus($machine_id, $status) {
        $validStatuses = ['ACTIVE', 'INACTIVE', 'MAINTENANCE'];
        $status = strtoupper($status);
        
        if (!in_array($status, $validStatuses)) {
            return false;
        }
        
        $stmt = $this->conn->prepare("
            UPDATE machines SET status = ? WHERE machine_id = ?
        ");
        $stmt->bind_param("ss", $status, $machine_id);
        $success = $stmt->execute();
        $stmt->close();
        
        return $success;
    }
    
    public function close() {
        // DO NOT CLOSE - connection is shared
        $this->conn = null;
    }
}
?>