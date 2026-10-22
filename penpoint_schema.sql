-- phpMyAdmin SQL Dump
-- PenPoint: Smart Vending for Everyday School Essentials
-- Database: `penpoint`

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+08:00";

-- --------------------------------------------------------
-- Table structure for table `machines`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `machines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `machine_id` varchar(50) NOT NULL UNIQUE,
  `location` varchar(255) NOT NULL,
  `status` enum('ACTIVE','INACTIVE','MAINTENANCE') DEFAULT 'ACTIVE',
  `ip_address` varchar(45) DEFAULT NULL,
  `total_dispenses` int(11) DEFAULT 0,
  `last_ping` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `machines` (`machine_id`, `location`, `status`, `ip_address`, `total_dispenses`, `last_ping`, `created_at`) VALUES
('PEN-01', 'Engineering Hallway (Near Room 302)', 'ACTIVE', '192.168.1.101', 42, NOW(), NOW()),
('PEN-02', 'Main University Library (2nd Floor)', 'ACTIVE', '192.168.1.102', 28, NOW(), NOW()),
('PEN-03', 'Science & Technology Complex', 'ACTIVE', '192.168.1.103', 19, NOW(), NOW()),
('PEN-04', 'Senior High School Building Lobby', 'MAINTENANCE', '192.168.1.104', 5, NOW(), NOW());

-- --------------------------------------------------------
-- Table structure for table `products` (replaces old bottle/rewards)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slot_number` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT 'Pens',
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 10.00,
  `stock` int(11) NOT NULL DEFAULT 15,
  `max_capacity` int(11) NOT NULL DEFAULT 20,
  `low_stock_threshold` int(11) NOT NULL DEFAULT 4,
  `sku` varchar(50) DEFAULT NULL,
  `badge` varchar(50) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `dispensed_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slot_unique` (`slot_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `products` (`id`, `slot_number`, `name`, `category`, `description`, `price`, `stock`, `max_capacity`, `low_stock_threshold`, `sku`, `badge`, `image_url`, `is_active`, `dispensed_count`, `created_at`) VALUES
(1, 1, 'Pilot G-Tec C4 Black 0.4mm', 'Pens', 'Ultra-fine gel ink pen for precise notes and technical drawing.', 45.00, 14, 20, 4, 'PEN-GTEC-BLK', 'Popular', 'assets/img/gtec.png', 1, 31, NOW()),
('2', 2, 'HBW Matrix Ballpen 0.5 Black', 'Pens', 'Smooth-gliding everyday exam & quiz ballpen with rubber grip.', 10.00, 18, 25, 5, 'PEN-HBW-BLK', 'Best Value', 'assets/img/hbw.png', 1, 84, NOW()),
('3', 3, 'Faber-Castell 2B Exam Pencil', 'Pencils', 'Dark, high-opacity exam grade pencil for Scantron answer sheets.', 15.00, 12, 20, 3, 'PCL-FC-2B', 'Exam Ready', 'assets/img/pencil.png', 1, 23, NOW()),
('4', 4, 'Standard Blue Book (Pack of 2)', 'Examination', 'Official university examination booklet with lined answer leaves.', 20.00, 16, 25, 5, 'EXM-BLUEBOOK', 'Must-Have', 'assets/img/bluebook.png', 1, 62, NOW()),
('5', 5, 'Short Bond Paper (10 Sheets/Pack)', 'Paper', 'High-brightness 70gsm multipurpose white copy paper in sealed pack.', 15.00, 8, 20, 4, 'PPR-BOND-SHT', 'Sealed', 'assets/img/bond-short.png', 1, 18, NOW()),
('6', 6, 'A4 Bond Paper (10 Sheets/Pack)', 'Paper', 'Quality 80gsm A4 leaves for project reports and term papers.', 18.00, 10, 20, 4, 'PPR-BOND-A4', 'Standard', 'assets/img/bond-a4.png', 1, 27, NOW()),
('7', 7, 'Pentel Hi-Polymer Eraser + Lead Refill', 'Accessories', 'Non-abrasive plastic eraser with 0.5mm 2B mechanical pencil lead tubes.', 25.00, 6, 15, 3, 'ACC-ERASER-LEAD', 'Combo', 'assets/img/eraser.png', 1, 12, NOW()),
('8', 8, 'Correction Tape 5mm x 6m', 'Accessories', 'Instant-dry white tear-proof correction tape with ergonomic applicator.', 28.00, 5, 15, 3, 'ACC-CORR-TAPE', 'Low Stock', 'assets/img/correction.png', 1, 15, NOW());

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `idcard` varchar(100) NOT NULL UNIQUE,
  `fname` varchar(100) DEFAULT NULL,
  `mname` varchar(100) DEFAULT NULL,
  `lname` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `student_no` varchar(50) DEFAULT NULL,
  `wallet_balance` decimal(10,2) NOT NULL DEFAULT 50.00,
  `current_points` int(11) DEFAULT 0,
  `total_spent` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_items_bought` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`idcard`, `fname`, `mname`, `lname`, `student_no`, `wallet_balance`, `current_points`, `total_spent`, `total_items_bought`, `created_at`) VALUES
('2201499204', 'Kurt Luis', 'E.', 'Llanes', '2022-10492', 125.00, 45, 135.00, 6, NOW()),
('3474697234', 'Tracy Carmelo', 'M.', 'Del Villar', '2022-10884', 90.00, 30, 95.00, 4, NOW()),
('846977114', 'John Matthew', 'S.', 'Palma', '2022-10331', 110.00, 25, 75.00, 3, NOW()),
('3474741991', 'Jiyan Carl', 'C.', 'Polinio', '2022-10552', 150.00, 50, 160.00, 7, NOW());

-- --------------------------------------------------------
-- Table structure for table `transactions`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_key` varchar(100) NOT NULL UNIQUE,
  `machine_id` varchar(50) NOT NULL,
  `idcard` varchar(100) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `slot_number` int(11) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `payment_method` enum('GCASH_QR','MAYA_QR','STUDENT_NFC','CASHLESS_PORTAL') DEFAULT 'GCASH_QR',
  `dispense_status` enum('PENDING','DISPENSING','VERIFIED','FAILED','TIMEOUT') DEFAULT 'VERIFIED',
  `ir_sensor_verified` tinyint(1) DEFAULT 1,
  `reference_no` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `machine_idx` (`machine_id`),
  KEY `idcard_idx` (`idcard`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `transactions` (`transaction_key`, `machine_id`, `idcard`, `product_id`, `product_name`, `slot_number`, `amount`, `payment_method`, `dispense_status`, `ir_sensor_verified`, `reference_no`, `created_at`) VALUES
('TXN-PEN01-1001', 'PEN-01', '2201499204', 1, 'Pilot G-Tec C4 Black 0.4mm', 1, 45.00, 'GCASH_QR', 'VERIFIED', 1, 'GCASH-9812471', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
('TXN-PEN01-1002', 'PEN-01', '3474697234', 4, 'Standard Blue Book (Pack of 2)', 4, 20.00, 'MAYA_QR', 'VERIFIED', 1, 'MAYA-4419203', DATE_SUB(NOW(), INTERVAL 1 HOUR)),
('TXN-PEN02-1003', 'PEN-02', '846977114', 2, 'HBW Matrix Ballpen 0.5 Black', 2, 10.00, 'STUDENT_NFC', 'VERIFIED', 1, 'NFC-846977114', DATE_SUB(NOW(), INTERVAL 45 MINUTE)),
('TXN-PEN01-1004', 'PEN-01', '3474741991', 6, 'A4 Bond Paper (10 Sheets/Pack)', 6, 18.00, 'GCASH_QR', 'VERIFIED', 1, 'GCASH-5519302', DATE_SUB(NOW(), INTERVAL 12 MINUTE));

-- --------------------------------------------------------
-- Table structure for table `activity_logs`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `log_type` enum('scan','dispense','verification','qr_generated','payment_received','restock','machine_status','error') NOT NULL,
  `idcard` varchar(100) DEFAULT NULL,
  `machine_id` varchar(50) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `points_change` int(11) DEFAULT 0,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `activity_logs` (`log_type`, `idcard`, `machine_id`, `details`, `points_change`, `created_at`) VALUES
('machine_status', NULL, 'PEN-01', 'ESP32 heartbeat OK. All 8 slots online. IR dispense sensors verified.', 0, DATE_SUB(NOW(), INTERVAL 10 MINUTE)),
('payment_received', '3474741991', 'PEN-01', 'Payment of ₱18.00 verified for Slot #6 (A4 Bond Paper) via GCash QR.', 5, DATE_SUB(NOW(), INTERVAL 12 MINUTE)),
('dispense', '3474741991', 'PEN-01', 'Servo actuated slot #6. IR sensor confirmed product dropped into tray.', 0, DATE_SUB(NOW(), INTERVAL 12 MINUTE)),
('verification', '3474741991', 'PEN-01', 'Dispense verified successfully by IR sensor.', 0, DATE_SUB(NOW(), INTERVAL 12 MINUTE));

COMMIT;
