-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql212.infinityfree.com
-- Generation Time: Apr 24, 2026 at 07:01 AM
-- Server version: 11.4.10-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_41735972_ecopoints`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `log_type` enum('scan','transaction','registration','reward_claim','profile_update','machine_auth','error') DEFAULT NULL,
  `idcard` varchar(100) DEFAULT NULL,
  `machine_id` varchar(50) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `points_change` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `log_type`, `idcard`, `machine_id`, `details`, `points_change`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 'registration', '3474697234', 'M001', 'New card registered automatically via scan', NULL, '192', 'ESP32HTTPClient', '2026-04-16 08:12:02'),
(2, 'scan', '3474697234', 'M001', 'Card validated: existing user - User', NULL, '192', 'ESP32HTTPClient', '2026-04-16 08:12:04'),
(3, 'transaction', '3474697234', 'M001', 'Recycled 15 bottles, earned 30 points', 30, '192', 'ESP32HTTPClient', '2026-04-16 08:12:33'),
(4, 'registration', '846977114', 'M001', 'New card registered automatically via scan', NULL, '192', 'ESP32HTTPClient', '2026-04-16 08:13:57'),
(5, 'scan', '846977114', 'M001', 'Card validated: existing user - User', NULL, '192', 'ESP32HTTPClient', '2026-04-16 08:14:00'),
(6, 'transaction', '846977114', 'M001', 'Recycled 30 bottles, earned 60 points', 60, '192', 'ESP32HTTPClient', '2026-04-16 08:14:36'),
(7, 'scan', '2201499204', 'M001', 'Card validated: existing user - KURT LUIS LLANES', NULL, '192', 'ESP32HTTPClient', '2026-04-16 08:16:50'),
(8, 'transaction', '2201499204', 'M001', 'Recycled 67 bottles, earned 134 points', 134, '192', 'ESP32HTTPClient', '2026-04-16 08:18:04'),
(9, 'scan', '846977114', 'M001', 'Card validated: existing user - User', NULL, '192', 'ESP32HTTPClient', '2026-04-16 08:18:35'),
(10, 'scan', '2201499204', 'M001', 'Card validated: existing user - KURT LUIS LLANES', NULL, '192', 'ESP32HTTPClient', '2026-04-22 08:21:28'),
(11, 'scan', '2201499204', 'M001', 'Temp transaction started: 0 bottles, 0 points', NULL, '192', 'ESP32HTTPClient', '2026-04-22 08:21:28'),
(12, 'scan', '2201499204', 'M001', 'Card validated: existing user - KURT LUIS LLANES', NULL, '192', 'ESP32HTTPClient', '2026-04-22 08:21:55'),
(13, 'error', '2201499204', 'M001', 'Temp transaction commit failed: points_failed', NULL, '192', 'ESP32HTTPClient', '2026-04-22 08:21:57'),
(14, 'scan', '2201499204', 'M001', 'Temp transaction cancelled: 0 bottles discarded', NULL, '192', 'ESP32HTTPClient', '2026-04-22 08:22:04'),
(15, 'scan', '2201499204', 'M001', 'Card validated: existing user - KURT LUIS LLANES', NULL, '192', 'ESP32HTTPClient', '2026-04-22 08:22:18'),
(16, 'scan', '2201499204', 'M001', 'Temp transaction started: 0 bottles, 0 points', NULL, '192', 'ESP32HTTPClient', '2026-04-22 08:22:19'),
(17, 'scan', '2201499204', 'M001', 'Card validated: existing user - KURT LUIS LLANES', NULL, '192', 'ESP32HTTPClient', '2026-04-22 08:22:33'),
(18, 'error', '2201499204', 'M001', 'Temp transaction commit failed: points_failed', NULL, '192', 'ESP32HTTPClient', '2026-04-22 08:22:42'),
(19, 'scan', '2201499204', 'M001', 'Temp transaction cancelled: 0 bottles discarded', NULL, '192', 'ESP32HTTPClient', '2026-04-22 08:22:46'),
(20, 'scan', '3474697234', 'M001', 'Card validated: existing user - User', NULL, '192', 'ESP32HTTPClient', '2026-04-22 08:23:08'),
(21, 'scan', '3474697234', 'M001', 'Temp transaction started: 0 bottles, 0 points', NULL, '192', 'ESP32HTTPClient', '2026-04-22 08:23:08'),
(22, 'scan', '3474697234', 'M001', 'Card validated: existing user - User', NULL, '192', 'ESP32HTTPClient', '2026-04-22 08:23:22'),
(23, 'scan', '3474697234', 'M001', 'Card validated: existing user - User', NULL, '192', 'ESP32HTTPClient', '2026-04-22 08:26:14'),
(24, 'scan', '3474697234', 'M001', 'Temp transaction cancelled: 0 bottles discarded', NULL, '192', 'ESP32HTTPClient', '2026-04-22 08:26:27'),
(25, 'scan', '3474697234', 'M001', 'Card validated: existing user - User', NULL, '192', 'ESP32HTTPClient', '2026-04-22 08:34:40'),
(26, 'scan', '3474697234', 'M001', 'Temp transaction started: 0 bottles, 0 points', NULL, '192', 'ESP32HTTPClient', '2026-04-22 08:34:40'),
(27, 'scan', '3474697234', 'M001', 'Card validated: existing user - User', NULL, '192', 'ESP32HTTPClient', '2026-04-22 08:34:51'),
(28, 'scan', '3474697234', 'M001', 'Temp transaction cancelled: 0 bottles discarded', NULL, '192', 'ESP32HTTPClient', '2026-04-22 08:35:32'),
(29, 'scan', '3474697234', 'M001', 'Card validated: existing user - User', NULL, '192', 'ESP32HTTPClient', '2026-04-22 08:37:47'),
(30, 'scan', '3474697234', 'M001', 'Temp transaction started: 0 bottles, 0 points', NULL, '192', 'ESP32HTTPClient', '2026-04-22 08:37:53'),
(31, 'scan', '3474697234', 'M001', 'Card validated: existing user - User', NULL, '192', 'ESP32HTTPClient', '2026-04-22 08:41:59'),
(32, 'scan', '3474697234', 'M001', 'Temp transaction cancelled: 0 bottles discarded', NULL, '192', 'ESP32HTTPClient', '2026-04-22 08:42:07'),
(33, 'scan', '3474697234', 'M001', 'Card validated: existing user - User', NULL, '192', 'ESP32HTTPClient', '2026-04-22 08:42:11'),
(34, 'scan', '3474697234', 'M001', 'Temp transaction started: 0 bottles, 0 points', NULL, '192', 'ESP32HTTPClient', '2026-04-22 08:42:11'),
(35, 'scan', '3474697234', 'M001', 'Card validated: existing user - User', NULL, '192', 'ESP32HTTPClient', '2026-04-22 08:49:53'),
(36, 'scan', '3474697234', 'M001', 'Card validated: existing user - User', NULL, '192', 'ESP32HTTPClient', '2026-04-22 08:57:50'),
(37, 'scan', '3474697234', 'M001', 'Temp transaction cancelled: 0 bottles discarded', NULL, '192', 'ESP32HTTPClient', '2026-04-22 08:57:59'),
(38, 'scan', '3474697234', 'M001', 'Card validated: existing user - User', NULL, '192', 'ESP32HTTPClient', '2026-04-22 08:58:12'),
(39, 'scan', '3474697234', 'M001', 'Temp transaction started: 0 bottles, 0 points', NULL, '192', 'ESP32HTTPClient', '2026-04-22 08:58:12'),
(40, 'scan', '3474697234', 'M001', 'Card validated: existing user - User', NULL, '192', 'ESP32HTTPClient', '2026-04-22 09:23:04'),
(41, 'scan', '3474697234', 'M001', 'Temp transaction cancelled: 0 bottles discarded', NULL, '192', 'ESP32HTTPClient', '2026-04-22 09:23:36'),
(42, 'scan', '3474741991', 'M001', 'Card validated: existing user - John Doe', NULL, '180', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 06:19:46'),
(43, 'scan', '3474741991', 'M001', 'Card validated: existing user - John Doe', NULL, '180', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 06:27:13'),
(44, 'registration', 'TESTNEW', 'M001', 'New card registered automatically via scan', NULL, '180', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 06:27:18'),
(45, 'transaction', '3474741991', 'M001', 'Recycled 5 bottles, earned 10 points', 10, '180', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 06:27:25'),
(46, 'scan', '3474741991', 'M001', 'Temp transaction started: 0 bottles, 0 points', NULL, '180', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 06:27:51'),
(47, 'registration', 'STUDENT999', 'M001', 'New card registered automatically via scan', NULL, '180', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 06:29:03'),
(48, 'scan', 'STUDENT999', 'M001', 'Card validated: existing user - User', NULL, '180', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 06:29:10'),
(49, 'transaction', 'STUDENT999', 'M001', 'Recycled 5 bottles, earned 10 points', 10, '180', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 06:29:18'),
(50, 'scan', 'STUDENT999', 'M001', 'Card validated: existing user - User', NULL, '180', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 06:29:25'),
(51, 'transaction', 'STUDENT999', 'M001', 'Recycled 10 bottles, earned 20 points', 20, '180', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 06:29:30'),
(53, 'error', 'STUDENT999', 'M001n_key=TEST_STUDENT999_002', 'Transaction failed: machine_invalid', NULL, '180', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 06:29:36'),
(54, 'scan', 'STUDENT999', 'M001', 'Card validated: existing user - User', NULL, '180', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 06:29:38'),
(55, 'error', 'STUDENT999', 'M001', 'Duplicate transaction attempted: TEST_STUDENT999_001', NULL, '180', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 06:29:43'),
(56, 'registration', 'PROFESSOR123', 'M001', 'New card registered automatically via scan', NULL, '180', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 06:29:52'),
(57, 'transaction', 'PROFESSOR123', 'M001', 'Recycled 20 bottles, earned 40 points', 40, '180', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 06:29:58'),
(58, 'registration', 'NEWUSER001', 'M001', 'New card registered automatically via scan', NULL, '180', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 08:43:17'),
(59, 'scan', 'NEWUSER001', 'M001', 'Card validated: existing user - User', NULL, '180', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 08:43:22'),
(60, 'transaction', 'NEWUSER001', 'M001', 'Recycled 5 bottles, earned 10 points', 10, '180', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 08:43:32'),
(62, 'error', 'NEWUSER001', 'M001achine_id=M001', 'Transaction failed: machine_invalid', NULL, '180', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 08:43:37'),
(63, 'scan', 'NEWUSER001', 'M001', 'Card validated: existing user - User', NULL, '180', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 08:43:39'),
(64, 'transaction', 'NEWUSER001', 'M001', 'Recycled 10 bottles, earned 20 points', 20, '180', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 08:43:44'),
(65, 'error', 'NEWUSER001', 'M001', 'Duplicate transaction attempted: TEST_NEWUSER_001', NULL, '180', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 08:43:49'),
(66, 'registration', 'STUDENT2024', 'M001', 'New card registered automatically via scan', NULL, '180', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 08:43:58'),
(67, 'transaction', 'STUDENT2024', 'M001', 'Recycled 20 bottles, earned 40 points', 40, '180', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 08:44:03'),
(68, 'registration', 'VERSTAPPEN01', 'M001', 'New card registered automatically via scan', NULL, '180', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 09:10:34'),
(69, 'scan', 'VERSTAPPEN01', 'M001', 'Card validated: existing user - User', NULL, '180', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 09:10:39'),
(70, 'scan', 'VERSTAPPEN01', 'M001', 'Card validated: existing user - User', NULL, '180', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 09:10:44'),
(71, 'transaction', 'VERSTAPPEN01', 'M001', 'Recycled 33 bottles, earned 66 points', 66, '180', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 09:10:53'),
(72, 'scan', 'VERSTAPPEN01', 'M001', 'Card validated: existing user - User', NULL, '180', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 09:11:00'),
(73, 'transaction', 'VERSTAPPEN01', 'M001', 'Recycled 10 bottles, earned 20 points', 20, '180', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 09:11:09'),
(74, 'error', 'VERSTAPPEN01', 'M001', 'Duplicate transaction attempted: TEST_MAX_001', NULL, '180', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 09:11:14'),
(75, 'registration', 'ALONSO14', 'M001', 'New card registered automatically via scan', NULL, '180', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 09:11:21'),
(76, 'scan', 'ALONSO14', 'M001', 'Card validated: existing user - User', NULL, '180', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 09:11:26'),
(77, 'transaction', 'ALONSO14', 'M001', 'Recycled 14 bottles, earned 28 points', 28, '180', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 09:11:31'),
(78, 'transaction', 'ALONSO14', 'M001', 'Recycled 20 bottles, earned 40 points', 40, '180', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 09:11:35'),
(79, 'error', 'ALONSO14', 'M001', 'Duplicate transaction attempted: TEST_ALONSO_002', NULL, '180', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 09:11:44'),
(80, 'error', 'ALONSO14', 'M001', 'Duplicate transaction attempted: TEST_ALONSO_001', NULL, '180', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 09:11:49'),
(81, 'error', 'ALONSO14', 'M001', 'Duplicate transaction attempted: TEST_ALONSO_001', NULL, '180', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-24 09:11:56');

-- --------------------------------------------------------

--
-- Table structure for table `machines`
--

CREATE TABLE `machines` (
  `id` int(11) NOT NULL,
  `machine_id` varchar(50) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `status` enum('ACTIVE','INACTIVE','MAINTENANCE') DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `machines`
--

INSERT INTO `machines` (`id`, `machine_id`, `location`, `status`, `created_at`) VALUES
(1, 'M001', 'School Entrance', 'ACTIVE', '2026-04-09 10:51:10'),
(2, 'M002', 'Mall Lobby', 'ACTIVE', '2026-04-09 10:51:10'),
(3, 'M003', 'Barangay Hall', 'ACTIVE', '2026-04-09 10:51:10');

-- --------------------------------------------------------

--
-- Table structure for table `rewards`
--

CREATE TABLE `rewards` (
  `id` int(11) NOT NULL,
  `reward_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `points_required` int(11) NOT NULL,
  `stock` int(11) DEFAULT 0,
  `is_active` tinyint(4) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `temp_transactions`
--

CREATE TABLE `temp_transactions` (
  `id` int(11) NOT NULL,
  `machine_id` varchar(50) DEFAULT NULL,
  `idcard` varchar(100) DEFAULT NULL,
  `bottle_count` int(11) DEFAULT 0,
  `points_to_add` int(11) DEFAULT 0,
  `status` enum('pending','done','unchanged','cancelled') DEFAULT 'pending',
  `transaction_key` varchar(100) DEFAULT NULL,
  `session_start` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `temp_transactions`
--

INSERT INTO `temp_transactions` (`id`, `machine_id`, `idcard`, `bottle_count`, `points_to_add`, `status`, `transaction_key`, `session_start`, `created_at`, `updated_at`) VALUES
(1, 'M001', '2201499204', 0, 0, 'cancelled', 'M001_2201499204_1776846088', '2026-04-22 08:21:28', '2026-04-22 08:21:28', '2026-04-22 08:22:04'),
(2, 'M001', '2201499204', 0, 0, 'cancelled', 'M001_2201499204_1776846139', '2026-04-22 08:22:19', '2026-04-22 08:22:19', '2026-04-22 08:22:46'),
(3, 'M001', '3474697234', 0, 0, 'cancelled', 'M001_3474697234_1776846188', '2026-04-22 08:23:08', '2026-04-22 08:23:08', '2026-04-22 08:26:27'),
(4, 'M001', '3474697234', 0, 0, 'cancelled', 'M001_3474697234_1776846880', '2026-04-22 08:34:40', '2026-04-22 08:34:40', '2026-04-22 08:35:32'),
(5, 'M001', '3474697234', 0, 0, 'cancelled', 'M001_3474697234_1776847073', '2026-04-22 08:37:53', '2026-04-22 08:37:53', '2026-04-22 08:42:07'),
(6, 'M001', '3474697234', 0, 0, 'cancelled', 'M001_3474697234_1776847331', '2026-04-22 08:42:11', '2026-04-22 08:42:11', '2026-04-22 08:57:59'),
(7, 'M001', '3474697234', 0, 0, 'cancelled', 'M001_3474697234_1776848292', '2026-04-22 08:58:12', '2026-04-22 08:58:12', '2026-04-22 09:23:36'),
(8, 'M001', '3474741991', 0, 0, 'pending', 'M001_3474741991_1777012070', '2026-04-24 06:27:51', '2026-04-24 06:27:51', '2026-04-24 06:27:51');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `machine_id` varchar(50) DEFAULT NULL,
  `transaction_key` varchar(100) DEFAULT NULL,
  `idcard` varchar(100) DEFAULT NULL,
  `received_points` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `item_count` int(11) DEFAULT 1,
  `transaction_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `machine_id`, `transaction_key`, `idcard`, `received_points`, `created_at`, `item_count`, `transaction_date`) VALUES
(1, 'M001', 'TEST001', '3474741991', 6, '2026-04-10 06:14:35', 3, '2026-04-10 06:14:35'),
(2, 'M001', 'TEST123', '3474741991', 6, '2026-04-10 06:17:19', 3, '2026-04-10 06:17:19'),
(3, 'M001', 'TEST1', '3474741991', 6, '2026-04-10 06:18:30', 3, '2026-04-10 06:18:30'),
(4, 'M001', 'TEST', '3474741991', 10, '2026-04-10 06:18:49', 5, '2026-04-10 06:18:49'),
(5, 'M001', 'TEST_ADD_5', '3474741991', 10, '2026-04-10 06:20:30', 5, '2026-04-10 06:20:30'),
(6, 'M001', 'M001_3474697234_49994', '3474697234', 30, '2026-04-16 08:12:33', 15, '2026-04-16 08:12:33'),
(7, 'M001', 'M001_846977114_172605', '846977114', 60, '2026-04-16 08:14:36', 30, '2026-04-16 08:14:36'),
(8, 'M001', 'M001_2201499204_380636', '2201499204', 134, '2026-04-16 08:18:04', 67, '2026-04-16 08:18:04'),
(9, 'M001', 'M001_2201499204_1776846088_final', '2201499204', 0, '2026-04-22 08:21:57', 0, '2026-04-22 08:21:57'),
(10, 'M001', 'M001_2201499204_1776846139_final', '2201499204', 0, '2026-04-22 08:22:42', 0, '2026-04-22 08:22:42'),
(11, 'M001', 'WEBTEST_001', '3474741991', 10, '2026-04-24 06:27:25', 5, '2026-04-24 06:27:25'),
(12, 'M001', 'TEST_STUDENT999_001', 'STUDENT999', 10, '2026-04-24 06:29:18', 5, '2026-04-24 06:29:18'),
(13, 'M001', 'TEST_STUDENT999_002', 'STUDENT999', 20, '2026-04-24 06:29:30', 10, '2026-04-24 06:29:30'),
(14, 'M001', 'TEST_PROF123_001', 'PROFESSOR123', 40, '2026-04-24 06:29:58', 20, '2026-04-24 06:29:58'),
(15, 'M001', 'TEST_NEWUSER_001', 'NEWUSER001', 10, '2026-04-24 08:43:32', 5, '2026-04-24 08:43:32'),
(16, 'M001', 'TEST_NEWUSER_002', 'NEWUSER001', 20, '2026-04-24 08:43:44', 10, '2026-04-24 08:43:44'),
(17, 'M001', 'TEST_STUDENT_001', 'STUDENT2024', 40, '2026-04-24 08:44:03', 20, '2026-04-24 08:44:03'),
(18, 'M001', 'TEST_MAX_001', 'VERSTAPPEN01', 66, '2026-04-24 09:10:53', 33, '2026-04-24 09:10:53'),
(19, 'M001', 'TEST_MAX_002', 'VERSTAPPEN01', 20, '2026-04-24 09:11:09', 10, '2026-04-24 09:11:09'),
(20, 'M001', 'TEST_ALONSO_001', 'ALONSO14', 28, '2026-04-24 09:11:31', 14, '2026-04-24 09:11:31'),
(21, 'M001', 'TEST_ALONSO_002', 'ALONSO14', 40, '2026-04-24 09:11:35', 20, '2026-04-24 09:11:35');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `idcard` varchar(100) DEFAULT NULL,
  `fname` varchar(100) DEFAULT NULL,
  `mname` varchar(100) DEFAULT NULL,
  `lname` varchar(100) DEFAULT NULL,
  `user_type` enum('student','faculty','worker','outsider') DEFAULT NULL,
  `current_points` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `total_points_earned` int(11) DEFAULT 0,
  `total_items_recycled` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `idcard`, `fname`, `mname`, `lname`, `user_type`, `current_points`, `created_at`, `total_points_earned`, `total_items_recycled`) VALUES
(1, 'CARD123', NULL, NULL, NULL, NULL, 0, '2026-04-09 10:54:27', 0, 0),
(2, 'TEST123', NULL, NULL, NULL, NULL, 0, '2026-04-09 11:04:47', 0, 0),
(3, 'TEST001', 'John', NULL, 'Doe', NULL, 0, '2026-04-10 05:34:56', 0, 0),
(4, 'TEST6969', 'Juan', NULL, 'Juan', NULL, 0, '2026-04-10 05:36:05', 0, 0),
(5, 'A12345417', 'Kurt', NULL, 'Luis E. Llanes', NULL, 0, '2026-04-10 05:37:34', 0, 0),
(6, 'A12312312312', 'MAX', 'EMILIAN', 'VERSTAPPEN', NULL, 0, '2026-04-10 05:44:25', 0, 0),
(7, '3474741991', 'John', '', 'Doe', NULL, 48, '2026-04-10 06:14:23', 48, 24),
(9, '2201499204', 'KURT LUIS', 'EUSTAQUIO', 'LLANES', NULL, 134, '2026-04-11 07:24:48', 134, 67),
(10, '0846977114', '3474741991', '', '', NULL, 0, '2026-04-11 07:51:07', 0, 0),
(16, '1053973353', NULL, NULL, NULL, NULL, 0, '2026-04-13 08:50:05', 0, 0),
(17, '3474697234', NULL, NULL, NULL, NULL, 30, '2026-04-16 08:12:02', 30, 15),
(18, '846977114', NULL, NULL, NULL, NULL, 60, '2026-04-16 08:13:57', 60, 30),
(19, 'TESTNEW', NULL, NULL, NULL, NULL, 0, '2026-04-24 06:27:18', 0, 0),
(20, 'STUDENT999', NULL, NULL, NULL, NULL, 30, '2026-04-24 06:29:03', 30, 15),
(21, 'PROFESSOR123', NULL, NULL, NULL, NULL, 40, '2026-04-24 06:29:52', 40, 20),
(22, 'NEWUSER001', NULL, NULL, NULL, NULL, 30, '2026-04-24 08:43:17', 30, 15),
(23, 'STUDENT2024', NULL, NULL, NULL, NULL, 40, '2026-04-24 08:43:58', 40, 20),
(24, 'VERSTAPPEN01', NULL, NULL, NULL, NULL, 86, '2026-04-24 09:10:34', 86, 43),
(25, 'ALONSO14', NULL, NULL, NULL, NULL, 68, '2026-04-24 09:11:21', 68, 34);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_idcard` (`idcard`),
  ADD KEY `idx_type` (`log_type`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_machine` (`machine_id`);

--
-- Indexes for table `machines`
--
ALTER TABLE `machines`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `machine_id` (`machine_id`);

--
-- Indexes for table `rewards`
--
ALTER TABLE `rewards`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `temp_transactions`
--
ALTER TABLE `temp_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_machine` (`machine_id`),
  ADD KEY `idx_idcard` (`idcard`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_session` (`transaction_key`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_key` (`transaction_key`),
  ADD KEY `machine_id` (`machine_id`),
  ADD KEY `idx_txn_key` (`transaction_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idcard` (`idcard`),
  ADD KEY `idx_card` (`idcard`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT for table `machines`
--
ALTER TABLE `machines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `rewards`
--
ALTER TABLE `rewards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `temp_transactions`
--
ALTER TABLE `temp_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`machine_id`) REFERENCES `machines` (`machine_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
