-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Sep 16, 2024 at 01:29 PM
-- Server version: 8.2.0
-- PHP Version: 8.2.13

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `laravel`
--

-- --------------------------------------------------------

--
-- Table structure for table `cities`
--

DROP TABLE IF EXISTS `cities`;
CREATE TABLE IF NOT EXISTS `cities` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `state_id` bigint UNSIGNED NOT NULL,
  `city_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cities_state_id_foreign` (`state_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cities`
--

INSERT INTO `cities` (`id`, `state_id`, `city_name`, `is_active`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 'Kota', 1, NULL, NULL, NULL),
(2, 1, 'Bundi', 1, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

DROP TABLE IF EXISTS `courses`;
CREATE TABLE IF NOT EXISTS `courses` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `course_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `course_fees` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `duration` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_types`
--

DROP TABLE IF EXISTS `course_types`;
CREATE TABLE IF NOT EXISTS `course_types` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

DROP TABLE IF EXISTS `expenses`;
CREATE TABLE IF NOT EXISTS `expenses` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `library_id` bigint UNSIGNED NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expenses_library_id_foreign` (`library_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE IF NOT EXISTS `failed_jobs` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grades`
--

DROP TABLE IF EXISTS `grades`;
CREATE TABLE IF NOT EXISTS `grades` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `class_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hour`
--

DROP TABLE IF EXISTS `hour`;
CREATE TABLE IF NOT EXISTS `hour` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `library_id` bigint UNSIGNED NOT NULL,
  `hour` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hour_library_id_foreign` (`library_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `learners`
--

DROP TABLE IF EXISTS `learners`;
CREATE TABLE IF NOT EXISTS `learners` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `library_id` bigint UNSIGNED NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mobile` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `address` date DEFAULT NULL,
  `id_proof_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_proof_file` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `seat_no` bigint UNSIGNED NOT NULL,
  `payment_mode` int DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `learners_email_unique` (`email`),
  KEY `learners_library_id_foreign` (`library_id`),
  KEY `learners_seat_no_foreign` (`seat_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `learner_detail`
--

DROP TABLE IF EXISTS `learner_detail`;
CREATE TABLE IF NOT EXISTS `learner_detail` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `library_id` bigint UNSIGNED NOT NULL,
  `learner_id` bigint UNSIGNED NOT NULL,
  `join_date` date DEFAULT NULL,
  `plan_start_date` date DEFAULT NULL,
  `plan_end_date` date DEFAULT NULL,
  `plan_price_id` bigint UNSIGNED NOT NULL,
  `plan_id` bigint UNSIGNED NOT NULL,
  `plan_type_id` bigint UNSIGNED NOT NULL,
  `seat_id` bigint UNSIGNED NOT NULL,
  `hours` int DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `is_paid` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `learner_detail_library_id_foreign` (`library_id`),
  KEY `learner_detail_plan_price_id_foreign` (`plan_price_id`),
  KEY `learner_detail_plan_id_foreign` (`plan_id`),
  KEY `learner_detail_plan_type_id_foreign` (`plan_type_id`),
  KEY `learner_detail_seat_id_foreign` (`seat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `learner_transactions`
--

DROP TABLE IF EXISTS `learner_transactions`;
CREATE TABLE IF NOT EXISTS `learner_transactions` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `library_id` bigint UNSIGNED NOT NULL,
  `learner_id` bigint UNSIGNED NOT NULL,
  `total_amount` decimal(8,2) NOT NULL,
  `paid_amount` decimal(8,2) NOT NULL,
  `pending_amount` decimal(8,2) NOT NULL,
  `paid_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `library_transactions_library_id_foreign` (`library_id`),
  KEY `library_transactions_learner_id_foreign` (`learner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `libraries`
--

DROP TABLE IF EXISTS `libraries`;
CREATE TABLE IF NOT EXISTS `libraries` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `library_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `library_mobile` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `library_address` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `library_zip` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `library_logo` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `library_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `library_owner` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `is_paid` tinyint(1) NOT NULL DEFAULT '0',
  `library_no` int DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `email_otp` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `libraries`
--

INSERT INTO `libraries` (`id`, `library_name`, `email`, `library_mobile`, `username`, `password`, `state_id`, `city_id`, `library_address`, `library_zip`, `library_logo`, `library_type`, `library_owner`, `status`, `is_paid`, `library_no`, `email_verified_at`, `email_otp`, `created_at`, `updated_at`, `deleted_at`) VALUES
(54, 'LIbrary One', 'libraryone@gmail.com', '1234546897', NULL, '$2y$12$Yj1SKWmwbNRzDvajRckg2eGh0LEIpDEbo92gENjZD5Cyfhd8DPXAW', NULL, NULL, NULL, NULL, NULL, '2', NULL, 1, 0, NULL, '2024-09-14 21:41:47', 'xK8ttk', '2024-09-14 21:37:08', '2024-09-16 07:00:18', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `library_transactions`
--

DROP TABLE IF EXISTS `library_transactions`;
CREATE TABLE IF NOT EXISTS `library_transactions` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `library_id` bigint UNSIGNED NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `month` int DEFAULT NULL,
  `transaction_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_mode` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(8,2) DEFAULT NULL,
  `paid_amount` decimal(8,2) DEFAULT NULL,
  `transaction_date` date DEFAULT NULL,
  `is_paid` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `library_transactions`
--

INSERT INTO `library_transactions` (`id`, `library_id`, `start_date`, `end_date`, `month`, `transaction_id`, `payment_mode`, `amount`, `paid_amount`, `transaction_date`, `is_paid`, `created_at`, `updated_at`) VALUES
(3, 54, NULL, NULL, 1, NULL, NULL, 200.00, NULL, NULL, 0, '2024-09-15 13:42:53', '2024-09-15 13:42:53'),
(5, 54, '2024-09-16', NULL, 1, NULL, NULL, 200.00, NULL, '2024-09-16', 1, '2024-09-16 07:00:18', '2024-09-16 07:00:18');

-- --------------------------------------------------------

--
-- Table structure for table `library_user`
--

DROP TABLE IF EXISTS `library_user`;
CREATE TABLE IF NOT EXISTS `library_user` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint UNSIGNED NOT NULL,
  `library_id` bigint UNSIGNED NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `library_user_user_id_foreign` (`user_id`),
  KEY `library_user_library_id_foreign` (`library_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `menus`
--

DROP TABLE IF EXISTS `menus`;
CREATE TABLE IF NOT EXISTS `menus` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icon` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parent_id` bigint UNSIGNED DEFAULT NULL,
  `order` int NOT NULL DEFAULT '0',
  `status` int NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `menus_parent_id_foreign` (`parent_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `menus`
--

INSERT INTO `menus` (`id`, `name`, `url`, `icon`, `parent_id`, `order`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Dashboard', 'home', 'fa fa-fw fa-cog\r\n', NULL, 1, 1, NULL, NULL, NULL),
(2, 'Library', 'library', 'fa fa-fw fa-cog\r\n', NULL, 2, 1, NULL, NULL, NULL),
(3, 'Library Plan', 'subscriptions.choosePlan', NULL, NULL, 0, 1, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE IF NOT EXISTS `migrations` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `migration` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2014_10_12_300000_create_password_reset_tokens_table', 1),
(2, '2014_10_12_300000_create_password_resets_table', 1),
(3, '2014_10_12_300001_create_libraries_table', 1),
(4, '2014_10_12_300002_create_users_table', 1),
(5, '2019_08_19_300000_create_failed_jobs_table', 1),
(6, '2019_12_14_000001_create_personal_access_tokens_table', 1),
(7, '2024_07_02_325400_create_permission_tables', 1),
(8, '2024_07_18_032930_create_hour_table', 1),
(9, '2024_07_18_032930_create_plans_table', 1),
(10, '2024_07_18_064646_create_plan_types_table', 1),
(11, '2024_07_18_102144_create_plan_prices_table', 1),
(12, '2024_07_19_093657_create_seats_table', 1),
(13, '2024_07_20_085758_create_customers_table', 1),
(14, '2024_07_26_085829_create_states_table', 1),
(15, '2024_07_26_094213_create_cities_table', 1),
(16, '2024_07_26_095228_create_grades_table', 1),
(17, '2024_07_26_095329_create_courses_table', 1),
(18, '2024_07_26_101933_create_course_types_table', 1),
(19, '2024_07_27_132928_create_students_table', 1),
(20, '2024_07_29_163245_create_learners_table', 1),
(21, '2024_07_29_163246_create_library_transactions_table', 1),
(22, '2024_07_29_163247_create_learner_detail_table', 1),
(23, '2024_08_01_164742_create_menus_table', 1),
(24, '2024_08_01_164752_create_sub_menus_table', 1),
(25, '2024_08_22_165753_create_monthly_expense_table', 1),
(26, '2024_08_28_021755_create_expenses_table', 1),
(27, '2024_09_01_055654_create_library_user_table', 1),
(28, '2024_08_01_165742_create_menus_table', 2),
(29, '2024_09_10_155059_create_subscriptions_table', 3),
(30, '2024_09_10_155060_create_user_subscription_table', 3),
(31, '2024_09_10_155061_create_subscription_permission_table', 3);

-- --------------------------------------------------------

--
-- Table structure for table `model_has_permissions`
--

DROP TABLE IF EXISTS `model_has_permissions`;
CREATE TABLE IF NOT EXISTS `model_has_permissions` (
  `permission_id` bigint UNSIGNED NOT NULL,
  `model_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint UNSIGNED NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `model_has_roles`
--

DROP TABLE IF EXISTS `model_has_roles`;
CREATE TABLE IF NOT EXISTS `model_has_roles` (
  `role_id` bigint UNSIGNED NOT NULL,
  `model_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint UNSIGNED NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `model_has_roles`
--

INSERT INTO `model_has_roles` (`role_id`, `model_type`, `model_id`) VALUES
(1, 'App\\Models\\User', 1),
(2, 'App\\Models\\Library', 2),
(2, 'App\\Models\\Library', 3),
(2, 'App\\Models\\Library', 53),
(2, 'App\\Models\\Library', 54);

-- --------------------------------------------------------

--
-- Table structure for table `monthly_expense`
--

DROP TABLE IF EXISTS `monthly_expense`;
CREATE TABLE IF NOT EXISTS `monthly_expense` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `library_id` bigint UNSIGNED NOT NULL,
  `project` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `month` int DEFAULT NULL,
  `year` int DEFAULT NULL,
  `expense_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `monthly_expense_library_id_foreign` (`library_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE IF NOT EXISTS `password_resets` (
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
CREATE TABLE IF NOT EXISTS `permissions` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'view dashboard', 'web', '2024-09-01 21:32:44', '2024-09-01 21:32:44'),
(2, 'user-create', 'web', '2024-09-01 21:32:44', '2024-09-01 21:32:44'),
(3, 'user-edit', 'web', '2024-09-01 21:32:44', '2024-09-01 21:32:44'),
(4, 'user-delete', 'web', '2024-09-01 21:32:44', '2024-09-01 21:32:44'),
(5, 'request-create', 'web', '2024-09-01 21:32:44', '2024-09-01 21:32:44'),
(6, 'request-show', 'web', '2024-09-01 21:32:44', '2024-09-01 21:32:44'),
(7, 'request-edit', 'web', '2024-09-01 21:32:44', '2024-09-01 21:32:44'),
(8, 'request-delete', 'web', '2024-09-01 21:32:44', '2024-09-01 21:32:44'),
(9, 'request-approved', 'web', '2024-09-01 21:32:44', '2024-09-01 21:32:44'),
(10, 'Swap Seat', 'library', NULL, NULL),
(11, 'upgrade seat', 'library', NULL, NULL),
(12, 'learner create', 'library', NULL, NULL),
(13, 'full day', 'library', NULL, NULL),
(14, 'Half day', 'library', NULL, NULL),
(15, 'Haourly', 'library', NULL, NULL),
(16, 'Renew', 'library', NULL, NULL),
(17, 'Online PAyment', 'library', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
CREATE TABLE IF NOT EXISTS `personal_access_tokens` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint UNSIGNED NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `plans`
--

DROP TABLE IF EXISTS `plans`;
CREATE TABLE IF NOT EXISTS `plans` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `library_id` bigint UNSIGNED NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `plan_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `plans_library_id_foreign` (`library_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `plan_prices`
--

DROP TABLE IF EXISTS `plan_prices`;
CREATE TABLE IF NOT EXISTS `plan_prices` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `library_id` bigint UNSIGNED NOT NULL,
  `plan_id` bigint UNSIGNED NOT NULL,
  `plan_type_id` bigint UNSIGNED NOT NULL,
  `price` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `plan_prices_library_id_foreign` (`library_id`),
  KEY `plan_prices_plan_id_foreign` (`plan_id`),
  KEY `plan_prices_plan_type_id_foreign` (`plan_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `plan_types`
--

DROP TABLE IF EXISTS `plan_types`;
CREATE TABLE IF NOT EXISTS `plan_types` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `library_id` bigint UNSIGNED NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_time` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `end_time` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `slot_hours` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `day_type_id` int DEFAULT NULL,
  `image` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `plan_types_library_id_foreign` (`library_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE IF NOT EXISTS `roles` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'superadmin', 'web', '2024-09-01 21:32:44', '2024-09-01 21:32:44'),
(2, 'admin', 'library', '2024-09-01 21:32:44', '2024-09-01 21:32:44');

-- --------------------------------------------------------

--
-- Table structure for table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
CREATE TABLE IF NOT EXISTS `role_has_permissions` (
  `permission_id` bigint UNSIGNED NOT NULL,
  `role_id` bigint UNSIGNED NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_has_permissions`
--

INSERT INTO `role_has_permissions` (`permission_id`, `role_id`) VALUES
(1, 1),
(2, 1),
(3, 1),
(4, 1),
(5, 1),
(6, 1),
(7, 1),
(8, 1),
(9, 1),
(1, 2),
(2, 2),
(3, 2),
(4, 2),
(5, 2),
(6, 2),
(7, 2),
(8, 2),
(9, 2);

-- --------------------------------------------------------

--
-- Table structure for table `seats`
--

DROP TABLE IF EXISTS `seats`;
CREATE TABLE IF NOT EXISTS `seats` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `library_id` bigint UNSIGNED NOT NULL,
  `seat_no` int NOT NULL,
  `is_available` tinyint(1) NOT NULL DEFAULT '1',
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `total_hours` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `seats_library_id_foreign` (`library_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `states`
--

DROP TABLE IF EXISTS `states`;
CREATE TABLE IF NOT EXISTS `states` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `state_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `states`
--

INSERT INTO `states` (`id`, `state_name`, `is_active`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Rajasthan', 1, NULL, NULL, NULL),
(2, 'Utter Pradesh', 1, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
CREATE TABLE IF NOT EXISTS `students` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `form_no` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mobile` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alt_mobile` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `father_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dob` date NOT NULL,
  `gender` enum('male','female') COLLATE utf8mb4_unicode_ci NOT NULL,
  `grade_id` bigint UNSIGNED NOT NULL,
  `stream` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state_id` bigint UNSIGNED NOT NULL,
  `city_id` bigint UNSIGNED NOT NULL,
  `address` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `pin_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `course_type_id` bigint UNSIGNED NOT NULL,
  `course_id` bigint UNSIGNED NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `is_paid` tinyint(1) NOT NULL DEFAULT '0',
  `is_certificate` tinyint(1) NOT NULL DEFAULT '0',
  `profile_image` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `students_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscriptions`
--

DROP TABLE IF EXISTS `subscriptions`;
CREATE TABLE IF NOT EXISTS `subscriptions` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `monthly_fees` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `yearly_fees` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subscriptions`
--

INSERT INTO `subscriptions` (`id`, `name`, `monthly_fees`, `yearly_fees`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Basic Plan', '100', '1000', '2024-09-10 11:07:45', '2024-09-10 11:07:45', NULL),
(2, 'Pro Plan', '200', '2200', '2024-09-10 11:07:54', '2024-09-10 11:07:54', NULL),
(3, 'Premiume Plan', '300', '3500', '2024-09-10 11:08:05', '2024-09-10 11:08:05', NULL),
(4, 'Standard', '400', '4500', '2024-09-14 23:12:09', '2024-09-14 23:12:09', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `subscription_permission`
--

DROP TABLE IF EXISTS `subscription_permission`;
CREATE TABLE IF NOT EXISTS `subscription_permission` (
  `subscription_id` bigint UNSIGNED NOT NULL,
  `permission_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  KEY `subscription_permission_subscription_id_foreign` (`subscription_id`),
  KEY `subscription_permission_permission_id_foreign` (`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subscription_permission`
--

INSERT INTO `subscription_permission` (`subscription_id`, `permission_id`, `created_at`, `updated_at`) VALUES
(1, 12, NULL, NULL),
(1, 13, NULL, NULL),
(2, 7, NULL, NULL),
(2, 9, NULL, NULL),
(2, 10, NULL, NULL),
(2, 11, NULL, NULL),
(2, 13, NULL, NULL),
(3, 9, NULL, NULL),
(3, 10, NULL, NULL),
(3, 11, NULL, NULL),
(3, 12, NULL, NULL),
(3, 13, NULL, NULL),
(3, 14, NULL, NULL),
(3, 15, NULL, NULL),
(3, 16, NULL, NULL),
(3, 17, NULL, NULL),
(4, 1, NULL, NULL),
(4, 2, NULL, NULL),
(4, 3, NULL, NULL),
(4, 4, NULL, NULL),
(4, 7, NULL, NULL),
(4, 10, NULL, NULL),
(4, 11, NULL, NULL),
(4, 12, NULL, NULL),
(4, 13, NULL, NULL),
(4, 14, NULL, NULL),
(4, 15, NULL, NULL),
(4, 16, NULL, NULL),
(4, 17, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sub_menus`
--

DROP TABLE IF EXISTS `sub_menus`;
CREATE TABLE IF NOT EXISTS `sub_menus` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent_id` int DEFAULT NULL,
  `slug` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `icon` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `order` int NOT NULL DEFAULT '0',
  `status` int NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sub_menus`
--

INSERT INTO `sub_menus` (`id`, `name`, `url`, `parent_id`, `slug`, `icon`, `order`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Library', 'library', 2, 'library', NULL, 1, 1, NULL, NULL, NULL),
(2, 'Subscriptions', 'subscriptions.permissions', 2, '', NULL, 0, 1, NULL, NULL, NULL),
(3, 'Subscription Plan', 'subscriptions.choosePlan', 3, '', NULL, 0, 1, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mobile` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `username`, `mobile`, `address`, `remember_token`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Super Admin', 'superadmin@admin.com', '$2y$12$fVO88A2SCydAdCKdBKAaBuSm6MrRzZuEETIgEAl/uH5jsW.pfxQM2', NULL, NULL, NULL, NULL, '2024-09-01 21:32:45', '2024-09-10 20:44:04', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `user_subscription`
--

DROP TABLE IF EXISTS `user_subscription`;
CREATE TABLE IF NOT EXISTS `user_subscription` (
  `user_id` bigint UNSIGNED NOT NULL,
  `subscription_id` bigint UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  KEY `user_subscription_user_id_foreign` (`user_id`),
  KEY `user_subscription_subscription_id_foreign` (`subscription_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cities`
--
ALTER TABLE `cities`
  ADD CONSTRAINT `cities_state_id_foreign` FOREIGN KEY (`state_id`) REFERENCES `states` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_library_id_foreign` FOREIGN KEY (`library_id`) REFERENCES `libraries` (`id`);

--
-- Constraints for table `hour`
--
ALTER TABLE `hour`
  ADD CONSTRAINT `hour_library_id_foreign` FOREIGN KEY (`library_id`) REFERENCES `libraries` (`id`);

--
-- Constraints for table `learners`
--
ALTER TABLE `learners`
  ADD CONSTRAINT `learners_library_id_foreign` FOREIGN KEY (`library_id`) REFERENCES `libraries` (`id`),
  ADD CONSTRAINT `learners_seat_no_foreign` FOREIGN KEY (`seat_no`) REFERENCES `seats` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `learner_detail`
--
ALTER TABLE `learner_detail`
  ADD CONSTRAINT `learner_detail_library_id_foreign` FOREIGN KEY (`library_id`) REFERENCES `libraries` (`id`),
  ADD CONSTRAINT `learner_detail_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`),
  ADD CONSTRAINT `learner_detail_plan_price_id_foreign` FOREIGN KEY (`plan_price_id`) REFERENCES `plan_prices` (`id`),
  ADD CONSTRAINT `learner_detail_plan_type_id_foreign` FOREIGN KEY (`plan_type_id`) REFERENCES `plan_types` (`id`),
  ADD CONSTRAINT `learner_detail_seat_id_foreign` FOREIGN KEY (`seat_id`) REFERENCES `seats` (`id`);

--
-- Constraints for table `learner_transactions`
--
ALTER TABLE `learner_transactions`
  ADD CONSTRAINT `library_transactions_learner_id_foreign` FOREIGN KEY (`learner_id`) REFERENCES `learners` (`id`),
  ADD CONSTRAINT `library_transactions_library_id_foreign` FOREIGN KEY (`library_id`) REFERENCES `libraries` (`id`);

--
-- Constraints for table `library_user`
--
ALTER TABLE `library_user`
  ADD CONSTRAINT `library_user_library_id_foreign` FOREIGN KEY (`library_id`) REFERENCES `libraries` (`id`),
  ADD CONSTRAINT `library_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `menus`
--
ALTER TABLE `menus`
  ADD CONSTRAINT `menus_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_permissions`
--
ALTER TABLE `model_has_permissions`
  ADD CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `model_has_roles`
--
ALTER TABLE `model_has_roles`
  ADD CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `monthly_expense`
--
ALTER TABLE `monthly_expense`
  ADD CONSTRAINT `monthly_expense_library_id_foreign` FOREIGN KEY (`library_id`) REFERENCES `libraries` (`id`);

--
-- Constraints for table `plans`
--
ALTER TABLE `plans`
  ADD CONSTRAINT `plans_library_id_foreign` FOREIGN KEY (`library_id`) REFERENCES `libraries` (`id`);

--
-- Constraints for table `plan_prices`
--
ALTER TABLE `plan_prices`
  ADD CONSTRAINT `plan_prices_library_id_foreign` FOREIGN KEY (`library_id`) REFERENCES `libraries` (`id`),
  ADD CONSTRAINT `plan_prices_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`),
  ADD CONSTRAINT `plan_prices_plan_type_id_foreign` FOREIGN KEY (`plan_type_id`) REFERENCES `plan_types` (`id`);

--
-- Constraints for table `plan_types`
--
ALTER TABLE `plan_types`
  ADD CONSTRAINT `plan_types_library_id_foreign` FOREIGN KEY (`library_id`) REFERENCES `libraries` (`id`);

--
-- Constraints for table `role_has_permissions`
--
ALTER TABLE `role_has_permissions`
  ADD CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seats`
--
ALTER TABLE `seats`
  ADD CONSTRAINT `seats_library_id_foreign` FOREIGN KEY (`library_id`) REFERENCES `libraries` (`id`);

--
-- Constraints for table `subscription_permission`
--
ALTER TABLE `subscription_permission`
  ADD CONSTRAINT `subscription_permission_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subscription_permission_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_subscription`
--
ALTER TABLE `user_subscription`
  ADD CONSTRAINT `user_subscription_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `subscriptions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_subscription_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
