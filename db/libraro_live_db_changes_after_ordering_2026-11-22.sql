-- =====================================================================
-- Libraro: every DB change AFTER the Ordering & Services (canteen) module — for the live DB, phpMyAdmin.
-- Built 22 Nov 2026. Covers the only two migrations added since then:
--   A. 2026_11_19_000001_orders_summary_permission_and_toggles  (Orders Summary permission + Show / Hide "Orders" switches)
--   B. 2026_11_19_000002_learner_requests_inbox                 (Learners → Requests inbox)
-- The later work (Show / Hide Options redesign, seat-map floor numbers, booking form, sidebar logo,
-- QR booking page) needs NO database change.
--
-- Same content as db/libraro_live_db_changes_2026-11-19.sql — if that file was already run, skip this one
-- (running it again is harmless: every statement checks first, nothing is added twice).
--
-- Before you run it:
--   1. TAKE A BACKUP of the live DB.
--   2. The canteen module SQL must already be on the server: db/libraro_live_db_changes_2026-11-18.sql (sections 1–5).
--   3. Run this file BEFORE deploying the new code.
-- Works on MySQL 5.7+ / 8 and MariaDB 10.3+. Plain statements, no stored procedures. Rows are found by name, never by id.
-- =====================================================================
SET NAMES utf8mb4;

-- =====================================================================
-- A1. Permission "Orders Summary" (Pro plan): the Orders card on the library dashboard
-- =====================================================================
INSERT INTO `permission_categories` (`name`, `created_at`, `updated_at`)
SELECT 'Ordering & Services', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `permission_categories` WHERE `name` = 'Ordering & Services');

INSERT INTO `permissions` (`name`, `slug`, `guard_name`, `permission_category_id`, `description`, `created_at`, `updated_at`)
SELECT 'Orders Summary', 'orders-summary', 'library',
       (SELECT `id` FROM `permission_categories` WHERE `name` = 'Ordering & Services' ORDER BY `id` LIMIT 1),
       'Dashboard card: orders and income from Ordering & Services, today and this month', NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `guard_name` = 'library' AND `name` = 'Orders Summary');

INSERT INTO `subscription_permission` (`subscription_id`, `permission_id`, `created_at`)
SELECT 3, p.`id`, NOW() FROM `permissions` p
WHERE p.`guard_name` = 'library' AND p.`name` = 'Orders Summary'
  AND NOT EXISTS (SELECT 1 FROM `subscription_permission` sp WHERE sp.`subscription_id` = 3 AND sp.`permission_id` = p.`id`);

-- =====================================================================
-- A2. Settings → Show / Hide Options, section "Orders" (3 switches).
--     Ids always go after 35 (35 = privacy / contact masking, used by code). One statement per row.
-- =====================================================================
INSERT INTO `toggle_features` (`id`, `name`, `category`, `description`, `created_at`)
SELECT n.`next_id`, 'Hide Orders Menu', 'Orders', 'Hide the whole Orders menu (Order Board, Menu & Items, Order Settings, Order Reports) and the dashboard summary. Learners can still order while ordering is on.', NOW()
FROM (SELECT GREATEST(IFNULL(MAX(`id`), 0), 35) + 1 AS `next_id` FROM `toggle_features`) n
WHERE NOT EXISTS (SELECT 1 FROM `toggle_features` x WHERE x.`name` = 'Hide Orders Menu');

INSERT INTO `toggle_features` (`id`, `name`, `category`, `description`, `created_at`)
SELECT n.`next_id`, 'Hide Order Reports Menu', 'Orders', 'Hide Orders → Order Reports from the menu', NOW()
FROM (SELECT GREATEST(IFNULL(MAX(`id`), 0), 35) + 1 AS `next_id` FROM `toggle_features`) n
WHERE NOT EXISTS (SELECT 1 FROM `toggle_features` x WHERE x.`name` = 'Hide Order Reports Menu');

INSERT INTO `toggle_features` (`id`, `name`, `category`, `description`, `created_at`)
SELECT n.`next_id`, 'Hide Orders Summary on Dashboard', 'Orders', 'Hide the Orders card (orders and income, today and this month) on the dashboard', NOW()
FROM (SELECT GREATEST(IFNULL(MAX(`id`), 0), 35) + 1 AS `next_id` FROM `toggle_features`) n
WHERE NOT EXISTS (SELECT 1 FROM `toggle_features` x WHERE x.`name` = 'Hide Orders Summary on Dashboard');

-- A3. mark migration A as run (so `php artisan migrate` skips it later)
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_19_000001_orders_summary_permission_and_toggles', (SELECT IFNULL(MAX(`batch`), 0) + 1 FROM `migrations` m) FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_11_19_000001_orders_summary_permission_and_toggles');

-- =====================================================================
-- B1. Learners → Requests: 4 new nullable columns on learner_request
--     (request_status stays as is: 0 = waiting, 1 = done, 2 = not possible).
--     Each column is added only if missing — this form works on MySQL and MariaDB.
-- =====================================================================
SET @t := 'learner_request';

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = @t AND COLUMN_NAME = 'reply');
SET @s := IF(@c = 0, 'ALTER TABLE `learner_request` ADD COLUMN `reply` TEXT NULL AFTER `request_status`', 'SELECT 1');
PREPARE q FROM @s; EXECUTE q; DEALLOCATE PREPARE q;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = @t AND COLUMN_NAME = 'handled_at');
SET @s := IF(@c = 0, 'ALTER TABLE `learner_request` ADD COLUMN `handled_at` TIMESTAMP NULL DEFAULT NULL AFTER `reply`', 'SELECT 1');
PREPARE q FROM @s; EXECUTE q; DEALLOCATE PREPARE q;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = @t AND COLUMN_NAME = 'handled_by_type');
SET @s := IF(@c = 0, 'ALTER TABLE `learner_request` ADD COLUMN `handled_by_type` VARCHAR(20) NULL AFTER `handled_at`', 'SELECT 1');
PREPARE q FROM @s; EXECUTE q; DEALLOCATE PREPARE q;

SET @c := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = @t AND COLUMN_NAME = 'handled_by_id');
SET @s := IF(@c = 0, 'ALTER TABLE `learner_request` ADD COLUMN `handled_by_id` BIGINT UNSIGNED NULL AFTER `handled_by_type`', 'SELECT 1');
PREPARE q FROM @s; EXECUTE q; DEALLOCATE PREPARE q;

-- =====================================================================
-- B2. Permission "Learner Requests": same category and same plans as "Learner List"
-- =====================================================================
INSERT INTO `permissions` (`name`, `slug`, `guard_name`, `permission_category_id`, `description`, `created_at`, `updated_at`)
SELECT 'Learner Requests', 'learner-requests', 'library',
       (SELECT `permission_category_id` FROM `permissions` WHERE `guard_name` = 'library' AND `name` = 'Learner List' ORDER BY `id` LIMIT 1),
       'See and answer learner requests: renew, change plan, upgrade, swap seat, close', NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `guard_name` = 'library' AND `name` = 'Learner Requests');

INSERT INTO `subscription_permission` (`subscription_id`, `permission_id`, `created_at`)
SELECT sp.`subscription_id`, p2.`id`, NOW()
FROM `subscription_permission` sp
JOIN `permissions` p1 ON p1.`id` = sp.`permission_id` AND p1.`guard_name` = 'library' AND p1.`name` = 'Learner List'
JOIN `permissions` p2 ON p2.`guard_name` = 'library' AND p2.`name` = 'Learner Requests'
WHERE NOT EXISTS (SELECT 1 FROM `subscription_permission` x WHERE x.`subscription_id` = sp.`subscription_id` AND x.`permission_id` = p2.`id`);

-- =====================================================================
-- B3. Sidebar: Learners → Requests (same group as the "Learners" list link)
-- =====================================================================
INSERT INTO `menus` (`name`, `url`, `icon`, `parent_id`, `order`, `guard`, `status`, `has_permissions`, `created_at`, `updated_at`)
SELECT 'Requests', 'learner.requests.index', NULL, m.`parent_id`, 7, 'library', 1, 'Learner Requests', NOW(), NOW()
FROM `menus` m
WHERE m.`guard` = 'library' AND m.`url` = 'learners' AND m.`deleted_at` IS NULL AND m.`parent_id` IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM `menus` x WHERE x.`guard` = 'library' AND x.`url` = 'learner.requests.index' AND x.`deleted_at` IS NULL)
LIMIT 1;

-- B4. mark migration B as run
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_19_000002_learner_requests_inbox', (SELECT IFNULL(MAX(`batch`), 0) + 1 FROM `migrations` m) FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_11_19_000002_learner_requests_inbox');

-- =====================================================================
-- CHECK (run after; each line should return what the comment says)
-- =====================================================================
-- SELECT `id`, `name` FROM `toggle_features` WHERE `category` = 'Orders';                          → 3 rows, ids above 35
-- SELECT `name` FROM `permissions` WHERE `guard_name` = 'library' AND `name` IN ('Orders Summary', 'Learner Requests');   → 2 rows
-- SHOW COLUMNS FROM `learner_request` LIKE 'handled%';                                              → handled_at, handled_by_type, handled_by_id
-- SELECT `name`, `url` FROM `menus` WHERE `url` = 'learner.requests.index';                           → 1 row
-- SELECT `migration` FROM `migrations` WHERE `migration` LIKE '2026_11_19_%';                         → 2 rows

-- =====================================================================
-- UNDO — only if you need to roll back; uncomment and run
-- =====================================================================
-- DELETE FROM `toggle_features` WHERE `category` = 'Orders' AND `name` IN ('Hide Orders Menu', 'Hide Order Reports Menu', 'Hide Orders Summary on Dashboard');
-- DELETE FROM `subscription_permission` WHERE `permission_id` IN (SELECT `id` FROM `permissions` WHERE `guard_name` = 'library' AND `name` IN ('Orders Summary', 'Learner Requests'));
-- DELETE FROM `library_user_permissions` WHERE `permission_id` IN (SELECT `id` FROM `permissions` WHERE `guard_name` = 'library' AND `name` IN ('Orders Summary', 'Learner Requests'));
-- DELETE FROM `permissions` WHERE `guard_name` = 'library' AND `name` IN ('Orders Summary', 'Learner Requests');
-- DELETE FROM `menus` WHERE `guard` = 'library' AND `url` = 'learner.requests.index';
-- DELETE FROM `migrations` WHERE `migration` IN ('2026_11_19_000001_orders_summary_permission_and_toggles', '2026_11_19_000002_learner_requests_inbox');
-- (the four learner_request columns can stay: they are nullable and unused without the code)
