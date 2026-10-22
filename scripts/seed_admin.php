<?php
// scripts/seed_admin.php
require_once __DIR__ . '/../config/database.php';

$conn = getConnection();
if ($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        role VARCHAR(30) DEFAULT 'operator',
        full_name VARCHAR(100) DEFAULT 'System Operator',
        last_login DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $hash = password_hash('admin123', PASSWORD_BCRYPT);
    $stmt = $conn->prepare("INSERT INTO admins (username, password_hash, role, full_name)
        VALUES ('admin', ?, 'superadmin', 'Vending Operator')
        ON DUPLICATE KEY UPDATE password_hash = ?");
    $stmt->bind_param('ss', $hash, $hash);
    $stmt->execute();

    echo "Admin user seeded successfully with bcrypt hash!\n";
} else {
    echo "Could not connect to database.\n";
}
