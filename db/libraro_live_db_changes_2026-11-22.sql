-- =====================================================================
-- Libraro: live DB changes of 22 Nov 2026 — for phpMyAdmin.
-- Plain statements, MariaDB 10.3+ / MySQL 5.7+. Safe to run again.
-- TAKE A BACKUP FIRST. Run this BEFORE deploying the code.
-- =====================================================================
SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- 1. Pay-to-library link (renewal / pending payment reminders) — migration
--    2026_11_22_000001_bookings_type_allow_due_payment (docs/QR_PAY_LINK.md)
--    bookings.type ENUM → VARCHAR(30): every existing value stays as it is;
--    new value 'qr_due_payment' (and the app's 'learner_book') can now be stored.
-- ---------------------------------------------------------------------
ALTER TABLE `bookings` MODIFY `type` VARCHAR(30) NULL DEFAULT NULL;

INSERT INTO `migrations` (`migration`, `batch`) SELECT '2026_11_22_000001_bookings_type_allow_due_payment', (SELECT IFNULL(MAX(`batch`), 0) + 1 FROM `migrations` m) FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_11_22_000001_bookings_type_allow_due_payment');

-- Check: SHOW COLUMNS FROM `bookings` LIKE 'type';   → varchar(30)
-- UNDO: not needed (VARCHAR accepts everything the old ENUM did).
