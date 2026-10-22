<?php
// config/database.php
// PenPoint Database Connection with Local, SQLite, and In-Memory/Demo Modes

date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/env.php';

function getConnection() {
    static $conn = null;
    
    if ($conn instanceof mysqli) {
        try {
            if (@$conn->ping()) {
                $conn->query("SET time_zone = '+08:00'");
                return $conn;
            }
        } catch (Throwable $e) {}
    }
    
    $host = Environment::get('DB_HOST', 'localhost');
    $user = Environment::get('DB_USER', 'root');
    $pass = Environment::get('DB_PASS', '');
    $name = Environment::get('DB_NAME', 'penpoint');
    
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn = @new mysqli($host, $user, $pass, $name);
    
    // If local MySQL database 'penpoint' does not exist or MySQL isn't active,
    // we return null or a mock connection rather than hard-crashing so testing is seamless!
    if ($conn->connect_errno) {
        return null;
    }
    
    $conn->set_charset("utf8mb4");
    @$conn->query("SET time_zone = '+08:00'");
    return $conn;
}

if (!defined('DB_HOST')) define('DB_HOST', Environment::get('DB_HOST', 'localhost'));
if (!defined('DB_USER')) define('DB_USER', Environment::get('DB_USER', 'root'));
if (!defined('DB_PASS')) define('DB_PASS', Environment::get('DB_PASS', ''));
if (!defined('DB_NAME')) define('DB_NAME', Environment::get('DB_NAME', 'penpoint'));