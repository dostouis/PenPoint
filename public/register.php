<?php
// public/register.php
// User Registration Page - For new RFID cards

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../models/ActivityLogModel.php';

$appName = defined('APP_NAME') ? APP_NAME : 'EcoPoints';

// Handle registration form submission
$registrationComplete = false;
$registeredCard = '';
$error = '';
$logModel = new ActivityLogModel();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $carddata = trim($_POST['carddata'] ?? '');
    $fname = trim($_POST['fname'] ?? '');
    $mname = trim($_POST['mname'] ?? '');
    $lname = trim($_POST['lname'] ?? '');
    
    if (!empty($carddata)) {
        $conn = getConnection();
        
        // Check if card already exists
        $check = $conn->prepare("SELECT idcard FROM users WHERE idcard = ?");
        $check->bind_param("s", $carddata);
        $check->execute();
        $result = $check->get_result();
        
        if ($result->num_rows > 0) {
            $error = "This card is already registered!";
            $logModel->addLog('error', $carddata, null, 'Registration attempt for already registered card');
        } else {
            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users (idcard, fname, mname, lname, current_points, total_points_earned, total_items_recycled) VALUES (?, ?, ?, ?, 0, 0, 0)");
            $stmt->bind_param("ssss", $carddata, $fname, $mname, $lname);
            
            if ($stmt->execute()) {
                $registrationComplete = true;
                $registeredCard = $carddata;
                
                // ✅ ADD ACTIVITY LOG HERE
                $nameString = trim("$fname $mname $lname") ?: 'No name provided';
                $logModel->addLog('registration', $carddata, null, "New user registered: $nameString");
            } else {
                $error = "Registration failed. Please try again.";
                $logModel->addLog('error', $carddata, null, 'Registration failed: Database error');
            }
            $stmt->close();
        }
        $check->close();
        $conn->close();
    } else {
        $error = "Card ID is required.";
    }
}

// Get card from URL if redirected from user.php
$cardFromUrl = trim($_GET['card'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?= htmlspecialchars($appName) ?></title>
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

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Nunito Sans', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
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

        .register-container {
            position: relative;
            z-index: 1;
            max-width: 520px;
            width: 100%;
        }

        /* ── Logo ─────────────────────────────── */
        .logo-area {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 32px;
        }

        .logo-icon {
            width: 52px; height: 52px;
            background: linear-gradient(135deg, var(--green-mid), var(--green-bright));
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 28px;
            box-shadow: 0 4px 24px rgba(53,224,122,0.35);
        }

        .logo-area h1 {
            font-size: 28px;
            font-weight: 900;
            letter-spacing: -0.5px;
            color: var(--text);
        }

        .logo-area h1 span {
            color: var(--green-bright);
        }

        /* ── Register Card ────────────────────── */
        .register-card {
            background: linear-gradient(160deg, rgba(13,38,22,0.98), rgba(8,22,13,0.99));
            border: 1px solid var(--border);
            border-radius: 28px;
            padding: 36px 32px;
            backdrop-filter: blur(12px);
            box-shadow: 0 8px 40px rgba(0,0,0,0.4), 0 0 0 1px rgba(53,224,122,0.1);
        }

        .register-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .register-icon {
            font-size: 56px;
            margin-bottom: 16px;
        }

        .register-header h2 {
            font-size: 26px;
            font-weight: 900;
            margin-bottom: 8px;
        }

        .register-header p {
            color: var(--muted);
            font-size: 14px;
        }

        /* ── Success State ────────────────────── */
        .success-state {
            text-align: center;
            padding: 20px 0;
        }

        .success-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .success-state h3 {
            font-size: 24px;
            font-weight: 900;
            color: var(--green-bright);
            margin-bottom: 12px;
        }

        .success-state p {
            color: var(--muted);
            font-size: 14px;
            margin-bottom: 24px;
        }

        .success-card-id {
            font-family: 'DM Mono', monospace;
            font-size: 20px;
            background: rgba(53,224,122,0.1);
            padding: 12px 24px;
            border-radius: 40px;
            display: inline-block;
            border: 1px solid rgba(53,224,122,0.3);
            margin-bottom: 28px;
        }

        /* ── Form ─────────────────────────────── */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 11px;
            font-family: 'DM Mono', monospace;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 8px;
        }

        .form-group input {
            width: 100%;
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 14px 16px;
            color: var(--text);
            font-family: 'Nunito Sans', sans-serif;
            font-size: 15px;
            font-weight: 600;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-group input:focus {
            border-color: var(--green-bright);
            box-shadow: 0 0 0 3px rgba(53,224,122,0.1);
        }

        .form-group input::placeholder {
            color: var(--muted);
            font-weight: 400;
            opacity: 0.6;
        }

        .form-group input[readonly] {
            background: rgba(53,224,122,0.05);
            border-color: rgba(53,224,122,0.3);
            color: var(--green-bright);
            font-family: 'DM Mono', monospace;
            font-size: 16px;
            text-align: center;
            letter-spacing: 2px;
        }

        .name-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .name-row .form-group:first-child {
            grid-column: span 2;
        }

        .error-message {
            background: rgba(224,90,90,0.1);
            border: 1px solid rgba(224,90,90,0.3);
            border-radius: 12px;
            padding: 14px 18px;
            color: var(--red);
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn {
            width: 100%;
            padding: 16px 24px;
            border-radius: 14px;
            border: none;
            cursor: pointer;
            font-family: 'Nunito Sans', sans-serif;
            font-weight: 800;
            font-size: 15px;
            transition: all 0.2s;
            letter-spacing: 0.5px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--green-mid), var(--green-bright));
            color: #061410;
            box-shadow: 0 4px 20px rgba(53,224,122,0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 28px rgba(53,224,122,0.4);
            filter: brightness(1.08);
        }

        .btn-secondary {
            background: rgba(255,255,255,0.05);
            color: var(--text);
            border: 1px solid var(--border);
            margin-top: 12px;
        }

        .btn-secondary:hover {
            background: rgba(53,224,122,0.1);
            border-color: rgba(53,224,122,0.3);
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: var(--muted);
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: color 0.2s;
        }

        .back-link a:hover {
            color: var(--green-bright);
        }

        .required-badge {
            color: var(--green-bright);
            margin-left: 4px;
        }

        .optional-badge {
            color: var(--muted);
            font-size: 10px;
            margin-left: 6px;
            font-weight: 400;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .register-card {
            animation: fadeIn 0.4s ease;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="logo-area">
            <div class="logo-icon">♻️</div>
            <h1><?= htmlspecialchars($appName) ?> <span>· Register</span></h1>
        </div>

        <div class="register-card">
            <?php if ($registrationComplete): ?>
                <!-- Success State -->
                <div class="success-state">
                    <div class="success-icon">🎉</div>
                    <h3>Registration Complete!</h3>
                    <p>Your card has been successfully registered</p>
                    <div class="success-card-id">🪪 <?= htmlspecialchars($registeredCard) ?></div>
                    <button class="btn btn-primary" onclick="window.location.href='user.php'">
                        Go to Profile Scanner
                    </button>
                    <button class="btn btn-secondary" onclick="window.location.href='index.php'">
                        View Leaderboard
                    </button>
                </div>
            <?php else: ?>
                <!-- Registration Form -->
                <div class="register-header">
                    <div class="register-icon">🆕</div>
                    <h2>New Card Detected</h2>
                    <p>Register your RFID card to start earning points</p>
                </div>

                <?php if (!empty($error)): ?>
                <div class="error-message">
                    <span>⚠️</span>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label>
                            CARD ID (RFID UID) 
                            <span class="required-badge">*</span>
                        </label>
                        <input type="text" name="carddata" value="<?= htmlspecialchars($cardFromUrl) ?>" readonly placeholder="Scanned automatically">
                    </div>

                    <div class="name-row">
                        <div class="form-group">
                            <label>
                                FIRST NAME
                                <span class="optional-badge">(optional)</span>
                            </label>
                            <input type="text" name="fname" placeholder="e.g., Juan" autofocus>
                        </div>
                        <div class="form-group">
                            <label>
                                LAST NAME
                                <span class="optional-badge">(optional)</span>
                            </label>
                            <input type="text" name="lname" placeholder="e.g., Dela Cruz">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>
                            MIDDLE NAME
                            <span class="optional-badge">(optional)</span>
                        </label>
                        <input type="text" name="mname" placeholder="e.g., Santos">
                    </div>

                    <button type="submit" class="btn btn-primary">✅ Complete Registration</button>
                </form>
            <?php endif; ?>
        </div>

        <div class="back-link">
            <a href="user.php">← Back to Scanner</a>
        </div>
    </div>
</body>
</html>