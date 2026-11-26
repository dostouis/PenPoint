<?php
// public/esp32_simulator.php
// PenPoint ESP32 Hardware Simulator & Live IoT Testbench
// Allows developers, evaluators, and students to test hardware dispensing
// in real-time when ordering from the PenPoint Student Kiosk

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/ProductModel.php';

$productModel = new ProductModel();
$products = $productModel->getAllProducts();
$machine_id = $_GET['machine'] ?? DEFAULT_MACHINE_ID;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ESP32 Hardware Simulator & Testbench — <?= APP_NAME ?></title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Dela+Gothic+One&family=Noto+Sans+JP:wght@400;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Lucide Icons -->
    <script src="assets/js/lucide.min.js"></script>
    <script>if (typeof lucide === 'undefined') document.write('<script src="https://unpkg.com/lucide@latest"><\/script>');</script>
    <style>
        :root {
            --bg-base: #0c0e14;
            --bg-card: #151821;
            --bg-panel: #1e2330;
            --border: #2b3245;
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
            --accent-blue: #3b82f6;
            --accent-green: #22c55e;
            --accent-red: #ef4444;
            --accent-amber: #f59e0b;
            --accent-purple: #a855f7;
            --font-mono: 'DM Mono', monospace;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background: var(--bg-base);
            color: var(--text-main);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Top Header */
        header {
            background: #11141c;
            border-bottom: 2px solid var(--border);
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .header-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #1e3a8a, #3b82f6);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 15px rgba(59, 130, 246, 0.4);
        }

        .header-text h1 {
            font-size: 1.1rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tag-pill {
            background: rgba(34, 197, 94, 0.15);
            color: #4ade80;
            border: 1px solid rgba(34, 197, 94, 0.3);
            font-size: 0.7rem;
            padding: 2px 8px;
            border-radius: 999px;
            font-family: var(--font-mono);
            font-weight: 600;
        }

        .header-text p {
            font-size: 0.78rem;
            color: var(--text-muted);
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn {
            background: var(--bg-panel);
            color: var(--text-main);
            border: 1px solid var(--border);
            padding: 7px 14px;
            border-radius: 8px;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .btn:hover {
            background: #272e40;
            border-color: #3e4860;
        }

        .btn-primary {
            background: #2563eb;
            border-color: #3b82f6;
            color: #ffffff;
        }
        .btn-primary:hover {
            background: #1d4ed8;
        }

        .btn-success {
            background: #15803d;
            border-color: #22c55e;
            color: #ffffff;
        }

        /* Layout Grid */
        .workspace {
            padding: 20px;
            max-width: 1440px;
            margin: 0 auto;
            width: 100%;
            display: grid;
            grid-template-columns: 360px 1fr 420px;
            gap: 20px;
            flex: 1;
        }

        @media (max-width: 1200px) {
            .workspace {
                grid-template-columns: 1fr;
            }
        }

        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 18px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.3);
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border);
        }

        .card-title {
            font-size: 0.9rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #e2e8f0;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        /* ESP32 Chip Representation */
        .esp-board {
            background: #111a14;
            border: 2px solid #166534;
            border-radius: 10px;
            padding: 16px;
            position: relative;
            box-shadow: inset 0 0 20px rgba(22, 101, 52, 0.2);
        }

        .esp-chip {
            background: #1f2937;
            border: 1px solid #4b5563;
            border-radius: 6px;
            padding: 12px;
            text-align: center;
            margin-bottom: 14px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.5);
            position: relative;
        }

        .chip-notch {
            position: absolute;
            top: 0;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 16px;
            height: 6px;
            background: #111a14;
            border-radius: 0 0 6px 6px;
            border: 1px solid #4b5563;
        }

        .chip-label {
            font-family: var(--font-mono);
            font-size: 0.85rem;
            font-weight: 700;
            color: #cbd5e1;
        }

        .chip-sub {
            font-size: 0.65rem;
            color: #64748b;
            font-family: var(--font-mono);
        }

        .esp-leds {
            display: flex;
            justify-content: space-around;
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px dashed #2e384d;
        }

        .led-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.7rem;
            font-family: var(--font-mono);
            color: var(--text-muted);
        }

        .led-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #334155;
            transition: all 0.2s ease;
        }

        .led-dot.pwr.active { background: #ef4444; box-shadow: 0 0 8px #ef4444; }
        .led-dot.wifi.active { background: #22c55e; box-shadow: 0 0 8px #22c55e; }
        .led-dot.rx.active { background: #3b82f6; box-shadow: 0 0 8px #3b82f6; }
        .led-dot.tx.active { background: #f59e0b; box-shadow: 0 0 8px #f59e0b; }

        /* Pinout Status Table */
        .pin-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.75rem;
            font-family: var(--font-mono);
        }

        .pin-table th {
            text-align: left;
            padding: 6px;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border);
        }

        .pin-table td {
            padding: 6px;
            border-bottom: 1px solid rgba(255,255,255,0.04);
        }

        .pin-badge {
            background: #262c3d;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 700;
            color: #93c5fd;
            display: inline-block;
        }

        .pin-active {
            background: rgba(34, 197, 94, 0.15);
            color: #4ade80 !important;
            animation: pulse-pin 0.8s infinite alternate;
        }

        @keyframes pulse-pin {
            0% { opacity: 0.6; }
            100% { opacity: 1; }
        }

        /* Actuators Middle Column */
        .actuators-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }

        .actuator-card {
            background: var(--bg-panel);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 12px;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            gap: 10px;
            transition: border-color 0.3s ease;
        }

        .actuator-card.active {
            border-color: #3b82f6;
            box-shadow: 0 0 15px rgba(59, 130, 246, 0.3);
        }

        .actuator-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .actuator-name {
            font-size: 0.8rem;
            font-weight: 700;
            color: #e2e8f0;
        }

        .actuator-pin {
            font-family: var(--font-mono);
            font-size: 0.7rem;
            color: #94a3b8;
            background: #11141c;
            padding: 1px 6px;
            border-radius: 4px;
        }

        /* Servo Visualizer Dial */
        .servo-dial-wrap {
            height: 90px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .servo-body {
            width: 74px;
            height: 74px;
            background: #181d2a;
            border: 2px solid #334155;
            border-radius: 50%;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .servo-horn {
            position: absolute;
            width: 4px;
            height: 32px;
            background: linear-gradient(to top, #3b82f6, #93c5fd);
            border-radius: 2px;
            transform-origin: bottom center;
            bottom: 35px;
            left: 33px;
            transform: rotate(90deg);
            transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 0 8px rgba(59, 130, 246, 0.8);
        }

        .servo-hub {
            width: 14px;
            height: 14px;
            background: #ffffff;
            border: 3px solid #1e293b;
            border-radius: 50%;
            z-index: 2;
        }

        .servo-angle-text {
            font-family: var(--font-mono);
            font-size: 0.8rem;
            font-weight: 700;
            color: #38bdf8;
            text-align: center;
        }

        /* DC Roller Motor Visualizer */
        .roller-wrap {
            height: 90px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .roller-cylinder {
            width: 110px;
            height: 34px;
            background: repeating-linear-gradient(90deg, #334155, #334155 10px, #475569 10px, #475569 20px);
            border-radius: 6px;
            border: 2px solid #64748b;
            position: relative;
            overflow: hidden;
        }

        .roller-cylinder.spinning {
            animation: roll-cylinder 0.3s linear infinite;
            border-color: #22c55e;
            box-shadow: 0 0 12px rgba(34, 197, 94, 0.4);
        }

        @keyframes roll-cylinder {
            0% { background-position: 0 0; }
            100% { background-position: 20px 0; }
        }

        /* IR Break Beam Chute */
        .ir-chute-box {
            background: #11141c;
            border: 2px solid #283042;
            border-radius: 10px;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            position: relative;
        }

        .ir-beam-graphic {
            height: 60px;
            background: #181c26;
            border-radius: 8px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 16px;
            overflow: hidden;
        }

        .emitter, .receiver {
            width: 14px;
            height: 24px;
            background: #334155;
            border-radius: 3px;
            z-index: 2;
        }

        .beam-line {
            position: absolute;
            left: 28px;
            right: 28px;
            height: 2px;
            background: #22c55e;
            box-shadow: 0 0 10px #22c55e;
            transition: all 0.2s ease;
        }

        .beam-line.tripped {
            background: #ef4444;
            height: 4px;
            box-shadow: 0 0 18px #ef4444;
            animation: trip-flash 0.15s ease 3;
        }

        @keyframes trip-flash {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        .falling-item-sim {
            position: absolute;
            top: -30px;
            left: 50%;
            transform: translateX(-50%);
            background: #f59e0b;
            color: #111;
            font-size: 0.65rem;
            font-weight: 700;
            padding: 4px 8px;
            border-radius: 4px;
            opacity: 0;
            pointer-events: none;
            transition: all 0.4s cubic-bezier(0.5, 0, 0.75, 0);
            z-index: 5;
        }

        .falling-item-sim.fall {
            opacity: 1;
            top: 20px;
        }

        .ir-status-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.78rem;
        }

        /* Right Column: Serial Monitor */
        .terminal-box {
            background: #090c10;
            border: 1px solid #1f293d;
            border-radius: 10px;
            display: flex;
            flex-direction: column;
            height: 480px;
            font-family: var(--font-mono);
            overflow: hidden;
        }

        .terminal-header {
            background: #141824;
            padding: 8px 12px;
            border-bottom: 1px solid #1f293d;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.74rem;
            color: #94a3b8;
        }

        .terminal-body {
            flex: 1;
            padding: 12px;
            overflow-y: auto;
            font-size: 0.76rem;
            line-height: 1.6;
            color: #a7f3d0;
            white-space: pre-wrap;
            word-break: break-all;
        }

        .log-time { color: #64748b; }
        .log-cmd { color: #38bdf8; font-weight: 600; }
        .log-hw { color: #f59e0b; }
        .log-ir { color: #4ade80; font-weight: 600; }
        .log-err { color: #f87171; font-weight: 600; }

        /* Testing Toolbar */
        .test-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
        }

        .slot-btn {
            background: var(--bg-panel);
            border: 1px solid var(--border);
            color: #e2e8f0;
            border-radius: 6px;
            padding: 8px 6px;
            font-size: 0.74rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .slot-btn:hover {
            border-color: #3b82f6;
            background: #253047;
        }

        .slot-btn .slot-num {
            font-weight: 700;
            color: #38bdf8;
            font-family: var(--font-mono);
            display: block;
            margin-bottom: 2px;
        }

        /* Toggle switch */
        .switch-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.8rem;
            padding: 4px 0;
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 38px;
            height: 20px;
        }

        .switch input { opacity: 0; width: 0; height: 0; }
        .slider {
            position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0;
            background-color: #334155; transition: .3s; border-radius: 20px;
        }
        .slider:before {
            position: absolute; content: ""; height: 14px; width: 14px; left: 3px; bottom: 3px;
            background-color: white; transition: .3s; border-radius: 50%;
        }
        input:checked + .slider { background-color: #3b82f6; }
        input:checked + .slider:before { transform: translateX(18px); }

        /* Modal for Arduino Sketch */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.8);
            backdrop-filter: blur(4px);
            z-index: 100;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-overlay.active { display: flex; }

        .modal-content {
            background: #131722;
            border: 1px solid var(--border);
            border-radius: 12px;
            width: 100%;
            max-width: 800px;
            max-height: 85vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .modal-header {
            padding: 14px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-body {
            padding: 16px 20px;
            overflow-y: auto;
            flex: 1;
            font-family: var(--font-mono);
            font-size: 0.78rem;
            color: #cbd5e1;
            background: #090c10;
        }
    </style>
</head>
<body>

    <!-- Header -->
    <header>
        <div class="header-title">
            <div class="header-icon">
                <i data-lucide="cpu" style="color: #ffffff; width: 22px; height: 22px;"></i>
            </div>
            <div class="header-text">
                <h1>ESP32 Hardware Emulator <span class="tag-pill" id="pollBadge">POLLING: ACTIVE</span></h1>
                <p>PenPoint IoT Testbench • Machine ID: <strong><?= htmlspecialchars($machine_id) ?></strong> • Server: <strong>http://127.0.0.1:8000</strong></p>
            </div>
        </div>
        <div class="header-actions">
            <a href="index.php" target="_blank" class="btn btn-primary" title="Open the Student Kiosk in a split tab">
                <i data-lucide="shopping-cart" style="width: 14px; height: 14px;"></i> Open Student Kiosk
            </a>
            <button class="btn" onclick="openSketchModal()">
                <i data-lucide="code" style="width: 14px; height: 14px;"></i> View Arduino .ino
            </button>
            <button class="btn" onclick="clearQueue()">
                <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i> Clear Queue
            </button>
        </div>
    </header>

    <!-- Workspace Grid -->
    <div class="workspace">

        <!-- Column 1: Microcontroller & Pin Configuration -->
        <div class="card">
            <div class="card-header">
                <span class="card-title"><i data-lucide="microchip" style="width: 16px; height: 16px; color: #4ade80;"></i> ESP32-WROOM-32</span>
                <span style="font-size: 0.72rem; font-family: var(--font-mono); color: #64748b;">38-PIN DEV MODULE</span>
            </div>

            <!-- ESP32 Physical Board Visual -->
            <div class="esp-board">
                <div class="esp-chip">
                    <div class="chip-notch"></div>
                    <div class="chip-label">ESP32-D0WDQ6</div>
                    <div class="chip-sub">Xtensa Dual-Core 240MHz • 520KB SRAM</div>
                </div>

                <div style="font-size: 0.74rem; font-family: var(--font-mono); color: #94a3b8; display: flex; flex-direction: column; gap: 4px;">
                    <div>WiFi SSID: <strong style="color: #4ade80;">SKYW_443E_2G</strong></div>
                    <div>Simulated IP: <strong style="color: #38bdf8;" id="dispIp">192.168.1.105</strong></div>
                    <div>RSSI Signal: <strong style="color: #facc15;">-58 dBm (Excellent)</strong></div>
                    <div>Free Heap: <strong style="color: #cbd5e1;">248,312 bytes</strong></div>
                </div>

                <div class="esp-leds">
                    <div class="led-item"><div class="led-dot pwr active"></div> PWR</div>
                    <div class="led-item"><div class="led-dot wifi active"></div> WIFI</div>
                    <div class="led-item"><div class="led-dot rx" id="ledRx"></div> RX</div>
                    <div class="led-item"><div class="led-dot tx" id="ledTx"></div> TX</div>
                </div>
            </div>

            <!-- Pin Assignments -->
            <div style="font-size: 0.8rem; font-weight: 700; color: #94a3b8; margin-top: 6px;">GPIO PIN MATRIX</div>
            <table class="pin-table">
                <thead>
                    <tr>
                        <th>PIN</th>
                        <th>TARGET</th>
                        <th>TYPE</th>
                    </tr>
                </thead>
                <tbody>
                    <tr id="pin-13"><td><span class="pin-badge">GPIO 13</span></td><td>Servo 1 (Slot 1)</td><td>PWM (50Hz)</td></tr>
                    <tr id="pin-18"><td><span class="pin-badge">GPIO 18</span></td><td>Servo 2 (Slot 2)</td><td>PWM (50Hz)</td></tr>
                    <tr id="pin-19"><td><span class="pin-badge">GPIO 19</span></td><td>Servo 3 (Slot 3)</td><td>PWM (50Hz)</td></tr>
                    <tr id="pin-21"><td><span class="pin-badge">GPIO 21</span></td><td>Servo 4 (Slot 4)</td><td>PWM (50Hz)</td></tr>
                    <tr id="pin-22"><td><span class="pin-badge">GPIO 22</span></td><td>Servo 5 (Slot 5)</td><td>PWM (50Hz)</td></tr>
                    <tr id="pin-25"><td><span class="pin-badge">GPIO 25/26/27</span></td><td>TB6612 #1 Motor 1 (Slot 1)</td><td>PWMA/AIN1/AIN2</td></tr>
                    <tr id="pin-14"><td><span class="pin-badge">GPIO 14/16/17</span></td><td>TB6612 #1 Motor 2 (Slot 2)</td><td>PWMB/BIN1/BIN2</td></tr>
                    <tr id="pin-4"><td><span class="pin-badge">GPIO 4/5/15</span></td><td>TB6612 #2 Motor 3 (Slot 3)</td><td>PWMA/AIN1/AIN2</td></tr>
                    <tr id="pin-33"><td><span class="pin-badge">GPIO 33</span></td><td>Both TB6612 Modules Standby</td><td>STBY (DIGITAL OUT)</td></tr>
                    <tr id="pin-32"><td><span class="pin-badge">GPIO 32</span></td><td>Optical IR Receiver 1 (OUT)</td><td>INPUT (PULLUP)</td></tr>
                    <tr id="pin-23"><td><span class="pin-badge">GPIO 23</span></td><td>Optical IR Receiver 2 (OUT)</td><td>INPUT (PULLUP)</td></tr>
                </tbody>
            </table>

                <!-- Simulation Overrides -->
                <div style="background: #11141c; border-radius: 8px; padding: 12px; display: flex; flex-direction: column; gap: 8px;">
                    <div style="font-size: 0.76rem; font-weight: 700; color: #94a3b8;">SIMULATOR RUNTIME SETTINGS</div>
                    <div class="switch-row">
                        <div>
                            <div>Forward to Physical ESP32 Node</div>
                            <div style="font-size: 0.68rem; color: #38bdf8;">http://192.168.1.26/api/dispense</div>
                        </div>
                        <label class="switch">
                            <input type="checkbox" id="toggleForwardToEsp32" checked>
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="switch-row">
                        <div>
                            <div>Auto-Dispense Pending Orders</div>
                            <div style="font-size: 0.68rem; color: #4ade80;">Active (Automatically processes incoming orders)</div>
                        </div>
                        <label class="switch">
                            <input type="checkbox" id="toggleAutoDispense" checked>
                            <span class="slider"></span>
                        </label>
                    </div>
                <div class="switch-row">
                    <span>Simulate Jam / IR Timeout</span>
                    <label class="switch">
                        <input type="checkbox" id="toggleJamFailure">
                        <span class="slider"></span>
                    </label>
                </div>
                <div class="switch-row">
                    <span>Hardware Sound Effects (SFX)</span>
                    <label class="switch">
                        <input type="checkbox" id="toggleSound" checked>
                        <span class="slider"></span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Column 2: Physical Hardware Actuation & IR Verification -->
        <div class="card">
            <div class="card-header">
                <span class="card-title"><i data-lucide="activity" style="width: 16px; height: 16px; color: #38bdf8;"></i> Actuator & Drop Verification Bay</span>
                <span style="font-size: 0.72rem; color: #38bdf8; font-family: var(--font-mono);" id="actuatorStatusText">IDLE — READY FOR ORDERS</span>
            </div>

            <!-- Servos 1 to 4 Visualizer -->
            <div class="actuators-grid">
                <!-- Servo 1 -->
                <div class="actuator-card" id="card-servo-1">
                    <div class="actuator-header">
                        <span class="actuator-name">Servo 1: Pilot G-Tec</span>
                        <span class="actuator-pin">GPIO 13</span>
                    </div>
                    <div class="servo-dial-wrap">
                        <div class="servo-body">
                            <div class="servo-horn" id="horn-1"></div>
                            <div class="servo-hub"></div>
                        </div>
                    </div>
                    <div class="servo-angle-text" id="angle-1">90° (IDLE)</div>
                </div>

                <!-- Servo 2 -->
                <div class="actuator-card" id="card-servo-2">
                    <div class="actuator-header">
                        <span class="actuator-name">Servo 2: HBW Ballpen</span>
                        <span class="actuator-pin">GPIO 18</span>
                    </div>
                    <div class="servo-dial-wrap">
                        <div class="servo-body">
                            <div class="servo-horn" id="horn-2"></div>
                            <div class="servo-hub"></div>
                        </div>
                    </div>
                    <div class="servo-angle-text" id="angle-2">90° (IDLE)</div>
                </div>

                <!-- Servo 3 -->
                <div class="actuator-card" id="card-servo-3">
                    <div class="actuator-header">
                        <span class="actuator-name">Servo 3: Faber Pencil</span>
                        <span class="actuator-pin">GPIO 19</span>
                    </div>
                    <div class="servo-dial-wrap">
                        <div class="servo-body">
                            <div class="servo-horn" id="horn-3"></div>
                            <div class="servo-hub"></div>
                        </div>
                    </div>
                    <div class="servo-angle-text" id="angle-3">90° (IDLE)</div>
                </div>

                <!-- Servo 4 -->
                <div class="actuator-card" id="card-servo-4">
                    <div class="actuator-header">
                        <span class="actuator-name">Servo 4: Blue Book</span>
                        <span class="actuator-pin">GPIO 21</span>
                    </div>
                    <div class="servo-dial-wrap">
                        <div class="servo-body">
                            <div class="servo-horn" id="horn-4"></div>
                            <div class="servo-hub"></div>
                        </div>
                    </div>
                    <div class="servo-angle-text" id="angle-4">90° (IDLE)</div>
                </div>

                <!-- DC Motor A (Slot 5) -->
                <div class="actuator-card" id="card-dc-5">
                    <div class="actuator-header">
                        <span class="actuator-name">Motor A: Short Bond Paper</span>
                        <span class="actuator-pin">TB6612 (Pin 25)</span>
                    </div>
                    <div class="roller-wrap">
                        <div class="roller-cylinder" id="roller-5"></div>
                        <span style="font-size: 0.68rem; color: #94a3b8; font-family: var(--font-mono);" id="rpm-5">0 RPM (STOP)</span>
                    </div>
                    <div class="servo-angle-text" id="status-5" style="color: #94a3b8;">STANDBY</div>
                </div>

                <!-- DC Motor B (Slot 6) -->
                <div class="actuator-card" id="card-dc-6">
                    <div class="actuator-header">
                        <span class="actuator-name">Motor B: A4 Bond Paper</span>
                        <span class="actuator-pin">TB6612 (Pin 32)</span>
                    </div>
                    <div class="roller-wrap">
                        <div class="roller-cylinder" id="roller-6"></div>
                        <span style="font-size: 0.68rem; color: #94a3b8; font-family: var(--font-mono);" id="rpm-6">0 RPM (STOP)</span>
                    </div>
                    <div class="servo-angle-text" id="status-6" style="color: #94a3b8;">STANDBY</div>
                </div>
            </div>

            <!-- Optical IR Break-Beam Chute -->
            <div class="ir-chute-box">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 0.8rem; font-weight: 700; color: #cbd5e1; display: flex; align-items: center; gap: 6px;">
                        <i data-lucide="scan" style="width: 14px; height: 14px; color: #22c55e;"></i>
                        Optical IR Break-Beam Drop Sensor (Collection Bay)
                    </span>
                    <span class="pin-badge" style="color: #4ade80;">GPIO 34</span>
                </div>

                <div class="ir-beam-graphic">
                    <div class="falling-item-sim" id="fallingItem">ITEM DROPPING</div>
                    <div class="emitter" title="IR LED Emitter"></div>
                    <div class="beam-line" id="irBeamLine"></div>
                    <div class="receiver" title="Phototransistor Receiver"></div>
                </div>

                <div class="ir-status-row">
                    <span id="beamStatusText" style="color: #4ade80; font-family: var(--font-mono); font-weight: 600;">BEAM INTACT (DROP BAY ARMED)</span>
                    <button class="btn" style="font-size: 0.72rem; padding: 4px 8px;" onclick="manuallyTripBeam()">
                        <i data-lucide="zap" style="width: 12px; height: 12px;"></i> Manually Trip Beam
                    </button>
                </div>
            </div>

            <!-- API Connection & Communication Diagnostic Suite -->
            <div style="background: #11141c; border: 1px solid #2563eb; border-radius: 10px; padding: 14px; display: flex; flex-direction: column; gap: 10px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 0.8rem; font-weight: 700; color: #60a5fa; display: flex; align-items: center; gap: 6px;">
                        <i data-lucide="radio" style="width: 14px; height: 14px; color: #3b82f6;"></i>
                        Website API Communication Test Suite
                    </span>
                    <span style="font-size: 0.68rem; font-family: var(--font-mono); color: #94a3b8;">ESP32 ⇄ PenPoint API</span>
                </div>
                <div style="font-size: 0.74rem; color: #94a3b8;">
                    Click each button to test bidirectional HTTP requests between ESP32 and website backend:
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                    <button class="btn btn-primary" style="justify-content: center; font-size: 0.76rem;" onclick="testPingApi()">
                        <i data-lucide="activity" style="width: 13px; height: 13px;"></i> 1. Ping / Heartbeat
                    </button>
                    <button class="btn btn-primary" style="justify-content: center; font-size: 0.76rem;" onclick="testPollApi()">
                        <i data-lucide="refresh-cw" style="width: 13px; height: 13px;"></i> 2. Poll Dispense Queue
                    </button>
                    <button class="btn" style="justify-content: center; font-size: 0.76rem; background: #b45309; border-color: #f59e0b;" onclick="triggerTestOrder(2)">
                        <i data-lucide="play" style="width: 13px; height: 13px;"></i> 3. Order & Dispense Slot 2
                    </button>
                    <button class="btn btn-success" style="justify-content: center; font-size: 0.76rem;" onclick="testVerifyCallbackApi()">
                        <i data-lucide="check-circle" style="width: 13px; height: 13px;"></i> 4. Test IR Drop Callback
                    </button>
                </div>
            </div>

            <!-- Manual Test Trigger Toolbar -->
            <div>
                <div style="font-size: 0.8rem; font-weight: 700; color: #94a3b8; margin-bottom: 8px;">
                    QUICK DISPENSE (SLOTS 1 TO 6)
                </div>
                <div class="test-grid">
                    <button class="slot-btn" onclick="triggerTestOrder(1)">
                        <span class="slot-num">SLOT #1</span>
                        <span>Pilot G-Tec Pen</span>
                    </button>
                    <button class="slot-btn" onclick="triggerTestOrder(2)">
                        <span class="slot-num">SLOT #2</span>
                        <span>HBW Ballpen</span>
                    </button>
                    <button class="slot-btn" onclick="triggerTestOrder(3)">
                        <span class="slot-num">SLOT #3</span>
                        <span>Faber 2B Pencil</span>
                    </button>
                    <button class="slot-btn" onclick="triggerTestOrder(4)">
                        <span class="slot-num">SLOT #4</span>
                        <span>Blue Book</span>
                    </button>
                    <button class="slot-btn" onclick="triggerTestOrder(5)">
                        <span class="slot-num">SLOT #5</span>
                        <span>Short Bond Paper</span>
                    </button>
                    <button class="slot-btn" onclick="triggerTestOrder(6)">
                        <span class="slot-num">SLOT #6</span>
                        <span>A4 Bond Paper</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Column 3: Live Serial Monitor Console & Queue -->
        <div class="card">
            <div class="card-header">
                <span class="card-title"><i data-lucide="terminal" style="width: 16px; height: 16px; color: #f59e0b;"></i> Serial Monitor (115200)</span>
                <div style="display: flex; gap: 6px;">
                    <button class="btn" style="padding: 2px 8px; font-size: 0.7rem;" onclick="clearConsole()">Clear</button>
                </div>
            </div>

            <!-- Terminal Output Box -->
            <div class="terminal-box">
                <div class="terminal-header">
                    <span>ESP32 UART0 Console • Baud 115200</span>
                    <span id="termStatus" style="color: #4ade80;">● LISTENING</span>
                </div>
                <div class="terminal-body" id="consoleBody"></div>
            </div>

            <!-- Recent Hardware Queue Items -->
            <div style="font-size: 0.8rem; font-weight: 700; color: #94a3b8; margin-top: 6px;">RECENT QUEUED COMMANDS</div>
            <div id="recentQueueList" style="display: flex; flex-direction: column; gap: 6px; max-height: 160px; overflow-y: auto;">
                <div style="font-size: 0.75rem; color: #64748b; font-family: var(--font-mono);">No commands processed yet.</div>
            </div>
        </div>

    </div>

    <!-- Modal: Arduino C++ Sketch -->
    <div class="modal-overlay" id="sketchModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 style="font-size: 0.95rem; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="file-code" style="color: #38bdf8; width: 18px; height: 18px;"></i>
                    ESP32 PenPoint Vending Arduino Sketch (.ino)
                </h3>
                <div style="display: flex; gap: 8px;">
                    <button class="btn btn-primary" onclick="copyArduinoCode()">
                        <i data-lucide="copy" style="width: 14px; height: 14px;"></i> Copy Code
                    </button>
                    <button class="btn" onclick="closeSketchModal()">Close</button>
                </div>
            </div>
            <div class="modal-body">
                <pre id="arduinoCodeBlock">Loading Arduino sketch...</pre>
            </div>
        </div>
    </div>

    <script>
        // Initialize Lucide Icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }

        const MACHINE_ID = "<?= htmlspecialchars($machine_id) ?>";
        const consoleBody = document.getElementById('consoleBody');
        const pollBadge = document.getElementById('pollBadge');
        const ledRx = document.getElementById('ledRx');
        const ledTx = document.getElementById('ledTx');
        const irBeamLine = document.getElementById('irBeamLine');
        const beamStatusText = document.getElementById('beamStatusText');
        const fallingItem = document.getElementById('fallingItem');
        const actuatorStatusText = document.getElementById('actuatorStatusText');

        let isDispensing = false;
        let pollTimer = null;

        // Web Audio API synthesizer for sound effects
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        function playBeep(freq = 800, type = 'sine', duration = 0.1) {
            if (!document.getElementById('toggleSound').checked) return;
            try {
                const osc = audioCtx.createOscillator();
                const gain = audioCtx.createGain();
                osc.type = type;
                osc.frequency.value = freq;
                gain.gain.setValueAtTime(0.08, audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.0001, audioCtx.currentTime + duration);
                osc.connect(gain);
                gain.connect(audioCtx.destination);
                osc.start();
                osc.stop(audioCtx.currentTime + duration);
            } catch (e) {}
        }

        function playServoBuzz() {
            if (!document.getElementById('toggleSound').checked) return;
            try {
                const osc = audioCtx.createOscillator();
                const gain = audioCtx.createGain();
                osc.type = 'sawtooth';
                osc.frequency.setValueAtTime(140, audioCtx.currentTime);
                osc.frequency.linearRampToValueAtTime(220, audioCtx.currentTime + 0.3);
                gain.gain.setValueAtTime(0.04, audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.5);
                osc.connect(gain);
                gain.connect(audioCtx.destination);
                osc.start();
                osc.stop(audioCtx.currentTime + 0.5);
            } catch (e) {}
        }

        function log(msg, type = 'normal') {
            const now = new Date();
            const timeStr = now.toTimeString().split(' ')[0] + '.' + String(now.getMilliseconds()).padStart(3, '0');
            
            let colorClass = '';
            if (type === 'cmd') colorClass = 'log-cmd';
            else if (type === 'hw') colorClass = 'log-hw';
            else if (type === 'ir') colorClass = 'log-ir';
            else if (type === 'err') colorClass = 'log-err';

            const line = document.createElement('div');
            line.innerHTML = `<span class="log-time">[${timeStr}]</span> <span class="${colorClass}">${msg}</span>`;
            consoleBody.appendChild(line);
            consoleBody.scrollTop = consoleBody.scrollHeight;
        }

        function clearConsole() {
            consoleBody.innerHTML = '';
            log('Console cleared.', 'hw');
        }

        // Flash TX / RX LEDs
        function blinkRx() {
            ledRx.classList.add('active');
            setTimeout(() => ledRx.classList.remove('active'), 120);
        }
        function blinkTx() {
            ledTx.classList.add('active');
            setTimeout(() => ledTx.classList.remove('active'), 120);
        }

        // Start Boot Log
        log('--- ESP32 SYSTEM BOOT (PenPoint Smart Vending) ---', 'hw');
        log('Connecting to WiFi: SKYW_443E_2G...', 'normal');
        setTimeout(() => {
            log('WiFi Connected! IP: 192.168.1.105 (RSSI: -58 dBm)', 'ir');
            log('HTTP Client initialized. Polling server at: http://127.0.0.1:8000/api/esp32.php', 'normal');
            startPolling();
        }, 600);

        let lastNotifiedPendingId = null;

        // Polling loop to check for commands queued by kiosk
        function startPolling() {
            if (pollTimer) clearInterval(pollTimer);
            pollTimer = setInterval(async () => {
                if (isDispensing) return;

                blinkRx();
                try {
                    const resp = await fetch(`../api/esp32.php?action=poll&machine_id=${MACHINE_ID}`);
                    const data = await resp.json();

                    if (data.has_command && data.command) {
                        const autoDisp = document.getElementById('toggleAutoDispense')?.checked ?? true;
                        if (autoDisp) {
                            lastNotifiedPendingId = null;
                            executeDispense(data.command);
                        } else {
                            if (lastNotifiedPendingId !== data.command.command_id) {
                                lastNotifiedPendingId = data.command.command_id;
                                log(`[QUEUE] Pending command received: ${data.command.command_id} for Slot #${data.command.slot} (${data.command.product_name}). Click 'DISPENSE NOW' in Recent Queue or enable Auto-Dispense.`, 'cmd');
                            }
                        }
                    } else {
                        lastNotifiedPendingId = null;
                    }
                } catch (e) {
                    // Silently continue polling
                }
            }, 900);

            // Also periodic heartbeat every 5s
            setInterval(async () => {
                try {
                    await fetch(`../api/esp32.php?action=heartbeat&machine_id=${MACHINE_ID}&rssi=-58&uptime_sec=120`);
                    updateRecentQueue();
                } catch (e) {}
            }, 4000);
        }

        // Execute Simulated Physical Dispensing
        async function executeDispense(cmd) {
            isDispensing = true;
            blinkRx();
            playBeep(920, 'sine', 0.15);

            const slot = cmd.slot;
            const pin = cmd.pin;
            const mechanism = cmd.mechanism;
            const simulateJam = document.getElementById('toggleJamFailure').checked;

            log(`[RX COMMAND] DISPENSE TRIGGER: ID ${cmd.command_id}`, 'cmd');
            log(`[HARDWARE] Target: Slot #${slot} (${cmd.product_name}) x${cmd.quantity} | Mech: ${mechanism} | GPIO: ${pin}`, 'hw');
            actuatorStatusText.innerText = `DISPENSING SLOT #${slot} (${cmd.product_name})...`;
            actuatorStatusText.style.color = '#f59e0b';

            // 1. Report START_DISPENSE to server
            try {
                await fetch(`../api/esp32.php?action=start_dispense&command_id=${cmd.command_id}&machine_id=${MACHINE_ID}`);
                blinkTx();
            } catch (e) {}

            // Highlight active pin row in table
            const pinRow = document.getElementById(`pin-${pin}`);
            if (pinRow) pinRow.classList.add('pin-active');

            // 2. Actuate Physical Servo or DC Motor
            if (mechanism === 'servo') {
                const horn = document.getElementById(`horn-${slot}`);
                const card = document.getElementById(`card-servo-${slot}`);
                const angleText = document.getElementById(`angle-${slot}`);

                if (horn && card && angleText) {
                    card.classList.add('active');
                    horn.style.transform = 'rotate(180deg)';
                    angleText.innerText = '180° (PUSHING)';
                    angleText.style.color = '#ef4444';
                    playServoBuzz();
                    log(`[GPIO ${pin}] Servo PWM angle driven to 180° (Actuating coil/paddle)...`, 'hw');
                }

                // Wait 1.1s for mechanical travel
                await new Promise(r => setTimeout(r, 1100));

                if (horn && angleText) {
                    horn.style.transform = 'rotate(90deg)';
                    angleText.innerText = '90° (IDLE)';
                    angleText.style.color = '#38bdf8';
                    if (card) card.classList.remove('active');
                    log(`[GPIO ${pin}] Servo returned to 90° center position.`, 'normal');
                }
            } else {
                // DC Rubber Roller (Slots 5 or 6)
                const roller = document.getElementById(`roller-${slot}`);
                const rpm = document.getElementById(`rpm-${slot}`);
                const card = document.getElementById(`card-dc-${slot}`);
                const status = document.getElementById(`status-${slot}`);

                if (roller && card) {
                    card.classList.add('active');
                    roller.classList.add('spinning');
                    if (rpm) rpm.innerText = '240 RPM (FORWARD)';
                    if (status) { status.innerText = 'FEEDING SHEET'; status.style.color = '#22c55e'; }
                    playServoBuzz();
                    log(`[TB6612] STBY=HIGH | PWMA/B Speed=200 PWM | Motor running...`, 'hw');
                }

                // Wait 1.4s for paper roller feed
                await new Promise(r => setTimeout(r, 1400));

                if (roller) {
                    roller.classList.remove('spinning');
                    if (rpm) rpm.innerText = '0 RPM (STOP)';
                    if (status) { status.innerText = 'STANDBY'; status.style.color = '#94a3b8'; }
                    if (card) card.classList.remove('active');
                    log(`[TB6612] Motor stopped. Standby asserted.`, 'normal');
                }
            }

            if (pinRow) pinRow.classList.remove('pin-active');

            // 3. Sensor Drop Verification (or Jam Timeout)
            if (!simulateJam) {
                // Normal Success: Product drops through optical IR beam
                fallingItem.innerText = `${cmd.product_name}`;
                fallingItem.classList.add('fall');
                await new Promise(r => setTimeout(r, 250));

                // Beam tripped!
                irBeamLine.classList.add('tripped');
                beamStatusText.innerText = 'IR BEAM TRIPPED 🟢 (PRODUCT DETECTED IN TRAY)';
                beamStatusText.style.color = '#ef4444';
                playBeep(1200, 'triangle', 0.18);
                log(`[OPTICAL SENSOR] GPIO 34 beam broken! Item dropped into collection bay.`, 'ir');

                await new Promise(r => setTimeout(r, 650));
                irBeamLine.classList.remove('tripped');
                fallingItem.classList.remove('fall');
                beamStatusText.innerText = 'BEAM INTACT (DROP BAY ARMED)';
                beamStatusText.style.color = '#4ade80';

                // 4. Report COMPLETE to server
                try {
                    const comp = await fetch(`../api/esp32.php?action=complete_dispense&command_id=${cmd.command_id}&ir_verified=1&machine_id=${MACHINE_ID}`);
                    const cData = await comp.json();
                    blinkTx();
                    log(`[TX API] Dispense verified callback sent: status = ${cData.status}`, 'ir');
                } catch (e) {}

                actuatorStatusText.innerText = `COMPLETED: Slot #${slot} Dispensed & Verified!`;
                actuatorStatusText.style.color = '#22c55e';
            } else {
                // Simulated Jam / Sensor Failure
                log(`[SENSOR TIMEOUT] Optical beam was NOT interrupted within 2500ms!`, 'err');
                log(`[REVERSE RETRY] Executing 1x anti-jam oscillation...`, 'hw');
                playBeep(400, 'sawtooth', 0.3);

                await new Promise(r => setTimeout(r, 800));
                log(`[JAM PERSISTENT] Drop unconfirmed after reverse retry. Status: REFUND_PENDING`, 'err');

                try {
                    await fetch(`../api/esp32.php?action=complete_dispense&command_id=${cmd.command_id}&ir_verified=0&details=Optical+break+beam+timed+out&machine_id=${MACHINE_ID}`);
                    blinkTx();
                    log(`[TX API] Failure reported to server. Automated student refund initiated.`, 'err');
                } catch (e) {}

                actuatorStatusText.innerText = `FAILED: Sensor Timeout on Slot #${slot} (Refund Queued)`;
                actuatorStatusText.style.color = '#ef4444';
            }

            updateRecentQueue();
            setTimeout(() => {
                actuatorStatusText.innerText = 'IDLE — READY FOR ORDERS';
                actuatorStatusText.style.color = '#38bdf8';
                isDispensing = false;
            }, 1200);
        }

        // API Diagnostic Suite Test Handlers
        async function testPingApi() {
            log('>>> [TEST 1] Sending Ping/Heartbeat to /api/esp32.php...', 'hw');
            blinkTx();
            const t0 = performance.now();
            try {
                const resp = await fetch(`../api/esp32.php?action=heartbeat&machine_id=${MACHINE_ID}&rssi=-58&uptime_sec=140`);
                const data = await resp.json();
                const dt = Math.round(performance.now() - t0);
                blinkRx();
                if (data.success) {
                    playBeep(880, 'sine', 0.12);
                    log(`[PASS 🟢] Ping OK! Latency: ${dt}ms | Status: ${data.status} | Server Time: ${data.server_time}`, 'ir');
                } else {
                    log(`[FAIL 🔴] Server responded with error.`, 'err');
                }
            } catch (e) {
                log(`[ERROR] Ping failed: ${e}`, 'err');
            }
        }

        async function testPollApi() {
            log('>>> [TEST 2] Polling /api/esp32.php?action=poll...', 'hw');
            blinkTx();
            try {
                const resp = await fetch(`../api/esp32.php?action=poll&machine_id=${MACHINE_ID}`);
                const data = await resp.json();
                blinkRx();
                if (data.has_command && data.command) {
                    playBeep(1000, 'triangle', 0.15);
                    log(`[FOUND 🟢] Active Command: ${data.command.command_id} for Slot #${data.command.slot} (${data.command.product_name} x${data.command.quantity})`, 'cmd');
                } else {
                    playBeep(600, 'sine', 0.1);
                    log(`[CLEAR 🟢] Queue empty (has_command = false). Server is responsive & awaiting kiosk orders.`, 'ir');
                }
            } catch (e) {
                log(`[ERROR] Poll failed: ${e}`, 'err');
            }
        }

        async function testVerifyCallbackApi() {
            log('>>> [TEST 4] Testing IR Verification Callback to /api/esp32.php...', 'hw');
            blinkTx();
            const dummyCmd = 'CMD-TEST-CB-' + Math.floor(1000 + Math.random() * 9000);
            try {
                const resp = await fetch(`../api/esp32.php?action=complete_dispense&command_id=${dummyCmd}&ir_verified=1&details=Manual+Diagnostic+Button&machine_id=${MACHINE_ID}`);
                const data = await resp.json();
                blinkRx();
                playBeep(1100, 'sine', 0.15);
                log(`[PASS 🟢] Verification callback acknowledged by server! Status: ${data.status} | Time: ${data.time}`, 'ir');
                updateRecentQueue();
            } catch (e) {
                log(`[ERROR] Callback failed: ${e}`, 'err');
            }
        }

        // Test Order Trigger from Simulator Buttons
        async function triggerTestOrder(slot) {
            log(`[TEST BTN] Queuing manual test order for Slot #${slot}...`, 'hw');
            try {
                // 1. Inject to central server database queue
                const resp = await fetch(`../api/esp32.php?action=trigger_test&slot=${slot}&machine_id=${MACHINE_ID}`);
                const data = await resp.json();
                const cmdId = data.command_id || ('CMD-TEST-' + Date.now());
                if (data.success) {
                    log(`[TEST INJECTED] Command ID: ${cmdId}`, 'cmd');
                }

                // 2. Direct HTTP push to physical ESP32 if forward is enabled
                const shouldForward = document.getElementById('toggleForwardToEsp32')?.checked;
                if (shouldForward) {
                    log(`[HTTP PUSH] Sending direct vend to physical ESP32 (http://192.168.1.26/api/dispense)...`, 'hw');
                    try {
                        const directUrl = `http://192.168.1.26/api/dispense?slot=${slot}&qty=1&cmd_id=${encodeURIComponent(cmdId)}&prod=Slot+${slot}`;
                        fetch(directUrl, { mode: 'cors' }).then(r=>r.json()).then(res=>{
                            log(`[ESP32 PHYSICAL 🟢] Response: Slot #${slot} Drop Verified = ${res.ir_verified}`, 'ir');
                        }).catch(e=>{
                            log(`[ESP32 NOTE] Direct push sent. Polling queue is active on ESP32.`, 'cmd');
                        });
                    } catch(e) {}
                }
            } catch (e) {
                log(`[ERROR] Failed to inject test command: ${e}`, 'err');
            }
        }

        // Manual Beam Trip
        function manuallyTripBeam() {
            irBeamLine.classList.add('tripped');
            beamStatusText.innerText = 'MANUALLY TRIPPED 🔴';
            beamStatusText.style.color = '#ef4444';
            playBeep(1200, 'sine', 0.15);
            log('[MANUAL SENSOR] GPIO 34 IR beam interrupted manually.', 'ir');
            setTimeout(() => {
                irBeamLine.classList.remove('tripped');
                beamStatusText.innerText = 'BEAM INTACT (DROP BAY ARMED)';
                beamStatusText.style.color = '#4ade80';
            }, 600);
        }

        // Manual Trigger for Pending Command in Queue
        async function executeDispenseManually(cmdId, slot, prodName, mech) {
            if (isDispensing) {
                alert('A dispense operation is already in progress.');
                return;
            }
            const isDc = (mech === 'dc_roller' || mech === 'motor' || slot === 5 || slot === 6);
            const pinMap = isDc 
                ? { 1: 25, 2: 14, 3: 4, 4: 21, 5: 25, 6: 14 }
                : { 1: 13, 2: 18, 3: 19, 4: 21, 5: 22, 6: 22 };
            executeDispense({
                command_id: cmdId,
                slot: parseInt(slot),
                product_name: decodeURIComponent(prodName),
                quantity: 1,
                mechanism: isDc ? 'dc_roller' : 'servo',
                pin: pinMap[slot] || 13
            });
        }

        // Update Recent Queue list
        async function updateRecentQueue() {
            try {
                const resp = await fetch(`../api/esp32.php?action=status_summary&machine_id=${MACHINE_ID}`);
                const data = await resp.json();
                const container = document.getElementById('recentQueueList');

                if (data.recent_queue && data.recent_queue.length > 0) {
                    container.innerHTML = data.recent_queue.map(q => {
                        let badgeColor = '#94a3b8';
                        if (q.status === 'COMPLETED') badgeColor = '#22c55e';
                        else if (q.status === 'DISPENSING') badgeColor = '#3b82f6';
                        else if (q.status === 'FAILED') badgeColor = '#ef4444';
                        else if (q.status === 'PENDING') badgeColor = '#f59e0b';

                        let badgeHtml = `
                            <span style="background: ${badgeColor}22; color: ${badgeColor}; border: 1px solid ${badgeColor}55; padding: 2px 6px; border-radius: 4px; font-weight: 700; font-family: var(--font-mono);">
                                ${q.status}
                            </span>
                        `;

                        if (q.status === 'PENDING') {
                            badgeHtml = `
                                <button onclick="executeDispenseManually('${q.command_id}', ${q.slot_number}, '${encodeURIComponent(q.product_name)}', '${q.mechanism}')" 
                                        style="background: #f59e0b; color: #111; border: none; padding: 3px 8px; border-radius: 4px; font-weight: 700; font-family: var(--font-mono); font-size: 0.7rem; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;"
                                        title="Click to manually execute this pending vend now">
                                    <span>DISPENSE NOW ▶</span>
                                </button>
                            `;
                        }

                        return `
                            <div style="background: #11141c; padding: 6px 10px; border-radius: 6px; font-size: 0.72rem; display: flex; justify-content: space-between; align-items: center; border: 1px solid rgba(255,255,255,0.04);">
                                <div>
                                    <strong style="color: #cbd5e1;">Slot #${q.slot_number}:</strong> ${q.product_name}
                                    <div style="color: #64748b; font-family: var(--font-mono); font-size: 0.65rem;">${q.command_id}</div>
                                </div>
                                <div>
                                    ${badgeHtml}
                                </div>
                            </div>
                        `;
                    }).join('');
                }
            } catch (e) {}
        }

        // Clear Queue
        async function clearQueue() {
            if (confirm('Clear all queued and completed dispense jobs?')) {
                await fetch(`../api/esp32.php?action=clear_queue&machine_id=${MACHINE_ID}`);
                log('[SYSTEM] Dispense queue cleared by operator.', 'hw');
                updateRecentQueue();
            }
        }

        // Sketch Modal Helpers
        function openSketchModal() {
            document.getElementById('sketchModal').classList.add('active');
            loadSketchCode();
        }

        function closeSketchModal() {
            document.getElementById('sketchModal').classList.remove('active');
        }

        async function loadSketchCode() {
            try {
                const resp = await fetch('../scripts/esp32_penpoint_vending.ino');
                const text = await resp.text();
                document.getElementById('arduinoCodeBlock').textContent = text;
            } catch (e) {
                document.getElementById('arduinoCodeBlock').textContent = '// Please see scripts/esp32_penpoint_vending.ino in the repository.';
            }
        }

        function copyArduinoCode() {
            const code = document.getElementById('arduinoCodeBlock').textContent;
            navigator.clipboard.writeText(code).then(() => {
                alert('Arduino .ino code copied to clipboard!');
            });
        }
    </script>
</body>
</html>
