-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Nov 14, 2025 at 02:07 AM
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
) ENGINE=InnoDB AUTO_INCREMENT=88 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `menus`
--

INSERT INTO `menus` (`id`, `name`, `url`, `icon`, `parent_id`, `order`, `guard`, `status`, `has_permissions`, `created_at`, `updated_at`, `deleted_at`, `role_id`) VALUES
(1, 'Dashboard', 'home', 'fa fa-cog', NULL, 1, 'web', 1, NULL, '2024-09-18 19:10:33', '2024-09-18 19:10:33', NULL, NULL),
(2, 'Manage Library', 'library', 'fa fa-building', NULL, 2, 'library', 1, 'Manage Library', '2024-09-18 19:10:33', '2024-09-18 19:10:33', NULL, NULL),
(4, 'Library List', 'library', 'fa fa-angle-right', 63, 1, 'web', 1, NULL, '2024-09-18 19:10:33', '2024-09-18 19:10:33', NULL, NULL),
(5, 'Subscription', 'subscription.master', 'fa fa-keys', 31, 2, 'web', 1, NULL, '2024-09-18 19:10:33', '2024-09-18 19:10:33', NULL, NULL),
(7, 'Dashboard', 'library.home', 'fa fa-dashboard', NULL, 1, 'library', 1, NULL, NULL, NULL, NULL, NULL),
(9, 'Book a  Seat', 'seats', NULL, 2, 1, 'library', 1, NULL, NULL, NULL, NULL, NULL),
(10, 'Learner List', 'learners', NULL, 2, 1, 'library', 1, 'Learner List\n', NULL, NULL, NULL, NULL),
(11, 'Library Register', 'seats.history', NULL, 2, 3, 'library', 1, NULL, NULL, NULL, NULL, NULL),
(12, 'Learner History', 'learnerHistory', NULL, 2, 4, 'library', 1, 'Learner’s History Menu', NULL, NULL, NULL, NULL),
(13, 'Manage Account', 'library.myplan', 'fa fa-user-tie', NULL, 6, 'library', 1, NULL, NULL, NULL, NULL, NULL),
(14, 'My Plan', 'library.myplan', NULL, 13, 1, 'library', 1, NULL, NULL, NULL, NULL, NULL),
(15, 'My Profile', 'profile', NULL, 13, 2, 'library', 1, NULL, NULL, NULL, NULL, NULL),
(16, 'My Transaction', 'library.transaction', NULL, 13, 3, 'library', 1, NULL, NULL, NULL, NULL, NULL),
(17, 'Library Configration', 'library.master', NULL, 13, 4, 'library', 1, NULL, NULL, NULL, NULL, NULL),
(18, 'Manage Permissions', 'permissions', 'fa fa-keys', 31, 3, 'web', 1, NULL, '2025-02-19 10:53:45', NULL, NULL, NULL),
(19, 'Manage Report', 'report.monthly', 'fa fa-chart-simple', NULL, 4, 'library', 1, 'Manage Report Menu', NULL, NULL, NULL, NULL),
(20, 'Monthly Report', 'report.monthly', NULL, 19, 1, 'library', 1, NULL, NULL, NULL, NULL, NULL),
(21, 'Import Learners', 'library.upload.form', NULL, 2, 0, 'library', 1, NULL, NULL, NULL, '2024-10-01 04:44:48', NULL),
(23, 'Library Settings', 'library.settings', 'fa fa-cog fa-spin', 57, 2, 'library', 1, NULL, NULL, NULL, NULL, NULL),
(24, 'Feedback', 'library.feedback', 'fa-solid fa-comment', NULL, 9, 'library', 1, 'Feedback', NULL, NULL, NULL, NULL),
(25, 'Suggestions', 'library', 'fa-solid fa-comment-dots', NULL, 8, 'library', 1, NULL, NULL, NULL, '2024-11-01 11:33:20', NULL),
(26, 'Video Training', 'library.video-training', 'fa fa-video', 57, 1, 'library', 1, NULL, NULL, NULL, NULL, NULL),
(27, 'Pending Payment Report', 'pending.payment.report', NULL, 19, 1, NULL, 1, NULL, NULL, NULL, NULL, NULL),
(28, 'Learner report', 'learner.report', NULL, 19, 2, NULL, 1, NULL, NULL, NULL, NULL, NULL),
(29, 'Upcoming Payment Report', 'upcoming.payment.report', NULL, 19, 3, NULL, 1, NULL, NULL, NULL, NULL, NULL),
(30, 'Expired Learners Report', 'expired.learner.report', NULL, 19, 4, NULL, 1, NULL, NULL, NULL, NULL, NULL),
(31, 'Manage Subscriptions', 'expired.learner.report', 'fa fa-cog', NULL, 4, 'web', 1, NULL, '2025-02-03 10:53:49', NULL, NULL, NULL),
(34, 'Notification', 'create.notification', 'fa fa-keys', 31, 4, 'web', 1, NULL, '2025-02-10 10:53:52', NULL, NULL, NULL),
(36, 'Add Feature', 'feature.create', 'fa fa-plus', 63, 2, 'web', 1, NULL, NULL, NULL, NULL, NULL),
(37, 'Dahbaord', 'learner.home', 'fa fa-dashboard me-2', NULL, 0, 'learner', 1, NULL, NULL, NULL, NULL, NULL),
(38, 'My Request', 'learner.request', 'fa fa-user-plus', 49, 0, NULL, 1, 'Learne-Account', NULL, NULL, NULL, NULL),
(39, 'My Profile', 'learner.profile', 'fa fa-user', 49, 0, 'learner', 1, NULL, NULL, NULL, NULL, NULL),
(40, 'My Id Card', 'my-library-id', 'fa fa-id', 49, 1, 'learner', 1, NULL, NULL, NULL, NULL, NULL),
(41, 'My Attendance', 'my-attendance', 'fa fa-calendar', 49, 2, 'learner', 1, NULL, NULL, NULL, NULL, NULL),
(42, 'My Transactions', 'my-transactions', NULL, 49, 3, 'learner', 1, NULL, NULL, NULL, NULL, NULL),
(43, 'Complaints', 'complaints', NULL, 45, 0, 'learner', 1, NULL, NULL, NULL, NULL, NULL),
(44, 'Suggestions', 'learner.suggestions', NULL, 45, 0, 'learner', 1, NULL, NULL, NULL, NULL, NULL),
(45, 'Student Corner', 'home', 'fa-solid fa-user-graduate', NULL, 2, 'learner', 1, NULL, NULL, NULL, NULL, NULL),
(46, 'Blog', 'learner.blog', 'fa fa-blog', 45, 0, 'learner', 1, NULL, NULL, NULL, NULL, NULL),
(47, 'Books Library', 'books-library', 'fa fa-books', 45, 0, 'learner', 1, NULL, NULL, NULL, NULL, NULL),
(48, 'Feedback', 'learner.feadback', NULL, 45, 0, 'learner', 1, NULL, NULL, NULL, NULL, NULL),
(49, 'Account', 'home', 'fa-solid fa-user-tie', NULL, 0, 'learner', 1, NULL, NULL, NULL, NULL, NULL),
(50, 'Support', 'support', 'fa-solid fa-headset', NULL, 10, 'learner', 1, NULL, NULL, NULL, NULL, NULL),
(51, 'Manage Website', 'page', 'fa fa-globe', 0, 3, 'web', 1, NULL, NULL, NULL, NULL, NULL),
(52, 'Page', 'page', 'fa fa-plus', 51, 1, 'web', 1, NULL, NULL, NULL, NULL, NULL),
(55, 'Attendance', 'attendance', NULL, 79, 1, 'library', 1, NULL, NULL, NULL, NULL, NULL),
(56, 'Blog', 'blogs', 'fa fa-keys', 51, 2, 'web', 1, NULL, NULL, NULL, NULL, NULL),
(57, 'Library Corner', 'library.learner.complaints', 'fa-solid fa-book', NULL, 10, 'library', 1, 'Library Corner', NULL, NULL, NULL, NULL),
(58, 'Complaints', 'library.learner.complaints', NULL, 57, 1, 'library', 1, NULL, NULL, NULL, NULL, NULL),
(59, 'Suggestion', 'library.learner.suggestions', NULL, 57, 2, 'library', 1, NULL, NULL, NULL, NULL, NULL),
(60, 'Learner Feedback', 'library.learner.feedback', NULL, 57, 3, 'library', 1, NULL, NULL, NULL, NULL, NULL),
(61, 'Demo', 'demo', 'fa fa-keys', 51, 3, 'web', 1, NULL, NULL, NULL, NULL, NULL),
(62, 'Inquiry', 'inquiry', 'fa fa-keys', 51, 4, 'web', 1, NULL, NULL, NULL, NULL, NULL),
(63, 'Manage Library', 'library', 'fa fa-cog', NULL, 2, 'web', 1, NULL, NULL, NULL, NULL, NULL),
(65, 'Learner Attendance', 'get.learner.attendance', NULL, 79, 2, 'library', 1, NULL, NULL, NULL, NULL, NULL),
(66, 'Library Enquiry', 'library.enquiry', NULL, 57, 4, NULL, 1, NULL, NULL, NULL, NULL, NULL),
(67, 'Library User', 'library-users.index', NULL, 80, 0, 'library', 1, NULL, NULL, NULL, NULL, NULL),
(68, 'Branch', 'branch.list', NULL, 78, 1, 'library', 1, NULL, NULL, NULL, NULL, NULL),
(69, 'Find a Learner', 'learner.search', NULL, 2, 2, 'library', 1, 'Learner Search Bar', NULL, NULL, NULL, NULL),
(70, 'Payment Collection Report', 'payment.collection.report', NULL, 19, 0, 'library', 1, NULL, NULL, NULL, NULL, NULL),
(71, 'Partial Payment', 'partial.payment.collection.report', NULL, 19, 0, 'library', 1, NULL, NULL, NULL, NULL, NULL),
(72, 'Activity Report', 'activity.report', NULL, 19, 0, 'library', 1, NULL, NULL, NULL, NULL, NULL),
(73, 'Attendance Report', 'attendance.report', NULL, 19, 0, 'library', 1, NULL, NULL, NULL, NULL, NULL),
(74, 'Plan Type ', 'plantype.index', NULL, 78, 3, 'library', 1, NULL, NULL, NULL, NULL, NULL),
(75, 'Plan', 'plan.index', NULL, 78, 2, 'library', 1, NULL, NULL, NULL, NULL, NULL),
(76, 'Expense', 'expense.index', NULL, 78, 5, 'library', 1, NULL, NULL, NULL, NULL, NULL),
(77, 'Plan Price', 'planPrice.index', NULL, 78, 4, 'library', 1, NULL, NULL, NULL, NULL, NULL),
(78, 'Library Master Console', 'branch.list', 'fa-solid fa-sliders', NULL, 5, 'library', 1, NULL, NULL, NULL, NULL, NULL),
(79, 'Manage Attendance', 'attendance', 'fa-solid fa-clipboard-user', NULL, 3, 'library', 1, 'Manage Attendance Menu', NULL, NULL, NULL, NULL),
(80, 'User Management', 'library-users.index', 'fa-solid fa-user-tie', NULL, 10, 'library', 1, 'User Management', NULL, NULL, NULL, NULL),
(81, 'Exams', 'exam.index', 'fa fa-plus', 78, 6, 'library', 1, NULL, NULL, NULL, NULL, NULL),
(82, 'Video Upload', 'videos.index', 'fa fa-plus', 63, 3, 'web', 1, NULL, NULL, NULL, NULL, NULL),
(83, 'Toggle', 'toggle.feature', NULL, 78, 7, 'library', 1, NULL, NULL, NULL, NULL, NULL),
(84, 'Book Category', 'book.category.index', NULL, 78, 8, 'library', 1, NULL, NULL, NULL, NULL, NULL),
(85, 'Library Master CSV', 'library.master', NULL, 78, 9, 'library', 1, NULL, NULL, NULL, NULL, NULL),
(86, 'Floor', 'floor.index', NULL, 78, 3, 'library', 1, NULL, NULL, NULL, NULL, NULL),
(87, 'Future Bookings', 'future.bookings', NULL, 2, 5, 'library', 1, NULL, NULL, NULL, NULL, NULL);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
