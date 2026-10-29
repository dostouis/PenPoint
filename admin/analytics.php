<?php
// admin/analytics.php
// PenPoint Sales Analytics & Product Demand Trends
// Theme: Japanese Retro Vending Maintenance & Telemetry Console
// English Console

date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/ProductModel.php';
require_once __DIR__ . '/../models/TransactionModel.php';

session_start();
if (empty($_SESSION['penpoint_admin'])) {
    header('Location: index.php');
    exit;
}

$productModel = new ProductModel();
$txnModel = new TransactionModel();
$products = $productModel->getAllProducts();

// Sort by popularity
usort($products, function($a, $b) {
    return $b['dispensed_count'] - $a['dispensed_count'];
});

$totalDispensed = array_sum(array_column($products, 'dispensed_count'));

// Calculate estimated revenue
$totalRevenue = 0;
foreach ($products as $p) {
    $totalRevenue += ($p['dispensed_count'] * $p['price']);
}

// Visual analytics data
$salesTrend = $txnModel->getDailySalesTrend(7);
$categorySales = $txnModel->getCategoryBreakdown();

// 7-day timeline arrays
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
    <title><?= APP_NAME ?> — Sales & Demand Analytics</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Zen+Maru+Gothic:wght@500;700;900&family=Noto+Sans+JP:wght@400;600;700;900&family=Dela+Gothic+One&family=DM+Mono:ital,wght@0,400;0,500;1,400&display=swap" rel="stylesheet">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <!-- Chart.js for Visual Analytics -->
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

        /* Charts Row */
        .charts-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }

        @media (max-width: 900px) {
            .charts-row { grid-template-columns: 1fr; }
        }

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

        .chart-box-canvas {
            position: relative;
            height: 240px;
            width: 100%;
        }

        /* Bar Rankings */
        .bar-row {
            margin-bottom: 16px;
        }

        .bar-label-group {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            font-size: 0.82rem;
            margin-bottom: 6px;
        }

        .bar-track {
            height: 10px;
            background: #181d27;
            border-radius: 9999px;
            overflow: hidden;
            border: 1px solid #333a4c;
        }

        .bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #ff4757, var(--retro-red));
            border-radius: 9999px;
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
                    <span class="sign-kanji">統計</span>
                    <span class="sign-sub-tag">DATA</span>
                </div>
                <div class="brand-text">
                    <h1><span><?= APP_NAME ?></span><span class="brand-jp-badge">ANALYTICS</span></h1>
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
                <a href="analytics.php" class="retro-link-btn active">
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
                <div class="metric-icon-box" style="color: #2ecc71;">
                    <i data-lucide="check-circle" style="width: 22px; height: 22px;"></i>
                </div>
                <div>
                    <div class="metric-val"><?= $totalDispensed ?></div>
                    <div class="metric-caption">All-Time Units Dispensed</div>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-icon-box" style="color: #ffeaa7;">
                    <i data-lucide="credit-card" style="width: 22px; height: 22px;"></i>
                </div>
                <div>
                    <div class="metric-val"><?= CURRENCY_SYMBOL ?><?= number_format($totalRevenue, 2) ?></div>
                    <div class="metric-caption">Gross Dispense Value</div>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-icon-box" style="color: #74b9ff;">
                    <i data-lucide="award" style="width: 22px; height: 22px;"></i>
                </div>
                <div>
                    <div class="metric-val" style="font-size: 1.15rem; font-family: var(--font-body); color: #74b9ff;">
                        <?= !empty($products) ? htmlspecialchars($products[0]['name']) : 'N/A' ?>
                    </div>
                    <div class="metric-caption">Top Selling Stationery</div>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-icon-box" style="color: var(--retro-mustard);">
                    <i data-lucide="layers" style="width: 22px; height: 22px;"></i>
                </div>
                <div>
                    <div class="metric-val"><?= count($products) ?> Slots</div>
                    <div class="metric-caption">Active Tray Capacity</div>
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

        <!-- Stationery Rankings Card -->
        <div class="retro-panel-card">
            <div class="panel-header-strip">
                <div class="panel-heading">
                    <i data-lucide="bar-chart-2" style="color: var(--retro-mustard); width: 18px; height: 18px;"></i>
                    Stationery Dispense Rankings & Share
                </div>
                <span style="font-family: var(--font-mono); font-size: 0.68rem; color: #ffeaa7;">ALL-TIME VOLUME</span>
            </div>
            <p style="font-size: 0.78rem; color: var(--retro-cream-muted);">
                Demand ranking across all 6 mechanical trays and lanes. Total verified drops: <strong style="color: #ffeaa7; font-family: var(--font-mono);"><?= $totalDispensed ?> units</strong>.
            </p>

            <div style="display: flex; flex-direction: column; gap: 14px; margin-top: 10px;">
                <?php foreach ($products as $p): ?>
                    <?php 
                        $percent = ($totalDispensed > 0) ? round(($p['dispensed_count'] / $totalDispensed) * 100) : 0;
                        $tierNum = $p['tier_number'] ?? ($p['slot_number'] <= 3 ? $p['slot_number'] : 4);
                    ?>
                    <div class="bar-row">
                        <div class="bar-label-group">
                            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                <span style="font-family: var(--font-mono); font-weight: 800; color: #74b9ff; font-size: 0.78rem;">SLOT #<?= $p['slot_number'] ?></span>
                                <span style="font-size: 0.68rem; font-family: var(--font-mono); color: var(--retro-cream-muted); background: #181d27; padding: 2px 7px; border-radius: 4px; border: 1px solid #333a4c;">Tier <?= $tierNum ?></span>
                                <strong style="color: var(--retro-cream); font-size: 0.88rem;"><?= htmlspecialchars($p['name']) ?></strong>
                                <span style="font-size: 0.72rem; color: var(--retro-cream-muted);">(<?= htmlspecialchars($p['category']) ?>)</span>
                            </div>
                            <div style="font-family: var(--font-mono); font-weight: 800; color: #ffeaa7;">
                                <?= $p['dispensed_count'] ?> units (<?= $percent ?>%)
                            </div>
                        </div>
                        <div class="bar-track">
                            <div class="bar-fill" style="width: <?= max(4, $percent) ?>%;"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>

    <script>
        lucide.createIcons();

        // Chart.js Theme Defaults for Japanese Retro Palette
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
