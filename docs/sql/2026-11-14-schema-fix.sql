-- Idempotent schema patch for live DBs imported from older docs/sql/install.sql (MySQL 8+)
-- Run once after deploy: mysql -u USER -p DATABASE < docs/sql/2026-11-14-schema-fix.sql

SET NAMES utf8mb4;
SET @db := DATABASE();

-- Helper: run ALTER only when column is missing
DROP PROCEDURE IF EXISTS _coffee_add_column_if_missing;
DELIMITER //
CREATE PROCEDURE _coffee_add_column_if_missing(IN tbl VARCHAR(64), IN col VARCHAR(64), IN ddl TEXT)
BEGIN
  IF (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = tbl AND COLUMN_NAME = col
  ) = 0 THEN
    SET @sql = CONCAT('ALTER TABLE `', tbl, '` ADD COLUMN ', ddl);
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END//
DELIMITER ;

DROP PROCEDURE IF EXISTS _coffee_drop_column_if_exists;
DELIMITER //
CREATE PROCEDURE _coffee_drop_column_if_exists(IN tbl VARCHAR(64), IN col VARCHAR(64))
BEGIN
  IF (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = tbl AND COLUMN_NAME = col
  ) > 0 THEN
    SET @sql = CONCAT('ALTER TABLE `', tbl, '` DROP COLUMN `', col, '`');
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END//
DELIMITER ;

CALL _coffee_add_column_if_missing('branches', 'address', '`address` TEXT NULL AFTER `name_ar`');

CALL _coffee_add_column_if_missing('customers', 'referral_code', '`referral_code` VARCHAR(32) NULL AFTER `vat_number`');
CALL _coffee_add_column_if_missing('customers', 'referred_by_customer_id', '`referred_by_customer_id` BIGINT UNSIGNED NULL AFTER `referral_code`');
CALL _coffee_add_column_if_missing('customers', 'points_balance', '`points_balance` BIGINT NOT NULL DEFAULT 0 AFTER `referred_by_customer_id`');
CALL _coffee_add_column_if_missing('customers', 'profile', '`profile` JSON NULL AFTER `points_balance`');

-- Migrate legacy loyalty_points -> points_balance when present
SET @sql = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'loyalty_points') > 0
  AND (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'customers' AND COLUMN_NAME = 'points_balance') > 0,
  'UPDATE `customers` SET `points_balance` = COALESCE(`points_balance`, 0) + COALESCE(`loyalty_points`, 0)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
CALL _coffee_drop_column_if_exists('customers', 'loyalty_points');

CALL _coffee_add_column_if_missing('loyalty_transactions', 'type', '`type` VARCHAR(32) NOT NULL DEFAULT ''adjustment'' AFTER `pos_order_id`');
CALL _coffee_add_column_if_missing('loyalty_transactions', 'monetary_value_ex_vat', '`monetary_value_ex_vat` DECIMAL(12,2) NULL AFTER `points_delta`');
CALL _coffee_add_column_if_missing('loyalty_transactions', 'metadata', '`metadata` JSON NULL AFTER `monetary_value_ex_vat`');
CALL _coffee_add_column_if_missing('loyalty_transactions', 'occurred_at', '`occurred_at` DATETIME NULL AFTER `metadata`');

SET @sql = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'loyalty_transactions' AND COLUMN_NAME = 'reason') > 0
  AND (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'loyalty_transactions' AND COLUMN_NAME = 'type') > 0,
  'UPDATE `loyalty_transactions` SET `type` = COALESCE(NULLIF(`type`, ''''), `reason`, ''adjustment''), `occurred_at` = COALESCE(`occurred_at`, `created_at`, NOW())',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'loyalty_transactions' AND COLUMN_NAME = 'occurred_at') > 0,
  'UPDATE `loyalty_transactions` SET `occurred_at` = COALESCE(`occurred_at`, `created_at`, NOW()) WHERE `occurred_at` IS NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

CALL _coffee_drop_column_if_exists('loyalty_transactions', 'reason');

-- ingredients: drop legacy `name` when i18n columns exist (code no longer requires it)
CALL _coffee_drop_column_if_exists('ingredients', 'name');

DROP PROCEDURE IF EXISTS _coffee_add_column_if_missing;
DROP PROCEDURE IF EXISTS _coffee_drop_column_if_exists;
