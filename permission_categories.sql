-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Nov 14, 2025 at 02:13 AM
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
-- Table structure for table `permission_categories`
--

DROP TABLE IF EXISTS `permission_categories`;
CREATE TABLE IF NOT EXISTS `permission_categories` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permission_categories`
--

INSERT INTO `permission_categories` (`id`, `name`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Master Permission', '2024-10-21 13:24:28', '2024-10-21 13:24:28', NULL),
(2, 'Menu Permission', '2024-10-23 12:49:44', '2024-10-23 12:49:44', NULL),
(3, 'Learner Operation Permission', '2024-10-23 12:49:49', '2024-10-23 12:49:49', NULL),
(4, 'Filter Permission', '2024-10-23 12:49:54', '2024-10-23 12:49:54', NULL),
(5, 'Search Permission', '2024-10-23 12:50:02', '2024-10-23 12:50:02', NULL),
(6, 'Master Permission', '2024-10-23 12:50:09', '2024-10-23 12:50:09', NULL),
(7, 'Import Permission', '2024-10-23 12:50:14', '2024-10-23 12:50:14', NULL),
(8, 'Export Permission', '2024-10-23 12:50:19', '2024-10-23 12:50:19', NULL),
(9, 'Report Permission', '2024-10-23 12:50:23', '2024-10-23 12:50:23', NULL),
(10, 'Dashboard Permission', '2024-10-23 12:50:28', '2024-10-23 12:50:28', NULL),
(11, 'Logs Permission', '2024-10-23 12:50:33', '2024-10-23 12:50:33', NULL),
(12, 'Advanced Permission', '2024-10-23 12:50:38', '2024-10-23 12:50:38', NULL),
(13, 'Plan Type Permission', '2024-10-23 12:50:46', '2024-10-23 12:50:46', NULL),
(14, 'Other Permission', '2024-10-23 12:50:55', '2024-10-23 12:50:55', NULL);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
