<?php
// admin/temp_transactions.php
// Temporary Transactions Management Page

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/TempTransactionModel.php';

$appName = defined('APP_NAME') ? APP_NAME : 'EcoPoints';

$tempModel = new TempTransactionModel();
$conn = getConnection();

// Get filter parameters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$status = $_GET['status'] ?? '';
$machine_id = $_GET['machine_id'] ?? '';
$limit = 50;
$offset = ($page - 1) * $limit;

// Get transactions
$transactions = $tempModel->getAll($limit, $offset, $status ?: null, $machine_id ?: null);
$totalCount = $tempModel->getCount($status ?: null, $machine_id ?: null);
$totalPages = ceil($totalCount / $limit);

// Get stats
$statsSQL = "
    SELECT 
        status, COUNT(*) as count 
    FROM temp_transactions 
    GROUP BY status
";
$statsResult = $conn->query($statsSQL);
$stats = [];
while ($row = $statsResult->fetch_assoc()) {
    $stats[$row['status']] = $row['count'];
}

// Get machines for filter
$machines = [];
$machineResult = $conn->query("SELECT DISTINCT machine_id, location FROM machines ORDER BY machine_id");
while ($row = $machineResult->fetch_assoc()) {
    $machines[] = $row;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Temp Transactions - <?= htmlspecialchars($appName) ?> Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;600;700;800;900&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
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
            --orange: #f39c12;
            --purple: #9b59b6;
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

        .stats-mini {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 14px;
            margin-bottom: 24px;
        }

        .stat-mini-card {
            background: linear-gradient(145deg, rgba(14,40,22,0.9), rgba(9,25,14,0.95));
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 16px;
            text-align: center;
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

        .filter-select {
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

        .badge-pending { background: rgba(243,156,18,0.12); color: var(--orange); border: 1px solid rgba(243,156,18,0.2); }
        .badge-done { background: rgba(53,224,122,0.12); color: var(--green-bright); border: 1px solid rgba(53,224,122,0.2); }
        .badge-unchanged { background: rgba(90,180,255,0.12); color: var(--blue); border: 1px solid rgba(90,180,255,0.2); }
        .badge-cancelled { background: rgba(224,90,90,0.12); color: var(--red); border: 1px solid rgba(224,90,90,0.2); }

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

        .btn-success { background: var(--green-bright); color: #061410; margin-right: 5px; }
        .btn-danger { background: var(--red); color: white; }
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
            <a href="activity_logs.php" class="nav-item"><span class="icon">📜</span> Activity Logs</a>
            <a href="transaction_logs.php" class="nav-item"><span class="icon">📊</span> Transaction Logs</a>
            <a href="temp_transactions.php" class="nav-item active"><span class="icon">⏳</span> Temp Transactions</a>
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
                <h2>Temporary Transactions ⏳</h2>
                <div style="font-size:13px;color:var(--muted);margin-top:4px;font-family:'DM Mono',monospace">Pending machine sessions awaiting commit</div>
            </div>
            <div class="topbar-right">
                <a href="temp_transactions.php" class="btn btn-ghost">🔄 Refresh</a>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-mini">
            <div class="stat-mini-card">
                <div class="stat-mini-label">Pending</div>
                <div class="stat-mini-value" style="color: var(--orange);"><?= number_format($stats['pending'] ?? 0) ?></div>
            </div>
            <div class="stat-mini-card">
                <div class="stat-mini-label">Done</div>
                <div class="stat-mini-value" style="color: var(--green-bright);"><?= number_format($stats['done'] ?? 0) ?></div>
            </div>
            <div class="stat-mini-card">
                <div class="stat-mini-label">Unchanged</div>
                <div class="stat-mini-value" style="color: var(--blue);"><?= number_format($stats['unchanged'] ?? 0) ?></div>
            </div>
            <div class="stat-mini-card">
                <div class="stat-mini-label">Cancelled</div>
                <div class="stat-mini-value" style="color: var(--red);"><?= number_format($stats['cancelled'] ?? 0) ?></div>
            </div>
            <div class="stat-mini-card">
                <div class="stat-mini-label">Total</div>
                <div class="stat-mini-value"><?= number_format($totalCount) ?></div>
            </div>
        </div>

        <!-- Filter Bar -->
        <form class="filter-bar" method="GET">
            <div class="filter-group">
                <label>Status:</label>
                <select name="status" class="filter-select" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="done" <?= $status === 'done' ? 'selected' : '' ?>>Done</option>
                    <option value="unchanged" <?= $status === 'unchanged' ? 'selected' : '' ?>>Unchanged</option>
                    <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>
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
            <?php if ($status || $machine_id): ?>
            <a href="temp_transactions.php" class="btn btn-ghost" style="margin-left:auto;">Clear Filters</a>
            <?php endif; ?>
        </form>

        <!-- Transactions Table -->
        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Machine</th>
                        <th>Card ID</th>
                        <th>User</th>
                        <th>Bottles</th>
                        <th>Points</th>
                        <th>Status</th>
                        <th>Session Key</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                    <tr><td colspan="8" class="empty-state"><div class="empty-icon">⏳</div>No temporary transactions found</td></tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $txn): 
                            $badgeClass = match($txn['status']) {
                                'pending' => 'badge-pending',
                                'done' => 'badge-done',
                                'unchanged' => 'badge-unchanged',
                                'cancelled' => 'badge-cancelled',
                                default => ''
                            };
                        ?>
                        <tr>
                            <td style="font-family:'DM Mono',monospace;font-size:11px;color:var(--muted);"><?= date('M j, g:i:s A', strtotime($txn['created_at'])) ?></td>
                            <td><span class="badge" style="background:rgba(90,180,255,0.12);color:var(--blue);"><?= htmlspecialchars($txn['machine_id']) ?></span></td>
                            <td style="font-family:'DM Mono',monospace;font-size:12px;"><?= htmlspecialchars(substr($txn['idcard'], 0, 12)) ?>...</td>
                            <td><?= htmlspecialchars(trim($txn['fname'] . ' ' . $txn['lname']) ?: '—') ?></td>
                            <td>🍶 <?= $txn['bottle_count'] ?></td>
                            <td><span style="color:var(--green-bright);">+<?= $txn['points_to_add'] ?></span></td>
                            <td><span class="badge <?= $badgeClass ?>"><?= ucfirst($txn['status']) ?></span></td>
                            <td style="font-family:'DM Mono',monospace;font-size:10px;" title="<?= htmlspecialchars($txn['transaction_key']) ?>">
                                <?= htmlspecialchars(substr($txn['transaction_key'], 0, 16)) ?>...
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
            <a href="?page=<?= $page-1 ?>&status=<?= urlencode($status) ?>&machine_id=<?= urlencode($machine_id) ?>" class="page-link">← Previous</a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
            <a href="?page=<?= $i ?>&status=<?= urlencode($status) ?>&machine_id=<?= urlencode($machine_id) ?>" class="page-link <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page+1 ?>&status=<?= urlencode($status) ?>&machine_id=<?= urlencode($machine_id) ?>" class="page-link">Next →</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </main>
</body>
</html>