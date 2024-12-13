-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 12, 2024 at 08:16 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `payroll`
--

-- --------------------------------------------------------

--
-- Table structure for table `allowances`
--

CREATE TABLE `allowances` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `is_taxable` tinyint(1) UNSIGNED NOT NULL,
  `frequency` enum('Weekly','Bi-weekly','Semi-monthly','Monthly') NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive','Archived') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(10) UNSIGNED NOT NULL,
  `work_schedule_id` int(10) UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `check_in_time` datetime NOT NULL,
  `check_out_time` datetime DEFAULT NULL,
  `total_break_duration_in_minutes` int(10) UNSIGNED DEFAULT NULL,
  `total_hours_worked` decimal(5,2) DEFAULT NULL,
  `late_check_in` int(10) UNSIGNED DEFAULT NULL,
  `early_check_out` int(10) UNSIGNED DEFAULT NULL,
  `overtime_hours` decimal(5,2) DEFAULT NULL,
  `is_overtime_approved` tinyint(1) UNSIGNED DEFAULT NULL,
  `attendance_status` varchar(25) NOT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `work_schedule_id`, `date`, `check_in_time`, `check_out_time`, `total_break_duration_in_minutes`, `total_hours_worked`, `late_check_in`, `early_check_out`, `overtime_hours`, `is_overtime_approved`, `attendance_status`, `remarks`, `created_at`, `updated_at`) VALUES
(1, 1, '2024-12-02', '2024-12-02 08:00:00', '2024-12-02 17:00:00', 120, 7.00, 0, 0, 0.00, NULL, 'Present', NULL, '2024-12-12 09:35:17', '2024-12-12 09:35:37'),
(2, 1, '2024-11-28', '2024-11-28 08:00:00', '2024-11-28 17:00:00', 120, 7.00, 0, 0, 0.00, NULL, 'Present', NULL, '2024-12-12 13:40:49', '2024-12-12 13:40:49'),
(3, 1, '2024-11-27', '2024-11-27 08:00:00', '2024-11-27 17:00:00', 120, 7.00, 0, 0, 0.00, NULL, 'Present', NULL, '2024-12-12 13:44:29', '2024-12-12 13:44:29'),
(4, 3, '2024-11-27', '2024-11-27 22:00:00', '2024-11-28 06:00:00', 120, 6.00, 0, 0, 0.00, NULL, 'Present', NULL, '2024-12-12 13:44:49', '2024-12-12 13:44:49');

-- --------------------------------------------------------

--
-- Table structure for table `break_schedules`
--

CREATE TABLE `break_schedules` (
  `id` int(10) UNSIGNED NOT NULL,
  `work_schedule_id` int(10) UNSIGNED NOT NULL,
  `break_type_id` int(10) UNSIGNED NOT NULL,
  `start_time` datetime DEFAULT NULL,
  `is_flexible` tinyint(1) NOT NULL,
  `earliest_start_time` datetime DEFAULT NULL,
  `latest_end_time` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `break_schedules`
--

INSERT INTO `break_schedules` (`id`, `work_schedule_id`, `break_type_id`, `start_time`, `is_flexible`, `earliest_start_time`, `latest_end_time`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 1, '1970-01-01 12:00:00', 0, NULL, NULL, '2024-12-08 16:29:56', '2024-12-08 16:29:56', NULL),
(2, 1, 1, '1970-01-01 15:00:00', 0, NULL, NULL, '2024-12-10 08:26:13', '2024-12-10 08:35:41', NULL),
(3, 3, 1, '1970-01-02 01:00:00', 0, NULL, NULL, '2024-12-10 08:26:13', '2024-12-10 08:35:41', NULL),
(4, 3, 1, '1970-01-02 03:00:00', 0, NULL, NULL, '2024-12-10 08:26:13', '2024-12-10 08:35:41', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `break_types`
--

CREATE TABLE `break_types` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `duration_in_minutes` int(10) UNSIGNED NOT NULL,
  `is_paid` tinyint(1) UNSIGNED NOT NULL,
  `is_require_break_in_and_break_out` tinyint(1) UNSIGNED NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `break_types`
--

INSERT INTO `break_types` (`id`, `name`, `duration_in_minutes`, `is_paid`, `is_require_break_in_and_break_out`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Lunch Break', 60, 0, 1, '2024-11-25 16:00:00', '2024-11-25 16:00:00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `deductions`
--

CREATE TABLE `deductions` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `amount_type` enum('Fixed Amount','Percentage-based') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `is_pre_tax` tinyint(1) UNSIGNED NOT NULL,
  `frequency` enum('Weekly','Bi-weekly','Semi-monthly','Monthly','One-time') NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive','Archived') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `department_head_id` int(10) UNSIGNED DEFAULT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('Active','Inactive','Archived') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `department_head_id`, `description`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Human Resources', NULL, 'Responsible for recruiting, training, and employee welfare', 'Active', '2024-11-28 10:02:50', '2024-11-28 10:02:50', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(10) UNSIGNED NOT NULL,
  `rfid_uid` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `first_name` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `middle_name` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `last_name` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `full_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci GENERATED ALWAYS AS (concat_ws(' ',`first_name`,`middle_name`,`last_name`)) STORED,
  `date_of_birth` date NOT NULL,
  `gender` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `marital_status` enum('Single','Married','Divorced','Legally Separated','Widowed') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `nationality` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `religion` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone_number` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `email_address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `profile_picture` mediumblob DEFAULT NULL,
  `emergency_contact_name` varchar(90) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `emergency_contact_relationship` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `emergency_contact_phone_number` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `emergency_contact_email_address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `emergency_contact_address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `employee_code` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `job_title_id` int(10) UNSIGNED NOT NULL,
  `department_id` int(10) UNSIGNED NOT NULL,
  `employment_type` enum('Regular / Permanent','Casual','Contractual','Project-Based','Seasonal','Fixed-Term','Probationary','Part-Time','Self-Employment','Freelance','Internship','Consultancy','Apprenticeship','Traineeship','Gig') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `date_of_hire` date NOT NULL,
  `supervisor_id` int(10) UNSIGNED DEFAULT NULL,
  `manager_id` int(10) UNSIGNED DEFAULT NULL,
  `access_role` enum('Staff','Supervisor','Manager','Admin') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `payroll_group_id` int(10) UNSIGNED NOT NULL,
  `annual_salary` decimal(10,2) DEFAULT NULL,
  `hourly_rate` decimal(10,2) DEFAULT NULL,
  `tin_number` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `sss_number` varchar(14) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `philhealth_number` varchar(14) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `pagibig_fund_number` varchar(14) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `bank_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `bank_branch_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `bank_account_number` varchar(25) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `bank_account_type` enum('Payroll Account','Current Account','Checking Account','Savings Account') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `rfid_uid`, `first_name`, `middle_name`, `last_name`, `date_of_birth`, `gender`, `marital_status`, `nationality`, `religion`, `phone_number`, `email_address`, `address`, `profile_picture`, `emergency_contact_name`, `emergency_contact_relationship`, `emergency_contact_phone_number`, `emergency_contact_email_address`, `emergency_contact_address`, `employee_code`, `job_title_id`, `department_id`, `employment_type`, `date_of_hire`, `supervisor_id`, `manager_id`, `access_role`, `payroll_group_id`, `annual_salary`, `hourly_rate`, `tin_number`, `sss_number`, `philhealth_number`, `pagibig_fund_number`, `bank_name`, `bank_branch_name`, `bank_account_number`, `bank_account_type`, `username`, `password`, `notes`, `created_at`, `updated_at`, `deleted_at`) VALUES
(6, '123456789', 'John', 'Doe', 'Smith', '1985-07-20', 'Male', 'Single', 'American', 'Christian', '+1234567890', 'john.smith@example.com', '123 Elm St, Springfield, IL', NULL, 'Jane Smith', 'Spouse', '+1234567891', 'jane.smith@example.com', '456 Oak St, Springfield, IL', 'EMP-0001', 1, 1, 'Regular / Permanent', '2020-01-15', NULL, NULL, 'Staff', 1, NULL, 100.00, '123-45-6789', '987-65-4321', 'PH1234567890', 'PAGIBIG1234567', 'ABC Bank', 'Main Branch, Springfield', '1234567890123456', 'Payroll Account', 'johnsmith123', '$2y$10$w/VLzSHuRvT41/1NRdNrrOJwQLPLqqCoJciuDL6v/dgAvLy9gnqhu', NULL, '2024-11-28 10:09:41', '2024-12-11 13:17:55', NULL),
(11, '1234567891', 'John', 'Doe', 'Smith', '1985-07-20', 'Male', 'Single', 'American', 'Christian', '+12345678901', 'john.smith@example.com1', '123 Elm St, Springfield, IL', NULL, 'Jane Smith', 'Spouse', '+1234567891', 'jane.smith@example.com', '456 Oak St, Springfield, IL', 'EMP-0002', 1, 1, 'Regular / Permanent', '2020-01-15', NULL, NULL, 'Staff', 1, NULL, 100.00, '123-45-67891', '987-65-4111321', 'PH123456111789', 'PAGI11BIG12345', 'ABC Bank', 'Main Branch, Springfield', '12345678111190123456', 'Payroll Account', 'johnsmith1231', '$2y$10$w/VLzSHuRvT41/1NRdNrrOJwQLPLqqCoJciuDL6v/dgAvLy9gnqhu', NULL, '2024-11-28 10:09:41', '2024-12-11 13:17:58', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `employee_allowances`
--

CREATE TABLE `employee_allowances` (
  `id` int(10) UNSIGNED NOT NULL,
  `employee_id` int(10) UNSIGNED NOT NULL,
  `allowance_id` int(10) UNSIGNED NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employee_breaks`
--

CREATE TABLE `employee_breaks` (
  `id` int(10) UNSIGNED NOT NULL,
  `break_schedule_id` int(10) UNSIGNED NOT NULL,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `break_duration_in_minutes` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_breaks`
--

INSERT INTO `employee_breaks` (`id`, `break_schedule_id`, `start_time`, `end_time`, `break_duration_in_minutes`, `created_at`, `updated_at`) VALUES
(1, 1, '2024-12-02 12:10:00', '2024-12-02 12:30:00', 20, '2024-12-02 04:10:00', '2024-12-12 09:35:17'),
(2, 1, '2024-12-02 12:35:00', '2024-12-02 12:55:00', 20, '2024-12-02 04:35:00', '2024-12-12 09:35:17'),
(3, 2, NULL, NULL, 0, '2024-12-02 09:00:00', '2024-12-12 09:35:37'),
(4, 1, NULL, NULL, 0, '2024-11-28 09:00:00', '2024-12-12 13:40:49'),
(5, 2, NULL, NULL, 0, '2024-11-28 09:00:00', '2024-12-12 13:40:49'),
(6, 1, NULL, NULL, 0, '2024-11-27 09:00:00', '2024-12-12 13:44:29'),
(7, 2, NULL, NULL, 0, '2024-11-27 09:00:00', '2024-12-12 13:44:29'),
(8, 3, NULL, NULL, 0, '2024-11-27 22:00:00', '2024-12-12 13:44:49'),
(9, 4, NULL, NULL, 0, '2024-11-27 22:00:00', '2024-12-12 13:44:49');

-- --------------------------------------------------------

--
-- Table structure for table `employee_deductions`
--

CREATE TABLE `employee_deductions` (
  `id` int(10) UNSIGNED NOT NULL,
  `employee_id` int(10) UNSIGNED NOT NULL,
  `deduction_id` int(10) UNSIGNED NOT NULL,
  `amount_type` enum('Fixed Amount','Percentage-based') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `holidays`
--

CREATE TABLE `holidays` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_paid` tinyint(1) UNSIGNED NOT NULL,
  `is_recurring_annually` tinyint(1) UNSIGNED NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive','Archived') NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `holidays`
--

INSERT INTO `holidays` (`id`, `name`, `start_date`, `end_date`, `is_paid`, `is_recurring_annually`, `description`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, '1', '2024-11-28', '2024-11-28', 1, 0, NULL, 'Active', '2024-12-09 15:53:48', '2024-12-09 15:54:56', NULL),
(2, '2', '2024-11-28', '2024-11-28', 1, 1, NULL, 'Active', '2024-12-09 16:04:52', '2024-12-09 16:04:52', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `job_titles`
--

CREATE TABLE `job_titles` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `department_id` int(10) UNSIGNED NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('Active','Inactive','Archived') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `job_titles`
--

INSERT INTO `job_titles` (`id`, `title`, `department_id`, `description`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Software Engineer', 1, 'Responsible for developing, testing, and maintaining software applications.', 'Active', '2024-11-28 10:06:55', '2024-11-28 10:06:55', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `leave_entitlements`
--

CREATE TABLE `leave_entitlements` (
  `id` int(10) UNSIGNED NOT NULL,
  `employee_id` int(10) UNSIGNED NOT NULL,
  `leave_type_id` int(10) UNSIGNED NOT NULL,
  `number_of_entitled_days` int(10) NOT NULL,
  `number_of_days_taken` int(10) NOT NULL DEFAULT 0,
  `remaining_days` int(10) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_requests`
--

CREATE TABLE `leave_requests` (
  `id` int(10) UNSIGNED NOT NULL,
  `employee_id` int(10) UNSIGNED NOT NULL,
  `leave_type_id` int(10) UNSIGNED NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reason` varchar(255) NOT NULL,
  `status` enum('Pending','Approved','Rejected','Canceled','Expired','In Progress','Completed') NOT NULL DEFAULT 'Pending',
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_types`
--

CREATE TABLE `leave_types` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `maximum_number_of_days` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `is_paid` tinyint(1) UNSIGNED NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive','Archived') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `overtime_rates`
--

CREATE TABLE `overtime_rates` (
  `id` int(10) UNSIGNED NOT NULL,
  `overtime_rate_assignment_id` int(10) UNSIGNED NOT NULL,
  `day_type` enum('Regular Day','Rest Day') NOT NULL,
  `holiday_type` enum('Non-holiday','Special Holiday','Regular Holiday','Double Holiday') NOT NULL,
  `regular_time_rate` decimal(10,5) NOT NULL,
  `overtime_rate` decimal(10,5) NOT NULL,
  `night_differential_rate` decimal(10,5) NOT NULL,
  `night_differential_and_overtime_rate` decimal(10,5) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `overtime_rates`
--

INSERT INTO `overtime_rates` (`id`, `overtime_rate_assignment_id`, `day_type`, `holiday_type`, `regular_time_rate`, `overtime_rate`, `night_differential_rate`, `night_differential_and_overtime_rate`, `created_at`, `updated_at`) VALUES
(9, 1, 'Regular Day', 'Non-holiday', 1.00000, 1.25000, 1.10000, 1.37500, '2024-12-04 02:05:38', '2024-12-04 02:05:38'),
(10, 1, 'Regular Day', 'Special Holiday', 1.30000, 1.69000, 1.43000, 1.85900, '2024-12-04 02:05:38', '2024-12-04 02:05:38'),
(11, 1, 'Regular Day', 'Regular Holiday', 2.00000, 2.60000, 2.20000, 2.86000, '2024-12-04 02:05:38', '2024-12-04 02:05:38'),
(12, 1, 'Regular Day', 'Double Holiday', 2.60000, 3.90000, 3.30000, 4.29000, '2024-12-04 02:05:38', '2024-12-04 02:05:38'),
(13, 1, 'Rest Day', 'Non-holiday', 1.30000, 1.69000, 1.43000, 1.85900, '2024-12-04 02:05:38', '2024-12-04 02:05:38'),
(14, 1, 'Rest Day', 'Special Holiday', 1.50000, 1.95000, 1.65000, 2.14500, '2024-12-04 02:05:38', '2024-12-04 02:05:38'),
(15, 1, 'Rest Day', 'Regular Holiday', 2.60000, 3.38000, 2.86000, 3.71800, '2024-12-04 02:05:38', '2024-12-04 02:05:38'),
(16, 1, 'Rest Day', 'Double Holiday', 3.90000, 5.07000, 4.29000, 5.51700, '2024-12-04 02:05:38', '2024-12-04 02:05:38');

-- --------------------------------------------------------

--
-- Table structure for table `overtime_rate_assignments`
--

CREATE TABLE `overtime_rate_assignments` (
  `id` int(10) UNSIGNED NOT NULL,
  `department_id` int(10) UNSIGNED DEFAULT NULL,
  `job_title_id` int(10) UNSIGNED DEFAULT NULL,
  `employee_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `overtime_rate_assignments`
--

INSERT INTO `overtime_rate_assignments` (`id`, `department_id`, `job_title_id`, `employee_id`, `created_at`) VALUES
(1, NULL, NULL, NULL, '2024-12-02 07:09:13');

-- --------------------------------------------------------

--
-- Table structure for table `payroll_groups`
--

CREATE TABLE `payroll_groups` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `pay_frequency` enum('Weekly','Bi-weekly','Semi-monthly','Monthly') NOT NULL,
  `status` enum('Active','Inactive','Archived') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payroll_groups`
--

INSERT INTO `payroll_groups` (`id`, `name`, `pay_frequency`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, '', 'Weekly', 'Active', '2024-12-12 19:15:00', '2024-12-12 19:15:00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(10) UNSIGNED NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` varchar(50) NOT NULL,
  `group_name` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `group_name`, `created_at`, `updated_at`) VALUES
(1, 'minutes_can_check_in_before_shift', '15', 'work_schedule', '2024-11-28 04:24:36', '2024-11-28 04:24:36'),
(2, 'grace_period', '15', 'work_schedule', '2024-11-28 04:24:36', '2024-11-28 04:24:36');

-- --------------------------------------------------------

--
-- Table structure for table `work_schedules`
--

CREATE TABLE `work_schedules` (
  `id` int(10) UNSIGNED NOT NULL,
  `employee_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(50) NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `is_flextime` tinyint(1) UNSIGNED NOT NULL,
  `core_hours_start_time` datetime DEFAULT NULL,
  `core_hours_end_time` datetime DEFAULT NULL,
  `total_hours_per_week` int(10) UNSIGNED DEFAULT NULL,
  `total_work_hours` int(10) NOT NULL,
  `start_date` date NOT NULL,
  `recurrence_rule` text NOT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `work_schedules`
--

INSERT INTO `work_schedules` (`id`, `employee_id`, `title`, `start_time`, `end_time`, `is_flextime`, `core_hours_start_time`, `core_hours_end_time`, `total_hours_per_week`, `total_work_hours`, `start_date`, `recurrence_rule`, `note`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 6, 'Regular Schedule', '1970-01-01 08:00:00', '1970-01-01 17:00:00', 0, NULL, NULL, NULL, 7, '2024-11-26', 'FREQ=WEEKLY;INTERVAL=1;DTSTART=2024-11-26;BYDAY=MO,TU,WE,TH,FR,SA;', NULL, '2024-12-08 16:28:07', '2024-12-10 08:27:03', NULL),
(3, 6, 'Regular Schedule', '1970-01-01 22:00:00', '1970-01-02 06:00:00', 0, NULL, NULL, NULL, 6, '2024-11-26', 'FREQ=WEEKLY;INTERVAL=1;DTSTART=2024-11-26;BYDAY=MO,TU,WE,TH,FR,SA;', NULL, '2024-12-08 16:28:07', '2024-12-10 14:52:30', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `allowances`
--
ALTER TABLE `allowances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_allowances_name` (`name`),
  ADD KEY `idx_allowances_frequency` (`frequency`),
  ADD KEY `idx_allowances_status` (`status`),
  ADD KEY `idx_allowances_deleted_at` (`deleted_at`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_attendance_date` (`date`),
  ADD KEY `idx_attendance_check_in_time` (`check_in_time`),
  ADD KEY `idx_attendance_check_out_time` (`check_out_time`),
  ADD KEY `idx_attendance_attendance_status` (`attendance_status`),
  ADD KEY `fk_work_schedules_attendance_work_schedule_id` (`work_schedule_id`);

--
-- Indexes for table `break_schedules`
--
ALTER TABLE `break_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_schedule_breaks_deleted_at` (`deleted_at`),
  ADD KEY `idx_schedule_breaks_start_time` (`start_time`),
  ADD KEY `fk_work_schedules_schedule_breaks_work_schedule_id` (`work_schedule_id`),
  ADD KEY `fk_break_types_work_schedules_break_type_id` (`break_type_id`);

--
-- Indexes for table `break_types`
--
ALTER TABLE `break_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_break_types_name` (`name`);

--
-- Indexes for table `deductions`
--
ALTER TABLE `deductions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_deductions_name` (`name`),
  ADD KEY `idx_deductions_frequency` (`frequency`),
  ADD KEY `idx_deductions_status` (`status`),
  ADD KEY `idx_deductions_deleted_at` (`deleted_at`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_departments_name` (`name`),
  ADD KEY `idx_departments_status` (`status`),
  ADD KEY `idx_departments_deleted_at` (`deleted_at`),
  ADD KEY `fk_employees_departments_department_head_id` (`department_head_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_staff_rfid_uid` (`rfid_uid`),
  ADD UNIQUE KEY `uq_staff_phone_number` (`phone_number`),
  ADD UNIQUE KEY `uq_staff_email_address` (`email_address`),
  ADD UNIQUE KEY `uq_staff_employee_code` (`employee_code`),
  ADD UNIQUE KEY `uq_staff_tin_number` (`tin_number`),
  ADD UNIQUE KEY `uq_staff_sss_number` (`sss_number`),
  ADD UNIQUE KEY `uq_staff_philhealth_number` (`philhealth_number`),
  ADD UNIQUE KEY `uq_staff_pagibig_fund_number` (`pagibig_fund_number`),
  ADD UNIQUE KEY `uq_staff_bank_account_number` (`bank_account_number`),
  ADD UNIQUE KEY `uq_staff_username` (`username`),
  ADD KEY `idx_staff_first_name_last_name` (`first_name`,`last_name`),
  ADD KEY `idx_staff_date_of_birth` (`date_of_birth`),
  ADD KEY `idx_staff_gender` (`gender`),
  ADD KEY `idx_staff_marital_status` (`marital_status`),
  ADD KEY `idx_staff_employment_type` (`employment_type`),
  ADD KEY `idx_staff_date_of_hire` (`date_of_hire`),
  ADD KEY `idx_staff_access_role` (`access_role`),
  ADD KEY `idx_staff_deleted_at` (`deleted_at`),
  ADD KEY `fk_job_titles_staff_job_title_id` (`job_title_id`),
  ADD KEY `fk_departments_staff_department_id` (`department_id`),
  ADD KEY `fk_staff_supervisor_id` (`supervisor_id`),
  ADD KEY `fk_staff_manager_id` (`manager_id`),
  ADD KEY `fk_payroll_groups_staff_payroll_group_id` (`payroll_group_id`);

--
-- Indexes for table `employee_allowances`
--
ALTER TABLE `employee_allowances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_employee_allowances_allowance_id` (`allowance_id`),
  ADD KEY `idx_employee_allowances_deleted_at` (`deleted_at`),
  ADD KEY `fk_employees_employee_allowances_employee_id` (`employee_id`);

--
-- Indexes for table `employee_breaks`
--
ALTER TABLE `employee_breaks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_break_schedules_employee_breaks_break_schedule_id` (`break_schedule_id`);

--
-- Indexes for table `employee_deductions`
--
ALTER TABLE `employee_deductions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_employee_deductions_deduction_id` (`deduction_id`),
  ADD KEY `idx_employee_deductions_deleted_at` (`deleted_at`),
  ADD KEY `fk_employees_employee_deductions_employee_id` (`employee_id`);

--
-- Indexes for table `holidays`
--
ALTER TABLE `holidays`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_holidays_name` (`name`),
  ADD KEY `idx_holidays_deleted_at` (`deleted_at`),
  ADD KEY `idx_holidays_status` (`status`),
  ADD KEY `idx_holidays_start_date` (`start_date`),
  ADD KEY `idx_holidays_end_date` (`end_date`);

--
-- Indexes for table `job_titles`
--
ALTER TABLE `job_titles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_job_titles_title` (`title`),
  ADD KEY `idx_job_titles_status` (`status`),
  ADD KEY `idx_job_titles_deleted_at` (`deleted_at`),
  ADD KEY `fk_departments_job_titles_department_id` (`department_id`);

--
-- Indexes for table `leave_entitlements`
--
ALTER TABLE `leave_entitlements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_leave_entitlements_leave_type_id` (`leave_type_id`),
  ADD KEY `idx_leave_entitlements_remaining_days` (`remaining_days`),
  ADD KEY `idx_leave_entitlements_deleted_at` (`deleted_at`),
  ADD KEY `fk_employees_leave_entitlements_employee_id` (`employee_id`);

--
-- Indexes for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_leave_requests_status` (`status`),
  ADD KEY `idx_leave_requests_deleted_at` (`deleted_at`),
  ADD KEY `fk_employees_leave_requests_approved_by` (`approved_by`);

--
-- Indexes for table `leave_types`
--
ALTER TABLE `leave_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_leave_types_name` (`name`),
  ADD KEY `idx_leave_types_status` (`status`),
  ADD KEY `idx_leave_types_deleted_at` (`deleted_at`);

--
-- Indexes for table `overtime_rates`
--
ALTER TABLE `overtime_rates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_overtime_rates_overtime_rate_assignment_id` (`overtime_rate_assignment_id`);

--
-- Indexes for table `overtime_rate_assignments`
--
ALTER TABLE `overtime_rate_assignments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_overtime_rate_assignments_department_job_title_employee_id` (`department_id`,`job_title_id`,`employee_id`),
  ADD KEY `fk_job_titles_overtime_rate_assignments_job_title_id` (`job_title_id`),
  ADD KEY `fk_employees_overtime_rate_assignments_employee_id` (`employee_id`);

--
-- Indexes for table `payroll_groups`
--
ALTER TABLE `payroll_groups`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_payroll_groups_name` (`name`),
  ADD KEY `idx_payroll_groups_status` (`status`),
  ADD KEY `idx_payroll_groups_deleted_at` (`deleted_at`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_settings_setting_key` (`setting_key`),
  ADD KEY `idx_settings_setting_value` (`setting_value`) USING BTREE,
  ADD KEY `idx_settings_group_name` (`group_name`) USING BTREE;

--
-- Indexes for table `work_schedules`
--
ALTER TABLE `work_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_work_schedules_deleted_at` (`deleted_at`),
  ADD KEY `idx_work_schedules_start_date` (`start_date`),
  ADD KEY `fk_employees_work_schedules_employee_id` (`employee_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `allowances`
--
ALTER TABLE `allowances`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `break_schedules`
--
ALTER TABLE `break_schedules`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `break_types`
--
ALTER TABLE `break_types`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `deductions`
--
ALTER TABLE `deductions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `employee_allowances`
--
ALTER TABLE `employee_allowances`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employee_breaks`
--
ALTER TABLE `employee_breaks`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `employee_deductions`
--
ALTER TABLE `employee_deductions`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `holidays`
--
ALTER TABLE `holidays`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `job_titles`
--
ALTER TABLE `job_titles`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `leave_entitlements`
--
ALTER TABLE `leave_entitlements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_types`
--
ALTER TABLE `leave_types`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `overtime_rates`
--
ALTER TABLE `overtime_rates`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `overtime_rate_assignments`
--
ALTER TABLE `overtime_rate_assignments`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payroll_groups`
--
ALTER TABLE `payroll_groups`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `work_schedules`
--
ALTER TABLE `work_schedules`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `fk_work_schedules_attendance_work_schedule_id` FOREIGN KEY (`work_schedule_id`) REFERENCES `work_schedules` (`id`);

--
-- Constraints for table `break_schedules`
--
ALTER TABLE `break_schedules`
  ADD CONSTRAINT `fk_break_types_work_schedules_break_type_id` FOREIGN KEY (`break_type_id`) REFERENCES `break_types` (`id`),
  ADD CONSTRAINT `fk_work_schedules_schedule_breaks_work_schedule_id` FOREIGN KEY (`work_schedule_id`) REFERENCES `work_schedules` (`id`);

--
-- Constraints for table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `fk_employees_departments_department_head_id` FOREIGN KEY (`department_head_id`) REFERENCES `employees` (`id`);

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `fk_departments_staff_department_id` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  ADD CONSTRAINT `fk_job_titles_staff_job_title_id` FOREIGN KEY (`job_title_id`) REFERENCES `job_titles` (`id`),
  ADD CONSTRAINT `fk_payroll_groups_staff_payroll_group_id` FOREIGN KEY (`payroll_group_id`) REFERENCES `payroll_groups` (`id`),
  ADD CONSTRAINT `fk_staff_manager_id` FOREIGN KEY (`manager_id`) REFERENCES `employees` (`id`),
  ADD CONSTRAINT `fk_staff_supervisor_id` FOREIGN KEY (`supervisor_id`) REFERENCES `employees` (`id`);

--
-- Constraints for table `employee_allowances`
--
ALTER TABLE `employee_allowances`
  ADD CONSTRAINT `fk_allowances_employee_allowances_allowance_id` FOREIGN KEY (`allowance_id`) REFERENCES `allowances` (`id`),
  ADD CONSTRAINT `fk_employees_employee_allowances_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`);

--
-- Constraints for table `employee_breaks`
--
ALTER TABLE `employee_breaks`
  ADD CONSTRAINT `fk_break_schedules_employee_breaks_break_schedule_id` FOREIGN KEY (`break_schedule_id`) REFERENCES `break_schedules` (`id`);

--
-- Constraints for table `employee_deductions`
--
ALTER TABLE `employee_deductions`
  ADD CONSTRAINT `fk_deductions_employee_deductions_deduction_id` FOREIGN KEY (`deduction_id`) REFERENCES `deductions` (`id`),
  ADD CONSTRAINT `fk_employees_employee_deductions_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`);

--
-- Constraints for table `job_titles`
--
ALTER TABLE `job_titles`
  ADD CONSTRAINT `fk_departments_job_titles_department_id` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`);

--
-- Constraints for table `leave_entitlements`
--
ALTER TABLE `leave_entitlements`
  ADD CONSTRAINT `fk_employees_leave_entitlements_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  ADD CONSTRAINT `fk_leave_types_leave_entitlements_leave_type_id` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`id`);

--
-- Constraints for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD CONSTRAINT `fk_employees_leave_requests_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `employees` (`id`);

--
-- Constraints for table `overtime_rates`
--
ALTER TABLE `overtime_rates`
  ADD CONSTRAINT `fk_overtime_rates_overtime_rate_assignment_id` FOREIGN KEY (`overtime_rate_assignment_id`) REFERENCES `overtime_rate_assignments` (`id`);

--
-- Constraints for table `overtime_rate_assignments`
--
ALTER TABLE `overtime_rate_assignments`
  ADD CONSTRAINT `fk_departments_overtime_rate_assignments_department_id` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  ADD CONSTRAINT `fk_employees_overtime_rate_assignments_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`),
  ADD CONSTRAINT `fk_job_titles_overtime_rate_assignments_job_title_id` FOREIGN KEY (`job_title_id`) REFERENCES `job_titles` (`id`);

--
-- Constraints for table `work_schedules`
--
ALTER TABLE `work_schedules`
  ADD CONSTRAINT `fk_employees_work_schedules_employee_id` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
