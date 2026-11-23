-- Idempotent Fancy Coffee menu + inventory seed (MariaDB 10.4+ / MySQL 8+)
-- Safe to re-run in phpMyAdmin. Does not touch invoices.
--
-- Prerequisites:
--   docs/sql/install.sql (and 2026-11-14-schema-fix.sql if that was needed)
--   docs/sql/2026-11-23-invoice-port-schema.sql (for product_units / products.product_unit_id)
--   public/images/menu/* deployed via git pull (this file only stores relative image paths)
--
-- Scope: the single-client Fancy Coffee database. Identified by business_settings.name
-- or legal_entity.name_en = 'Fancy Coffee' (no tenants table in the current schema;
-- if a leftover tenants.slug = 'fancy-coffee' row exists, that also qualifies).
-- Products are shared (branch_id NULL) so POS at every active branch can see them.
-- VAT 15% is standard-rated category S. Unit is PCS when product_units exists.
--
-- Inc-VAT shelf prices (SAR): Espresso 14, Americano 14, Cappuccino 18, Latte 18,
-- Spanish latte 20, Flat white 18, Iced latte 20, Saudi coffee 15, Croissant 14,
-- Cake slice 16. Stored as price_ex_vat so incFromEx(ex, 15) matches those amounts.

SET NAMES utf8mb4;

SET @is_fancy := (
  SELECT CASE
    WHEN EXISTS (
      SELECT 1 FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'business_settings'
    ) AND EXISTS (
      SELECT 1 FROM business_settings
      WHERE name = 'Fancy Coffee'
         OR settings LIKE '%"name_en": "Fancy Coffee"%'
         OR settings LIKE '%"name_en":"Fancy Coffee"%'
    ) THEN 1
    WHEN EXISTS (
      SELECT 1 FROM information_schema.TABLES
      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tenants'
    ) AND EXISTS (
      SELECT 1 FROM tenants WHERE slug = 'fancy-coffee'
    ) THEN 1
    ELSE 0
  END
);

SET @branch_id := (
  SELECT id FROM branches WHERE code = 'jad-main' LIMIT 1
);
SET @branch_id := IFNULL(
  @branch_id,
  (SELECT id FROM branches WHERE is_active = 1 ORDER BY id LIMIT 1)
);

SET @has_unit_col := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'products'
    AND COLUMN_NAME = 'product_unit_id'
);
SET @unit_pcs := IF(
  @has_unit_col > 0,
  (SELECT id FROM product_units WHERE code = 'PCS' LIMIT 1),
  NULL
);

-- ---------------------------------------------------------------------------
-- Categories
-- ---------------------------------------------------------------------------
INSERT INTO product_categories (name_en, name_ar, name_bn, description_en, description_ar, description_bn, icon_key, sort_order, is_active, created_at, updated_at)
SELECT 'Hot drinks', 'مشروبات ساخنة', 'গরম পানীয়',
       'Espresso-based drinks served hot.', 'مشروبات على أساس الإسبريسو تُقدّم ساخنة.', 'গরম এসপ্রেসো ভিত্তিক পানীয়।',
       'cup', 10, 1, NOW(), NOW()
FROM DUAL
WHERE @is_fancy = 1
  AND NOT EXISTS (SELECT 1 FROM product_categories WHERE name_en = 'Hot drinks');

INSERT INTO product_categories (name_en, name_ar, name_bn, description_en, description_ar, description_bn, icon_key, sort_order, is_active, created_at, updated_at)
SELECT 'Cold drinks', 'مشروبات باردة', 'ঠান্ডা পানীয়',
       'Iced coffee drinks.', 'مشروبات قهوة مثلجة.', 'আইসড কফি পানীয়।',
       'snow', 20, 1, NOW(), NOW()
FROM DUAL
WHERE @is_fancy = 1
  AND NOT EXISTS (SELECT 1 FROM product_categories WHERE name_en = 'Cold drinks');

INSERT INTO product_categories (name_en, name_ar, name_bn, description_en, description_ar, description_bn, icon_key, sort_order, is_active, created_at, updated_at)
SELECT 'Pastries & bites', 'مخبوزات ووجبات خفيفة', 'পেস্ট্রি ও স্ন্যাকস',
       'Fresh pastries and a cake slice.', 'مخبوزات طازجة وشريحة كيك.', 'তাজা পেস্ট্রি ও কেকের স্লাইস।',
       'cookie', 30, 1, NOW(), NOW()
FROM DUAL
WHERE @is_fancy = 1
  AND NOT EXISTS (SELECT 1 FROM product_categories WHERE name_en = 'Pastries & bites');

UPDATE product_categories
SET name_ar = COALESCE(NULLIF(name_ar, ''), 'مشروبات ساخنة'),
    name_bn = COALESCE(NULLIF(name_bn, ''), 'গরম পানীয়'),
    icon_key = COALESCE(NULLIF(icon_key, ''), 'cup'),
    is_active = 1,
    updated_at = NOW()
WHERE @is_fancy = 1 AND name_en = 'Hot drinks';

UPDATE product_categories
SET name_ar = COALESCE(NULLIF(name_ar, ''), 'مشروبات باردة'),
    name_bn = COALESCE(NULLIF(name_bn, ''), 'ঠান্ডা পানীয়'),
    icon_key = COALESCE(NULLIF(icon_key, ''), 'snow'),
    is_active = 1,
    updated_at = NOW()
WHERE @is_fancy = 1 AND name_en = 'Cold drinks';

UPDATE product_categories
SET name_ar = COALESCE(NULLIF(name_ar, ''), 'مخبوزات ووجبات خفيفة'),
    name_bn = COALESCE(NULLIF(name_bn, ''), 'পেস্ট্রি ও স্ন্যাকস'),
    icon_key = COALESCE(NULLIF(icon_key, ''), 'cookie'),
    is_active = 1,
    updated_at = NOW()
WHERE @is_fancy = 1 AND name_en = 'Pastries & bites';

SET @cat_hot := (SELECT id FROM product_categories WHERE name_en = 'Hot drinks' ORDER BY id LIMIT 1);
SET @cat_cold := (SELECT id FROM product_categories WHERE name_en = 'Cold drinks' ORDER BY id LIMIT 1);
SET @cat_bites := (SELECT id FROM product_categories WHERE name_en = 'Pastries & bites' ORDER BY id LIMIT 1);

-- ---------------------------------------------------------------------------
-- Products (upsert by sku). Images are relative to public/.
-- ---------------------------------------------------------------------------
-- sku, cat, sort, homepage, name_en, name_ar, name_bn, desc_en, desc_ar, desc_bn, image, price_ex, show_home

INSERT INTO products (
  sku, product_category_id, item_type, name_en, name_ar, name_bn,
  description_en, description_ar, description_bn, image_url,
  price_ex_vat, vat_rate, sort_order, is_active, show_on_homepage, show_on_menu,
  created_at, updated_at
)
SELECT * FROM (
  SELECT 'FC-ESPRESSO' AS sku, @cat_hot AS cid, 'item' AS typ,
         'Espresso' AS ne, 'إسبريسو' AS na, 'এসপ্রেসো' AS nb,
         'Double shot, medium roast.' AS de, 'جرعتان، تحميص متوسط.' AS da, 'ডাবল শট, মিডিয়াম রোস্ট।' AS db,
         'images/menu/espresso.webp' AS img, 12.17 AS price, 15.00 AS vat, 10 AS so, 1 AS act, 1 AS home, 1 AS menu,
         NOW() AS c, NOW() AS u
  UNION ALL SELECT 'FC-AMERICANO', @cat_hot, 'item', 'Americano', 'أمريكانو', 'আমেরিকানো',
         'Espresso lengthened with hot water.', 'إسبريسو ممدود بماء ساخن.', 'গরম জল দিয়ে লম্বা করা এসপ্রেসো।',
         'images/menu/americano.webp', 12.17, 15.00, 20, 1, 0, 1, NOW(), NOW()
  UNION ALL SELECT 'FC-CAPPUCCINO', @cat_hot, 'item', 'Cappuccino', 'كابتشينو', 'কাপুচিনো',
         'Espresso with steamed milk and foam.', 'إسبريسو مع حليب مبخر ورغوة.', 'এসপ্রেসো, স্টিমড দুধ ও ফোম।',
         'images/menu/cappuccino.webp', 15.65, 15.00, 30, 1, 0, 1, NOW(), NOW()
  UNION ALL SELECT 'FC-LATTE', @cat_hot, 'item', 'Latte', 'لاتيه', 'লাতে',
         'Espresso with a smooth steamed-milk finish.', 'إسبريسو مع حليب مبخر ناعم.', 'এসপ্রেসো ও মসৃণ স্টিমড দুধ।',
         'images/menu/latte.webp', 15.65, 15.00, 40, 1, 1, 1, NOW(), NOW()
  UNION ALL SELECT 'FC-SPANISH-LATTE', @cat_hot, 'item', 'Spanish latte', 'لاتيه إسباني', 'স্প্যানিশ লাতে',
         'Latte sweetened with condensed milk.', 'لاتيه محلى بالحليب المكثف.', 'কনডেন্সড মিল্ক দিয়ে মিষ্টি লাতে।',
         'images/menu/spanish-latte.webp', 17.39, 15.00, 50, 1, 1, 1, NOW(), NOW()
  UNION ALL SELECT 'FC-FLAT-WHITE', @cat_hot, 'item', 'Flat white', 'فلات وايت', 'ফ্ল্যাট হোয়াইট',
         'Velvety microfoam over a double shot.', 'رغوة حريرية فوق جرعتين.', 'ডাবল শটের উপর মসৃণ মাইক্রোফোম।',
         'images/menu/flat-white.webp', 15.65, 15.00, 60, 1, 0, 1, NOW(), NOW()
  UNION ALL SELECT 'FC-ICED-LATTE', @cat_cold, 'item', 'Iced latte', 'آيس لاتيه', 'আইসড লাতে',
         'Chilled milk over espresso and ice.', 'حليب بارد مع إسبريسو وثلج.', 'এসপ্রেসো, ঠান্ডা দুধ ও বরফ।',
         'images/menu/iced-latte.webp', 17.39, 15.00, 70, 1, 0, 1, NOW(), NOW()
  UNION ALL SELECT 'FC-QAHWA', @cat_hot, 'item', 'Saudi coffee (Qahwa)', 'قهوة سعودية (قهوة)', 'সৌদি কাহওয়া',
         'Lightly spiced Arabic coffee, served in a finjan.', 'قهوة عربية خفيفة التوابل تُقدّم في فنجان.', 'হালকা মশলাদার আরবীয় কফি, ফিনজানে পরিবেশিত।',
         'images/menu/saudi-coffee.webp', 13.04, 15.00, 80, 1, 1, 1, NOW(), NOW()
  UNION ALL SELECT 'FC-CROISSANT', @cat_bites, 'item', 'Croissant', 'كرواسون', 'ক্রোয়াসাঁ',
         'Butter croissant, baked fresh.', 'كرواسون بالزبدة، طازج من الفرن.', 'মাখনের ক্রোয়াসাঁ, তাজা সেঁকা।',
         'images/menu/croissant.webp', 12.17, 15.00, 90, 1, 0, 1, NOW(), NOW()
  UNION ALL SELECT 'FC-CAKE-SLICE', @cat_bites, 'item', 'Cake slice', 'شريحة كيك', 'কেকের স্লাইস',
         'A slice of the day’s café cake.', 'شريحة من كيك اليوم.', 'আজকের ক্যাফে কেকের একটি স্লাইস।',
         'images/menu/cake-slice.webp', 13.91, 15.00, 100, 1, 0, 1, NOW(), NOW()
) AS seed
WHERE @is_fancy = 1
  AND NOT EXISTS (SELECT 1 FROM products p WHERE p.sku = seed.sku);

UPDATE products p
JOIN (
  SELECT 'FC-ESPRESSO' AS sku, @cat_hot AS cid, 'Espresso' AS ne, 'إسبريسو' AS na, 'এসপ্রেসো' AS nb,
         'Double shot, medium roast.' AS de, 'جرعتان، تحميص متوسط.' AS da, 'ডাবল শট, মিডিয়াম রোস্ট।' AS db,
         'images/menu/espresso.webp' AS img, 12.17 AS price, 10 AS so, 1 AS home
  UNION ALL SELECT 'FC-AMERICANO', @cat_hot, 'Americano', 'أمريكانو', 'আমেরিকানো',
         'Espresso lengthened with hot water.', 'إسبريسو ممدود بماء ساخن.', 'গরম জল দিয়ে লম্বা করা এসপ্রেসো।',
         'images/menu/americano.webp', 12.17, 20, 0
  UNION ALL SELECT 'FC-CAPPUCCINO', @cat_hot, 'Cappuccino', 'كابتشينو', 'কাপুচিনো',
         'Espresso with steamed milk and foam.', 'إسبريسو مع حليب مبخر ورغوة.', 'এসপ্রেসো, স্টিমড দুধ ও ফোম।',
         'images/menu/cappuccino.webp', 15.65, 30, 0
  UNION ALL SELECT 'FC-LATTE', @cat_hot, 'Latte', 'لاتيه', 'লাতে',
         'Espresso with a smooth steamed-milk finish.', 'إسبريسو مع حليب مبخر ناعم.', 'এসপ্রেসো ও মসৃণ স্টিমড দুধ।',
         'images/menu/latte.webp', 15.65, 40, 1
  UNION ALL SELECT 'FC-SPANISH-LATTE', @cat_hot, 'Spanish latte', 'لاتيه إسباني', 'স্প্যানিশ লাতে',
         'Latte sweetened with condensed milk.', 'لاتيه محلى بالحليب المكثف.', 'কনডেন্সড মিল্ক দিয়ে মিষ্টি লাতে।',
         'images/menu/spanish-latte.webp', 17.39, 50, 1
  UNION ALL SELECT 'FC-FLAT-WHITE', @cat_hot, 'Flat white', 'فلات وايت', 'ফ্ল্যাট হোয়াইট',
         'Velvety microfoam over a double shot.', 'رغوة حريرية فوق جرعتين.', 'ডাবল শটের উপর মসৃণ মাইক্রোফোম।',
         'images/menu/flat-white.webp', 15.65, 60, 0
  UNION ALL SELECT 'FC-ICED-LATTE', @cat_cold, 'Iced latte', 'آيس لاتيه', 'আইসড লাতে',
         'Chilled milk over espresso and ice.', 'حليب بارد مع إسبريسو وثلج.', 'এসপ্রেসো, ঠান্ডা দুধ ও বরফ।',
         'images/menu/iced-latte.webp', 17.39, 70, 0
  UNION ALL SELECT 'FC-QAHWA', @cat_hot, 'Saudi coffee (Qahwa)', 'قهوة سعودية (قهوة)', 'সৌদি কাহওয়া',
         'Lightly spiced Arabic coffee, served in a finjan.', 'قهوة عربية خفيفة التوابل تُقدّم في فنجان.', 'হালকা মশলাদার আরবীয় কফি, ফিনজানে পরিবেশিত।',
         'images/menu/saudi-coffee.webp', 13.04, 80, 1
  UNION ALL SELECT 'FC-CROISSANT', @cat_bites, 'Croissant', 'كرواسون', 'ক্রোয়াসাঁ',
         'Butter croissant, baked fresh.', 'كرواسون بالزبدة، طازج من الفرن.', 'মাখনের ক্রোয়াসাঁ, তাজা সেঁকা।',
         'images/menu/croissant.webp', 12.17, 90, 0
  UNION ALL SELECT 'FC-CAKE-SLICE', @cat_bites, 'Cake slice', 'شريحة كيك', 'কেকের স্লাইস',
         'A slice of the day’s café cake.', 'شريحة من كيك اليوم.', 'আজকের ক্যাফে কেকের একটি স্লাইস।',
         'images/menu/cake-slice.webp', 13.91, 100, 0
) AS seed ON p.sku = seed.sku
SET
  p.product_category_id = seed.cid,
  p.item_type = 'item',
  p.name_en = seed.ne,
  p.name_ar = seed.na,
  p.name_bn = seed.nb,
  p.description_en = seed.de,
  p.description_ar = seed.da,
  p.description_bn = seed.db,
  p.image_url = seed.img,
  p.price_ex_vat = seed.price,
  p.vat_rate = 15.00,
  p.sort_order = seed.so,
  p.is_active = 1,
  p.show_on_homepage = seed.home,
  p.show_on_menu = 1,
  p.updated_at = NOW()
WHERE @is_fancy = 1;

SET @unit_sql := IF(
  @is_fancy = 1 AND @has_unit_col > 0 AND @unit_pcs IS NOT NULL,
  'UPDATE products SET product_unit_id = @unit_pcs WHERE sku LIKE ''FC-%''',
  'SELECT 1'
);
PREPARE _fc_unit_stmt FROM @unit_sql;
EXECUTE _fc_unit_stmt;
DEALLOCATE PREPARE _fc_unit_stmt;

-- ---------------------------------------------------------------------------
-- Ingredients + opening quantities (inventory page) and recipes
-- ---------------------------------------------------------------------------
INSERT INTO ingredients (branch_id, name_en, name_ar, name_bn, unit, quantity_on_hand, reorder_level, low_stock_threshold, is_active, created_at, updated_at)
SELECT * FROM (
  SELECT @branch_id AS bid, 'Espresso beans' AS ne, 'حبوب إسبريسو' AS na, 'এসপ্রেসো বিন' AS nb,
         'g' AS unit, 5000.000 AS qty, 800.000 AS reorder, 800.000 AS low, 1 AS act, NOW() AS c, NOW() AS u
  UNION ALL SELECT @branch_id, 'Fresh milk', 'حليب طازج', 'তাজা দুধ', 'ml', 20000.000, 3000.000, 3000.000, 1, NOW(), NOW()
  UNION ALL SELECT @branch_id, 'Arabic coffee blend', 'خلطة القهوة العربية', 'আরবীয় কফি মিশ্রণ', 'g', 3000.000, 400.000, 400.000, 1, NOW(), NOW()
  UNION ALL SELECT @branch_id, 'Butter croissant', 'كرواسون بالزبدة', 'মাখনের ক্রোয়াসাঁ', 'pcs', 80.000, 12.000, 12.000, 1, NOW(), NOW()
  UNION ALL SELECT @branch_id, 'Cake portion', 'حصة كيك', 'কেকের অংশ', 'pcs', 40.000, 8.000, 8.000, 1, NOW(), NOW()
) AS seed
WHERE @is_fancy = 1
  AND NOT EXISTS (SELECT 1 FROM ingredients i WHERE i.name_en = seed.ne);

UPDATE ingredients i
JOIN (
  SELECT 'Espresso beans' AS ne, 'حبوب إسبريسو' AS na, 'এসপ্রেসো বিন' AS nb, 'g' AS unit, 5000.000 AS qty, 800.000 AS low
  UNION ALL SELECT 'Fresh milk', 'حليب طازج', 'তাজা দুধ', 'ml', 20000.000, 3000.000
  UNION ALL SELECT 'Arabic coffee blend', 'خلطة القهوة العربية', 'আরবীয় কফি মিশ্রণ', 'g', 3000.000, 400.000
  UNION ALL SELECT 'Butter croissant', 'كرواسون بالزبدة', 'মাখনের ক্রোয়াসাঁ', 'pcs', 80.000, 12.000
  UNION ALL SELECT 'Cake portion', 'حصة كيك', 'কেকের অংশ', 'pcs', 40.000, 8.000
) AS seed ON i.name_en = seed.ne
SET
  i.name_ar = seed.na,
  i.name_bn = seed.nb,
  i.unit = seed.unit,
  i.quantity_on_hand = seed.qty,
  i.reorder_level = seed.low,
  i.low_stock_threshold = seed.low,
  i.is_active = 1,
  i.updated_at = NOW()
WHERE @is_fancy = 1;

-- product_ingredients (recipe quantities per unit sold)
INSERT INTO product_ingredients (product_id, ingredient_id, quantity_per_unit, created_at, updated_at)
SELECT p.id, i.id, seed.qty, NOW(), NOW()
FROM (
  SELECT 'FC-ESPRESSO' AS sku, 'Espresso beans' AS ing, 18.000 AS qty
  UNION ALL SELECT 'FC-AMERICANO', 'Espresso beans', 18.000
  UNION ALL SELECT 'FC-CAPPUCCINO', 'Espresso beans', 18.000
  UNION ALL SELECT 'FC-CAPPUCCINO', 'Fresh milk', 120.000
  UNION ALL SELECT 'FC-LATTE', 'Espresso beans', 18.000
  UNION ALL SELECT 'FC-LATTE', 'Fresh milk', 180.000
  UNION ALL SELECT 'FC-SPANISH-LATTE', 'Espresso beans', 18.000
  UNION ALL SELECT 'FC-SPANISH-LATTE', 'Fresh milk', 180.000
  UNION ALL SELECT 'FC-FLAT-WHITE', 'Espresso beans', 18.000
  UNION ALL SELECT 'FC-FLAT-WHITE', 'Fresh milk', 130.000
  UNION ALL SELECT 'FC-ICED-LATTE', 'Espresso beans', 18.000
  UNION ALL SELECT 'FC-ICED-LATTE', 'Fresh milk', 180.000
  UNION ALL SELECT 'FC-QAHWA', 'Arabic coffee blend', 12.000
  UNION ALL SELECT 'FC-CROISSANT', 'Butter croissant', 1.000
  UNION ALL SELECT 'FC-CAKE-SLICE', 'Cake portion', 1.000
) AS seed
JOIN products p ON p.sku = seed.sku
JOIN ingredients i ON i.name_en = seed.ing
WHERE @is_fancy = 1
  AND NOT EXISTS (
    SELECT 1 FROM product_ingredients pi
    WHERE pi.product_id = p.id AND pi.ingredient_id = i.id
  );

UPDATE product_ingredients pi
JOIN products p ON p.id = pi.product_id
JOIN ingredients i ON i.id = pi.ingredient_id
JOIN (
  SELECT 'FC-ESPRESSO' AS sku, 'Espresso beans' AS ing, 18.000 AS qty
  UNION ALL SELECT 'FC-AMERICANO', 'Espresso beans', 18.000
  UNION ALL SELECT 'FC-CAPPUCCINO', 'Espresso beans', 18.000
  UNION ALL SELECT 'FC-CAPPUCCINO', 'Fresh milk', 120.000
  UNION ALL SELECT 'FC-LATTE', 'Espresso beans', 18.000
  UNION ALL SELECT 'FC-LATTE', 'Fresh milk', 180.000
  UNION ALL SELECT 'FC-SPANISH-LATTE', 'Espresso beans', 18.000
  UNION ALL SELECT 'FC-SPANISH-LATTE', 'Fresh milk', 180.000
  UNION ALL SELECT 'FC-FLAT-WHITE', 'Espresso beans', 18.000
  UNION ALL SELECT 'FC-FLAT-WHITE', 'Fresh milk', 130.000
  UNION ALL SELECT 'FC-ICED-LATTE', 'Espresso beans', 18.000
  UNION ALL SELECT 'FC-ICED-LATTE', 'Fresh milk', 180.000
  UNION ALL SELECT 'FC-QAHWA', 'Arabic coffee blend', 12.000
  UNION ALL SELECT 'FC-CROISSANT', 'Butter croissant', 1.000
  UNION ALL SELECT 'FC-CAKE-SLICE', 'Cake portion', 1.000
) AS seed ON p.sku = seed.sku AND i.name_en = seed.ing
SET pi.quantity_per_unit = seed.qty, pi.updated_at = NOW()
WHERE @is_fancy = 1;

-- Opening stock movements (one per ingredient, skipped on re-run)
INSERT INTO inventory_movements (branch_id, ingredient_id, type, reason, quantity_delta, created_at, updated_at)
SELECT @branch_id, i.id, 'adjust', 'opening', i.quantity_on_hand, NOW(), NOW()
FROM ingredients i
WHERE @is_fancy = 1
  AND @branch_id IS NOT NULL
  AND i.name_en IN ('Espresso beans', 'Fresh milk', 'Arabic coffee blend', 'Butter croissant', 'Cake portion')
  AND NOT EXISTS (
    SELECT 1 FROM inventory_movements m
    WHERE m.ingredient_id = i.id AND m.reason = 'opening'
  );

SELECT
  IF(@is_fancy = 1, 'ok', 'skipped: Fancy Coffee business not found') AS status,
  (SELECT COUNT(*) FROM products WHERE sku LIKE 'FC-%') AS products,
  (SELECT COUNT(*) FROM ingredients WHERE name_en IN ('Espresso beans', 'Fresh milk', 'Arabic coffee blend', 'Butter croissant', 'Cake portion')) AS ingredients;
