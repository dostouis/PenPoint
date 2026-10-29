<?php
// public/claim.php
// Claim Rewards Page - Redeem points for rewards (with RFID scanning)

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/ActivityLogModel.php';

$appName = defined('APP_NAME') ? APP_NAME : 'EcoPoints';

// Get card from URL (passed from user.php) or default to empty
$cardFromUrl = trim($_GET['card'] ?? '');
$user = null;
$rewards = [];
$logModel = new ActivityLogModel();

if (!empty($cardFromUrl)) {
    $conn = getConnection();
    
    // Get user data
    $stmt = $conn->prepare("SELECT id, idcard, fname, lname, current_points, total_points_earned FROM users WHERE idcard = ?");
    $stmt->bind_param("s", $cardFromUrl);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    // Get available rewards
    if ($user) {
        $rewardResult = $conn->query("
            SELECT id, reward_name, description, points_required, stock, is_active 
            FROM rewards 
            WHERE is_active = 1 AND stock > 0 
            ORDER BY points_required ASC
        ");
        while ($row = $rewardResult->fetch_assoc()) {
            $rewards[] = $row;
        }
    }
    
    $conn->close();
}

// Handle reward claim
$claimSuccess = false;
$claimError = '';
$claimedReward = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $carddata = trim($_POST['carddata'] ?? '');
    $rewardId = intval($_POST['reward_id'] ?? 0);
    
    if (!empty($carddata) && $rewardId > 0) {
        $conn = getConnection();
        
        // Get user data
        $stmt = $conn->prepare("SELECT id, idcard, fname, lname, current_points FROM users WHERE idcard = ?");
        $stmt->bind_param("s", $carddata);
        $stmt->execute();
        $userData = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if ($userData) {
            $conn->begin_transaction();
            
            try {
                // Get reward details
                $stmt = $conn->prepare("SELECT id, reward_name, points_required, stock FROM rewards WHERE id = ? AND is_active = 1 AND stock > 0 FOR UPDATE");
                $stmt->bind_param("i", $rewardId);
                $stmt->execute();
                $reward = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                
                if (!$reward) {
                    throw new Exception("Reward not available");
                }
                
                if ($userData['current_points'] < $reward['points_required']) {
                    throw new Exception("Insufficient points");
                }
                
                // Deduct points
                $stmt = $conn->prepare("UPDATE users SET current_points = current_points - ? WHERE idcard = ? AND current_points >= ?");
                $stmt->bind_param("isi", $reward['points_required'], $carddata, $reward['points_required']);
                $stmt->execute();
                
                if ($stmt->affected_rows === 0) {
                    throw new Exception("Failed to deduct points");
                }
                $stmt->close();
                
                // Reduce stock
                $stmt = $conn->prepare("UPDATE rewards SET stock = stock - 1 WHERE id = ?");
                $stmt->bind_param("i", $rewardId);
                $stmt->execute();
                $stmt->close();
                
                $conn->commit();
                
                $claimSuccess = true;
                $claimedReward = $reward;
                
                // ✅ ADD ACTIVITY LOG HERE
                $logModel->addLog('reward_claim', $carddata, null, "Claimed: {$reward['reward_name']}", -$reward['points_required']);
                
                // Reload user data
                $user['current_points'] = $userData['current_points'] - $reward['points_required'];
                
                // Reload rewards
                $rewardResult = $conn->query("
                    SELECT id, reward_name, description, points_required, stock, is_active 
                    FROM rewards 
                    WHERE is_active = 1 AND stock > 0 
                    ORDER BY points_required ASC
                ");
                $rewards = [];
                while ($row = $rewardResult->fetch_assoc()) {
                    $rewards[] = $row;
                }
                
            } catch (Exception $e) {
                $conn->rollback();
                $claimError = $e->getMessage();
                // ✅ LOG ERROR
                $logModel->addLog('error', $carddata, null, "Reward claim failed: " . $e->getMessage());
            }
        }
        
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claim Rewards - <?= htmlspecialchars($appName) ?></title>
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
            --text: #eef8f2;
            --muted: #6faa88;
            --border: rgba(53,224,122,0.12);
            --border-solid: #1c3d28;
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
            background: #e8a735;
        }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Nunito Sans', sans-serif;
            min-height: 100vh;
            padding: 24px;
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
                repeating-linear-gradient(
                    90deg,
                    transparent 0px,
                    transparent 80px,
                    rgba(20,60,30,0.08) 80px,
                    rgba(20,60,30,0.08) 82px
                );
            pointer-events: none;
            z-index: 0;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        /* ── HEADER ───────────────────────────── */
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 0 24px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 28px;
            position: relative;
            flex-wrap: wrap;
            gap: 16px;
        }

        .header::after {
            content: '';
            position: absolute;
            bottom: -1px; left: 0; right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--green-bright), transparent);
            opacity: 0.3;
        }

        .logo-area {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-icon {
            width: 42px; height: 42px;
            background: linear-gradient(135deg, var(--green-mid), var(--green-bright));
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px;
            box-shadow: 0 4px 20px rgba(53,224,122,0.3);
        }

        .logo-area h1 {
            font-size: 24px;
            font-weight: 900;
            letter-spacing: -0.5px;
            color: var(--text);
        }

        .logo-area h1 span {
            color: var(--accent);
        }

        .nav-links {
            display: flex;
            gap: 16px;
        }

        .nav-link {
            padding: 8px 18px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
            font-size: 13px;
            transition: all 0.2s;
            background: rgba(255,255,255,0.05);
            color: var(--text);
            border: 1px solid var(--border);
        }

        .nav-link:hover {
            background: rgba(53,224,122,0.1);
            border-color: rgba(53,224,122,0.3);
        }

        /* ─── SCAN BAR ────────────────────────── */
        .scan-bar {
            background: linear-gradient(160deg, rgba(13,38,22,0.95), rgba(8,22,13,0.98));
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 16px 24px;
            margin-bottom: 24px;
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
        }

        .scan-status {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .scan-icon {
            font-size: 28px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(1.05); }
        }

        .scan-text {
            color: var(--muted);
            font-size: 14px;
        }

        .scan-text strong {
            color: var(--green-bright);
        }

        .scan-input-wrapper {
            position: relative;
        }

        .scan-input {
            background: rgba(255,255,255,0.05);
            border: 2px solid var(--green-bright);
            border-radius: 12px;
            padding: 12px 18px;
            color: var(--text);
            font-family: 'DM Mono', monospace;
            font-size: 16px;
            outline: none;
            width: 240px;
            text-align: center;
            box-shadow: 0 0 15px rgba(53,224,122,0.15);
            letter-spacing: 2px;
        }

        .scan-input::placeholder {
            color: var(--muted);
            font-size: 12px;
            letter-spacing: normal;
        }

        /* ─── USER BAR ─────────────────────────── */
        .user-bar {
            background: linear-gradient(160deg, rgba(13,38,22,0.95), rgba(8,22,13,0.98));
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 16px 24px;
            margin-bottom: 24px;
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .user-avatar {
            width: 48px; height: 48px;
            background: linear-gradient(135deg, var(--green-mid), var(--green-bright));
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px;
        }

        .user-details h3 {
            font-size: 18px;
            font-weight: 800;
            margin-bottom: 2px;
        }

        .user-details .card-id {
            font-family: 'DM Mono', monospace;
            font-size: 12px;
            color: var(--muted);
        }

        .points-display {
            text-align: right;
        }

        .points-label {
            font-size: 11px;
            font-family: 'DM Mono', monospace;
            color: var(--muted);
            text-transform: uppercase;
        }

        .points-value {
            font-size: 32px;
            font-weight: 900;
            color: var(--green-bright);
            line-height: 1;
        }

        /* ─── REWARDS GRID ────────────────────── */
        .rewards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }

        .reward-card {
            background: linear-gradient(160deg, rgba(13,38,22,0.95), rgba(8,22,13,0.98));
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 24px;
            backdrop-filter: blur(10px);
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
            overflow: hidden;
        }

        .reward-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 32px rgba(0,0,0,0.3);
            border-color: rgba(53,224,122,0.3);
        }

        .reward-card.disabled {
            opacity: 0.5;
            pointer-events: none;
        }

        .reward-icon {
            font-size: 36px;
            margin-bottom: 16px;
        }

        .reward-name {
            font-size: 18px;
            font-weight: 800;
            margin-bottom: 6px;
        }

        .reward-desc {
            font-size: 12px;
            color: var(--muted);
            margin-bottom: 16px;
            line-height: 1.5;
        }

        .reward-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .reward-points {
            font-family: 'DM Mono', monospace;
            font-size: 20px;
            font-weight: 700;
            color: var(--accent);
        }

        .reward-stock {
            font-family: 'DM Mono', monospace;
            font-size: 11px;
            color: var(--muted);
        }

        .btn-claim {
            width: 100%;
            padding: 12px 16px;
            border-radius: 12px;
            border: none;
            cursor: pointer;
            font-family: 'Nunito Sans', sans-serif;
            font-weight: 800;
            font-size: 13px;
            transition: all 0.2s;
            background: linear-gradient(135deg, var(--green-mid), var(--green-bright));
            color: #061410;
        }

        .btn-claim:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(53,224,122,0.3);
        }

        .btn-claim:disabled {
            opacity: 0.4;
            cursor: not-allowed;
            background: var(--surface2);
            color: var(--muted);
        }

        /* ─── SUCCESS MODAL ───────────────────── */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.8);
            backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 999;
        }

        .modal {
            background: linear-gradient(160deg, #0e2818, #081608);
            border: 1px solid rgba(53,224,122,0.3);
            border-radius: 28px;
            padding: 36px;
            max-width: 400px;
            width: 90%;
            text-align: center;
            animation: modalIn 0.3s ease;
        }

        @keyframes modalIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }

        .modal-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .modal h2 {
            font-size: 24px;
            font-weight: 900;
            color: var(--green-bright);
            margin-bottom: 12px;
        }

        .modal p {
            color: var(--muted);
            font-size: 14px;
            margin-bottom: 24px;
        }

        /* ─── EMPTY STATE ─────────────────────── */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--muted);
            background: linear-gradient(160deg, rgba(13,38,22,0.95), rgba(8,22,13,0.98));
            border: 1px solid var(--border);
            border-radius: 24px;
        }

        .empty-icon {
            font-size: 56px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 20px;
            font-weight: 800;
            margin-bottom: 8px;
            color: var(--text);
        }

        .error-message {
            background: rgba(224,90,90,0.1);
            border: 1px solid rgba(224,90,90,0.3);
            border-radius: 12px;
            padding: 14px 18px;
            color: var(--red);
            margin-bottom: 20px;
        }

        .hidden { display: none !important; }

        /* ─── TOAST ────────────────────────────── */
        .toast {
            position: fixed;
            bottom: 24px; right: 24px;
            background: linear-gradient(135deg, rgba(13,38,22,0.98), rgba(9,26,15,0.99));
            border: 1px solid rgba(53,224,122,0.3);
            border-radius: 14px;
            padding: 14px 20px;
            font-size: 13px;
            font-weight: 700;
            color: var(--green-bright);
            z-index: 999;
            transform: translateY(80px);
            opacity: 0;
            transition: all 0.3s;
            display: flex; align-items: center; gap: 10px;
            backdrop-filter: blur(16px);
            box-shadow: 0 8px 32px rgba(0,0,0,0.4);
        }

        .toast.show { transform: translateY(0); opacity: 1; }
        .toast.error { color: var(--red); border-color: rgba(224,90,90,0.3); }

        @media (max-width: 768px) {
            .header { flex-direction: column; text-align: center; }
            .scan-bar { flex-direction: column; text-align: center; }
            .user-bar { flex-direction: column; text-align: center; }
            .user-info { flex-direction: column; }
            .points-display { text-align: center; }
        }
    </style>
</head>
<body>
    <!-- Hidden input for RFID scanning -->
    <input type="text" id="hiddenCardInput" style="position: absolute; opacity: 0; height: 0; width: 0; pointer-events: none;" autofocus>

    <!-- Toast -->
    <div class="toast" id="toast"></div>

    <div class="container">
        <!-- Header -->
        <header class="header">
            <div class="logo-area">
                <div class="logo-icon">🎁</div>
                <h1><?= htmlspecialchars($appName) ?> <span>· Claim Rewards</span></h1>
            </div>
            <div class="nav-links">
                <a href="index.php" class="nav-link">🏆 Leaderboard</a>
                <a href="user.php" class="nav-link">👤 My Profile</a>
            </div>
        </header>

        <!-- Scan Bar (Always visible) -->
        <div class="scan-bar">
            <div class="scan-status">
                <span class="scan-icon">💳</span>
                <span class="scan-text"><strong>Scan any card</strong> to switch user</span>
            </div>
            <div class="scan-input-wrapper">
                <input type="text" id="visibleCardInput" class="scan-input" placeholder="Waiting for scan..." readonly>
            </div>
        </div>

        <?php if ($user): ?>
        <!-- User Bar -->
        <div class="user-bar">
            <div class="user-info">
                <div class="user-avatar">👤</div>
                <div class="user-details">
                    <h3><?= htmlspecialchars(trim($user['fname'] . ' ' . $user['lname']) ?: 'Recycler') ?></h3>
                    <span class="card-id">🪪 <?= htmlspecialchars(substr($user['idcard'], 0, 10)) ?>...</span>
                </div>
            </div>
            <div class="points-display">
                <div class="points-label">Available Points</div>
                <div class="points-value"><?= number_format($user['current_points']) ?></div>
            </div>
        </div>

        <?php if (!empty($claimError)): ?>
        <div class="error-message">
            <span>⚠️</span>
            <span><?= htmlspecialchars($claimError) ?></span>
        </div>
        <?php endif; ?>

        <?php if (empty($rewards)): ?>
        <div class="empty-state">
            <div class="empty-icon">📭</div>
            <h3>No Rewards Available</h3>
            <p>Check back later for new rewards!</p>
        </div>
        <?php else: ?>
        <div class="rewards-grid">
            <?php foreach ($rewards as $reward): 
                $canAfford = $user['current_points'] >= $reward['points_required'];
                $icons = ['🍫', '🍜', '✏️', '📓', '🥤', '🎒', '⭐', '🎟️', '☕', '🍕'];
                $icon = $icons[$reward['id'] % count($icons)];
            ?>
            <div class="reward-card <?= !$canAfford ? 'disabled' : '' ?>">
                <div class="reward-icon"><?= $icon ?></div>
                <div class="reward-name"><?= htmlspecialchars($reward['reward_name']) ?></div>
                <div class="reward-desc"><?= htmlspecialchars($reward['description'] ?: 'No description') ?></div>
                <div class="reward-meta">
                    <span class="reward-points"><?= number_format($reward['points_required']) ?> pts</span>
                    <span class="reward-stock">📦 <?= $reward['stock'] ?> left</span>
                </div>
                <form method="POST" onsubmit="return confirm('Claim <?= htmlspecialchars($reward['reward_name']) ?> for <?= number_format($reward['points_required']) ?> points?')">
                    <input type="hidden" name="carddata" value="<?= htmlspecialchars($cardFromUrl) ?>">
                    <input type="hidden" name="reward_id" value="<?= $reward['id'] ?>">
                    <button type="submit" class="btn-claim" <?= !$canAfford ? 'disabled' : '' ?>>
                        <?= $canAfford ? '🎁 Claim Reward' : '🔒 Not Enough Points' ?>
                    </button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">💳</div>
            <h3>Waiting for Card</h3>
            <p>Scan your RFID card to view available rewards</p>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($claimSuccess && $claimedReward): ?>
    <div class="modal-overlay" id="successModal" onclick="closeModalAndRefresh()">
        <div class="modal" onclick="event.stopPropagation()">
            <div class="modal-icon">🎉</div>
            <h2>Reward Claimed!</h2>
            <p>You have successfully claimed:</p>
            <div style="font-size: 22px; font-weight: 800; margin-bottom: 8px;">
                <?= htmlspecialchars($claimedReward['reward_name']) ?>
            </div>
            <div style="font-family: 'DM Mono', monospace; font-size: 18px; color: var(--text); margin-bottom: 24px;">
                -<?= number_format($claimedReward['points_required']) ?> points
            </div>
            <button class="btn-claim" onclick="closeModalAndRefresh()" style="width: auto; padding: 12px 32px;">
                Awesome!
            </button>
        </div>
    </div>
    <?php endif; ?>

    <script>
        let scanTimeout = null;
        let currentCardId = '<?= htmlspecialchars($cardFromUrl) ?>';
        
        const hiddenInput = document.getElementById('hiddenCardInput');
        const visibleInput = document.getElementById('visibleCardInput');

        // ─── Keep hidden input ALWAYS focused ─────────
        function keepFocus() {
            hiddenInput.focus();
        }
        
        setInterval(keepFocus, 100);
        hiddenInput.addEventListener('blur', keepFocus);
        document.addEventListener('click', keepFocus);
        window.addEventListener('load', keepFocus);

        // ─── Handle card scan ────────────────────────
        hiddenInput.addEventListener('input', function(e) {
            const value = this.value.trim();
            visibleInput.value = value;
            
            if (scanTimeout) {
                clearTimeout(scanTimeout);
            }
            
            if (value.length > 0) {
                scanTimeout = setTimeout(() => {
                    handleScannedCard(value);
                }, 300);
            }
        });

        hiddenInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const value = this.value.trim();
                if (value.length > 0) {
                    if (scanTimeout) clearTimeout(scanTimeout);
                    handleScannedCard(value);
                }
            }
        });

        // ─── Process scanned card ─────────────────────
        function handleScannedCard(scannedCard) {
            // Clear the hidden input for next scan
            hiddenInput.value = '';
            
            // Check if card is registered
            fetch(`../api/get_users.php`)
                .then(response => response.json())
                .then(users => {
                    const user = users.find(u => u.idcard === scannedCard);
                    
                    if (!user) {
                        // New card - redirect to registration
                        showToast('New card detected! Redirecting to registration...');
                        setTimeout(() => {
                            window.location.href = `register.php?card=${encodeURIComponent(scannedCard)}`;
                        }, 800);
                        return;
                    }
                    
                    // Card is registered - redirect to user.php with card
                    showToast('Loading profile...');
                    setTimeout(() => {
                        window.location.href = `user.php?card=${encodeURIComponent(scannedCard)}`;
                    }, 300);
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Error checking card', true);
                    visibleInput.value = '';
                });
        }

        // ─── Close Modal ──────────────────────────────
        function closeModalAndRefresh() {
            const modal = document.getElementById('successModal');
            if (modal) {
                modal.style.display = 'none';
            }
            location.reload();
        }
        
        // ─── Toast ───────────────────────────────────
        function showToast(msg, isError = false) {
            const t = document.getElementById('toast');
            t.textContent = msg;
            t.classList.add('show');
            isError ? t.classList.add('error') : t.classList.remove('error');
            setTimeout(() => {
                t.classList.remove('show');
                t.classList.remove('error');
            }, 2000);
        }
    </script>
</body>
</html>