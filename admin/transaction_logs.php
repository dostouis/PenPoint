<?php
// admin/transaction_logs.php
// Transaction Logs Page - Detailed transaction history

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/TransactionModel.php';

$appName = defined('APP_NAME') ? APP_NAME : 'EcoPoints';

$txnModel = new TransactionModel();
$conn = getConnection();

// Get filter parameters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$machine_id = $_GET['machine_id'] ?? '';
$idcard = $_GET['idcard'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$limit = 50;
$offset = ($page - 1) * $limit;

// Build query with filters
$whereClauses = [];
if ($machine_id) {
    $whereClauses[] = "t.machine_id = '" . $conn->real_escape_string($machine_id) . "'";
}
if ($idcard) {
    $whereClauses[] = "t.idcard = '" . $conn->real_escape_string($idcard) . "'";
}
if ($date_from) {
    $whereClauses[] = "DATE(t.created_at) >= '" . $conn->real_escape_string($date_from) . "'";
}
if ($date_to) {
    $whereClauses[] = "DATE(t.created_at) <= '" . $conn->real_escape_string($date_to) . "'";
}

$whereSQL = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

// Get transactions
$sql = "
    SELECT 
        t.*,
        u.fname,
        u.lname,
        u.total_points_earned as user_total_points,
        u.total_items_recycled as user_total_bottles,
        m.location as machine_location
    FROM transactions t
    LEFT JOIN users u ON t.idcard = u.idcard
    LEFT JOIN machines m ON t.machine_id = m.machine_id
    $whereSQL
    ORDER BY t.created_at DESC 
    LIMIT $limit OFFSET $offset
";
$result = $conn->query($sql);
$transactions = [];
while ($row = $result->fetch_assoc()) {
    $transactions[] = $row;
}

// Get total count
$countSQL = "
    SELECT COUNT(*) as count 
    FROM transactions t
    $whereSQL
";
$countResult = $conn->query($countSQL);
$totalTransactions = $countResult->fetch_assoc()['count'];
$totalPages = ceil($totalTransactions / $limit);

// Get stats
$statsSQL = "
    SELECT 
        COUNT(*) as total_txn,
        COALESCE(SUM(t.received_points), 0) as total_points,
        COALESCE(SUM(t.item_count), 0) as total_bottles,
        COUNT(DISTINCT t.idcard) as unique_users,
        COUNT(DISTINCT t.machine_id) as active_machines,
        AVG(t.item_count) as avg_bottles_per_txn,
        AVG(t.received_points) as avg_points_per_txn
    FROM transactions t
    $whereSQL
";
$statsResult = $conn->query($statsSQL);
$stats = $statsResult->fetch_assoc();

// Get today's stats
$todaySQL = "
    SELECT 
        COUNT(*) as today_txn,
        COALESCE(SUM(received_points), 0) as today_points,
        COALESCE(SUM(item_count), 0) as today_bottles
    FROM transactions 
    WHERE DATE(created_at) = CURDATE()
";
$todayResult = $conn->query($todaySQL);
$todayStats = $todayResult->fetch_assoc();

// Get machines for filter
$machines = [];
$machineResult = $conn->query("SELECT DISTINCT machine_id, location FROM machines ORDER BY machine_id");
while ($row = $machineResult->fetch_assoc()) {
    $machines[] = $row;
}

// Get users for filter
$users = [];
$userResult = $conn->query("SELECT DISTINCT idcard, fname, lname FROM users ORDER BY fname");
while ($row = $userResult->fetch_assoc()) {
    $users[] = $row;
}

// Get hourly distribution for chart
$hourlySQL = "
    SELECT 
        HOUR(created_at) as hour,
        COUNT(*) as count,
        COALESCE(SUM(item_count), 0) as bottles
    FROM transactions 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY HOUR(created_at)
    ORDER BY hour
";
$hourlyResult = $conn->query($hourlySQL);
$hourlyData = [];
while ($row = $hourlyResult->fetch_assoc()) {
    $hourlyData[$row['hour']] = $row;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Logs - <?= htmlspecialchars($appName) ?> Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;600;700;800;900&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- jsPDF for PDF export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <style>
        :root {
            --bg: #071a0e;
            --forest: #0d2b17;
            --surface: rgba(10, 30, 17, 0.85);
            --surface-solid: #0e2416;
            --surface2: rgba(255,255,255,0.06);
            --surface2-solid: #132d1c;
            --green: #1e7a45;
            --green-bright: #35e07a;
            --green-mid: #28b85e;
            --green-light: #a6f2c4;
            --green-glow: rgba(53,224,122,0.18);
            --accent: #f5c842;
            --red: #e05a5a;
            --blue: #5ab4ff;
            --text: #eef8f2;
            --muted: #6faa88;
            --border: rgba(53,224,122,0.12);
            --border-solid: #1c3d28;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Nunito Sans', sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(ellipse 100% 70% at 50% 0%, rgba(14,50,28,0.95) 0%, transparent 70%),
                radial-gradient(ellipse 60% 50% at 10% 50%, rgba(20,70,38,0.4) 0%, transparent 60%),
                radial-gradient(ellipse 50% 40% at 90% 60%, rgba(8,40,20,0.5) 0%, transparent 60%),
                linear-gradient(180deg, #071a0e 0%, #0a2212 40%, #071a0e 100%);
            pointer-events: none;
            z-index: 0;
        }

        body::after {
            content: '';
            position: fixed;
            inset: 0;
            background-image:
                repeating-linear-gradient(90deg, transparent 0px, transparent 80px, rgba(20,60,30,0.08) 80px, rgba(20,60,30,0.08) 82px);
            pointer-events: none;
            z-index: 0;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0; top: 0; bottom: 0;
            width: 248px;
            background: linear-gradient(180deg, #0b2016 0%, #071a0e 100%);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            z-index: 100;
            box-shadow: 4px 0 32px rgba(0,0,0,0.5);
        }

        .logo {
            padding: 28px 24px 22px;
            border-bottom: 1px solid var(--border);
            position: relative;
        }

        .logo::after {
            content: '';
            position: absolute;
            bottom: -1px; left: 24px; right: 24px;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--green-bright), transparent);
            opacity: 0.3;
        }

        .logo-icon {
            width: 42px; height: 42px;
            background: linear-gradient(135deg, var(--green-mid), var(--green-bright));
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px;
            margin-bottom: 12px;
            box-shadow: 0 4px 20px rgba(53,224,122,0.3);
        }

        .logo h1 { font-size: 21px; font-weight: 900; color: var(--text); letter-spacing: -0.5px; }
        .logo p { font-size: 11px; color: var(--muted); font-family: 'DM Mono', monospace; margin-top: 3px; }

        .nav {
            padding: 18px 12px;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 2px;
            overflow-y: auto;
        }

        .nav-label {
            font-size: 9px;
            font-family: 'DM Mono', monospace;
            color: var(--muted);
            letter-spacing: 2px;
            padding: 12px 14px 6px;
            text-transform: uppercase;
            opacity: 0.7;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border-radius: 10px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 700;
            color: var(--muted);
            transition: all 0.2s ease;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            text-decoration: none;
        }

        .nav-item:hover {
            background: rgba(53,224,122,0.07);
            color: var(--text);
            padding-left: 18px;
        }

        .nav-item.active {
            background: linear-gradient(90deg, rgba(53,224,122,0.15), rgba(53,224,122,0.05));
            color: var(--green-bright);
            border-left: 3px solid var(--green-bright);
            padding-left: 11px;
        }

        .nav-item .icon { font-size: 16px; width: 22px; text-align: center; }

        .sidebar-footer {
            padding: 16px;
            border-top: 1px solid var(--border);
        }

        .machine-status {
            background: rgba(53,224,122,0.06);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 13px 14px;
            font-size: 12px;
        }

        .machine-status .label { color: var(--muted); margin-bottom: 8px; font-family: 'DM Mono', monospace; font-size: 9px; letter-spacing: 1.5px; text-transform: uppercase; }
        .machine-status .machine { display: flex; align-items: center; gap: 8px; color: var(--text); font-weight: 700; font-size: 13px; }
        .dot { width: 8px; height: 8px; border-radius: 50%; background: var(--green-bright); box-shadow: 0 0 8px var(--green-bright); animation: pulse 2s infinite; }
        @keyframes pulse { 0%,100% { opacity:1; } 50% { opacity:0.5; } }

        /* Main Content */
        .main {
            margin-left: 248px;
            padding: 32px 36px;
            position: relative;
            z-index: 1;
            min-height: 100vh;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 28px;
            flex-wrap: wrap;
            gap: 16px;
        }

        .topbar h2 {
            font-size: 28px;
            font-weight: 900;
            letter-spacing: -0.5px;
            color: var(--text);
        }

        .topbar-right { display: flex; gap: 10px; align-items: center; }

        .btn {
            padding: 10px 20px;
            border-radius: 10px;
            border: none;
            cursor: pointer;
            font-family: 'Nunito Sans', sans-serif;
            font-weight: 800;
            font-size: 13px;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-ghost {
            background: rgba(255,255,255,0.06);
            color: var(--text);
            border: 1px solid var(--border);
        }

        .btn-ghost:hover { background: rgba(255,255,255,0.1); border-color: rgba(53,224,122,0.3); }

        .btn-primary {
            background: linear-gradient(135deg, var(--green-mid), var(--green-bright));
            color: #061410;
            margin-left: 8px;
        }

        .btn-primary:hover { filter: brightness(1.1); }

        .btn-pdf {
            background: linear-gradient(135deg, #e05a5a, #c0392b);
            color: white;
            margin-left: 8px;
        }

        .btn-pdf:hover { filter: brightness(1.1); }

        /* Export Dropdown */
        .export-dropdown {
            position: relative;
            display: inline-block;
        }

        .export-menu {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background: var(--surface-solid);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 8px 0;
            min-width: 160px;
            z-index: 1000;
            margin-top: 5px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.4);
        }

        .export-menu.show {
            display: block;
        }

        .export-option {
            padding: 10px 16px;
            color: var(--text);
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .export-option:hover {
            background: rgba(53,224,122,0.1);
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 14px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: linear-gradient(145deg, rgba(14,40,22,0.9), rgba(9,25,14,0.95));
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 16px;
        }

        .stat-label {
            font-size: 10px;
            font-family: 'DM Mono', monospace;
            color: var(--muted);
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .stat-value {
            font-size: 26px;
            font-weight: 900;
            color: var(--text);
        }

        .stat-sub {
            font-size: 11px;
            color: var(--muted);
            margin-top: 4px;
        }

        /* Today Stats */
        .today-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            margin-bottom: 24px;
        }

        .today-card {
            background: linear-gradient(145deg, rgba(53,224,122,0.08), rgba(53,224,122,0.02));
            border: 1px solid rgba(53,224,122,0.2);
            border-radius: 14px;
            padding: 16px;
            text-align: center;
        }

        .today-label {
            font-size: 11px;
            font-family: 'DM Mono', monospace;
            color: var(--green-bright);
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .today-value {
            font-size: 28px;
            font-weight: 900;
            color: var(--green-bright);
        }

        /* Filter Bar */
        .filter-bar {
            background: linear-gradient(160deg, rgba(13,38,22,0.92), rgba(8,22,13,0.97));
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 20px 24px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-group label {
            font-size: 11px;
            font-family: 'DM Mono', monospace;
            color: var(--muted);
            text-transform: uppercase;
        }

        .filter-select, .filter-input {
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 9px 12px;
            color: var(--text);
            font-family: 'Nunito Sans', sans-serif;
            font-size: 13px;
            outline: none;
            min-width: 140px;
        }

        .filter-select option { background: var(--surface-solid); }

        .filter-input { width: 130px; }

        /* Table */
        .card {
            background: linear-gradient(160deg, rgba(13,38,22,0.92), rgba(8,22,13,0.97));
            border: 1px solid var(--border);
            border-radius: 18px;
            overflow: hidden;
            margin-bottom: 24px;
        }

        table { width: 100%; border-collapse: collapse; }

        th {
            padding: 12px 14px;
            text-align: left;
            font-size: 9px;
            font-family: 'DM Mono', monospace;
            color: var(--muted);
            letter-spacing: 1.5px;
            text-transform: uppercase;
            background: rgba(255,255,255,0.03);
            border-bottom: 1px solid var(--border);
        }

        td {
            padding: 12px 14px;
            font-size: 12px;
            font-weight: 600;
            border-bottom: 1px solid rgba(53,224,122,0.05);
            color: var(--text);
        }

        tr:hover td { background: rgba(53,224,122,0.03); }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 8px;
            border-radius: 20px;
            font-size: 9px;
            font-weight: 800;
            font-family: 'DM Mono', monospace;
            background: rgba(53,224,122,0.12);
            color: var(--green-bright);
            border: 1px solid rgba(53,224,122,0.2);
        }

        .points-positive {
            color: var(--green-bright);
            font-family: 'DM Mono', monospace;
            font-weight: 700;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 24px;
        }

        .page-link {
            padding: 8px 14px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            font-size: 13px;
            background: rgba(255,255,255,0.05);
            color: var(--text);
            border: 1px solid var(--border);
            transition: all 0.2s;
        }

        .page-link:hover { background: rgba(53,224,122,0.1); border-color: rgba(53,224,122,0.3); }
        .page-link.active { background: var(--green-bright); color: #061410; border-color: var(--green-bright); }

        .empty-state {
            padding: 60px;
            text-align: center;
            color: var(--muted);
        }

        .empty-icon { font-size: 48px; margin-bottom: 16px; opacity: 0.4; }

        /* Chart */
        .chart-container {
            background: linear-gradient(160deg, rgba(13,38,22,0.92), rgba(8,22,13,0.97));
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 24px;
        }

        .chart-title {
            font-size: 14px;
            font-weight: 800;
            margin-bottom: 16px;
        }

        .hourly-chart {
            display: flex;
            gap: 4px;
            align-items: flex-end;
            height: 120px;
        }

        .hour-bar {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
        }

        .bar {
            width: 100%;
            background: linear-gradient(180deg, var(--green-bright), var(--green-mid));
            border-radius: 4px 4px 0 0;
            min-height: 2px;
            transition: height 0.3s;
        }

        .hour-label {
            font-size: 9px;
            font-family: 'DM Mono', monospace;
            color: var(--muted);
        }

        * {
            scrollbar-width: thin;
            scrollbar-color: rgba(53, 224, 122, 0.45) rgba(8, 24, 14, 0.8);
        }

        *::-webkit-scrollbar {
            width: 10px;
            height: 10px;
        }

        *::-webkit-scrollbar-track {
            background: rgba(8, 24, 14, 0.8);
            border-radius: 999px;
        }

        *::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, rgba(53, 224, 122, 0.7), rgba(30, 122, 69, 0.85));
            border: 2px solid rgba(8, 24, 14, 0.8);
            border-radius: 999px;
        }

        *::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, rgba(80, 238, 141, 0.85), rgba(40, 184, 94, 0.95));
        }

        .filter-input.flatpickr-input {
            cursor: pointer;
            font-family: 'DM Mono', monospace;
        }

        .flatpickr-calendar {
            background: #0e2416;
            border: 1px solid rgba(53,224,122,0.2);
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.55);
            border-radius: 12px;
        }

        .flatpickr-months .flatpickr-month,
        .flatpickr-current-month .flatpickr-monthDropdown-months,
        .flatpickr-current-month input.cur-year {
            color: #eef8f2;
            fill: #eef8f2;
            background: transparent;
        }

        .flatpickr-weekday {
            color: #6faa88;
            font-weight: 700;
        }

        .flatpickr-day {
            color: #d9f5e7;
            border-radius: 8px;
        }

        .flatpickr-day.prevMonthDay,
        .flatpickr-day.nextMonthDay {
            color: rgba(217, 245, 231, 0.35);
        }

        .flatpickr-day:hover {
            background: rgba(53,224,122,0.15);
            border-color: transparent;
        }

        .flatpickr-day.selected,
        .flatpickr-day.startRange,
        .flatpickr-day.endRange {
            background: #35e07a;
            color: #052111;
            border-color: #35e07a;
        }

        .flatpickr-day.today {
            border-color: #35e07a;
        }

        .flatpickr-monthDropdown-months option {
            background: #0e2416;
            color: #eef8f2;
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="logo">
            <div class="logo-icon">♻️</div>
            <h1>EcoPoints</h1>
            <p>Admin Dashboard</p>
        </div>
        <nav class="nav">
            <div class="nav-label">PenPoint Console</div>
            <a href="index.php" class="nav-item"><span class="icon">🗂️</span> Slots Matrix</a>
            <a href="transaction_logs.php" class="nav-item active"><span class="icon">🧾</span> Dispense Logs</a>
            <a href="analytics.php" class="nav-item"><span class="icon">📈</span> Analytics</a>
            <a href="activity_logs.php" class="nav-item"><span class="icon">⚡</span> Sensors & Audit</a>
            <a href="../public/index.php" class="nav-item" target="_blank"><span class="icon">🏪</span> Student Kiosk ↗</a>
        </nav>
        <div class="sidebar-footer">
            <div class="machine-status">
                <div class="label">MACHINE STATUS</div>
                <div class="machine"><div class="dot"></div> M001 · Main Building</div>
            </div>
        </div>
    </aside>

    <main class="main">
        <div class="topbar">
            <div>
                <h2>Transaction Logs 📊</h2>
                <div style="font-size:13px;color:var(--muted);margin-top:4px;font-family:'DM Mono',monospace">Detailed recycling transaction history</div>
            </div>
            <div class="topbar-right">
                <a href="transaction_logs.php" class="btn btn-ghost">🔄 Refresh</a>
                <div class="export-dropdown">
                    <button class="btn btn-primary" onclick="toggleExportMenu()">📥 Export ▼</button>
                    <div class="export-menu" id="exportMenu">
                        <div class="export-option" onclick="exportToCSV()">
                            <span>📄</span> Export as CSV
                        </div>
                        <div class="export-option" onclick="exportToPDF('portrait')">
                            <span>📑</span> Export PDF (Portrait)
                        </div>
                        <div class="export-option" onclick="exportToPDF('landscape')">
                            <span>📑</span> Export PDF (Landscape)
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Overview -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Transactions</div>
                <div class="stat-value"><?= number_format($stats['total_txn'] ?? 0) ?></div>
                <div class="stat-sub">All time</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Points</div>
                <div class="stat-value"><?= number_format($stats['total_points'] ?? 0) ?></div>
                <div class="stat-sub">Awarded</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Total Bottles</div>
                <div class="stat-value"><?= number_format($stats['total_bottles'] ?? 0) ?></div>
                <div class="stat-sub">Recycled</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Unique Users</div>
                <div class="stat-value"><?= number_format($stats['unique_users'] ?? 0) ?></div>
                <div class="stat-sub">Participating</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Avg Points/Txn</div>
                <div class="stat-value"><?= number_format($stats['avg_points_per_txn'] ?? 0, 1) ?></div>
                <div class="stat-sub">Per transaction</div>
            </div>
        </div>

        <!-- Today's Stats -->
        <div class="today-stats">
            <div class="today-card">
                <div class="today-label">📅 Today's Transactions</div>
                <div class="today-value"><?= number_format($todayStats['today_txn'] ?? 0) ?></div>
            </div>
            <div class="today-card">
                <div class="today-label">⭐ Today's Points</div>
                <div class="today-value"><?= number_format($todayStats['today_points'] ?? 0) ?></div>
            </div>
            <div class="today-card">
                <div class="today-label">🍶 Today's Bottles</div>
                <div class="today-value"><?= number_format($todayStats['today_bottles'] ?? 0) ?></div>
            </div>
        </div>

        <!-- Hourly Chart -->
        <div class="chart-container">
            <div class="chart-title">📈 Hourly Activity (Last 7 Days)</div>
            <div class="hourly-chart">
                <?php for ($h = 0; $h < 24; $h++): 
                    $count = $hourlyData[$h]['count'] ?? 0;
                    $maxCount = max(array_column($hourlyData, 'count')) ?: 1;
                    $height = max(10, round(($count / $maxCount) * 100));
                ?>
                <div class="hour-bar">
                    <div class="bar" style="height: <?= $height ?>px;" title="<?= $count ?> transactions"></div>
                    <div class="hour-label"><?= sprintf('%02d', $h) ?></div>
                </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Filter Bar -->
        <form class="filter-bar" method="GET">
            <div class="filter-group">
                <label>Machine:</label>
                <select name="machine_id" class="filter-select" onchange="this.form.submit()">
                    <option value="">All Machines</option>
                    <?php foreach ($machines as $m): ?>
                    <option value="<?= htmlspecialchars($m['machine_id']) ?>" <?= $machine_id === $m['machine_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($m['machine_id']) ?> (<?= htmlspecialchars($m['location']) ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>User:</label>
                <select name="idcard" class="filter-select" onchange="this.form.submit()">
                    <option value="">All Users</option>
                    <?php foreach ($users as $u): ?>
                    <option value="<?= htmlspecialchars($u['idcard']) ?>" <?= $idcard === $u['idcard'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars(substr($u['idcard'], 0, 10)) ?>... (<?= htmlspecialchars($u['fname'] ?: '—') ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>From:</label>
                <input type="text" id="dateFromFilter" name="date_from" class="filter-input" value="<?= htmlspecialchars($date_from) ?>" placeholder="Select start date">
            </div>
            <div class="filter-group">
                <label>To:</label>
                <input type="text" id="dateToFilter" name="date_to" class="filter-input" value="<?= htmlspecialchars($date_to) ?>" placeholder="Select end date">
            </div>
            <?php if ($machine_id || $idcard || $date_from || $date_to): ?>
            <a href="transaction_logs.php" class="btn btn-ghost" style="margin-left:auto;">Clear Filters</a>
            <?php endif; ?>
        </form>

        <!-- Transactions Table -->
        <div class="card">
            <table id="transactionsTable">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Transaction Key</th>
                        <th>Card ID</th>
                        <th>User</th>
                        <th>Machine</th>
                        <th>Location</th>
                        <th>Bottles</th>
                        <th>Points</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                    <tr><td colspan="8" class="empty-state"><div class="empty-icon">📊</div>No transactions found</td></tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $txn): ?>
                        <tr>
                            <td style="font-family:'DM Mono',monospace;font-size:11px;color:var(--muted);"><?= date('M j, g:i:s A', strtotime($txn['created_at'])) ?></td>
                            <td style="font-family:'DM Mono',monospace;font-size:10px;max-width:150px;overflow:hidden;text-overflow:ellipsis;" title="<?= htmlspecialchars($txn['transaction_key']) ?>">
                                <?= htmlspecialchars(substr($txn['transaction_key'], 0, 16)) ?>...
                            </td>
                            <td style="font-family:'DM Mono',monospace;font-size:12px;"><?= htmlspecialchars(substr($txn['idcard'], 0, 12)) ?>...</td>
                            <td><?= htmlspecialchars(trim($txn['fname'] . ' ' . $txn['lname']) ?: '—') ?></td>
                            <td><span class="badge"><?= htmlspecialchars($txn['machine_id']) ?></span></td>
                            <td style="font-size:11px;color:var(--muted);"><?= htmlspecialchars($txn['machine_location'] ?: '—') ?></td>
                            <td>🍶 <?= $txn['item_count'] ?></td>
                            <td><span class="points-positive">+<?= $txn['received_points'] ?></span></td>
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
            <a href="?page=<?= $page-1 ?>&machine_id=<?= urlencode($machine_id) ?>&idcard=<?= urlencode($idcard) ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>" class="page-link">← Previous</a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
            <a href="?page=<?= $i ?>&machine_id=<?= urlencode($machine_id) ?>&idcard=<?= urlencode($idcard) ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>" class="page-link <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page+1 ?>&machine_id=<?= urlencode($machine_id) ?>&idcard=<?= urlencode($idcard) ?>&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>" class="page-link">Next →</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </main>

    <script>
        const transactionFilterForm = document.querySelector('.filter-bar');
        const fromFilterInput = document.getElementById('dateFromFilter');
        const toFilterInput = document.getElementById('dateToFilter');

        const toDatePicker = flatpickr(toFilterInput, {
            dateFormat: 'Y-m-d',
            disableMobile: true,
            allowInput: false,
            onChange: function(selectedDates, dateStr) {
                fromDatePicker.set('maxDate', dateStr || null);
                if (fromFilterInput.value && dateStr && fromFilterInput.value > dateStr) {
                    fromDatePicker.setDate(dateStr, true);
                }
                transactionFilterForm.submit();
            }
        });

        const fromDatePicker = flatpickr(fromFilterInput, {
            dateFormat: 'Y-m-d',
            disableMobile: true,
            allowInput: false,
            onChange: function(selectedDates, dateStr) {
                toDatePicker.set('minDate', dateStr || null);
                if (toFilterInput.value && dateStr && toFilterInput.value < dateStr) {
                    toDatePicker.setDate(dateStr, true);
                }
                transactionFilterForm.submit();
            }
        });

        function syncTransactionDateLimits() {
            const fromVal = fromFilterInput.value;
            const toVal = toFilterInput.value;
            toDatePicker.set('minDate', fromVal || null);
            fromDatePicker.set('maxDate', toVal || null);
        }
        syncTransactionDateLimits();

        // Toggle export dropdown
        function toggleExportMenu() {
            const menu = document.getElementById('exportMenu');
            menu.classList.toggle('show');
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.export-dropdown')) {
                document.getElementById('exportMenu').classList.remove('show');
            }
        });
        
        // Export to CSV
        function exportToCSV() {
            document.getElementById('exportMenu').classList.remove('show');
            
            const rows = [];
            const headers = ['Time', 'Transaction Key', 'Card ID', 'User', 'Machine', 'Location', 'Bottles', 'Points'];
            rows.push(headers.join(','));
            
            <?php foreach ($transactions as $txn): ?>
            rows.push([
                '<?= date('Y-m-d H:i:s', strtotime($txn['created_at'])) ?>',
                '<?= str_replace(["\r", "\n", ","], ["", "", ";"], htmlspecialchars($txn['transaction_key'])) ?>',
                '<?= htmlspecialchars($txn['idcard']) ?>',
                '<?= str_replace(",", ";", htmlspecialchars(trim($txn['fname'] . ' ' . $txn['lname']) ?: '—')) ?>',
                '<?= htmlspecialchars($txn['machine_id']) ?>',
                '<?= str_replace(",", ";", htmlspecialchars($txn['machine_location'] ?: '—')) ?>',
                '<?= $txn['item_count'] ?>',
                '<?= $txn['received_points'] ?>'
            ].join(','));
            <?php endforeach; ?>
            
            const csv = rows.join('\n');
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'transactions_<?= date('Y-m-d') ?>.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }
        
        // Export to PDF
        function exportToPDF(orientation = 'landscape') {
            document.getElementById('exportMenu').classList.remove('show');
            
            const { jsPDF } = window.jspdf;
            const isPortrait = orientation === 'portrait';
            const doc = new jsPDF({ orientation: isPortrait ? 'portrait' : 'landscape', unit: 'mm', format: 'a4' });
            const pageWidth = doc.internal.pageSize.getWidth();
            const pageHeight = doc.internal.pageSize.getHeight();
            const generatedAt = new Date().toLocaleString();
            const reportPeriod = '<?= htmlspecialchars(($date_from ?: "Start") . " to " . ($date_to ?: "Present")) ?>';

            const drawHeader = (pageNumber) => {
                // Top accent bar
                doc.setFillColor(16, 185, 129);
                doc.rect(0, 0, pageWidth, 10, 'F');

                // Brand mark block
                doc.setFillColor(34, 197, 94);
                doc.roundedRect(14, 14, 12, 12, 2, 2, 'F');
                doc.setTextColor(255, 255, 255);
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(10);
                doc.text('EP', 20, 22, { align: 'center' });

                // Title and subtitle
                doc.setTextColor(24, 24, 27);
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(16);
                doc.text('EcoPoints Transaction Report', 30, 19);
                doc.setTextColor(82, 82, 91);
                doc.setFont('helvetica', 'normal');
                doc.setFontSize(10);
                doc.text('Detailed recycling transaction history', 30, 24);

                // Metadata (right)
                doc.setFontSize(9);
                doc.setTextColor(63, 63, 70);
                doc.text('Generated: ' + generatedAt, pageWidth - 14, 18, { align: 'right' });
                doc.text('Period: ' + reportPeriod, pageWidth - 14, 23, { align: 'right' });
                doc.text('Page ' + pageNumber, pageWidth - 14, 28, { align: 'right' });

                // Divider
                doc.setDrawColor(228, 228, 231);
                doc.setLineWidth(0.2);
                doc.line(14, 32, pageWidth - 14, 32);
            };

            const drawFooter = () => {
                doc.setDrawColor(228, 228, 231);
                doc.setLineWidth(0.2);
                doc.line(14, pageHeight - 12, pageWidth - 14, pageHeight - 12);
                doc.setFont('helvetica', 'normal');
                doc.setFontSize(8);
                doc.setTextColor(113, 113, 122);
                doc.text('EcoPoints Admin Report', 14, pageHeight - 7);
                doc.text('Confidential', pageWidth - 14, pageHeight - 7, { align: 'right' });
            };

            drawHeader(1);

            // Summary blocks
            const summaryY = 38;
            const cardWidth = isPortrait ? ((pageWidth - 14 - 14 - 4) / 2) : 62;
            const cardGap = 4;
            const cardHeight = 18;
            const cardsPerRow = isPortrait ? 2 : 4;
            const summaryCards = [
                { label: 'Total Transactions', value: '<?= number_format($stats['total_txn'] ?? 0) ?>' },
                { label: 'Total Points', value: '<?= number_format($stats['total_points'] ?? 0) ?>' },
                { label: 'Total Bottles', value: '<?= number_format($stats['total_bottles'] ?? 0) ?>' },
                { label: 'Unique Users', value: '<?= number_format($stats['unique_users'] ?? 0) ?>' }
            ];

            summaryCards.forEach((card, index) => {
                const row = Math.floor(index / cardsPerRow);
                const col = index % cardsPerRow;
                const x = 14 + col * (cardWidth + cardGap);
                const y = summaryY + row * (cardHeight + 4);
                doc.setFillColor(244, 244, 245);
                doc.roundedRect(x, y, cardWidth, cardHeight, 2, 2, 'F');
                doc.setFont('helvetica', 'normal');
                doc.setFontSize(8);
                doc.setTextColor(113, 113, 122);
                doc.text(card.label, x + 3, y + 6);
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(12);
                doc.setTextColor(24, 24, 27);
                doc.text(card.value, x + 3, y + 13);
            });

            // Filters / scope
            let filterText = 'No filters applied';
            const activeFilters = [];
            <?php if ($machine_id): ?> activeFilters.push('Machine: <?= htmlspecialchars($machine_id) ?>'); <?php endif; ?>
            <?php if ($idcard): ?> activeFilters.push('User: <?= htmlspecialchars(substr($idcard, 0, 12)) ?>...'); <?php endif; ?>
            <?php if ($date_from): ?> activeFilters.push('From: <?= htmlspecialchars($date_from) ?>'); <?php endif; ?>
            <?php if ($date_to): ?> activeFilters.push('To: <?= htmlspecialchars($date_to) ?>'); <?php endif; ?>
            if (activeFilters.length > 0) filterText = activeFilters.join(' | ');

            doc.setFont('helvetica', 'normal');
            doc.setFontSize(9);
            doc.setTextColor(82, 82, 91);
            const tableStartY = isPortrait ? 84 : 68;
            doc.text('Filters: ' + filterText, 14, tableStartY - 6);

            // Table headers
            const headers = ['Time', 'Transaction Key', 'Card ID', 'User', 'Machine', 'Location', 'Bottles', 'Points'];
            const data = [];
            
            <?php foreach ($transactions as $txn): ?>
            data.push([
                '<?= date('Y-m-d H:i:s', strtotime($txn['created_at'])) ?>',
                '<?= htmlspecialchars(substr($txn['transaction_key'], 0, 14)) ?>...',
                '<?= htmlspecialchars(substr($txn['idcard'], 0, 12)) ?>...',
                '<?= htmlspecialchars(trim($txn['fname'] . ' ' . $txn['lname']) ?: '—') ?>',
                '<?= htmlspecialchars($txn['machine_id']) ?>',
                '<?= htmlspecialchars($txn['machine_location'] ?: '—') ?>',
                '<?= $txn['item_count'] ?>',
                '<?= $txn['received_points'] ?>'
            ]);
            <?php endforeach; ?>
            
            // Generate table
            doc.autoTable({
                head: [headers],
                body: data,
                startY: tableStartY,
                styles: {
                    font: 'helvetica',
                    fontSize: isPortrait ? 7.3 : 8.5,
                    cellPadding: isPortrait ? 2 : 2.8,
                    lineColor: [228, 228, 231],
                    lineWidth: 0.1,
                    textColor: [39, 39, 42]
                },
                headStyles: {
                    fillColor: [22, 163, 74],
                    textColor: [255, 255, 255],
                    fontStyle: 'bold',
                    halign: 'left'
                },
                alternateRowStyles: {
                    fillColor: [250, 250, 250],
                },
                columnStyles: {
                    0: { cellWidth: isPortrait ? 24 : 30 },
                    1: { cellWidth: isPortrait ? 24 : 36 },
                    2: { cellWidth: isPortrait ? 20 : 30 },
                    3: { cellWidth: isPortrait ? 28 : 42 },
                    4: { cellWidth: isPortrait ? 14 : 20, halign: 'center' },
                    5: { cellWidth: isPortrait ? 40 : 56 },
                    6: { cellWidth: isPortrait ? 14 : 18, halign: 'center' },
                    7: { cellWidth: isPortrait ? 14 : 18, halign: 'right' }
                },
                margin: { top: tableStartY, bottom: 16, left: 14, right: 14 },
                didDrawPage: function (tableData) {
                    drawHeader(tableData.pageNumber);
                    drawFooter();
                }
            });
            
            // Save PDF
            doc.save('transactions_' + orientation + '_<?= date('Y-m-d') ?>.pdf');
        }
    </script>
</body>
</html>