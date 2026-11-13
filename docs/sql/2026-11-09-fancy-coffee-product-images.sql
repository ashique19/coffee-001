-- Idempotent patch for phpMyAdmin (MySQL 8+ / MariaDB 10.4+)
-- Covers migration: 2026_11_09_100000_add_pos_catalog_presentation_columns
-- Plus fancy-coffee demo image_url / icon_key seed data.

-- ── DDL: product_categories.icon_key ─────────────────────────────────────────
SET @db := DATABASE();
SET @ddl := (
    SELECT IF(
        EXISTS(
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = @db
              AND TABLE_NAME = 'product_categories'
              AND COLUMN_NAME = 'icon_key'
        ),
        'SELECT 1',
        'ALTER TABLE product_categories ADD COLUMN icon_key VARCHAR(32) NULL AFTER name_ar'
    )
);
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ── DDL: products.image_url ────────────────────────────────────────────────────
SET @ddl := (
    SELECT IF(
        EXISTS(
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = @db
              AND TABLE_NAME = 'products'
              AND COLUMN_NAME = 'image_url'
        ),
        'SELECT 1',
        'ALTER TABLE products ADD COLUMN image_url VARCHAR(255) NULL AFTER name_ar'
    )
);
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ── Register migration (derived-table MAX avoids empty-set NULL on aggregates) ─
INSERT INTO migrations (migration, batch)
SELECT '2026_11_09_100000_add_pos_catalog_presentation_columns', b.max_batch + 1
FROM (SELECT COALESCE(MAX(batch), 0) AS max_batch FROM migrations) AS b
WHERE NOT EXISTS (
    SELECT 1 FROM migrations
    WHERE migration = '2026_11_09_100000_add_pos_catalog_presentation_columns'
);

-- ── Demo data: fancy-coffee product images (safe to re-run) ───────────────────
UPDATE products p
    INNER JOIN tenants t ON t.id = p.tenant_id
SET p.image_url = 'images/fancy-coffee/cardamom-latte.webp'
WHERE t.slug = 'fancy-coffee' AND p.sku = 'LATTE';

UPDATE products p
    INNER JOIN tenants t ON t.id = p.tenant_id
SET p.image_url = 'images/fancy-coffee/espresso.webp'
WHERE t.slug = 'fancy-coffee' AND p.sku = 'ESP';

UPDATE products p
    INNER JOIN tenants t ON t.id = p.tenant_id
SET p.image_url = 'images/fancy-coffee/iced-latte.webp'
WHERE t.slug = 'fancy-coffee' AND p.sku = 'ICED';

UPDATE products p
    INNER JOIN tenants t ON t.id = p.tenant_id
SET p.image_url = 'images/fancy-coffee/cold-brew.webp'
WHERE t.slug = 'fancy-coffee' AND p.sku = 'COLD';

UPDATE product_categories c
    INNER JOIN tenants t ON t.id = c.tenant_id
SET c.icon_key = 'cup'
WHERE t.slug = 'fancy-coffee';
