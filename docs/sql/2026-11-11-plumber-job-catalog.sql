-- Idempotent cPanel / phpMyAdmin patch (MySQL 8+ / MariaDB 10.4+)
-- Adds products.item_type, products.image_url (when missing), and seeds demo-plumber job catalog.
-- Assigns staff roles with backslash-safe model_type (CONCAT + CHAR(92)).
-- Clears Spatie permission cache rows when a `cache` table exists.
-- After import: if CACHE_STORE=file in .env, delete files inside storage/framework/cache/data/
--   (not bootstrap/cache) so staff permissions apply — see docs/DEPLOY-TEST.md.
-- Safe to re-run. Touches only slug demo-plumber (+ global spatie permission cache keys).

SET @demo_slug := 'demo-plumber';
SET @demo_password_hash := '$2y$10$rU1Juu0yLx1GarXjGNEgMuHi0PG0ZSLdmUZUWKacf4TOLnCSZk9F.';
SET @egs_uuid := '00000000-0000-4000-8000-000000000010';
SET @db := DATABASE();

SET @ddl := (
    SELECT IF(
        EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'products' AND COLUMN_NAME = 'item_type'),
        'SELECT 1',
        'ALTER TABLE products ADD COLUMN item_type VARCHAR(16) NOT NULL DEFAULT ''item'' AFTER sku'
    )
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @ddl := (
    SELECT IF(
        EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'product_categories' AND COLUMN_NAME = 'icon_key'),
        'SELECT 1',
        'ALTER TABLE product_categories ADD COLUMN icon_key VARCHAR(32) NULL AFTER name_ar'
    )
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @ddl := (
    SELECT IF(
        EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'products' AND COLUMN_NAME = 'image_url'),
        'SELECT 1',
        'ALTER TABLE products ADD COLUMN image_url VARCHAR(255) NULL AFTER name_ar'
    )
);
PREPARE stmt FROM @ddl; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT INTO migrations (migration, batch)
SELECT '2026_11_09_100000_add_pos_catalog_presentation_columns', b.max_batch + 1
FROM (SELECT COALESCE(MAX(batch), 0) AS max_batch FROM migrations) AS b
WHERE NOT EXISTS (
    SELECT 1 FROM migrations
    WHERE migration = '2026_11_09_100000_add_pos_catalog_presentation_columns'
);

INSERT INTO tenants (slug, name, settings, created_at, updated_at)
VALUES (@demo_slug, 'Demo Plumbing Co', '{"theme":{"layout_key":"plumber","logo_url":null,"colors":{"navy":"#0B1F3A","steel":"#2D4A6F","sky":"#E8F1FA","ice":"#F4F8FC","safety":"#E87722","accent":"#1B8A8A","slate":"#475569"},"font":"Instrument Sans","font_ar":"Tajawal"},"default_locale":"en","legal_entity":{"name_en":"Demo Plumbing Co","name_ar":"شركة السباكة التجريبية","vat_number":"311122393500004","cr_number":"1010987654","address":"Al Olaya Street, Building 12, Riyadh 12211, Kingdom of Saudi Arabia","address_ar":"شارع العليا، مبنى ١٢، الرياض ١٢٢١١، المملكة العربية السعودية"},"zatca":{"integration_enabled":false,"invoice_type":"1100","solution_unit":"EGS","model_version":"v1","business_category":"Plumbing","default_document_type":"standard"},"content":{"plumber":{"tagline_en":"Licensed plumbing & water systems across Riyadh","tagline_ar":"سباكة مرخّصة وأنظمة مياه في الرياض","about_en":"Demo Plumbing Co provides residential and light commercial plumbing — leaks, heaters, drain clearing, and preventative maintenance. Our technicians arrive on time with clear quotes before work begins.","about_ar":"تقدّم شركة السباكة التجريبية خدمات سباكة للمنازل والمنشآت الصغيرة — تسريبات، سخانات، تسليك مجاري وصيانة وقائية. فنّيونا يلتزمون بالمواعيد مع عرض سعر واضح قبل البدء.","hours":[{"label_en":"Sat – Thu","label_ar":"السبت – الخميس","value_en":"8:00 AM – 9:00 PM","value_ar":"٨:٠٠ ص – ٩:٠٠ م"},{"label_en":"Friday","label_ar":"الجمعة","value_en":"2:00 PM – 9:00 PM","value_ar":"٢:٠٠ م – ٩:٠٠ م"},{"label_en":"Emergency line","label_ar":"طوارئ","value_en":"24/7 dispatch","value_ar":"استقبال على مدار الساعة"}],"location":{"map_placeholder":true,"lat":24.7136,"lng":46.6753},"contact":{"phone":"+966 11 200 4472","whatsapp":"+966500000447","email":"service@demoplumbingco.sa"},"trust_points":[{"icon":"shield","title_en":"Licensed technicians","title_ar":"فنيون مرخّصون","body_en":"Municipal-compliant workmanship with photo documentation.","body_ar":"تنفيذ متوافق مع الأنظمة مع توثيق بالصور."},{"icon":"clock","title_en":"Same-day visits","title_ar":"زيارات في نفس اليوم","body_en":"Most jobs scheduled within 4 hours in Riyadh.","body_ar":"معظم الطلبات خلال ٤ ساعات داخل الرياض."},{"icon":"receipt","title_en":"Clear SAR pricing","title_ar":"أسعار واضحة بالريال","body_en":"Itemised quotes; VAT invoices available for businesses.","body_ar":"عروض أسعار مفصّلة وفواتير ضريبية للمنشآت."}],"materials_note_en":"Parts and small materials used on your visit are itemised on the job invoice—not listed as a public hardware catalog.","materials_note_ar":"القطع والمواد الصغيرة المستخدمة في الزيارة تُفصَّل في فاتورة العمل—وليست متجرًا عامًا للتجهيزات.","featured_services":[{"key":"leak-repair","badge_en":"Popular","badge_ar":"الأكثر طلباً"},{"key":"drain-clear","badge_en":"Fast","badge_ar":"سريع"},{"key":"water-heater","badge_en":"Install","badge_ar":"تركيب"}],"service_categories":[{"slug":"emergency","name_en":"Emergency","name_ar":"طوارئ","items":[{"slug":"leak-repair","name_en":"Leak repair visit","name_ar":"زيارة إصلاح تسرب","desc_en":"Diagnose and patch active leaks","desc_ar":"تشخيص ومعالجة التسربات النشطة","price_sar":180},{"slug":"burst-pipe","name_en":"Burst pipe isolation","name_ar":"عزل أنبوب منفجر","desc_en":"Shut-off and temporary repair","desc_ar":"إغلاق المياه وإصلاح مؤقت","price_sar":250}]},{"slug":"maintenance","name_en":"Maintenance","name_ar":"صيانة","items":[{"slug":"drain-clear","name_en":"Drain clearing","name_ar":"تسليك مجاري","desc_en":"Kitchen or bathroom line","desc_ar":"مطبخ أو حمّام","price_sar":140},{"slug":"fixture-service","name_en":"Fixture tune-up","name_ar":"صيانة تجهيزات","desc_en":"Taps, mixers, seals","desc_ar":"محابس ومفاصل وأختام","price_sar":95}]},{"slug":"install","name_en":"Installation","name_ar":"تركيب","items":[{"slug":"water-heater","name_en":"Water heater install","name_ar":"تركيب سخان مياه","desc_en":"Supply and labour, standard unit","desc_ar":"توريد وتركيب وحدة قياسية","price_sar":420},{"slug":"filtration","name_en":"Filtration system","name_ar":"نظام تنقية","desc_en":"Under-sink carbon block","desc_ar":"فلتر تحت الحوض","price_sar":320}]}]}}}', NOW(), NOW())
ON DUPLICATE KEY UPDATE name = VALUES(name), settings = VALUES(settings), updated_at = NOW();

SET @tenant_id := (SELECT id FROM tenants WHERE slug = @demo_slug LIMIT 1);

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'pos.access', 'tenant', NOW(), NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'pos.access' AND guard_name = 'tenant');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'inventory.view', 'tenant', NOW(), NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'inventory.view' AND guard_name = 'tenant');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'invoices.issue', 'tenant', NOW(), NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'invoices.issue' AND guard_name = 'tenant');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'reports.view', 'tenant', NOW(), NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'reports.view' AND guard_name = 'tenant');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'staff.manage', 'tenant', NOW(), NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'staff.manage' AND guard_name = 'tenant');
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'zatca.manage', 'tenant', NOW(), NOW() FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'zatca.manage' AND guard_name = 'tenant');

INSERT INTO roles (tenant_id, name, guard_name, created_at, updated_at)
SELECT @tenant_id, 'owner', 'tenant', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE tenant_id = @tenant_id AND name = 'owner' AND guard_name = 'tenant');
INSERT INTO roles (tenant_id, name, guard_name, created_at, updated_at)
SELECT @tenant_id, 'manager', 'tenant', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE tenant_id = @tenant_id AND name = 'manager' AND guard_name = 'tenant');
INSERT INTO roles (tenant_id, name, guard_name, created_at, updated_at)
SELECT @tenant_id, 'cashier', 'tenant', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE tenant_id = @tenant_id AND name = 'cashier' AND guard_name = 'tenant');
INSERT INTO roles (tenant_id, name, guard_name, created_at, updated_at)
SELECT @tenant_id, 'accountant', 'tenant', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM roles WHERE tenant_id = @tenant_id AND name = 'accountant' AND guard_name = 'tenant');

INSERT INTO role_has_permissions (permission_id, role_id)
SELECT p.id, r.id FROM permissions p
INNER JOIN roles r ON r.tenant_id = @tenant_id AND r.name = 'owner' AND r.guard_name = 'tenant'
WHERE p.guard_name = 'tenant'
  AND NOT EXISTS (SELECT 1 FROM role_has_permissions x WHERE x.permission_id = p.id AND x.role_id = r.id);

INSERT INTO role_has_permissions (permission_id, role_id)
SELECT p.id, r.id FROM permissions p
INNER JOIN roles r ON r.tenant_id = @tenant_id AND r.name = 'manager' AND r.guard_name = 'tenant'
WHERE p.guard_name = 'tenant' AND p.name IN ('pos.access','inventory.view','reports.view','staff.manage','zatca.manage')
  AND NOT EXISTS (SELECT 1 FROM role_has_permissions x WHERE x.permission_id = p.id AND x.role_id = r.id);

INSERT INTO role_has_permissions (permission_id, role_id)
SELECT p.id, r.id FROM permissions p
INNER JOIN roles r ON r.tenant_id = @tenant_id AND r.name = 'cashier' AND r.guard_name = 'tenant'
WHERE p.guard_name = 'tenant' AND p.name = 'pos.access'
  AND NOT EXISTS (SELECT 1 FROM role_has_permissions x WHERE x.permission_id = p.id AND x.role_id = r.id);

INSERT INTO role_has_permissions (permission_id, role_id)
SELECT p.id, r.id FROM permissions p
INNER JOIN roles r ON r.tenant_id = @tenant_id AND r.name = 'accountant' AND r.guard_name = 'tenant'
WHERE p.guard_name = 'tenant' AND p.name IN ('invoices.issue','reports.view','zatca.manage')
  AND NOT EXISTS (SELECT 1 FROM role_has_permissions x WHERE x.permission_id = p.id AND x.role_id = r.id);


INSERT INTO branches (tenant_id, code, name, name_ar, address, is_active, created_at, updated_at)
SELECT @tenant_id, 'ruh-main', 'Riyadh Service Center', 'مركز الرياض',
       'Al Olaya Street, Building 12, Riyadh 12211, Kingdom of Saudi Arabia', 1, NOW(), NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM branches WHERE tenant_id = @tenant_id AND code = 'ruh-main');

SET @branch_id := (SELECT id FROM branches WHERE tenant_id = @tenant_id AND code = 'ruh-main' LIMIT 1);

INSERT INTO egs_devices (tenant_id, branch_id, uuid, name, last_icv, created_at, updated_at)
SELECT @tenant_id, @branch_id, @egs_uuid, 'EGS Riyadh Demo', 0, NOW(), NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM egs_devices WHERE uuid = @egs_uuid);

INSERT INTO tenant_users (tenant_id, branch_id, name, email, password, is_active, created_at, updated_at)
SELECT @tenant_id, NULL, 'Owner', 'owner@demo-plumber.test', @demo_password_hash, 1, NOW(), NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM tenant_users WHERE tenant_id = @tenant_id AND email = 'owner@demo-plumber.test');

INSERT INTO tenant_users (tenant_id, branch_id, name, email, password, is_active, created_at, updated_at)
SELECT @tenant_id, @branch_id, 'Technician', 'technician@demo-plumber.test', @demo_password_hash, 1, NOW(), NOW()
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM tenant_users WHERE tenant_id = @tenant_id AND email = 'technician@demo-plumber.test');

SET @model_type := CONCAT('App', CHAR(92), 'Models', CHAR(92), 'TenantUser');

DELETE m
FROM model_has_roles m
INNER JOIN tenant_users u ON u.id = m.model_id AND u.tenant_id = @tenant_id
WHERE @tenant_id IS NOT NULL
  AND m.tenant_id = @tenant_id
  AND u.email IN ('owner@demo-plumber.test', 'technician@demo-plumber.test')
  AND m.model_type <> @model_type;

INSERT INTO model_has_roles (tenant_id, role_id, model_id, model_type)
SELECT @tenant_id, r.id, u.id, @model_type
FROM tenant_users u
INNER JOIN roles r ON r.tenant_id = @tenant_id AND r.name = 'owner' AND r.guard_name = 'tenant'
WHERE @tenant_id IS NOT NULL
  AND u.tenant_id = @tenant_id AND u.email = 'owner@demo-plumber.test'
  AND NOT EXISTS (
    SELECT 1 FROM model_has_roles m
    WHERE m.tenant_id = @tenant_id AND m.model_type = @model_type
      AND m.model_id = u.id AND m.role_id = r.id
  );

INSERT INTO model_has_roles (tenant_id, role_id, model_id, model_type)
SELECT @tenant_id, r.id, u.id, @model_type
FROM tenant_users u
INNER JOIN roles r ON r.tenant_id = @tenant_id AND r.name = 'cashier' AND r.guard_name = 'tenant'
WHERE @tenant_id IS NOT NULL
  AND u.tenant_id = @tenant_id AND u.email = 'technician@demo-plumber.test'
  AND NOT EXISTS (
    SELECT 1 FROM model_has_roles m
    WHERE m.tenant_id = @tenant_id AND m.model_type = @model_type
      AND m.model_id = u.id AND m.role_id = r.id
  );


-- Clear Spatie permission cache when CACHE_STORE=database (skipped if no `cache` table).
SET @has_cache := (
  SELECT COUNT(*)
  FROM information_schema.tables
  WHERE table_schema = DATABASE() AND table_name = 'cache'
);

SET @cache_sql := IF(
  @has_cache > 0,
  'DELETE FROM `cache` WHERE `key` LIKE ''%spatie.permission.cache%''',
  'SELECT 1'
);

PREPARE cache_stmt FROM @cache_sql;
EXECUTE cache_stmt;
DEALLOCATE PREPARE cache_stmt;


UPDATE products SET is_active = 0
WHERE tenant_id = @tenant_id AND sku IN ('LEAK','BURST','DRAIN','FIX','HEAT','FILT');

INSERT INTO product_categories (tenant_id, name_en, name_ar, icon_key, is_active, created_at, updated_at)
SELECT @tenant_id, 'Emergency', 'طوارئ', 'wrench', 1, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Emergency');
INSERT INTO product_categories (tenant_id, name_en, name_ar, icon_key, is_active, created_at, updated_at)
SELECT @tenant_id, 'Maintenance', 'صيانة', 'wrench', 1, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Maintenance');
INSERT INTO product_categories (tenant_id, name_en, name_ar, icon_key, is_active, created_at, updated_at)
SELECT @tenant_id, 'Installation', 'تركيب', 'wrench', 1, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Installation');
INSERT INTO product_categories (tenant_id, name_en, name_ar, icon_key, is_active, created_at, updated_at)
SELECT @tenant_id, 'Parts & materials', 'قطع ومواد', 'wrench', 1, NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Parts & materials');

INSERT INTO products (tenant_id, product_category_id, sku, item_type, name_en, name_ar, price_ex_vat, vat_rate, image_url, is_active, created_at, updated_at)
SELECT @tenant_id,
       (SELECT id FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Emergency' LIMIT 1),
       'SRV-LEAK', 'service', 'Leak repair visit', 'زيارة إصلاح تسرب', 180, 15.00, 'images/plumber/leak-repair.webp', 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM products WHERE tenant_id = @tenant_id AND sku = 'SRV-LEAK');

UPDATE products
SET item_type = 'service', name_en = 'Leak repair visit', name_ar = 'زيارة إصلاح تسرب', price_ex_vat = 180, is_active = 1, image_url = 'images/plumber/leak-repair.webp',
    product_category_id = (SELECT id FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Emergency' LIMIT 1)
WHERE tenant_id = @tenant_id AND sku = 'SRV-LEAK';
INSERT INTO products (tenant_id, product_category_id, sku, item_type, name_en, name_ar, price_ex_vat, vat_rate, image_url, is_active, created_at, updated_at)
SELECT @tenant_id,
       (SELECT id FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Emergency' LIMIT 1),
       'SRV-BURST', 'service', 'Burst pipe isolation', 'عزل أنبوب منفجر', 250, 15.00, 'images/plumber/burst-pipe.webp', 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM products WHERE tenant_id = @tenant_id AND sku = 'SRV-BURST');

UPDATE products
SET item_type = 'service', name_en = 'Burst pipe isolation', name_ar = 'عزل أنبوب منفجر', price_ex_vat = 250, is_active = 1, image_url = 'images/plumber/burst-pipe.webp',
    product_category_id = (SELECT id FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Emergency' LIMIT 1)
WHERE tenant_id = @tenant_id AND sku = 'SRV-BURST';
INSERT INTO products (tenant_id, product_category_id, sku, item_type, name_en, name_ar, price_ex_vat, vat_rate, image_url, is_active, created_at, updated_at)
SELECT @tenant_id,
       (SELECT id FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Maintenance' LIMIT 1),
       'SRV-DRAIN', 'service', 'Drain clearing', 'تسليك مجاري', 140, 15.00, 'images/plumber/drain-clear.webp', 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM products WHERE tenant_id = @tenant_id AND sku = 'SRV-DRAIN');

UPDATE products
SET item_type = 'service', name_en = 'Drain clearing', name_ar = 'تسليك مجاري', price_ex_vat = 140, is_active = 1, image_url = 'images/plumber/drain-clear.webp',
    product_category_id = (SELECT id FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Maintenance' LIMIT 1)
WHERE tenant_id = @tenant_id AND sku = 'SRV-DRAIN';
INSERT INTO products (tenant_id, product_category_id, sku, item_type, name_en, name_ar, price_ex_vat, vat_rate, image_url, is_active, created_at, updated_at)
SELECT @tenant_id,
       (SELECT id FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Maintenance' LIMIT 1),
       'SRV-FIX', 'service', 'Fixture tune-up', 'صيانة تجهيزات', 95, 15.00, 'images/plumber/fixture-service.webp', 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM products WHERE tenant_id = @tenant_id AND sku = 'SRV-FIX');

UPDATE products
SET item_type = 'service', name_en = 'Fixture tune-up', name_ar = 'صيانة تجهيزات', price_ex_vat = 95, is_active = 1, image_url = 'images/plumber/fixture-service.webp',
    product_category_id = (SELECT id FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Maintenance' LIMIT 1)
WHERE tenant_id = @tenant_id AND sku = 'SRV-FIX';
INSERT INTO products (tenant_id, product_category_id, sku, item_type, name_en, name_ar, price_ex_vat, vat_rate, image_url, is_active, created_at, updated_at)
SELECT @tenant_id,
       (SELECT id FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Installation' LIMIT 1),
       'SRV-HEAT', 'service', 'Water heater install', 'تركيب سخان مياه', 420, 15.00, 'images/plumber/water-heater.webp', 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM products WHERE tenant_id = @tenant_id AND sku = 'SRV-HEAT');

UPDATE products
SET item_type = 'service', name_en = 'Water heater install', name_ar = 'تركيب سخان مياه', price_ex_vat = 420, is_active = 1, image_url = 'images/plumber/water-heater.webp',
    product_category_id = (SELECT id FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Installation' LIMIT 1)
WHERE tenant_id = @tenant_id AND sku = 'SRV-HEAT';
INSERT INTO products (tenant_id, product_category_id, sku, item_type, name_en, name_ar, price_ex_vat, vat_rate, image_url, is_active, created_at, updated_at)
SELECT @tenant_id,
       (SELECT id FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Installation' LIMIT 1),
       'SRV-FILT', 'service', 'Filtration system install', 'تركيب نظام تنقية', 320, 15.00, 'images/plumber/filtration.webp', 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM products WHERE tenant_id = @tenant_id AND sku = 'SRV-FILT');

UPDATE products
SET item_type = 'service', name_en = 'Filtration system install', name_ar = 'تركيب نظام تنقية', price_ex_vat = 320, is_active = 1, image_url = 'images/plumber/filtration.webp',
    product_category_id = (SELECT id FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Installation' LIMIT 1)
WHERE tenant_id = @tenant_id AND sku = 'SRV-FILT';
INSERT INTO products (tenant_id, product_category_id, sku, item_type, name_en, name_ar, price_ex_vat, vat_rate, is_active, created_at, updated_at)
SELECT @tenant_id,
       (SELECT id FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Parts & materials' LIMIT 1),
       'PTFE', 'part', 'PTFE tape roll', 'شريط تفلون', 8, 15.00, 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM products WHERE tenant_id = @tenant_id AND sku = 'PTFE');

UPDATE products
SET item_type = 'part', name_en = 'PTFE tape roll', name_ar = 'شريط تفلون', price_ex_vat = 8, is_active = 1,
    product_category_id = (SELECT id FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Parts & materials' LIMIT 1)
WHERE tenant_id = @tenant_id AND sku = 'PTFE';
INSERT INTO products (tenant_id, product_category_id, sku, item_type, name_en, name_ar, price_ex_vat, vat_rate, is_active, created_at, updated_at)
SELECT @tenant_id,
       (SELECT id FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Parts & materials' LIMIT 1),
       'WASH-KIT', 'part', 'Rubber washer kit', 'طقم حشوات مطاطية', 15, 15.00, 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM products WHERE tenant_id = @tenant_id AND sku = 'WASH-KIT');

UPDATE products
SET item_type = 'part', name_en = 'Rubber washer kit', name_ar = 'طقم حشوات مطاطية', price_ex_vat = 15, is_active = 1,
    product_category_id = (SELECT id FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Parts & materials' LIMIT 1)
WHERE tenant_id = @tenant_id AND sku = 'WASH-KIT';
INSERT INTO products (tenant_id, product_category_id, sku, item_type, name_en, name_ar, price_ex_vat, vat_rate, is_active, created_at, updated_at)
SELECT @tenant_id,
       (SELECT id FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Parts & materials' LIMIT 1),
       'ELBOW-15', 'part', '15mm brass elbow', 'كوع نحاس ١٥ مم', 12, 15.00, 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM products WHERE tenant_id = @tenant_id AND sku = 'ELBOW-15');

UPDATE products
SET item_type = 'part', name_en = '15mm brass elbow', name_ar = 'كوع نحاس ١٥ مم', price_ex_vat = 12, is_active = 1,
    product_category_id = (SELECT id FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Parts & materials' LIMIT 1)
WHERE tenant_id = @tenant_id AND sku = 'ELBOW-15';
INSERT INTO products (tenant_id, product_category_id, sku, item_type, name_en, name_ar, price_ex_vat, vat_rate, is_active, created_at, updated_at)
SELECT @tenant_id,
       (SELECT id FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Parts & materials' LIMIT 1),
       'VALVE-ANG', 'part', 'Angle stop valve', 'صمام زاوية', 45, 15.00, 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM products WHERE tenant_id = @tenant_id AND sku = 'VALVE-ANG');

UPDATE products
SET item_type = 'part', name_en = 'Angle stop valve', name_ar = 'صمام زاوية', price_ex_vat = 45, is_active = 1,
    product_category_id = (SELECT id FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Parts & materials' LIMIT 1)
WHERE tenant_id = @tenant_id AND sku = 'VALVE-ANG';
INSERT INTO products (tenant_id, product_category_id, sku, item_type, name_en, name_ar, price_ex_vat, vat_rate, is_active, created_at, updated_at)
SELECT @tenant_id,
       (SELECT id FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Parts & materials' LIMIT 1),
       'PTRAP', 'part', 'P-trap assembly', 'مصيدة P', 38, 15.00, 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM products WHERE tenant_id = @tenant_id AND sku = 'PTRAP');

UPDATE products
SET item_type = 'part', name_en = 'P-trap assembly', name_ar = 'مصيدة P', price_ex_vat = 38, is_active = 1,
    product_category_id = (SELECT id FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Parts & materials' LIMIT 1)
WHERE tenant_id = @tenant_id AND sku = 'PTRAP';
INSERT INTO products (tenant_id, product_category_id, sku, item_type, name_en, name_ar, price_ex_vat, vat_rate, is_active, created_at, updated_at)
SELECT @tenant_id,
       (SELECT id FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Parts & materials' LIMIT 1),
       'SEALANT', 'part', 'Plumber’s putty (tub)', 'معجون سباكة', 18, 15.00, 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM products WHERE tenant_id = @tenant_id AND sku = 'SEALANT');

UPDATE products
SET item_type = 'part', name_en = 'Plumber’s putty (tub)', name_ar = 'معجون سباكة', price_ex_vat = 18, is_active = 1,
    product_category_id = (SELECT id FROM product_categories WHERE tenant_id = @tenant_id AND name_en = 'Parts & materials' LIMIT 1)
WHERE tenant_id = @tenant_id AND sku = 'SEALANT';
