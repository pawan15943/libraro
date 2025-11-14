-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 12, 2025 at 11:07 AM
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
-- Database: `libraro_final`
--

-- --------------------------------------------------------

--
-- Table structure for table `menus`
--

CREATE TABLE `menus` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icon` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `order` int(11) NOT NULL DEFAULT 0,
  `guard` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `has_permissions` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `role_id` bigint(20) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `menus`
--

INSERT INTO `menus` (`id`, `name`, `url`, `icon`, `parent_id`, `order`, `guard`, `status`, `has_permissions`, `created_at`, `updated_at`, `deleted_at`, `role_id`) VALUES
(1, 'Dashboard', 'library.home', 'fa fa-dashboard', NULL, 1, 'library', 1, NULL, '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(2, 'Manage Library', 'library', 'fa fa-building', NULL, 2, 'library', 1, 'Manage Library', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(3, 'Seat Assignment', 'seats', NULL, 2, 1, 'library', 1, 'Seat Booking', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(4, 'Search Learner', 'learner.search', NULL, 2, 2, 'library', 1, 'Search Learner', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(5, 'Learner List', 'learners', NULL, 2, 3, 'library', 1, 'Learner List', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(6, 'Library Register', 'seats.history', NULL, 2, 4, 'library', 1, 'Library Register', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(7, 'Learner History', 'learnerHistory', NULL, 2, 5, 'library', 1, 'Learners History', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(8, 'Future Bookings', 'future.bookings', NULL, 2, 6, 'library', 1, 'Future Bookings', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(9, 'Import Learners', 'library.upload.form', NULL, 2, 7, 'library', 1, 'Import Learners', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(10, 'Manage Attendance', 'attendance', 'fa-solid fa-clipboard-user', NULL, 3, 'library', 1, 'Manage Attendence', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(11, 'Mark Attendance', 'attendance', NULL, 10, 1, 'library', 1, 'Mark Attendance', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(12, 'Learner Attendance', 'get.learner.attendance', NULL, 10, 1, 'library', 1, 'Learner Attendance', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(13, 'Manage Report', 'payment.collection.report', 'fa fa-chart-simple', NULL, 4, 'library', 1, 'Manage Report', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(14, 'Payment Collection Report', 'payment.collection.report', NULL, 13, 1, 'library', 1, 'Payment Collection Report', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(15, 'Partial Payment Report', 'partial.payment.collection.report', NULL, 13, 2, 'library', 1, 'Partial Payment Report', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(16, 'Library Activity Report', 'activity.report', NULL, 13, 3, 'library', 1, 'Library Activity Report', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(17, 'Attendance Report', 'attendance.report', NULL, 13, 4, 'library', 1, 'Attendence Report', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(18, 'Monthly Revenue Report', 'report.monthly', NULL, 13, 5, 'library', 1, 'Monthly Revenue Report', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(19, 'Pending Payment Report', 'pending.payment.report', NULL, 13, 6, NULL, 1, 'Pending Payment Report', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(20, 'Learner Report', 'learner.report', NULL, 13, 7, NULL, 1, 'Learner Report', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(21, 'Upcoming Payment Report', 'upcoming.payment.report', NULL, 13, 8, NULL, 1, 'Upcoming Payment Report', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(22, 'Expired Learners Report', 'expired.learner.report', NULL, 13, 9, NULL, 1, 'Expired Learners Report', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(23, 'Library Master Console', 'library-users.index', 'fa-solid fa-sliders', NULL, 5, 'library', 1, 'Library Master Console', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(24, 'Users', 'library-users.index', NULL, 23, 1, 'library', 1, 'Add User', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(25, 'Branches', 'branch.list', NULL, 23, 2, 'library', 1, 'Add Branch', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(26, 'Floors', 'floor.index', NULL, 23, 3, 'library', 1, 'Add Floor', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(27, 'Plans', 'plan.index', NULL, 23, 4, 'library', 1, 'Add Plan', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(28, 'Plan Types', 'plantype.index', NULL, 23, 5, 'library', 1, 'Add Plan Type', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(29, 'Plan Price', 'planPrice.index', NULL, 23, 6, 'library', 1, 'Add Plan Price', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(30, 'Expenses', 'expense.index', NULL, 23, 7, 'library', 1, 'Add Expense', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(31, 'Exams', 'exam.index', NULL, 23, 8, 'library', 1, 'Add Exam', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(32, 'Show / Hide Options', 'toggle.feature', NULL, 23, 9, 'library', 1, 'Show / Hide Options', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(33, 'Import Library Settings', 'library.master', NULL, 23, 10, 'library', 1, 'Import Library Settings', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(34, 'Account Settings', 'library.myplan', 'fa fa-user-tie', NULL, 6, 'library', 1, 'Account Settings', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(35, 'My Plan', 'library.myplan', NULL, 34, 1, 'library', 1, 'My Plans', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(36, 'My Profile', 'profile', NULL, 34, 2, 'library', 1, 'My Profile', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(37, 'My Transactions', 'library.transaction', NULL, 34, 3, 'library', 1, 'My Transactions', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(38, 'Library Enquiry', 'library.enquiry', 'fa-solid fa-book', NULL, 7, 'library', 1, 'Library Enquiry', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(39, 'Library Corner', 'library.learner.complaints', 'fa-solid fa-book', NULL, 9, 'library', 1, 'Library Corner', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(40, 'Suggestions', 'library', NULL, 39, 1, 'library', 1, 'Suggestions', '2025-09-28 06:10:00', '2025-09-28 06:10:00', '0000-00-00 00:00:00', NULL),
(41, 'Library Genral Settings', 'library.settings', NULL, 39, 2, 'library', 1, 'Library Genral Settings', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(42, 'Video Training', 'library.video-training', NULL, 39, 3, 'library', 1, 'Video Training', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(43, 'Complaints', 'library.learner.complaints', NULL, 39, 4, 'library', 1, 'Complaints', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(44, 'Learner Feedback', 'library.learner.feedback', NULL, 39, 5, 'library', 1, 'Learner Feedback', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(45, 'Book Category', 'book.category.index', NULL, 39, 6, 'library', 1, 'Book Category', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(46, 'Dashboard', 'home', 'fa fa-cog', NULL, 1, 'web', 1, NULL, '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(47, 'Library List', 'library', 'fa fa-angle-right', 58, 1, 'web', 1, NULL, '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(48, 'Subscription', 'subscription.master', 'fa fa-keys', 50, 2, 'web', 1, NULL, '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(49, 'Manage Permissions', 'permissions', 'fa fa-keys', 50, 3, 'web', 1, NULL, '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(50, 'Manage Subscriptions', 'expired.learner.report', 'fa fa-cog', NULL, 4, 'web', 1, NULL, '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(51, 'Notification', 'create.notification', 'fa fa-keys', 31, 4, 'web', 1, NULL, '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(52, 'Add Feature', 'feature.create', 'fa fa-plus', 63, 2, 'web', 1, NULL, '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(53, 'Manage Website', 'page', 'fa fa-globe', 0, 3, 'web', 1, NULL, '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(54, 'Page', 'page', 'fa fa-plus', 51, 1, 'web', 1, NULL, '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(55, 'Blog', 'blogs', 'fa fa-keys', 51, 2, 'web', 1, NULL, '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(56, 'Demo', 'demo', 'fa fa-keys', 51, 3, 'web', 1, NULL, '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(57, 'Inquiry', 'inquiry', 'fa fa-keys', 51, 4, 'web', 1, NULL, '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(58, 'Manage Library', 'library', 'fa fa-cog', NULL, 2, 'web', 1, NULL, '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(59, 'Video Upload', 'videos.index', 'fa fa-plus', 63, 3, 'web', 1, NULL, '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(60, 'Dahbaord', 'learner.home', 'fa fa-dashboard me-2', NULL, 0, 'learner', 1, NULL, '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(61, 'My Profile', 'learner.profile', 'fa fa-user', 49, 0, 'learner', 1, NULL, '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(62, 'My Id Card', 'my-library-id', 'fa fa-id', 49, 1, 'learner', 1, NULL, '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(63, 'My Attendance', 'my-attendance', 'fa fa-calendar', 49, 2, 'learner', 1, NULL, '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(64, 'My Transactions', 'my-transactions', NULL, 49, 3, 'learner', 1, NULL, '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(65, 'Complaints', 'complaints', NULL, 45, 0, 'learner', 1, NULL, '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(66, 'Suggestions', 'learner.suggestions', NULL, 45, 0, 'learner', 1, NULL, '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(67, 'Student Corner', 'home', 'fa-solid fa-user-graduate', NULL, 2, 'learner', 1, NULL, '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(68, 'Blog', 'learner.blog', 'fa fa-blog', 45, 0, 'learner', 1, NULL, '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(69, 'Books Library', 'books-library', 'fa fa-books', 45, 0, 'learner', 1, NULL, '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(70, 'Feedback', 'learner.feadback', NULL, 45, 0, 'learner', 1, NULL, '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(71, 'Account', 'home', 'fa-solid fa-user-tie', NULL, 0, 'learner', 1, NULL, '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(72, 'Support', 'support', 'fa-solid fa-headset', NULL, 10, 'learner', 1, NULL, '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL),
(73, 'My Request', 'learner.request', 'fa fa-user-plus', 49, 0, 'learner', 1, 'Learne-Account', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guard_name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `permission_category_id` bigint(20) UNSIGNED DEFAULT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `name`, `slug`, `guard_name`, `permission_category_id`, `description`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Welcome Banner', 'welcome-banner', 'library', 1, 'This permission allow library owner to show/hide Welcome Banner', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(2, 'Show Plan Info', 'show-plan-info', 'library', 1, 'This permission allow library owner to show/hide Show Plan Info', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(3, 'Year and Month Filter', 'year-and-month-filter', 'library', 1, 'This permission allow library owner to show/hide Year and Month Filter', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(4, 'Total Seats', 'total-seats', 'library', 1, 'This permission allow library owner to show/hide Total Seats', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(5, 'Booked Seats', 'booked-seats', 'library', 1, 'This permission allow library owner to show/hide Booked Seats', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(6, 'Available Seats', 'available-seats', 'library', 1, 'This permission allow library owner to show/hide Available Seats', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(7, 'Expired Seats', 'expired-seats', 'library', 1, 'This permission allow library owner to show/hide Expired Seats', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(8, 'General Seat Counts', 'general-seat-counts', 'library', 1, 'This permission allow library owner to show/hide General Seat Counts', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(9, 'Todays Financial Snapshot', 'todays-financial-snapshot', 'library', 1, 'This permission allow library owner to show/hide Todays Financial Snapshot', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(10, 'Monthly Financial Overview', 'monthly-financial-overview', 'library', 1, 'This permission allow library owner to show/hide Monthly Financial Overview', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(11, 'QR Seat Booking', 'qr-seat-booking', 'library', 1, 'This permission allow library owner to show/hide QR Seat Booking', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(12, 'Online Seat Booking', 'online-seat-booking', 'library', 1, 'This permission allow library owner to show/hide Online Seat Booking', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(13, 'Recent Activity', 'recent-activity', 'library', 1, 'This permission allow library owner to show/hide Recent Activity', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(14, 'Till Today Bookings', 'till-today-bookings', 'library', 1, 'This permission allow library owner to show/hide Till Today Bookings', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(15, 'This Month Bookings', 'this-month-bookings', 'library', 1, 'This permission allow library owner to show/hide This Month Bookings', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(16, 'Expired in 5 Days Count', 'expired-in-5-days-count', 'library', 1, 'This permission allow library owner to show/hide Expired in 5 Days Count', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(17, 'Extended Seats Count', 'extended-seats-count', 'library', 1, 'This permission allow library owner to show/hide Extended Seats Count', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(18, 'Online Paid Count', 'online-paid-count', 'library', 1, 'This permission allow library owner to show/hide Online Paid Count', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(19, 'Offline Paid Count', 'offline-paid-count', 'library', 1, 'This permission allow library owner to show/hide Offline Paid Count', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(20, 'Swap Seats Count', 'swap-seats-count', 'library', 1, 'This permission allow library owner to show/hide Swap Seats Count', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(21, 'Upgrade Seats Count', 'upgrade-seats-count', 'library', 1, 'This permission allow library owner to show/hide Upgrade Seats Count', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(22, 'Reactive Seats Count', 'reactive-seats-count', 'library', 1, 'This permission allow library owner to show/hide Reactive Seats Count', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(23, 'Pay Later Count', 'pay-later-count', 'library', 1, 'This permission allow library owner to show/hide Pay Later Count', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(24, 'Renew Seat Count', 'renew-seat-count', 'library', 1, 'This permission allow library owner to show/hide Renew Seat Count', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(25, 'Delate Seat Count', 'delate-seat-count', 'library', 1, 'This permission allow library owner to show/hide Delate Seat Count', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(26, 'Close Seat Count', 'close-seat-count', 'library', 1, 'This permission allow library owner to show/hide Close Seat Count', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(27, 'Delete Seat Count', 'delete-seat-count', 'library', 1, 'This permission allow library owner to show/hide Delete Seat Count', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(28, 'Change Plan Count', 'change-plan-count', 'library', 1, 'This permission allow library owner to show/hide Change Plan Count', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(29, 'Plan wise count', 'plan-wise-count', 'library', 1, 'This permission allow library owner to show/hide Plan wise count', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(30, 'Library Analytics', 'library-analytics', 'library', 1, 'This permission allow library owner to show/hide Library Analytics', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(31, 'Avaialble Seats List', 'avaialble-seats-list', 'library', 1, 'This permission allow library owner to show/hide Avaialble Seats List', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(32, 'Seat About to Expire List', 'seat-about-to-expire-list', 'library', 1, 'This permission allow library owner to show/hide Seat About to Expire List', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(33, 'Extend Seats list', 'extend-seats-list', 'library', 1, 'This permission allow library owner to show/hide Extend Seats list', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(34, 'Dashboard', 'dashboard', 'library', 2, 'This permission allow library owner to show/hide Dashboard', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(35, 'Manage Library', 'manage-library', 'library', 2, 'This permission allow library owner to show/hide Manage Library', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(36, 'Seat Booking', 'seat-booking', 'library', 2, 'This permission allow library owner to show/hide Seat Booking', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(38, 'Learner List', 'learner-list', 'library', 2, 'This permission allow library owner to show/hide Learner List', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(39, 'Library Register', 'Library-history', 'library', 2, 'This permission allow library owner to show/hide Seat History', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(40, 'Learners History', 'learners-history', 'library', 2, 'This permission allow library owner to show/hide Learners History', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(41, 'Future Bookings', 'future-bookings', 'library', 2, 'This permission allow library owner to show/hide Future Bookings', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(42, 'Import Learners', 'import-learners', 'library', 2, 'This permission allow library owner to show/hide Import Learners', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(43, 'Manage Attendence', 'manage-attendence', 'library', 2, 'This permission allow library owner to show/hide Manage Attendence', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(44, 'Mark Attendance', 'attendance', 'library', 2, 'This permission allow library owner to show/hide Mark Attendance', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(45, 'Learner Attendance', 'learner-attendance', 'library', 2, 'This permission allow library owner to show/hide Learner Attendance', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(49, 'Library Activity Report', 'library-activity-report', 'library', 6, 'This permission allow library owner to show/hide Library Activity Report', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(50, 'Attendence Report', 'attendence-report', 'library', 6, 'This permission allow library owner to show/hide Attendence Report', '2025-09-28 06:10:00', '2025-09-28 06:10:00', '2025-11-11 03:14:27'),
(51, 'Monthly Revenue Report', 'monthly-revenue-report', 'library', 6, 'This permission allow library owner to show/hide Monthly Revenue Report', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(54, 'Upcoming Payment Report', 'upcoming-payment-report', 'library', 6, 'This permission allow library owner to show/hide Upcoming Payment Report', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(56, 'Library Master Console', 'library-master-console', 'library', 2, 'This permission allow library owner to show/hide Library Master Console', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(57, 'Add User', 'add-user', 'library', 2, 'This permission allow library owner to show/hide Add User', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(58, 'Add Branch', 'add-branch', 'library', 2, 'This permission allow library owner to show/hide Add Branch', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(59, 'Add Floor', 'add-floor', 'library', 2, 'This permission allow library owner to show/hide Add Floor', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(60, 'Add Plan', 'add-plan', 'library', 2, 'This permission allow library owner to show/hide Add Plan', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(61, 'Add Plan Type', 'add-plan-type', 'library', 2, 'This permission allow library owner to show/hide Add Plan Type', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(62, 'Add Plan Price', 'add-plan-price', 'library', 2, 'This permission allow library owner to show/hide Add Plan Price', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(63, 'Add Expense', 'add-expense', 'library', 2, 'This permission allow library owner to show/hide Add Expense', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(64, 'Add Exam', 'add-exam', 'library', 2, 'This permission allow library owner to show/hide Add Exam', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(65, 'Show / Hide Options', 'show-/-hide-options', 'library', 2, 'This permission allow library owner to show/hide Show / Hide Options', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(66, 'Import Library Settings', 'import-library-settings', 'library', 2, 'This permission allow library owner to show/hide Import Library Settings', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(67, 'Account Settings', 'account-settings', 'library', 2, 'This permission allow library owner to show/hide Account Settings', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(68, 'My Plans', 'my-plans', 'library', 2, 'This permission allow library owner to show/hide My Plans', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(69, 'My Profile', 'my-profile', 'library', 2, 'This permission allow library owner to show/hide My Profile', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(70, 'My Transactions', 'my-transactions', 'library', 2, 'This permission allow library owner to show/hide My Transactions', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(71, 'Library Enquiry', 'library enquiry', 'library', 2, 'This permission allow library owner to show/hide Library Enquiry', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(72, 'Library Corner', 'library corner', 'library', 2, 'This permission allow library owner to show/hide Library Corner', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(74, 'Video Training', 'video training', 'library', 2, 'This permission allow library owner to show/hide Video Training', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(76, 'Learner Feedback', 'learner feedback', 'library', 2, 'This permission allow library owner to show/hide Learner Feedback', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(77, 'Book Category', 'book category', 'library', 2, 'This permission allow library owner to show/hide My Profile', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(78, 'Library configuration', 'library-configuration', 'library', 2, 'This permission allow library owner to show/hide Library configuration', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(79, 'User management', 'user-management', 'library', 2, 'This permission allow library owner to show/hide User management', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(80, 'Seat Assignment', 'seat-assignment', 'library', 2, 'This permission allow library owner to show/hide Seat Assignment', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(81, 'Monthly Report', 'monthly-report', 'library', 2, 'This permission allow library owner to show/hide Monthly Report', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(82, 'Video Tutorial', 'video-tutorial', 'library', 2, 'This permission allow library owner to show/hide Video Tutorial', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(83, 'Student Corner', 'student-corner', 'library', 2, 'This permission allow library owner to show/hide Student Corner', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(84, 'Suggestions', 'suggestions', 'library', 2, 'This permission allow library owner to show/hide Suggestions', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(85, 'Feedback', 'feedback', 'library', 2, 'This permission allow library owner to show/hide Feedback', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(86, 'Complaints', 'complaints', 'library', 2, 'This permission allow library owner to show/hide Complaints', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(87, 'Book Seat', 'book-seat', 'library', 3, 'This permission allow library owner to show/hide Book Seat', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(88, 'General Seat Booking', 'general-seat-booking', 'library', 3, 'This permission allow library owner to show/hide General Seat Booking', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(89, 'View Seat', 'view-seat', 'library', 3, 'This permission allow library owner to show/hide View Seat', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(90, 'Edit Seat', 'edit-seat', 'library', 3, 'This permission allow library owner to show/hide Edit Seat', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(91, 'Renew Seat', 'renew-seat', 'library', 3, 'This permission allow library owner to show/hide Renew Seat', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(92, 'Swap Seat', 'swap-seat', 'library', 3, 'This permission allow library owner to show/hide Swap Seat', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(93, 'Upgrade Seat Plan', 'upgrade-seat-plan', 'library', 3, 'This permission allow library owner to show/hide Upgrade Seat Plan', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(94, 'Reactive Seat', 'reactive-seat', 'library', 3, 'This permission allow library owner to show/hide Reactive Seat', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(95, 'Extend Seat', 'extend-seat', 'library', 3, 'This permission allow library owner to show/hide Extend Seat', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(96, 'Receipt Generation', 'receipt-generation', 'library', 3, 'This permission allow library owner to show/hide Receipt Generation', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(97, 'Delete Seat', 'delete-seat', 'library', 3, 'This permission allow library owner to show/hide Delete Seat', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(98, 'Close Seat', 'close-seat', 'library', 3, 'This permission allow library owner to show/hide Close Seat', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(99, 'Change Plan', 'change-plan', 'library', 3, 'This permission allow library owner to show/hide Change Plan', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(100, 'Genrate ID Card', 'genrate-id-card', 'library', 3, 'This permission allow library owner to show/hide Genrate ID Card', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(101, 'Pending Payment', 'pending-payment', 'library', 3, 'This permission allow library owner to show/hide Pending Payment', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(102, 'Search Learner', 'search-learner', 'library', 3, 'This permission allow library owner to show/hide Search Learner', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(103, 'Download Receipt', 'download-payment-receipt', 'library', 3, 'This permission allow library owner to show/hide Download Payment Receipt', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(104, 'Add Operating Hours', 'add-operating-hours', 'library', 4, 'This permission allow library owner to show/hide Add Operating Hours', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(105, 'Add Library Seats', 'add-library-seats', 'library', 4, 'This permission allow library owner to show/hide Add Library Seats', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(106, 'Add Extend Days', 'add-extend-days', 'library', 4, 'This permission allow library owner to show/hide Add Extend Days', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(107, 'Add Token Money', 'add-token-money', 'library', 4, 'This permission allow library owner to show/hide Add Token Money', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(108, 'Add Misllaneous Payment', 'add-misllaneous-payment', 'library', 4, 'This permission allow library owner to show/hide Add Misllaneous Payment', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(109, 'Add Locker Amount ', 'add-locker-amount-', 'library', 4, 'This permission allow library owner to show/hide Add Locker Amount ', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(110, 'Add Daily Expense', 'add-daily-expense', 'library', 4, 'This permission allow library owner to show/hide Add Daily Expense', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(111, 'All Day', 'all-day', 'library', 5, 'This permission allow library owner to show/hide All Day', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(112, 'Full Night', 'full-night', 'library', 5, 'This permission allow library owner to show/hide Full Night', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(113, 'Full Day', 'full-day', 'library', 5, 'This permission allow library owner to show/hide Full Day', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(114, 'First Half', 'first-half', 'library', 5, 'This permission allow library owner to show/hide First Half', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(115, 'Second Half', 'second-half', 'library', 5, 'This permission allow library owner to show/hide Second Half', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(116, 'Hourly Slot 1', 'hourly-slot-1', 'library', 5, 'This permission allow library owner to show/hide Hourly Slot 1', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(117, 'Hourly Slot 2', 'hourly-slot-2', 'library', 5, 'This permission allow library owner to show/hide Hourly Slot 2', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(118, 'Hourly Slot 3', 'hourly-slot-3', 'library', 5, 'This permission allow library owner to show/hide Hourly Slot 3', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(119, 'Hourly Slot 4', 'hourly-slot-4', 'library', 5, 'This permission allow library owner to show/hide Hourly Slot 4', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(120, 'Custom Plan', 'custom-plan', 'library', 5, 'This permission allow library owner to show/hide Custom Plan Permission', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(121, 'Manage Report', 'manage-report', 'library', 2, 'This permission allow library owner to show/hide Manage Report', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(122, 'Payment Collection Report', 'payment-collection-report', 'library', 6, 'This permission allow library owner to show/hide Payment Collection Report', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(123, 'Partial Payment Report', 'partial-payment-report', 'library', 6, 'This permission allow library owner to show/hide Partial Payment Report', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(124, 'Attendance Report', 'attendance-report', 'library', 6, 'This permission allow library owner to show/hide Attendance Report', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(125, 'Pending Payment Report', 'pending-payment-report', 'library', 6, 'This permission allow library owner to show/hide Pending Payment Report', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(126, 'Expired Learners Report', 'expired-learners-report', 'library', 6, 'This permission allow library owner to show/hide Expired Learners Report', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(127, 'Activity Report', 'activity-report', 'library', 6, 'This permission allow library owner to show/hide Activity Report', '2025-09-28 06:10:00', '2025-09-28 06:10:00', '2025-11-11 03:14:53'),
(128, 'Learner Report', 'learner-report', 'library', 6, 'This permission allow library owner to show/hide Learner Report', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(129, 'Import Student', 'import-student', 'library', 7, 'This permission allow library owner to show/hide Import Student', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(130, 'Learner Login', 'learner-login', 'library', 7, 'This permission allow library owner to show/hide Learner Login', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(131, 'Library filter', 'library-filter', 'library', 7, 'This permission allow library owner to show/hide Library filter', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(132, 'Extended Seat Highlighted', 'extended-seat-highlighted', 'library', 7, 'This permission allow library owner to show/hide Extended Seat Highlighted', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(133, 'Expired Seat highlight', 'expired-seat-highlight', 'library', 7, 'This permission allow library owner to show/hide Expired Seat highlight', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(134, 'Pay Later Seat Highlight', 'pay-later-seat-highlight', 'library', 7, 'This permission allow library owner to show/hide Pay Later Seat Highlight', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(135, 'Pending Payment Highlight', 'pending-payment-highlight', 'library', 7, 'This permission allow library owner to show/hide Pending Payment Highlight', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(136, 'Overdue Payment Highlight', 'overdue-payment-highlight', 'library', 7, 'This permission allow library owner to show/hide Overdue Payment Highlight', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(137, 'Email Notification', 'email-notification', 'library', 8, 'This permission allow library owner to show/hide Email Notification', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(138, 'WhatsApp Notification', 'whatsapp-notification', 'library', 8, 'This permission allow library owner to show/hide WhatsApp Notification', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(139, 'Add Branch Master', 'add-branch', 'library', 9, 'This permission allow library owner to show/hide Add Branch Master', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(140, 'Add Floor Master', 'add-floor', 'library', 9, 'This permission allow library owner to show/hide Add Floor Master', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(141, 'Add Plan Master', 'add-plan', 'library', 9, 'This permission allow library owner to show/hide Add Plan Master', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(142, 'Add Plan Type Master', 'add-plan-type', 'library', 9, 'This permission allow library owner to show/hide Add Plan Type Master', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(143, 'Add Plan Price Master', 'add-plan-price', 'library', 9, 'This permission allow library owner to show/hide Add Plan Price Master', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(144, 'Add Expense Master', 'add-expense', 'library', 9, 'This permission allow library owner to show/hide Add Expense Master', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(145, 'Add Exam Master', 'add-exam', 'library', 9, 'This permission allow library owner to show/hide Add Exam Master', '2025-09-28 06:10:00', '2025-09-28 06:10:00', NULL),
(146, 'Edit Branch', 'edit-branch', 'library', 4, 'This permission allow library owner to show/hide Edit Branch', NULL, NULL, NULL),
(147, 'Add User Master', NULL, 'library', 9, 'This permission allow library owner to show/hide Add User Master', '2025-11-10 16:26:21', '2025-11-10 16:26:21', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `permission_categories`
--

CREATE TABLE `permission_categories` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `permission_categories`
--

INSERT INTO `permission_categories` (`id`, `name`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Dashboard Permission', '2024-10-21 13:24:28', '2024-10-21 13:24:28', NULL),
(2, 'Menu Permission', '2024-10-23 12:49:44', '2024-10-23 12:49:44', NULL),
(3, 'Operation Permission', '2024-10-23 12:49:49', '2024-10-23 12:49:49', NULL),
(4, 'Branch Operation Pemission', '2024-10-23 12:50:23', '2024-10-23 12:50:23', NULL),
(5, 'Seat Plan Permission', '2024-10-23 12:50:28', '2024-10-23 12:50:28', NULL),
(6, 'Report Permission', '2024-10-23 12:50:38', '2024-10-23 12:50:38', NULL),
(7, 'Advanced Permission', '2024-10-23 12:50:38', '2024-10-23 12:50:38', NULL),
(8, 'Notification Permission', '2024-10-23 12:50:38', '2024-10-23 12:50:38', NULL),
(9, 'Master Permissions', '2024-10-23 12:50:38', '2024-10-23 12:50:38', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `menus`
--
ALTER TABLE `menus`
  ADD PRIMARY KEY (`id`),
  ADD KEY `menus_parent_id_foreign` (`parent_id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`),
  ADD KEY `permissions_permission_category_id_foreign` (`permission_category_id`);

--
-- Indexes for table `permission_categories`
--
ALTER TABLE `permission_categories`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `menus`
--
ALTER TABLE `menus`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=148;

--
-- AUTO_INCREMENT for table `permission_categories`
--
ALTER TABLE `permission_categories`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
