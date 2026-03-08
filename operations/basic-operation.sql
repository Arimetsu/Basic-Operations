-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 08, 2026 at 04:11 PM
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
-- Database: `bankingdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `account_applications`
--

CREATE TABLE `account_applications` (
  `application_id` int(11) NOT NULL,
  `application_number` varchar(50) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `account_type_id` int(11) NOT NULL,
  `application_status` enum('Pending','Under_Review','Approved','Rejected','Cancelled') DEFAULT 'Pending',
  `initial_deposit` decimal(18,2) DEFAULT NULL,
  `wants_passbook` tinyint(1) DEFAULT 0,
  `wants_atm_card` tinyint(1) DEFAULT 0,
  `terms_accepted_at` timestamp NULL DEFAULT NULL,
  `privacy_accepted_at` timestamp NULL DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_by_employee_id` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `account_history`
--

CREATE TABLE `account_history` (
  `history_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `closed_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `closure_reason` varchar(255) DEFAULT NULL,
  `final_balance` decimal(18,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `account_status_history`
--

CREATE TABLE `account_status_history` (
  `status_history_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `previous_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) NOT NULL,
  `balance_at_change` decimal(18,2) DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `addresses`
--

CREATE TABLE `addresses` (
  `address_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `address_line` varchar(255) DEFAULT NULL,
  `barangay_id` int(11) DEFAULT NULL,
  `city_id` int(11) DEFAULT NULL,
  `province_id` int(11) DEFAULT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `addresses`
--

INSERT INTO `addresses` (`address_id`, `customer_id`, `address_line`, `barangay_id`, `city_id`, `province_id`, `postal_code`, `is_primary`, `is_active`, `created_at`) VALUES
(2, 5, 'Union Drive Ext.', 14, 2, 1, '1128', 1, 1, '2026-02-20 12:54:57');

-- --------------------------------------------------------

--
-- Table structure for table `archived_customer_accounts`
--

CREATE TABLE `archived_customer_accounts` (
  `archived_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `account_number` varchar(100) NOT NULL,
  `account_type_id` int(11) DEFAULT NULL,
  `interest_rate` decimal(5,4) DEFAULT NULL,
  `last_interest_date` date DEFAULT NULL,
  `is_locked` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `created_by_employee_id` int(11) DEFAULT NULL,
  `maintaining_balance_required` decimal(18,2) DEFAULT NULL,
  `monthly_service_fee` decimal(18,2) DEFAULT NULL,
  `final_balance` decimal(18,2) DEFAULT NULL,
  `below_maintaining_since` date DEFAULT NULL,
  `last_service_fee_date` date DEFAULT NULL,
  `closure_warning_date` date DEFAULT NULL,
  `flagged_for_removal_date` date DEFAULT NULL,
  `original_status` varchar(50) DEFAULT NULL,
  `archive_reason` varchar(255) DEFAULT NULL,
  `archived_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bank_account_types`
--

CREATE TABLE `bank_account_types` (
  `account_type_id` int(11) NOT NULL,
  `type_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `allows_passbook` tinyint(1) DEFAULT 0,
  `allows_atm_card` tinyint(1) DEFAULT 0,
  `requires_parent_guardian` tinyint(1) DEFAULT 0,
  `minimum_age` int(11) DEFAULT NULL,
  `base_interest_rate` decimal(5,4) DEFAULT 0.0000,
  `currency` varchar(10) DEFAULT 'PHP',
  `minimum_balance` decimal(18,2) DEFAULT 0.00,
  `monthly_fee` decimal(18,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bank_account_types`
--

INSERT INTO `bank_account_types` (`account_type_id`, `type_name`, `description`, `allows_passbook`, `allows_atm_card`, `requires_parent_guardian`, `minimum_age`, `base_interest_rate`, `currency`, `minimum_balance`, `monthly_fee`, `is_active`, `created_at`) VALUES
(1, 'Savings', 'Basic savings account', 1, 1, 0, NULL, 0.0250, 'PHP', 500.00, 30.00, 1, '2026-02-20 12:18:00'),
(2, 'Junior Savings', 'Savings for minors', 1, 0, 1, 0, 0.0150, 'PHP', 0.00, 0.00, 1, '2026-02-20 12:18:00'),
(3, 'Checking', 'Checking account for businesses', 0, 1, 0, NULL, 0.0000, 'PHP', 5000.00, 350.00, 1, '2026-02-20 12:18:00'),
(4, 'Time Deposit', 'Fixed term deposit', 0, 0, 0, 18, 0.0500, 'PHP', 10000.00, 0.00, 1, '2026-02-20 12:18:00');

-- --------------------------------------------------------

--
-- Table structure for table `bank_customers`
--

CREATE TABLE `bank_customers` (
  `customer_id` int(11) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `application_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bank_customers`
--

INSERT INTO `bank_customers` (`customer_id`, `last_name`, `first_name`, `middle_name`, `password_hash`, `is_active`, `created_at`, `updated_at`, `application_id`) VALUES
(5, 'D. Cruz', 'Grace', '', '$2y$10$S9Y.19q7S5hSaVYUeRxfQedPS9oITJl4tqp0LKr53Qw3eUY3ukZia', 1, '2026-02-20 12:54:57', '2026-02-20 12:54:57', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `bank_employees`
--

CREATE TABLE `bank_employees` (
  `employee_id` int(11) NOT NULL,
  `employee_name` varchar(100) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `role` enum('admin','teller','manager') DEFAULT 'teller',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bank_employees`
--

INSERT INTO `bank_employees` (`employee_id`, `employee_name`, `username`, `password_hash`, `email`, `first_name`, `last_name`, `role`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Admin User', 'admin', '$2y$10$VoiC7LlRr/UrfiFoqMvyZ.KQ47iqMQVmGXpc6Tgxk09ChvUQhipW.', 'admin@bank.com', 'Admin', 'User', 'admin', 1, '2026-03-08 13:47:17', '2026-03-08 13:47:17');

-- --------------------------------------------------------

--
-- Table structure for table `bank_transactions`
--

CREATE TABLE `bank_transactions` (
  `transaction_id` int(11) NOT NULL,
  `transaction_ref` varchar(150) NOT NULL,
  `account_id` int(11) NOT NULL,
  `transaction_type_id` int(11) NOT NULL,
  `related_account_id` int(11) DEFAULT NULL,
  `amount` decimal(18,2) NOT NULL,
  `balance_after` decimal(18,2) NOT NULL,
  `description` text DEFAULT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bank_transactions`
--

INSERT INTO `bank_transactions` (`transaction_id`, `transaction_ref`, `account_id`, `transaction_type_id`, `related_account_id`, `amount`, `balance_after`, `description`, `employee_id`, `created_at`) VALUES
(1, 'DEP-2026-0001', 1, 1, NULL, 5000.00, 5000.00, 'Initial deposit - Account opening', NULL, '2026-02-21 03:36:00');

-- --------------------------------------------------------

--
-- Table structure for table `barangays`
--

CREATE TABLE `barangays` (
  `barangay_id` int(11) NOT NULL,
  `city_id` int(11) NOT NULL,
  `barangay_name` varchar(255) NOT NULL,
  `zip_code` varchar(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `barangays`
--

INSERT INTO `barangays` (`barangay_id`, `city_id`, `barangay_name`, `zip_code`, `created_at`) VALUES
(14, 2, 'Bagong Silang', NULL, '2026-03-08 13:37:06');

-- --------------------------------------------------------

--
-- Table structure for table `cities`
--

CREATE TABLE `cities` (
  `city_id` int(11) NOT NULL,
  `province_id` int(11) NOT NULL,
  `city_name` varchar(255) NOT NULL,
  `city_type` varchar(50) DEFAULT 'City',
  `zip_code` varchar(10) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cities`
--

INSERT INTO `cities` (`city_id`, `province_id`, `city_name`, `city_type`, `zip_code`, `created_at`) VALUES
(2, 1, 'Caloocan', 'City', NULL, '2026-03-08 13:37:06');

-- --------------------------------------------------------

--
-- Table structure for table `country_codes`
--

CREATE TABLE `country_codes` (
  `country_code_id` int(11) NOT NULL,
  `country_name` varchar(100) NOT NULL,
  `phone_code` varchar(10) NOT NULL,
  `iso_code` varchar(3) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `country_codes`
--

INSERT INTO `country_codes` (`country_code_id`, `country_name`, `phone_code`, `iso_code`, `created_at`) VALUES
(1, 'Philippines', '+63', 'PH', '2026-03-08 13:50:26'),
(2, 'United States', '+1', 'US', '2026-03-08 13:50:26'),
(3, 'United Kingdom', '+44', 'GB', '2026-03-08 13:50:26'),
(4, 'Singapore', '+65', 'SG', '2026-03-08 13:50:26'),
(5, 'Malaysia', '+60', 'MY', '2026-03-08 13:50:26'),
(6, 'Japan', '+81', 'JP', '2026-03-08 13:50:26'),
(7, 'China', '+86', 'CN', '2026-03-08 13:50:26'),
(8, 'South Korea', '+82', 'KR', '2026-03-08 13:50:26'),
(9, 'Australia', '+61', 'AU', '2026-03-08 13:50:26');

-- --------------------------------------------------------

--
-- Table structure for table `customer_accounts`
--

CREATE TABLE `customer_accounts` (
  `account_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `account_type_id` int(11) NOT NULL,
  `account_number` varchar(100) NOT NULL,
  `is_locked` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `account_status` varchar(50) DEFAULT 'active',
  `maintaining_balance_required` decimal(18,2) DEFAULT 500.00,
  `monthly_service_fee` decimal(18,2) DEFAULT 100.00,
  `below_maintaining_since` date DEFAULT NULL,
  `last_service_fee_date` date DEFAULT NULL,
  `closure_warning_date` date DEFAULT NULL,
  `is_hidden` tinyint(1) DEFAULT 0,
  `hidden_at` timestamp NULL DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `parent_account_id` int(11) DEFAULT NULL,
  `created_by_employee_id` int(11) DEFAULT NULL,
  `interest_rate` decimal(5,4) DEFAULT NULL,
  `last_interest_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_accounts`
--

INSERT INTO `customer_accounts` (`account_id`, `customer_id`, `account_type_id`, `account_number`, `is_locked`, `is_active`, `account_status`, `maintaining_balance_required`, `monthly_service_fee`, `below_maintaining_since`, `last_service_fee_date`, `closure_warning_date`, `is_hidden`, `hidden_at`, `archived_at`, `parent_account_id`, `created_by_employee_id`, `interest_rate`, `last_interest_date`, `created_at`, `updated_at`) VALUES
(1, 5, 1, 'SA-2026-0001', 0, 1, 'active', 500.00, 100.00, NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, '2026-02-21 03:36:00', '2026-02-21 03:36:00');

-- --------------------------------------------------------

--
-- Table structure for table `customer_documents`
--

CREATE TABLE `customer_documents` (
  `document_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `doc_type_id` int(11) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_ids`
--

CREATE TABLE `customer_ids` (
  `customer_id` int(11) NOT NULL,
  `id_type_id` int(11) NOT NULL,
  `id_number` varchar(150) NOT NULL,
  `issue_date` date DEFAULT NULL,
  `expiration_date` date DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_linked_accounts`
--

CREATE TABLE `customer_linked_accounts` (
  `link_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `linked_at` date DEFAULT curdate(),
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customer_profile`
--

CREATE TABLE `customer_profile` (
  `profile_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `gender_id` int(11) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `marital_status` varchar(50) DEFAULT NULL,
  `nationality` varchar(100) DEFAULT 'Filipino',
  `employment_status` varchar(100) DEFAULT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `occupation` varchar(255) DEFAULT NULL,
  `income_range` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer_profile`
--

INSERT INTO `customer_profile` (`profile_id`, `customer_id`, `gender_id`, `date_of_birth`, `marital_status`, `nationality`, `employment_status`, `company_name`, `occupation`, `income_range`, `created_at`, `updated_at`) VALUES
(1, 5, 2, '1990-05-15', 'Single', 'Filipino', 'Employed', 'ABC Corporation', 'Manager', '250,000 - 500,000', '2026-03-08 13:37:06', '2026-03-08 13:37:06');

-- --------------------------------------------------------

--
-- Table structure for table `document_types`
--

CREATE TABLE `document_types` (
  `doc_type_id` int(11) NOT NULL,
  `type_name` varchar(100) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `document_types`
--

INSERT INTO `document_types` (`doc_type_id`, `type_name`, `description`) VALUES
(1, 'Profile Picture', 'Customer selfie for verification'),
(2, 'E-Signature', 'Digitized signature image'),
(3, 'ID Front', 'Front photo of government ID'),
(4, 'ID Back', 'Back photo of government ID');

-- --------------------------------------------------------

--
-- Table structure for table `emails`
--

CREATE TABLE `emails` (
  `email_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `emails`
--

INSERT INTO `emails` (`email_id`, `customer_id`, `email`, `is_primary`, `is_active`, `created_at`) VALUES
(5, 5, 'vennidictdimayuga@gmail.com', 1, 1, '2026-02-20 12:54:57');

-- --------------------------------------------------------

--
-- Table structure for table `employment_statuses`
--

CREATE TABLE `employment_statuses` (
  `employment_status_id` int(11) NOT NULL,
  `status_name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employment_statuses`
--

INSERT INTO `employment_statuses` (`employment_status_id`, `status_name`, `description`, `is_active`, `created_at`) VALUES
(1, 'Employed', 'Currently employed full-time or part-time', 1, '2026-03-08 13:52:20'),
(2, 'Self-Employed', 'Owns or operates a business', 1, '2026-03-08 13:52:20'),
(3, 'Unemployed', 'Currently not employed', 1, '2026-03-08 13:52:20'),
(4, 'Student', 'Currently enrolled in an educational institution', 1, '2026-03-08 13:52:20'),
(5, 'Retired', 'No longer working due to retirement', 1, '2026-03-08 13:52:20'),
(6, 'OFW', 'Overseas Filipino Worker', 1, '2026-03-08 13:52:20'),
(7, 'Freelancer', 'Works independently on a contract basis', 1, '2026-03-08 13:52:20'),
(8, 'Government Employee', 'Employed by a government agency', 1, '2026-03-08 13:52:20');

-- --------------------------------------------------------

--
-- Table structure for table `gender`
--

CREATE TABLE `gender` (
  `gender_id` int(11) NOT NULL,
  `gender_name` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gender`
--

INSERT INTO `gender` (`gender_id`, `gender_name`, `created_at`) VALUES
(1, 'Male', '2026-03-08 13:37:06'),
(2, 'Female', '2026-03-08 13:37:06'),
(3, 'Other', '2026-03-08 13:37:06');

-- --------------------------------------------------------

--
-- Table structure for table `id_types`
--

CREATE TABLE `id_types` (
  `id_type_id` int(11) NOT NULL,
  `type_name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `id_types`
--

INSERT INTO `id_types` (`id_type_id`, `type_name`, `description`) VALUES
(1, 'National ID', 'Government issued national identification'),
(2, 'Passport', 'International travel document'),
(3, 'Driver License', 'Driving permit issued by government');

-- --------------------------------------------------------

--
-- Table structure for table `phones`
--

CREATE TABLE `phones` (
  `phone_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `phone_number` varchar(50) NOT NULL,
  `phone_type` varchar(50) DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `phones`
--

INSERT INTO `phones` (`phone_id`, `customer_id`, `phone_number`, `phone_type`, `is_primary`, `is_active`, `created_at`) VALUES
(5, 5, '+639875324678', 'mobile', 1, 1, '2026-02-20 12:54:57');

-- --------------------------------------------------------

--
-- Table structure for table `provinces`
--

CREATE TABLE `provinces` (
  `province_id` int(11) NOT NULL,
  `province_name` varchar(255) NOT NULL,
  `region` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `provinces`
--

INSERT INTO `provinces` (`province_id`, `province_name`, `region`, `created_at`) VALUES
(1, 'Metro Manila', NULL, '2026-03-08 13:37:06');

-- --------------------------------------------------------

--
-- Table structure for table `source_of_funds`
--

CREATE TABLE `source_of_funds` (
  `source_id` int(11) NOT NULL,
  `source_name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `requires_proof` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `source_of_funds`
--

INSERT INTO `source_of_funds` (`source_id`, `source_name`, `description`, `requires_proof`, `is_active`, `created_at`) VALUES
(1, 'Employment/Salary', 'Regular income from employment', 0, 1, '2026-03-08 13:52:20'),
(2, 'Business Income', 'Income from business operations', 1, 1, '2026-03-08 13:52:20'),
(3, 'Remittance', 'Money sent from abroad', 1, 1, '2026-03-08 13:52:20'),
(4, 'Pension', 'Retirement pension', 0, 1, '2026-03-08 13:52:20'),
(5, 'Investments', 'Returns from investments', 1, 1, '2026-03-08 13:52:20'),
(6, 'Savings', 'Previously accumulated savings', 0, 1, '2026-03-08 13:52:20'),
(7, 'Allowance', 'Regular allowance from family or guardian', 0, 1, '2026-03-08 13:52:20'),
(8, 'Government Benefits', 'Benefits from government programs', 0, 1, '2026-03-08 13:52:20'),
(9, 'Inheritance', 'Money received through inheritance', 1, 1, '2026-03-08 13:52:20'),
(10, 'Loan Proceeds', 'Funds from an approved loan', 1, 1, '2026-03-08 13:52:20');

-- --------------------------------------------------------

--
-- Table structure for table `transaction_types`
--

CREATE TABLE `transaction_types` (
  `transaction_type_id` int(11) NOT NULL,
  `type_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaction_types`
--

INSERT INTO `transaction_types` (`transaction_type_id`, `type_name`, `description`, `created_at`) VALUES
(1, 'Deposit', 'Money deposited into account', '2026-03-08 13:37:06'),
(2, 'Withdrawal', 'Money withdrawn from account', '2026-03-08 13:37:06'),
(3, 'Transfer', 'Money transferred between accounts', '2026-03-08 13:37:06'),
(4, 'Interest', 'Interest credited to account', '2026-03-08 13:37:06'),
(5, 'Fee', 'Service fee charged', '2026-03-08 13:37:06'),
(6, 'Loan Payment', 'Payment towards loan', '2026-03-08 13:37:06'),
(7, 'Loan Disbursement', 'Loan amount credited', '2026-03-08 13:37:06'),
(8, 'Transfer In', 'Money transferred into account', '2026-03-08 13:47:17'),
(9, 'Transfer Out', 'Money transferred out of account', '2026-03-08 13:47:17'),
(10, 'Interest Payment', 'Interest payment credited to account', '2026-03-08 13:47:17'),
(11, 'Service Charge', 'Service charge deducted from account', '2026-03-08 13:47:17'),
(12, 'Monthly Maintenance Fee', 'Monthly maintenance fee for below-maintaining accounts', '2026-03-08 13:47:17');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `account_applications`
--
ALTER TABLE `account_applications`
  ADD PRIMARY KEY (`application_id`),
  ADD UNIQUE KEY `application_number` (`application_number`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `account_type_id` (`account_type_id`),
  ADD KEY `fk_aa_reviewed_by_employee` (`reviewed_by_employee_id`);

--
-- Indexes for table `account_history`
--
ALTER TABLE `account_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `account_id` (`account_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `account_status_history`
--
ALTER TABLE `account_status_history`
  ADD PRIMARY KEY (`status_history_id`),
  ADD KEY `idx_account_id` (`account_id`),
  ADD KEY `idx_changed_by` (`changed_by`);

--
-- Indexes for table `addresses`
--
ALTER TABLE `addresses`
  ADD PRIMARY KEY (`address_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `barangay_id` (`barangay_id`),
  ADD KEY `city_id` (`city_id`),
  ADD KEY `province_id` (`province_id`);

--
-- Indexes for table `archived_customer_accounts`
--
ALTER TABLE `archived_customer_accounts`
  ADD PRIMARY KEY (`archived_id`);

--
-- Indexes for table `bank_account_types`
--
ALTER TABLE `bank_account_types`
  ADD PRIMARY KEY (`account_type_id`);

--
-- Indexes for table `bank_customers`
--
ALTER TABLE `bank_customers`
  ADD PRIMARY KEY (`customer_id`);

--
-- Indexes for table `bank_employees`
--
ALTER TABLE `bank_employees`
  ADD PRIMARY KEY (`employee_id`),
  ADD UNIQUE KEY `idx_username` (`username`),
  ADD UNIQUE KEY `idx_email` (`email`);

--
-- Indexes for table `bank_transactions`
--
ALTER TABLE `bank_transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD UNIQUE KEY `transaction_ref` (`transaction_ref`),
  ADD KEY `account_id` (`account_id`),
  ADD KEY `transaction_type_id` (`transaction_type_id`),
  ADD KEY `related_account_id` (`related_account_id`),
  ADD KEY `fk_bt_employee` (`employee_id`);

--
-- Indexes for table `barangays`
--
ALTER TABLE `barangays`
  ADD PRIMARY KEY (`barangay_id`),
  ADD KEY `city_id` (`city_id`);

--
-- Indexes for table `cities`
--
ALTER TABLE `cities`
  ADD PRIMARY KEY (`city_id`),
  ADD KEY `province_id` (`province_id`);

--
-- Indexes for table `country_codes`
--
ALTER TABLE `country_codes`
  ADD PRIMARY KEY (`country_code_id`),
  ADD UNIQUE KEY `phone_code` (`phone_code`),
  ADD UNIQUE KEY `iso_code` (`iso_code`),
  ADD KEY `idx_phone_code` (`phone_code`),
  ADD KEY `idx_iso_code` (`iso_code`);

--
-- Indexes for table `customer_accounts`
--
ALTER TABLE `customer_accounts`
  ADD PRIMARY KEY (`account_id`),
  ADD UNIQUE KEY `account_number` (`account_number`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `account_type_id` (`account_type_id`),
  ADD KEY `parent_account_id` (`parent_account_id`),
  ADD KEY `fk_ca_created_by_employee` (`created_by_employee_id`);

--
-- Indexes for table `customer_documents`
--
ALTER TABLE `customer_documents`
  ADD PRIMARY KEY (`document_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `doc_type_id` (`doc_type_id`);

--
-- Indexes for table `customer_ids`
--
ALTER TABLE `customer_ids`
  ADD PRIMARY KEY (`customer_id`,`id_type_id`),
  ADD KEY `id_type_id` (`id_type_id`);

--
-- Indexes for table `customer_linked_accounts`
--
ALTER TABLE `customer_linked_accounts`
  ADD PRIMARY KEY (`link_id`),
  ADD UNIQUE KEY `unique_customer_account` (`customer_id`,`account_id`),
  ADD KEY `account_id` (`account_id`);

--
-- Indexes for table `customer_profile`
--
ALTER TABLE `customer_profile`
  ADD PRIMARY KEY (`profile_id`),
  ADD UNIQUE KEY `customer_id` (`customer_id`),
  ADD KEY `gender_id` (`gender_id`);

--
-- Indexes for table `document_types`
--
ALTER TABLE `document_types`
  ADD PRIMARY KEY (`doc_type_id`);

--
-- Indexes for table `emails`
--
ALTER TABLE `emails`
  ADD PRIMARY KEY (`email_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `employment_statuses`
--
ALTER TABLE `employment_statuses`
  ADD PRIMARY KEY (`employment_status_id`);

--
-- Indexes for table `gender`
--
ALTER TABLE `gender`
  ADD PRIMARY KEY (`gender_id`);

--
-- Indexes for table `id_types`
--
ALTER TABLE `id_types`
  ADD PRIMARY KEY (`id_type_id`);

--
-- Indexes for table `phones`
--
ALTER TABLE `phones`
  ADD PRIMARY KEY (`phone_id`),
  ADD KEY `customer_id` (`customer_id`);

--
-- Indexes for table `provinces`
--
ALTER TABLE `provinces`
  ADD PRIMARY KEY (`province_id`);

--
-- Indexes for table `source_of_funds`
--
ALTER TABLE `source_of_funds`
  ADD PRIMARY KEY (`source_id`);

--
-- Indexes for table `transaction_types`
--
ALTER TABLE `transaction_types`
  ADD PRIMARY KEY (`transaction_type_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `account_applications`
--
ALTER TABLE `account_applications`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `account_history`
--
ALTER TABLE `account_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `account_status_history`
--
ALTER TABLE `account_status_history`
  MODIFY `status_history_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `addresses`
--
ALTER TABLE `addresses`
  MODIFY `address_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `archived_customer_accounts`
--
ALTER TABLE `archived_customer_accounts`
  MODIFY `archived_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bank_account_types`
--
ALTER TABLE `bank_account_types`
  MODIFY `account_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `bank_customers`
--
ALTER TABLE `bank_customers`
  MODIFY `customer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `bank_employees`
--
ALTER TABLE `bank_employees`
  MODIFY `employee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `bank_transactions`
--
ALTER TABLE `bank_transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `barangays`
--
ALTER TABLE `barangays`
  MODIFY `barangay_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `cities`
--
ALTER TABLE `cities`
  MODIFY `city_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `country_codes`
--
ALTER TABLE `country_codes`
  MODIFY `country_code_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `customer_accounts`
--
ALTER TABLE `customer_accounts`
  MODIFY `account_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `customer_documents`
--
ALTER TABLE `customer_documents`
  MODIFY `document_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customer_linked_accounts`
--
ALTER TABLE `customer_linked_accounts`
  MODIFY `link_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customer_profile`
--
ALTER TABLE `customer_profile`
  MODIFY `profile_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `document_types`
--
ALTER TABLE `document_types`
  MODIFY `doc_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `emails`
--
ALTER TABLE `emails`
  MODIFY `email_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `employment_statuses`
--
ALTER TABLE `employment_statuses`
  MODIFY `employment_status_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `gender`
--
ALTER TABLE `gender`
  MODIFY `gender_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `id_types`
--
ALTER TABLE `id_types`
  MODIFY `id_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `phones`
--
ALTER TABLE `phones`
  MODIFY `phone_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `provinces`
--
ALTER TABLE `provinces`
  MODIFY `province_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `source_of_funds`
--
ALTER TABLE `source_of_funds`
  MODIFY `source_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `transaction_types`
--
ALTER TABLE `transaction_types`
  MODIFY `transaction_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `account_applications`
--
ALTER TABLE `account_applications`
  ADD CONSTRAINT `account_applications_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `bank_customers` (`customer_id`),
  ADD CONSTRAINT `account_applications_ibfk_2` FOREIGN KEY (`account_type_id`) REFERENCES `bank_account_types` (`account_type_id`),
  ADD CONSTRAINT `fk_aa_reviewed_by_employee` FOREIGN KEY (`reviewed_by_employee_id`) REFERENCES `bank_employees` (`employee_id`);

--
-- Constraints for table `account_history`
--
ALTER TABLE `account_history`
  ADD CONSTRAINT `account_history_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `customer_accounts` (`account_id`),
  ADD CONSTRAINT `account_history_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `bank_customers` (`customer_id`);

--
-- Constraints for table `account_status_history`
--
ALTER TABLE `account_status_history`
  ADD CONSTRAINT `account_status_history_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `customer_accounts` (`account_id`),
  ADD CONSTRAINT `account_status_history_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `bank_employees` (`employee_id`);

--
-- Constraints for table `addresses`
--
ALTER TABLE `addresses`
  ADD CONSTRAINT `addresses_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `bank_customers` (`customer_id`),
  ADD CONSTRAINT `addresses_ibfk_2` FOREIGN KEY (`barangay_id`) REFERENCES `barangays` (`barangay_id`),
  ADD CONSTRAINT `addresses_ibfk_3` FOREIGN KEY (`city_id`) REFERENCES `cities` (`city_id`),
  ADD CONSTRAINT `addresses_ibfk_4` FOREIGN KEY (`province_id`) REFERENCES `provinces` (`province_id`);

--
-- Constraints for table `bank_transactions`
--
ALTER TABLE `bank_transactions`
  ADD CONSTRAINT `bank_transactions_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `customer_accounts` (`account_id`),
  ADD CONSTRAINT `bank_transactions_ibfk_2` FOREIGN KEY (`transaction_type_id`) REFERENCES `transaction_types` (`transaction_type_id`),
  ADD CONSTRAINT `bank_transactions_ibfk_3` FOREIGN KEY (`related_account_id`) REFERENCES `customer_accounts` (`account_id`),
  ADD CONSTRAINT `fk_bt_employee` FOREIGN KEY (`employee_id`) REFERENCES `bank_employees` (`employee_id`);

--
-- Constraints for table `barangays`
--
ALTER TABLE `barangays`
  ADD CONSTRAINT `barangays_ibfk_1` FOREIGN KEY (`city_id`) REFERENCES `cities` (`city_id`);

--
-- Constraints for table `cities`
--
ALTER TABLE `cities`
  ADD CONSTRAINT `cities_ibfk_1` FOREIGN KEY (`province_id`) REFERENCES `provinces` (`province_id`);

--
-- Constraints for table `customer_accounts`
--
ALTER TABLE `customer_accounts`
  ADD CONSTRAINT `customer_accounts_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `bank_customers` (`customer_id`),
  ADD CONSTRAINT `customer_accounts_ibfk_2` FOREIGN KEY (`account_type_id`) REFERENCES `bank_account_types` (`account_type_id`),
  ADD CONSTRAINT `customer_accounts_ibfk_3` FOREIGN KEY (`parent_account_id`) REFERENCES `customer_accounts` (`account_id`),
  ADD CONSTRAINT `fk_ca_created_by_employee` FOREIGN KEY (`created_by_employee_id`) REFERENCES `bank_employees` (`employee_id`);

--
-- Constraints for table `customer_documents`
--
ALTER TABLE `customer_documents`
  ADD CONSTRAINT `customer_documents_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `bank_customers` (`customer_id`),
  ADD CONSTRAINT `customer_documents_ibfk_2` FOREIGN KEY (`doc_type_id`) REFERENCES `document_types` (`doc_type_id`);

--
-- Constraints for table `customer_ids`
--
ALTER TABLE `customer_ids`
  ADD CONSTRAINT `customer_ids_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `bank_customers` (`customer_id`),
  ADD CONSTRAINT `customer_ids_ibfk_2` FOREIGN KEY (`id_type_id`) REFERENCES `id_types` (`id_type_id`);

--
-- Constraints for table `customer_linked_accounts`
--
ALTER TABLE `customer_linked_accounts`
  ADD CONSTRAINT `customer_linked_accounts_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `bank_customers` (`customer_id`),
  ADD CONSTRAINT `customer_linked_accounts_ibfk_2` FOREIGN KEY (`account_id`) REFERENCES `customer_accounts` (`account_id`);

--
-- Constraints for table `customer_profile`
--
ALTER TABLE `customer_profile`
  ADD CONSTRAINT `customer_profile_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `bank_customers` (`customer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `customer_profile_ibfk_2` FOREIGN KEY (`gender_id`) REFERENCES `gender` (`gender_id`);

--
-- Constraints for table `emails`
--
ALTER TABLE `emails`
  ADD CONSTRAINT `emails_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `bank_customers` (`customer_id`);

--
-- Constraints for table `phones`
--
ALTER TABLE `phones`
  ADD CONSTRAINT `phones_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `bank_customers` (`customer_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;