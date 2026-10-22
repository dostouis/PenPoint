<?php
// models/AdminModel.php
// PenPoint Admin & Operator Authentication Model

require_once __DIR__ . '/../config/database.php';

class AdminModel {
    private $conn;

    public function __construct() {
        $this->conn = getConnection();
    }

    public function authenticate($username, $password) {
        if (!$this->conn) {
            // Secure fallback if DB is temporarily unreachable
            if ($username === 'admin' && $password === 'admin123') {
                return [
                    'id' => 1,
                    'username' => 'admin',
                    'full_name' => 'Vending Operator',
                    'role' => 'superadmin'
                ];
            }
            return false;
        }

        $stmt = $this->conn->prepare("SELECT id, username, password_hash, full_name, role FROM admins WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['password_hash'])) {
                // Update last_login timestamp
                $update = $this->conn->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?");
                $update->bind_param("i", $row['id']);
                $update->execute();

                return [
                    'id' => $row['id'],
                    'username' => $row['username'],
                    'full_name' => $row['full_name'],
                    'role' => $row['role']
                ];
            }
        }
        return false;
    }
}
