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
