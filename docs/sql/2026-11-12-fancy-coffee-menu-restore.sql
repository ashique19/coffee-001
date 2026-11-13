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
