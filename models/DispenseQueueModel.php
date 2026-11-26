<?php
// models/DispenseQueueModel.php
// Hardware Dispense Queue & ESP32 Microcontroller Integration Model

require_once __DIR__ . '/../config/database.php';

class DispenseQueueModel {
    private $conn;

    public function __construct() {
        $this->conn = getConnection();
    }

    /**
     * Enqueue a new hardware dispense command
     */
    public function enqueue($machine_id, $slot_number, $product_name, $quantity = 1, $mechanism = 'servo_paddle', $tier_number = 1, $pin_used = 16, $product_id = null, $command_id = null) {
        if (!$this->conn) return false;

        if (!$command_id) {
            $command_id = 'CMD-' . preg_replace('/[^A-Za-z0-9_-]/', '', $machine_id) . '-' . time() . '-' . rand(100, 999);
        }

        $stmt = $this->conn->prepare("
            INSERT INTO dispense_queue 
            (command_id, machine_id, slot_number, product_id, product_name, quantity, mechanism, tier_number, pin_used, status, ir_verified)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'PENDING', 0)
        ");

        if (!$stmt) return false;

        $stmt->bind_param("ssiisisii", 
            $command_id, $machine_id, $slot_number, $product_id, $product_name, 
            $quantity, $mechanism, $tier_number, $pin_used
        );

        $success = $stmt->execute();
        $insertId = $stmt->insert_id;
        $stmt->close();

        return $success ? ['id' => $insertId, 'command_id' => $command_id] : false;
    }

    /**
     * Get the oldest PENDING command for a machine
     */
    public function getNextPending($machine_id = 'PEN-01') {
        if (!$this->conn) return null;

        $stmt = $this->conn->prepare("
            SELECT * FROM dispense_queue 
            WHERE machine_id = ? AND status = 'PENDING'
            ORDER BY id ASC LIMIT 1
        ");

        if (!$stmt) return null;

        $stmt->bind_param("s", $machine_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }

    /**
     * Get a command by its unique command_id
     */
    public function getByCommandId($command_id) {
        if (!$this->conn) return null;

        $stmt = $this->conn->prepare("
            SELECT * FROM dispense_queue 
            WHERE command_id = ? 
            LIMIT 1
        ");

        if (!$stmt) return null;

        $stmt->bind_param("s", $command_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }

    /**
     * Update command status
     */
    public function updateStatus($command_id, $status, $ir_verified = null, $details = null) {
        if (!$this->conn) return false;

        $validStatuses = ['PENDING', 'DISPENSING', 'COMPLETED', 'FAILED', 'TIMEOUT'];
        if (!in_array($status, $validStatuses)) return false;

        if ($ir_verified !== null && $details !== null) {
            $stmt = $this->conn->prepare("
                UPDATE dispense_queue 
                SET status = ?, ir_verified = ?, details = ?, updated_at = NOW() 
                WHERE command_id = ?
            ");
            $irInt = $ir_verified ? 1 : 0;
            $stmt->bind_param("siss", $status, $irInt, $details, $command_id);
        } elseif ($ir_verified !== null) {
            $stmt = $this->conn->prepare("
                UPDATE dispense_queue 
                SET status = ?, ir_verified = ?, updated_at = NOW() 
                WHERE command_id = ?
            ");
            $irInt = $ir_verified ? 1 : 0;
            $stmt->bind_param("sis", $status, $irInt, $command_id);
        } else {
            $stmt = $this->conn->prepare("
                UPDATE dispense_queue 
                SET status = ?, updated_at = NOW() 
                WHERE command_id = ?
            ");
            $stmt->bind_param("ss", $status, $command_id);
        }

        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    /**
     * Get recent queue items
     */
    public function getRecent($machine_id = null, $limit = 15) {
        if (!$this->conn) return [];

        $limit = max(1, min(50, intval($limit)));

        if ($machine_id) {
            $stmt = $this->conn->prepare("
                SELECT * FROM dispense_queue 
                WHERE machine_id = ? 
                ORDER BY id DESC LIMIT ?
            ");
            $stmt->bind_param("si", $machine_id, $limit);
        } else {
            $stmt = $this->conn->prepare("
                SELECT * FROM dispense_queue 
                ORDER BY id DESC LIMIT ?
            ");
            $stmt->bind_param("i", $limit);
        }

        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($r = $res->fetch_assoc()) {
            $rows[] = $r;
        }
        $stmt->close();
        return $rows;
    }

    /**
     * Clear queue
     */
    public function clearQueue($machine_id = null) {
        if (!$this->conn) return false;

        if ($machine_id) {
            $stmt = $this->conn->prepare("DELETE FROM dispense_queue WHERE machine_id = ?");
            $stmt->bind_param("s", $machine_id);
        } else {
            $stmt = $this->conn->prepare("DELETE FROM dispense_queue");
        }

        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
}
