<?php
// admin/index.php
// PenPoint Admin & Telemetry Dashboard
// Theme: Japanese Retro Vending Maintenance & Telemetry Console
// English Console

date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/ProductModel.php';
require_once __DIR__ . '/../models/MachineModel.php';
require_once __DIR__ . '/../models/ActivityLogModel.php';
require_once __DIR__ . '/../models/TransactionModel.php';
require_once __DIR__ . '/../models/AdminModel.php';

// Secure Session Configuration
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

$adminModel = new AdminModel();

// Logout Action
if (isset($_GET['logout'])) {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header('Location: index.php');
    exit;
}

// Generate CSRF token for login
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Rate Limiting Protection (Max 5 attempts per 10 minutes)
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt_time'] = time();
}

// Reset attempts if 10 minutes passed
if (time() - $_SESSION['last_attempt_time'] > 600) {
    $_SESSION['login_attempts'] = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username']) && isset($_POST['password'])) {
    // Validate CSRF token
    $submittedCsrf = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $submittedCsrf)) {
        $login_error = "Security token mismatch. Please try again.";
    } elseif ($_SESSION['login_attempts'] >= 5) {
        $login_error = "Too many failed attempts. Console locked for 10 minutes.";
    } else {
        $username = trim($_POST['username']);
        $password = $_POST['password'];

        $auth = $adminModel->authenticate($username, $password);

        if ($auth) {
            // Regenerate session ID to prevent session fixation attacks
            session_regenerate_id(true);
            $_SESSION['penpoint_admin'] = $auth;
            $_SESSION['login_attempts'] = 0;
            // Generate new CSRF token for logged in session
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header('Location: index.php');
            exit;
        } else {
            $_SESSION['login_attempts']++;
            $_SESSION['last_attempt_time'] = time();
            $remaining = 5 - $_SESSION['login_attempts'];
            $login_error = "Invalid credentials. {$remaining} attempts remaining.";
        }
    }
}

// If not logged in, render Japanese Retro Operator Verification Lock Screen
if (empty($_SESSION['penpoint_admin'])) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#151821">
    <title>Operator Verification — <?= APP_NAME ?></title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Zen+Maru+Gothic:wght@500;700;900&family=Noto+Sans+JP:wght@400;600;700;900&family=Dela+Gothic+One&family=DM+Mono:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --retro-bg: #151821;
            --retro-cream: #fbf7ee;
            --retro-cream-muted: #e6dfcf;
            --retro-red: #d63031;
            --retro-mustard: #f39c12;
            --retro-border: #3d465c;
            --font-display: 'Dela Gothic One', sans-serif;
            --font-body: 'Zen Maru Gothic', 'Noto Sans JP', sans-serif;
            --font-mono: 'DM Mono', monospace;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        /* Custom Themed Scrollbar */
        html { scrollbar-width: thin; scrollbar-color: #3d465c #151821; }
        ::-webkit-scrollbar { width: 7px; height: 7px; }
        ::-webkit-scrollbar-track { background: #151821; border-left: 1px solid #232938; }
        ::-webkit-scrollbar-thumb { background: #3a445b; border-radius: 9999px; }
        ::-webkit-scrollbar-thumb:hover { background: #505e7e; }

        body {
            font-family: var(--font-body);
            background: var(--retro-bg);
            color: var(--retro-cream);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background-image: 
                radial-gradient(circle at 50% 0%, #252c3c 0%, transparent 60%),
                radial-gradient(circle at 10% 100%, #1c212c 0%, transparent 40%);
        }

        body::after {
            content: "";
            position: fixed;
            inset: 0;
            background-image: radial-gradient(rgba(255, 255, 255, 0.04) 1px, transparent 0);
            background-size: 8px 8px;
            pointer-events: none;
            z-index: 998;
        }

        .login-chassis {
            background: #242a3a;
            border: 3px solid #4a5570;
            border-radius: 18px;
            padding: 32px 24px;
            width: 100%;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 8px 0 #10131a, 0 24px 50px rgba(0,0,0,0.8);
            position: relative;
            z-index: 10;
        }

        .operator-stamp-tag {
            display: inline-block;
            background: var(--retro-mustard);
            color: #12151b;
            font-family: 'Dela Gothic One', sans-serif;
            font-size: 0.7rem;
            padding: 3px 10px;
            border-radius: 4px;
            box-shadow: 0 2px 0 rgba(0,0,0,0.25);
            letter-spacing: 0.06em;
            margin-bottom: 12px;
        }

        .lock-icon-housing {
            width: 60px;
            height: 60px;
            background: #181d27;
            border: 2px solid #3d475c;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            color: var(--retro-mustard);
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
        }

        .form-label {
            display: block;
            text-align: left;
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--retro-cream-muted);
            text-transform: uppercase;
            font-family: var(--font-mono);
            letter-spacing: 0.05em;
            margin-bottom: 5px;
        }

        .auth-input-field {
            width: 100%;
            background: #181d27;
            border: 2px solid #3d475c;
            border-radius: 8px;
            padding: 12px 14px;
            color: #ffeaa7;
            font-size: 0.95rem;
            margin-bottom: 14px;
            outline: none;
            font-family: var(--font-mono);
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.5);
            transition: border-color 0.2s;
        }

        .auth-input-field:focus {
            border-color: var(--retro-mustard);
        }

        .btn-retro-unlock {
            width: 100%;
            background: linear-gradient(180deg, #ff4757 0%, var(--retro-red) 100%);
            border: 2px solid #ffffff;
            color: #ffffff;
            box-shadow: 0 4px 0 #7e0c0d, 0 8px 16px rgba(0, 0, 0, 0.4);
            border-radius: 10px;
            padding: 13px;
            font-family: 'Dela Gothic One', sans-serif;
            font-size: 0.95rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.12s;
            letter-spacing: 0.04em;
            margin-top: 6px;
        }

        .btn-retro-unlock:active {
            transform: translateY(3px);
            box-shadow: 0 1px 0 #7e0c0d;
        }

        .error-alert-bar {
            background: rgba(214, 48, 49, 0.2);
            border: 1px solid var(--retro-red);
            color: #ff7675;
            padding: 10px;
            border-radius: 8px;
            font-size: 0.8rem;
            margin-top: 14px;
            margin-bottom: 14px;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="login-chassis">
        <span class="operator-stamp-tag">OPERATOR // SECURE CONSOLE</span>
        <div class="lock-icon-housing">
            <i data-lucide="shield-check" style="width: 28px; height: 28px;"></i>
        </div>
        <h2 style="font-family: var(--font-body); font-weight: 900; font-size: 1.35rem; color: var(--retro-cream);">
            Operator Login
        </h2>
        <p style="font-size: 0.82rem; color: var(--retro-cream-muted); margin-top: 4px; margin-bottom: 16px; line-height: 1.45;">
            Authenticate with verified credentials to access vending maintenance.
        </p>

        <?php if (!empty($login_error)): ?>
            <div class="error-alert-bar">
                <i data-lucide="alert-circle" style="width: 14px; height: 14px; display: inline;"></i>
                <?= htmlspecialchars($login_error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            
            <label class="form-label">Operator Username</label>
            <input type="text" name="username" class="auth-input-field" placeholder="e.g. admin" required autofocus autocomplete="username">

            <label class="form-label">Password</label>
            <input type="password" name="password" class="auth-input-field" placeholder="••••••••" required autocomplete="current-password">

            <button type="submit" class="btn-retro-unlock">
                <i data-lucide="key-round" style="width: 16px; height: 16px;"></i>
                Authenticate & Unlock
            </button>
        </form>

        <div style="margin-top: 20px;">
            <a href="../public/index.php" style="font-size: 0.8rem; color: var(--retro-cream-muted); text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i> Return to Student Kiosk
            </a>
        </div>
    </div>
    <script>lucide.createIcons();</script>
</body>
</html>
<?php
    exit;
}

$productModel = new ProductModel();
$machineModel = new MachineModel();
$logModel = new ActivityLogModel();
$txnModel = new TransactionModel();
$conn = getConnection();

// Handle Actions (Restock, Issue Refund)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    if ($_POST['action'] === 'restock_slot') {
        $slot = intval($_POST['slot_number'] ?? 0);
        $newStock = intval($_POST['stock'] ?? 0);
        if ($slot >= 1 && $slot <= 6) {
            $updated = $productModel->updateStock($slot, $newStock);
            $logModel->addLog('restock', null, DEFAULT_MACHINE_ID, "Restocked slot #{$slot} to {$newStock} units.");
            echo json_encode(['success' => $updated, 'message' => "Slot #{$slot} restocked to {$newStock}"]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid slot number (1-6)']);
        }
        exit;
    }
    if ($_POST['action'] === 'quick_restock') {
        $slot = intval($_POST['slot_number'] ?? 0);
        $addQty = intval($_POST['add_qty'] ?? 5);
        if ($slot >= 1 && $slot <= 6) {
            $updated = $productModel->quickRestock($slot, $addQty);
            $logModel->addLog('quick_restock', null, DEFAULT_MACHINE_ID, "Quick added +{$addQty} units to slot #{$slot}.");
            echo json_encode(['success' => $updated, 'message' => "Slot #{$slot} replenished with +{$addQty} units"]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid slot number']);
        }
        exit;
    }
    if ($_POST['action'] === 'restock_all_max') {
        $updated = $productModel->restockAllToMax();
        $logModel->addLog('restock_all', null, DEFAULT_MACHINE_ID, "Admin triggered full machine restock to maximum capacity on all slots.");
        echo json_encode(['success' => $updated, 'message' => "All slots replenished to maximum capacity!"]);
        exit;
    }
    if ($_POST['action'] === 'issue_refund') {
        $txnKey = trim($_POST['transaction_key'] ?? '');
        if (!empty($txnKey)) {
            $updated = $txnModel->updateStatus($txnKey, 'REFUNDED');
            $logModel->addLog('refund_issued', 'ADMIN_CONSOLE', DEFAULT_MACHINE_ID, "Admin approved manual/auto refund for {$txnKey}.");
            echo json_encode(['success' => $updated, 'message' => "Transaction {$txnKey} refunded successfully"]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Missing transaction key']);
        }
        exit;
    }
}

$products = $productModel->getAllProducts();
$machines = $machineModel->getAllMachines();
$recentLogs = $logModel->getLogs(15);
$pendingRefunds = $txnModel->getPendingRefunds();

// Fetch Machine Sensors
$sensorData = [
    'tier_1_ir' => 'CLEAR',
    'tier_2_ir' => 'CLEAR',
    'tier_3_ir' => 'CLEAR',
    'tier_4_ir' => 'CLEAR',
    'door_switch' => 'CLOSED',
    'last_ping' => date('Y-m-d H:i:s')
];
if ($conn) {
    $resSensors = @$conn->query("SELECT * FROM machine_sensors WHERE machine_id = '" . DEFAULT_MACHINE_ID . "' LIMIT 1");
    if ($resSensors && $rowS = $resSensors->fetch_assoc()) {
        $sensorData = $rowS;
    }
}

// Calculate system metrics
$totalSlots = count($products);
$totalStock = array_sum(array_column($products, 'stock'));
$totalDispensed = array_sum(array_column($products, 'dispensed_count'));
$lowStockItems = 0;
foreach ($products as $p) {
    if ($p['stock'] <= $p['low_stock_threshold']) {
        $lowStockItems++;
    }
}
$estimatedRevenue = 0;
foreach ($products as $p) {
    $estimatedRevenue += ($p['dispensed_count'] * $p['price']);
}
$salesTrend = $txnModel->getDailySalesTrend(7);
$categorySales = $txnModel->getCategoryBreakdown();

// Build continuous 7-day timeline arrays
$trendLabels = [];
$trendOrders = [];
$trendRevenue = [];
$trendMap = [];
foreach ($salesTrend as $row) {
    $trendMap[$row['sale_date']] = $row;
}
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $label = date('M d', strtotime("-$i days"));
    $trendLabels[] = $label;
    if (isset($trendMap[$d])) {
        $trendOrders[] = (int)$trendMap[$d]['orders_count'];
        $trendRevenue[] = (float)$trendMap[$d]['total_revenue'];
    } else {
        $trendOrders[] = 0;
        $trendRevenue[] = 0.0;
    }
}

// Category breakdown data
$catLabels = [];
$catCounts = [];
$catColors = ['#f39c12', '#2ecc71', '#74b9ff', '#d63031', '#9b59b6', '#1abc9c'];
foreach ($categorySales as $c) {
    $catLabels[] = $c['category'];
    $catCounts[] = (int)$c['order_count'];
}
if (empty($catLabels)) {
    $catLabels = ['Paper (Long)', 'Paper (A4)', 'Paper (Short)', 'Pens & Supplies'];
    $catCounts = [0, 0, 0, 0];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#181c24">
    <title><?= APP_NAME ?> Operator Hub — Maintenance Console</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Zen+Maru+Gothic:wght@500;700;900&family=Noto+Sans+JP:wght@400;600;700;900&family=Dela+Gothic+One&family=DM+Mono:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- Chart.js for Business Analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
        :root {
            --retro-bg: #151821;
            --retro-chassis: #212634;
            --retro-panel: #2a3142;
            --retro-cream: #fbf7ee;
            --retro-cream-muted: #e6dfcf;
            --retro-red: #d63031;
            --retro-red-deep: #b71516;
            --retro-matcha: #2e7d32;
            --retro-mustard: #f39c12;
            --retro-border: #3d465c;
            --font-display: 'Dela Gothic One', sans-serif;
            --font-body: 'Zen Maru Gothic', 'Noto Sans JP', sans-serif;
            --font-mono: 'DM Mono', monospace;
            --radius-box: 14px;
            --radius-btn: 10px;
            --radius-pill: 9999px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        /* Custom Themed Scrollbar */
        html { scrollbar-width: thin; scrollbar-color: #3d465c #151821; }
        ::-webkit-scrollbar { width: 7px; height: 7px; }
        ::-webkit-scrollbar-track { background: #151821; border-left: 1px solid #232938; }
        ::-webkit-scrollbar-thumb { background: #3a445b; border-radius: 9999px; border: 1px solid #1c2230; }
        ::-webkit-scrollbar-thumb:hover { background: #505e7e; }
        ::-webkit-scrollbar-thumb:active { background: var(--retro-mustard); }
        ::selection { background: var(--retro-mustard); color: #12151b; }

        body {
            font-family: var(--font-body);
            background-color: var(--retro-bg);
            color: var(--retro-text-light, #fbf7ee);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
            background-image: 
                radial-gradient(circle at 50% 0%, #252c3c 0%, transparent 60%),
                radial-gradient(circle at 10% 100%, #1c212c 0%, transparent 40%);
        }

        body::after {
            content: "";
            position: fixed;
            inset: 0;
            background-image: radial-gradient(rgba(255, 255, 255, 0.04) 1px, transparent 0);
            background-size: 8px 8px;
            pointer-events: none;
            z-index: 998;
        }

        /* Top Bar */
        header {
            position: sticky;
            top: 0;
            z-index: 60;
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            background: rgba(33, 38, 52, 0.94);
            border-bottom: 3px solid #11141c;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.5);
            padding: 10px 18px;
        }

        .header-inner {
            max-width: 1300px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .brand-cluster {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: inherit;
        }

        .retro-stationery-sign {
            width: 42px;
            height: 42px;
            background: linear-gradient(135deg, var(--retro-red), var(--retro-red-deep));
            border: 2px solid #ffffff;
            box-shadow: 0 3px 0 #7e0c0d, 0 6px 12px rgba(0, 0, 0, 0.4);
            border-radius: 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .sign-kanji {
            font-family: 'Dela Gothic One', sans-serif;
            font-size: 0.9rem;
            color: #ffffff;
            line-height: 1;
        }

        .sign-sub-tag {
            font-size: 0.5rem;
            font-family: var(--font-mono);
            letter-spacing: 0.08em;
            color: #ffcccc;
            margin-top: 1px;
        }

        .brand-text h1 {
            font-family: var(--font-display);
            font-size: 1.25rem;
            color: var(--retro-cream);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .brand-jp-badge {
            font-size: 0.62rem;
            font-family: var(--font-body);
            font-weight: 900;
            color: #12151b;
            background: var(--retro-mustard);
            padding: 2px 6px;
            border-radius: 4px;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .retro-link-btn {
            background: #232938;
            border: 2px solid #3c465d;
            box-shadow: 0 3px 0 #12151b;
            color: var(--retro-cream);
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.76rem;
            font-weight: 700;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.12s;
        }

        .retro-link-btn:active {
            transform: translateY(2px);
            box-shadow: 0 1px 0 #12151b;
        }

        .retro-link-btn.active {
            background: var(--retro-mustard);
            color: #12151b;
            border-color: #ffffff;
            box-shadow: 0 3px 0 #b3740e;
        }

        .retro-btn-quick {
            background: #181d28;
            border: 1px solid #3d4a65;
            color: var(--retro-cream);
            padding: 4px 8px;
            border-radius: 5px;
            font-size: 0.7rem;
            font-family: var(--font-mono);
            font-weight: 700;
            cursor: pointer;
            transition: all 0.12s;
        }

        .retro-btn-quick:hover {
            background: #283348;
            border-color: var(--retro-mustard);
            color: #ffeaa7;
        }

        .retro-btn-quick:active {
            transform: scale(0.96);
        }

        .btn-restock-all {
            background: linear-gradient(180deg, #2ecc71 0%, #27ae60 100%);
            border: 2px solid #ffffff;
            color: #ffffff;
            box-shadow: 0 3px 0 #1b663e;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 0.78rem;
            font-weight: 800;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.12s;
        }

        .btn-restock-all:active {
            transform: translateY(2px);
            box-shadow: none;
        }

        /* Dashboard Container */
        .dashboard-body {
            max-width: 1300px;
            margin: 18px auto;
            padding: 0 16px 60px;
            width: 100%;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* Metrics Row */
        .metrics-deck {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
        }

        .metric-card {
            background: #232938;
            border: 2px solid #3a445b;
            border-radius: var(--radius-box);
            box-shadow: 0 6px 0 #13161f, 0 12px 24px rgba(0, 0, 0, 0.5);
            padding: 18px;
            display: flex;
            align-items: center;
            gap: 16px;
            position: relative;
            overflow: hidden;
        }

        .metric-icon-box {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: #181d27;
            border: 1px solid #333a4c;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: var(--retro-mustard);
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
        }

        .metric-val {
            font-size: 1.6rem;
            font-weight: 900;
            font-family: var(--font-mono);
            color: #ffeaa7;
            line-height: 1.2;
        }

        .metric-caption {
            font-size: 0.72rem;
            color: var(--retro-cream-muted);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* Balanced Command Decks */
        .lower-deck-split {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        @media (max-width: 992px) {
            .lower-deck-split { grid-template-columns: 1fr; }
        }

        .telemetry-feed-scroll {
            max-height: 380px;
            overflow-y: auto;
            padding-right: 6px;
        }

        .telemetry-feed-scroll::-webkit-scrollbar {
            width: 5px;
        }

        .telemetry-feed-scroll::-webkit-scrollbar-track {
            background: #181d27;
            border-radius: 999px;
        }

        .telemetry-feed-scroll::-webkit-scrollbar-thumb {
            background: #3d475c;
            border-radius: 999px;
        }

        /* Panel Cards */
        .retro-panel-card {
            background: #232938;
            border: 2px solid #3a445b;
            border-radius: var(--radius-box);
            box-shadow: 0 6px 0 #13161f, 0 12px 24px rgba(0, 0, 0, 0.5);
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .panel-header-strip {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px dashed #30394c;
            padding-bottom: 12px;
        }

        .panel-heading {
            font-family: var(--font-body);
            font-size: 1.1rem;
            font-weight: 900;
            color: var(--retro-cream);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Table */
        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.85rem;
        }

        th {
            padding: 10px 12px;
            color: var(--retro-cream-muted);
            border-bottom: 2px solid #30394c;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.72rem;
            letter-spacing: 0.06em;
            font-family: var(--font-mono);
        }

        td {
            padding: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            vertical-align: middle;
        }

        tr:hover td {
            background: rgba(255, 255, 255, 0.02);
        }

        .slot-tag {
            background: #181d27;
            border: 1px solid #364055;
            color: #74b9ff;
            padding: 2px 7px;
            border-radius: 4px;
            font-weight: 800;
            font-family: var(--font-mono);
            font-size: 0.75rem;
        }

        .badge-status {
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 800;
            display: inline-block;
            font-family: var(--font-mono);
        }

        .badge-normal { background: rgba(46, 204, 113, 0.15); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.4); }
        .badge-low { background: rgba(243, 156, 18, 0.15); color: #f39c12; border: 1px solid rgba(243, 156, 18, 0.4); }
        .badge-out { background: rgba(214, 48, 49, 0.15); color: #ff7675; border: 1px solid rgba(214, 48, 49, 0.4); }

        .btn-restock-action {
            background: #1c212e;
            border: 2px solid #323b4f;
            box-shadow: 0 2px 0 #12151b;
            color: var(--retro-cream);
            padding: 5px 11px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: all 0.12s;
        }

        .btn-restock-action:active {
            transform: translateY(2px);
            box-shadow: 0 0 0 transparent;
        }

        /* Activity Feeds */
        .log-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 0.8rem;
        }

        .log-icon-chip {
            width: 26px;
            height: 26px;
            border-radius: 6px;
            background: #181d27;
            border: 1px solid #333a4c;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--retro-mustard);
            flex-shrink: 0;
            margin-top: 2px;
        }

        /* Modal Restock */
        .modal-retro-scrim {
            position: fixed;
            inset: 0;
            background: rgba(10, 13, 19, 0.88);
            backdrop-filter: blur(12px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 100;
            padding: 16px;
        }

        .modal-retro-scrim.open { display: flex; }

        .modal-retro-chassis {
            background: #242a3a;
            border: 3px solid #4a5570;
            border-radius: 18px;
            padding: 24px;
            width: 100%;
            max-width: 380px;
            box-shadow: 0 8px 0 #10131a, 0 24px 50px rgba(0,0,0,0.8);
        }

        .restock-number-input {
            width: 100%;
            background: #181d27;
            border: 2px solid #3d475c;
            border-radius: 8px;
            padding: 12px;
            color: #ffeaa7;
            font-size: 1.15rem;
            margin: 12px 0 18px;
            outline: none;
            font-family: var(--font-mono);
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.5);
        }

        .charts-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }

        .chart-box-canvas {
            position: relative;
            height: 220px;
            width: 100%;
        }

        @media (max-width: 900px) {
            .charts-row {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            .metrics-deck { grid-template-columns: 1fr 1fr; gap: 10px; }
            .metric-card { padding: 12px; gap: 10px; }
            .metric-val { font-size: 1.25rem; }
            .metric-caption { font-size: 0.65rem; }
            .retro-link-btn span { display: none; }
        }
    </style>
</head>
<body>

    <!-- Top Navigation Header -->
    <header>
        <div class="header-inner">
            <a href="index.php" class="brand-cluster">
                <div class="retro-stationery-sign">
                    <span class="sign-kanji" style="font-size: 0.72rem; letter-spacing: 0.05em;">ADM</span>
                    <span class="sign-sub-tag">HUB</span>
                </div>
                <div class="brand-text">
                    <h1><?= APP_NAME ?> <span class="brand-jp-badge">OPERATOR HUB</span></h1>
                </div>
            </a>

            <div class="nav-actions">
                <a href="index.php" class="retro-link-btn active">
                    <i data-lucide="layers" style="width: 14px; height: 14px;"></i>
                    <span>Slots Matrix</span>
                </a>
                <a href="transaction_logs.php" class="retro-link-btn">
                    <i data-lucide="receipt" style="width: 14px; height: 14px; color: #74b9ff;"></i>
                    <span>Dispense Logs</span>
                </a>
                <a href="analytics.php" class="retro-link-btn">
                    <i data-lucide="bar-chart-2" style="width: 14px; height: 14px; color: var(--retro-mustard);"></i>
                    <span>Analytics</span>
                </a>
                <a href="activity_logs.php" class="retro-link-btn">
                    <i data-lucide="activity" style="width: 14px; height: 14px; color: #2ecc71;"></i>
                    <span>Sensors & Audit</span>
                </a>
                <a href="../public/index.php" class="retro-link-btn" target="_blank" title="View Public Student Kiosk">
                    <i data-lucide="external-link" style="width: 14px; height: 14px; color: #ffeaa7;"></i>
                    <span>Kiosk</span>
                </a>
                <a href="index.php?logout=1" class="retro-link-btn" style="color: #ff7675; border-color: rgba(214, 48, 49, 0.4);">
                    <i data-lucide="log-out" style="width: 14px; height: 14px;"></i>
                    <span>Logout</span>
                </a>
            </div>
        </div>
    </header>

    <div class="dashboard-body">

        <!-- Metrics Row -->
        <div class="metrics-deck">
            <div class="metric-card">
                <div class="metric-icon-box">
                    <i data-lucide="package-search" style="width: 22px; height: 22px;"></i>
                </div>
                <div>
                    <div class="metric-val"><?= $totalStock ?></div>
                    <div class="metric-caption">In-Stock Units</div>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-icon-box" style="color: #2ecc71;">
                    <i data-lucide="check-circle" style="width: 22px; height: 22px;"></i>
                </div>
                <div>
                    <div class="metric-val"><?= $totalDispensed ?></div>
                    <div class="metric-caption">IR Verified Drops</div>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-icon-box" style="color: <?= $lowStockItems > 0 ? '#f39c12' : '#2ecc71' ?>;">
                    <i data-lucide="alert-triangle" style="width: 22px; height: 22px;"></i>
                </div>
                <div>
                    <div class="metric-val" style="color: <?= $lowStockItems > 0 ? '#f39c12' : '#2ecc71' ?>;"><?= $lowStockItems ?></div>
                    <div class="metric-caption">Slots Need Restock</div>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-icon-box" style="color: #ffeaa7;">
                    <i data-lucide="credit-card" style="width: 22px; height: 22px;"></i>
                </div>
                <div>
                    <div class="metric-val"><?= CURRENCY_SYMBOL ?><?= number_format($estimatedRevenue, 2) ?></div>
                    <div class="metric-caption">Cashless Sales Vol</div>
                </div>
            </div>
        </div>

        <!-- Visual Analytics Overview Deck (Chart.js) -->
        <div class="charts-row">
            <div class="retro-panel-card">
                <div class="panel-header-strip">
                    <div class="panel-heading">
                        <i data-lucide="trending-up" style="color: #74b9ff; width: 18px; height: 18px;"></i>
                        7-Day Dispense & Revenue Activity
                    </div>
                    <span style="font-family: var(--font-mono); font-size: 0.68rem; color: #ffeaa7; background: #181d27; padding: 2px 8px; border-radius: 4px; border: 1px solid #333a4c;">DAILY VOLUME</span>
                </div>
                <div class="chart-box-canvas">
                    <canvas id="salesTrendChart"></canvas>
                </div>
            </div>

            <div class="retro-panel-card">
                <div class="panel-header-strip">
                    <div class="panel-heading">
                        <i data-lucide="pie-chart" style="color: #2ecc71; width: 18px; height: 18px;"></i>
                        Category Share
                    </div>
                    <span style="font-family: var(--font-mono); font-size: 0.68rem; color: #2ecc71; background: #181d27; padding: 2px 8px; border-radius: 4px; border: 1px solid #333a4c;">BY DISPENSE</span>
                </div>
                <div class="chart-box-canvas" style="display: flex; align-items: center; justify-content: center;">
                    <canvas id="categoryDonutChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Slots Inventory Matrix (6-Slot 4-Tier Cabinet) - Full Width -->
        <div class="retro-panel-card">
            <div class="panel-header-strip">
                <div class="panel-heading">
                    <i data-lucide="server" style="color: var(--retro-mustard); width: 18px; height: 18px;"></i>
                    Vending Slot Inventory Matrix (Unit: <?= DEFAULT_MACHINE_ID ?>)
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <button type="button" class="btn-restock-all" onclick="restockAllMax()">
                        <i data-lucide="package-plus" style="width: 14px; height: 14px;"></i>
                        Restock All to Max Capacity
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Slot / Tier</th>
                            <th>Item</th>
                            <th>Price</th>
                            <th>Stock Level</th>
                            <th>Status</th>
                            <th>Dispensed</th>
                            <th>Quick Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $p): ?>
                            <?php 
                                $isOut = $p['stock'] <= 0;
                                $isLow = $p['stock'] <= $p['low_stock_threshold'] && !$isOut;
                                $tierNum = $p['tier_number'] ?? ($p['slot_number'] <= 3 ? $p['slot_number'] : 4);
                            ?>
                            <tr>
                                <td>
                                    <span class="slot-tag">#<?= str_pad($p['slot_number'], 2, '0', STR_PAD_LEFT) ?></span>
                                    <span style="font-size: 0.7rem; font-family: var(--font-mono); color: var(--retro-cream-muted); margin-left: 4px;">Tier <?= $tierNum ?></span>
                                </td>
                                <td>
                                    <div style="font-weight: 800; color: var(--retro-cream);"><?= htmlspecialchars($p['name']) ?></div>
                                    <div style="font-size: 0.72rem; color: var(--retro-cream-muted); margin-top: 2px;">
                                        <?= htmlspecialchars($p['category']) ?>
                                    </div>
                                </td>
                                <td style="font-weight: 800; font-family: var(--font-mono); color: #ffeaa7;">
                                    <?= CURRENCY_SYMBOL ?><?= number_format($p['price'], 2) ?>
                                </td>
                                <td>
                                    <strong style="color: #ffffff;"><?= $p['stock'] ?></strong> / <?= $p['max_capacity'] ?>
                                </td>
                                <td>
                                    <?php if ($isOut): ?>
                                        <span class="badge-status badge-out">EMPTY</span>
                                    <?php elseif ($isLow): ?>
                                        <span class="badge-status badge-low">LOW</span>
                                    <?php else: ?>
                                        <span class="badge-status badge-normal">OPTIMAL</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-family: var(--font-mono); font-weight: 700; color: #74b9ff;">
                                    <?= $p['dispensed_count'] ?>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 5px;">
                                        <button class="retro-btn-quick" onclick="quickAddStock(<?= $p['slot_number'] ?>, 5)" title="Add +5 units to this slot">
                                            +5
                                        </button>
                                        <button class="retro-btn-quick" onclick="quickAddStock(<?= $p['slot_number'] ?>, <?= ($p['max_capacity'] - $p['stock']) ?>)" title="Fill to maximum capacity">
                                            Max
                                        </button>
                                        <button class="btn-restock-action" onclick="openRestockModal(<?= $p['slot_number'] ?>, '<?= htmlspecialchars(addslashes($p['name'])) ?>', <?= $p['stock'] ?>)">
                                            <i data-lucide="edit-3" style="width: 12px; height: 12px; color: var(--retro-mustard);"></i>
                                            Set
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if (!empty($pendingRefunds)): ?>
            <!-- High-Priority Refund Review Queue (Only shown when pending drop timeouts exist) -->
            <div class="retro-panel-card" style="border-color: #ff7675; box-shadow: 0 6px 0 #7e0c0d, 0 12px 24px rgba(0, 0, 0, 0.5);">
                <div class="panel-header-strip">
                    <div class="panel-heading" style="color: #ff7675;">
                        <i data-lucide="alert-octagon" style="color: #ff7675; width: 18px; height: 18px;"></i>
                        Action Required: Refund-Pending Optical Drop Queue (<?= count($pendingRefunds) ?>)
                    </div>
                    <span class="badge-status badge-out"><?= count($pendingRefunds) ?> UNRESOLVED</span>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Ref / Key</th>
                                <th>Item & Slot</th>
                                <th>Amount</th>
                                <th>Sensor Trigger</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingRefunds as $ref): ?>
                                <tr>
                                    <td>
                                        <div style="font-family: var(--font-mono); font-weight: 800; color: #ffeaa7; font-size: 0.78rem;"><?= htmlspecialchars($ref['reference_no'] ?? $ref['transaction_key']) ?></div>
                                        <div style="font-size: 0.68rem; color: var(--retro-cream-muted);"><?= date('M d, H:i', strtotime($ref['created_at'])) ?></div>
                                    </td>
                                    <td>
                                        <strong style="color: var(--retro-cream);"><?= htmlspecialchars($ref['product_name']) ?></strong>
                                        <div style="font-size: 0.7rem; color: var(--retro-cream-muted);">Slot #<?= $ref['slot_number'] ?></div>
                                    </td>
                                    <td style="font-family: var(--font-mono); font-weight: 800; color: #ff7675;">
                                        <?= CURRENCY_SYMBOL ?><?= number_format($ref['amount'], 2) ?>
                                    </td>
                                    <td style="font-size: 0.74rem; color: #ff7675;">
                                        Optical Break-Beam Timeout
                                    </td>
                                    <td>
                                        <button class="btn-restock-action" style="border-color: #2ecc71; color: #2ecc71;" onclick="issueRefund('<?= htmlspecialchars($ref['transaction_key']) ?>')">
                                            <i data-lucide="check" style="width: 13px; height: 13px;"></i>
                                            Approve Refund
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <!-- Balanced Lower Command Deck: Diagnostics (Left) & Telemetry Drop Feed (Right) -->
        <div class="lower-deck-split">
            
            <!-- Left Panel: Machine Telemetry & ESP32 Sensors -->
            <div class="retro-panel-card">
                <div class="panel-header-strip">
                    <div class="panel-heading">
                        <i data-lucide="cpu" style="color: #74b9ff; width: 18px; height: 18px;"></i>
                        Cabinet Sensor Diagnostics
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-family: var(--font-mono); font-size: 0.68rem; color: #4ade80;">LIVE ESP32</span>
                        <a href="activity_logs.php" class="retro-link-btn" style="padding: 3px 8px; font-size: 0.7rem;" title="View Sensors & Full Audit Trail">
                            Audit ↗
                        </a>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <div style="background: #181d27; border: 1px solid #333a4c; border-radius: 8px; padding: 9px 12px; display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <div style="font-weight: 700; font-size: 0.8rem; color: var(--retro-cream);">Tier 1 Break-Beam IR</div>
                            <div style="font-size: 0.68rem; color: var(--retro-cream-muted);">Long Bond Paper Exit</div>
                        </div>
                        <span class="badge-status <?= $sensorData['tier_1_ir'] === 'CLEAR' ? 'badge-normal' : 'badge-out' ?>">
                            <?= htmlspecialchars($sensorData['tier_1_ir']) ?>
                        </span>
                    </div>

                    <div style="background: #181d27; border: 1px solid #333a4c; border-radius: 8px; padding: 9px 12px; display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <div style="font-weight: 700; font-size: 0.8rem; color: var(--retro-cream);">Tier 2 Break-Beam IR</div>
                            <div style="font-size: 0.68rem; color: var(--retro-cream-muted);">A4 Bond Paper Exit</div>
                        </div>
                        <span class="badge-status <?= $sensorData['tier_2_ir'] === 'CLEAR' ? 'badge-normal' : 'badge-out' ?>">
                            <?= htmlspecialchars($sensorData['tier_2_ir']) ?>
                        </span>
                    </div>

                    <div style="background: #181d27; border: 1px solid #333a4c; border-radius: 8px; padding: 9px 12px; display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <div style="font-weight: 700; font-size: 0.8rem; color: var(--retro-cream);">Tier 3 Break-Beam IR</div>
                            <div style="font-size: 0.68rem; color: var(--retro-cream-muted);">Short Bond Paper Exit</div>
                        </div>
                        <span class="badge-status <?= $sensorData['tier_3_ir'] === 'CLEAR' ? 'badge-normal' : 'badge-out' ?>">
                            <?= htmlspecialchars($sensorData['tier_3_ir']) ?>
                        </span>
                    </div>

                    <div style="background: #181d27; border: 1px solid #333a4c; border-radius: 8px; padding: 9px 12px; display: flex; align-items: center; justify-content: space-between;">
                        <div>
                            <div style="font-weight: 700; font-size: 0.8rem; color: var(--retro-cream);">Tier 4 Break-Beam IR</div>
                            <div style="font-size: 0.68rem; color: var(--retro-cream-muted);">Stationery Drop Chute (Slots 4-6)</div>
                        </div>
                        <span class="badge-status <?= $sensorData['tier_4_ir'] === 'CLEAR' ? 'badge-normal' : 'badge-out' ?>">
                            <?= htmlspecialchars($sensorData['tier_4_ir']) ?>
                        </span>
                    </div>

                    <div style="background: #181d27; border: 1px solid #333a4c; border-radius: 8px; padding: 9px 12px; display: flex; align-items: center; justify-content: space-between; border-left: 3px solid <?= $sensorData['door_switch'] === 'CLOSED' ? '#2ecc71' : '#e74c3c' ?>;">
                        <div>
                            <div style="font-weight: 700; font-size: 0.8rem; color: var(--retro-cream);">Cabinet Door Limit Switch</div>
                            <div style="font-size: 0.68rem; color: var(--retro-cream-muted);">Anti-Tamper Monitor</div>
                        </div>
                        <span class="badge-status <?= $sensorData['door_switch'] === 'CLOSED' ? 'badge-normal' : 'badge-out' ?>">
                            <?= $sensorData['door_switch'] === 'CLOSED' ? 'LOCKED / CLOSED' : 'ALARM: TAMPER OPEN' ?>
                        </span>
                    </div>

                    <!-- Connected Machine Badge -->
                    <?php foreach ($machines as $m): ?>
                        <div style="background: #151922; border: 1px solid #2d3545; border-radius: 8px; padding: 8px 12px; display: flex; align-items: center; justify-content: space-between; margin-top: 4px;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <i data-lucide="hard-drive" style="width: 14px; height: 14px; color: #2ecc71;"></i>
                                <span style="font-weight: 800; font-size: 0.78rem; font-family: var(--font-mono); color: var(--retro-cream);"><?= htmlspecialchars($m['machine_id']) ?></span>
                                <span style="font-size: 0.7rem; color: var(--retro-cream-muted);">• <?= htmlspecialchars($m['location']) ?></span>
                            </div>
                            <span class="badge-status <?= $m['status'] === 'ACTIVE' ? 'badge-normal' : 'badge-out' ?>">
                                <?= htmlspecialchars($m['status']) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Right Panel: Live Drop & Telemetry Feed -->
            <div class="retro-panel-card">
                <div class="panel-header-strip">
                    <div class="panel-heading">
                        <i data-lucide="activity" style="color: #74b9ff; width: 18px; height: 18px;"></i>
                        Live Drop & Telemetry Feed
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="display: inline-flex; align-items: center; gap: 5px; font-family: var(--font-mono); font-size: 0.68rem; color: #ffeaa7;">
                            <span style="width: 6px; height: 6px; border-radius: 50%; background: #2ecc71; box-shadow: 0 0 6px #2ecc71;"></span>
                            LIVE STREAM
                        </span>
                        <a href="transaction_logs.php" class="retro-link-btn" style="padding: 3px 8px; font-size: 0.7rem;" title="View Dispense Transaction History">
                            Full Logs ↗
                        </a>
                    </div>
                </div>

                <div class="telemetry-feed-scroll">
                    <?php if (empty($recentLogs)): ?>
                        <div style="text-align: center; color: var(--retro-cream-muted); padding: 30px 0; font-size: 0.8rem;">
                            No recent drop events recorded.
                        </div>
                    <?php else: ?>
                        <div style="display: flex; flex-direction: column;">
                            <?php foreach ($recentLogs as $log): ?>
                                <div class="log-item">
                                    <div class="log-icon-chip">
                                        <i data-lucide="zap" style="width: 14px; height: 14px;"></i>
                                    </div>
                                    <div style="flex: 1;">
                                        <div style="color: var(--retro-cream); font-weight: 600; font-size: 0.78rem; line-height: 1.4;"><?= htmlspecialchars($log['details']) ?></div>
                                        <div style="font-size: 0.68rem; color: var(--retro-cream-muted); font-family: var(--font-mono); margin-top: 3px;">
                                            <?= date('M d, g:i A', strtotime($log['created_at'])) ?> • <?= htmlspecialchars($log['machine_id'] ?? 'SYSTEM') ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </div>

    <!-- Restock Modal Dialog -->
    <div class="modal-retro-scrim" id="restockModal">
        <div class="modal-retro-chassis">
            <h3 style="font-family: var(--font-body); font-size: 1.2rem; font-weight: 900; color: var(--retro-cream);" id="modalRestockTitle">
                Restock Slot
            </h3>
            <p style="font-size: 0.78rem; color: var(--retro-cream-muted); margin-top: 4px;">
                Enter new quantity loaded into physical tray / paddle lane.
            </p>

            <input type="number" id="restockInput" class="restock-number-input" min="0" max="30">

            <div style="display: flex; justify-content: flex-end; gap: 8px;">
                <button class="retro-link-btn" onclick="closeRestockModal()">Cancel</button>
                <button class="retro-link-btn" style="background: var(--retro-red); border-color: #ffffff; color: white;" onclick="submitRestock()">
                    Save Stock
                </button>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        let activeRestockSlot = null;

        function openRestockModal(slot, name, currentStock) {
            activeRestockSlot = slot;
            document.getElementById('modalRestockTitle').innerText = `Restock Slot #${slot} — ${name}`;
            document.getElementById('restockInput').value = currentStock;
            document.getElementById('restockModal').classList.add('open');
        }

        function closeRestockModal() {
            document.getElementById('restockModal').classList.remove('open');
        }

        async function submitRestock() {
            const val = parseInt(document.getElementById('restockInput').value, 10);
            if (isNaN(val) || val < 0) return;

            const formData = new FormData();
            formData.append('action', 'restock_slot');
            formData.append('slot_number', activeRestockSlot);
            formData.append('stock', val);

            const res = await fetch('index.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                window.location.reload();
            } else {
                alert('Restock failed: ' + data.error);
            }
        }

        async function quickAddStock(slot, qty) {
            qty = parseInt(qty, 10);
            if (isNaN(qty) || qty <= 0) return;
            const formData = new FormData();
            formData.append('action', 'quick_restock');
            formData.append('slot_number', slot);
            formData.append('add_qty', qty);

            try {
                const res = await fetch('index.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Quick restock failed: ' + (data.error || 'Unknown error'));
                }
            } catch (err) {
                console.error(err);
                alert('Connection error during quick restock.');
            }
        }

        async function restockAllMax() {
            if (!confirm('Refill ALL machine slots to their maximum capacity?')) return;
            const formData = new FormData();
            formData.append('action', 'restock_all_max');

            try {
                const res = await fetch('index.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Restock all failed: ' + (data.error || 'Unknown error'));
                }
            } catch (err) {
                console.error(err);
                alert('Connection error during restock all.');
            }
        }

        async function issueRefund(txnKey) {
            if (!confirm(`Are you sure you want to approve and issue refund for transaction ${txnKey}?`)) return;

            const formData = new FormData();
            formData.append('action', 'issue_refund');
            formData.append('transaction_key', txnKey);

            const res = await fetch('index.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                alert('Refund processed successfully!');
                window.location.reload();
            } else {
                alert('Refund failed: ' + data.error);
            }
        }

        // Initialize Chart.js with Showa/Heisei Retro Palette
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof Chart !== 'undefined') {
                Chart.defaults.color = '#e6dfcf';
                Chart.defaults.font.family = "'Zen Maru Gothic', 'Noto Sans JP', sans-serif";

                const ctxTrend = document.getElementById('salesTrendChart')?.getContext('2d');
                if (ctxTrend) {
                    new Chart(ctxTrend, {
                        type: 'line',
                        data: {
                            labels: <?= json_encode($trendLabels) ?>,
                            datasets: [
                                {
                                    label: 'Dispense Units',
                                    data: <?= json_encode($trendOrders) ?>,
                                    borderColor: '#74b9ff',
                                    backgroundColor: 'rgba(116, 185, 255, 0.12)',
                                    tension: 0.35,
                                    fill: true,
                                    pointBackgroundColor: '#74b9ff',
                                    pointBorderColor: '#ffffff',
                                    pointRadius: 4,
                                    yAxisID: 'y'
                                },
                                {
                                    label: 'Revenue (<?= CURRENCY_SYMBOL ?>)',
                                    data: <?= json_encode($trendRevenue) ?>,
                                    borderColor: '#ffeaa7',
                                    backgroundColor: 'transparent',
                                    borderDash: [4, 4],
                                    tension: 0.35,
                                    pointBackgroundColor: '#ffeaa7',
                                    pointBorderColor: '#ffffff',
                                    pointRadius: 3,
                                    yAxisID: 'y1'
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'top',
                                    labels: {
                                        color: '#fbf7ee',
                                        font: { size: 11, weight: '700' },
                                        boxWidth: 12,
                                        padding: 10
                                    }
                                },
                                tooltip: {
                                    backgroundColor: '#181d27',
                                    borderColor: '#3d465c',
                                    borderWidth: 1,
                                    titleColor: '#ffeaa7',
                                    bodyColor: '#fbf7ee',
                                    padding: 10,
                                    cornerRadius: 8
                                }
                            },
                            scales: {
                                x: {
                                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                                    ticks: { color: '#a0a8b9', font: { size: 10, family: "'DM Mono', monospace" } }
                                },
                                y: {
                                    type: 'linear',
                                    display: true,
                                    position: 'left',
                                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                                    ticks: { 
                                        stepSize: 1,
                                        color: '#74b9ff', 
                                        font: { size: 10, family: "'DM Mono', monospace" } 
                                    }
                                },
                                y1: {
                                    type: 'linear',
                                    display: true,
                                    position: 'right',
                                    grid: { drawOnChartArea: false },
                                    ticks: { 
                                        color: '#ffeaa7', 
                                        font: { size: 10, family: "'DM Mono', monospace" },
                                        callback: function(value) { return '<?= CURRENCY_SYMBOL ?>' + value; }
                                    }
                                }
                            }
                        }
                    });
                }

                const ctxCat = document.getElementById('categoryDonutChart')?.getContext('2d');
                if (ctxCat) {
                    new Chart(ctxCat, {
                        type: 'doughnut',
                        data: {
                            labels: <?= json_encode($catLabels) ?>,
                            datasets: [{
                                data: <?= json_encode($catCounts) ?>,
                                backgroundColor: <?= json_encode($catColors) ?>,
                                borderColor: '#212634',
                                borderWidth: 2,
                                hoverOffset: 6
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'right',
                                    labels: {
                                        color: '#fbf7ee',
                                        font: { size: 10, weight: '600' },
                                        boxWidth: 10,
                                        padding: 8
                                    }
                                },
                                tooltip: {
                                    backgroundColor: '#181d27',
                                    borderColor: '#3d465c',
                                    borderWidth: 1,
                                    titleColor: '#ffeaa7',
                                    bodyColor: '#fbf7ee',
                                    padding: 10,
                                    cornerRadius: 8
                                }
                            },
                            cutout: '62%'
                        }
                    });
                }
            }
        });
    </script>
</body>
</html>