<?php
// admin/transaction_logs.php
// PenPoint Dispense Logs & Cashless Transaction History
// Theme: Japanese Retro Vending Maintenance & Telemetry Console
// English Console

date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/TransactionModel.php';
require_once __DIR__ . '/../models/ActivityLogModel.php';

session_start();
if (empty($_SESSION['penpoint_admin'])) {
    header('Location: index.php');
    exit;
}

$conn = getConnection();
$txnModel = new TransactionModel();
$logModel = new ActivityLogModel();

// Handle AJAX refund approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'issue_refund') {
    header('Content-Type: application/json');
    $txnKey = trim($_POST['transaction_key'] ?? '');
    if (!empty($txnKey)) {
        $updated = $txnModel->updateStatus($txnKey, 'REFUNDED');
        $logModel->addLog('refund_issued', 'ADMIN_CONSOLE', DEFAULT_MACHINE_ID, "Admin approved refund for {$txnKey} via Dispense Logs.");
        echo json_encode(['success' => $updated, 'message' => "Transaction {$txnKey} refunded successfully"]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Missing transaction key']);
    }
    exit;
}

// Filters & Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$slot = isset($_GET['slot']) && $_GET['slot'] !== '' ? intval($_GET['slot']) : '';
$date_from = trim($_GET['date_from'] ?? '');
$date_to = trim($_GET['date_to'] ?? '');
$limit = 25;
$offset = ($page - 1) * $limit;

// Build WHERE query
$whereClauses = [];
if ($search !== '') {
    $safeSearch = $conn->real_escape_string($search);
    $whereClauses[] = "(t.reference_no LIKE '%{$safeSearch}%' OR t.transaction_key LIKE '%{$safeSearch}%' OR t.product_name LIKE '%{$safeSearch}%' OR t.idcard LIKE '%{$safeSearch}%')";
}
if ($status !== '') {
    $safeStatus = $conn->real_escape_string($status);
    $whereClauses[] = "t.dispense_status = '{$safeStatus}'";
}
if ($slot !== '') {
    $safeSlot = intval($slot);
    $whereClauses[] = "t.slot_number = {$safeSlot}";
}
if ($date_from !== '') {
    $safeFrom = $conn->real_escape_string($date_from);
    $whereClauses[] = "DATE(t.created_at) >= '{$safeFrom}'";
}
if ($date_to !== '') {
    $safeTo = $conn->real_escape_string($date_to);
    $whereClauses[] = "DATE(t.created_at) <= '{$safeTo}'";
}

$whereSQL = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

// CSV Export Handler
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=penpoint_dispense_logs_' . date('Ymd_His') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Reference No', 'Transaction Key', 'Machine ID', 'Slot', 'Item Name', 'Amount (PHP)', 'Payment Method', 'IR Break-Beam Verified', 'Dispense Status', 'Date & Time']);

    $exportSQL = "SELECT * FROM transactions t {$whereSQL} ORDER BY t.created_at DESC";
    $expRes = $conn->query($exportSQL);
    if ($expRes) {
        while ($r = $expRes->fetch_assoc()) {
            fputcsv($output, [
                $r['id'],
                $r['reference_no'] ?? '',
                $r['transaction_key'],
                $r['machine_id'] ?? DEFAULT_MACHINE_ID,
                'Slot #' . $r['slot_number'],
                $r['product_name'],
                number_format($r['amount'], 2),
                $r['payment_method'],
                $r['ir_sensor_verified'] ? 'YES' : 'NO',
                $r['dispense_status'],
                $r['created_at']
            ]);
        }
    }
    fclose($output);
    exit;
}

// Fetch Metrics Summary
$metricsSQL = "
    SELECT 
        COUNT(*) as total_orders,
        COALESCE(SUM(amount), 0) as total_volume,
        COALESCE(SUM(CASE WHEN ir_sensor_verified = 1 THEN 1 ELSE 0 END), 0) as verified_count,
        COALESCE(SUM(CASE WHEN dispense_status = 'REFUND_PENDING' THEN 1 ELSE 0 END), 0) as pending_refunds
    FROM transactions t
";
$metricsRes = $conn->query($metricsSQL);
$metrics = $metricsRes ? $metricsRes->fetch_assoc() : [
    'total_orders' => 0,
    'total_volume' => 0,
    'verified_count' => 0,
    'pending_refunds' => 0
];

// Fetch filtered rows
$countSQL = "SELECT COUNT(*) as count FROM transactions t {$whereSQL}";
$countRes = $conn->query($countSQL);
$totalRows = $countRes ? (int)$countRes->fetch_assoc()['count'] : 0;
$totalPages = max(1, ceil($totalRows / $limit));

$listSQL = "
    SELECT t.* 
    FROM transactions t 
    {$whereSQL} 
    ORDER BY t.created_at DESC 
    LIMIT {$limit} OFFSET {$offset}
";
$listRes = $conn->query($listSQL);
$transactions = [];
if ($listRes) {
    while ($row = $listRes->fetch_assoc()) {
        $transactions[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#181c24">
    <title><?= APP_NAME ?> — Dispense Logs</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Zen+Maru+Gothic:wght@500;700;900&family=Noto+Sans+JP:wght@400;600;700;900&family=Dela+Gothic+One&family=DM+Mono:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- Flatpickr Datepicker & Custom Retro Theme -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="../public/css/retro-calendar.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
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
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        /* Custom Themed Scrollbar matching Retro Machine Chassis */
        html {
            scrollbar-width: thin;
            scrollbar-color: #3d465c #151821;
        }

        ::-webkit-scrollbar {
            width: 7px;
            height: 7px;
        }

        ::-webkit-scrollbar-track {
            background: #151821;
            border-left: 1px solid #232938;
        }

        ::-webkit-scrollbar-thumb {
            background: #3a445b;
            border-radius: 9999px;
            border: 1px solid #1c2230;
            transition: background 0.2s;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #505e7e;
        }

        ::-webkit-scrollbar-thumb:active {
            background: var(--retro-mustard, #e8a735);
        }

        body {
            font-family: var(--font-body);
            background-color: var(--retro-bg);
            color: var(--retro-cream);
            min-height: 100vh;
            background-image: 
                radial-gradient(circle at 50% 0%, #252c3c 0%, transparent 60%),
                radial-gradient(circle at 10% 100%, #1c212c 0%, transparent 40%);
        }

        /* Top Navigation Header */
        header {
            background: #1b202c;
            border-bottom: 3px solid #363f54;
            box-shadow: 0 4px 0 #10131a, 0 10px 20px rgba(0,0,0,0.5);
            padding: 12px 18px;
            position: sticky;
            top: 0;
            z-index: 50;
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
            line-height: 1;
            color: var(--retro-cream);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 0;
            padding: 0;
        }

        .brand-jp-badge {
            font-size: 0.65rem;
            font-family: var(--font-body);
            font-weight: 900;
            letter-spacing: 0.04em;
            color: #12151b;
            background: var(--retro-mustard);
            padding: 3px 8px;
            border-radius: 5px;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transform: translateY(-2px);
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
            background: #ffeaa7;
            border-color: #ffffff;
            color: #12151b;
            box-shadow: 0 3px 0 #b39b56;
            font-weight: 900;
        }

        /* Container */
        .container {
            max-width: 1300px;
            margin: 20px auto;
            padding: 0 16px 60px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* Metrics Deck */
        .metrics-deck {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
        }

        .metric-card {
            background: #232938;
            border: 2px solid #3a445b;
            border-radius: var(--radius-box);
            box-shadow: 0 5px 0 #13161f, 0 10px 20px rgba(0, 0, 0, 0.4);
            padding: 16px 18px;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .metric-icon-box {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            background: #181d27;
            border: 2px solid #343d50;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: var(--retro-mustard);
        }

        .metric-val {
            font-size: 1.55rem;
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

        /* Panel Card */
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
            flex-wrap: wrap;
            gap: 10px;
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

        /* Filter Strip */
        .filter-bar {
            background: #181d27;
            border: 1px solid #333a4c;
            border-radius: 10px;
            padding: 12px 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .filter-input {
            background: #212634;
            border: 1px solid #3a445b;
            border-radius: 6px;
            padding: 8px 10px;
            color: #ffeaa7;
            font-family: var(--font-mono);
            font-size: 0.8rem;
            outline: none;
        }

        .filter-select {
            background: #212634;
            border: 1px solid #3a445b;
            border-radius: 6px;
            padding: 8px 10px;
            color: var(--retro-cream);
            font-family: var(--font-body);
            font-size: 0.8rem;
            outline: none;
        }

        .btn-filter {
            background: #344055;
            border: 1px solid #4a5772;
            color: var(--retro-cream);
            border-radius: 6px;
            padding: 8px 14px;
            font-size: 0.8rem;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-csv-export {
            background: linear-gradient(180deg, #2ecc71 0%, #27ae60 100%);
            border: 2px solid #ffffff;
            color: #ffffff;
            box-shadow: 0 2px 0 #1b663e;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.76rem;
            font-weight: 800;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        /* Table */
        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 0.82rem;
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
            padding: 11px 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            vertical-align: middle;
        }

        tr:hover td {
            background: rgba(255, 255, 255, 0.02);
        }

        .badge-status {
            padding: 2px 7px;
            border-radius: 4px;
            font-size: 0.68rem;
            font-weight: 800;
            display: inline-block;
            font-family: var(--font-mono);
        }

        .badge-success { background: rgba(46, 204, 113, 0.15); color: #2ecc71; border: 1px solid rgba(46, 204, 113, 0.4); }
        .badge-pending { background: rgba(243, 156, 18, 0.15); color: #f39c12; border: 1px solid rgba(243, 156, 18, 0.4); }
        .badge-refunded { background: rgba(116, 185, 255, 0.15); color: #74b9ff; border: 1px solid rgba(116, 185, 255, 0.4); }

        .pagination {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 10px;
        }

        .page-btn {
            background: #181d27;
            border: 1px solid #333a4c;
            color: var(--retro-cream);
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 0.78rem;
            text-decoration: none;
            font-family: var(--font-mono);
        }

        .page-btn.active {
            background: #ffeaa7;
            color: #12151b;
            font-weight: 800;
            border-color: #ffffff;
        }

        @media (max-width: 480px) {
            .metrics-deck { grid-template-columns: 1fr 1fr; gap: 10px; }
            .retro-link-btn span { display: none; }
        }
    </style>
</head>
<body>

    <!-- Unified Command Navigation Header -->
    <header>
        <div class="header-inner">
            <a href="index.php" class="brand-cluster">
                <div class="retro-stationery-sign">
                    <span class="sign-kanji">出庫</span>
                    <span class="sign-sub-tag">LOG</span>
                </div>
                <div class="brand-text">
                    <h1><span><?= APP_NAME ?></span><span class="brand-jp-badge">DISPENSE LOGS</span></h1>
                </div>
            </a>

            <div class="nav-actions">
                <a href="index.php" class="retro-link-btn">
                    <i data-lucide="layers" style="width: 14px; height: 14px;"></i>
                    <span>Slots Matrix</span>
                </a>
                <a href="transaction_logs.php" class="retro-link-btn active">
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

    <div class="container">

        <!-- Metrics Deck -->
        <div class="metrics-deck">
            <div class="metric-card">
                <div class="metric-icon-box" style="color: #74b9ff;">
                    <i data-lucide="receipt" style="width: 22px; height: 22px;"></i>
                </div>
                <div>
                    <div class="metric-val"><?= (int)$metrics['total_orders'] ?></div>
                    <div class="metric-caption">Total Dispense Orders</div>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-icon-box" style="color: #ffeaa7;">
                    <i data-lucide="credit-card" style="width: 22px; height: 22px;"></i>
                </div>
                <div>
                    <div class="metric-val"><?= CURRENCY_SYMBOL ?><?= number_format((float)$metrics['total_volume'], 2) ?></div>
                    <div class="metric-caption">Cashless Gross Volume</div>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-icon-box" style="color: #2ecc71;">
                    <i data-lucide="check-check" style="width: 22px; height: 22px;"></i>
                </div>
                <div>
                    <div class="metric-val"><?= (int)$metrics['verified_count'] ?></div>
                    <div class="metric-caption">IR Break-Beam Verified</div>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-icon-box" style="color: <?= (int)$metrics['pending_refunds'] > 0 ? '#ff7675' : '#2ecc71' ?>;">
                    <i data-lucide="rotate-ccw" style="width: 22px; height: 22px;"></i>
                </div>
                <div>
                    <div class="metric-val" style="color: <?= (int)$metrics['pending_refunds'] > 0 ? '#ff7675' : '#2ecc71' ?>;"><?= (int)$metrics['pending_refunds'] ?></div>
                    <div class="metric-caption">Pending Drop Refunds</div>
                </div>
            </div>
        </div>

        <!-- Dispense Logs Master Panel -->
        <div class="retro-panel-card">
            <div class="panel-header-strip">
                <div class="panel-heading">
                    <i data-lucide="database" style="color: #74b9ff; width: 18px; height: 18px;"></i>
                    Dispense Transaction Records (<?= number_format($totalRows) ?> Total)
                </div>
                <div>
                    <a href="transaction_logs.php?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" class="btn-csv-export">
                        <i data-lucide="download" style="width: 14px; height: 14px;"></i>
                        Export CSV Data
                    </a>
                </div>
            </div>

            <!-- Filter Controls -->
            <form method="GET" action="transaction_logs.php" class="filter-bar">
                <input type="text" name="search" class="filter-input" placeholder="Search Ref, Item, Key..." value="<?= htmlspecialchars($search) ?>" style="flex: 1; min-width: 180px;">

                <select name="status" class="filter-select">
                    <option value="">All Statuses</option>
                    <option value="SUCCESS" <?= $status === 'SUCCESS' ? 'selected' : '' ?>>SUCCESS</option>
                    <option value="REFUND_PENDING" <?= $status === 'REFUND_PENDING' ? 'selected' : '' ?>>REFUND_PENDING</option>
                    <option value="REFUNDED" <?= $status === 'REFUNDED' ? 'selected' : '' ?>>REFUNDED</option>
                </select>

                <select name="slot" class="filter-select">
                    <option value="">All Slots</option>
                    <?php for ($s = 1; $s <= 6; $s++): ?>
                        <option value="<?= $s ?>" <?= (string)$slot === (string)$s ? 'selected' : '' ?>>Slot #<?= $s ?></option>
                    <?php endfor; ?>
                </select>

                <div class="date-input-wrap">
                    <i data-lucide="calendar" class="date-input-icon"></i>
                    <input type="text" name="date_from" id="date_from" class="filter-input retro-datepicker" placeholder="From Date" value="<?= htmlspecialchars($date_from) ?>" autocomplete="off">
                </div>
                <div class="date-input-wrap">
                    <i data-lucide="calendar" class="date-input-icon"></i>
                    <input type="text" name="date_to" id="date_to" class="filter-input retro-datepicker" placeholder="To Date" value="<?= htmlspecialchars($date_to) ?>" autocomplete="off">
                </div>

                <button type="submit" class="btn-filter">
                    <i data-lucide="filter" style="width: 13px; height: 13px;"></i> Filter
                </button>
                <?php if ($search || $status || $slot !== '' || $date_from || $date_to): ?>
                    <a href="transaction_logs.php" class="btn-filter" style="background: transparent; border-color: #555;">Clear</a>
                <?php endif; ?>
            </form>

            <!-- Table -->
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Ref / Key</th>
                            <th>Item & Slot</th>
                            <th>Amount</th>
                            <th>Payment</th>
                            <th>IR Break-Beam</th>
                            <th>Status</th>
                            <th>Date & Time</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 36px 0; color: var(--retro-cream-muted);">
                                    <i data-lucide="inbox" style="width: 28px; height: 28px; opacity: 0.5; margin-bottom: 6px; display: inline-block;"></i><br>
                                    No dispense transactions found matching criteria.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $t): ?>
                                <?php 
                                    $isRefundPending = ($t['dispense_status'] === 'REFUND_PENDING');
                                    $isSuccess = ($t['dispense_status'] === 'SUCCESS');
                                ?>
                                <tr>
                                    <td>
                                        <div style="font-family: var(--font-mono); font-weight: 800; color: #ffeaa7; font-size: 0.78rem;">
                                            <?= htmlspecialchars($t['reference_no'] ?? $t['transaction_key']) ?>
                                        </div>
                                        <div style="font-size: 0.68rem; color: var(--retro-cream-muted); font-family: var(--font-mono);">
                                            <?= htmlspecialchars(substr($t['transaction_key'], 0, 14)) ?>...
                                        </div>
                                    </td>
                                    <td>
                                        <strong style="color: var(--retro-cream);"><?= htmlspecialchars($t['product_name']) ?></strong>
                                        <div style="font-size: 0.7rem; color: #74b9ff; font-family: var(--font-mono);">
                                            Slot #<?= $t['slot_number'] ?> (Unit: <?= htmlspecialchars($t['machine_id'] ?? DEFAULT_MACHINE_ID) ?>)
                                        </div>
                                    </td>
                                    <td style="font-weight: 800; font-family: var(--font-mono); color: #ffeaa7;">
                                        <?= CURRENCY_SYMBOL ?><?= number_format($t['amount'], 2) ?>
                                    </td>
                                    <td>
                                        <span style="font-family: var(--font-mono); font-size: 0.72rem; padding: 2px 6px; border-radius: 4px; background: #181d27; border: 1px solid #333a4c;">
                                            <?= htmlspecialchars($t['payment_method']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($t['ir_sensor_verified']): ?>
                                            <span class="badge-status badge-success">
                                                <i data-lucide="check" style="width: 10px; height: 10px; display: inline; vertical-align: middle;"></i> VERIFIED
                                            </span>
                                        <?php else: ?>
                                            <span class="badge-status" style="background: rgba(214, 48, 49, 0.15); color: #ff7675; border: 1px solid rgba(214, 48, 49, 0.3);">
                                                TIMEOUT
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($isSuccess): ?>
                                            <span class="badge-status badge-success">SUCCESS</span>
                                        <?php elseif ($isRefundPending): ?>
                                            <span class="badge-status badge-pending">REFUND_PENDING</span>
                                        <?php else: ?>
                                            <span class="badge-status badge-refunded"><?= htmlspecialchars($t['dispense_status']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-family: var(--font-mono); font-size: 0.72rem; color: var(--retro-cream-muted);">
                                        <?= date('M d, Y • h:i A', strtotime($t['created_at'])) ?>
                                    </td>
                                    <td>
                                        <?php if ($isRefundPending): ?>
                                            <button class="retro-link-btn" style="padding: 3px 8px; font-size: 0.7rem; border-color: #2ecc71; color: #2ecc71;" onclick="issueRefund('<?= htmlspecialchars($t['transaction_key']) ?>')">
                                                <i data-lucide="rotate-ccw" style="width: 11px; height: 11px;"></i>
                                                Refund
                                            </button>
                                        <?php else: ?>
                                            <span style="color: var(--retro-cream-muted); font-size: 0.7rem;">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="transaction_logs.php?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="page-btn">« Prev</a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <a href="transaction_logs.php?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="page-btn <?= $i === $page ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="transaction_logs.php?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="page-btn">Next »</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div>

    </div>

    <script>
        lucide.createIcons();

        if (typeof flatpickr !== 'undefined') {
            flatpickr(".retro-datepicker", {
                dateFormat: "Y-m-d",
                allowInput: true,
                disableMobile: true
            });
        }

        async function issueRefund(txnKey) {
            if (!confirm(`Are you sure you want to approve and issue refund for transaction ${txnKey}?`)) return;

            const formData = new FormData();
            formData.append('action', 'issue_refund');
            formData.append('transaction_key', txnKey);

            try {
                const res = await fetch('transaction_logs.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) {
                    alert('Refund processed successfully!');
                    window.location.reload();
                } else {
                    alert('Refund failed: ' + (data.error || 'Unknown error'));
                }
            } catch (err) {
                console.error(err);
                alert('Connection error during refund processing.');
            }
        }
    </script>
</body>
</html>