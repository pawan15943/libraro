-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Nov 14, 2025 at 02:08 AM
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
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`),
  KEY `permissions_permission_category_id_foreign` (`permission_category_id`)
) ENGINE=InnoDB AUTO_INCREMENT=103 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `slug`, `guard_name`, `permission_category_id`, `description`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Library Master Console', 'library-master-console', 'library', 1, 'Allows access to library master console features and functionalities in the system.', NULL, NULL, NULL),
(2, 'Seat History', 'seat-history', 'library', 2, 'Allows access to seat history features and functionalities in the system.', NULL, NULL, NULL),
(3, 'Dashboard', 'dashboard', 'library', 2, 'Allows access to dashboard features and functionalities in the system.', NULL, NULL, NULL),
(4, 'Learners History', 'learners-history', 'library', 2, 'Allows access to learners history features and functionalities in the system.', NULL, NULL, NULL),
(5, 'Seat Assignment', 'seat-assignment', 'library', 2, 'Allows access to seat assignment features and functionalities in the system.', NULL, NULL, NULL),
(6, 'Learner List', 'learner-list', 'library', 2, 'Allows access to learner list features and functionalities in the system.', NULL, NULL, NULL),
(7, 'Manage Library', 'manage-library', 'library', 2, 'Allows access to manage library features and functionalities in the system.', NULL, NULL, NULL),
(8, 'Manage Report', 'manage-report', 'library', 2, 'Allows access to manage report features and functionalities in the system.', NULL, NULL, NULL),
(9, 'Manage Account', 'manage-account', 'library', 2, 'Allows access to manage account features and functionalities in the system.', NULL, NULL, NULL),
(10, 'Monthly Report', 'monthly-report', 'library', 2, 'Allows access to monthly report features and functionalities in the system.', NULL, NULL, NULL),
(11, 'Manage Attendence', 'manage-attendence', 'library', 2, 'Allows access to manage attendence features and functionalities in the system.', NULL, NULL, NULL),
(12, 'User management', 'user-management', 'library', 2, 'Allows access to user management features and functionalities in the system.', NULL, NULL, NULL),
(13, 'Video Tutorial', 'video-tutorial', 'library', 2, 'Allows access to video tutorial features and functionalities in the system.', NULL, NULL, NULL),
(14, 'View Seat', 'view-seat', 'library', 3, 'Allows access to view seat features and functionalities in the system.', NULL, NULL, NULL),
(15, 'Seat Booking', 'seat-booking', 'library', 3, 'Allows access to seat booking features and functionalities in the system.', NULL, NULL, NULL),
(16, 'Edit Seat', 'edit-seat', 'library', 3, 'Allows access to edit seat features and functionalities in the system.', NULL, NULL, NULL),
(17, 'Renew Seat', 'renew-seat', 'library', 3, 'Allows access to renew seat features and functionalities in the system.', NULL, NULL, NULL),
(18, 'Swap Seat', 'swap-seat', 'library', 3, 'Allows access to swap seat features and functionalities in the system.', NULL, NULL, NULL),
(19, 'Upgrade Seat Plan', 'upgrade-seat-plan', 'library', 3, 'Allows access to upgrade seat plan features and functionalities in the system.', NULL, NULL, NULL),
(20, 'Reactive Seat', 'reactive-seat', 'library', 3, 'Allows access to reactive seat features and functionalities in the system.', NULL, NULL, NULL),
(21, 'Extend Seat', 'extend-seat', 'library', 3, 'Allows access to extend seat features and functionalities in the system.', NULL, NULL, NULL),
(22, 'Receipt Generation', 'receipt-generation', 'library', 3, 'Allows access to receipt generation features and functionalities in the system.', NULL, NULL, NULL),
(23, 'Delete Seat', 'delete-seat', 'library', 3, 'Allows access to delete seat features and functionalities in the system.', NULL, NULL, NULL),
(24, 'Close Seat', 'close-seat', 'library', 3, 'Allows access to close seat features and functionalities in the system.', NULL, NULL, NULL),
(25, 'Change Plan', 'change-plan', 'library', 3, 'Allows access to change plan features and functionalities in the system.', NULL, NULL, NULL),
(26, 'Add Operating Hours', 'add-operating-hours', 'library', 6, 'Allows access to add operating hours features and functionalities in the system.', NULL, NULL, NULL),
(27, 'Add Library Seats', 'add-library-seats', 'library', 6, 'Allows access to add library seats features and functionalities in the system.', NULL, NULL, NULL),
(28, 'Add Extend Days', 'add-extend-days', 'library', 6, 'Allows access to add extend days features and functionalities in the system.', NULL, NULL, NULL),
(29, 'Add Plan', 'add-plan', 'library', 6, 'Allows access to add plan features and functionalities in the system.', NULL, NULL, NULL),
(30, 'Add Expense', 'add-expense', 'library', 6, 'Allows access to add expense features and functionalities in the system.', NULL, NULL, NULL),
(31, 'Add Master Plan Type', 'add-master-plan-type', 'library', 6, 'Allows access to add master plan type features and functionalities in the system.', NULL, NULL, NULL),
(32, 'Add Master Plan Price', 'add-master-plan-price', 'library', 6, 'Allows access to add master plan price features and functionalities in the system.', NULL, NULL, NULL),
(33, 'All Day', 'all-day', 'library', 6, 'Allows access to all day features and functionalities in the system.', NULL, NULL, NULL),
(34, 'Full Night', 'full-night', 'library', 6, 'Allows access to full night features and functionalities in the system.', NULL, NULL, NULL),
(35, 'Add Branch', 'add-branch', 'library', 6, 'Allows access to add branch features and functionalities in the system.', NULL, NULL, NULL),
(36, 'Add User', 'add-user', 'library', 6, 'Allows access to add user features and functionalities in the system.', NULL, NULL, NULL),
(37, 'Library Analytics (Report Menu)', 'library-analytics-dashboard-menu', 'library', 9, 'Allows access to library analytics (report menu) features and functionalities in the system.', NULL, NULL, NULL),
(38, 'Payment Collection Report', 'payment-collection-report', 'library', 9, 'Allows access to payment collection report features and functionalities in the system.', NULL, NULL, NULL),
(39, 'Partial Payment Report', 'partial-payment-report', 'library', 9, 'Allows access to partial payment report features and functionalities in the system.', NULL, NULL, NULL),
(40, 'Attendance Report', 'attendance-report', 'library', 9, 'Allows access to attendance report features and functionalities in the system.', NULL, NULL, NULL),
(41, 'Pending Payment Report', 'pending-payment-report', 'library', 9, 'Allows access to pending payment report features and functionalities in the system.', NULL, NULL, NULL),
(42, 'Expired Learners Report', 'expired-learners-report', 'library', 9, 'Allows access to expired learners report features and functionalities in the system.', NULL, NULL, NULL),
(43, 'Activity Report', 'activity-report', 'library', 9, 'Allows access to activity report features and functionalities in the system.', NULL, NULL, NULL),
(44, 'Learner Report', 'learner-report', 'library', 9, 'Allows access to learner report features and functionalities in the system.', NULL, NULL, NULL),
(45, 'Show Plan Info', 'show-plan-info', 'library', 10, 'Allows access to show plan info features and functionalities in the system.', NULL, NULL, NULL),
(46, 'Total Seats', 'total-seats', 'library', 10, 'Allows access to total seats features and functionalities in the system.', NULL, NULL, NULL),
(47, 'Booked Seats', 'booked-seats', 'web', 10, 'Allows access to booked seats features and functionalities in the system.', NULL, NULL, NULL),
(48, 'Monthly Revenues', 'monthly-revenues', 'library', 10, 'Allows access to monthly revenues features and functionalities in the system.', NULL, NULL, NULL),
(49, 'Total Bookings', 'total-bookings', 'library', 10, 'Allows access to total bookings features and functionalities in the system.', NULL, NULL, NULL),
(50, 'Online Paid', 'online-paid', 'library', 10, 'Allows access to online paid features and functionalities in the system.', NULL, NULL, NULL),
(51, 'Offline Paid', 'offline-paid', 'library', 10, 'Allows access to offline paid features and functionalities in the system.', NULL, NULL, NULL),
(52, 'Expired in 5 Days', 'expired-in-5-days', 'library', 10, 'Allows access to expired in 5 days features and functionalities in the system.', NULL, NULL, NULL),
(53, 'Expired Seats', 'expired-seats', 'library', 10, 'Allows access to expired seats features and functionalities in the system.', NULL, NULL, NULL),
(54, 'Extended Seats', 'extended-seats', 'library', 10, 'Allows access to extended seats features and functionalities in the system.', NULL, NULL, NULL),
(55, 'Swap Seats', 'swap-seats', 'library', 10, 'Allows access to swap seats features and functionalities in the system.', NULL, NULL, NULL),
(56, 'Upgrade Seats', 'upgrade-seats', 'library', 10, 'Allows access to upgrade seats features and functionalities in the system.', NULL, NULL, NULL),
(57, 'Reactive Seats', 'reactive-seats', 'library', 10, 'Allows access to reactive seats features and functionalities in the system.', NULL, NULL, NULL),
(58, 'WhatsApp Sended', 'whatsapp-sended', 'library', 10, 'Allows access to whatsapp sended features and functionalities in the system.', NULL, NULL, NULL),
(59, 'Email Sended', 'email-sended', 'library', 10, 'Allows access to email sended features and functionalities in the system.', NULL, NULL, NULL),
(60, 'Plan Renews', 'plan-renews', 'library', 10, 'Allows access to plan renews features and functionalities in the system.', NULL, NULL, NULL),
(61, 'Full Day Count', 'full-day-count', 'library', 10, 'Allows access to full day count features and functionalities in the system.', NULL, NULL, NULL),
(62, 'First Half Count', 'first-half-count', 'library', 10, 'Allows access to first half count features and functionalities in the system.', NULL, NULL, NULL),
(63, 'Second Half Count', 'second-half-count', 'library', 10, 'Allows access to second half count features and functionalities in the system.', NULL, NULL, NULL),
(64, 'Hourly Slot 1 Count', 'hourly-slot-1-count', 'library', 10, 'Allows access to hourly slot 1 count features and functionalities in the system.', NULL, NULL, NULL),
(65, 'Hourly Slot 2 Count', 'hourly-slot-2-count', 'library', 10, 'Allows access to hourly slot 2 count features and functionalities in the system.', NULL, NULL, NULL),
(66, 'Hourly Slot 3 Count', 'hourly-slot-3-count', 'library', 10, 'Allows access to hourly slot 3 count features and functionalities in the system.', NULL, NULL, NULL),
(67, 'Total Booked Seats Count', 'total-booked-seats-count', 'library', 10, 'Allows access to total booked seats count features and functionalities in the system.', NULL, NULL, NULL),
(68, 'Available Seats', 'available-seats', 'library', 10, 'Allows access to available seats features and functionalities in the system.', NULL, NULL, NULL),
(69, 'Avaialble Seats List', 'avaialble-seats-list', 'library', 10, 'Allows access to avaialble seats list features and functionalities in the system.', NULL, NULL, NULL),
(70, 'Seat About to Expire List', 'seat-about-to-expire-list', 'library', 10, 'Allows access to seat about to expire list features and functionalities in the system.', NULL, NULL, NULL),
(71, 'Extend Seats list', 'extend-seats-list', 'library', 10, 'Allows access to extend seats list features and functionalities in the system.', NULL, NULL, NULL),
(72, 'Library Analytics', 'library-analytics', 'library', 10, 'Allows access to library analytics features and functionalities in the system.', NULL, NULL, NULL),
(73, 'Plan wise count', 'plan-wise-count', 'library', 10, 'Allows access to plan wise count features and functionalities in the system.', NULL, NULL, NULL),
(74, 'Daily Transaction', 'daily-transaction', 'library', 10, 'Allows access to daily transaction features and functionalities in the system.', NULL, NULL, NULL),
(75, 'Recent Activity', 'recent-activity', 'library', 10, 'Allows access to recent activity features and functionalities in the system.', NULL, NULL, NULL),
(76, 'Pay Later Count', 'pay-later-count', 'library', 10, 'Allows access to pay later count features and functionalities in the system.', NULL, NULL, NULL),
(77, 'Renew Seat Count', 'renew-seat-count', 'library', 10, 'Allows access to renew seat count features and functionalities in the system.', NULL, NULL, NULL),
(78, 'Delate Seat Count', 'delate-seat-count', 'library', 10, 'Allows access to delate seat count features and functionalities in the system.', NULL, NULL, NULL),
(79, 'WhatsApp Reminder Counts', 'whatsapp-reminder-counts', 'library', 10, 'Allows access to whatsapp reminder counts features and functionalities in the system.', NULL, NULL, NULL),
(80, 'Import Library Seats', 'import-library-seats', 'library', 12, 'Allows access to import library seats features and functionalities in the system.', NULL, NULL, NULL),
(81, 'Learner Login', 'learner-login', 'library', 12, 'Allows access to learner login features and functionalities in the system.', NULL, NULL, NULL),
(82, 'Filter', 'filter', 'library', 12, 'Allows access to filter features and functionalities in the system.', NULL, NULL, NULL),
(83, 'Extended Seat Highlighted', 'extended-seat-highlighted', 'library', 12, 'Allows access to extended seat highlighted features and functionalities in the system.', NULL, NULL, NULL),
(84, 'Download Payment Receipt', 'download-payment-receipt', 'library', 12, 'Allows access to download payment receipt features and functionalities in the system.', NULL, NULL, NULL),
(85, 'Full Day', 'full-day', 'library', 13, 'Allows access to full day features and functionalities in the system.', NULL, NULL, NULL),
(86, 'First Half', 'first-half', 'library', 13, 'Allows access to first half features and functionalities in the system.', NULL, NULL, NULL),
(87, 'Second Half', 'second-half', 'library', 13, 'Allows access to second half features and functionalities in the system.', NULL, NULL, NULL),
(88, 'Hourly Slot 1', 'hourly-slot-1', 'library', 13, 'Allows access to hourly slot 1 features and functionalities in the system.', NULL, NULL, NULL),
(89, 'Hourly Slot 2', 'hourly-slot-2', 'library', 13, 'Allows access to hourly slot 2 features and functionalities in the system.', NULL, NULL, NULL),
(90, 'Hourly Slot 3', 'hourly-slot-3', 'library', 13, 'Allows access to hourly slot 3 features and functionalities in the system.', NULL, NULL, NULL),
(91, 'Hourly Slot 4', 'hourly-slot-4', 'library', 13, 'Allows access to hourly slot 4 features and functionalities in the system.', NULL, NULL, NULL),
(92, 'Email Notification', 'email-notification', 'library', 14, 'Allows access to email notification features and functionalities in the system.', NULL, NULL, NULL),
(93, 'WhatsApp Notification', 'whatsapp-notification', 'library', 14, 'Allows access to whatsapp notification features and functionalities in the system.', NULL, NULL, NULL),
(94, 'Suggestions', 'suggestions', 'library', 14, 'Allows access to suggestions features and functionalities in the system.', NULL, NULL, NULL),
(95, 'Feedback', 'feedback', 'library', 14, 'Allows access to feedback features and functionalities in the system.', NULL, NULL, NULL),
(96, 'Complaints', 'complaints', 'library', 14, 'Allows access to complaints features and functionalities in the system.', NULL, NULL, NULL),
(97, 'General Seat Booked', 'general-seat-booked', 'library', 14, 'Allows access to general seat booked features and functionalities in the system.', NULL, NULL, NULL),
(98, 'Welcome Banner', 'welcome-banner', 'library', 15, 'Allows access to welcome banner features and functionalities in the system.', NULL, NULL, NULL),
(99, 'Active Plan Info', 'active-plan-info', 'library', 15, 'Allows access to active plan info features and functionalities in the system.', NULL, NULL, NULL),
(100, 'Learne-Account', 'learne-account', 'learner', NULL, 'Allows access to learne-account features and functionalities in the system.', NULL, NULL, NULL),
(101, 'Learner Search Bar', NULL, 'library', 5, 'learner search bar and data show', '2025-11-05 16:18:55', '2025-11-05 16:18:55', NULL),
(102, 'Add User Master', 'add-user-master', 'library', NULL, NULL, NULL, NULL, NULL);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `permissions`
--
ALTER TABLE `permissions`
  ADD CONSTRAINT `permissions_permission_category_id_foreign` FOREIGN KEY (`permission_category_id`) REFERENCES `permission_categories` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
