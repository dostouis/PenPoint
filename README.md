# PenPoint: Smart Vending for Everyday School Essentials
A Cashless, Sensor-Monitored Vending Platform for Everyday Academic Supplies

Designed and developed by:
- **Del Villar, Tracy Carmelo M.**
- **Llanes, Kurt Luis E.**
- **Palma, John Matthew S.**
- **Polinio, Jiyan Carl C.**

---

## 🎯 Project Overview
PenPoint is a compact, ESP32-powered automated vending machine designed to provide students with instant, convenient access to commonly needed academic supplies (ballpens, pencils, bond paper, and blue books) right outside classrooms, labs, and exam halls.

It replaces the previous bottle-recycling system (**EcoPoints**) with a modern IoT school essentials vending platform featuring **Cashless QR Payments (GCash / Maya)**, **motor-driven dispensing**, and **IR sensor drop verification**.

---

## 🚀 Key Features
1. **Cashless QR Payment**: Scan to pay via GCash, Maya, or academic card without coins/bills.
2. **Automated Dispensing**: Servo or geared DC motors actuate supply coils (4–8 standardized slots).
3. **Sensor-Monitored Verification**: IR beam break sensors confirm that supplies dropped into the retrieval tray before the transaction is finalized.
4. **Mobile-First Responsive Interface**: Modern UI built with Tailwind-inspired glassmorphism, Lucide icons, and quick-tap checkout.
5. **Centralized Admin & Telemetry Console**: Live stock matrix, low-stock alerts, sales analytics, and connected machine health.
6. **ESP32 Microcontroller Integration**: RESTful endpoints for dispensing commands, ping heartbeats, and sensor callbacks.

---

## 📂 Project Structure
- `public/index.php`: Modern mobile-friendly student vending kiosk with slot catalog, QR checkout modal, and simulated IR sensor verification.
- `public/user.php`: Student card/pass portal.
- `admin/index.php`: Central vending control console with real-time stock levels, one-click restock, and activity telemetry.
- `admin/analytics.php`: Demand and popularity trends for academic supplies.
- `api/dispense.php`: Hardware/software integration endpoint for motor trigger and IR verification.
- `api/products.php`: Slot and item availability API.
- `models/ProductModel.php`: 4–8 slot supply matrix and stock management.
- `config/config.php`: System settings, currency symbols, and team configuration.
- `penpoint_schema.sql`: SQL database schema for MySQL / MariaDB.

---

## 🛠️ How to Run Locally with XAMPP

1. Place this directory or link it in `C:\xampp\htdocs\penpoint`.
2. Start **Apache** and **MySQL** in XAMPP Control Panel.
3. Open `http://localhost/phpmyadmin` and import `penpoint_schema.sql` into a database named `penpoint`.
4. Open the student kiosk in your browser or phone:
   - `http://localhost/penpoint/public/index.php`
5. Open the management console:
   - `http://localhost/penpoint/admin/index.php`
