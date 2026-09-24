-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Sep 24, 2026 at 07:35 PM
-- Server version: 10.11.19-MariaDB-cll-lve
-- PHP Version: 8.4.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `allthin2_Admin`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance_records`
--

CREATE TABLE `attendance_records` (
  `id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `status` varchar(20) DEFAULT 'present',
  `checked_in_at` datetime DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance_records`
--

INSERT INTO `attendance_records` (`id`, `session_id`, `member_id`, `status`, `checked_in_at`, `notes`) VALUES
(1, 1, 1, 'present', '2026-07-12 01:21:45', NULL),
(2, 1, 2, 'present', '2026-07-12 01:21:45', NULL),
(3, 1, 4, 'present', '2026-07-12 01:21:45', NULL),
(4, 1, 6, 'absent', '2026-07-12 01:21:45', NULL),
(5, 2, 1, 'present', '2026-07-12 01:21:45', NULL),
(6, 2, 2, 'present', '2026-07-12 01:21:45', NULL),
(7, 2, 6, 'present', '2026-07-12 01:21:45', NULL),
(8, 3, 1, 'present', '2026-07-12 01:21:45', NULL),
(9, 3, 2, 'present', '2026-07-12 01:21:45', NULL),
(10, 3, 6, 'present', '2026-07-12 01:21:45', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `attendance_sessions`
--

CREATE TABLE `attendance_sessions` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `type` varchar(50) NOT NULL,
  `session_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance_sessions`
--

INSERT INTO `attendance_sessions` (`id`, `title`, `type`, `session_date`, `start_time`, `location`, `notes`, `created_by`, `created_at`) VALUES
(1, 'Sunday Morning Service', 'service', '2026-07-05', '09:00:00', 'Main Sanctuary', NULL, NULL, '2026-07-12 01:21:45'),
(2, 'Sunday Morning Service', 'service', '2026-07-12', '09:00:00', 'Main Sanctuary', NULL, NULL, '2026-07-12 01:21:45'),
(3, 'Faith Cell Meeting', 'cell_group', '2026-07-09', '18:30:00', 'Westlands', NULL, NULL, '2026-07-12 01:21:45');

-- --------------------------------------------------------

--
-- Table structure for table `cell_groups`
--

CREATE TABLE `cell_groups` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `leader_id` int(11) DEFAULT NULL,
  `meeting_day` varchar(30) DEFAULT NULL,
  `meeting_time` time DEFAULT NULL,
  `location` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cell_groups`
--

INSERT INTO `cell_groups` (`id`, `name`, `leader_id`, `meeting_day`, `meeting_time`, `location`, `is_active`, `created_at`) VALUES
(1, 'Faith Cell - Westlands', 1, 'Wednesday', '18:30:00', 'Kamau Residence', 1, '2026-07-12 01:21:45'),
(2, 'Hope Cell - Karen', 4, 'Tuesday', '19:00:00', 'Ochieng Home', 1, '2026-07-12 01:21:45');

-- --------------------------------------------------------

--
-- Table structure for table `cell_group_members`
--

CREATE TABLE `cell_group_members` (
  `id` int(11) NOT NULL,
  `cell_group_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cell_group_members`
--

INSERT INTO `cell_group_members` (`id`, `cell_group_id`, `member_id`) VALUES
(1, 1, 1),
(2, 1, 2),
(3, 1, 3),
(4, 1, 6),
(5, 2, 4),
(6, 2, 5);

-- --------------------------------------------------------

--
-- Table structure for table `communications`
--

CREATE TABLE `communications` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `channel` varchar(20) NOT NULL,
  `audience` varchar(50) DEFAULT 'all',
  `status` varchar(20) DEFAULT 'draft',
  `scheduled_at` datetime DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `sent_count` int(11) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contributions`
--

CREATE TABLE `contributions` (
  `id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `household_id` int(11) DEFAULT NULL,
  `fund_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT 'cash',
  `transaction_ref` varchar(100) DEFAULT NULL,
  `contribution_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `sms_sent` tinyint(1) DEFAULT 0,
  `recorded_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contributions`
--

INSERT INTO `contributions` (`id`, `member_id`, `household_id`, `fund_id`, `amount`, `payment_method`, `transaction_ref`, `contribution_date`, `notes`, `sms_sent`, `recorded_by`, `created_at`) VALUES
(1, 1, 1, 1, 15000.00, 'mpesa', 'QHK7X2ABCD', '2026-06-12', NULL, 0, NULL, '2026-07-12 01:21:45'),
(2, 1, 1, 2, 2000.00, 'mpesa', 'QHK7X2EFGH', '2026-06-12', NULL, 0, NULL, '2026-07-12 01:21:45'),
(3, 1, 1, 1, 15000.00, 'mpesa', 'QHK8Y3IJKL', '2026-07-05', NULL, 0, NULL, '2026-07-12 01:21:45'),
(4, 4, 2, 1, 20000.00, 'mpesa', 'QHK8Y3MNOP', '2026-07-05', NULL, 0, NULL, '2026-07-12 01:21:45'),
(5, 4, 2, 3, 5000.00, 'mpesa', 'QHK9Z4QRST', '2026-07-09', NULL, 0, NULL, '2026-07-12 01:21:45'),
(6, 6, 3, 1, 12000.00, 'cash', 'CASH-001', '2026-06-28', NULL, 0, NULL, '2026-07-12 01:21:45'),
(7, 6, 3, 2, 1500.00, 'mpesa', 'QHK9Z4UVWX', '2026-07-11', NULL, 0, NULL, '2026-07-12 01:21:45');

-- --------------------------------------------------------

--
-- Table structure for table `finance_budget_lines`
--

CREATE TABLE `finance_budget_lines` (
  `id` int(10) UNSIGNED NOT NULL,
  `budget_year` smallint(5) UNSIGNED NOT NULL COMMENT 'Start year of FY e.g. 2026 for FY 2026/2027',
  `line_type` enum('income','expense') NOT NULL,
  `section` varchar(80) NOT NULL DEFAULT '',
  `label` varchar(160) NOT NULL,
  `account_code` varchar(32) NOT NULL,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 100,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `finance_budget_lines`
--

INSERT INTO `finance_budget_lines` (`id`, `budget_year`, `line_type`, `section`, `label`, `account_code`, `sort_order`, `created_at`, `updated_at`) VALUES
(2, 2026, 'income', 'Incomes', 'Tithe', '001/1/010', 20, '2026-07-18 12:33:40', '2026-07-18 12:33:40'),
(3, 2026, 'income', 'Incomes', 'Donation/Grants-Cash', '001/1/015', 30, '2026-07-18 12:33:40', '2026-07-18 12:33:40'),
(4, 2026, 'income', 'Incomes', 'Donation/Grants-Kind', '001/1/020', 40, '2026-07-18 12:33:40', '2026-07-18 12:33:40'),
(5, 2026, 'income', 'Incomes', 'Soko Sales', '001/1/025', 50, '2026-07-18 12:33:40', '2026-07-18 12:33:40'),
(6, 2026, 'income', 'Incomes', 'Gain on Disposals', '001/1/030', 60, '2026-07-18 12:33:40', '2026-07-18 12:33:40'),
(7, 2026, 'income', 'Incomes', 'Other Incomes', '001/1/035', 70, '2026-07-18 12:33:40', '2026-07-18 12:33:40'),
(8, 2026, 'expense', 'Administration', 'Rent', '001/2/001', 80, '2026-07-18 12:33:40', '2026-07-18 12:33:40'),
(9, 2026, 'expense', 'Administration', 'Water', '001/2/002', 90, '2026-07-18 12:33:40', '2026-07-18 12:33:40'),
(10, 2026, 'expense', 'Administration', 'Electricity', '001/2/003', 100, '2026-07-18 12:33:40', '2026-07-18 12:33:40'),
(11, 2026, 'expense', 'Administration', 'Transport', '001/2/004', 110, '2026-07-18 12:33:40', '2026-07-18 12:33:40'),
(12, 2026, 'expense', 'Administration', 'Insurance', '001/2/005', 120, '2026-07-18 12:33:40', '2026-07-18 12:33:40'),
(13, 2026, 'expense', 'Administration', 'Security', '001/2/006', 130, '2026-07-18 12:33:40', '2026-07-18 12:33:40'),
(14, 2026, 'expense', 'Administration', 'Communication/Telephone', '001/2/007', 140, '2026-07-18 12:33:40', '2026-07-18 12:33:40'),
(15, 2026, 'expense', 'Administration', 'Stationery', '001/2/008', 150, '2026-07-18 12:33:40', '2026-07-18 12:33:40'),
(16, 2026, 'expense', 'Administration', 'Refreshments', '001/2/009', 160, '2026-07-18 12:33:40', '2026-07-18 12:33:40'),
(17, 2026, 'expense', 'Administration', 'Health & Safety(Fumigation/F.Extiguishers)', '001/2/010', 170, '2026-07-18 12:33:40', '2026-07-18 12:33:40'),
(18, 2026, 'expense', 'Administration', 'Hospitality', '001/2/011', 180, '2026-07-18 12:33:40', '2026-07-18 12:33:40'),
(19, 2026, 'expense', 'Administration', 'Detergents & Toiletries', '001/2/012', 190, '2026-07-18 12:33:40', '2026-07-18 12:33:40'),
(20, 2026, 'expense', 'Administration', 'Consultancy Fee', '001/2/013', 200, '2026-07-18 12:33:40', '2026-07-18 12:33:40'),
(21, 2026, 'expense', 'Administration', 'Repair & Maintenance', '001/2/014', 210, '2026-07-18 12:33:40', '2026-07-18 12:33:40'),
(22, 2026, 'expense', 'Administration', 'Licences', '001/2/015', 220, '2026-07-18 12:33:40', '2026-07-18 12:33:40'),
(23, 2026, 'expense', 'Administration', 'Audit Fees', '001/2/016', 230, '2026-07-18 12:33:40', '2026-07-18 12:33:40'),
(24, 2026, 'expense', 'Ministry & Departments', 'K.Kids', '001/3/001', 240, '2026-07-18 12:33:40', '2026-07-18 12:33:40'),
(25, 2026, 'expense', 'Ministry & Departments', 'Worship & Services', '001/3/002', 250, '2026-07-18 12:33:40', '2026-07-18 12:33:40'),
(26, 2026, 'expense', 'Ministry & Departments', 'Discipleship Programes', '001/3/003', 260, '2026-07-18 12:33:40', '2026-07-18 12:33:40'),
(27, 2026, 'expense', 'Ministry & Departments', 'Production, Sound & Lighting', '001/3/004', 270, '2026-07-18 12:33:40', '2026-07-18 12:33:40'),
(28, 2026, 'expense', 'Ministry & Departments', 'Wages', '001/3/005', 280, '2026-07-18 12:33:40', '2026-07-18 12:33:40'),
(29, 2026, 'expense', 'Ministry & Departments', 'Training & Development', '001/3/006', 290, '2026-07-18 12:33:40', '2026-07-18 12:33:40'),
(30, 2026, 'expense', 'Ministry & Departments', 'Pastoral Allowances/ Salaries', '001/3/007', 300, '2026-07-18 12:33:40', '2026-07-18 12:33:40'),
(31, 2026, 'expense', 'Ministry & Departments', 'Pastoral Care', '001/3/008', 310, '2026-07-18 12:33:40', '2026-07-18 12:33:40'),
(32, 2026, 'expense', 'Ministry & Departments', 'Missions & Outreach', '001/3/009', 320, '2026-07-18 12:33:40', '2026-07-18 12:33:40'),
(33, 2026, 'expense', 'Ministry & Departments', 'GPM Remittances', '001/3/010', 330, '2026-07-18 12:33:40', '2026-07-18 12:33:40'),
(34, 2026, 'expense', 'Ministry & Departments', 'Honorarium & Gifts', '001/3/011', 340, '2026-07-18 12:33:40', '2026-07-18 12:33:40'),
(35, 2026, 'expense', 'Ministry & Departments', 'Benovelent', '001/3/012', 350, '2026-07-18 12:33:40', '2026-07-18 12:33:40'),
(36, 2026, 'expense', 'Finance Costs', 'Bank Charges', '001/4/001', 360, '2026-07-18 12:33:40', '2026-07-18 12:33:40'),
(37, 2026, 'expense', 'Finance Costs', 'Mpesa Charges', '001/4/002', 370, '2026-07-18 12:33:40', '2026-07-18 12:33:40');

-- --------------------------------------------------------

--
-- Table structure for table `finance_budget_monthly`
--

CREATE TABLE `finance_budget_monthly` (
  `id` int(10) UNSIGNED NOT NULL,
  `budget_line_id` int(10) UNSIGNED NOT NULL,
  `budget_month` char(7) NOT NULL COMMENT 'YYYY-MM calendar month',
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `finance_budget_monthly`
--

INSERT INTO `finance_budget_monthly` (`id`, `budget_line_id`, `budget_month`, `amount`) VALUES
(1, 8, '2026-04', 55000.00),
(2, 9, '2026-04', 2000.00),
(3, 10, '2026-04', 2000.00),
(4, 14, '2026-04', 2000.00),
(5, 15, '2026-04', 2000.00),
(6, 17, '2026-04', 1000.00),
(7, 19, '2026-04', 2000.00),
(8, 21, '2026-04', 1000.00),
(9, 24, '2026-04', 10000.00),
(10, 25, '2026-04', 20000.00),
(11, 28, '2026-04', 16800.00),
(12, 34, '2026-04', 20000.00),
(13, 36, '2026-04', 1500.00),
(14, 37, '2026-04', 500.00);

-- --------------------------------------------------------

--
-- Table structure for table `finance_collections`
--

CREATE TABLE `finance_collections` (
  `id` int(10) UNSIGNED NOT NULL,
  `collection_date` date NOT NULL,
  `payment_method` enum('paybill','cheque','cash') NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `reference` varchar(255) DEFAULT NULL,
  `fund_type` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `budget_year` smallint(5) UNSIGNED NOT NULL DEFAULT 2026,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `finance_expense_arrears`
--

CREATE TABLE `finance_expense_arrears` (
  `id` int(10) UNSIGNED NOT NULL,
  `expense_item` varchar(255) NOT NULL,
  `category_id` int(10) UNSIGNED DEFAULT NULL,
  `month_incurred` varchar(120) NOT NULL,
  `amount_due` decimal(12,2) NOT NULL DEFAULT 0.00,
  `amount_paid` decimal(12,2) NOT NULL DEFAULT 0.00,
  `date_paid` date DEFAULT NULL,
  `paid_by_ref` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `budget_year` smallint(5) UNSIGNED NOT NULL DEFAULT 2026,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `finance_expense_categories`
--

CREATE TABLE `finance_expense_categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `department_id` int(10) UNSIGNED NOT NULL,
  `slug` varchar(64) NOT NULL,
  `label` varchar(160) NOT NULL,
  `account_code` varchar(32) NOT NULL COMMENT 'e.g. 001/2/001',
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 100,
  `is_system` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `finance_expense_categories`
--

INSERT INTO `finance_expense_categories` (`id`, `department_id`, `slug`, `label`, `account_code`, `sort_order`, `is_system`, `created_at`, `updated_at`) VALUES
(1, 1, 'rent', 'Rent', '001/2/001', 10, 1, '2026-07-12 01:31:44', '2026-07-12 01:31:44'),
(2, 1, 'water', 'Water', '001/2/002', 20, 1, '2026-07-12 01:31:44', '2026-07-12 01:31:44'),
(3, 1, 'electricity', 'Electricity', '001/2/003', 30, 1, '2026-07-12 01:31:44', '2026-07-12 01:31:44'),
(4, 1, 'transport', 'Transport', '001/2/004', 40, 1, '2026-07-12 01:31:44', '2026-07-12 01:31:44'),
(5, 1, 'insurance', 'Insurance', '001/2/005', 50, 1, '2026-07-12 01:31:44', '2026-07-12 01:31:44'),
(6, 1, 'security', 'Security', '001/2/006', 60, 1, '2026-07-12 01:31:44', '2026-07-12 01:31:44'),
(7, 1, 'communication_telephone', 'Communication/Telephone', '001/2/007', 70, 1, '2026-07-12 01:31:44', '2026-07-12 01:31:44'),
(8, 1, 'stationery', 'Stationery', '001/2/008', 80, 1, '2026-07-12 01:31:44', '2026-07-12 01:31:44'),
(9, 1, 'refreshments', 'Refreshments', '001/2/009', 90, 1, '2026-07-12 01:31:44', '2026-07-12 01:31:44'),
(10, 1, 'health_safety_fumigation_f_extinguishers', 'Health & Safety (Fumigation/F.Extinguishers)', '001/2/010', 100, 1, '2026-07-12 01:31:44', '2026-07-12 01:31:44'),
(11, 1, 'hospitality', 'Hospitality', '001/2/011', 110, 1, '2026-07-12 01:31:44', '2026-07-12 01:31:44'),
(12, 1, 'detergents_toiletries', 'Detergents & Toiletries', '001/2/012', 120, 1, '2026-07-12 01:31:44', '2026-07-12 01:31:44'),
(13, 1, 'consultancy_fee', 'Consultancy Fee', '001/2/013', 130, 1, '2026-07-12 01:31:44', '2026-07-12 01:31:44'),
(14, 1, 'repair_maintenance', 'Repair & Maintenance', '001/2/014', 140, 1, '2026-07-12 01:31:44', '2026-07-12 01:31:44'),
(15, 1, 'licences', 'Licences', '001/2/015', 150, 1, '2026-07-12 01:31:44', '2026-07-12 01:31:44'),
(16, 1, 'audit_fees', 'Audit Fees', '001/2/016', 160, 1, '2026-07-12 01:31:44', '2026-07-12 01:31:44'),
(17, 2, 'k_kids', 'K.Kids', '001/1/001', 10, 1, '2026-07-12 01:31:44', '2026-07-12 01:31:44'),
(18, 3, 'worship_services', 'Worship & Services', '001/3/001', 10, 1, '2026-07-12 01:31:44', '2026-07-12 01:31:44'),
(19, 4, 'discipleship_programes', 'Discipleship Programes', '001/4/001', 10, 1, '2026-07-12 01:31:44', '2026-07-12 01:31:44'),
(20, 5, 'production_sound_lighting', 'Production, Sound & Lighting', '001/5/001', 10, 1, '2026-07-12 01:31:44', '2026-07-12 01:31:44'),
(21, 6, 'wages', 'Wages', '001/6/001', 10, 1, '2026-07-12 01:31:44', '2026-07-12 01:31:44'),
(22, 7, 'training_development', 'Training & Development', '001/7/001', 10, 1, '2026-07-12 01:31:44', '2026-07-12 01:31:44'),
(23, 8, 'pastoral_allowances_salaries', 'Pastoral Allowances/ Salaries', '001/8/001', 10, 1, '2026-07-12 01:31:44', '2026-07-12 01:31:44'),
(24, 9, 'pastoral_care', 'Pastoral Care', '001/9/001', 10, 1, '2026-07-12 01:31:44', '2026-07-12 01:31:44'),
(25, 10, 'missions_outreach', 'Missions & Outreach', '001/10/001', 10, 1, '2026-07-12 01:31:44', '2026-07-12 01:31:44'),
(26, 11, 'gpm_remittances', 'GPM Remittances', '001/11/001', 10, 1, '2026-07-12 01:31:44', '2026-07-12 01:31:44'),
(27, 12, 'honorarium_gifts', 'Honorarium & Gifts', '001/12/001', 10, 1, '2026-07-12 01:31:44', '2026-07-12 01:31:44'),
(28, 13, 'benovelent', 'Benovelent', '001/13/001', 10, 1, '2026-07-12 01:31:44', '2026-07-12 01:31:44'),
(29, 3, 'keyboardist', 'Keyboardist', '001/3/002', 20, 1, '2026-07-12 01:31:44', '2026-07-12 01:31:44'),
(30, 3, 'drummer', 'Drummer', '001/3/003', 30, 1, '2026-07-12 01:31:44', '2026-07-12 01:31:44'),
(31, 3, 'bassist', 'Bassist', '001/3/004', 40, 1, '2026-07-12 01:31:44', '2026-07-12 01:31:44'),
(32, 2, 'kids_teacher', 'Kids Teacher', '001/1/002', 20, 1, '2026-07-12 01:31:44', '2026-07-12 01:31:44'),
(33, 6, 'caretaker', 'Caretaker', '001/6/002', 20, 1, '2026-07-12 01:31:44', '2026-07-12 01:31:44'),
(34, 1, 'kplc_tokens', 'KPLC Tokens', '001/2/017', 170, 1, '2026-07-12 01:31:44', '2026-07-12 01:31:44'),
(35, 1, 'billboards', 'Billboards', '001/2/018', 180, 0, '2026-08-30 15:16:01', '2026-08-30 15:16:01');

-- --------------------------------------------------------

--
-- Table structure for table `finance_expense_departments`
--

CREATE TABLE `finance_expense_departments` (
  `id` int(10) UNSIGNED NOT NULL,
  `slug` varchar(64) NOT NULL,
  `label` varchar(160) NOT NULL,
  `expense_group` varchar(32) NOT NULL DEFAULT 'ministry_departments',
  `code_prefix` varchar(32) NOT NULL COMMENT 'e.g. 001/2',
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 100,
  `is_system` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `finance_expense_departments`
--

INSERT INTO `finance_expense_departments` (`id`, `slug`, `label`, `expense_group`, `code_prefix`, `sort_order`, `is_system`, `created_at`, `updated_at`) VALUES
(1, 'administration', 'Administration', 'admin_expenses', '001/2', 10, 1, '2026-07-12 01:31:44', '2026-07-12 01:31:44'),
(2, 'k_kids', 'K.Kids', 'ministry_departments', '001/1', 10, 1, '2026-07-12 01:31:44', '2026-07-12 01:31:44'),
(3, 'worship_services', 'Worship & Services', 'ministry_departments', '001/3', 20, 1, '2026-07-12 01:31:44', '2026-07-12 01:31:44'),
(4, 'discipleship_programmes', 'Discipleship Programes', 'ministry_departments', '001/4', 30, 1, '2026-07-12 01:31:44', '2026-07-12 01:31:44'),
(5, 'production_sound_lighting', 'Production, Sound & Lighting', 'ministry_departments', '001/5', 40, 1, '2026-07-12 01:31:44', '2026-07-12 01:31:44'),
(6, 'wages', 'Wages', 'ministry_departments', '001/6', 50, 1, '2026-07-12 01:31:44', '2026-07-12 01:31:44'),
(7, 'training_development', 'Training & Development', 'ministry_departments', '001/7', 60, 1, '2026-07-12 01:31:44', '2026-07-12 01:31:44'),
(8, 'pastoral_allowances_salaries', 'Pastoral Allowances/ Salaries', 'ministry_departments', '001/8', 70, 1, '2026-07-12 01:31:44', '2026-07-12 01:31:44'),
(9, 'pastoral_care', 'Pastoral Care', 'ministry_departments', '001/9', 80, 1, '2026-07-12 01:31:44', '2026-07-12 01:31:44'),
(10, 'missions_outreach', 'Missions & Outreach', 'ministry_departments', '001/10', 90, 1, '2026-07-12 01:31:44', '2026-07-12 01:31:44'),
(11, 'gpm_remittances', 'GPM Remittances', 'ministry_departments', '001/11', 100, 1, '2026-07-12 01:31:44', '2026-07-12 01:31:44'),
(12, 'honorarium_gifts', 'Honorarium & Gifts', 'ministry_departments', '001/12', 110, 1, '2026-07-12 01:31:44', '2026-07-12 01:31:44'),
(13, 'benevolent', 'Benovelent', 'ministry_departments', '001/13', 120, 1, '2026-07-12 01:31:44', '2026-07-12 01:31:44');

-- --------------------------------------------------------

--
-- Table structure for table `finance_sunday_sessions`
--

CREATE TABLE `finance_sunday_sessions` (
  `week_date` date NOT NULL COMMENT 'Sunday service date',
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `finance_weekly_categories`
--

CREATE TABLE `finance_weekly_categories` (
  `id` int(10) UNSIGNED NOT NULL,
  `slug` varchar(50) NOT NULL,
  `label` varchar(120) NOT NULL,
  `hint` varchar(255) DEFAULT '',
  `department_id` int(10) UNSIGNED DEFAULT NULL,
  `expense_category_id` int(10) UNSIGNED DEFAULT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 100,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `finance_weekly_categories`
--

INSERT INTO `finance_weekly_categories` (`id`, `slug`, `label`, `hint`, `department_id`, `expense_category_id`, `is_system`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'keyboardist', 'Keyboardist', 'Sunday allowance', 3, NULL, 1, 10, '2026-07-12 01:31:44', '2026-07-12 01:31:44'),
(2, 'drummer', 'Drummer', 'Sunday allowance', 3, NULL, 1, 20, '2026-07-12 01:31:44', '2026-07-12 01:31:44'),
(3, 'bassist', 'Bassist', 'Sunday allowance', 3, NULL, 1, 30, '2026-07-12 01:31:44', '2026-07-12 01:31:44'),
(4, 'kids_teacher', 'Kids Teacher', 'Sunday allowance', 2, NULL, 1, 40, '2026-07-12 01:31:44', '2026-07-12 01:31:44'),
(5, 'caretaker', 'Caretaker', 'Sunday allowance', 6, NULL, 1, 50, '2026-07-12 01:31:44', '2026-07-12 01:31:44'),
(6, 'honorarium_gifts', 'Honorarium & Gifts', '', 12, NULL, 1, 60, '2026-07-12 01:31:44', '2026-07-12 01:31:44'),
(7, 'kplc_tokens', 'KPLC Tokens', 'Weekly usage', 1, NULL, 1, 70, '2026-07-12 01:31:44', '2026-07-12 01:31:44');

-- --------------------------------------------------------

--
-- Table structure for table `finance_weekly_collections`
--

CREATE TABLE `finance_weekly_collections` (
  `id` int(10) UNSIGNED NOT NULL,
  `week_date` date NOT NULL COMMENT 'Sunday service date',
  `payment_method` enum('paybill','cheque','cash') NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `finance_weekly_collections`
--

INSERT INTO `finance_weekly_collections` (`id`, `week_date`, `payment_method`, `amount`, `notes`, `created_at`, `updated_at`) VALUES
(4, '2026-03-01', 'paybill', 40.00, NULL, '2026-08-30 16:01:06', '2026-08-30 16:01:06'),
(5, '2026-03-08', 'paybill', 50.00, NULL, '2026-08-30 16:01:06', '2026-08-30 16:01:06'),
(6, '2026-03-15', 'paybill', 60.00, NULL, '2026-08-30 16:01:06', '2026-08-30 16:01:06'),
(7, '2026-03-22', 'paybill', 70.00, NULL, '2026-08-30 16:01:06', '2026-08-30 16:01:06');

-- --------------------------------------------------------

--
-- Table structure for table `finance_weekly_expenses`
--

CREATE TABLE `finance_weekly_expenses` (
  `id` int(10) UNSIGNED NOT NULL,
  `week_date` date NOT NULL COMMENT 'Sunday service date',
  `category_slug` varchar(50) NOT NULL,
  `category_label` varchar(120) NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `notes` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `funds`
--

CREATE TABLE `funds` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(30) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `funds`
--

INSERT INTO `funds` (`id`, `name`, `code`, `description`, `is_active`, `created_at`) VALUES
(1, 'Tithe', 'TITHE', 'Regular tithe contributions', 1, '2026-07-12 01:21:45'),
(2, 'General Offering', 'OFFERING', 'Sunday and special offerings', 1, '2026-07-12 01:21:45'),
(3, 'Building Fund', 'BUILDING', 'Church building project', 1, '2026-07-12 01:21:45'),
(4, 'Missions', 'MISSIONS', 'Missions and outreach', 1, '2026-07-12 01:21:45'),
(5, 'Youth Ministry', 'YOUTH', 'Youth programs and activities', 1, '2026-07-12 01:21:45');

-- --------------------------------------------------------

--
-- Table structure for table `households`
--

CREATE TABLE `households` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `head_member_id` int(11) DEFAULT NULL,
  `anniversary_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `households`
--

INSERT INTO `households` (`id`, `name`, `address`, `city`, `phone`, `head_member_id`, `anniversary_date`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'Kamau Family', '45 Oak Street', 'Nairobi', '+254712345678', 1, NULL, NULL, '2026-07-12 01:21:45', '2026-07-12 01:21:45'),
(2, 'Ochieng Family', '12 River Road', 'Nairobi', '+254723456789', 4, NULL, NULL, '2026-07-12 01:21:45', '2026-07-12 01:21:45'),
(3, 'Wanjiku Family', '8 Hill View', 'Nairobi', '+254734567890', 6, NULL, NULL, '2026-07-12 01:21:45', '2026-07-12 01:21:45');

-- --------------------------------------------------------

--
-- Table structure for table `household_children`
--

CREATE TABLE `household_children` (
  `id` int(11) NOT NULL,
  `household_id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `age` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_items`
--

CREATE TABLE `inventory_items` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `unit` varchar(50) DEFAULT 'pcs',
  `location` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `members`
--

CREATE TABLE `members` (
  `id` int(11) NOT NULL,
  `household_id` int(11) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `gender` varchar(100) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `marital_status` varchar(30) DEFAULT NULL,
  `spouse_name` varchar(150) DEFAULT NULL,
  `residence` varchar(255) DEFAULT NULL,
  `county` varchar(100) DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `employer` varchar(150) DEFAULT NULL,
  `emergency_contact_name` varchar(150) DEFAULT NULL,
  `emergency_contact_phone` varchar(30) DEFAULT NULL,
  `how_heard_about_us` varchar(100) DEFAULT NULL,
  `previous_church` varchar(200) DEFAULT NULL,
  `baptized` tinyint(1) DEFAULT 0,
  `baptism_date` date DEFAULT NULL,
  `wish_to_be_baptized` tinyint(1) DEFAULT 0,
  `ministry_interests` text DEFAULT NULL,
  `skills_talents` text DEFAULT NULL,
  `member_notes` text DEFAULT NULL,
  `photo_url` varchar(255) DEFAULT NULL,
  `is_head_of_household` tinyint(1) DEFAULT 0,
  `membership_status` varchar(30) DEFAULT 'active',
  `joined_date` date DEFAULT NULL,
  `onboarding_token` varchar(64) DEFAULT NULL,
  `onboarding_completed` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `members`
--

INSERT INTO `members` (`id`, `household_id`, `first_name`, `last_name`, `email`, `phone`, `gender`, `date_of_birth`, `marital_status`, `spouse_name`, `residence`, `county`, `occupation`, `employer`, `emergency_contact_name`, `emergency_contact_phone`, `how_heard_about_us`, `previous_church`, `baptized`, `baptism_date`, `wish_to_be_baptized`, `ministry_interests`, `skills_talents`, `member_notes`, `photo_url`, `is_head_of_household`, `membership_status`, `joined_date`, `onboarding_token`, `onboarding_completed`, `created_at`, `updated_at`) VALUES
(1, 1, 'James', 'Kamau', 'james.kamau@email.com', '+254712345678', 'male', '1985-03-15', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL, NULL, 1, 'active', '2020-01-12', NULL, 1, '2026-07-12 01:21:45', '2026-07-12 01:21:45'),
(2, 1, 'Grace', 'Kamau', 'grace.kamau@email.com', '+254712345679', 'female', '1988-07-22', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL, NULL, 0, 'active', '2020-01-12', NULL, 1, '2026-07-12 01:21:45', '2026-07-12 01:21:45'),
(3, 1, 'David', 'Kamau', 'david.kamau@email.com', NULL, 'male', '2010-11-05', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL, NULL, 0, 'active', '2020-01-12', NULL, 0, '2026-07-12 01:21:45', '2026-07-12 01:21:45'),
(4, 2, 'Peter', 'Ochieng', 'peter.ochieng@email.com', '+254723456789', 'male', '1978-09-30', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL, NULL, 1, 'active', '2019-06-20', NULL, 1, '2026-07-12 01:21:45', '2026-07-12 01:21:45'),
(5, 2, 'Mary', 'Ochieng', 'mary.ochieng@email.com', '+254723456790', 'female', '1982-12-14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL, NULL, 0, 'active', '2019-06-20', NULL, 1, '2026-07-12 01:21:45', '2026-07-12 01:21:45'),
(6, 3, 'Faith', 'Wanjiku', 'faith.wanjiku@email.com', '+254734567890', 'female', '1990-05-08', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, 0, NULL, NULL, NULL, NULL, 1, 'active', '2021-03-01', NULL, 1, '2026-07-12 01:21:45', '2026-07-12 01:21:45');

-- --------------------------------------------------------

--
-- Table structure for table `ministries`
--

CREATE TABLE `ministries` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `leader_id` int(11) DEFAULT NULL,
  `meeting_day` varchar(30) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ministries`
--

INSERT INTO `ministries` (`id`, `name`, `description`, `leader_id`, `meeting_day`, `is_active`, `created_at`) VALUES
(1, 'Praise & Worship', 'Music ministry and worship team', 6, 'Thursday', 1, '2026-07-12 01:21:45'),
(2, 'Ushers', 'Ushering and guest services', 1, 'Saturday', 1, '2026-07-12 01:21:45'),
(3, 'Youth Ministry', 'Teens and young adults', 4, 'Friday', 1, '2026-07-12 01:21:45'),
(4, 'Sunday School', 'Children ministry', 2, 'Sunday', 1, '2026-07-12 01:21:45');

-- --------------------------------------------------------

--
-- Table structure for table `ministry_members`
--

CREATE TABLE `ministry_members` (
  `id` int(11) NOT NULL,
  `ministry_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `role` varchar(50) DEFAULT 'member',
  `joined_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ministry_members`
--

INSERT INTO `ministry_members` (`id`, `ministry_id`, `member_id`, `role`, `joined_date`) VALUES
(1, 1, 6, 'leader', NULL),
(2, 1, 2, 'member', NULL),
(3, 2, 1, 'leader', NULL),
(4, 2, 4, 'member', NULL),
(5, 3, 4, 'leader', NULL),
(6, 3, 3, 'member', NULL),
(7, 4, 2, 'leader', NULL),
(8, 4, 5, 'member', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `mobile_money_statements`
--

CREATE TABLE `mobile_money_statements` (
  `id` int(11) NOT NULL,
  `provider` varchar(50) DEFAULT NULL,
  `transaction_ref` varchar(100) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `amount` decimal(12,2) DEFAULT NULL,
  `transaction_date` datetime DEFAULT NULL,
  `raw_payload` text DEFAULT NULL,
  `matched_contribution_id` int(11) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `onboarding_qr_codes`
--

CREATE TABLE `onboarding_qr_codes` (
  `id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `label` varchar(150) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `scan_count` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `onboarding_qr_codes`
--

INSERT INTO `onboarding_qr_codes` (`id`, `token`, `label`, `is_active`, `scan_count`, `created_at`) VALUES
(1, 'church-onboard-2026', 'Main Entrance QR', 1, 0, '2026-07-12 01:21:45');

-- --------------------------------------------------------

--
-- Table structure for table `pledges`
--

CREATE TABLE `pledges` (
  `id` int(11) NOT NULL,
  `campaign_id` int(11) NOT NULL,
  `member_id` int(11) NOT NULL,
  `pledged_amount` decimal(12,2) NOT NULL,
  `amount_paid` decimal(12,2) DEFAULT 0.00,
  `pledge_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pledges`
--

INSERT INTO `pledges` (`id`, `campaign_id`, `member_id`, `pledged_amount`, `amount_paid`, `pledge_date`, `notes`, `created_at`) VALUES
(1, 1, 1, 50000.00, 15000.00, '2026-05-28', NULL, '2026-07-12 01:21:45'),
(2, 1, 4, 75000.00, 5000.00, '2026-06-02', NULL, '2026-07-12 01:21:45'),
(3, 1, 6, 30000.00, 12000.00, '2026-06-07', NULL, '2026-07-12 01:21:45'),
(4, 2, 1, 200000.00, 0.00, '2026-06-22', NULL, '2026-07-12 01:21:45'),
(5, 2, 4, 150000.00, 5000.00, '2026-06-27', NULL, '2026-07-12 01:21:45');

-- --------------------------------------------------------

--
-- Table structure for table `pledge_campaigns`
--

CREATE TABLE `pledge_campaigns` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `target_amount` decimal(14,2) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `fund_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pledge_campaigns`
--

INSERT INTO `pledge_campaigns` (`id`, `title`, `description`, `target_amount`, `start_date`, `end_date`, `fund_id`, `is_active`, `created_at`) VALUES
(1, 'New Worship Instruments', 'Fundraising for new keyboard, drums, and sound equipment', 500000.00, '2026-05-13', '2026-10-10', 5, 1, '2026-07-12 01:21:45'),
(2, 'Church Van', 'Purchase a van for outreach and member transport', 1500000.00, '2026-06-12', '2027-01-08', 3, 1, '2026-07-12 01:21:45');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'finance_schema_version', '1', '2026-07-12 01:31:44'),
(2, 'finance_budget_schema_version', '1', '2026-07-18 12:33:40'),
(3, 'church_name', 'Kingdomcity church Nanyuki', '2026-08-16 03:13:34'),
(4, 'church_address', 'Nanyuki,Kenya', '2026-07-30 10:24:27'),
(5, 'church_phone', '', '2026-07-30 10:24:27'),
(6, 'church_logo_url', '', '2026-07-30 10:24:27'),
(7, 'church_logo_path', 'uploads/branding/logo.png', '2026-08-16 03:13:08');

-- --------------------------------------------------------

--
-- Table structure for table `sms_logs`
--

CREATE TABLE `sms_logs` (
  `id` int(11) NOT NULL,
  `member_id` int(11) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `provider_ref` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_members`
--

CREATE TABLE `staff_members` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `role_title` varchar(150) DEFAULT NULL,
  `department` varchar(150) DEFAULT NULL,
  `phone` varchar(64) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `member_id` int(11) DEFAULT NULL,
  `username` varchar(80) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `display_name` varchar(150) DEFAULT NULL,
  `avatar_path` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(30) DEFAULT 'member',
  `magic_link_token` varchar(64) DEFAULT NULL,
  `magic_link_expires` datetime DEFAULT NULL,
  `email_verified_at` datetime DEFAULT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `member_id`, `username`, `email`, `phone`, `display_name`, `avatar_path`, `password`, `role`, `magic_link_token`, `magic_link_expires`, `email_verified_at`, `last_login_at`, `created_at`) VALUES
(1, NULL, 'Admin', 'admin@kingdomcitychurchnanyuki.org', NULL, NULL, NULL, '$2y$10$u2w22R5Fd5nV52befQsRO.ig4yZNJklC8EzDTVJgwrufDRfKFDtjG', 'admin', NULL, NULL, '2026-07-12 01:21:45', '2026-09-24 19:28:55', '2026-07-12 01:21:45'),
(2, 1, 'james.kamau', 'james.kamau@email.com', NULL, NULL, NULL, '$2y$10$u2w22R5Fd5nV52befQsRO.ig4yZNJklC8EzDTVJgwrufDRfKFDtjG', 'member', NULL, NULL, '2026-07-12 01:21:45', NULL, '2026-07-12 01:21:45'),
(3, 4, 'peter.ochieng', 'peter.ochieng@email.com', NULL, NULL, NULL, '$2y$10$u2w22R5Fd5nV52befQsRO.ig4yZNJklC8EzDTVJgwrufDRfKFDtjG', 'member', NULL, NULL, '2026-07-12 01:21:45', NULL, '2026-07-12 01:21:45'),
(4, 6, 'faith.wanjiku', 'faith.wanjiku@email.com', NULL, NULL, NULL, '$2y$10$u2w22R5Fd5nV52befQsRO.ig4yZNJklC8EzDTVJgwrufDRfKFDtjG', 'member', NULL, NULL, '2026-07-12 01:21:45', NULL, '2026-07-12 01:21:45');

-- --------------------------------------------------------

--
-- Table structure for table `visitor_feedback`
--

CREATE TABLE `visitor_feedback` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `spouse_name` varchar(150) DEFAULT NULL,
  `children_names` text DEFAULT NULL,
  `phone` varchar(30) NOT NULL,
  `email` varchar(150) NOT NULL,
  `review` text DEFAULT NULL,
  `how_heard_about_us` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance_records`
--
ALTER TABLE `attendance_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_session_member` (`session_id`,`member_id`),
  ADD KEY `idx_attendance_member` (`member_id`);

--
-- Indexes for table `attendance_sessions`
--
ALTER TABLE `attendance_sessions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cell_groups`
--
ALTER TABLE `cell_groups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `leader_id` (`leader_id`);

--
-- Indexes for table `cell_group_members`
--
ALTER TABLE `cell_group_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_cell_member` (`cell_group_id`,`member_id`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexes for table `communications`
--
ALTER TABLE `communications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contributions`
--
ALTER TABLE `contributions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `household_id` (`household_id`),
  ADD KEY `fund_id` (`fund_id`),
  ADD KEY `idx_contributions_member` (`member_id`),
  ADD KEY `idx_contributions_date` (`contribution_date`);

--
-- Indexes for table `finance_budget_lines`
--
ALTER TABLE `finance_budget_lines`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_budget_year_code` (`budget_year`,`account_code`),
  ADD KEY `idx_budget_year_type` (`budget_year`,`line_type`);

--
-- Indexes for table `finance_budget_monthly`
--
ALTER TABLE `finance_budget_monthly`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_line_month` (`budget_line_id`,`budget_month`);

--
-- Indexes for table `finance_collections`
--
ALTER TABLE `finance_collections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_collection_date` (`collection_date`),
  ADD KEY `idx_payment_method` (`payment_method`),
  ADD KEY `idx_budget_year` (`budget_year`);

--
-- Indexes for table `finance_expense_arrears`
--
ALTER TABLE `finance_expense_arrears`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_budget_year` (`budget_year`),
  ADD KEY `idx_category_id` (`category_id`);

--
-- Indexes for table `finance_expense_categories`
--
ALTER TABLE `finance_expense_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_slug` (`slug`),
  ADD UNIQUE KEY `uk_account_code` (`account_code`),
  ADD KEY `idx_department` (`department_id`);

--
-- Indexes for table `finance_expense_departments`
--
ALTER TABLE `finance_expense_departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_slug` (`slug`),
  ADD UNIQUE KEY `uk_code_prefix` (`code_prefix`),
  ADD KEY `idx_expense_group` (`expense_group`);

--
-- Indexes for table `finance_sunday_sessions`
--
ALTER TABLE `finance_sunday_sessions`
  ADD PRIMARY KEY (`week_date`);

--
-- Indexes for table `finance_weekly_categories`
--
ALTER TABLE `finance_weekly_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_slug` (`slug`),
  ADD KEY `idx_weekly_department_id` (`department_id`),
  ADD KEY `idx_weekly_expense_category_id` (`expense_category_id`);

--
-- Indexes for table `finance_weekly_collections`
--
ALTER TABLE `finance_weekly_collections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_week_method` (`week_date`,`payment_method`),
  ADD KEY `idx_week_date` (`week_date`);

--
-- Indexes for table `finance_weekly_expenses`
--
ALTER TABLE `finance_weekly_expenses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_week_category` (`week_date`,`category_slug`),
  ADD KEY `idx_week_date` (`week_date`);

--
-- Indexes for table `funds`
--
ALTER TABLE `funds`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `households`
--
ALTER TABLE `households`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `household_children`
--
ALTER TABLE `household_children`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_household_children_household` (`household_id`);

--
-- Indexes for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `members`
--
ALTER TABLE `members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `onboarding_token` (`onboarding_token`),
  ADD KEY `idx_members_household` (`household_id`),
  ADD KEY `idx_members_email` (`email`);

--
-- Indexes for table `ministries`
--
ALTER TABLE `ministries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `leader_id` (`leader_id`);

--
-- Indexes for table `ministry_members`
--
ALTER TABLE `ministry_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_ministry_member` (`ministry_id`,`member_id`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexes for table `mobile_money_statements`
--
ALTER TABLE `mobile_money_statements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_ref` (`transaction_ref`),
  ADD KEY `matched_contribution_id` (`matched_contribution_id`);

--
-- Indexes for table `onboarding_qr_codes`
--
ALTER TABLE `onboarding_qr_codes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`);

--
-- Indexes for table `pledges`
--
ALTER TABLE `pledges`
  ADD PRIMARY KEY (`id`),
  ADD KEY `campaign_id` (`campaign_id`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexes for table `pledge_campaigns`
--
ALTER TABLE `pledge_campaigns`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fund_id` (`fund_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `sms_logs`
--
ALTER TABLE `sms_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staff_members`
--
ALTER TABLE `staff_members`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_staff_status` (`status`),
  ADD KEY `idx_staff_department` (`department`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `uk_users_username` (`username`),
  ADD KEY `member_id` (`member_id`);

--
-- Indexes for table `visitor_feedback`
--
ALTER TABLE `visitor_feedback`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance_records`
--
ALTER TABLE `attendance_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `attendance_sessions`
--
ALTER TABLE `attendance_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `cell_groups`
--
ALTER TABLE `cell_groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `cell_group_members`
--
ALTER TABLE `cell_group_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `communications`
--
ALTER TABLE `communications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contributions`
--
ALTER TABLE `contributions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `finance_budget_lines`
--
ALTER TABLE `finance_budget_lines`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `finance_budget_monthly`
--
ALTER TABLE `finance_budget_monthly`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `finance_collections`
--
ALTER TABLE `finance_collections`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `finance_expense_arrears`
--
ALTER TABLE `finance_expense_arrears`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `finance_expense_categories`
--
ALTER TABLE `finance_expense_categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `finance_expense_departments`
--
ALTER TABLE `finance_expense_departments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `finance_weekly_categories`
--
ALTER TABLE `finance_weekly_categories`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `finance_weekly_collections`
--
ALTER TABLE `finance_weekly_collections`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `finance_weekly_expenses`
--
ALTER TABLE `finance_weekly_expenses`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `funds`
--
ALTER TABLE `funds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `households`
--
ALTER TABLE `households`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `household_children`
--
ALTER TABLE `household_children`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_items`
--
ALTER TABLE `inventory_items`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `members`
--
ALTER TABLE `members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `ministries`
--
ALTER TABLE `ministries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `ministry_members`
--
ALTER TABLE `ministry_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `mobile_money_statements`
--
ALTER TABLE `mobile_money_statements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `onboarding_qr_codes`
--
ALTER TABLE `onboarding_qr_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pledges`
--
ALTER TABLE `pledges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `pledge_campaigns`
--
ALTER TABLE `pledge_campaigns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `sms_logs`
--
ALTER TABLE `sms_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_members`
--
ALTER TABLE `staff_members`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `visitor_feedback`
--
ALTER TABLE `visitor_feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance_records`
--
ALTER TABLE `attendance_records`
  ADD CONSTRAINT `attendance_records_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `attendance_sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_records_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cell_groups`
--
ALTER TABLE `cell_groups`
  ADD CONSTRAINT `cell_groups_ibfk_1` FOREIGN KEY (`leader_id`) REFERENCES `members` (`id`);

--
-- Constraints for table `cell_group_members`
--
ALTER TABLE `cell_group_members`
  ADD CONSTRAINT `cell_group_members_ibfk_1` FOREIGN KEY (`cell_group_id`) REFERENCES `cell_groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cell_group_members_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `contributions`
--
ALTER TABLE `contributions`
  ADD CONSTRAINT `contributions_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`),
  ADD CONSTRAINT `contributions_ibfk_2` FOREIGN KEY (`household_id`) REFERENCES `households` (`id`),
  ADD CONSTRAINT `contributions_ibfk_3` FOREIGN KEY (`fund_id`) REFERENCES `funds` (`id`);

--
-- Constraints for table `finance_budget_monthly`
--
ALTER TABLE `finance_budget_monthly`
  ADD CONSTRAINT `fk_budget_monthly_line` FOREIGN KEY (`budget_line_id`) REFERENCES `finance_budget_lines` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `finance_expense_categories`
--
ALTER TABLE `finance_expense_categories`
  ADD CONSTRAINT `fk_expense_category_department` FOREIGN KEY (`department_id`) REFERENCES `finance_expense_departments` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `household_children`
--
ALTER TABLE `household_children`
  ADD CONSTRAINT `household_children_ibfk_1` FOREIGN KEY (`household_id`) REFERENCES `households` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `members`
--
ALTER TABLE `members`
  ADD CONSTRAINT `members_ibfk_1` FOREIGN KEY (`household_id`) REFERENCES `households` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `ministries`
--
ALTER TABLE `ministries`
  ADD CONSTRAINT `ministries_ibfk_1` FOREIGN KEY (`leader_id`) REFERENCES `members` (`id`);

--
-- Constraints for table `ministry_members`
--
ALTER TABLE `ministry_members`
  ADD CONSTRAINT `ministry_members_ibfk_1` FOREIGN KEY (`ministry_id`) REFERENCES `ministries` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ministry_members_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `mobile_money_statements`
--
ALTER TABLE `mobile_money_statements`
  ADD CONSTRAINT `mobile_money_statements_ibfk_1` FOREIGN KEY (`matched_contribution_id`) REFERENCES `contributions` (`id`);

--
-- Constraints for table `pledges`
--
ALTER TABLE `pledges`
  ADD CONSTRAINT `pledges_ibfk_1` FOREIGN KEY (`campaign_id`) REFERENCES `pledge_campaigns` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pledges_ibfk_2` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pledge_campaigns`
--
ALTER TABLE `pledge_campaigns`
  ADD CONSTRAINT `pledge_campaigns_ibfk_1` FOREIGN KEY (`fund_id`) REFERENCES `funds` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
