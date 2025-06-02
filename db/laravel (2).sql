-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jan 19, 2025 at 06:50 AM
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
  `city_name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cities_state_id_foreign` (`state_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cities`
--

INSERT INTO `cities` (`id`, `state_id`, `city_name`, `is_active`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 1, 'Kota', 1, NULL, NULL, NULL),
(2, 1, 'Bundi', 1, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `email_message`
--

DROP TABLE IF EXISTS `email_message`;
CREATE TABLE IF NOT EXISTS `email_message` (
  `id` int NOT NULL AUTO_INCREMENT,
  `library_id` bigint UNSIGNED NOT NULL,
  `learner_id` bigint UNSIGNED NOT NULL,
  `learner_email` int NOT NULL DEFAULT '0',
  `learner_message` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

DROP TABLE IF EXISTS `expenses`;
CREATE TABLE IF NOT EXISTS `expenses` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `library_id` bigint UNSIGNED NOT NULL,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expenses_library_id_foreign` (`library_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Table structure for table `features`
--

DROP TABLE IF EXISTS `features`;
CREATE TABLE IF NOT EXISTS `features` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `image` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `features`
--

INSERT INTO `features` (`id`, `name`, `image`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Wi-Fi Access', NULL, '2024-12-31 02:07:44', NULL, NULL),
(2, 'Air Conditioning (AC)', NULL, '2024-12-31 02:07:44', NULL, NULL),
(3, 'Individual Reading Lamps', NULL, '2024-12-31 02:08:11', NULL, NULL),
(4, 'Charging Ports (at least 2 per seat)', NULL, '2024-12-31 02:08:11', NULL, NULL),
(5, 'Charging Ports (at least 2 per seat)', NULL, '2024-12-31 02:08:33', NULL, NULL),
(6, 'Separate Space for Each Learner', 'uploads/icon/icon1735919374.png', '2024-12-31 02:08:33', '2025-01-03 10:19:34', NULL),
(13, 'dfgdfg23', 'uploads/icon/icon1735919342.jpg', '2025-01-03 10:18:18', '2025-01-03 10:19:08', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

DROP TABLE IF EXISTS `feedback`;
CREATE TABLE IF NOT EXISTS `feedback` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `library_id` bigint UNSIGNED NOT NULL,
  `feedback_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `rating` tinyint UNSIGNED NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attachment` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recommend` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `feedback_library_id_foreign` (`library_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gst_discount`
--

DROP TABLE IF EXISTS `gst_discount`;
CREATE TABLE IF NOT EXISTS `gst_discount` (
  `id` int NOT NULL AUTO_INCREMENT,
  `gst` int DEFAULT NULL,
  `discount` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gst_discount`
--

INSERT INTO `gst_discount` (`id`, `gst`, `discount`, `created_at`, `updated_at`) VALUES
(1, 12, 15, '2024-10-13 05:57:42', '2024-10-13 05:57:42');

-- --------------------------------------------------------

--
-- Table structure for table `hour`
--

DROP TABLE IF EXISTS `hour`;
CREATE TABLE IF NOT EXISTS `hour` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `library_id` bigint UNSIGNED NOT NULL,
  `hour` int DEFAULT NULL,
  `extend_days` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hour_library_id_foreign` (`library_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inquiries`
--

DROP TABLE IF EXISTS `inquiries`;
CREATE TABLE IF NOT EXISTS `inquiries` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mobile_number` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
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
  `address` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_proof_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_proof_file` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hours` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '0',
  `seat_no` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_mode` int DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
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
  `plan_price_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `plan_id` bigint UNSIGNED NOT NULL,
  `plan_type_id` bigint UNSIGNED NOT NULL,
  `seat_id` bigint UNSIGNED NOT NULL,
  `hour` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '0',
  `payment_mode` int DEFAULT NULL,
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
-- Table structure for table `learner_operations_log`
--

DROP TABLE IF EXISTS `learner_operations_log`;
CREATE TABLE IF NOT EXISTS `learner_operations_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `learner_id` int NOT NULL,
  `learner_detail_id` bigint UNSIGNED DEFAULT NULL,
  `library_id` bigint NOT NULL,
  `operation` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `field_updated` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `old_value` text COLLATE utf8mb4_general_ci,
  `new_value` text COLLATE utf8mb4_general_ci,
  `summary` text COLLATE utf8mb4_general_ci,
  `updated_by` bigint UNSIGNED NOT NULL COMMENT 'user_id',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `learner_request`
--

DROP TABLE IF EXISTS `learner_request`;
CREATE TABLE IF NOT EXISTS `learner_request` (
  `id` int NOT NULL AUTO_INCREMENT,
  `learner_id` bigint UNSIGNED DEFAULT NULL,
  `request_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `request_date` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `request_status` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `learner_transactions`
--

DROP TABLE IF EXISTS `learner_transactions`;
CREATE TABLE IF NOT EXISTS `learner_transactions` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `library_id` bigint UNSIGNED NOT NULL,
  `learner_id` bigint UNSIGNED NOT NULL,
  `learner_detail_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_amount` decimal(8,2) NOT NULL,
  `paid_amount` decimal(8,2) NOT NULL,
  `pending_amount` decimal(8,2) NOT NULL,
  `paid_date` date NOT NULL,
  `transaction_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_paid` tinyint NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `learner_transactions_library_id_foreign` (`library_id`),
  KEY `learner_transactions_learner_id_foreign` (`learner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `libraries`
--

DROP TABLE IF EXISTS `libraries`;
CREATE TABLE IF NOT EXISTS `libraries` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `library_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `library_mobile` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `library_address` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `library_zip` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `library_logo` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `library_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `library_owner` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `library_owner_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `library_owner_contact` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `is_paid` tinyint(1) NOT NULL DEFAULT '0',
  `is_profile` tinyint NOT NULL DEFAULT '0',
  `library_no` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_otp` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `features` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `library_activity_log`
--

DROP TABLE IF EXISTS `library_activity_log`;
CREATE TABLE IF NOT EXISTS `library_activity_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `library_id` bigint UNSIGNED NOT NULL,
  `method` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `status_code` int NOT NULL,
  `ip_address` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `action` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `request_body` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `library_settings`
--

DROP TABLE IF EXISTS `library_settings`;
CREATE TABLE IF NOT EXISTS `library_settings` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `library_id` bigint UNSIGNED NOT NULL,
  `library_favicon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `library_title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `library_meta_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `library_primary_color` varchar(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `library_language` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `library_settings_library_id_foreign` (`library_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `subscription` bigint UNSIGNED DEFAULT NULL,
  `transaction_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_mode` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(8,2) DEFAULT NULL,
  `paid_amount` decimal(8,2) DEFAULT NULL,
  `transaction_date` date DEFAULT NULL,
  `gst` int DEFAULT NULL,
  `discount` int DEFAULT NULL,
  `is_paid` tinyint(1) NOT NULL DEFAULT '0',
  `status` tinyint NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `url` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icon` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parent_id` bigint UNSIGNED DEFAULT NULL,
  `order` int NOT NULL DEFAULT '0',
  `guard` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` int NOT NULL DEFAULT '1',
  `has_permissions` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `role_id` bigint UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `menus_parent_id_foreign` (`parent_id`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `menus`
--

INSERT INTO `menus` (`id`, `name`, `url`, `icon`, `parent_id`, `order`, `guard`, `status`, `has_permissions`, `created_at`, `updated_at`, `deleted_at`, `role_id`) VALUES
(1, 'Dashboard', 'home', 'fa fa-cog', NULL, 1, 'web', 1, '', '2024-09-19 00:40:33', '2024-09-19 00:40:33', NULL, NULL),
(2, 'Manage Library', 'library', 'fa fa-building', NULL, 2, NULL, 1, NULL, '2024-09-19 00:40:33', '2024-09-19 00:40:33', NULL, NULL),
(4, 'Library List', 'library', 'fa fa-angle-right', 2, 2, 'web', 1, NULL, '2024-09-19 00:40:33', '2024-09-19 00:40:33', NULL, NULL),
(5, 'Add Subscription', 'subscription.master', NULL, 31, 2, 'web', 1, NULL, '2024-09-19 00:40:33', '2024-09-19 00:40:33', NULL, NULL),
(7, 'Dashboard', 'library.home', 'fa fa-dashboard', NULL, 1, 'library', 1, 'Dashboard', NULL, NULL, NULL, NULL),
(9, 'Seat Assignment', 'seats', NULL, 2, 1, 'library', 1, 'Seat Assignment', NULL, NULL, NULL, NULL),
(10, 'Learner List', 'learners', NULL, 2, 1, 'library', 1, 'Learner List', NULL, NULL, NULL, NULL),
(11, 'Seat History', 'seats.history', NULL, 2, 3, 'library', 1, 'Seat History', NULL, NULL, NULL, NULL),
(12, 'Learner History', 'learnerHistory', NULL, 2, 4, 'library', 1, 'Learners History', NULL, NULL, NULL, NULL),
(13, 'Manage Account', 'library.myplan', 'fa fa-user-tie', NULL, 5, 'library', 1, 'Manage Account', NULL, NULL, NULL, NULL),
(14, 'My Plan', 'library.myplan', NULL, 13, 1, 'library', 1, 'Show Plan Info', NULL, NULL, NULL, NULL),
(15, 'My Profile', 'profile', NULL, 13, 2, 'library', 1, NULL, NULL, NULL, NULL, NULL),
(16, 'My Transaction', 'library.transaction', NULL, 13, 3, 'library', 1, NULL, NULL, NULL, NULL, NULL),
(17, 'Library Configration', 'library.master', NULL, 13, 4, 'library', 1, NULL, NULL, NULL, NULL, NULL),
(18, 'Manage Permissions', 'permissions', 'fa fa-keys', 2, 3, 'web', 1, NULL, NULL, NULL, NULL, NULL),
(19, 'Manage Report', 'report.monthly', 'fa fa-chart-simple', NULL, 6, 'library', 1, 'Manage Report', NULL, NULL, NULL, NULL),
(20, 'Monthly Report', 'report.monthly', NULL, 19, 1, 'library', 1, 'Monthly Report', NULL, NULL, NULL, NULL),
(21, 'Import Learners', 'library.upload.form', NULL, 2, 0, 'library', 1, NULL, NULL, NULL, '2024-10-01 10:14:48', NULL),
(23, 'Library Settings', 'library.settings', 'fa fa-cog fa-spin', NULL, 10, 'library', 1, NULL, NULL, NULL, NULL, NULL),
(24, 'Feedback', 'library.feedback', 'fa-solid fa-comment', NULL, 9, 'library', 1, 'Feedback', NULL, NULL, NULL, NULL),
(25, 'Suggestions', 'library', 'fa-solid fa-comment-dots', NULL, 8, 'library', 1, NULL, NULL, NULL, '2024-11-01 17:03:20', NULL),
(26, 'Video Training', 'library', 'fa fa-video', NULL, 7, 'library', 1, NULL, NULL, NULL, NULL, NULL),
(27, 'Pending Payment Report', 'pending.payment.report', NULL, 19, 1, NULL, 1, NULL, NULL, NULL, NULL, NULL),
(28, 'Lerner\'s report', 'learner.report', NULL, 19, 2, NULL, 1, NULL, NULL, NULL, NULL, NULL),
(29, 'Upcoming Payment Report', 'upcoming.payment.report', NULL, 19, 3, NULL, 1, NULL, NULL, NULL, NULL, NULL),
(30, 'Expired Learners Report', 'expired.learner.report', NULL, 19, 4, NULL, 1, NULL, NULL, NULL, NULL, NULL),
(31, 'Manage Subscriptions', 'expired.learner.report', 'fa fa-cog', NULL, 1, 'web', 1, NULL, NULL, NULL, NULL, NULL),
(33, 'Add Permission to Plan', 'expired.learner.report', NULL, 31, 0, NULL, 1, NULL, NULL, NULL, NULL, NULL),
(34, 'Notification', 'create.notification', NULL, NULL, 4, 'web', 1, NULL, NULL, NULL, NULL, NULL),
(35, 'Feature', 'feature.create', NULL, 2, 0, 'web', 1, NULL, NULL, NULL, NULL, NULL);

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
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(31, '2024_09_10_155061_create_subscription_permission_table', 3),
(32, '2024_10_21_183227_create_permission_categories_table', 4),
(33, '2024_10_21_183609_add_permission_category_id_to_permissions_table', 5),
(34, '2024_12_10_031053_create_temp_orders_table', 6),
(35, '2025_01_17_161329_create_inquiries_table', 7);

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
(3, 'App\\Models\\Learner', 1),
(1, 'App\\Models\\User', 1),
(2, 'App\\Models\\Library', 2),
(2, 'App\\Models\\Library', 3),
(2, 'App\\Models\\Library', 53),
(2, 'App\\Models\\Library', 54),
(2, 'App\\Models\\Library', 58),
(2, 'App\\Models\\Library', 59),
(2, 'App\\Models\\Library', 60),
(2, 'App\\Models\\Library', 61),
(2, 'App\\Models\\Library', 62),
(2, 'App\\Models\\Library', 63),
(2, 'App\\Models\\Library', 64),
(2, 'App\\Models\\Library', 65),
(2, 'App\\Models\\Library', 66),
(2, 'App\\Models\\Library', 67),
(2, 'App\\Models\\Library', 68),
(2, 'App\\Models\\Library', 69),
(2, 'App\\Models\\Library', 70),
(2, 'App\\Models\\Library', 71),
(2, 'App\\Models\\Library', 72),
(2, 'App\\Models\\Library', 73),
(2, 'App\\Models\\Library', 75),
(2, 'App\\Models\\Library', 76),
(2, 'App\\Models\\Library', 77),
(2, 'App\\Models\\Library', 78),
(2, 'App\\Models\\Library', 79),
(2, 'App\\Models\\Library', 80),
(2, 'App\\Models\\Library', 81),
(2, 'App\\Models\\Library', 82),
(2, 'App\\Models\\Library', 83),
(2, 'App\\Models\\Library', 84),
(2, 'App\\Models\\Library', 85),
(2, 'App\\Models\\Library', 86),
(2, 'App\\Models\\Library', 87),
(2, 'App\\Models\\Library', 88),
(2, 'App\\Models\\Library', 89),
(2, 'App\\Models\\Library', 90),
(2, 'App\\Models\\Library', 91),
(2, 'App\\Models\\Library', 92),
(2, 'App\\Models\\Library', 93),
(2, 'App\\Models\\Library', 94),
(2, 'App\\Models\\Library', 95),
(2, 'App\\Models\\Library', 96);

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
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch_id` int DEFAULT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_id` bigint UNSIGNED NOT NULL,
  `data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`email`, `token`, `created_at`) VALUES
('heena.kaushar@allenoverseas.com', 'Fvt1Bv4sRzmNe63YadaEqgoRjrhFtp5VxTZJKFokubNs0oXuuULAemq9S1NM', '2024-09-21 22:59:49');

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

--
-- Dumping data for table `password_reset_tokens`
--

INSERT INTO `password_reset_tokens` (`email`, `token`, `created_at`) VALUES
('superadmin@admin.com', '$2y$12$lMjrfWDLZU10jczPwUpfLO6iWg4xRsHJSiS7I/y5fFAUzfL9Xuh4e', '2024-09-21 22:03:20');

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
CREATE TABLE IF NOT EXISTS `permissions` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guard_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `permission_category_id` bigint UNSIGNED DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`),
  KEY `permissions_permission_category_id_foreign` (`permission_category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=86 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `slug`, `guard_name`, `permission_category_id`, `description`, `created_at`, `updated_at`) VALUES
(1, 'View Seat', NULL, 'library', 3, 'Allow us to view the Learners Information', '2024-10-02 06:58:33', '2024-10-23 12:51:29'),
(2, 'Seat Booking', NULL, 'library', 3, 'Allow us to book a seat in library', '2024-10-02 06:58:33', '2024-10-23 12:52:06'),
(3, 'Edit Seat', NULL, 'library', 3, 'Allow us to edit a seat in Library', '2024-10-02 06:58:33', '2024-10-23 12:52:28'),
(5, 'Renew Seat', NULL, 'library', 3, 'Allow us to Re-New Learner Plan in Library', '2024-10-02 06:58:33', '2024-10-23 12:54:34'),
(6, 'Swap Seat', NULL, 'library', 3, 'Allow us to swap seat in Library', '2024-10-02 06:58:33', '2024-10-23 12:54:57'),
(7, 'Upgrade Seat Plan', NULL, 'library', 3, 'Allow us to Upgrade learners plan form Lower to uppder and upper to lower', '2024-10-02 06:58:33', '2024-10-23 12:55:44'),
(8, 'Reactive Seat', NULL, 'library', 3, 'Allow us to reactive an expired user with same information on different seat', '2024-10-02 06:58:33', '2024-10-23 13:02:50'),
(9, 'Extend Seat', NULL, 'library', 3, 'Allow us to extend seat expiration period according to Library Extend Period.', '2024-10-02 06:58:33', '2024-10-23 13:03:45'),
(10, 'Library Analytics (Report Menu)', NULL, 'library', 9, NULL, '2024-10-02 06:58:33', '2024-10-23 13:04:13'),
(11, 'Email Notification', NULL, 'library', 14, 'Allow us to send Email Notification to Learner.', '2024-10-02 06:58:33', '2024-10-23 13:04:53'),
(12, 'WhatsApp Notification', NULL, 'library', 14, 'Allow us to send WhatsApp notification to Learner', '2024-10-02 06:58:33', '2024-10-23 13:05:21'),
(13, 'Receipt Generation', NULL, 'library', 3, 'Allow us to Generate Learner payment Receipt.', '2024-10-02 06:58:33', '2024-10-23 13:05:49'),
(14, 'Import Library Seats', NULL, 'library', 12, 'Allow library to Import Previous Records on Import Facility', '2024-10-02 06:58:33', '2024-10-24 07:24:44'),
(15, 'Suggestions', NULL, 'library', 14, 'Add Suggestion Menu to Library', '2024-10-02 06:58:33', '2024-10-24 07:26:19'),
(16, 'Feedback', NULL, 'library', 14, 'Add Feedback menu to Library', '2024-10-02 06:58:33', '2024-10-24 07:26:40'),
(17, 'Complaints', NULL, 'library', 14, 'Add Complaints option to Library', '2024-10-02 06:58:33', '2024-10-24 07:27:06'),
(19, 'Learner Login', NULL, 'library', 12, 'Allow learner to Login', '2024-10-02 06:58:33', '2024-10-24 07:27:50'),
(20, 'Delete Seat', NULL, 'library', 3, 'Allow Library to delete Learner Seat', '2024-10-03 10:55:18', '2024-10-24 07:28:25'),
(21, 'Close Seat', NULL, 'library', 3, 'Allow Library to close anyone Plan immediately', '2024-10-03 10:55:37', '2024-10-24 07:29:00'),
(22, 'Seat History', NULL, 'library', 2, 'Allow Library to Show this Menu', '2024-10-03 10:57:50', '2024-10-24 07:29:34'),
(23, 'Dashboard', NULL, 'library', 2, 'Allow Library to Show this Dashboard Menu', '2024-10-03 10:58:38', '2024-10-24 07:30:22'),
(24, 'Learners History', NULL, 'library', 2, 'Allow Library to Show Learners History Menu', '2024-10-03 10:58:59', '2024-10-24 07:30:47'),
(28, 'Filter', NULL, 'library', 12, 'Allow Library to use Filter Option', '2024-10-03 11:01:39', '2024-10-24 07:31:43'),
(29, 'Extended Seat Highlighted', NULL, 'library', 12, 'Show Extended Seat highlighted on Seat Assignment', '2024-10-03 11:02:35', '2024-10-24 07:32:50'),
(30, 'Add Operating Hours', NULL, 'library', 6, 'Allow us to add Operating Hours in Library master', '2024-10-03 11:09:41', '2024-10-24 07:33:47'),
(31, 'Add Library Seats', NULL, 'library', 6, 'Allow us to add Seats in Library master', '2024-10-03 11:09:50', '2024-10-24 07:35:06'),
(32, 'Add Extend Days', NULL, 'library', 6, 'Allow us to add Extend Days in Library master', '2024-10-03 11:09:56', '2024-10-24 07:35:39'),
(33, 'Add Plan', NULL, 'library', 6, 'Allow us to Add Plan in Library master', '2024-10-03 11:10:04', '2024-10-24 07:18:51'),
(36, 'Add Expense', NULL, 'library', 6, 'Allow us to add Expanse in Library master', '2024-10-03 11:10:32', '2024-10-24 07:36:41'),
(37, 'Download Payment Receipt', NULL, 'library', 12, 'Allow Library to Download Payment Transaction Receipt', '2024-10-03 11:24:54', '2024-10-24 07:24:00'),
(38, 'Full Day', NULL, 'library', 13, 'Allow us to add Full Day Plan in Library', '2024-10-06 00:21:59', '2024-10-24 07:19:50'),
(39, 'First Half', NULL, 'library', 13, 'Allow us to add First half Plan in Library', '2024-10-06 00:22:08', '2024-10-24 07:21:08'),
(40, 'Second Half', NULL, 'library', 13, 'Allow us to add Second half Plan in Library', '2024-10-06 00:22:14', '2024-10-24 07:21:32'),
(41, 'Hourly Slot 1', NULL, 'library', 13, 'Allow us to add plan Hourly Slot 1 to Library', '2024-10-06 00:22:23', '2024-10-24 07:22:11'),
(42, 'Hourly Slot 2', NULL, 'library', 13, 'Allow us to add plan Hourly Slot 2 to Library', '2024-10-06 00:22:31', '2024-10-24 07:22:25'),
(43, 'Hourly Slot 3', NULL, 'library', 13, 'Allow us to add plan Hourly Slot 3 to Library', '2024-10-06 00:22:38', '2024-10-24 07:23:09'),
(44, 'Hourly Slot 4', NULL, 'library', 13, 'Allow us to add plan Hourly Slot 4 to Library', '2024-10-06 00:22:50', '2024-10-24 07:23:00'),
(47, 'Add Master Plan Type', NULL, 'library', 6, 'Allow us to add Plan Type in library', '2024-10-23 13:13:04', '2024-10-23 13:13:04'),
(48, 'Add Master Plan Price', NULL, 'library', 6, 'Allow us to add Plan Price in Library', '2024-10-24 07:18:05', '2024-10-24 07:18:05'),
(49, 'Seat Assignment', NULL, 'library', 2, 'Show Menu in Libary', '2024-10-24 07:38:01', '2024-10-24 07:38:01'),
(50, 'Learner List', NULL, 'library', 2, 'Show Menu Learner List', '2024-10-24 07:38:24', '2024-10-24 07:38:24'),
(51, 'Manage Library', NULL, 'library', 2, 'Show Menu Manage Library', '2024-10-24 07:39:32', '2024-10-24 07:39:32'),
(52, 'Manage Report', NULL, 'library', 2, 'Show Menu Manage Report', '2024-10-24 07:39:58', '2024-10-24 07:40:08'),
(53, 'Manage Account', NULL, 'library', 2, 'Show Menu manage Account', '2024-10-24 07:40:32', '2024-10-24 07:40:32'),
(54, 'Monthly Report', NULL, 'library', 2, 'Show Menu Monthly Report', '2024-10-24 07:41:09', '2024-10-24 07:41:09'),
(55, 'Show Plan Info', NULL, 'library', 10, 'Show Plan Info', '2024-10-24 07:42:34', '2024-10-24 07:42:34'),
(56, 'Total Seats', NULL, 'library', 10, 'Show Total Seats opiton dashboard', '2024-10-24 07:43:02', '2024-10-24 07:43:02'),
(57, 'Booked Seats', NULL, 'web', 10, 'Show Booked Seats option dashboard', '2024-10-24 07:43:21', '2024-10-24 07:43:21'),
(58, 'Monthly Revenues', NULL, 'library', 10, 'Show Monthly Revenues option dashboard', '2024-10-24 07:46:23', '2024-10-24 07:46:23'),
(59, 'Total Bookings', NULL, 'library', 10, NULL, '2024-10-24 07:48:18', '2024-10-24 07:48:18'),
(60, 'Online Paid', NULL, 'library', 10, NULL, '2024-10-24 07:48:29', '2024-10-24 07:48:29'),
(61, 'Offline Paid', NULL, 'library', 10, NULL, '2024-10-24 07:49:03', '2024-10-24 07:49:03'),
(62, 'Expired in 5 Days', NULL, 'library', 10, NULL, '2024-10-24 07:49:14', '2024-10-24 07:49:14'),
(63, 'Expired Seats', NULL, 'library', 10, NULL, '2024-10-24 07:49:26', '2024-10-24 07:49:26'),
(64, 'Extended Seats', NULL, 'library', 10, NULL, '2024-10-24 07:49:38', '2024-10-24 07:49:38'),
(65, 'Swap Seats', NULL, 'library', 10, NULL, '2024-10-24 07:49:47', '2024-10-24 07:49:47'),
(66, 'Upgrade Seats', NULL, 'library', 10, NULL, '2024-10-24 07:49:54', '2024-10-24 07:49:54'),
(67, 'Reactive Seats', NULL, 'library', 10, NULL, '2024-10-24 07:50:11', '2024-10-24 07:50:11'),
(68, 'WhatsApp Sended', NULL, 'library', 10, NULL, '2024-10-24 07:50:25', '2024-10-24 07:50:25'),
(69, 'Email Sended', NULL, 'library', 10, NULL, '2024-10-24 07:50:38', '2024-10-24 07:50:38'),
(70, 'Plan Renews', NULL, 'library', 10, NULL, '2024-10-24 07:51:02', '2024-10-24 07:51:02'),
(71, 'Full Day Count', NULL, 'library', 10, NULL, '2024-10-24 07:51:20', '2024-10-24 07:51:20'),
(72, 'First Half Count', NULL, 'library', 10, NULL, '2024-10-24 07:51:32', '2024-10-24 07:51:32'),
(73, 'Second Half Count', NULL, 'library', 10, NULL, '2024-10-24 07:51:44', '2024-10-24 07:51:44'),
(74, 'Hourly Slot 1 Count', NULL, 'library', 10, NULL, '2024-10-24 07:51:58', '2024-10-24 07:51:58'),
(75, 'Hourly Slot 2 Count', NULL, 'library', 10, NULL, '2024-10-24 07:52:32', '2024-10-24 07:52:32'),
(76, 'Hourly Slot 3 Count', NULL, 'library', 10, NULL, '2024-10-24 07:53:02', '2024-10-24 07:53:02'),
(77, 'Total Booked Seats Count', NULL, 'library', 10, NULL, '2024-10-24 07:53:20', '2024-10-24 07:53:20'),
(78, 'Available Seats', NULL, 'library', 10, NULL, '2024-10-24 07:53:35', '2024-10-24 07:53:35'),
(79, 'Avaialble Seats List', NULL, 'library', 10, NULL, '2024-10-24 07:53:49', '2024-10-24 07:53:49'),
(80, 'Seat About to Expire List', NULL, 'library', 10, NULL, '2024-10-24 07:54:01', '2024-10-24 07:54:25'),
(81, 'Extend Seats list', NULL, 'library', 10, NULL, '2024-10-24 07:54:15', '2024-10-24 07:54:15'),
(82, 'Library Analytics', NULL, 'library', 10, 'Allow us to see detailed insight of Library dashboard graph show hide', '2024-10-24 07:55:00', '2024-10-24 07:55:00'),
(83, 'Change Plan', NULL, 'library', 3, NULL, NULL, NULL),
(84, 'Plan wise count', NULL, 'library', 10, NULL, NULL, NULL),
(85, 'Booked Seats', NULL, 'library', 10, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `permission_categories`
--

DROP TABLE IF EXISTS `permission_categories`;
CREATE TABLE IF NOT EXISTS `permission_categories` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permission_categories`
--

INSERT INTO `permission_categories` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'Master Permission', '2024-10-21 13:24:28', '2024-10-21 13:24:28'),
(2, 'Menu Permission', '2024-10-23 12:49:44', '2024-10-23 12:49:44'),
(3, 'Learner Operation Permission', '2024-10-23 12:49:49', '2024-10-23 12:49:49'),
(4, 'Filter Permission', '2024-10-23 12:49:54', '2024-10-23 12:49:54'),
(5, 'Search Permission', '2024-10-23 12:50:02', '2024-10-23 12:50:02'),
(6, 'Master Permission', '2024-10-23 12:50:09', '2024-10-23 12:50:09'),
(7, 'Import Permission', '2024-10-23 12:50:14', '2024-10-23 12:50:14'),
(8, 'Export Permission', '2024-10-23 12:50:19', '2024-10-23 12:50:19'),
(9, 'Report Permission', '2024-10-23 12:50:23', '2024-10-23 12:50:23'),
(10, 'Dashboard Permission', '2024-10-23 12:50:28', '2024-10-23 12:50:28'),
(11, 'Logs Permission', '2024-10-23 12:50:33', '2024-10-23 12:50:33'),
(12, 'Advanced Permission', '2024-10-23 12:50:38', '2024-10-23 12:50:38'),
(13, 'Plan Type Permission', '2024-10-23 12:50:46', '2024-10-23 12:50:46'),
(14, 'Other Permission', '2024-10-23 12:50:55', '2024-10-23 12:50:55');

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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `guard_name`, `created_at`, `updated_at`) VALUES
(1, 'superadmin', 'web', '2024-09-01 21:32:44', '2024-09-01 21:32:44'),
(2, 'admin', 'library', '2024-09-01 21:32:44', '2024-09-01 21:32:44'),
(3, 'learner', 'learner', NULL, NULL);

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
-- Table structure for table `subscriptions`
--

DROP TABLE IF EXISTS `subscriptions`;
CREATE TABLE IF NOT EXISTS `subscriptions` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `monthly_fees` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `yearly_fees` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subscriptions`
--

INSERT INTO `subscriptions` (`id`, `name`, `monthly_fees`, `yearly_fees`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Basic Plan', '150', '1500', '2024-09-10 11:07:45', '2024-12-12 22:13:26', NULL),
(2, 'Standard Plan', '200', '2200', '2024-09-10 11:07:54', '2024-12-12 22:28:07', NULL),
(3, 'Premium Plan', '300', '3500', '2024-09-10 11:08:05', '2024-09-10 11:08:05', NULL);

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
(2, 1, NULL, NULL),
(2, 2, NULL, NULL),
(2, 3, NULL, NULL),
(2, 5, NULL, NULL),
(2, 6, NULL, NULL),
(2, 7, NULL, NULL),
(2, 8, NULL, NULL),
(2, 9, NULL, NULL),
(2, 11, NULL, NULL),
(3, 2, NULL, NULL),
(3, 3, NULL, NULL),
(3, 5, NULL, NULL),
(3, 6, NULL, NULL),
(3, 7, NULL, NULL),
(3, 8, NULL, NULL),
(3, 9, NULL, NULL),
(3, 10, NULL, NULL),
(3, 11, NULL, NULL),
(3, 12, NULL, NULL),
(3, 13, NULL, NULL),
(3, 14, NULL, NULL),
(3, 15, NULL, NULL),
(3, 17, NULL, NULL),
(3, 19, NULL, NULL),
(3, 20, NULL, NULL),
(3, 21, NULL, NULL),
(3, 22, NULL, NULL),
(3, 23, NULL, NULL),
(3, 24, NULL, NULL),
(3, 28, NULL, NULL),
(3, 29, NULL, NULL),
(3, 30, NULL, NULL),
(3, 31, NULL, NULL),
(3, 32, NULL, NULL),
(3, 33, NULL, NULL),
(3, 36, NULL, NULL),
(1, 38, NULL, NULL),
(1, 1, NULL, NULL),
(1, 3, NULL, NULL),
(1, 6, NULL, NULL),
(1, 7, NULL, NULL),
(1, 8, NULL, NULL),
(1, 9, NULL, NULL),
(1, 10, NULL, NULL),
(1, 11, NULL, NULL),
(1, 12, NULL, NULL),
(1, 13, NULL, NULL),
(1, 14, NULL, NULL),
(1, 15, NULL, NULL),
(1, 17, NULL, NULL),
(1, 19, NULL, NULL),
(1, 20, NULL, NULL),
(1, 21, NULL, NULL),
(1, 22, NULL, NULL),
(1, 23, NULL, NULL),
(1, 24, NULL, NULL),
(1, 28, NULL, NULL),
(1, 29, NULL, NULL),
(1, 30, NULL, NULL),
(1, 31, NULL, NULL),
(1, 32, NULL, NULL),
(1, 33, NULL, NULL),
(1, 36, NULL, NULL),
(1, 37, NULL, NULL),
(3, 37, NULL, NULL),
(3, 41, NULL, NULL),
(3, 42, NULL, NULL),
(3, 43, NULL, NULL),
(3, 44, NULL, NULL),
(1, 39, NULL, NULL),
(1, 40, NULL, NULL),
(3, 39, NULL, NULL),
(3, 40, NULL, NULL),
(2, 13, NULL, NULL),
(2, 10, NULL, NULL),
(2, 12, NULL, NULL),
(2, 14, NULL, NULL),
(2, 15, NULL, NULL),
(2, 17, NULL, NULL),
(2, 19, NULL, NULL),
(2, 20, NULL, NULL),
(2, 21, NULL, NULL),
(2, 22, NULL, NULL),
(2, 23, NULL, NULL),
(2, 24, NULL, NULL),
(2, 28, NULL, NULL),
(2, 29, NULL, NULL),
(2, 30, NULL, NULL),
(2, 31, NULL, NULL),
(2, 32, NULL, NULL),
(2, 33, NULL, NULL),
(2, 47, NULL, NULL),
(3, 49, NULL, NULL),
(3, 50, NULL, NULL),
(3, 51, NULL, NULL),
(3, 52, NULL, NULL),
(3, 53, NULL, NULL),
(3, 54, NULL, NULL),
(3, 47, NULL, NULL),
(3, 48, NULL, NULL),
(3, 38, NULL, NULL),
(3, 55, NULL, NULL),
(3, 56, NULL, NULL),
(3, 58, NULL, NULL),
(3, 59, NULL, NULL),
(3, 60, NULL, NULL),
(3, 61, NULL, NULL),
(3, 62, NULL, NULL),
(3, 63, NULL, NULL),
(3, 64, NULL, NULL),
(3, 65, NULL, NULL),
(3, 66, NULL, NULL),
(3, 67, NULL, NULL),
(3, 68, NULL, NULL),
(3, 69, NULL, NULL),
(3, 70, NULL, NULL),
(3, 72, NULL, NULL),
(3, 73, NULL, NULL),
(3, 74, NULL, NULL),
(3, 75, NULL, NULL),
(3, 76, NULL, NULL),
(3, 77, NULL, NULL),
(3, 78, NULL, NULL),
(3, 79, NULL, NULL),
(3, 80, NULL, NULL),
(3, 81, NULL, NULL),
(3, 82, NULL, NULL),
(1, 2, NULL, NULL),
(1, 5, NULL, NULL),
(3, 16, NULL, NULL),
(2, 16, NULL, NULL),
(1, 16, NULL, NULL),
(3, 1, NULL, NULL),
(3, 84, NULL, NULL),
(3, 83, NULL, NULL),
(3, 71, NULL, NULL),
(3, 85, NULL, NULL);

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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `temp_orders`
--

DROP TABLE IF EXISTS `temp_orders`;
CREATE TABLE IF NOT EXISTS `temp_orders` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `razorpay_order_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `library_transaction_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(1, 'Super Admin', 'superadmin@admin.com', '$2y$12$fVO88A2SCydAdCKdBKAaBuSm6MrRzZuEETIgEAl/uH5jsW.pfxQM2', NULL, NULL, NULL, 'wEdgKFmBwp7hGX7QyghQJX4WtzJR1T6uFgMp8lqXh6H4djyhTxA0jSURBd9G', '2024-09-01 21:32:45', '2024-09-10 20:44:04', NULL);

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
  ADD CONSTRAINT `learners_library_id_foreign` FOREIGN KEY (`library_id`) REFERENCES `libraries` (`id`);

--
-- Constraints for table `learner_detail`
--
ALTER TABLE `learner_detail`
  ADD CONSTRAINT `learner_detail_library_id_foreign` FOREIGN KEY (`library_id`) REFERENCES `libraries` (`id`),
  ADD CONSTRAINT `learner_detail_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `plans` (`id`),
  ADD CONSTRAINT `learner_detail_plan_type_id_foreign` FOREIGN KEY (`plan_type_id`) REFERENCES `plan_types` (`id`),
  ADD CONSTRAINT `learner_detail_seat_id_foreign` FOREIGN KEY (`seat_id`) REFERENCES `seats` (`id`);

--
-- Constraints for table `learner_transactions`
--
ALTER TABLE `learner_transactions`
  ADD CONSTRAINT `learner_transactions_learner_id_foreign` FOREIGN KEY (`learner_id`) REFERENCES `learners` (`id`),
  ADD CONSTRAINT `learner_transactions_library_id_foreign` FOREIGN KEY (`library_id`) REFERENCES `libraries` (`id`);

--
-- Constraints for table `library_user`
--
ALTER TABLE `library_user`
  ADD CONSTRAINT `library_user_library_id_foreign` FOREIGN KEY (`library_id`) REFERENCES `libraries` (`id`),
  ADD CONSTRAINT `library_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

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
-- Constraints for table `permissions`
--
ALTER TABLE `permissions`
  ADD CONSTRAINT `permissions_permission_category_id_foreign` FOREIGN KEY (`permission_category_id`) REFERENCES `permission_categories` (`id`) ON DELETE CASCADE;

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
