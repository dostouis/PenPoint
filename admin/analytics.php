<?php
// admin/analytics.php
// PenPoint Sales Analytics & Product Demand Trends
// Theme: Japanese Retro Vending Maintenance & Telemetry Console

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
$products = $productModel->getAllProducts();

// Sort by popularity
usort($products, function($a, $b) {
    return $b['dispensed_count'] - $a['dispensed_count'];
});

$totalDispensed = array_sum(array_column($products, 'dispensed_count'));
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
            --radius-box: 14px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        /* Custom Themed Scrollbar */
        html { scrollbar-width: thin; scrollbar-color: #3d465c #151821; }
        ::-webkit-scrollbar { width: 7px; height: 7px; }
        ::-webkit-scrollbar-track { background: #151821; border-left: 1px solid #232938; }
        ::-webkit-scrollbar-thumb { background: #3a445b; border-radius: 9999px; }

        body {
            font-family: var(--font-body);
            background-color: var(--retro-bg);
            color: var(--retro-cream);
            min-height: 100vh;
            padding: 20px 16px 60px;
            background-image: 
                radial-gradient(circle at 50% 0%, #252c3c 0%, transparent 60%),
                radial-gradient(circle at 10% 100%, #1c212c 0%, transparent 40%);
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .header-strip {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }

        .brand-title {
            font-family: var(--font-display);
            font-size: 1.25rem;
            color: var(--retro-cream);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-retro-back {
            background: #232938;
            border: 2px solid #3c465d;
            box-shadow: 0 3px 0 #12151b;
            color: var(--retro-cream);
            text-decoration: none;
            padding: 7px 14px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.12s;
        }

        .btn-retro-back:active {
            transform: translateY(2px);
            box-shadow: 0 1px 0 #12151b;
        }

        .retro-chart-card {
            background: #232938;
            border: 2px solid #3a445b;
            border-radius: var(--radius-box);
            box-shadow: 0 6px 0 #13161f, 0 12px 24px rgba(0, 0, 0, 0.5);
            padding: 24px;
            margin-bottom: 20px;
        }

        .card-headline {
            font-family: var(--font-body);
            font-size: 1.15rem;
            font-weight: 900;
            color: var(--retro-cream);
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-caption {
            font-size: 0.78rem;
            color: var(--retro-cream-muted);
            margin-bottom: 20px;
        }

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
    </style>
</head>
<body>
    <div class="container">
        <div class="header-strip">
            <div class="brand-title">
                <i data-lucide="bar-chart-2" style="width: 22px; height: 22px; color: var(--retro-mustard);"></i>
                <span>Demand & Sales Analytics</span>
            </div>
            <a href="index.php" class="btn-retro-back">
                <i data-lucide="arrow-left" style="width: 14px; height: 14px;"></i>
                Back to Control Hub
            </a>
        </div>

        <div class="retro-chart-card">
            <h2 class="card-headline">
                <i data-lucide="trending-up" style="width: 18px; height: 18px; color: #2ecc71;"></i>
                Stationery Dispense Rankings
            </h2>
            <p class="card-caption">
                Volume ranking across all 6 mechanical trays and lanes (4 Tiers). Total verified dispenses: <strong style="color: #ffeaa7; font-family: var(--font-mono);"><?= $totalDispensed ?> units</strong>.
            </p>

            <?php foreach ($products as $p): ?>
                <?php 
                    $percent = ($totalDispensed > 0) ? round(($p['dispensed_count'] / $totalDispensed) * 100) : 0;
                    $tierNum = $p['tier_number'] ?? ($p['slot_number'] <= 3 ? $p['slot_number'] : 4);
                    $mech = $p['mechanism_type'] ?? ($p['slot_number'] <= 3 ? 'roller_feed' : 'paddle_push');
                    $isRoller = ($mech === 'roller_feed');
                ?>
                <div class="bar-row">
                    <div class="bar-label-group">
                        <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                            <span style="font-family: var(--font-mono); font-weight: 800; color: #74b9ff;">SLOT #<?= $p['slot_number'] ?></span>
                            <span style="font-size: 0.68rem; font-family: var(--font-mono); color: var(--retro-cream-muted); background: #181d27; padding: 1px 6px; border-radius: 4px; border: 1px solid #333a4c;">Tier <?= $tierNum ?></span>
                            <strong style="color: var(--retro-cream);"><?= htmlspecialchars($p['name']) ?></strong>
                            <span style="font-size: 0.65rem; font-family: var(--font-mono); padding: 1px 5px; border-radius: 4px; background: <?= $isRoller ? 'rgba(9, 132, 227, 0.2)' : 'rgba(230, 126, 34, 0.2)' ?>; color: <?= $isRoller ? '#74b9ff' : '#f39c12' ?>;">
                                <?= $isRoller ? 'DC Rubber Roller' : 'Servo Paddle Push' ?>
                            </span>
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
    <script>lucide.createIcons();</script>
</body>
</html>
