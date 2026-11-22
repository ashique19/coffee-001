-- Idempotent schema additions for Fancy Coffee invoice ports (MariaDB 10.4+ / MySQL 8+)
-- Run once in phpMyAdmin (or: mysql -u USER -p DATABASE < docs/sql/2026-11-23-invoice-port-schema.sql)
-- after docs/sql/install.sql (and docs/sql/2026-11-14-schema-fix.sql if that was needed).
--
-- Adds:
--   staff_users.locale          (staff UI language; default bn)
--   invoices.due_date           (canonical due date used when issuing invoices)
--   product_units               (line units for the issue page; seeds PCS, KG, L)
--   products.product_unit_id    (nullable FK to product_units)
-- Skips: invoice_payments, standard-buyer / customer identity columns, product_sizes.

SET NAMES utf8mb4;

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

DROP PROCEDURE IF EXISTS _coffee_add_fk_if_missing;
DELIMITER //
CREATE PROCEDURE _coffee_add_fk_if_missing(
  IN tbl VARCHAR(64),
  IN fk_name VARCHAR(64),
  IN fk_ddl TEXT
)
BEGIN
  IF (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = tbl
      AND CONSTRAINT_NAME = fk_name
      AND CONSTRAINT_TYPE = 'FOREIGN KEY'
  ) = 0 THEN
    SET @sql = CONCAT('ALTER TABLE `', tbl, '` ADD CONSTRAINT `', fk_name, '` ', fk_ddl);
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
  END IF;
END//
DELIMITER ;

CALL _coffee_add_column_if_missing(
  'staff_users',
  'locale',
  '`locale` VARCHAR(8) NOT NULL DEFAULT ''bn'' AFTER `is_active`'
);

CALL _coffee_add_column_if_missing(
  'invoices',
  'due_date',
  '`due_date` DATE NULL AFTER `issued_at`'
);

CREATE TABLE IF NOT EXISTS `product_units` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name_en` VARCHAR(255) NOT NULL,
  `name_ar` VARCHAR(255) NOT NULL,
  `name_bn` VARCHAR(160) NOT NULL,
  `code` VARCHAR(16) NOT NULL,
  `ubl_unit_code` VARCHAR(16) NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_units_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CALL _coffee_add_column_if_missing(
  'products',
  'product_unit_id',
  '`product_unit_id` BIGINT UNSIGNED NULL AFTER `product_category_id`'
);

CALL _coffee_add_fk_if_missing(
  'products',
  'products_product_unit_id_foreign',
  'FOREIGN KEY (`product_unit_id`) REFERENCES `product_units` (`id`) ON DELETE SET NULL'
);

INSERT INTO `product_units` (`name_en`, `name_ar`, `name_bn`, `code`, `ubl_unit_code`, `is_active`, `created_at`, `updated_at`)
SELECT 'Piece', 'قطعة', 'পিস', 'PCS', 'PCE', 1, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `product_units` WHERE `code` = 'PCS');

INSERT INTO `product_units` (`name_en`, `name_ar`, `name_bn`, `code`, `ubl_unit_code`, `is_active`, `created_at`, `updated_at`)
SELECT 'Kilogram', 'كيلوغرام', 'কিলোগ্রাম', 'KG', 'KGM', 1, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `product_units` WHERE `code` = 'KG');

INSERT INTO `product_units` (`name_en`, `name_ar`, `name_bn`, `code`, `ubl_unit_code`, `is_active`, `created_at`, `updated_at`)
SELECT 'Liter', 'لتر', 'লিটার', 'L', 'LTR', 1, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `product_units` WHERE `code` = 'L');

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_23_120000_add_invoice_port_schema', b.max_batch + 1
FROM (SELECT COALESCE(MAX(batch), 0) AS max_batch FROM `migrations`) AS b
WHERE NOT EXISTS (SELECT 1 FROM `migrations` WHERE `migration` = '2026_11_23_120000_add_invoice_port_schema');

DROP PROCEDURE IF EXISTS _coffee_add_column_if_missing;
DROP PROCEDURE IF EXISTS _coffee_add_fk_if_missing;
