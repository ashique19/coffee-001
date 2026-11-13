-- Idempotent combined production release patch (MySQL 8+ / MariaDB 10.4+)
-- Run once in phpMyAdmin after `git pull` when you cannot rely on `php artisan migrate`.
-- Order: fancy-coffee menu → ingredient inventory → demo-plumber tenant → RBAC → migrations ledger.
-- Safe to re-run. Touches fancy-coffee catalog data, global schema/permissions, and demo-plumber slug only.
--
-- Sources: docs/sql/2026-11-12-fancy-coffee-menu-restore.sql,
--          docs/sql/2026-11-12-ingredient-inventory.sql,
--          docs/sql/2026-11-11-demo-plumber-tenant.sql
-- PRs: #91 menu, #89 inventory, #75 demo-plumber tenant, #74 reconcile (app-only), #69 branches (app-only)

-- ═══ 1. Fancy Coffee menu restore ═══

-- Idempotent phpMyAdmin patch (MySQL 8+ / MariaDB 10.4+)
-- Restores fancy-coffee storefront catalog (8 menu items) for post–#87 catalog-driven café pages.
-- Inserts missing rows only; never deletes. Does not overwrite existing name_en/name_ar/price_ex_vat.
-- Fills NULL i18n, description_*, image_url; sets is_active=1, show_on_menu=1; homepage flags for 6 signatures.
-- Safe to re-run. Touches only tenant slug fancy-coffee (not demo-plumber).

SET @db := DATABASE();
SET @tenant_slug := 'fancy-coffee';
SET @tenant_id := (SELECT id FROM tenants WHERE slug = @tenant_slug LIMIT 1);

SET @ddl := (
    SELECT IF(
        EXISTS(
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'products' AND COLUMN_NAME = 'show_on_homepage'
        ),
        'SELECT 1',
        'ALTER TABLE products ADD COLUMN show_on_homepage TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active'
    )
);
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @ddl := (
    SELECT IF(
        EXISTS(
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'products' AND COLUMN_NAME = 'show_on_menu'
        ),
        'SELECT 1',
        'ALTER TABLE products ADD COLUMN show_on_menu TINYINT(1) NOT NULL DEFAULT 1 AFTER show_on_homepage'
    )
);
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @ddl := (
    SELECT IF(
        EXISTS(
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'product_categories' AND COLUMN_NAME = 'name_bn'
        ),
        'SELECT 1',
        'ALTER TABLE product_categories
            ADD COLUMN name_bn VARCHAR(120) NULL AFTER name_ar,
            ADD COLUMN description_en TEXT NULL AFTER name_bn,
            ADD COLUMN description_ar TEXT NULL AFTER description_en,
            ADD COLUMN description_bn TEXT NULL AFTER description_ar'
    )
);
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @ddl := (
    SELECT IF(
        EXISTS(
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'products' AND COLUMN_NAME = 'name_bn'
        ),
        'SELECT 1',
        'ALTER TABLE products
            ADD COLUMN name_bn VARCHAR(160) NULL AFTER name_ar,
            ADD COLUMN description_en TEXT NULL AFTER name_bn,
            ADD COLUMN description_ar TEXT NULL AFTER description_en,
            ADD COLUMN description_bn TEXT NULL AFTER description_ar'
    )
);
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @ddl := (
    SELECT IF(
        EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'products' AND COLUMN_NAME = 'item_type'),
        'SELECT 1',
        'ALTER TABLE products ADD COLUMN item_type VARCHAR(16) NOT NULL DEFAULT ''item'' AFTER sku'
    )
);
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Categories (3)

INSERT INTO product_categories (tenant_id, name_en, name_ar, name_bn, description_en, description_ar, description_bn, icon_key, sort_order, is_active, created_at, updated_at)
SELECT @tenant_id, 'Hot drinks', 'مشروبات ساخنة', 'গরম পানীয়',
       'Espresso-based drinks served hot.', 'مشروبات على أساس الإسبريسو تُقدّم ساخنة.', 'গরম এসপ্রেসো ভিত্তিক পানীয়।',
       'cup', 10, 1, NOW(), NOW()
FROM DUAL
WHERE @tenant_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Hot drinks');

INSERT INTO product_categories (tenant_id, name_en, name_ar, name_bn, description_en, description_ar, description_bn, icon_key, sort_order, is_active, created_at, updated_at)
SELECT @tenant_id, 'Cold drinks', 'مشروبات باردة', 'ঠান্ডা পানীয়',
       'Iced and slow-brewed cold coffee.', 'قهوة باردة ومشروبات مثلجة.', 'আইসড ও ধীর ভিজানো ঠান্ডা কফি।',
       'cup', 20, 1, NOW(), NOW()
FROM DUAL
WHERE @tenant_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Cold drinks');

INSERT INTO product_categories (tenant_id, name_en, name_ar, name_bn, description_en, description_ar, description_bn, icon_key, sort_order, is_active, created_at, updated_at)
SELECT @tenant_id, 'Pastries & bites', 'مخبوزات ووجبات خفيفة', 'পেস্ট্রি ও স্ন্যাকস',
       'Fresh pastries and savoury bites.', 'مخبوزات طازجة ووجبات خفيفة.', 'তাজা পেস্ট্রি ও হালকা স্ন্যাকস।',
       'cookie', 30, 1, NOW(), NOW()
FROM DUAL
WHERE @tenant_id IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Pastries & bites');

UPDATE product_categories pc
SET
    pc.name_bn = COALESCE(pc.name_bn, 'গরম পানীয়'),
    pc.description_en = COALESCE(pc.description_en, 'Espresso-based drinks served hot.'),
    pc.description_ar = COALESCE(pc.description_ar, 'مشروبات على أساس الإسبريسو تُقدّم ساخنة.'),
    pc.description_bn = COALESCE(pc.description_bn, 'গরম এসপ্রেসো ভিত্তিক পানীয়।'),
    pc.icon_key = COALESCE(pc.icon_key, 'cup'),
    pc.is_active = 1
WHERE pc.tenant_id = @tenant_id AND pc.name_en = 'Hot drinks';

UPDATE product_categories pc
SET
    pc.name_bn = COALESCE(pc.name_bn, 'ঠান্ডা পানীয়'),
    pc.description_en = COALESCE(pc.description_en, 'Iced and slow-brewed cold coffee.'),
    pc.description_ar = COALESCE(pc.description_ar, 'قهوة باردة ومشروبات مثلجة.'),
    pc.description_bn = COALESCE(pc.description_bn, 'আইসড ও ধীর ভিজানো ঠান্ডা কফি।'),
    pc.icon_key = COALESCE(pc.icon_key, 'cup'),
    pc.is_active = 1
WHERE pc.tenant_id = @tenant_id AND pc.name_en = 'Cold drinks';

UPDATE product_categories pc
SET
    pc.name_bn = COALESCE(pc.name_bn, 'পেস্ট্রি ও স্ন্যাকস'),
    pc.description_en = COALESCE(pc.description_en, 'Fresh pastries and savoury bites.'),
    pc.description_ar = COALESCE(pc.description_ar, 'مخبوزات طازجة ووجبات خفيفة.'),
    pc.description_bn = COALESCE(pc.description_bn, 'তাজা পেস্ট্রি ও হালকা স্ন্যাকস।'),
    pc.icon_key = COALESCE(pc.icon_key, 'cookie'),
    pc.is_active = 1
WHERE pc.tenant_id = @tenant_id AND pc.name_en = 'Pastries & bites';

-- Products: INSERT when missing (8 SKUs)

INSERT INTO products (tenant_id, product_category_id, sku, item_type, name_en, name_ar, name_bn, description_en, description_ar, description_bn, price_ex_vat, vat_rate, image_url, sort_order, is_active, show_on_homepage, show_on_menu, created_at, updated_at)
SELECT @tenant_id,
       (SELECT id FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Hot drinks' LIMIT 1),
       'ESP', 'item', 'Espresso', 'إسبريسو', 'এসপ্রেসো',
       'Double shot, medium roast.', 'جرعتان، تحميص متوسط.', 'ডাবল শট, মিডিয়াম রোস্ট।',
       10.43, 15.00, 'images/fancy-coffee/menu/espresso.webp', 10, 1, 1, 1, NOW(), NOW()
FROM DUAL
WHERE @tenant_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM products WHERE tenant_id = @tenant_id AND sku = 'ESP');

INSERT INTO products (tenant_id, product_category_id, sku, item_type, name_en, name_ar, name_bn, description_en, description_ar, description_bn, price_ex_vat, vat_rate, image_url, sort_order, is_active, show_on_homepage, show_on_menu, created_at, updated_at)
SELECT @tenant_id,
       (SELECT id FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Hot drinks' LIMIT 1),
       'FLAT', 'item', 'Flat white', 'فلات وايت', 'ফ্ল্যাট হোয়াইট',
       'Velvety microfoam.', 'رغوة حريرية.', 'মসৃণ মাইক্রোফোম।',
       15.65, 15.00, 'images/fancy-coffee/menu/flat-white.webp', 20, 1, 1, 1, NOW(), NOW()
FROM DUAL
WHERE @tenant_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM products WHERE tenant_id = @tenant_id AND sku = 'FLAT');

INSERT INTO products (tenant_id, product_category_id, sku, item_type, name_en, name_ar, name_bn, description_en, description_ar, description_bn, price_ex_vat, vat_rate, image_url, sort_order, is_active, show_on_homepage, show_on_menu, created_at, updated_at)
SELECT @tenant_id,
       (SELECT id FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Hot drinks' LIMIT 1),
       'LATTE', 'item', 'Cardamom latte', 'لاتيه هيل', 'এলাচ ল্যাটে',
       'With lightly crushed Saudi cardamom.', 'بهيل سعودي مطحون.', 'হালকা ভাঙা সৌদি এলাচের সাথে।',
       19.13, 15.00, 'images/fancy-coffee/menu/cardamom-latte.webp', 30, 1, 1, 1, NOW(), NOW()
FROM DUAL
WHERE @tenant_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM products WHERE tenant_id = @tenant_id AND sku = 'LATTE');

INSERT INTO products (tenant_id, product_category_id, sku, item_type, name_en, name_ar, name_bn, description_en, description_ar, description_bn, price_ex_vat, vat_rate, image_url, sort_order, is_active, show_on_homepage, show_on_menu, created_at, updated_at)
SELECT @tenant_id,
       (SELECT id FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Hot drinks' LIMIT 1),
       'ARABIC', 'item', 'Arabic coffee (Gahwa)', 'قهوة عربية', 'আরবি কফি (গাহওয়া)',
       'Lightly spiced, served with dates.', 'بهارات خفيفة مع تمر.', 'হালকা মশলা, খেজুরসহ পরিবেশন।',
       13.04, 15.00, 'images/fancy-coffee/menu/arabic-coffee.webp', 40, 1, 1, 1, NOW(), NOW()
FROM DUAL
WHERE @tenant_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM products WHERE tenant_id = @tenant_id AND sku = 'ARABIC');

INSERT INTO products (tenant_id, product_category_id, sku, item_type, name_en, name_ar, name_bn, description_en, description_ar, description_bn, price_ex_vat, vat_rate, image_url, sort_order, is_active, show_on_homepage, show_on_menu, created_at, updated_at)
SELECT @tenant_id,
       (SELECT id FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Cold drinks' LIMIT 1),
       'ICED', 'item', 'Iced latte', 'آيس لاتيه', 'আইসড ল্যাটে',
       'Chilled milk over espresso.', 'حليب بارد مع إسبريسو.', 'ঠান্ডা দুধের উপর এসপ্রেসো।',
       17.39, 15.00, 'images/fancy-coffee/menu/iced-latte.webp', 50, 1, 1, 1, NOW(), NOW()
FROM DUAL
WHERE @tenant_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM products WHERE tenant_id = @tenant_id AND sku = 'ICED');

INSERT INTO products (tenant_id, product_category_id, sku, item_type, name_en, name_ar, name_bn, description_en, description_ar, description_bn, price_ex_vat, vat_rate, image_url, sort_order, is_active, show_on_homepage, show_on_menu, created_at, updated_at)
SELECT @tenant_id,
       (SELECT id FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Cold drinks' LIMIT 1),
       'COLD', 'item', 'Cold brew', 'كولد برو', 'কোল্ড ব্রু',
       '18-hour slow steep.', 'تخمير بطيء لمدة ١٨ ساعة.', '১৮ ঘণ্টার ধীর ভিজানো কফি।',
       16.52, 15.00, 'images/fancy-coffee/menu/cold-brew.webp', 60, 1, 1, 1, NOW(), NOW()
FROM DUAL
WHERE @tenant_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM products WHERE tenant_id = @tenant_id AND sku = 'COLD');

INSERT INTO products (tenant_id, product_category_id, sku, item_type, name_en, name_ar, name_bn, description_en, description_ar, description_bn, price_ex_vat, vat_rate, image_url, sort_order, is_active, show_on_homepage, show_on_menu, created_at, updated_at)
SELECT @tenant_id,
       (SELECT id FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Pastries & bites' LIMIT 1),
       'MAAMOUL', 'item', 'Date maamoul', 'معمول تمر', 'খেজুরের মামুল',
       'Buttery cookie with Ajwa dates.', 'مع تمر عجوة.', 'আজওয়া খেজুরে মাখনযুক্ত কুকি।',
       12.17, 15.00, 'images/fancy-coffee/menu/date-maamoul.webp', 70, 1, 0, 1, NOW(), NOW()
FROM DUAL
WHERE @tenant_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM products WHERE tenant_id = @tenant_id AND sku = 'MAAMOUL');

INSERT INTO products (tenant_id, product_category_id, sku, item_type, name_en, name_ar, name_bn, description_en, description_ar, description_bn, price_ex_vat, vat_rate, image_url, sort_order, is_active, show_on_homepage, show_on_menu, created_at, updated_at)
SELECT @tenant_id,
       (SELECT id FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Pastries & bites' LIMIT 1),
       'SAMBUSA', 'item', 'Cheese sambusa', 'سمبوسة جبن', 'পনিরের সামুসা',
       'Crisp pastry, three pieces.', 'ثلاث قطع مقرمشة.', 'মচমচে পেস্ট্রি, তিনটি পিস।',
       13.91, 15.00, 'images/fancy-coffee/menu/cheese-sambusa.webp', 80, 1, 0, 1, NOW(), NOW()
FROM DUAL
WHERE @tenant_id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM products WHERE tenant_id = @tenant_id AND sku = 'SAMBUSA');

-- Existing rows: fill NULLs, visibility; do not change price or primary names

UPDATE products p
SET
    p.product_category_id = COALESCE(p.product_category_id, (SELECT id FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Hot drinks' LIMIT 1)),
    p.name_bn = COALESCE(p.name_bn, 'এসপ্রেসো'),
    p.description_en = COALESCE(p.description_en, 'Double shot, medium roast.'),
    p.description_ar = COALESCE(p.description_ar, 'جرعتان، تحميص متوسط.'),
    p.description_bn = COALESCE(p.description_bn, 'ডাবল শট, মিডিয়াম রোস্ট।'),
    p.image_url = COALESCE(p.image_url, 'images/fancy-coffee/menu/espresso.webp'),
    p.is_active = 1,
    p.show_on_menu = 1,
    p.show_on_homepage = 1
WHERE p.tenant_id = @tenant_id AND p.sku = 'ESP';

UPDATE products p
SET
    p.product_category_id = COALESCE(p.product_category_id, (SELECT id FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Hot drinks' LIMIT 1)),
    p.name_bn = COALESCE(p.name_bn, 'ফ্ল্যাট হোয়াইট'),
    p.description_en = COALESCE(p.description_en, 'Velvety microfoam.'),
    p.description_ar = COALESCE(p.description_ar, 'رغوة حريرية.'),
    p.description_bn = COALESCE(p.description_bn, 'মসৃণ মাইক্রোফোম।'),
    p.image_url = COALESCE(p.image_url, 'images/fancy-coffee/menu/flat-white.webp'),
    p.is_active = 1,
    p.show_on_menu = 1,
    p.show_on_homepage = 1
WHERE p.tenant_id = @tenant_id AND p.sku = 'FLAT';

UPDATE products p
SET
    p.product_category_id = COALESCE(p.product_category_id, (SELECT id FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Hot drinks' LIMIT 1)),
    p.name_bn = COALESCE(p.name_bn, 'এলাচ ল্যাটে'),
    p.description_en = COALESCE(p.description_en, 'With lightly crushed Saudi cardamom.'),
    p.description_ar = COALESCE(p.description_ar, 'بهيل سعودي مطحون.'),
    p.description_bn = COALESCE(p.description_bn, 'হালকা ভাঙা সৌদি এলাচের সাথে।'),
    p.image_url = COALESCE(p.image_url, 'images/fancy-coffee/menu/cardamom-latte.webp'),
    p.is_active = 1,
    p.show_on_menu = 1,
    p.show_on_homepage = 1
WHERE p.tenant_id = @tenant_id AND p.sku = 'LATTE';

UPDATE products p
SET
    p.product_category_id = COALESCE(p.product_category_id, (SELECT id FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Hot drinks' LIMIT 1)),
    p.name_bn = COALESCE(p.name_bn, 'আরবি কফি (গাহওয়া)'),
    p.description_en = COALESCE(p.description_en, 'Lightly spiced, served with dates.'),
    p.description_ar = COALESCE(p.description_ar, 'بهارات خفيفة مع تمر.'),
    p.description_bn = COALESCE(p.description_bn, 'হালকা মশলা, খেজুরসহ পরিবেশন।'),
    p.image_url = COALESCE(p.image_url, 'images/fancy-coffee/menu/arabic-coffee.webp'),
    p.is_active = 1,
    p.show_on_menu = 1,
    p.show_on_homepage = 1
WHERE p.tenant_id = @tenant_id AND p.sku = 'ARABIC';

UPDATE products p
SET
    p.product_category_id = COALESCE(p.product_category_id, (SELECT id FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Cold drinks' LIMIT 1)),
    p.name_bn = COALESCE(p.name_bn, 'আইসড ল্যাটে'),
    p.description_en = COALESCE(p.description_en, 'Chilled milk over espresso.'),
    p.description_ar = COALESCE(p.description_ar, 'حليب بارد مع إسبريسو.'),
    p.description_bn = COALESCE(p.description_bn, 'ঠান্ডা দুধের উপর এসপ্রেসো।'),
    p.image_url = COALESCE(p.image_url, 'images/fancy-coffee/menu/iced-latte.webp'),
    p.is_active = 1,
    p.show_on_menu = 1,
    p.show_on_homepage = 1
WHERE p.tenant_id = @tenant_id AND p.sku = 'ICED';

UPDATE products p
SET
    p.product_category_id = COALESCE(p.product_category_id, (SELECT id FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Cold drinks' LIMIT 1)),
    p.name_bn = COALESCE(p.name_bn, 'কোল্ড ব্রু'),
    p.description_en = COALESCE(p.description_en, '18-hour slow steep.'),
    p.description_ar = COALESCE(p.description_ar, 'تخمير بطيء لمدة ١٨ ساعة.'),
    p.description_bn = COALESCE(p.description_bn, '১৮ ঘণ্টার ধীর ভিজানো কফি।'),
    p.image_url = COALESCE(p.image_url, 'images/fancy-coffee/menu/cold-brew.webp'),
    p.is_active = 1,
    p.show_on_menu = 1,
    p.show_on_homepage = 1
WHERE p.tenant_id = @tenant_id AND p.sku = 'COLD';

UPDATE products p
SET
    p.product_category_id = COALESCE(p.product_category_id, (SELECT id FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Pastries & bites' LIMIT 1)),
    p.name_bn = COALESCE(p.name_bn, 'খেজুরের মামুল'),
    p.description_en = COALESCE(p.description_en, 'Buttery cookie with Ajwa dates.'),
    p.description_ar = COALESCE(p.description_ar, 'مع تمر عجوة.'),
    p.description_bn = COALESCE(p.description_bn, 'আজওয়া খেজুরে মাখনযুক্ত কুকি।'),
    p.image_url = COALESCE(p.image_url, 'images/fancy-coffee/menu/date-maamoul.webp'),
    p.is_active = 1,
    p.show_on_menu = 1
WHERE p.tenant_id = @tenant_id AND p.sku = 'MAAMOUL';

UPDATE products p
SET
    p.product_category_id = COALESCE(p.product_category_id, (SELECT id FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Pastries & bites' LIMIT 1)),
    p.name_bn = COALESCE(p.name_bn, 'পনিরের সামুসা'),
    p.description_en = COALESCE(p.description_en, 'Crisp pastry, three pieces.'),
    p.description_ar = COALESCE(p.description_ar, 'ثلاث قطع مقرمشة.'),
    p.description_bn = COALESCE(p.description_bn, 'মচমচে পেস্ট্রি, তিনটি পিস।'),
    p.image_url = COALESCE(p.image_url, 'images/fancy-coffee/menu/cheese-sambusa.webp'),
    p.is_active = 1,
    p.show_on_menu = 1
WHERE p.tenant_id = @tenant_id AND p.sku = 'SAMBUSA';

-- ── Product storefront indexes (2026_11_12_120000) ─────────────────────────────
SET @ddl := (
    SELECT IF(
        EXISTS(
            SELECT 1 FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'products' AND INDEX_NAME = 'products_tenant_id_show_on_homepage_is_active_index'
        ),
        'SELECT 1',
        'ALTER TABLE products ADD INDEX products_tenant_id_show_on_homepage_is_active_index (tenant_id, show_on_homepage, is_active)'
    )
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @ddl := (
    SELECT IF(
        EXISTS(
            SELECT 1 FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'products' AND INDEX_NAME = 'products_tenant_id_show_on_menu_is_active_index'
        ),
        'SELECT 1',
        'ALTER TABLE products ADD INDEX products_tenant_id_show_on_menu_is_active_index (tenant_id, show_on_menu, is_active)'
    )
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ═══ 2. Ingredient inventory (#89) ═══

-- Idempotent ingredient inventory patch (MySQL 8+ / MariaDB 10.4+)

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


-- ═══ 3. Demo-plumber greenfield tenant (#75) ═══

-- Idempotent patch for cPanel / phpMyAdmin (MySQL 8+ / MariaDB 10.4+)
-- Seeds the demo-plumber tenant (Demo Plumbing Co). Safe to re-run.
-- Does not modify fancy-coffee or any other tenant slug.
-- Demo accounts use the same bcrypt convention as docs/DEPLOY-TEST.md (hash only below).

SET @demo_slug := 'demo-plumber';
SET @demo_password_hash := '$2y$10$rU1Juu0yLx1GarXjGNEgMuHi0PG0ZSLdmUZUWKacf4TOLnCSZk9F.';
SET @egs_uuid := '00000000-0000-4000-8000-000000000010';

-- Optional DDL when migrations were not run via artisan (git pull deploy)
SET @db := DATABASE();
SET @ddl := (
    SELECT IF(
        EXISTS(
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'product_categories' AND COLUMN_NAME = 'icon_key'
        ),
        'SELECT 1',
        'ALTER TABLE product_categories ADD COLUMN icon_key VARCHAR(32) NULL AFTER name_ar'
    )
);
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ── Tenant (upsert by unique slug only) ───────────────────────────────────────
INSERT INTO tenants (slug, name, settings, created_at, updated_at)
VALUES (@demo_slug, 'Demo Plumbing Co', '{"theme":{"layout_key":"plumber","logo_url":null,"colors":{"navy":"#0B1F3A","steel":"#2D4A6F","sky":"#E8F1FA","ice":"#F4F8FC","safety":"#E87722","accent":"#1B8A8A","slate":"#475569"},"font":"Instrument Sans","font_ar":"Tajawal"},"default_locale":"en","legal_entity":{"name_en":"Demo Plumbing Co","name_ar":"شركة السباكة التجريبية","vat_number":"311122393500004","cr_number":"1010987654","address":"Al Olaya Street, Building 12, Riyadh 12211, Kingdom of Saudi Arabia","address_ar":"شارع العليا، مبنى ١٢، الرياض ١٢٢١١، المملكة العربية السعودية"},"zatca":{"integration_enabled":false,"invoice_type":"1100","solution_unit":"EGS","model_version":"v1","business_category":"Plumbing","default_document_type":"standard"},"content":{"plumber":{"tagline_en":"Licensed plumbing & water systems across Riyadh","tagline_ar":"سباكة مرخّصة وأنظمة مياه في الرياض","about_en":"Demo Plumbing Co provides residential and light commercial plumbing — leaks, heaters, drain clearing, and preventative maintenance. Our technicians arrive on time with clear quotes before work begins.","about_ar":"تقدّم شركة السباكة التجريبية خدمات سباكة للمنازل والمنشآت الصغيرة — تسريبات، سخانات، تسليك مجاري وصيانة وقائية. فنّيونا يلتزمون بالمواعيد مع عرض سعر واضح قبل البدء.","hours":[{"label_en":"Sat – Thu","label_ar":"السبت – الخميس","value_en":"8:00 AM – 9:00 PM","value_ar":"٨:٠٠ ص – ٩:٠٠ م"},{"label_en":"Friday","label_ar":"الجمعة","value_en":"2:00 PM – 9:00 PM","value_ar":"٢:٠٠ م – ٩:٠٠ م"},{"label_en":"Emergency line","label_ar":"طوارئ","value_en":"24/7 dispatch","value_ar":"استقبال على مدار الساعة"}],"location":{"map_placeholder":true,"lat":24.7136,"lng":46.6753},"contact":{"phone":"+966 11 200 4472","whatsapp":"+966500000447","email":"service@demoplumbingco.sa"},"trust_points":[{"icon":"shield","title_en":"Licensed technicians","title_ar":"فنيون مرخّصون","body_en":"Municipal-compliant workmanship with photo documentation.","body_ar":"تنفيذ متوافق مع الأنظمة مع توثيق بالصور."},{"icon":"clock","title_en":"Same-day visits","title_ar":"زيارات في نفس اليوم","body_en":"Most jobs scheduled within 4 hours in Riyadh.","body_ar":"معظم الطلبات خلال ٤ ساعات داخل الرياض."},{"icon":"receipt","title_en":"Clear SAR pricing","title_ar":"أسعار واضحة بالريال","body_en":"Itemised quotes; VAT invoices available for businesses.","body_ar":"عروض أسعار مفصّلة وفواتير ضريبية للمنشآت."}],"materials_note_en":"Parts and small materials used on your visit are itemised on the job invoice—not listed as a public hardware catalog.","materials_note_ar":"القطع والمواد الصغيرة المستخدمة في الزيارة تُفصَّل في فاتورة العمل—وليست متجرًا عامًا للتجهيزات.","featured_services":[{"key":"leak-repair","badge_en":"Popular","badge_ar":"الأكثر طلباً"},{"key":"drain-clear","badge_en":"Fast","badge_ar":"سريع"},{"key":"water-heater","badge_en":"Install","badge_ar":"تركيب"}],"service_categories":[{"slug":"emergency","name_en":"Emergency","name_ar":"طوارئ","items":[{"slug":"leak-repair","name_en":"Leak repair visit","name_ar":"زيارة إصلاح تسرب","desc_en":"Diagnose and patch active leaks","desc_ar":"تشخيص ومعالجة التسربات النشطة","price_sar":180},{"slug":"burst-pipe","name_en":"Burst pipe isolation","name_ar":"عزل أنبوب منفجر","desc_en":"Shut-off and temporary repair","desc_ar":"إغلاق المياه وإصلاح مؤقت","price_sar":250}]},{"slug":"maintenance","name_en":"Maintenance","name_ar":"صيانة","items":[{"slug":"drain-clear","name_en":"Drain clearing","name_ar":"تسليك مجاري","desc_en":"Kitchen or bathroom line","desc_ar":"مطبخ أو حمّام","price_sar":140},{"slug":"fixture-service","name_en":"Fixture tune-up","name_ar":"صيانة تجهيزات","desc_en":"Taps, mixers, seals","desc_ar":"محابس ومفاصل وأختام","price_sar":95}]},{"slug":"install","name_en":"Installation","name_ar":"تركيب","items":[{"slug":"water-heater","name_en":"Water heater install","name_ar":"تركيب سخان مياه","desc_en":"Supply and labour, standard unit","desc_ar":"توريد وتركيب وحدة قياسية","price_sar":420},{"slug":"filtration","name_en":"Filtration system","name_ar":"نظام تنقية","desc_en":"Under-sink carbon block","desc_ar":"فلتر تحت الحوض","price_sar":320}]}]}}}', NOW(), NOW())
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    settings = VALUES(settings),
    updated_at = NOW();

SET @tenant_id := (SELECT id FROM tenants WHERE slug = @demo_slug LIMIT 1);

-- ── Tenant-scoped permissions (guard tenant, global rows) ───────────────────────
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'pos.access', 'tenant', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'pos.access' AND guard_name = 'tenant');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'inventory.view', 'tenant', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'inventory.view' AND guard_name = 'tenant');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'invoices.issue', 'tenant', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'invoices.issue' AND guard_name = 'tenant');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'reports.view', 'tenant', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'reports.view' AND guard_name = 'tenant');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'staff.manage', 'tenant', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'staff.manage' AND guard_name = 'tenant');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'zatca.manage', 'tenant', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'zatca.manage' AND guard_name = 'tenant');

-- ── Tenant roles (Spatie teams: tenant_id on roles) ───────────────────────────
INSERT INTO roles (tenant_id, name, guard_name, created_at, updated_at)
SELECT @tenant_id, 'owner', 'tenant', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM roles WHERE tenant_id = @tenant_id AND name = 'owner' AND guard_name = 'tenant'
);

INSERT INTO roles (tenant_id, name, guard_name, created_at, updated_at)
SELECT @tenant_id, 'manager', 'tenant', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM roles WHERE tenant_id = @tenant_id AND name = 'manager' AND guard_name = 'tenant'
);

INSERT INTO roles (tenant_id, name, guard_name, created_at, updated_at)
SELECT @tenant_id, 'cashier', 'tenant', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM roles WHERE tenant_id = @tenant_id AND name = 'cashier' AND guard_name = 'tenant'
);

INSERT INTO roles (tenant_id, name, guard_name, created_at, updated_at)
SELECT @tenant_id, 'accountant', 'tenant', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM roles WHERE tenant_id = @tenant_id AND name = 'accountant' AND guard_name = 'tenant'
);

-- Owner: all tenant permissions
INSERT INTO role_has_permissions (permission_id, role_id)
SELECT p.id, r.id
FROM permissions p
INNER JOIN roles r ON r.tenant_id = @tenant_id AND r.name = 'owner' AND r.guard_name = 'tenant'
WHERE p.guard_name = 'tenant'
  AND NOT EXISTS (SELECT 1 FROM role_has_permissions x WHERE x.permission_id = p.id AND x.role_id = r.id);

-- Manager
INSERT INTO role_has_permissions (permission_id, role_id)
SELECT p.id, r.id
FROM permissions p
INNER JOIN roles r ON r.tenant_id = @tenant_id AND r.name = 'manager' AND r.guard_name = 'tenant'
WHERE p.guard_name = 'tenant' AND p.name IN ('pos.access', 'inventory.view', 'reports.view', 'staff.manage', 'zatca.manage')
  AND NOT EXISTS (SELECT 1 FROM role_has_permissions x WHERE x.permission_id = p.id AND x.role_id = r.id);

-- Cashier
INSERT INTO role_has_permissions (permission_id, role_id)
SELECT p.id, r.id
FROM permissions p
INNER JOIN roles r ON r.tenant_id = @tenant_id AND r.name = 'cashier' AND r.guard_name = 'tenant'
WHERE p.guard_name = 'tenant' AND p.name = 'pos.access'
  AND NOT EXISTS (SELECT 1 FROM role_has_permissions x WHERE x.permission_id = p.id AND x.role_id = r.id);

-- Accountant
INSERT INTO role_has_permissions (permission_id, role_id)
SELECT p.id, r.id
FROM permissions p
INNER JOIN roles r ON r.tenant_id = @tenant_id AND r.name = 'accountant' AND r.guard_name = 'tenant'
WHERE p.guard_name = 'tenant' AND p.name IN ('invoices.issue', 'reports.view', 'zatca.manage')
  AND NOT EXISTS (SELECT 1 FROM role_has_permissions x WHERE x.permission_id = p.id AND x.role_id = r.id);

-- ── Branch ────────────────────────────────────────────────────────────────────
INSERT INTO branches (tenant_id, code, name, name_ar, address, is_active, created_at, updated_at)
SELECT @tenant_id, 'ruh-main', 'Riyadh Service Center', 'مركز الرياض',
       'Al Olaya Street, Building 12, Riyadh 12211, Kingdom of Saudi Arabia', 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM branches WHERE tenant_id = @tenant_id AND code = 'ruh-main');

SET @branch_id := (SELECT id FROM branches WHERE tenant_id = @tenant_id AND code = 'ruh-main' LIMIT 1);

-- ── EGS device ────────────────────────────────────────────────────────────────
INSERT INTO egs_devices (tenant_id, branch_id, uuid, name, last_icv, created_at, updated_at)
SELECT @tenant_id, @branch_id, @egs_uuid, 'EGS Riyadh Demo', 0, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM egs_devices WHERE uuid = @egs_uuid);

UPDATE egs_devices
SET branch_id = @branch_id, name = 'EGS Riyadh Demo'
WHERE uuid = @egs_uuid AND tenant_id = @tenant_id;

-- ── Staff users (password set only on insert) ───────────────────────────────────
INSERT INTO tenant_users (tenant_id, branch_id, name, email, password, is_active, created_at, updated_at)
SELECT @tenant_id, NULL, 'Owner', 'owner@demo-plumber.test', @demo_password_hash, 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM tenant_users WHERE tenant_id = @tenant_id AND email = 'owner@demo-plumber.test'
);

INSERT INTO tenant_users (tenant_id, branch_id, name, email, password, is_active, created_at, updated_at)
SELECT @tenant_id, @branch_id, 'Technician', 'technician@demo-plumber.test', @demo_password_hash, 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM tenant_users WHERE tenant_id = @tenant_id AND email = 'technician@demo-plumber.test'
);

UPDATE tenant_users SET branch_id = @branch_id
WHERE tenant_id = @tenant_id AND email = 'technician@demo-plumber.test' AND branch_id IS NULL;

-- ── Role assignments ──────────────────────────────────────────────────────────
INSERT INTO model_has_roles (tenant_id, role_id, model_id, model_type)
SELECT @tenant_id, r.id, u.id, 'App\\Models\\TenantUser'
FROM tenant_users u
INNER JOIN roles r ON r.tenant_id = @tenant_id AND r.name = 'owner' AND r.guard_name = 'tenant'
WHERE u.tenant_id = @tenant_id AND u.email = 'owner@demo-plumber.test'
  AND NOT EXISTS (
    SELECT 1 FROM model_has_roles m
    WHERE m.tenant_id = @tenant_id AND m.model_type = 'App\\Models\\TenantUser'
      AND m.model_id = u.id AND m.role_id = r.id
  );

INSERT INTO model_has_roles (tenant_id, role_id, model_id, model_type)
SELECT @tenant_id, r.id, u.id, 'App\\Models\\TenantUser'
FROM tenant_users u
INNER JOIN roles r ON r.tenant_id = @tenant_id AND r.name = 'cashier' AND r.guard_name = 'tenant'
WHERE u.tenant_id = @tenant_id AND u.email = 'technician@demo-plumber.test'
  AND NOT EXISTS (
    SELECT 1 FROM model_has_roles m
    WHERE m.tenant_id = @tenant_id AND m.model_type = 'App\\Models\\TenantUser'
      AND m.model_id = u.id AND m.role_id = r.id
  );

-- ── Catalog categories ──────────────────────────────────────────────────────────
INSERT INTO product_categories (tenant_id, name_en, name_ar, icon_key, is_active, created_at, updated_at)
SELECT @tenant_id, 'Emergency', 'طوارئ', 'wrench', 1, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Emergency');

INSERT INTO product_categories (tenant_id, name_en, name_ar, icon_key, is_active, created_at, updated_at)
SELECT @tenant_id, 'Maintenance', 'صيانة', 'wrench', 1, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Maintenance');

INSERT INTO product_categories (tenant_id, name_en, name_ar, icon_key, is_active, created_at, updated_at)
SELECT @tenant_id, 'Installation', 'تركيب', 'wrench', 1, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Installation');

SET @cat_emergency := (SELECT id FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Emergency' LIMIT 1);
SET @cat_maintenance := (SELECT id FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Maintenance' LIMIT 1);
SET @cat_install := (SELECT id FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Installation' LIMIT 1);

-- ── Products (by SKU) ───────────────────────────────────────────────────────────
INSERT INTO products (tenant_id, product_category_id, sku, name_en, name_ar, price_ex_vat, vat_rate, is_active, created_at, updated_at)
SELECT @tenant_id, @cat_emergency, 'LEAK', 'Leak repair visit', 'إصلاح تسرب', 180.00, 15.00, 1, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM products WHERE tenant_id = @tenant_id AND sku = 'LEAK');

INSERT INTO products (tenant_id, product_category_id, sku, name_en, name_ar, price_ex_vat, vat_rate, is_active, created_at, updated_at)
SELECT @tenant_id, @cat_emergency, 'BURST', 'Burst pipe isolation', 'عزل أنبوب', 250.00, 15.00, 1, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM products WHERE tenant_id = @tenant_id AND sku = 'BURST');

INSERT INTO products (tenant_id, product_category_id, sku, name_en, name_ar, price_ex_vat, vat_rate, is_active, created_at, updated_at)
SELECT @tenant_id, @cat_maintenance, 'DRAIN', 'Drain clearing', 'تسليك مجاري', 140.00, 15.00, 1, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM products WHERE tenant_id = @tenant_id AND sku = 'DRAIN');

INSERT INTO products (tenant_id, product_category_id, sku, name_en, name_ar, price_ex_vat, vat_rate, is_active, created_at, updated_at)
SELECT @tenant_id, @cat_maintenance, 'FIX', 'Fixture tune-up', 'صيانة تجهيزات', 95.00, 15.00, 1, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM products WHERE tenant_id = @tenant_id AND sku = 'FIX');

INSERT INTO products (tenant_id, product_category_id, sku, name_en, name_ar, price_ex_vat, vat_rate, is_active, created_at, updated_at)
SELECT @tenant_id, @cat_install, 'HEAT', 'Water heater install', 'تركيب سخان', 420.00, 15.00, 1, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM products WHERE tenant_id = @tenant_id AND sku = 'HEAT');

INSERT INTO products (tenant_id, product_category_id, sku, name_en, name_ar, price_ex_vat, vat_rate, is_active, created_at, updated_at)
SELECT @tenant_id, @cat_install, 'FILT', 'Filtration system', 'نظام تنقية', 320.00, 15.00, 1, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM products WHERE tenant_id = @tenant_id AND sku = 'FILT');

-- ═══ 4. Z-Heart tenant columns ═══

-- Idempotent Z-Heart tenant columns (MySQL 8+ / MariaDB 10.4+)

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


-- ── Tenant RBAC: cashier is POS-only (catalog/inventory admin for owner/manager) ─
DELETE rhp FROM role_has_permissions rhp
INNER JOIN permissions p ON p.id = rhp.permission_id AND p.guard_name = 'tenant' AND p.name IN ('inventory.view', 'inventory.manage')
INNER JOIN roles r ON r.id = rhp.role_id AND r.guard_name = 'tenant' AND r.name = 'cashier';

-- ── Laravel migrations table (idempotent) ────────────────────────────────────────
INSERT INTO migrations (migration, batch)
SELECT '2026_11_11_100000_add_item_type_to_products', b.max_batch + 1
FROM (SELECT COALESCE(MAX(batch), 0) AS max_batch FROM migrations) AS b
WHERE NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '2026_11_11_100000_add_item_type_to_products');

INSERT INTO migrations (migration, batch)
SELECT '2026_11_12_120000_add_product_storefront_visibility_columns', b.max_batch + 1
FROM (SELECT COALESCE(MAX(batch), 0) AS max_batch FROM migrations) AS b
WHERE NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '2026_11_12_120000_add_product_storefront_visibility_columns');

INSERT INTO migrations (migration, batch)
SELECT '2026_11_12_130000_add_catalog_i18n_columns', b.max_batch + 1
FROM (SELECT COALESCE(MAX(batch), 0) AS max_batch FROM migrations) AS b
WHERE NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '2026_11_12_130000_add_catalog_i18n_columns');

INSERT INTO migrations (migration, batch)
SELECT '2026_11_12_140000_ingredient_inventory_enhancements', b.max_batch + 1
FROM (SELECT COALESCE(MAX(batch), 0) AS max_batch FROM migrations) AS b
WHERE NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '2026_11_12_140000_ingredient_inventory_enhancements');

INSERT INTO migrations (migration, batch)
SELECT '2026_11_12_200000_add_zheart_columns_to_tenants', b.max_batch + 1
FROM (SELECT COALESCE(MAX(batch), 0) AS max_batch FROM migrations) AS b
WHERE NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '2026_11_12_200000_add_zheart_columns_to_tenants');
