-- Idempotent patch for phpMyAdmin (MySQL 8+ / MariaDB 10.4+)
-- Migration: 2026_11_09_120000_add_invoices_list_index

SET @db := DATABASE();

SET @ddl := (
    SELECT IF(
        EXISTS(
            SELECT 1 FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = @db
              AND TABLE_NAME = 'invoices'
              AND INDEX_NAME = 'invoices_tenant_issued_idx'
        ),
        'SELECT 1',
        'ALTER TABLE invoices ADD INDEX invoices_tenant_issued_idx (tenant_id, issued_at)'
    )
);
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT INTO migrations (migration, batch)
SELECT '2026_11_09_120000_add_invoices_list_index', COALESCE(MAX(batch), 0) + 1 FROM (SELECT batch FROM migrations) AS m
WHERE NOT EXISTS (
    SELECT 1 FROM migrations WHERE migration = '2026_11_09_120000_add_invoices_list_index'
);
