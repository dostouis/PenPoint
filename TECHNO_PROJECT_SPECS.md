# PENPOINT: Project Specification & Academic Architecture
**Institution:** University of Makati — College of Computing and Information Sciences  
**Course:** Technoentrepreneurship Project (IV-CINS)  
**Group:** SkiDB  
**Authors:**
- **Polinio, Jiyan Carl C.** *(Leader)*
- **Del Villar, Tracy Carmelo M.**
- **Llanes, Kurt Luis E.**
- **Palma, John Matthew S.**

---

## 1. Official Project Title
> **PENPOINT: An ESP32-Based, Cloud-Connected Smart Vending Machine with QR Ph Payment and Sensor-Verified Dispensing of Academic Supplies**

---

## 2. Hardware Architecture & Enclosure Specs
- **Machine Structure:** Compact tabletop mini cabinet (`40 × 40 × 71.4 cm`) constructed from `0.9 cm` marine plywood (`0.6 cm` shelves & back panel).
- **Physical Display & Chute:**
  - `3.5-inch` LCD Touchscreen (displays dynamic QR Ph code) & status LEDs on the head panel.
  - Transparent acrylic window with front door key lock and limit switch for forced-entry alerts.
  - `20°` drop ramp leading to a `34 × 6 cm` pickup flap.
- **Dispensing Mechanisms (6-Slot Prototype in 4 Tiers with 10 cm clear height):**
  1. **3 Roller-Feed Trays:** Powered by 3 small DC gear motors (6V average with PWM motor driver) with rubber rollers and adjustable gate plates for flat paper packs:
     - **Slot 1:** Long Bond Paper pack
     - **Slot 2:** A4 Bond Paper pack
     - **Slot 3:** Short Bond Paper pack
  2. **3 Paddle-Push Lanes:** Powered by 3 metal-gear servos with crank-slider pushers releasing the bottom item of a stacked column:
     - **Slot 4:** Ballpen (Pilot G-Tec / HBW)
     - **Slot 5:** Pencil (Exam Pencil 2B)
     - **Slot 6:** Spare Slot / Accessories (Eraser / Blue Book)
- **Drop Verification:**
  - 4 Infrared (IR) break-beam sensors (one per tier exit) confirming physical delivery before recording sales.
- **Power Subsystem:**
  - Mains 220V AC input → 12V / 5A power supply + fuse → 12V to 5V buck converter (for ESP32, touchscreen, IR sensors) + 12V motor driver rail (PWM to 6V DC motors).

---

## 3. Transaction State Machine & Failure Flow
The ESP32 firmware and cloud backend execute a formal 7-state transaction lifecycle:
1. `idle`: Machine ready, displaying inventory availability and connection status.
2. `item selected`: Student taps supply on screen.
3. `QR displayed`: Backend calls **PayMongo API** to generate a single-use dynamic **QR Ph** code (expires in 30 mins).
4. `paid`: Webhook (`payment.paid`) confirms payment from e-wallet/bank.
5. `dispensing`: ESP32 activates the corresponding DC roller motor or paddle servo for a fixed time.
6. `verified`: Break-beam IR sensor detects physical item drop through the exit beam.
   - *Failure Branch:* If no drop detected → Reverse/retry once. If retry fails → Sale cancelled, marked **refund-pending**, and alert sent to dashboard.
7. `completed`: Transaction logged, stock reduced in database, student collects item from tray.

---

## 4. Payment Gateway & Economy
- **Standard:** QR Ph (National QR Code Standard supported by GCash, Maya, ShopeePay, BPI, BDO, UnionBank, RCBC, etc.).
- **Gateway Provider:** PayMongo (1.34% fee, no fixed fee, ideal for ₱12–₱22 school essentials).

---

## 5. Web Software Requirements
- **Administrator Dashboard (Login Required):**
  - Live stock & slot matrix management (roller-feed & paddle-push lanes).
  - ESP32 hardware telemetry & online/offline connectivity monitoring.
  - Real-time break-beam IR verification log.
  - Refund-pending transactions queue & door forced-entry tamper alerts.
- **Public Kiosk & Status Page:**
  - Responsive web app with dynamic QR Ph display, live slot availability, and instant dispense telemetry.
