-- =====================================================================
-- Libraro: Ordering & Services module (18 Nov 2026) — for the live DB, phpMyAdmin.
-- Same result as the migrations 2026_11_18_000001 and _000002 (docs/ORDERING.md).
-- Plain statements (no variables), MariaDB 10.3+ / MySQL 5.7+. Rows are found by name, never by id.
-- Safe to run again. Only NEW tables / rows: nothing existing is changed.
-- TAKE A FULL BACKUP FIRST. Run this BEFORE deploying the Ordering code.
-- =====================================================================
SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- 1. Ordering tables (migration 2026_11_18_000001_create_ordering_tables)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `order_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `library_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `mode_seat` tinyint(1) NOT NULL DEFAULT 1,
  `mode_table` tinyint(1) NOT NULL DEFAULT 0,
  `mode_counter` tinyint(1) NOT NULL DEFAULT 1,
  `seat_change_allowed` tinyint(1) NOT NULL DEFAULT 0,
  `opens_at` time DEFAULT NULL,
  `closes_at` time DEFAULT NULL,
  `prep_minutes` smallint(5) unsigned NOT NULL DEFAULT 15,
  `delivery_charge` decimal(10,2) NOT NULL DEFAULT 0.00,
  `pay_online` tinyint(1) NOT NULL DEFAULT 1,
  `pay_offline` tinyint(1) NOT NULL DEFAULT 1,
  `pay_later` tinyint(1) NOT NULL DEFAULT 0,
  `pay_later_scope` enum('all','selected') NOT NULL DEFAULT 'all',
  `pay_later_learner_ids` json DEFAULT NULL,
  `pay_later_limit` decimal(10,2) NOT NULL DEFAULT 0.00,
  `expired_can_order` tinyint(1) NOT NULL DEFAULT 0,
  `max_open_orders` tinyint(3) unsigned NOT NULL DEFAULT 3,
  `reminder_minutes` smallint(5) unsigned NOT NULL DEFAULT 2,
  `escalation_minutes` smallint(5) unsigned NOT NULL DEFAULT 5,
  `sound_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `replacement_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `replacement_multiple` tinyint(1) NOT NULL DEFAULT 1,
  `partial_fulfilment` tinyint(1) NOT NULL DEFAULT 1,
  `replacement_timeout_minutes` smallint(5) unsigned NOT NULL DEFAULT 10,
  `replacement_timeout_action` enum('remove','escalate') NOT NULL DEFAULT 'remove',
  `learner_cancel_until` enum('new','under_review','confirmed') NOT NULL DEFAULT 'new',
  `upload_types` varchar(100) NOT NULL DEFAULT 'pdf,jpg,jpeg,png,doc,docx',
  `upload_max_mb` smallint(5) unsigned NOT NULL DEFAULT 10,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_settings_branch_id_unique` (`branch_id`),
  KEY `order_settings_library_id_index` (`library_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `order_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `library_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `name` varchar(80) NOT NULL,
  `icon` varchar(40) DEFAULT NULL,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `is_service` tinyint(1) NOT NULL DEFAULT 0,
  `allow_online` tinyint(1) NOT NULL DEFAULT 1,
  `allow_offline` tinyint(1) NOT NULL DEFAULT 1,
  `allow_pay_later` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_categories_library_id_branch_id_index` (`library_id`,`branch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `order_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `library_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `order_category_id` bigint(20) unsigned NOT NULL,
  `name` varchar(120) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `price_unit` varchar(30) DEFAULT NULL,
  `is_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `is_available` tinyint(1) NOT NULL DEFAULT 1,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `needs_upload` tinyint(1) NOT NULL DEFAULT 0,
  `modes` json DEFAULT NULL,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_items_library_id_branch_id_index` (`library_id`,`branch_id`),
  KEY `order_items_order_category_id_index` (`order_category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `order_tables` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `library_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `label` varchar(30) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_tables_branch_id_label_unique` (`branch_id`,`label`),
  KEY `order_tables_library_id_index` (`library_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `library_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `learner_id` bigint(20) unsigned NOT NULL,
  `order_no` varchar(30) NOT NULL,
  `idempotency_key` varchar(64) DEFAULT NULL,
  `mode` enum('seat','table','counter') NOT NULL,
  `seat_no` varchar(50) DEFAULT NULL,
  `order_table_id` bigint(20) unsigned DEFAULT NULL,
  `table_label` varchar(30) DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'new',
  `payment_method` enum('online','offline','pay_later') NOT NULL,
  `payment_status` varchar(30) NOT NULL DEFAULT 'pending',
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `delivery_charge` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `refunded_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `allow_partial` tinyint(1) NOT NULL DEFAULT 1,
  `note` varchar(500) DEFAULT NULL,
  `payment_screenshot` varchar(255) DEFAULT NULL,
  `payment_ref` varchar(100) DEFAULT NULL,
  `estimated_ready_at` timestamp NULL DEFAULT NULL,
  `handled_by_type` varchar(20) DEFAULT NULL,
  `handled_by_id` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `preparing_at` timestamp NULL DEFAULT NULL,
  `ready_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `cancel_reason` varchar(255) DEFAULT NULL,
  `reject_reason` varchar(255) DEFAULT NULL,
  `reminded_at` timestamp NULL DEFAULT NULL,
  `escalated_at` timestamp NULL DEFAULT NULL,
  `version` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `orders_order_no_unique` (`order_no`),
  UNIQUE KEY `orders_learner_id_idempotency_key_unique` (`learner_id`,`idempotency_key`),
  KEY `orders_branch_id_status_index` (`branch_id`,`status`),
  KEY `orders_branch_id_payment_status_index` (`branch_id`,`payment_status`),
  KEY `orders_learner_id_created_at_index` (`learner_id`,`created_at`),
  KEY `orders_library_id_index` (`library_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `order_lines` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `order_item_id` bigint(20) unsigned DEFAULT NULL,
  `order_category_id` bigint(20) unsigned DEFAULT NULL,
  `category_name` varchar(80) DEFAULT NULL,
  `item_name` varchar(120) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `quantity` smallint(5) unsigned NOT NULL DEFAULT 1,
  `line_total` decimal(10,2) NOT NULL,
  `fulfilment_status` varchar(30) NOT NULL DEFAULT 'pending',
  `replaced_line_id` bigint(20) unsigned DEFAULT NULL,
  `upload_path` varchar(255) DEFAULT NULL,
  `upload_name` varchar(255) DEFAULT NULL,
  `options` json DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_lines_order_id_index` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `order_replacements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `order_line_id` bigint(20) unsigned NOT NULL,
  `suggested_item_id` bigint(20) unsigned DEFAULT NULL,
  `suggested_name` varchar(120) DEFAULT NULL,
  `suggested_price` decimal(10,2) DEFAULT NULL,
  `quantity` smallint(5) unsigned NOT NULL DEFAULT 1,
  `price_diff` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('pending','accepted','rejected','expired','withdrawn') NOT NULL DEFAULT 'pending',
  `deadline_at` timestamp NULL DEFAULT NULL,
  `responded_at` timestamp NULL DEFAULT NULL,
  `reminded_at` timestamp NULL DEFAULT NULL,
  `suggested_by_type` varchar(20) DEFAULT NULL,
  `suggested_by_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_replacements_order_id_index` (`order_id`),
  KEY `order_replacements_order_line_id_index` (`order_line_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `order_payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `library_id` bigint(20) unsigned NOT NULL,
  `branch_id` bigint(20) unsigned NOT NULL,
  `order_id` bigint(20) unsigned NOT NULL,
  `kind` enum('payment','refund') NOT NULL DEFAULT 'payment',
  `method` varchar(30) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','verified','failed') NOT NULL DEFAULT 'pending',
  `txn_ref` varchar(100) DEFAULT NULL,
  `screenshot` varchar(255) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `recorded_by_type` varchar(20) DEFAULT NULL,
  `recorded_by_id` bigint(20) unsigned DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_payments_order_id_kind_txn_ref_unique` (`order_id`,`kind`,`txn_ref`),
  KEY `order_payments_branch_id_created_at_index` (`branch_id`,`created_at`),
  KEY `order_payments_order_id_index` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `order_activities` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) unsigned NOT NULL,
  `actor_type` varchar(20) NOT NULL,
  `actor_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `from_status` varchar(30) DEFAULT NULL,
  `to_status` varchar(30) DEFAULT NULL,
  `meta` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `order_activities_order_id_index` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- 2. Permissions "Ordering Setup" + "Manage Orders" (Pro plan) and the "Orders" sidebar group
--    (migration 2026_11_18_000002_ordering_permissions_and_menus)
-- ---------------------------------------------------------------------
INSERT INTO `permission_categories` (`name`, `created_at`, `updated_at`) SELECT 'Ordering & Services', NOW(), NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `permission_categories` WHERE `name` = 'Ordering & Services');

INSERT INTO `permissions` (`name`, `slug`, `guard_name`, `permission_category_id`, `description`, `created_at`, `updated_at`) SELECT 'Ordering Setup', 'ordering-setup', 'library', (SELECT `id` FROM `permission_categories` WHERE `name` = 'Ordering & Services' ORDER BY `id` LIMIT 1), 'Menu, items, cafeteria tables and settings of Ordering & Services', NOW(), NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `guard_name` = 'library' AND `name` = 'Ordering Setup');
INSERT INTO `permissions` (`name`, `slug`, `guard_name`, `permission_category_id`, `description`, `created_at`, `updated_at`) SELECT 'Manage Orders', 'manage-orders', 'library', (SELECT `id` FROM `permission_categories` WHERE `name` = 'Ordering & Services' ORDER BY `id` LIMIT 1), 'Receive, prepare and deliver learner orders; record their payments', NOW(), NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `permissions` WHERE `guard_name` = 'library' AND `name` = 'Manage Orders');

-- Pro plan (subscription id 3) gets both
INSERT INTO `subscription_permission` (`subscription_id`, `permission_id`, `created_at`) SELECT 3, p.`id`, NOW() FROM `permissions` p WHERE p.`guard_name` = 'library' AND p.`name` IN ('Ordering Setup', 'Manage Orders') AND NOT EXISTS (SELECT 1 FROM `subscription_permission` sp WHERE sp.`subscription_id` = 3 AND sp.`permission_id` = p.`id`);

-- Sidebar: "Orders" group (after Student Chat) → Menu & Items, Order Settings
INSERT INTO `menus` (`name`, `url`, `icon`, `parent_id`, `order`, `guard`, `status`, `has_permissions`, `created_at`, `updated_at`) SELECT 'Orders', 'ordering.items', 'fa fa-mug-hot', NULL, 4, 'library', 1, NULL, NOW(), NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `menus` WHERE `guard` = 'library' AND `parent_id` IS NULL AND `name` = 'Orders' AND `deleted_at` IS NULL);
INSERT INTO `menus` (`name`, `url`, `icon`, `parent_id`, `order`, `guard`, `status`, `has_permissions`, `created_at`, `updated_at`) SELECT 'Menu & Items', 'ordering.items', NULL, m.`id`, 2, 'library', 1, 'Ordering Setup', NOW(), NOW() FROM `menus` m WHERE m.`guard` = 'library' AND m.`parent_id` IS NULL AND m.`name` = 'Orders' AND m.`deleted_at` IS NULL AND NOT EXISTS (SELECT 1 FROM `menus` c WHERE c.`parent_id` = m.`id` AND c.`url` = 'ordering.items' AND c.`deleted_at` IS NULL) LIMIT 1;
INSERT INTO `menus` (`name`, `url`, `icon`, `parent_id`, `order`, `guard`, `status`, `has_permissions`, `created_at`, `updated_at`) SELECT 'Order Settings', 'ordering.settings', NULL, m.`id`, 3, 'library', 1, 'Ordering Setup', NOW(), NOW() FROM `menus` m WHERE m.`guard` = 'library' AND m.`parent_id` IS NULL AND m.`name` = 'Orders' AND m.`deleted_at` IS NULL AND NOT EXISTS (SELECT 1 FROM `menus` c WHERE c.`parent_id` = m.`id` AND c.`url` = 'ordering.settings' AND c.`deleted_at` IS NULL) LIMIT 1;

-- ---------------------------------------------------------------------
-- 3. Mark both migrations as run (so `php artisan migrate` skips them later)
-- ---------------------------------------------------------------------
INSERT INTO `migrations` (`migration`, `batch`) SELECT '2026_11_18_000001_create_ordering_tables', (SELECT IFNULL(MAX(`batch`), 0) + 1 FROM `migrations` m) FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_11_18_000001_create_ordering_tables');
INSERT INTO `migrations` (`migration`, `batch`) SELECT '2026_11_18_000002_ordering_permissions_and_menus', (SELECT IFNULL(MAX(`batch`), 0) FROM `migrations` m) FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_11_18_000002_ordering_permissions_and_menus');

-- ---------------------------------------------------------------------
-- 4. Step 3: "Order Board" first in the Orders group, for staff with "Manage Orders"
--    (migration 2026_11_18_000003_order_board_menu)
-- ---------------------------------------------------------------------
INSERT INTO `menus` (`name`, `url`, `icon`, `parent_id`, `order`, `guard`, `status`, `has_permissions`, `created_at`, `updated_at`) SELECT 'Order Board', 'orders.board', NULL, m.`id`, 1, 'library', 1, 'Manage Orders', NOW(), NOW() FROM `menus` m WHERE m.`guard` = 'library' AND m.`parent_id` IS NULL AND m.`name` = 'Orders' AND m.`deleted_at` IS NULL AND NOT EXISTS (SELECT 1 FROM `menus` c WHERE c.`parent_id` = m.`id` AND c.`url` = 'orders.board' AND c.`deleted_at` IS NULL) LIMIT 1;
INSERT INTO `migrations` (`migration`, `batch`) SELECT '2026_11_18_000003_order_board_menu', (SELECT IFNULL(MAX(`batch`), 0) + 1 FROM `migrations` m) FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_11_18_000003_order_board_menu');

-- ---------------------------------------------------------------------
-- UNDO (only if you want to remove the module again) — uncomment and run:
-- ---------------------------------------------------------------------
-- DELETE c FROM `menus` c JOIN `menus` p ON p.`id` = c.`parent_id` WHERE p.`guard` = 'library' AND p.`name` = 'Orders' AND p.`parent_id` IS NULL;
-- DELETE FROM `menus` WHERE `guard` = 'library' AND `name` = 'Orders' AND `parent_id` IS NULL;
-- DELETE FROM `subscription_permission` WHERE `permission_id` IN (SELECT `id` FROM `permissions` WHERE `guard_name` = 'library' AND `name` IN ('Ordering Setup', 'Manage Orders'));
-- DELETE FROM `library_user_permissions` WHERE `permission_id` IN (SELECT `id` FROM `permissions` WHERE `guard_name` = 'library' AND `name` IN ('Ordering Setup', 'Manage Orders'));
-- DELETE FROM `permissions` WHERE `guard_name` = 'library' AND `name` IN ('Ordering Setup', 'Manage Orders');
-- DELETE FROM `permission_categories` WHERE `name` = 'Ordering & Services';
-- DROP TABLE IF EXISTS `order_activities`, `order_payments`, `order_replacements`, `order_lines`, `orders`, `order_tables`, `order_items`, `order_categories`, `order_settings`;
-- DELETE FROM `migrations` WHERE `migration` IN ('2026_11_18_000001_create_ordering_tables', '2026_11_18_000002_ordering_permissions_and_menus', '2026_11_18_000003_order_board_menu');
