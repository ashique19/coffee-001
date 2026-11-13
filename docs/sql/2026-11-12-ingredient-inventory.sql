-- Idempotent ingredient inventory patch (MySQL 8+ / MariaDB 10.4+)
-- Migration: 2026_11_12_140000_ingredient_inventory_enhancements

SET @db := DATABASE();

-- Permissions (tenant guard)
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'inventory.manage', 'tenant', NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'inventory.manage' AND guard_name = 'tenant');

INSERT IGNORE INTO role_has_permissions (permission_id, role_id)
SELECT p.id, r.id
FROM permissions p
JOIN roles r ON r.guard_name = 'tenant' AND r.name IN ('owner', 'manager')
WHERE p.name = 'inventory.manage' AND p.guard_name = 'tenant';

DELETE rhp FROM role_has_permissions rhp
INNER JOIN permissions p ON p.id = rhp.permission_id AND p.guard_name = 'tenant' AND p.name = 'inventory.view'
INNER JOIN roles r ON r.id = rhp.role_id AND r.guard_name = 'tenant' AND r.name = 'cashier';

-- ingredients: i18n + threshold + active
SET @ddl := (
    SELECT IF(
        EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'ingredients'),
        IF(
            EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'ingredients' AND COLUMN_NAME = 'name_en'),
            'SELECT 1',
            'ALTER TABLE ingredients
                ADD COLUMN name_en VARCHAR(160) NULL AFTER name,
                ADD COLUMN name_ar VARCHAR(160) NULL AFTER name_en,
                ADD COLUMN name_bn VARCHAR(160) NULL AFTER name_ar,
                ADD COLUMN low_stock_threshold DECIMAL(12,3) NOT NULL DEFAULT 0 AFTER reorder_level,
                ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER low_stock_threshold'
        ),
        'SELECT 1'
    )
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE ingredients SET name_en = name WHERE name_en IS NULL OR name_en = '';

-- product_ingredients pivot
CREATE TABLE IF NOT EXISTS product_ingredients (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id BIGINT UNSIGNED NOT NULL,
    ingredient_id BIGINT UNSIGNED NOT NULL,
    quantity_per_unit DECIMAL(12,3) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY product_ingredients_product_id_ingredient_id_unique (product_id, ingredient_id),
    CONSTRAINT product_ingredients_product_id_foreign FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE,
    CONSTRAINT product_ingredients_ingredient_id_foreign FOREIGN KEY (ingredient_id) REFERENCES ingredients (id) ON DELETE CASCADE
);

-- inventory_movements extras
SET @ddl := (
    SELECT IF(
        EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'inventory_movements' AND COLUMN_NAME = 'reason'),
        'SELECT 1',
        'ALTER TABLE inventory_movements
            ADD COLUMN reason VARCHAR(32) NULL AFTER type,
            ADD COLUMN user_id BIGINT UNSIGNED NULL AFTER reference_id,
            ADD COLUMN pos_order_id BIGINT UNSIGNED NULL AFTER user_id'
    )
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Demo only (fancy-coffee): example ingredients + BOM — does not overwrite existing rows
INSERT INTO ingredients (tenant_id, branch_id, name, name_en, name_ar, name_bn, unit, quantity_on_hand, reorder_level, low_stock_threshold, is_active, created_at, updated_at)
SELECT t.id, NULL, 'Espresso beans', 'Espresso beans', 'حبوب إسبريسو', 'এসপ্রেসো বিন', 'g', 5000, 500, 800, 1, NOW(), NOW()
FROM tenants t
WHERE t.slug = 'fancy-coffee'
  AND NOT EXISTS (
    SELECT 1 FROM ingredients i WHERE i.tenant_id = t.id AND i.branch_id IS NULL AND i.name_en = 'Espresso beans'
  );

INSERT INTO ingredients (tenant_id, branch_id, name, name_en, name_ar, name_bn, unit, quantity_on_hand, reorder_level, low_stock_threshold, is_active, created_at, updated_at)
SELECT t.id, NULL, 'Milk', 'Milk', 'حليب', 'দুধ', 'ml', 8000, 1000, 1500, 1, NOW(), NOW()
FROM tenants t
WHERE t.slug = 'fancy-coffee'
  AND NOT EXISTS (
    SELECT 1 FROM ingredients i WHERE i.tenant_id = t.id AND i.branch_id IS NULL AND i.name_en = 'Milk'
  );

INSERT INTO ingredients (tenant_id, branch_id, name, name_en, name_ar, name_bn, unit, quantity_on_hand, reorder_level, low_stock_threshold, is_active, created_at, updated_at)
SELECT t.id, NULL, 'Paper cups', 'Paper cups', 'أكواب ورقية', 'কাগজের কাপ', 'pcs', 400, 50, 80, 1, NOW(), NOW()
FROM tenants t
WHERE t.slug = 'fancy-coffee'
  AND NOT EXISTS (
    SELECT 1 FROM ingredients i WHERE i.tenant_id = t.id AND i.branch_id IS NULL AND i.name_en = 'Paper cups'
  );

INSERT INTO product_ingredients (product_id, ingredient_id, quantity_per_unit, created_at, updated_at)
SELECT p.id, i.id, 18, NOW(), NOW()
FROM tenants t
JOIN products p ON p.tenant_id = t.id AND p.sku = 'LATTE'
JOIN ingredients i ON i.tenant_id = t.id AND i.name_en = 'Espresso beans' AND i.branch_id IS NULL
WHERE t.slug = 'fancy-coffee'
  AND NOT EXISTS (
    SELECT 1 FROM product_ingredients pi WHERE pi.product_id = p.id AND pi.ingredient_id = i.id
  );

INSERT INTO product_ingredients (product_id, ingredient_id, quantity_per_unit, created_at, updated_at)
SELECT p.id, i.id, 250, NOW(), NOW()
FROM tenants t
JOIN products p ON p.tenant_id = t.id AND p.sku = 'LATTE'
JOIN ingredients i ON i.tenant_id = t.id AND i.name_en = 'Milk' AND i.branch_id IS NULL
WHERE t.slug = 'fancy-coffee'
  AND NOT EXISTS (
    SELECT 1 FROM product_ingredients pi WHERE pi.product_id = p.id AND pi.ingredient_id = i.id
  );

INSERT INTO product_ingredients (product_id, ingredient_id, quantity_per_unit, created_at, updated_at)
SELECT p.id, i.id, 1, NOW(), NOW()
FROM tenants t
JOIN products p ON p.tenant_id = t.id AND p.sku = 'LATTE'
JOIN ingredients i ON i.tenant_id = t.id AND i.name_en = 'Paper cups' AND i.branch_id IS NULL
WHERE t.slug = 'fancy-coffee'
  AND NOT EXISTS (
    SELECT 1 FROM product_ingredients pi WHERE pi.product_id = p.id AND pi.ingredient_id = i.id
  );

INSERT INTO product_ingredients (product_id, ingredient_id, quantity_per_unit, created_at, updated_at)
SELECT p.id, i.id, 12, NOW(), NOW()
FROM tenants t
JOIN products p ON p.tenant_id = t.id AND p.sku = 'ESP'
JOIN ingredients i ON i.tenant_id = t.id AND i.name_en = 'Espresso beans' AND i.branch_id IS NULL
WHERE t.slug = 'fancy-coffee'
  AND NOT EXISTS (
    SELECT 1 FROM product_ingredients pi WHERE pi.product_id = p.id AND pi.ingredient_id = i.id
  );

INSERT INTO migrations (migration, batch)
SELECT '2026_11_12_140000_ingredient_inventory_enhancements', b.max_batch + 1
FROM (SELECT COALESCE(MAX(batch), 0) AS max_batch FROM migrations) AS b
WHERE NOT EXISTS (
    SELECT 1 FROM migrations WHERE migration = '2026_11_12_140000_ingredient_inventory_enhancements'
);
