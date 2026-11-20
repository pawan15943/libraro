-- =====================================================================
-- Libraro: Ordering plan permission + Show / Hide Options, and the Learner Requests inbox (19 Nov 2026) — for the live DB, phpMyAdmin.
-- Same result as the migration 2026_11_19_000001_orders_summary_permission_and_toggles (docs/ORDERING.md).
-- Plain statements (no variables), MariaDB 10.3+ / MySQL 5.7+. Rows are found by name, never by id.
-- Safe to run again. Only NEW rows: nothing existing is changed.
-- Needs db/libraro_live_db_changes_2026-11-18.sql (sections 1–5) to be run first.
-- TAKE A BACKUP FIRST. Run this BEFORE deploying the code.
-- =====================================================================
SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- 1. Permission "Orders Summary" (Pro plan): the Orders card on the library dashboard
-- ---------------------------------------------------------------------
INSERT INTO `permission_categories` (`name`, `created_at`, `updated_at`) SELECT 'Ordering & Services', NOW(), NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `permission_categories` WHERE `name` = 'Ordering & Services');

INSERT INTO `permissions` (`name`, `slug`, `guard_name`, `permission_category_id`, `description`, `created_at`, `updated_at`) SELECT 'Orders Summary', 'orders-summary', 'library', (SELECT `id` FROM `permission_categories` WHERE `name` = 'Ordering & Services' ORDER BY `id` LIMIT 1), 'Dashboard card: orders and income from Ordering & Services, today and this month', NOW(), NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `guard_name` = 'library' AND `name` = 'Orders Summary');

INSERT INTO `subscription_permission` (`subscription_id`, `permission_id`, `created_at`) SELECT 3, p.`id`, NOW() FROM `permissions` p WHERE p.`guard_name` = 'library' AND p.`name` = 'Orders Summary' AND NOT EXISTS (SELECT 1 FROM `subscription_permission` sp WHERE sp.`subscription_id` = 3 AND sp.`permission_id` = p.`id`);

-- ---------------------------------------------------------------------
-- 2. Settings → Show / Hide Options, new section "Orders" (3 switches).
--    Ids always go after 35 (35 = privacy / contact masking, used by code), one statement per row.
-- ---------------------------------------------------------------------
INSERT INTO `toggle_features` (`id`, `name`, `category`, `description`, `created_at`) SELECT n.`next_id`, 'Hide Orders Menu', 'Orders', 'Hide the whole Orders menu (Order Board, Menu & Items, Order Settings, Order Reports) and the dashboard summary. Learners can still order while ordering is on.', NOW() FROM (SELECT GREATEST(IFNULL(MAX(`id`), 0), 35) + 1 AS `next_id` FROM `toggle_features`) n WHERE NOT EXISTS (SELECT 1 FROM `toggle_features` x WHERE x.`name` = 'Hide Orders Menu');
INSERT INTO `toggle_features` (`id`, `name`, `category`, `description`, `created_at`) SELECT n.`next_id`, 'Hide Order Reports Menu', 'Orders', 'Hide Orders → Order Reports from the menu', NOW() FROM (SELECT GREATEST(IFNULL(MAX(`id`), 0), 35) + 1 AS `next_id` FROM `toggle_features`) n WHERE NOT EXISTS (SELECT 1 FROM `toggle_features` x WHERE x.`name` = 'Hide Order Reports Menu');
INSERT INTO `toggle_features` (`id`, `name`, `category`, `description`, `created_at`) SELECT n.`next_id`, 'Hide Orders Summary on Dashboard', 'Orders', 'Hide the Orders card (orders and income, today and this month) on the dashboard', NOW() FROM (SELECT GREATEST(IFNULL(MAX(`id`), 0), 35) + 1 AS `next_id` FROM `toggle_features`) n WHERE NOT EXISTS (SELECT 1 FROM `toggle_features` x WHERE x.`name` = 'Hide Orders Summary on Dashboard');

-- ---------------------------------------------------------------------
-- 3. Mark the migration as run (so `php artisan migrate` skips it later)
-- ---------------------------------------------------------------------
INSERT INTO `migrations` (`migration`, `batch`) SELECT '2026_11_19_000001_orders_summary_permission_and_toggles', (SELECT IFNULL(MAX(`batch`), 0) + 1 FROM `migrations` m) FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_11_19_000001_orders_summary_permission_and_toggles');

-- Check: SELECT `id`, `name` FROM `toggle_features` WHERE `category` = 'Orders';   → three rows, ids above 35

-- ---------------------------------------------------------------------
-- 4. Learners → Requests (inbox for learner requests) — migration 2026_11_19_000002_learner_requests_inbox
--    New nullable columns only; existing requests keep working (request_status 0 = waiting, 1 = done, 2 = not possible).
-- ---------------------------------------------------------------------
ALTER TABLE `learner_request` ADD COLUMN IF NOT EXISTS `reply` TEXT NULL AFTER `request_status`;
ALTER TABLE `learner_request` ADD COLUMN IF NOT EXISTS `handled_at` TIMESTAMP NULL DEFAULT NULL AFTER `reply`;
ALTER TABLE `learner_request` ADD COLUMN IF NOT EXISTS `handled_by_type` VARCHAR(20) NULL AFTER `handled_at`;
ALTER TABLE `learner_request` ADD COLUMN IF NOT EXISTS `handled_by_id` BIGINT UNSIGNED NULL AFTER `handled_by_type`;

-- permission "Learner Requests": same category and same plans as "Learner List"
INSERT INTO `permissions` (`name`, `slug`, `guard_name`, `permission_category_id`, `description`, `created_at`, `updated_at`) SELECT 'Learner Requests', 'learner-requests', 'library', (SELECT `permission_category_id` FROM `permissions` WHERE `guard_name` = 'library' AND `name` = 'Learner List' ORDER BY `id` LIMIT 1), 'See and answer learner requests: renew, change plan, upgrade, swap seat, close', NOW(), NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `guard_name` = 'library' AND `name` = 'Learner Requests');
INSERT INTO `subscription_permission` (`subscription_id`, `permission_id`, `created_at`) SELECT sp.`subscription_id`, p2.`id`, NOW() FROM `subscription_permission` sp JOIN `permissions` p1 ON p1.`id` = sp.`permission_id` AND p1.`guard_name` = 'library' AND p1.`name` = 'Learner List' JOIN `permissions` p2 ON p2.`guard_name` = 'library' AND p2.`name` = 'Learner Requests' WHERE NOT EXISTS (SELECT 1 FROM `subscription_permission` x WHERE x.`subscription_id` = sp.`subscription_id` AND x.`permission_id` = p2.`id`);

-- sidebar: Learners → Requests (same group as the "Learners" list link)
INSERT INTO `menus` (`name`, `url`, `icon`, `parent_id`, `order`, `guard`, `status`, `has_permissions`, `created_at`, `updated_at`) SELECT 'Requests', 'learner.requests.index', NULL, m.`parent_id`, 7, 'library', 1, 'Learner Requests', NOW(), NOW() FROM `menus` m WHERE m.`guard` = 'library' AND m.`url` = 'learners' AND m.`deleted_at` IS NULL AND m.`parent_id` IS NOT NULL AND NOT EXISTS (SELECT 1 FROM `menus` x WHERE x.`guard` = 'library' AND x.`url` = 'learner.requests.index' AND x.`deleted_at` IS NULL) LIMIT 1;

INSERT INTO `migrations` (`migration`, `batch`) SELECT '2026_11_19_000002_learner_requests_inbox', (SELECT IFNULL(MAX(`batch`), 0) + 1 FROM `migrations` m) FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_11_19_000002_learner_requests_inbox');

-- ---------------------------------------------------------------------
-- UNDO — uncomment and run:
-- ---------------------------------------------------------------------
-- DELETE FROM `toggle_features` WHERE `category` = 'Orders' AND `name` IN ('Hide Orders Menu', 'Hide Order Reports Menu', 'Hide Orders Summary on Dashboard');
-- DELETE FROM `subscription_permission` WHERE `permission_id` IN (SELECT `id` FROM `permissions` WHERE `guard_name` = 'library' AND `name` = 'Orders Summary');
-- DELETE FROM `library_user_permissions` WHERE `permission_id` IN (SELECT `id` FROM `permissions` WHERE `guard_name` = 'library' AND `name` = 'Orders Summary');
-- DELETE FROM `permissions` WHERE `guard_name` = 'library' AND `name` = 'Orders Summary';
-- DELETE FROM `migrations` WHERE `migration` = '2026_11_19_000001_orders_summary_permission_and_toggles';
-- (section 4) DELETE FROM `menus` WHERE `guard` = 'library' AND `url` = 'learner.requests.index';
-- (section 4) DELETE FROM `subscription_permission` WHERE `permission_id` IN (SELECT `id` FROM `permissions` WHERE `guard_name` = 'library' AND `name` = 'Learner Requests');
-- (section 4) DELETE FROM `library_user_permissions` WHERE `permission_id` IN (SELECT `id` FROM `permissions` WHERE `guard_name` = 'library' AND `name` = 'Learner Requests');
-- (section 4) DELETE FROM `permissions` WHERE `guard_name` = 'library' AND `name` = 'Learner Requests';
-- (section 4) DELETE FROM `migrations` WHERE `migration` = '2026_11_19_000002_learner_requests_inbox';
-- (the four learner_request columns can stay: they are nullable and unused without the code)
