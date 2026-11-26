<?php
// api/esp32.php
// Dedicated REST API for ESP32 Microcontroller & Simulated Vending Telemetry
// Connects ESP32 Hardware / Simulated Web Page <--> PenPoint Kiosk Server

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/DispenseQueueModel.php';
require_once __DIR__ . '/../models/ProductModel.php';
require_once __DIR__ . '/../models/MachineModel.php';
require_once __DIR__ . '/../models/ActivityLogModel.php';
require_once __DIR__ . '/../models/TransactionModel.php';

$action = trim($_REQUEST['action'] ?? 'status_summary');
$machine_id = trim($_REQUEST['machine_id'] ?? DEFAULT_MACHINE_ID);

$queueModel = new DispenseQueueModel();
$productModel = new ProductModel();
$machineModel = new MachineModel();
$logModel = new ActivityLogModel();
$txnModel = new TransactionModel();

switch ($action) {
    // -------------------------------------------------------------------------
    // 1. ESP32 Poll: Checks for new pending dispense commands
    // -------------------------------------------------------------------------
    case 'poll':
        $next = $queueModel->getNextPending($machine_id);

        if ($next) {
            $slot = intval($next['slot_number']);
            $mechanism = $next['mechanism'];
            $isDcRoller = ($slot == 5 || $slot == 6 || $mechanism === 'dc_roller' || $mechanism === 'roller_feed' || $mechanism === 'motor');
            
            // Map to ESP32 pins:
            // Servos 1 to 5: 13, 18, 19, 21, 22
            // TB6612 Motors: Motor 1 (25), Motor 2 (14), Motor 3 (4), STBY (33)
            // Optical IR Break-Beam Receivers: 32 (IR 1), 23 (IR 2)
            $pinMap = $isDcRoller 
                ? [1 => 25, 2 => 14, 3 => 4, 4 => 21, 5 => 25, 6 => 14, 7 => 4, 8 => 4]
                : [1 => 13, 2 => 18, 3 => 19, 4 => 21, 5 => 22, 6 => 22, 7 => 22, 8 => 22];
            $pin = intval($next['pin_used'] ?: ($pinMap[$slot] ?? 13));

            echo json_encode([
                'success' => true,
                'has_command' => true,
                'command' => [
                    'id' => intval($next['id']),
                    'command_id' => $next['command_id'],
                    'machine_id' => $next['machine_id'],
                    'slot' => $slot,
                    'product_name' => $next['product_name'],
                    'quantity' => intval($next['quantity']),
                    'mechanism' => $isDcRoller ? 'dc_roller' : 'servo',
                    'tier' => intval($next['tier_number'] ?: ($slot <= 3 ? $slot : 4)),
                    'pin' => $pin,
                    'servo_angle_active' => 180,
                    'servo_angle_idle' => 90,
                    'pwm_speed' => 200,
                    'duration_ms' => $isDcRoller ? (3000 * intval($next['quantity'])) : 1600,
                    'ir_sensor_pin' => 34,
                    'created_at' => $next['created_at']
                ]
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'has_command' => false,
                'machine_id' => $machine_id,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        }
        break;

    // -------------------------------------------------------------------------
    // 2. Start Dispense: ESP32 acknowledges and starts actuation
    // -------------------------------------------------------------------------
    case 'start_dispense':
        $command_id = trim($_REQUEST['command_id'] ?? '');
        if (!$command_id) {
            echo json_encode(['success' => false, 'error' => 'command_id required']);
            exit;
        }

        $queueModel->updateStatus($command_id, 'DISPENSING');
        $logModel->addLog('dispense', 'ESP32_HARDWARE', $machine_id, "ESP32 started dispensing for {$command_id}");

        echo json_encode([
            'success' => true,
            'status' => 'DISPENSING',
            'command_id' => $command_id,
            'time' => date('Y-m-d H:i:s')
        ]);
        break;

    // -------------------------------------------------------------------------
    // 3. Complete Dispense: ESP32 confirms IR beam drop verification
    // -------------------------------------------------------------------------
    case 'complete_dispense':
        $command_id = trim($_REQUEST['command_id'] ?? '');
        $ir_verified = isset($_REQUEST['ir_verified']) ? intval($_REQUEST['ir_verified']) : 1;
        $details = trim($_REQUEST['details'] ?? 'Drop confirmed by IR Break-Beam Sensor.');

        if (!$command_id) {
            echo json_encode(['success' => false, 'error' => 'command_id required']);
            exit;
        }

        $newStatus = ($ir_verified === 1) ? 'COMPLETED' : 'FAILED';
        $queueModel->updateStatus($command_id, $newStatus, $ir_verified, $details);

        $logModel->addLog(
            $ir_verified ? 'verification' : 'dispense_failed',
            'ESP32_HARDWARE',
            $machine_id,
            "ESP32 Result for {$command_id}: " . ($ir_verified ? "IR Drop Verified (PASS)" : "IR Drop Timeout (FAIL - Refund Queued)") . " - {$details}"
        );

        echo json_encode([
            'success' => true,
            'status' => $newStatus,
            'command_id' => $command_id,
            'ir_verified' => (bool)$ir_verified,
            'time' => date('Y-m-d H:i:s')
        ]);
        break;

    // -------------------------------------------------------------------------
    // 3b. Check Command Status: Kiosk polls this to wait for ESP32 completion
    // -------------------------------------------------------------------------
    case 'check_command':
        $command_id = trim($_REQUEST['command_id'] ?? '');
        if (!$command_id) {
            echo json_encode(['success' => false, 'error' => 'command_id required']);
            exit;
        }

        $cmd = $queueModel->getByCommandId($command_id);
        if ($cmd) {
            echo json_encode([
                'success' => true,
                'found' => true,
                'status' => $cmd['status'], // PENDING, DISPENSING, COMPLETED, FAILED
                'ir_verified' => (bool)$cmd['ir_verified'],
                'command_id' => $cmd['command_id'],
                'slot_number' => intval($cmd['slot_number']),
                'product_name' => $cmd['product_name'],
                'quantity' => intval($cmd['quantity']),
                'details' => $cmd['details'],
                'updated_at' => $cmd['updated_at']
            ]);
        } else {
            echo json_encode(['success' => false, 'found' => false]);
        }
        break;

    // -------------------------------------------------------------------------
    // 4. ESP32 Heartbeat / Ping
    // -------------------------------------------------------------------------
    case 'heartbeat':
    case 'ping':
        $ip = trim($_REQUEST['ip_address'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        $rssi = intval($_REQUEST['rssi'] ?? -60);
        $uptime = intval($_REQUEST['uptime_sec'] ?? 0);
        $heap = intval($_REQUEST['free_heap'] ?? 240000);

        // Update machines table
        $conn = getConnection();
        if ($conn) {
            $stmt = $conn->prepare("UPDATE machines SET last_ping = NOW(), ip_address = ?, status = 'ACTIVE' WHERE machine_id = ?");
            if ($stmt) {
                $stmt->bind_param("ss", $ip, $machine_id);
                $stmt->execute();
                $stmt->close();
            }
        }

        echo json_encode([
            'success' => true,
            'machine_id' => $machine_id,
            'status' => 'ONLINE',
            'server_time' => date('Y-m-d H:i:s'),
            'rssi' => $rssi,
            'ip' => $ip,
            'uptime_sec' => $uptime,
            'free_heap' => $heap
        ]);
        break;

    // -------------------------------------------------------------------------
    // 5. Test Trigger: Inject a simulated order from simulator page
    // -------------------------------------------------------------------------
    case 'trigger_test':
        $slot = intval($_REQUEST['slot'] ?? 2);
        $qty = max(1, min(5, intval($_REQUEST['qty'] ?? 1)));
        $fail = (isset($_REQUEST['fail']) && ($_REQUEST['fail'] === '1' || $_REQUEST['fail'] === 'true'));

        $product = $productModel->getProductBySlot($slot);
        $prodName = $product ? $product['name'] : "Test Supply (Slot #{$slot})";
        $prodId = $product ? intval($product['id']) : null;
        $tier = intval($product['tier_number'] ?? ($slot <= 3 ? $slot : 4));

        $pinMap = [1 => 13, 2 => 19, 3 => 22, 4 => 4, 5 => 25, 6 => 14];
        $isDcRoller = ($slot == 5 || $slot == 6);
        $pin = $pinMap[$slot] ?? 13;
        $mechanism = $isDcRoller ? 'dc_roller' : 'servo';

        $cmd_id = 'CMD-' . $machine_id . '-' . time() . '-' . rand(100, 999);
        $res = $queueModel->enqueue($machine_id, $slot, $prodName, $qty, $mechanism, $tier, $pin, $prodId, $cmd_id);

        if ($product && $product['stock'] >= $qty) {
            $productModel->decrementStock($slot, $qty);
        }

        $logModel->addLog(
            'qr_generated', 'SIMULATOR_TEST', $machine_id,
            "Simulator queued manual dispense for Slot #{$slot} ({$prodName} x{$qty}). Cmd: {$cmd_id}"
        );

        echo json_encode([
            'success' => (bool)$res,
            'command_id' => $cmd_id,
            'slot' => $slot,
            'product_name' => $prodName,
            'quantity' => $qty,
            'mechanism' => $mechanism,
            'pin' => $pin,
            'simulate_failure' => $fail
        ]);
        break;

    // -------------------------------------------------------------------------
    // 6. Clear Queue (For testing)
    // -------------------------------------------------------------------------
    case 'clear_queue':
        $cleared = $queueModel->clearQueue($machine_id);
        $logModel->addLog('machine_status', 'ADMIN', $machine_id, "Hardware dispense queue cleared via API.");
        echo json_encode(['success' => (bool)$cleared, 'message' => 'Dispense queue cleared']);
        break;

    // -------------------------------------------------------------------------
    // 7. Status Summary: Combined dashboard telemetry for simulator
    // -------------------------------------------------------------------------
    case 'status_summary':
    default:
        $recent = $queueModel->getRecent($machine_id, 10);
        $recentLogs = $logModel->getLogs(10);
        $pending = $queueModel->getNextPending($machine_id);

        $conn = getConnection();
        $machineInfo = ['machine_id' => $machine_id, 'status' => 'ACTIVE', 'location' => 'Main Campus Kiosk', 'last_ping' => date('Y-m-d H:i:s')];
        if ($conn) {
            $stmt = $conn->prepare("SELECT * FROM machines WHERE machine_id = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("s", $machine_id);
                $stmt->execute();
                $r = $stmt->get_result()->fetch_assoc();
                if ($r) $machineInfo = $r;
                $stmt->close();
            }
        }

        echo json_encode([
            'success' => true,
            'machine' => $machineInfo,
            'has_pending' => ($pending !== null),
            'pending_command' => $pending,
            'recent_queue' => $recent,
            'recent_logs' => $recentLogs,
            'server_time' => date('Y-m-d H:i:s'),
            'hardware_config' => [
                'tb6612_pins' => [
                    'TB6612_1_Slot1' => ['PWMA' => 25, 'AIN1' => 26, 'AIN2' => 27],
                    'TB6612_1_Slot2' => ['PWMB' => 14, 'BIN1' => 16, 'BIN2' => 17],
                    'TB6612_2_Slot3' => ['PWMA' => 4,  'AIN1' => 5,  'AIN2' => 15],
                    'STBY' => 33
                ],
                'servo_pins' => [
                    'Slot1_Front' => 13,
                    'Slot1_Rear'  => 18,
                    'Slot2_Front' => 19,
                    'Slot2_Rear'  => 21,
                    'Slot3_Front' => 22,
                    'Slot3_Rear'  => 2
                ],
                'ir_receivers' => ['IR1' => 32, 'IR2' => 23],
                'wifi_ssid' => 'GFiber_AD06B'
            ]
        ]);
        break;
}
