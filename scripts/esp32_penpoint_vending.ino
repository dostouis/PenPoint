/*

  =============================================================================

  PenPoint Smart Vending Machine — ESP32 Hardware Controller & Simulator

  =============================================================================

  Institution : University of Makati (UMak) - College of Computing and Information Sciences

  Project     : PenPoint: Smart Vending for Everyday School Essentials

  Group       : SkiDB (IV-CINS)



  OFFICIAL HARDWARE PIN CONFIGURATION:

  -----------------------------------------------------------------------------

  TB6612 #1 (Dual Motor Driver 1):

    - Motor 1 (Slot 1): PWMA = GPIO 25, AIN1 = GPIO 26, AIN2 = GPIO 27

    - Motor 2 (Slot 2): PWMB = GPIO 14, BIN1 = GPIO 16, BIN2 = GPIO 17

  TB6612 #2 (Dual Motor Driver 2):

    - Motor 3 (Slot 3): PWMA = GPIO 4,  AIN1 = GPIO 5,  AIN2 = GPIO 15

  Both TB6612 Modules:

    - Standby (STBY)  = GPIO 33

  Optical IR Break-Beam Sensors:

    - IR Receiver 1   = GPIO 32 (OUT - Active LOW)

    - IR Receiver 2   = GPIO 23 (OUT - Active LOW)

  Servo Motors (5 Servos):

    - Servo 1 (Slot 1): Signal = GPIO 13

    - Servo 2 (Slot 2): Signal = GPIO 18

    - Servo 3 (Slot 3): Signal = GPIO 19

    - Servo 4 (Slot 4): Signal = GPIO 21

    - Servo 5 (Slot 5): Signal = GPIO 22

  -----------------------------------------------------------------------------

*/



#include <WiFi.h>

#include <WebServer.h>

#include <HTTPClient.h>

#include <Preferences.h>

#include <ESP32Servo.h>



// ---------- WiFi Credentials ----------

const char* DEFAULT_WIFI_SSID = "GFiber_AD06B";

const char* DEFAULT_WIFI_PASS = "vn9SUmx7";



// ---------- Central Server URL ----------

// Automatically pointed to this PC's actual LAN IP on Wi-Fi (192.168.254.121)

String serverUrl = "http://192.168.254.121:8000";

String machineId = "PEN-01";

bool autoPollEnabled = true;



// Operating Mode:

// hardwareMode = true:  100% REAL HARDWARE (Real motors, real servos, strict physical IR beam break requirement, real physical reverse-retry).

// hardwareMode = false: BENCH SIMULATION (Pulses physical pins but auto-confirms drop after 1s for desk testing without physical items).

bool hardwareMode = true;

bool simulateJamMode = false; // When true, simulates drop sensor timeout & refund flow



Preferences prefs;

WebServer server(80);



unsigned long lastPollTime = 0;

const unsigned long POLL_INTERVAL_MS = 1200;



// =============================================================================

// GPIO PIN DEFINITIONS

// =============================================================================



// TB6612 #1 (Slot 1 & Slot 2)

#define MOTOR1_PWMA 25

#define MOTOR1_AIN1 26

#define MOTOR1_AIN2 27



#define MOTOR2_PWMB 14

#define MOTOR2_BIN1 16

#define MOTOR2_BIN2 17



// TB6612 #2 (Slot 3)

#define MOTOR3_PWMA 4

#define MOTOR3_AIN1 5

#define MOTOR3_AIN2 15



// Both TB6612 Modules Standby

#define TB6612_STBY 33



// Optical IR Break-Beam Sensors

#define IR_RECEIVER_1 32

#define IR_RECEIVER_2 23



// Servo Motors (6 Servos - Dual-Gate Escapement System for 3 Top Sloped Slots)

#define NUM_SERVOS 6

#define SERVO_1_PIN 13 // Slot 1 Front Gate (Drops foremost pen)

#define SERVO_2_PIN 18 // Slot 1 Rear Separator (Holds back sloped queue)

#define SERVO_3_PIN 19 // Slot 2 Front Gate (Drops foremost pen)

#define SERVO_4_PIN 21 // Slot 2 Rear Separator (Holds back sloped queue)

#define SERVO_5_PIN 22 // Slot 3 Front Gate (Drops foremost pen)

#define SERVO_6_PIN 2  // Slot 3 Rear Separator (Holds back sloped queue)



const int SERVO_PINS[NUM_SERVOS] = {

  SERVO_1_PIN,

  SERVO_2_PIN,

  SERVO_3_PIN,

  SERVO_4_PIN,

  SERVO_5_PIN,

  SERVO_6_PIN

};



Servo servos[NUM_SERVOS];

int servoAngle[NUM_SERVOS] = {90, 90, 90, 90, 90, 90};



// Telemetry State

bool motorRunning[3] = {false, false, false};

bool virtualIrBeamBroken = false;

String lastAction = "System Online — 100% Real Hardware Pins Initialized";



// In-Memory Realtime Log Buffer for Live Web Terminal Streaming

String systemLogs = "ESP32 Vending Controller Booted... Ready.\nMode: REAL PHYSICAL HARDWARE (Strict Sensors & Motors Active)\n";



void appendLog(const String &msg) {

  Serial.println(msg);

  systemLogs += msg + "\n";

  if (systemLogs.length() > 6000) {

    systemLogs = systemLogs.substring(systemLogs.length() - 3000);

  }

}



void setCorsHeaders() {

  server.sendHeader("Access-Control-Allow-Origin", "*");

  server.sendHeader("Access-Control-Allow-Methods", "GET, POST, OPTIONS");

  server.sendHeader("Access-Control-Allow-Headers", "*");

}



// ---------- Helper: Extract JSON value without external libraries ----------

String extractJsonValue(const String& json, const String& key) {

  int keyPos = json.indexOf("\"" + key + "\":");

  if (keyPos == -1) return "";

  int start = keyPos + key.length() + 3;

  while (start < json.length() && (json[start] == ' ' || json[start] == '\"')) start++;

  int end = start;

  while (end < json.length() && json[end] != '\"' && json[end] != ',' && json[end] != '}' && json[end] != '\r' && json[end] != '\n') end++;

  return json.substring(start, end);

}



// =============================================================================

// REAL HARDWARE ACTUATION FUNCTIONS

// =============================================================================



// Real Physical Motor Control: TB6612FNG Dual Driver (PWM speed & Direction)

void setMotor(int motorNum, int speed) {

  digitalWrite(TB6612_STBY, HIGH);

  speed = constrain(speed, -255, 255);



  if (motorNum == 1) {

    digitalWrite(MOTOR1_AIN1, speed > 0 ? HIGH : LOW);

    digitalWrite(MOTOR1_AIN2, speed < 0 ? HIGH : LOW);

    digitalWrite(MOTOR1_PWMA, speed != 0 ? HIGH : LOW);

    motorRunning[0] = (speed != 0);

  } else if (motorNum == 2) {

    digitalWrite(MOTOR2_BIN1, speed > 0 ? HIGH : LOW);

    digitalWrite(MOTOR2_BIN2, speed < 0 ? HIGH : LOW);

    digitalWrite(MOTOR2_PWMB, speed != 0 ? HIGH : LOW);

    motorRunning[1] = (speed != 0);

  } else if (motorNum == 3) {

    digitalWrite(MOTOR3_AIN1, speed > 0 ? HIGH : LOW);

    digitalWrite(MOTOR3_AIN2, speed < 0 ? HIGH : LOW);

    digitalWrite(MOTOR3_PWMA, speed != 0 ? HIGH : LOW);

    motorRunning[2] = (speed != 0);

  }

}



void stopAllMotors() {

  digitalWrite(MOTOR1_PWMA, LOW);

  digitalWrite(MOTOR1_AIN1, LOW);

  digitalWrite(MOTOR1_AIN2, LOW);



  digitalWrite(MOTOR2_PWMB, LOW);

  digitalWrite(MOTOR2_BIN1, LOW);

  digitalWrite(MOTOR2_BIN2, LOW);



  digitalWrite(MOTOR3_PWMA, LOW);

  digitalWrite(MOTOR3_AIN1, LOW);

  digitalWrite(MOTOR3_AIN2, LOW);



  digitalWrite(TB6612_STBY, LOW);

  motorRunning[0] = false;

  motorRunning[1] = false;

  motorRunning[2] = false;

}



// Real Physical Servo Control: 5x Servos (Attach-On-Demand with Auto-Detach Protection)

void servoWrite(int idx, int angle) {

  if (idx < 0 || idx >= NUM_SERVOS) return;

  angle = constrain(angle, 0, 180);

  servoAngle[idx] = angle;

  if (!servos[idx].attached()) {

    servos[idx].setPeriodHertz(50);

    servos[idx].attach(SERVO_PINS[idx], 500, 2400);

  }

  servos[idx].write(angle);

}



void servoDetach(int idx) {

  if (idx < 0 || idx >= NUM_SERVOS) return;

  servos[idx].detach();

  pinMode(SERVO_PINS[idx], OUTPUT);

  digitalWrite(SERVO_PINS[idx], LOW);

}



void servoPulse(int idx, int angle, int durationMs = 1100) {

  if (idx < 0 || idx >= NUM_SERVOS) return;

  servoWrite(idx, angle);

  delay(durationMs);

  servoWrite(idx, 90);

  delay(350);

  servoDetach(idx); // Releases PWM pulse so continuous/positional servos stop spinning & save power!

}



// Single IR Test Mode: Only GPIO 32 is actively monitored (Active-LOW when beam is interrupted)

bool checkIrBeamBroken(int sensorIdx = 0) {

  // Evaluates exclusively GPIO 32 for IR break-beam testing

  bool ir1 = (digitalRead(IR_RECEIVER_1) == LOW);

  return (ir1 || virtualIrBeamBroken);

}



// =============================================================================

// DUAL-SERVO ESCAPEMENT MECHANISM (Top Sloped Tray - Slots 1, 2, 3)

// =============================================================================

// Flow:

// 1. Rear Separator Servo blocks/clamps pen #2 so only one pen can fall.

// 2. Front Gate Servo opens to drop the foremost pen (#1) into the chute.

// 3. Front Gate Servo closes to seal the front pocket.

// 4. Rear Separator Servo retracts, allowing pen #2 to slide down by gravity

//    into the front pocket, ready for the next order.

// 5. Both servos auto-detach to prevent continuous spinning & brownout.

void dispenseEscapementSlot(int slot) {

  int frontIdx = 0;

  int rearIdx = 1;



  if (slot == 1) {

    frontIdx = 0; rearIdx = 1; // Slot 1: S1 Front (GPIO 13), S2 Rear (GPIO 18)

  } else if (slot == 2) {

    frontIdx = 2; rearIdx = 3; // Slot 2: S3 Front (GPIO 19), S4 Rear (GPIO 21)

  } else if (slot == 3) {

    frontIdx = 4; rearIdx = 5; // Slot 3: S5 Front (GPIO 22), S6 Rear (GPIO 2)

  } else {

    return;

  }



  appendLog("    [ESCAPEMENT SLOT #" + String(slot) + "] Starting Dual-Servo Escapement Sequence...");



  // Phase 1: Lock the queue! Rear Separator blocks remaining pens

  appendLog("    [REAR SEPARATOR S" + String(rearIdx + 1) + " (GPIO " + String(SERVO_PINS[rearIdx]) + ")] Clamping queue (Holding pen #2)...");

  servoWrite(rearIdx, 180);

  delay(350);



  // Phase 2: Open Front Gate to drop pen #1

  appendLog("    [FRONT GATE S" + String(frontIdx + 1) + " (GPIO " + String(SERVO_PINS[frontIdx]) + ")] Opening gate -> Dropping foremost pen...");

  servoWrite(frontIdx, 180);

  delay(800); // Pen drops through chute



  // Phase 3: Close Front Gate

  appendLog("    [FRONT GATE S" + String(frontIdx + 1) + "] Closing gate (90°)...");

  servoWrite(frontIdx, 90);

  delay(350);

  servoDetach(frontIdx); // Stop PWM on front servo



  // Phase 4: Retract Rear Separator so pen #2 rolls forward into front chamber

  appendLog("    [REAR SEPARATOR S" + String(rearIdx + 1) + "] Retracting (90°) -> Next pen rolls into front chamber...");

  servoWrite(rearIdx, 90);

  delay(450); // Settle pen against closed front gate

  servoDetach(rearIdx); // Stop PWM on rear servo



  appendLog("    [ESCAPEMENT SLOT #" + String(slot) + "] Escapement cycle complete!");

}



// Real Physical Reverse-Retry Oscillation (UMak Chapter 3 Anti-Jam Spec)

void reverseRetryMotor(int slot) {

  appendLog("    [ANTI-JAM RETRY] Reversing physical actuator to dislodge obstruction...");

  if (slot == 1 || slot == 2 || slot == 3) {

    int frontIdx = (slot - 1) * 2;

    int rearIdx = frontIdx + 1;

    appendLog("    [ANTI-JAM RETRY] Oscillating Front & Rear Servos for Slot #" + String(slot) + "...");

    servoWrite(frontIdx, 180);

    servoWrite(rearIdx, 90);

    delay(400);

    servoWrite(frontIdx, 0);

    servoWrite(rearIdx, 180);

    delay(400);

    servoWrite(frontIdx, 90);

    servoWrite(rearIdx, 90);

    delay(300);

    servoDetach(frontIdx);

    servoDetach(rearIdx);

  } else if (slot == 5) {

    setMotor(1, -255); delay(700); setMotor(1, 255); delay(1000); setMotor(1, 0);

  } else if (slot == 6) {

    setMotor(2, -255); delay(700); setMotor(2, 255); delay(1000); setMotor(2, 0);

  } else {

    setMotor(3, -255); delay(700); setMotor(3, 255); delay(1000); setMotor(3, 0);

  }

}



// =============================================================================

// MAIN DISPENSE CYCLE (Physical Hardware Execution)

// =============================================================================

bool executeDispense(int slot, int qty, String mech, String cmdId, String prodName = "") {

  String itemDesc = prodName.length() > 0 ? prodName : ("Slot #" + String(slot));

  appendLog("-------------------------------------------------------------");

  appendLog(">>> [ORDER DISPATCH] Slot #" + String(slot) + " (" + itemDesc + ") x" + String(qty));

  appendLog("    Command: " + cmdId + " | Mechanism: " + mech);

  appendLog("    Mode: " + String(hardwareMode ? "REAL PHYSICAL HARDWARE" : "BENCH SIMULATION"));

  appendLog("-------------------------------------------------------------");



  lastAction = "Dispensing Slot #" + String(slot) + " (" + itemDesc + ")";



  // 1. Notify central server that dispense started

  if (WiFi.status() == WL_CONNECTED && cmdId.length() > 0) {

    HTTPClient http;

    String url = serverUrl + "/api/esp32.php?action=start_dispense&command_id=" + cmdId + "&machine_id=" + machineId;

    http.begin(url);

    http.GET();

    http.end();

    appendLog("    [CENTRAL API] Status set to DISPENSING.");

  }



  bool dropVerified = false;



  for (int q = 0; q < qty; q++) {

    // Actuate physical escapement servos or paper rollers according to slot:

    if (slot == 1 || slot == 2 || slot == 3) {

      // Top Tier: Dual-Servo Escapement Flow (Pens & Pencils on sloped tray)

      dispenseEscapementSlot(slot);

    } else if (slot == 4) {

      // Auxiliary item / Blue Book (Motor 3 or Servo)

      appendLog("    [SLOT 4] Actuating Auxiliary Slot 4 Mechanism...");

      setMotor(3, 255);

      delay(1200);

      setMotor(3, 0);

    } else if (slot == 5) {

      // Paper Slot 5 (Short Bond Paper) -> Motor 1 (GPIO 25)

      appendLog("    [TB6612 Motor] Slot 5 Paper Roller ON (STBY=33, PWM=25)...");

      setMotor(1, 255);

      delay(1400);

      setMotor(1, 0);

      appendLog("    [TB6612 Motor] Slot 5 Paper Roller OFF.");

    } else if (slot == 6) {

      // Paper Slot 6 (A4 Bond Paper) -> Motor 2 (GPIO 14)

      appendLog("    [TB6612 Motor] Slot 6 Paper Roller ON (STBY=33, PWM=14)...");

      setMotor(2, 255);

      delay(1400);

      setMotor(2, 0);

      appendLog("    [TB6612 Motor] Slot 6 Paper Roller OFF.");

    } else {

      setMotor(1, 255);

      delay(1000);

      setMotor(1, 0);

    }



    // 2. Optical IR Break-Beam Sensor Drop Verification (Single-Sensor Mode: GPIO 23)

    appendLog("    [OPTICAL IR SENSOR] Monitoring physical beam on GPIO 32...");

    unsigned long waitStart = millis();

    bool beamBroken = false;



    // Monitor for physical beam break for up to 2800ms

    while (millis() - waitStart < 2800) {

      if (checkIrBeamBroken(0)) {

        beamBroken = true;

        break;

      }

      delay(15);

    }



    // If in Real Hardware Mode and drop was NOT confirmed, execute physical Reverse-Retry!

    if (!beamBroken && hardwareMode) {

      appendLog("    [OPTICAL IR SENSOR] Beam not broken after 2.8s. Initiating physical anti-jam retry...");

      reverseRetryMotor(slot);



      // Second check for physical drop after retry

      waitStart = millis();

      while (millis() - waitStart < 1600) {

        if (checkIrBeamBroken(0)) {

          beamBroken = true;

          break;

        }

        delay(15);

      }

    } else if (!beamBroken && !hardwareMode && !simulateJamMode) {

      // Bench Simulation Fallback (Only active when hardwareMode is toggled OFF)

      virtualIrBeamBroken = true;

      beamBroken = true;

      delay(600);

      virtualIrBeamBroken = false;

    }



    if (beamBroken && !simulateJamMode) {

      dropVerified = true;

      appendLog("    [OPTICAL IR SENSOR] *** PHYSICAL BEAM BROKEN! Drop verified (PASS 🟢) ***");

      delay(300);

    } else {

      dropVerified = false;

      appendLog("    [OPTICAL IR SENSOR] SENSOR TIMEOUT! Physical drop not confirmed on GPIO 32.");

      appendLog("    [TRANSACTION] Initiating student refund queue: REFUND_PENDING (FAIL 🔴).");

    }

  }



  // 3. Report completion callback to PenPoint central server

  if (WiFi.status() == WL_CONNECTED && cmdId.length() > 0) {

    HTTPClient http;

    String url = serverUrl + "/api/esp32.php?action=complete_dispense&command_id=" + cmdId + 

                 "&ir_verified=" + (dropVerified ? "1" : "0") + 

                 "&machine_id=" + machineId;

    http.begin(url);

    int code = http.GET();

    http.end();

    appendLog("    [CENTRAL API] Complete report delivered (HTTP " + String(code) + ").");

  }



  lastAction = dropVerified 

    ? ("Slot #" + String(slot) + " Dispensed & Verified!") 

    : ("Slot #" + String(slot) + " Sensor Timeout (Refund Queued)");

  appendLog("[STATUS 🟢] " + lastAction);

  return dropVerified;

}



// ---------- Polling Server Loop ----------

void pollServer() {

  if (!autoPollEnabled || WiFi.status() != WL_CONNECTED) return;



  HTTPClient http;

  String url = serverUrl + "/api/esp32.php?action=poll&machine_id=" + machineId;

  http.begin(url);

  int httpCode = http.GET();



  if (httpCode == 200) {

    String payload = http.getString();

    if (payload.indexOf("\"has_command\":true") != -1) {

      String cmdId = extractJsonValue(payload, "command_id");

      int slot = extractJsonValue(payload, "slot").toInt();

      int qty = extractJsonValue(payload, "quantity").toInt();

      if (qty < 1) qty = 1;

      String mech = extractJsonValue(payload, "mechanism");

      String prod = extractJsonValue(payload, "product_name");



      appendLog("\n>>> [QUEUE DETECTED] Processing command " + cmdId + " for Slot #" + String(slot));

      executeDispense(slot, qty, mech, cmdId, prod);

    }

  }

  http.end();

}



// =============================================================================

// BUILT-IN DIAGNOSTIC WEB INTERFACE (Port 80)

// =============================================================================

const char INDEX_HTML[] PROGMEM = R"rawliteral(

<!DOCTYPE html>

<html>

<head>

<meta charset="utf-8">

<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">

<title>PenPoint ESP32 Hardware Node</title>

<style>

  :root { color-scheme: dark; --bg:#0c0e14; --card:#151821; --border:#283145; --accent:#3b82f6; --green:#22c55e; --red:#ef4444; --amber:#f59e0b; }

  * { box-sizing:border-box; margin:0; padding:0; font-family:system-ui,-apple-system,sans-serif; }

  body { background:var(--bg); color:#f1f5f9; padding:16px; display:flex; flex-direction:column; align-items:center; }

  .wrap { width:100%; max-width:580px; display:flex; flex-direction:column; gap:14px; }

  .header { display:flex; align-items:center; justify-content:space-between; padding-bottom:10px; border-bottom:1px solid var(--border); }

  h1 { font-size:1.15rem; font-weight:700; color:#fff; display:flex; align-items:center; gap:8px; }

  .tag { background:rgba(34,197,94,0.18); color:#4ade80; border:1px solid rgba(34,197,94,0.35); font-size:0.7rem; padding:2px 8px; border-radius:99px; font-weight:700; }

  .card { background:var(--card); border:1px solid var(--border); border-radius:12px; padding:14px; display:flex; flex-direction:column; gap:10px; box-shadow:0 4px 12px rgba(0,0,0,0.4); }

  .card-title { font-size:0.85rem; font-weight:700; text-transform:uppercase; letter-spacing:0.04em; color:#94a3b8; display:flex; align-items:center; justify-content:space-between; }

  .btn-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px; }

  .btn-grid-3 { display:grid; grid-template-columns:repeat(3,1fr); gap:6px; }

  button { background:#252d3d; color:#f1f5f9; border:1px solid var(--border); border-radius:8px; padding:10px 8px; font-size:0.82rem; font-weight:600; cursor:pointer; transition:all 0.15s ease; text-align:center; }

  button:active { transform:scale(0.98); background:var(--accent); }

  .btn-primary { background:#2563eb; border-color:#3b82f6; color:#fff; }

  .btn-success { background:#16a34a; border-color:#22c55e; color:#fff; }

  .btn-warning { background:#b45309; border-color:#f59e0b; color:#fff; }

  .row { display:flex; align-items:center; justify-content:space-between; font-size:0.8rem; }

  .mono { font-family:monospace; color:#93c5fd; }

  input[type=text] { background:#0f131c; border:1px solid var(--border); color:#fff; padding:8px 10px; border-radius:6px; font-size:0.82rem; width:100%; font-family:monospace; }

  .terminal { background:#090c12; border:1px solid #1e2638; border-radius:8px; padding:10px; font-family:monospace; font-size:0.75rem; color:#a7f3d0; height:200px; overflow-y:auto; line-height:1.5; white-space:pre-wrap; }

  .ir-box { padding:10px; border-radius:8px; text-align:center; font-family:monospace; font-weight:700; font-size:0.85rem; }

  .ir-clear { background:rgba(34,197,94,0.12); color:var(--green); border:1px solid rgba(34,197,94,0.3); }

  .ir-broken { background:rgba(239,68,68,0.22); color:var(--red); border:1px solid rgba(239,68,68,0.5); animation:pulse 0.4s infinite alternate; }

  @keyframes pulse { 0%{opacity:1;} 100%{opacity:0.4;} }

  .pin-table { width:100%; border-collapse:collapse; font-size:0.75rem; font-family:monospace; margin-top:4px; }

  .pin-table th { background:#1f293d; color:#94a3b8; text-align:left; padding:4px 8px; border-radius:4px; }

  .pin-table td { padding:4px 8px; border-bottom:1px solid #232b3d; }

  .pbadge { background:#1e293b; color:#38bdf8; padding:2px 6px; border-radius:4px; font-weight:700; }

  .mode-badge { font-weight:700; padding:4px 10px; border-radius:6px; font-size:0.75rem; }

</style>

</head>

<body>

<div class="wrap">

  <div class="header">

    <h1>ESP32 Vending Controller <span class="tag" id="hwTag">REAL HARDWARE</span></h1>

    <span style="font-size:0.72rem;color:#64748b;font-family:monospace" id="espIp">Port 80</span>

  </div>



  <!-- MODE SWITCH CARD -->

  <div class="card" style="border-color:#22c55e">

    <div class="card-title" style="color:#4ade80">Hardware Execution Mode</div>

    <div class="row">

      <span>Operating Mode:</span>

      <button id="hwModeBtn" onclick="toggleHwMode()" style="padding:5px 12px;font-size:0.78rem;background:#15803d;color:#fff;">

        Mode: REAL PHYSICAL HARDWARE

      </button>

    </div>

    <div style="font-size:0.72rem;color:#94a3b8;">

      • <strong>REAL HARDWARE</strong>: Pulses TB6612 motors & Servos. Strictly awaits physical item fall across optical sensors (GPIO 32/23). Reverses motor if jammed.<br>

      • <strong>BENCH SIMULATION</strong>: Auto-confirms drop after 1s for desk testing without physical items.

    </div>

  </div>



  <!-- PINOUT MATRIX CARD -->

  <div class="card">

    <div class="card-title">Active Physical GPIO Pin Connections</div>

    <table class="pin-table">

      <thead>

        <tr><th>COMPONENT / SLOT</th><th>FUNCTION</th><th>ESP32 GPIO</th></tr>

      </thead>

      <tbody>

        <tr><td>Slot 1 Front Gate (Drops foremost pen)</td><td>Signal</td><td><span class="pbadge">13</span></td></tr>

        <tr><td>Slot 1 Rear Separator (Holds queue)</td><td>Signal</td><td><span class="pbadge">18</span></td></tr>

        <tr><td>Slot 2 Front Gate (Drops foremost pen)</td><td>Signal</td><td><span class="pbadge">19</span></td></tr>

        <tr><td>Slot 2 Rear Separator (Holds queue)</td><td>Signal</td><td><span class="pbadge">21</span></td></tr>

        <tr><td>Slot 3 Front Gate (Drops foremost pen)</td><td>Signal</td><td><span class="pbadge">22</span></td></tr>

        <tr><td>Slot 3 Rear Separator (Holds queue)</td><td>Signal</td><td><span class="pbadge">2 (D2)</span></td></tr>

        <tr><td>TB6612 #1 (Slot 5 Paper Roller)</td><td>PWMA / AIN1 / AIN2</td><td><span class="pbadge">25 / 26 / 27</span></td></tr>

        <tr><td>TB6612 #1 (Slot 6 Paper Roller)</td><td>PWMB / BIN1 / BIN2</td><td><span class="pbadge">14 / 16 / 17</span></td></tr>

        <tr><td>TB6612 #2 (Slot 4 / Aux Roller)</td><td>PWMA / AIN1 / AIN2</td><td><span class="pbadge">4 / 5 / 15</span></td></tr>

        <tr><td>Both TB6612 Modules</td><td>STBY (Standby)</td><td><span class="pbadge">33</span></td></tr>

        <tr><td>Optical IR Receivers 1 & 2</td><td>OUT 1 / OUT 2</td><td><span class="pbadge">32 / 23</span></td></tr>

      </tbody>

    </table>

  </div>



  <!-- SERVER CONFIG CARD -->

  <div class="card">

    <div class="card-title">PenPoint Central Server Configuration</div>

    <div style="display:flex;gap:6px">

      <input type="text" id="srvUrl" placeholder="http://192.168.1.9:8000">

      <button onclick="saveServerUrl()" style="white-space:nowrap;padding:0 12px">Save URL</button>

    </div>

    <div class="row">

      <span style="color:#64748b">Machine ID:</span>

      <span class="mono" id="mId">PEN-01</span>

    </div>

    <div class="row">

      <span style="color:#64748b">Auto-Polling Kiosk Orders:</span>

      <button id="pollBtn" onclick="toggleAutoPoll()" style="padding:4px 10px;font-size:0.75rem">Auto-Poll: ACTIVE</button>

    </div>

    <div class="row">

      <span style="color:#64748b">Simulate Jam / Sensor Timeout:</span>

      <button id="jamBtn" onclick="toggleJam()" style="padding:4px 10px;font-size:0.75rem">Jam Mode: OFF</button>

    </div>

  </div>



  <!-- TEST SUITE -->

  <div class="card" style="border-color:#3b82f6">

    <div class="card-title" style="color:#60a5fa">Hardware & Communication Test Suite</div>

    <div class="btn-grid">

      <button class="btn-primary" onclick="testPing()">1. Test Ping / Heartbeat</button>

      <button class="btn-primary" onclick="testPoll()">2. Test Poll Dispense Queue</button>

      <button class="btn-warning" onclick="testInjectOrder(2)">3. Order & Dispense Slot 2</button>

      <button class="btn-success" onclick="testVerifyCallback()">4. Test IR Verify Callback</button>

    </div>

  </div>



  <!-- HARDWARE TELEMETRY -->

  <div class="card">

    <div class="card-title">Live Hardware Telemetry (Optical IR Sensor — GPIO 32 Test Mode)</div>

    <div class="ir-box ir-clear" id="irDisplay">OPTICAL IR BEAM (GPIO 32): INTACT (CLEAR 🟢) [Pin 32: HIGH]</div>



    <div style="font-size:0.74rem;color:#94a3b8;margin-top:4px">Trigger Physical Slot Actuation:</div>

    <div class="btn-grid-3">

      <button onclick="testInjectOrder(1)">Slot 1 (Pen 1)</button>

      <button onclick="testInjectOrder(2)">Slot 2 (Pen 2)</button>

      <button onclick="testInjectOrder(3)">Slot 3 (Pencil)</button>

      <button onclick="testInjectOrder(4)">Slot 4 (Blue Book)</button>

      <button onclick="testInjectOrder(5)">Slot 5 (Bond Paper)</button>

      <button onclick="testInjectOrder(6)">Slot 6 (A4 Paper)</button>

    </div>



    <div style="font-size:0.74rem;color:#10b981;margin-top:10px;font-weight:700">Dual-Servo Escapement Tests (Top Sloped Slots 1-3):</div>

    <div class="btn-grid-3">

      <button style="border-color:#10b981;color:#6ee7b7" onclick="testEscapement(1)">⚡ Slot 1 Escapement</button>

      <button style="border-color:#10b981;color:#6ee7b7" onclick="testEscapement(2)">⚡ Slot 2 Escapement</button>

      <button style="border-color:#10b981;color:#6ee7b7" onclick="testEscapement(3)">⚡ Slot 3 Escapement</button>

    </div>



    <div style="font-size:0.74rem;color:#38bdf8;margin-top:10px;font-weight:700">Direct Paper Roller Tests (TB6612 H-Bridge):</div>

    <div class="btn-grid-3">

      <button class="btn-warning" onclick="spinMotor(1)">Roller 1 (Slot 5)</button>

      <button class="btn-warning" onclick="spinMotor(2)">Roller 2 (Slot 6)</button>

      <button class="btn-warning" onclick="spinMotor(3)">Roller 3 (Slot 4)</button>

    </div>

    <button class="btn-primary" onclick="spinAllMotors()" style="margin-top:6px;width:100%">⚡ Test All Rollers (Forward 1.5s)</button>



    <div style="font-size:0.74rem;color:#a78bfa;margin-top:12px;font-weight:700">Individual Servo Jog Tests (Servos 1 to 6):</div>

    <div class="btn-grid-3">

      <button style="border-color:#8b5cf6;color:#c4b5fd" onclick="testServo(1)">S1 Front (13)</button>

      <button style="border-color:#8b5cf6;color:#c4b5fd" onclick="testServo(2)">S2 Rear (18)</button>

      <button style="border-color:#8b5cf6;color:#c4b5fd" onclick="testServo(3)">S3 Front (19)</button>

      <button style="border-color:#8b5cf6;color:#c4b5fd" onclick="testServo(4)">S4 Rear (21)</button>

      <button style="border-color:#8b5cf6;color:#c4b5fd" onclick="testServo(5)">S5 Front (22)</button>

      <button style="border-color:#8b5cf6;color:#c4b5fd" onclick="testServo(6)">S6 Rear (2)</button>

    </div>

    <button class="btn-primary" onclick="testAllServos()" style="margin-top:6px;width:100%">⚡ Sweep All 6 Servos Sequentially</button>

  </div>



  <!-- LIVE TERMINAL -->

  <div class="card">

    <div class="card-title">

      <span>Hardware Node Terminal (Live Stream)</span>

      <button onclick="clearTerm()" style="padding:2px 8px;font-size:0.7rem">Clear</button>

    </div>

    <div class="terminal" id="term">ESP32 Hardware Controller Initialized... Ready.\n</div>

  </div>

</div>



<script>

  const term = document.getElementById('term');

  const irDisplay = document.getElementById('irDisplay');

  const pollBtn = document.getElementById('pollBtn');

  const jamBtn = document.getElementById('jamBtn');

  const hwModeBtn = document.getElementById('hwModeBtn');

  const hwTag = document.getElementById('hwTag');



  const log = (msg) => {

    const d = new Date();

    const t = d.toTimeString().split(' ')[0] + '.' + String(d.getMilliseconds()).padStart(3,'0');

    term.textContent += '[' + t + '] ' + msg + '\n';

    term.scrollTop = term.scrollHeight;

  };

  const clearTerm = () => { term.textContent = ''; };



  // 1. Initial State Fetch

  fetch('/api/status').then(r=>r.json()).then(data=>{

    document.getElementById('srvUrl').value = data.server_url;

    document.getElementById('mId').textContent = data.machine_id;

    document.getElementById('espIp').textContent = data.ip;

    if (!data.auto_poll) {

      pollBtn.textContent = 'Auto-Poll: PAUSED';

      pollBtn.style.background = '#78350f';

    }

    if (data.jam_mode) {

      jamBtn.textContent = 'Jam Mode: ON (Failures simulated)';

      jamBtn.style.background = '#991b1b';

    }

    if (data.hardware_mode) {

      hwModeBtn.textContent = 'Mode: REAL PHYSICAL HARDWARE';

      hwModeBtn.style.background = '#15803d';

      hwTag.textContent = 'REAL HARDWARE';

      hwTag.style.borderColor = '#22c55e';

    } else {

      hwModeBtn.textContent = 'Mode: BENCH SIMULATION';

      hwModeBtn.style.background = '#b45309';

      hwTag.textContent = 'BENCH SIMULATION';

      hwTag.style.borderColor = '#f59e0b';

    }

    log('ESP32 Hardware Controller Online! IP: ' + data.ip + ' | Server: ' + data.server_url);

  }).catch(()=>{

    log('Initial status fetched.');

  });



  // Toggle Hardware Mode

  const toggleHwMode = async () => {

    try {

      const res = await fetch('/api/toggle_hw_mode');

      const data = await res.json();

      if (data.hardware_mode) {

        hwModeBtn.textContent = 'Mode: REAL PHYSICAL HARDWARE';

        hwModeBtn.style.background = '#15803d';

        hwTag.textContent = 'REAL HARDWARE';

        log('[MODE] Switched to 100% REAL HARDWARE mode (Strict physical optical drop verification active).');

      } else {

        hwModeBtn.textContent = 'Mode: BENCH SIMULATION';

        hwModeBtn.style.background = '#b45309';

        hwTag.textContent = 'BENCH SIMULATION';

        log('[MODE] Switched to BENCH SIMULATION mode (Drop auto-confirmed for testing without physical items).');

      }

    } catch(e) {}

  };



  // Stream Live Logs from ESP32 In-Memory Buffer

  setInterval(() => {

    fetch('/api/logs').then(r=>r.json()).then(data=>{

      if (data && data.logs) {

        if (term.textContent !== data.logs) {

          term.textContent = data.logs;

          term.scrollTop = term.scrollHeight;

        }

      }

    }).catch(()=>{});

  }, 600);



  // Poll IR Sensor State (Every 250ms)

  setInterval(()=>{

    fetch('/api/sensor').then(r=>r.json()).then(s=>{

      if (s.beam_broken) {

        irDisplay.textContent = 'OPTICAL IR BEAM (GPIO 32): BROKEN / OBJECT DETECTED 🔴 [Pin 32: LOW]';

        irDisplay.className = 'ir-box ir-broken';

      } else {

        irDisplay.textContent = 'OPTICAL IR BEAM (GPIO 32): INTACT (CLEAR 🟢) [Pin 32: HIGH]';

        irDisplay.className = 'ir-box ir-clear';

      }

    }).catch(()=>{});

  }, 250);



  // 3. API Test 1: Ping / Heartbeat

  const testPing = async () => {

    log('>>> [TEST 1] Sending Ping/Heartbeat to PenPoint Server...');

    try {

      const t0 = performance.now();

      const res = await fetch('/api/test_ping');

      const data = await res.json();

      const dt = Math.round(performance.now() - t0);

      if (data.http_code === 200) {

        log('[PASS] Ping OK! HTTP 200 (' + dt + 'ms) -> Server Time: ' + (data.server_time || 'OK'));

      } else {

        log('[FAIL] Server replied HTTP ' + data.http_code);

      }

    } catch(e) { log('[ERROR] Ping failed: ' + e); }

  };



  // 4. API Test 2: Poll Dispense Queue

  const testPoll = async () => {

    log('>>> [TEST 2] Polling PenPoint API for pending commands...');

    try {

      const res = await fetch('/api/test_poll');

      const data = await res.json();

      if (data.http_code === 200) {

        if (data.has_command) {

          log('[PASS] Command FOUND! ID: ' + data.command_id + ' | Slot #' + data.slot + ' (' + data.product_name + ' x' + data.quantity + ')');

        } else {

          log('[PASS] Polled successfully! Queue is currently EMPTY.');

        }

      } else {

        log('[FAIL] HTTP ' + data.http_code);

      }

    } catch(e) { log('[ERROR] Poll failed: ' + e); }

  };



  // 5. API Test 3: Inject Test Order & Dispense

  const testInjectOrder = async (slot) => {

    log('>>> [TEST 3] Triggering physical hardware dispense for Slot #' + slot + '...');

    try {

      const res = await fetch('/api/test_order?slot=' + slot);

      const data = await res.json();

      if (data.success) {

        log('[SUCCESS] Dispense finished! Slot #' + slot + ' Drop Verified = ' + (data.ir_verified ? 'YES (PASS 🟢)' : 'NO (TIMEOUT 🔴)'));

      } else {

        log('[FAILED] Dispense failed: ' + (data.error || 'Unknown error'));

      }

    } catch(e) { log('[ERROR] Dispense error: ' + e); }

  };



  // 6. API Test 4: IR Drop Verification Callback

  const testVerifyCallback = async () => {

    log('>>> [TEST 4] Testing IR Verification Callback to Website API...');

    try {

      const res = await fetch('/api/test_verify');

      const data = await res.json();

      if (data.http_code === 200) {

        log('[PASS] Callback delivered! Server recorded status = ' + data.status);

      } else {

        log('[FAIL] Callback error: HTTP ' + data.http_code);

      }

    } catch(e) { log('[ERROR] Callback failed: ' + e); }

  };



  // 7. Save Server URL

  const saveServerUrl = async () => {

    const url = document.getElementById('srvUrl').value.trim();

    if (!url) return alert('Enter a valid server URL');

    log('Updating server URL to: ' + url + '...');

    try {

      const res = await fetch('/api/set_server?url=' + encodeURIComponent(url));

      const data = await res.json();

      if (data.success) {

        log('[SAVED] Server URL updated and saved to Flash!');

        alert('Server URL saved to ESP32 Flash memory!');

      }

    } catch(e) { log('[ERROR] Save URL failed: ' + e); }

  };



  // 8. Toggle Auto-Polling

  const toggleAutoPoll = async () => {

    const res = await fetch('/api/toggle_poll');

    const data = await res.json();

    if (data.auto_poll) {

      pollBtn.textContent = 'Auto-Poll: ACTIVE';

      pollBtn.style.background = '#252d3d';

      log('[POLL] Background kiosk order polling resumed.');

    } else {

      pollBtn.textContent = 'Auto-Poll: PAUSED';

      pollBtn.style.background = '#78350f';

      log('[POLL] Background kiosk order polling paused.');

    }

  };



  // 9. Toggle Jam Simulation Mode

  const toggleJam = async () => {

    const res = await fetch('/api/toggle_jam');

    const data = await res.json();

    if (data.jam_mode) {

      jamBtn.textContent = 'Jam Mode: ON (Failures simulated)';

      jamBtn.style.background = '#991b1b';

      log('[JAM] Sensor timeout & refund flow simulation enabled.');

    } else {

      jamBtn.textContent = 'Jam Mode: OFF';

      jamBtn.style.background = '#252d3d';

      log('[JAM] Normal drop verification restored.');

    }

  };



  // 10. Direct Wheel Spin Tests

  const spinMotor = async (m) => {

    log('>>> [WHEEL TEST] Spinning Motor #' + m + ' (1.5s)...');

    try {

      const res = await fetch('/api/spin_motor?m=' + m + '&ms=1500&dir=1');

      const data = await res.json();

      if (data.success) log('[WHEEL TEST] Motor #' + m + ' spin command completed.');

    } catch(e) { log('[ERROR] Motor test error: ' + e); }

  };



  const spinAllMotors = async () => {

    log('>>> [WHEEL TEST] Spinning Wheels 1, 2, 3 sequentially...');

    for (let m = 1; m <= 3; m++) {

      await spinMotor(m);

      await new Promise(r => setTimeout(r, 600));

    }

  };



  // 11. Dual-Servo Escapement Tests

  const testEscapement = async (slot) => {

    log('>>> [ESCAPEMENT TEST] Testing Slot #' + slot + ' Dual-Servo Sequence...');

    try {

      const res = await fetch('/api/test_escapement?slot=' + slot);

      const data = await res.json();

      if (data.success) log('[ESCAPEMENT TEST] Slot #' + slot + ' single-pen drop sequence completed.');

    } catch(e) { log('[ERROR] Escapement test error: ' + e); }

  };



  // 12. Direct Servo Tests

  const testServo = async (s) => {

    log('>>> [SERVO TEST] Actuating Servo #' + s + ' (1.1s pulse)...');

    try {

      const res = await fetch('/api/test_servo?s=' + s + '&angle=180&ms=1100');

      const data = await res.json();

      if (data.success) log('[SERVO TEST] Servo #' + s + ' (GPIO ' + data.pin + ') test finished.');

    } catch(e) { log('[ERROR] Servo test error: ' + e); }

  };



  const testAllServos = async () => {

    log('>>> [SERVO TEST] Sweeping Servos 1 through 6 sequentially...');

    for (let s = 1; s <= 6; s++) {

      await testServo(s);

      await new Promise(r => setTimeout(r, 600));

    }

    log('[SERVO TEST] All 6 servos swept successfully.');

  };

</script>

</body>

</html>

)rawliteral";



// =============================================================================

// WEBSERVER ROUTE HANDLERS

// =============================================================================



void handleRoot() { 

  server.send_P(200, "text/html", INDEX_HTML); 

}



void handleStatus() {

  setCorsHeaders();

  String out = "{";

  out += "\"ip\":\"" + WiFi.localIP().toString() + "\",";

  out += "\"server_url\":\"" + serverUrl + "\",";

  out += "\"machine_id\":\"" + machineId + "\",";

  out += "\"hardware_mode\":" + String(hardwareMode ? "true" : "false") + ",";

  out += "\"auto_poll\":" + String(autoPollEnabled ? "true" : "false") + ",";

  out += "\"jam_mode\":" + String(simulateJamMode ? "true" : "false") + ",";

  out += "\"ir_beam_broken\":" + String(checkIrBeamBroken(0) ? "true" : "false") + ",";

  out += "\"ir1_broken\":" + String((digitalRead(IR_RECEIVER_1) == LOW) ? "true" : "false") + ",";

  out += "\"ir2_broken\":" + String((digitalRead(IR_RECEIVER_1) == LOW) ? "true" : "false") + ",";

  out += "\"last_action\":\"" + lastAction + "\"";

  out += "}";

  server.send(200, "application/json", out);

}



void handleSensor() {

  setCorsHeaders();

  bool b = checkIrBeamBroken(0);

  String out = "{";

  out += "\"beam_broken\":" + String(b ? "true" : "false") + ",";

  out += "\"ir2\":" + String((digitalRead(IR_RECEIVER_1) == LOW) ? "true" : "false") + ",";

  out += "\"pin23_raw\":" + String(digitalRead(IR_RECEIVER_1));

  out += "}";

  server.send(200, "application/json", out);

}



// Toggle Hardware Mode (Real vs Bench Simulation)

void handleToggleHwMode() {

  setCorsHeaders();

  hardwareMode = !hardwareMode;

  server.send(200, "application/json", "{\"hardware_mode\":" + String(hardwareMode ? "true" : "false") + "}");

}



// Direct HTTP Dispense Endpoint for Website Kiosk

void handleDirectDispense() {

  setCorsHeaders();

  if (server.method() == HTTP_OPTIONS) {

    server.send(204);

    return;

  }



  int slot = server.arg("slot").toInt();

  if (slot < 1 || slot > 6) slot = 2;

  int qty = server.arg("qty").toInt();

  if (qty < 1) qty = 1;

  String cmdId = server.arg("cmd_id");

  if (cmdId.length() == 0) cmdId = server.arg("command_id");

  if (cmdId.length() == 0) cmdId = "CMD-DIR-" + String(millis());

  String mech = server.arg("mech");

  if (mech.length() == 0) mech = (slot >= 5 ? "dc_roller" : "servo");

  String prod = server.arg("prod");

  if (prod.length() == 0) prod = server.arg("product_name");

  if (prod.length() == 0) prod = "Slot #" + String(slot);



  appendLog("\n>>> [DIRECT VEND] Incoming dispense order from Website Kiosk!");

  bool verified = executeDispense(slot, qty, mech, cmdId, prod);



  String out = "{";

  out += "\"success\":true,";

  out += "\"slot\":" + String(slot) + ",";

  out += "\"command_id\":\"" + cmdId + "\",";

  out += "\"product_name\":\"" + prod + "\",";

  out += "\"ir_verified\":" + String(verified ? "true" : "false");

  out += "}";

  server.send(200, "application/json", out);

}



// Live Stream In-Memory Logs Endpoint

void handleLogs() {

  setCorsHeaders();

  if (server.method() == HTTP_OPTIONS) {

    server.send(204);

    return;

  }

  String escaped = "";

  for (unsigned int i = 0; i < systemLogs.length(); i++) {

    char c = systemLogs[i];

    if (c == '"') escaped += "\\\"";

    else if (c == '\\') escaped += "\\\\";

    else if (c == '\n') escaped += "\\n";

    else if (c == '\r') continue;

    else if (c == '\t') escaped += "\\t";

    else escaped += c;

  }

  String out = "{\"logs\":\"" + escaped + "\"}";

  server.send(200, "application/json", out);

}



// 1. Test Ping Endpoint

void handleTestPing() {

  setCorsHeaders();

  if (WiFi.status() != WL_CONNECTED) {

    server.send(200, "application/json", "{\"http_code\":0,\"error\":\"WiFi disconnected\"}");

    return;

  }



  HTTPClient http;

  String url = serverUrl + "/api/esp32.php?action=heartbeat&machine_id=" + machineId + 

               "&ip_address=" + WiFi.localIP().toString() + 

               "&rssi=" + String(WiFi.RSSI());

  http.begin(url);

  int httpCode = http.GET();

  String payload = http.getString();

  http.end();



  String sTime = extractJsonValue(payload, "server_time");

  String out = "{";

  out += "\"http_code\":" + String(httpCode) + ",";

  out += "\"server_time\":\"" + sTime + "\",";

  out += "\"raw_response\":";

  out += (httpCode == 200 ? "\"OK\"" : "\"ERROR\"");

  out += "}";

  server.send(200, "application/json", out);

}



// 2. Test Poll Endpoint

void handleTestPoll() {

  setCorsHeaders();

  if (WiFi.status() != WL_CONNECTED) {

    server.send(200, "application/json", "{\"http_code\":0,\"error\":\"WiFi disconnected\"}");

    return;

  }



  HTTPClient http;

  String url = serverUrl + "/api/esp32.php?action=poll&machine_id=" + machineId;

  http.begin(url);

  int httpCode = http.GET();

  String payload = http.getString();

  http.end();



  bool hasCmd = (payload.indexOf("\"has_command\":true") != -1);

  String cmdId = extractJsonValue(payload, "command_id");

  String slot = extractJsonValue(payload, "slot");

  String prod = extractJsonValue(payload, "product_name");

  String qty = extractJsonValue(payload, "quantity");



  String out = "{";

  out += "\"http_code\":" + String(httpCode) + ",";

  out += "\"has_command\":" + String(hasCmd ? "true" : "false") + ",";

  out += "\"command_id\":\"" + cmdId + "\",";

  out += "\"slot\":\"" + slot + "\",";

  out += "\"product_name\":\"" + prod + "\",";

  out += "\"quantity\":\"" + qty + "\"";

  out += "}";

  server.send(200, "application/json", out);

}



// 3. Test Order & Dispense

void handleTestOrder() {

  setCorsHeaders();

  int slot = server.arg("slot").toInt();

  if (slot < 1 || slot > 6) slot = 2;



  // Ask server to inject a test order

  HTTPClient http;

  String url = serverUrl + "/api/esp32.php?action=trigger_test&slot=" + String(slot) + "&machine_id=" + machineId;

  http.begin(url);

  int code = http.GET();

  String payload = http.getString();

  http.end();



  String cmdId = extractJsonValue(payload, "command_id");

  String mech = extractJsonValue(payload, "mechanism");

  String prod = extractJsonValue(payload, "product_name");

  if (cmdId.length() == 0) cmdId = "CMD-SIM-TEST-" + String(millis());



  bool verified = executeDispense(slot, 1, mech.length() > 0 ? mech : "servo", cmdId, prod);



  String out = "{";

  out += "\"success\":true,";

  out += "\"slot\":" + String(slot) + ",";

  out += "\"command_id\":\"" + cmdId + "\",";

  out += "\"ir_verified\":" + String(verified ? "true" : "false");

  out += "}";

  server.send(200, "application/json", out);

}



// 4. Test Verification Callback

void handleTestVerify() {

  setCorsHeaders();

  if (WiFi.status() != WL_CONNECTED) {

    server.send(200, "application/json", "{\"http_code\":0}");

    return;

  }



  String testCmdId = "CMD-TEST-CB-" + String(millis());

  HTTPClient http;

  String url = serverUrl + "/api/esp32.php?action=complete_dispense&command_id=" + testCmdId + 

               "&ir_verified=1&details=Simulation+Test+Button&machine_id=" + machineId;

  http.begin(url);

  int httpCode = http.GET();

  String payload = http.getString();

  http.end();



  String status = extractJsonValue(payload, "status");

  String out = "{";

  out += "\"http_code\":" + String(httpCode) + ",";

  out += "\"status\":\"" + status + "\"";

  out += "}";

  server.send(200, "application/json", out);

}



// 5. Update Server URL

void handleSetServer() {

  setCorsHeaders();

  String newUrl = server.arg("url");

  if (newUrl.length() > 0) {

    serverUrl = newUrl;

    prefs.begin("penpoint", false);

    prefs.putString("server_url", serverUrl);

    prefs.end();

    server.send(200, "application/json", "{\"success\":true,\"server_url\":\"" + serverUrl + "\"}");

  } else {

    server.send(400, "application/json", "{\"success\":false,\"error\":\"Empty URL\"}");

  }

}



// 6. Toggle Auto Polling

void handleTogglePoll() {

  setCorsHeaders();

  autoPollEnabled = !autoPollEnabled;

  server.send(200, "application/json", "{\"auto_poll\":" + String(autoPollEnabled ? "true" : "false") + "}");

}



// 7. Toggle Jam Simulation Mode

void handleToggleJam() {

  setCorsHeaders();

  simulateJamMode = !simulateJamMode;

  server.send(200, "application/json", "{\"jam_mode\":" + String(simulateJamMode ? "true" : "false") + "}");

}



// 8. Direct Wheel / Motor Spin Test Endpoint

void handleSpinMotor() {

  setCorsHeaders();

  int m = server.arg("m").toInt();

  if (m < 1 || m > 3) m = 1;

  int ms = server.arg("ms").toInt();

  if (ms < 100 || ms > 10000) ms = 1500;

  int dir = server.arg("dir").toInt();

  if (dir == 0) dir = 1;



  appendLog("\n>>> [MANUAL WHEEL TEST] Driving Motor #" + String(m) + " for " + String(ms) + "ms (Dir: " + String(dir) + ")...");

  setMotor(m, dir > 0 ? 255 : -255);

  delay(ms);

  setMotor(m, 0);

  stopAllMotors();

  appendLog("    [MANUAL WHEEL TEST] Motor #" + String(m) + " Stopped.");



  server.send(200, "application/json", "{\"success\":true,\"motor\":" + String(m) + ",\"duration_ms\":" + String(ms) + "}");

}



// 9. Direct Servo Actuation Test Endpoint

void handleTestServo() {

  setCorsHeaders();

  int s = server.arg("s").toInt();

  if (s < 1 || s > NUM_SERVOS) s = 1;

  int angle = server.arg("angle").toInt();

  if (angle <= 0 || angle > 180) angle = 180;

  int ms = server.arg("ms").toInt();

  if (ms < 200 || ms > 5000) ms = 1100;



  int idx = s - 1;

  appendLog("\n>>> [MANUAL SERVO TEST] Driving Servo #" + String(s) + " (GPIO " + String(SERVO_PINS[idx]) + ") to " + String(angle) + "° (" + String(ms) + "ms)...");

  servoPulse(idx, angle, ms);

  appendLog("    [MANUAL SERVO TEST] Servo #" + String(s) + " returned to 90° & detached.");



  String out = "{\"success\":true,\"servo\":" + String(s) + ",\"pin\":" + String(SERVO_PINS[idx]) + ",\"angle\":" + String(angle) + "}";

  server.send(200, "application/json", out);

}



// 10. Direct Dual-Servo Escapement Test Endpoint

void handleTestEscapement() {

  setCorsHeaders();

  int slot = server.arg("slot").toInt();

  if (slot < 1 || slot > 3) slot = 1;

  appendLog("\n>>> [MANUAL ESCAPEMENT TEST] Testing Slot #" + String(slot) + " Dual-Servo Sequence...");

  dispenseEscapementSlot(slot);

  server.send(200, "application/json", "{\"success\":true,\"slot\":" + String(slot) + "}");

}



// =============================================================================

// SETUP

// =============================================================================

void setup() {

  Serial.begin(115200);

  Serial.println("\n========================================================");

  Serial.println("  PENPOINT SMART VENDING — ESP32 HARDWARE CONTROLLER");

  Serial.println("  Mode: 100% REAL PHYSICAL HARDWARE (Strict Pins & Sensors)");

  Serial.println("========================================================");



  // 1. Initialize TB6612 #1 & #2 Motor Driver Pins

  pinMode(TB6612_STBY, OUTPUT);

  digitalWrite(TB6612_STBY, LOW);



  pinMode(MOTOR1_PWMA, OUTPUT);

  pinMode(MOTOR1_AIN1, OUTPUT);

  pinMode(MOTOR1_AIN2, OUTPUT);

  digitalWrite(MOTOR1_AIN1, LOW);

  digitalWrite(MOTOR1_AIN2, LOW);



  pinMode(MOTOR2_PWMB, OUTPUT);

  pinMode(MOTOR2_BIN1, OUTPUT);

  pinMode(MOTOR2_BIN2, OUTPUT);

  digitalWrite(MOTOR2_BIN1, LOW);

  digitalWrite(MOTOR2_BIN2, LOW);



  pinMode(MOTOR3_PWMA, OUTPUT);

  pinMode(MOTOR3_AIN1, OUTPUT);

  pinMode(MOTOR3_AIN2, OUTPUT);

  digitalWrite(MOTOR3_AIN1, LOW);

  digitalWrite(MOTOR3_AIN2, LOW);



  // 2. Initialize Optical IR Break-Beam Receiver Pins

  pinMode(IR_RECEIVER_1, INPUT_PULLUP);

  pinMode(IR_RECEIVER_2, INPUT_PULLUP);



  // 3. Initialize Servos (Detached at boot so continuous servos DO NOT spin or brown out the power rail!)

  ESP32PWM::allocateTimer(0);

  ESP32PWM::allocateTimer(1);

  ESP32PWM::allocateTimer(2);

  ESP32PWM::allocateTimer(3);



  for (int i = 0; i < NUM_SERVOS; i++) {

    pinMode(SERVO_PINS[i], OUTPUT);

    digitalWrite(SERVO_PINS[i], LOW);

    servos[i].detach();

  }



  // 4. Central Server URL Configuration (Auto-set to actual PC IP 192.168.254.121)

  prefs.begin("penpoint", false);

  if (prefs.isKey("server_url")) {

    String saved = prefs.getString("server_url");

    if (saved.indexOf("192.168.1.9") != -1 || saved.length() == 0) {

      serverUrl = "http://192.168.254.121:8000";

      prefs.putString("server_url", serverUrl);

    } else {

      serverUrl = saved;

    }

  } else {

    serverUrl = "http://192.168.254.121:8000";

    prefs.putString("server_url", serverUrl);

  }

  prefs.end();

  Serial.println("[SERVER] Target Central Server URL: " + serverUrl);



  // 5. Connect WiFi

  Serial.printf("[WIFI] Connecting to %s", DEFAULT_WIFI_SSID);

  WiFi.mode(WIFI_STA);

  WiFi.begin(DEFAULT_WIFI_SSID, DEFAULT_WIFI_PASS);



  int tries = 0;

  while (WiFi.status() != WL_CONNECTED && tries < 25) { 

    delay(400);

    Serial.print(".");

    tries++;

  }



  if (WiFi.status() == WL_CONNECTED) {

    Serial.println("\n[WIFI] Connected successfully!");

    Serial.print("[WIFI] Open ESP32 Web Dashboard: http://");

    Serial.println(WiFi.localIP());

  } else {

    Serial.println("\n[WIFI] Connection pending or timed out. Retrying in loop...");

  }



  // 6. Register WebServer Endpoints

  server.on("/", handleRoot);

  server.on("/api/status", handleStatus);

  server.on("/api/sensor", handleSensor);

  server.on("/api/toggle_hw_mode", handleToggleHwMode);

  server.on("/api/dispense", handleDirectDispense);

  server.on("/api/logs", handleLogs);

  server.on("/api/test_ping", handleTestPing);

  server.on("/api/test_poll", handleTestPoll);

  server.on("/api/test_order", handleTestOrder);

  server.on("/api/test_verify", handleTestVerify);

  server.on("/api/set_server", handleSetServer);

  server.on("/api/toggle_poll", handleTogglePoll);

  server.on("/api/toggle_jam", handleToggleJam);

  server.on("/api/spin_motor", handleSpinMotor);

  server.on("/api/test_servo", handleTestServo);

  server.on("/api/test_escapement", handleTestEscapement);



  server.begin();

  Serial.println("[HTTP] Controller Server running on Port 80.");

}



// =============================================================================

// MAIN LOOP

// =============================================================================

void loop() {

  server.handleClient();



  // Live Instant State-Change Monitor for GPIO 23 IR Sensor

  static int lastIrPin32 = -1;

  int currentIrPin32 = digitalRead(IR_RECEIVER_1);

  if (currentIrPin32 != lastIrPin32) {

    lastIrPin32 = currentIrPin32;

    if (currentIrPin32 == LOW) {

      Serial.println("\n[IR TEST GPIO 32] >>> BEAM BROKEN / OBJECT DETECTED! (Pin 32: LOW) <<<");

      appendLog(">>> [IR TEST GPIO 32] BEAM BROKEN / OBJECT DETECTED! (Pin 32: LOW) <<<");

    } else {

      Serial.println("[IR TEST GPIO 32] Beam Cleared / Intact (Pin 32: HIGH)");

      appendLog("[IR TEST GPIO 32] Beam Cleared / Intact (Pin 32: HIGH)");

    }

  }



  // Poll central PenPoint server for kiosk orders

  if (millis() - lastPollTime >= POLL_INTERVAL_MS) {

    lastPollTime = millis();

    pollServer();

  }



  // Auto-reconnect WiFi if connection drops

  if (WiFi.status() != WL_CONNECTED) {

    WiFi.reconnect();

    delay(1000);

  }

}

