<?php
// admin/activity_logs.php
// PenPoint Machine Sensor Telemetry & Audit Logs
// Theme: Japanese Retro Vending Maintenance & Telemetry Console
// English Console

date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/ActivityLogModel.php';

session_start();
if (empty($_SESSION['penpoint_admin'])) {
    header('Location: index.php');
    exit;
}

$conn = getConnection();
$logModel = new ActivityLogModel();

// Fetch Machine Sensors Telemetry
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

// Filters & Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$search = trim($_GET['search'] ?? '');
$type = trim($_GET['type'] ?? '');
$date_from = trim($_GET['date_from'] ?? '');
$date_to = trim($_GET['date_to'] ?? '');
$limit = 30;
$offset = ($page - 1) * $limit;

// Build WHERE query
$whereClauses = [];
if ($search !== '') {
    $safeSearch = $conn->real_escape_string($search);
    $whereClauses[] = "(details LIKE '%{$safeSearch}%' OR machine_id LIKE '%{$safeSearch}%' OR idcard LIKE '%{$safeSearch}%')";
}
if ($type !== '') {
    $safeType = $conn->real_escape_string($type);
    $whereClauses[] = "log_type = '{$safeType}'";
}
if ($date_from !== '') {
    $safeFrom = $conn->real_escape_string($date_from);
    $whereClauses[] = "DATE(created_at) >= '{$safeFrom}'";
}
if ($date_to !== '') {
    $safeTo = $conn->real_escape_string($date_to);
    $whereClauses[] = "DATE(created_at) <= '{$safeTo}'";
}

$whereSQL = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

// CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=penpoint_audit_logs_' . date('Ymd_His') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Log Type', 'Machine ID', 'Actor / Operator', 'Details', 'IP Address', 'Date & Time']);

    $expSQL = "SELECT * FROM activity_logs {$whereSQL} ORDER BY created_at DESC";
    $expRes = $conn->query($expSQL);
    if ($expRes) {
        while ($r = $expRes->fetch_assoc()) {
            fputcsv($output, [
                $r['id'],
                $r['log_type'],
                $r['machine_id'] ?? DEFAULT_MACHINE_ID,
                $r['idcard'] ?? 'SYSTEM',
                $r['details'],
                $r['ip_address'] ?? '127.0.0.1',
                $r['created_at']
            ]);
        }
    }
    fclose($output);
    exit;
}

// Get Distinct Log Types for dropdown
$typeList = [];
$tRes = $conn->query("SELECT DISTINCT log_type FROM activity_logs WHERE log_type IS NOT NULL AND log_type != '' ORDER BY log_type");
if ($tRes) {
    while ($tr = $tRes->fetch_assoc()) {
        $typeList[] = $tr['log_type'];
    }
}

// Count total
$countSQL = "SELECT COUNT(*) as count FROM activity_logs {$whereSQL}";
$countRes = $conn->query($countSQL);
$totalRows = $countRes ? (int)$countRes->fetch_assoc()['count'] : 0;
$totalPages = max(1, ceil($totalRows / $limit));

// Fetch logs
$logsSQL = "
    SELECT * 
    FROM activity_logs 
    {$whereSQL} 
    ORDER BY created_at DESC 
    LIMIT {$limit} OFFSET {$offset}
";
$logsRes = $conn->query($logsSQL);
$logs = [];
if ($logsRes) {
    while ($row = $logsRes->fetch_assoc()) {
        $logs[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#181c24">
    <title><?= APP_NAME ?> — Sensor Diagnostics & System Audit</title>
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

        html { scrollbar-width: thin; scrollbar-color: #3d465c #151821; }
        ::-webkit-scrollbar { width: 7px; height: 7px; }
        ::-webkit-scrollbar-track { background: #151821; border-left: 1px solid #232938; }
        ::-webkit-scrollbar-thumb { background: #3a445b; border-radius: 9999px; }

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

        /* Sensor Diagnostics Grid */
        .sensors-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 14px;
        }

        .sensor-card {
            background: #232938;
            border: 2px solid #3a445b;
            border-radius: var(--radius-box);
            padding: 14px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 0 #13161f;
        }

        .sensor-title {
            font-weight: 800;
            font-size: 0.85rem;
            color: var(--retro-cream);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .sensor-sub {
            font-size: 0.68rem;
            color: var(--retro-cream-muted);
            margin-top: 2px;
        }

        .badge-sensor-clear {
            background: rgba(46, 204, 113, 0.15);
            color: #2ecc71;
            border: 1px solid rgba(46, 204, 113, 0.4);
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 800;
            font-family: var(--font-mono);
        }

        .badge-sensor-beam {
            background: rgba(214, 48, 49, 0.15);
            color: #ff7675;
            border: 1px solid rgba(214, 48, 49, 0.4);
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 800;
            font-family: var(--font-mono);
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

        .badge-log {
            padding: 2px 7px;
            border-radius: 4px;
            font-size: 0.68rem;
            font-weight: 800;
            font-family: var(--font-mono);
            display: inline-block;
        }

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
            .sensors-grid { grid-template-columns: 1fr; }
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
                    <span class="sign-kanji">監査</span>
                    <span class="sign-sub-tag">AUDIT</span>
                </div>
                <div class="brand-text">
                    <h1><span><?= APP_NAME ?></span><span class="brand-jp-badge">SENSORS & AUDIT</span></h1>
                </div>
            </a>

            <div class="nav-actions">
                <a href="index.php" class="retro-link-btn">
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
                <a href="activity_logs.php" class="retro-link-btn active">
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

        <!-- Live Sensor Diagnostics Bar -->
        <div class="retro-panel-card">
            <div class="panel-header-strip">
                <div class="panel-heading">
                    <i data-lucide="cpu" style="color: #74b9ff; width: 18px; height: 18px;"></i>
                    Live ESP32 Cabinet & IR Break-Beam Diagnostics
                </div>
                <div style="font-family: var(--font-mono); font-size: 0.7rem; color: #4ade80; display: flex; align-items: center; gap: 6px;">
                    <span style="width: 8px; height: 8px; border-radius: 50%; background: #4ade80; box-shadow: 0 0 8px #4ade80;"></span>
                    PING: <?= htmlspecialchars($sensorData['last_ping'] ?? 'LIVE') ?>
                </div>
            </div>

            <div class="sensors-grid">
                <div class="sensor-card">
                    <div>
                        <div class="sensor-title">
                            <i data-lucide="radio" style="width: 14px; height: 14px; color: #74b9ff;"></i>
                            Tier 1 IR Beam
                        </div>
                        <div class="sensor-sub">Long Bond Exit</div>
                    </div>
                    <span class="<?= $sensorData['tier_1_ir'] === 'CLEAR' ? 'badge-sensor-clear' : 'badge-sensor-beam' ?>">
                        <?= htmlspecialchars($sensorData['tier_1_ir']) ?>
                    </span>
                </div>

                <div class="sensor-card">
                    <div>
                        <div class="sensor-title">
                            <i data-lucide="radio" style="width: 14px; height: 14px; color: #74b9ff;"></i>
                            Tier 2 IR Beam
                        </div>
                        <div class="sensor-sub">A4 Bond Exit</div>
                    </div>
                    <span class="<?= $sensorData['tier_2_ir'] === 'CLEAR' ? 'badge-sensor-clear' : 'badge-sensor-beam' ?>">
                        <?= htmlspecialchars($sensorData['tier_2_ir']) ?>
                    </span>
                </div>

                <div class="sensor-card">
                    <div>
                        <div class="sensor-title">
                            <i data-lucide="radio" style="width: 14px; height: 14px; color: #74b9ff;"></i>
                            Tier 3 IR Beam
                        </div>
                        <div class="sensor-sub">Short Bond Exit</div>
                    </div>
                    <span class="<?= $sensorData['tier_3_ir'] === 'CLEAR' ? 'badge-sensor-clear' : 'badge-sensor-beam' ?>">
                        <?= htmlspecialchars($sensorData['tier_3_ir']) ?>
                    </span>
                </div>

                <div class="sensor-card">
                    <div>
                        <div class="sensor-title">
                            <i data-lucide="radio" style="width: 14px; height: 14px; color: #74b9ff;"></i>
                            Tier 4 IR Beam
                        </div>
                        <div class="sensor-sub">Stationery Chute (Slots 4-6)</div>
                    </div>
                    <span class="<?= $sensorData['tier_4_ir'] === 'CLEAR' ? 'badge-sensor-clear' : 'badge-sensor-beam' ?>">
                        <?= htmlspecialchars($sensorData['tier_4_ir']) ?>
                    </span>
                </div>

                <div class="sensor-card" style="border-left: 3px solid <?= $sensorData['door_switch'] === 'CLOSED' ? '#2ecc71' : '#e74c3c' ?>;">
                    <div>
                        <div class="sensor-title">
                            <i data-lucide="shield-alert" style="width: 14px; height: 14px; color: #f39c12;"></i>
                            Door Limit Switch
                        </div>
                        <div class="sensor-sub">Anti-Tamper Monitor</div>
                    </div>
                    <span class="<?= $sensorData['door_switch'] === 'CLOSED' ? 'badge-sensor-clear' : 'badge-sensor-beam' ?>">
                        <?= $sensorData['door_switch'] === 'CLOSED' ? 'LOCKED / CLOSED' : 'ALARM: TAMPER OPEN' ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- System Audit Log Panel -->
        <div class="retro-panel-card">
            <div class="panel-header-strip">
                <div class="panel-heading">
                    <i data-lucide="scroll" style="color: #ffeaa7; width: 18px; height: 18px;"></i>
                    Activity Audit Trail (<?= number_format($totalRows) ?> Events)
                </div>
                <div>
                    <a href="activity_logs.php?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" class="btn-csv-export">
                        <i data-lucide="download" style="width: 14px; height: 14px;"></i>
                        Export Audit CSV
                    </a>
                </div>
            </div>

            <!-- Filter Controls -->
            <form method="GET" action="activity_logs.php" class="filter-bar">
                <input type="text" name="search" class="filter-input" placeholder="Search details, actor, machine..." value="<?= htmlspecialchars($search) ?>" style="flex: 1; min-width: 180px;">

                <select name="type" class="filter-select">
                    <option value="">All Event Types</option>
                    <?php foreach ($typeList as $tl): ?>
                        <option value="<?= htmlspecialchars($tl) ?>" <?= $type === $tl ? 'selected' : '' ?>>
                            <?= htmlspecialchars(strtoupper($tl)) ?>
                        </option>
                    <?php endforeach; ?>
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
                <?php if ($search || $type || $date_from || $date_to): ?>
                    <a href="activity_logs.php" class="btn-filter" style="background: transparent; border-color: #555;">Clear</a>
                <?php endif; ?>
            </form>

            <!-- Table -->
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Event Type</th>
                            <th>Details & Action Taken</th>
                            <th>Actor / Operator</th>
                            <th>Machine Unit</th>
                            <th>IP Address</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 36px 0; color: var(--retro-cream-muted);">
                                    <i data-lucide="shield-check" style="width: 28px; height: 28px; opacity: 0.5; margin-bottom: 6px; display: inline-block;"></i><br>
                                    No activity events found matching criteria.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $l): ?>
                                <?php 
                                    $lt = strtolower($l['log_type'] ?? 'info');
                                    $badgeColor = '#74b9ff';
                                    $badgeBg = 'rgba(116, 185, 255, 0.15)';
                                    if (strpos($lt, 'restock') !== false) {
                                        $badgeColor = '#ffeaa7';
                                        $badgeBg = 'rgba(255, 234, 167, 0.15)';
                                    } elseif (strpos($lt, 'refund') !== false || strpos($lt, 'alert') !== false || strpos($lt, 'error') !== false) {
                                        $badgeColor = '#ff7675';
                                        $badgeBg = 'rgba(214, 48, 49, 0.15)';
                                    } elseif (strpos($lt, 'dispense') !== false || strpos($lt, 'success') !== false) {
                                        $badgeColor = '#2ecc71';
                                        $badgeBg = 'rgba(46, 204, 113, 0.15)';
                                    }
                                ?>
                                <tr>
                                    <td>
                                        <span class="badge-log" style="color: <?= $badgeColor ?>; background: <?= $badgeBg ?>; border: 1px solid <?= $badgeColor ?>44;">
                                            <?= htmlspecialchars(strtoupper($l['log_type'] ?? 'EVENT')) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="font-weight: 700; color: var(--retro-cream); line-height: 1.4;">
                                            <?= htmlspecialchars($l['details']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span style="font-family: var(--font-mono); font-size: 0.72rem; color: #ffeaa7;">
                                            <?= htmlspecialchars($l['idcard'] ?? 'SYSTEM') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span style="font-family: var(--font-mono); font-size: 0.72rem; color: #74b9ff;">
                                            <?= htmlspecialchars($l['machine_id'] ?? DEFAULT_MACHINE_ID) ?>
                                        </span>
                                    </td>
                                    <td style="font-family: var(--font-mono); font-size: 0.7rem; color: var(--retro-cream-muted);">
                                        <?= htmlspecialchars($l['ip_address'] ?? '127.0.0.1') ?>
                                    </td>
                                    <td style="font-family: var(--font-mono); font-size: 0.72rem; color: var(--retro-cream-muted);">
                                        <?= date('M d, Y • h:i A', strtotime($l['created_at'])) ?>
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
                        <a href="activity_logs.php?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="page-btn">« Prev</a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <a href="activity_logs.php?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="page-btn <?= $i === $page ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="activity_logs.php?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="page-btn">Next »</a>
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
    </script>
</body>
</html>