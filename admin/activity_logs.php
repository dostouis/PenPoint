<?php
// admin/activity_logs.php
// Activity Logs Page

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/ActivityLogModel.php';

$appName = defined('APP_NAME') ? APP_NAME : 'EcoPoints';

$logModel = new ActivityLogModel();

// Get filter parameters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$type = $_GET['type'] ?? '';
$idcard = $_GET['idcard'] ?? '';
$limit = 50;
$offset = ($page - 1) * $limit;

// Get logs
$logs = $logModel->getLogs($limit, $offset, $type ?: null, $idcard ?: null);
$totalLogs = $logModel->getTotalCount($type ?: null, $idcard ?: null);
$totalPages = ceil($totalLogs / $limit);

// Get stats
$stats = $logModel->getStats();
$logTypes = $logModel->getLogTypes();

// Get users for filter dropdown
$conn = getConnection();
$users = [];
$userResult = $conn->query("SELECT DISTINCT idcard, fname, lname FROM users ORDER BY fname");
while ($row = $userResult->fetch_assoc()) {
    $users[] = $row;
}
$conn->close();

$logModel->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - <?= htmlspecialchars($appName) ?> Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;600;700;800;900&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
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
            box-shadow: 0 8px 24px rgba(53,224,122,0.25);
        }

        .btn-primary:hover { filter: brightness(1.08); }

        .export-dropdown {
            position: relative;
            display: inline-block;
        }

        .export-menu {
            position: absolute;
            top: 100%;
            right: 0;
            margin-top: 8px;
            background: var(--surface-solid);
            border: 1px solid var(--border);
            border-radius: 12px;
            min-width: 220px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.45);
            z-index: 1000;
            display: none;
            overflow: hidden;
        }

        .export-menu.show { display: block; }

        .export-option {
            padding: 11px 14px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 700;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 8px;
            border-bottom: 1px solid rgba(53,224,122,0.08);
        }

        .export-option:last-child { border-bottom: none; }
        .export-option:hover { background: rgba(53,224,122,0.08); color: var(--green-bright); }

        /* Stats Cards */
        .stats-mini {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-mini-card {
            background: linear-gradient(145deg, rgba(14,40,22,0.9), rgba(9,25,14,0.95));
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 18px;
        }

        .stat-mini-label {
            font-size: 10px;
            font-family: 'DM Mono', monospace;
            color: var(--muted);
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .stat-mini-value {
            font-size: 28px;
            font-weight: 900;
            color: var(--text);
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
            gap: 16px;
            flex-wrap: wrap;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
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
            padding: 10px 14px;
            color: var(--text);
            font-family: 'Nunito Sans', sans-serif;
            font-size: 13px;
            outline: none;
            min-width: 160px;
        }

        .filter-select option { background: var(--surface-solid); }

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
            padding: 14px 18px;
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
            padding: 14px 18px;
            font-size: 13px;
            font-weight: 600;
            border-bottom: 1px solid rgba(53,224,122,0.05);
            color: var(--text);
        }

        tr:hover td { background: rgba(53,224,122,0.03); }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 800;
            font-family: 'DM Mono', monospace;
        }

        .badge-scan { background: rgba(90,180,255,0.12); color: var(--blue); border: 1px solid rgba(90,180,255,0.2); }
        .badge-transaction { background: rgba(53,224,122,0.12); color: var(--green-bright); border: 1px solid rgba(53,224,122,0.2); }
        .badge-registration { background: rgba(245,200,66,0.12); color: var(--accent); border: 1px solid rgba(245,200,66,0.2); }
        .badge-reward { background: rgba(168,240,198,0.12); color: var(--green-light); border: 1px solid rgba(168,240,198,0.2); }
        .badge-profile { background: rgba(200,180,255,0.12); color: #b8a2ff; border: 1px solid rgba(200,180,255,0.2); }
        .badge-error { background: rgba(224,90,90,0.12); color: var(--red); border: 1px solid rgba(224,90,90,0.2); }

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
            <div class="nav-label">Overview</div>
            <a href="index.php" class="nav-item"><span class="icon">📊</span> Dashboard</a>
            
            <div class="nav-label">Data</div>
            <a href="index.php?page=users" class="nav-item"><span class="icon">👤</span> Users</a>
            <a href="index.php?page=transactions" class="nav-item"><span class="icon">📋</span> Transactions</a>
            <a href="index.php?page=leaderboard" class="nav-item"><span class="icon">🏆</span> Leaderboard</a>
            <a href="analytics.php" class="nav-item"><span class="icon">📈</span> Analytics</a>
            
            <div class="nav-label">System</div>
            <a href="index.php?page=rewards" class="nav-item"><span class="icon">🎁</span> Rewards</a>
            <a href="index.php?page=machines" class="nav-item"><span class="icon">🤖</span> Machines</a>
            <a href="activity_logs.php" class="nav-item active"><span class="icon">📜</span> Activity Logs</a>
            <a href="transaction_logs.php" class="nav-item"><span class="icon">📊</span> Transaction Logs</a>
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
                <h2>Activity Logs 📜</h2>
                <div style="font-size:13px;color:var(--muted);margin-top:4px;font-family:'DM Mono',monospace">System-wide activity tracking</div>
            </div>
            <div class="topbar-right">
                <a href="activity_logs.php" class="btn btn-ghost">🔄 Refresh</a>
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

        <!-- Mini Stats -->
        <div class="stats-mini">
            <div class="stat-mini-card">
                <div class="stat-mini-label">Total Events</div>
                <div class="stat-mini-value"><?= number_format($stats['total']) ?></div>
            </div>
            <div class="stat-mini-card">
                <div class="stat-mini-label">Today's Activity</div>
                <div class="stat-mini-value"><?= number_format($stats['today']) ?></div>
            </div>
            <div class="stat-mini-card">
                <div class="stat-mini-label">Recent Errors (24h)</div>
                <div class="stat-mini-value" style="color: <?= $stats['recent_errors'] > 0 ? 'var(--red)' : 'var(--green-bright)' ?>"><?= $stats['recent_errors'] ?></div>
            </div>
            <div class="stat-mini-card">
                <div class="stat-mini-label">Transactions</div>
                <div class="stat-mini-value"><?= number_format($stats['by_type']['transaction'] ?? 0) ?></div>
            </div>
        </div>

        <!-- Filter Bar -->
        <form class="filter-bar" method="GET">
            <div class="filter-group">
                <label>Type:</label>
                <select name="type" class="filter-select" onchange="this.form.submit()">
                    <option value="">All Types</option>
                    <?php foreach ($logTypes as $t): ?>
                    <option value="<?= htmlspecialchars($t) ?>" <?= $type === $t ? 'selected' : '' ?>>
                        <?= ucfirst($t) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Card ID:</label>
                <select name="idcard" class="filter-select" onchange="this.form.submit()">
                    <option value="">All Users</option>
                    <?php foreach ($users as $u): ?>
                    <option value="<?= htmlspecialchars($u['idcard']) ?>" <?= $idcard === $u['idcard'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars(substr($u['idcard'], 0, 10)) ?>... (<?= htmlspecialchars($u['fname'] ?: '—') ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($type || $idcard): ?>
            <a href="activity_logs.php" class="btn btn-ghost" style="margin-left:auto;">Clear Filters</a>
            <?php endif; ?>
        </form>

        <!-- Logs Table -->
        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Type</th>
                        <th>Card ID</th>
                        <th>User</th>
                        <th>Machine</th>
                        <th>Details</th>
                        <th>Points</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                    <tr><td colspan="8" class="empty-state"><div class="empty-icon">📜</div>No activity logs found</td></tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): 
                            $badgeClass = match($log['log_type']) {
                                'scan' => 'badge-scan',
                                'transaction' => 'badge-transaction',
                                'registration' => 'badge-registration',
                                'reward_claim' => 'badge-reward',
                                'profile_update' => 'badge-profile',
                                'error' => 'badge-error',
                                default => ''
                            };
                        ?>
                        <tr>
                            <td style="font-family:'DM Mono',monospace;font-size:11px;color:var(--muted);"><?= date('M j, g:i:s A', strtotime($log['created_at'])) ?></td>
                            <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($log['log_type'] ?? '—') ?></span></td>
                            <td style="font-family:'DM Mono',monospace;font-size:12px;"><?= htmlspecialchars(substr($log['idcard'] ?? '—', 0, 10)) ?>...</td>
                            <td><?= htmlspecialchars(trim($log['fname'] . ' ' . $log['lname']) ?: '—') ?></td>
                            <td><span style="font-family:'DM Mono',monospace;"><?= htmlspecialchars($log['machine_id'] ?? '—') ?></span></td>
                            <td style="max-width:250px;"><?= htmlspecialchars($log['details'] ?? '—') ?></td>
                            <td style="font-family:'DM Mono',monospace;">
                                <?php if ($log['points_change'] !== null): ?>
                                <span style="color: <?= $log['points_change'] >= 0 ? 'var(--green-bright)' : 'var(--red)' ?>">
                                    <?= $log['points_change'] > 0 ? '+' : '' ?><?= $log['points_change'] ?>
                                </span>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td style="font-family:'DM Mono',monospace;font-size:11px;color:var(--muted);"><?= htmlspecialchars($log['ip_address'] ?? '—') ?></td>
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
            <a href="?page=<?= $page-1 ?>&type=<?= urlencode($type) ?>&idcard=<?= urlencode($idcard) ?>" class="page-link">← Previous</a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
            <a href="?page=<?= $i ?>&type=<?= urlencode($type) ?>&idcard=<?= urlencode($idcard) ?>" class="page-link <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page+1 ?>&type=<?= urlencode($type) ?>&idcard=<?= urlencode($idcard) ?>" class="page-link">Next →</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </main>
    <script>
        function toggleExportMenu() {
            const menu = document.getElementById('exportMenu');
            menu.classList.toggle('show');
        }

        document.addEventListener('click', function (e) {
            if (!e.target.closest('.export-dropdown')) {
                document.getElementById('exportMenu').classList.remove('show');
            }
        });

        function exportToCSV() {
            document.getElementById('exportMenu').classList.remove('show');

            const rows = [];
            const headers = ['Time', 'Type', 'Card ID', 'User', 'Machine', 'Details', 'Points', 'IP'];
            rows.push(headers.join(','));

            <?php foreach ($logs as $log): ?>
            rows.push([
                '<?= date('Y-m-d H:i:s', strtotime($log['created_at'])) ?>',
                '<?= str_replace([",", "\r", "\n"], [";", "", ""], htmlspecialchars($log['log_type'] ?? '—')) ?>',
                '<?= htmlspecialchars($log['idcard'] ?? '—') ?>',
                '<?= str_replace([",", "\r", "\n"], [";", "", ""], htmlspecialchars(trim(($log['fname'] ?? '') . ' ' . ($log['lname'] ?? '')) ?: '—')) ?>',
                '<?= str_replace([",", "\r", "\n"], [";", "", ""], htmlspecialchars($log['machine_id'] ?? '—')) ?>',
                '<?= str_replace([",", "\r", "\n"], [";", "", ""], htmlspecialchars($log['details'] ?? '—')) ?>',
                '<?= $log['points_change'] !== null ? $log['points_change'] : '—' ?>',
                '<?= htmlspecialchars($log['ip_address'] ?? '—') ?>'
            ].join(','));
            <?php endforeach; ?>

            const csv = rows.join('\n');
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'activity_logs_<?= date('Y-m-d') ?>.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }

        function exportToPDF(orientation = 'landscape') {
            document.getElementById('exportMenu').classList.remove('show');

            const { jsPDF } = window.jspdf;
            const isPortrait = orientation === 'portrait';
            const doc = new jsPDF({ orientation: isPortrait ? 'portrait' : 'landscape', unit: 'mm', format: 'a4' });
            const pageWidth = doc.internal.pageSize.getWidth();
            const pageHeight = doc.internal.pageSize.getHeight();
            const generatedAt = new Date().toLocaleString();

            const drawHeader = (pageNumber) => {
                doc.setFillColor(16, 185, 129);
                doc.rect(0, 0, pageWidth, 10, 'F');

                doc.setFillColor(34, 197, 94);
                doc.roundedRect(14, 14, 12, 12, 2, 2, 'F');
                doc.setTextColor(255, 255, 255);
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(10);
                doc.text('EP', 20, 22, { align: 'center' });

                doc.setTextColor(24, 24, 27);
                doc.setFont('helvetica', 'bold');
                doc.setFontSize(16);
                doc.text('EcoPoints Activity Log Report', 30, 19);
                doc.setTextColor(82, 82, 91);
                doc.setFont('helvetica', 'normal');
                doc.setFontSize(10);
                doc.text('System-wide activity tracking', 30, 24);

                doc.setFontSize(9);
                doc.setTextColor(63, 63, 70);
                doc.text('Generated: ' + generatedAt, pageWidth - 14, 18, { align: 'right' });
                doc.text('Page ' + pageNumber, pageWidth - 14, 23, { align: 'right' });

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

            const summaryY = 38;
            const cardWidth = isPortrait ? ((pageWidth - 14 - 14 - 4) / 2) : 62;
            const cardGap = 4;
            const cardHeight = 18;
            const cardsPerRow = isPortrait ? 2 : 4;
            const summaryCards = [
                { label: 'Total Events', value: '<?= number_format($stats['total']) ?>' },
                { label: "Today's Activity", value: '<?= number_format($stats['today']) ?>' },
                { label: 'Recent Errors (24h)', value: '<?= number_format($stats['recent_errors']) ?>' },
                { label: 'Transactions', value: '<?= number_format($stats['by_type']['transaction'] ?? 0) ?>' }
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

            let filterText = 'No filters applied';
            const activeFilters = [];
            <?php if ($type): ?> activeFilters.push('Type: <?= htmlspecialchars($type) ?>'); <?php endif; ?>
            <?php if ($idcard): ?> activeFilters.push('User: <?= htmlspecialchars(substr($idcard, 0, 12)) ?>...'); <?php endif; ?>
            if (activeFilters.length > 0) filterText = activeFilters.join(' | ');

            const tableStartY = isPortrait ? 84 : 68;
            doc.setFont('helvetica', 'normal');
            doc.setFontSize(9);
            doc.setTextColor(82, 82, 91);
            doc.text('Filters: ' + filterText, 14, tableStartY - 6);

            const headers = ['Time', 'Type', 'Card ID', 'User', 'Machine', 'Details', 'Points', 'IP'];
            const data = [];
            <?php foreach ($logs as $log): ?>
            data.push([
                '<?= date('Y-m-d H:i:s', strtotime($log['created_at'])) ?>',
                '<?= htmlspecialchars($log['log_type'] ?? '—') ?>',
                '<?= htmlspecialchars(substr($log['idcard'] ?? '—', 0, 12)) ?>...',
                '<?= htmlspecialchars(trim(($log['fname'] ?? '') . ' ' . ($log['lname'] ?? '')) ?: '—') ?>',
                '<?= htmlspecialchars($log['machine_id'] ?? '—') ?>',
                '<?= htmlspecialchars(substr($log['details'] ?? '—', 0, 60)) ?>',
                '<?= $log['points_change'] !== null ? $log['points_change'] : '—' ?>',
                '<?= htmlspecialchars($log['ip_address'] ?? '—') ?>'
            ]);
            <?php endforeach; ?>

            doc.autoTable({
                head: [headers],
                body: data,
                startY: tableStartY,
                styles: {
                    font: 'helvetica',
                    fontSize: isPortrait ? 7.2 : 8.3,
                    cellPadding: isPortrait ? 2 : 2.6,
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
                    fillColor: [250, 250, 250]
                },
                columnStyles: {
                    0: { cellWidth: isPortrait ? 20 : 28 },
                    1: { cellWidth: isPortrait ? 16 : 20 },
                    2: { cellWidth: isPortrait ? 18 : 26 },
                    3: { cellWidth: isPortrait ? 22 : 30 },
                    4: { cellWidth: isPortrait ? 14 : 20, halign: 'center' },
                    5: { cellWidth: isPortrait ? 45 : 80 },
                    6: { cellWidth: isPortrait ? 14 : 16, halign: 'right' },
                    7: { cellWidth: isPortrait ? 24 : 36 }
                },
                margin: { top: tableStartY, bottom: 16, left: 14, right: 14 },
                didDrawPage: function (tableData) {
                    drawHeader(tableData.pageNumber);
                    drawFooter();
                }
            });

            doc.save('activity_logs_' + orientation + '_<?= date('Y-m-d') ?>.pdf');
        }
    </script>
</body>
</html>