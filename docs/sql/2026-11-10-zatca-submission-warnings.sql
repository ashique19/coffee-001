-- Idempotent patch: zatca_submissions.last_warnings (JSON)
-- Migration: 2026_11_10_120000_add_last_warnings_to_zatca_submissions

SET @db := DATABASE();

SET @exists := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = @db
      AND TABLE_NAME = 'zatca_submissions'
      AND COLUMN_NAME = 'last_warnings'
);

SET @sql := IF(
    @exists = 0,
    'ALTER TABLE zatca_submissions ADD COLUMN last_warnings JSON NULL AFTER last_error',
    'SELECT ''last_warnings already exists'' AS note'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT INTO migrations (migration, batch)
SELECT '2026_11_10_120000_add_last_warnings_to_zatca_submissions', b.max_batch + 1
FROM (SELECT COALESCE(MAX(batch), 0) AS max_batch FROM migrations) b
WHERE NOT EXISTS (
    SELECT 1 FROM migrations WHERE migration = '2026_11_10_120000_add_last_warnings_to_zatca_submissions'
);
