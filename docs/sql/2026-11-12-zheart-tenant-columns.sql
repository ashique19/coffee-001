-- Idempotent Z-Heart tenant columns (MySQL 8+ / MariaDB 10.4+)
-- Migration: 2026_11_12_200000_add_zheart_columns_to_tenants

SET @db := DATABASE();

SET @ddl := (
    SELECT IF(
        EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'tenants' AND COLUMN_NAME = 'zatca_driver'),
        'SELECT 1',
        'ALTER TABLE tenants ADD COLUMN zatca_driver VARCHAR(16) NULL AFTER settings'
    )
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @ddl := (
    SELECT IF(
        EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'tenants' AND COLUMN_NAME = 'zheart_api_key'),
        'SELECT 1',
        'ALTER TABLE tenants ADD COLUMN zheart_api_key TEXT NULL AFTER zatca_driver'
    )
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO migrations (migration, batch)
SELECT '2026_11_12_200000_add_zheart_columns_to_tenants', b.max_batch + 1
FROM (SELECT COALESCE(MAX(batch), 0) AS max_batch FROM migrations) AS b
WHERE NOT EXISTS (
    SELECT 1 FROM migrations WHERE migration = '2026_11_12_200000_add_zheart_columns_to_tenants'
);
