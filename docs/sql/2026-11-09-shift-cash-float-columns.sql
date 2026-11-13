-- Idempotent patch for phpMyAdmin (MySQL 8+ / MariaDB 10.4+)
-- Migration: 2026_11_09_110000_add_shift_cash_float_columns

SET @db := DATABASE();

SET @ddl := (
    SELECT IF(
        EXISTS(
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'shifts' AND COLUMN_NAME = 'opening_cash_float'
        ),
        'SELECT 1',
        'ALTER TABLE shifts ADD COLUMN opening_cash_float DECIMAL(12,2) NULL AFTER started_at'
    )
);
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @ddl := (
    SELECT IF(
        EXISTS(
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'shifts' AND COLUMN_NAME = 'closing_cash_counted'
        ),
        'SELECT 1',
        'ALTER TABLE shifts ADD COLUMN closing_cash_counted DECIMAL(12,2) NULL AFTER opening_cash_float'
    )
);
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @ddl := (
    SELECT IF(
        EXISTS(
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'shifts' AND COLUMN_NAME = 'close_notes'
        ),
        'SELECT 1',
        'ALTER TABLE shifts ADD COLUMN close_notes TEXT NULL AFTER closing_cash_counted'
    )
);
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT INTO migrations (migration, batch)
SELECT '2026_11_09_110000_add_shift_cash_float_columns', b.max_batch + 1
FROM (SELECT COALESCE(MAX(batch), 0) AS max_batch FROM migrations) AS b
WHERE NOT EXISTS (
    SELECT 1 FROM migrations
    WHERE migration = '2026_11_09_110000_add_shift_cash_float_columns'
);
