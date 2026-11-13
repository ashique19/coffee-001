-- Idempotent patch for phpMyAdmin (MySQL 8+ / MariaDB 10.4+)
-- Migration: 2026_11_12_120000_add_product_storefront_visibility_columns

SET @db := DATABASE();

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
            SELECT 1 FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'products' AND INDEX_NAME = 'products_tenant_id_show_on_homepage_is_active_index'
        ),
        'SELECT 1',
        'ALTER TABLE products ADD INDEX products_tenant_id_show_on_homepage_is_active_index (tenant_id, show_on_homepage, is_active)'
    )
);
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

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
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE products SET show_on_menu = 1 WHERE is_active = 1 AND show_on_menu = 0;

UPDATE products p
INNER JOIN (
    SELECT id
    FROM (
        SELECT
            id,
            ROW_NUMBER() OVER (PARTITION BY tenant_id ORDER BY sort_order ASC, id ASC) AS rn
        FROM products
        WHERE is_active = 1
    ) ranked
    WHERE rn <= 6
) pick ON pick.id = p.id
SET p.show_on_homepage = 1;

INSERT INTO migrations (migration, batch)
SELECT '2026_11_12_120000_add_product_storefront_visibility_columns', b.max_batch + 1
FROM (SELECT COALESCE(MAX(batch), 0) AS max_batch FROM migrations) AS b
WHERE NOT EXISTS (
    SELECT 1 FROM migrations
    WHERE migration = '2026_11_12_120000_add_product_storefront_visibility_columns'
);

-- Migration: 2026_11_12_130000_add_catalog_i18n_columns

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

-- Fancy Coffee (slug fancy-coffee): categories by English name

UPDATE product_categories pc
INNER JOIN tenants t ON t.id = pc.tenant_id
SET
    pc.name_bn = 'গরম পানীয়',
    pc.description_en = 'Espresso-based drinks served hot.',
    pc.description_ar = 'مشروبات على أساس الإسبريسو تُقدّم ساخنة.',
    pc.description_bn = 'গরম এসপ্রেসো ভিত্তিক পানীয়।'
WHERE t.slug = 'fancy-coffee' AND pc.name_en = 'Hot drinks';

UPDATE product_categories pc
INNER JOIN tenants t ON t.id = pc.tenant_id
SET
    pc.name_bn = 'ঠান্ডা পানীয়',
    pc.description_en = 'Iced and slow-brewed cold coffee.',
    pc.description_ar = 'قهوة باردة ومشروبات مثلجة.',
    pc.description_bn = 'আইসড ও ধীর ভিজানো ঠান্ডা কফি।'
WHERE t.slug = 'fancy-coffee' AND pc.name_en = 'Cold drinks';

UPDATE products p
INNER JOIN tenants t ON t.id = p.tenant_id
SET
    p.name_bn = 'ল্যাটে',
    p.description_en = 'Velvety espresso with steamed milk.',
    p.description_ar = 'إسبريسو كريمي مع حليب مبخر.',
    p.description_bn = 'বাষ্পে গরম দুধসহ মসৃণ এসপ্রেসো।',
    p.show_on_menu = 1
WHERE t.slug = 'fancy-coffee' AND p.is_active = 1 AND p.sku = 'LATTE';

UPDATE products p
INNER JOIN tenants t ON t.id = p.tenant_id
SET
    p.name_bn = 'এসপ্রেসো',
    p.description_en = 'Double shot, medium roast.',
    p.description_ar = 'جرعتان، تحميص متوسط.',
    p.description_bn = 'ডাবল শট, মিডিয়াম রোস্ট।',
    p.show_on_menu = 1
WHERE t.slug = 'fancy-coffee' AND p.is_active = 1 AND p.sku = 'ESP';

UPDATE products p
INNER JOIN tenants t ON t.id = p.tenant_id
SET
    p.name_bn = 'আইসড ল্যাটে',
    p.description_en = 'Chilled milk over espresso.',
    p.description_ar = 'حليب بارد مع إسبريسو.',
    p.description_bn = 'ঠান্ডা দুধের উপর এসপ্রেসো।',
    p.show_on_menu = 1
WHERE t.slug = 'fancy-coffee' AND p.is_active = 1 AND p.sku = 'ICED';

UPDATE products p
INNER JOIN tenants t ON t.id = p.tenant_id
SET
    p.name_bn = 'কোল্ড ব্রু',
    p.description_en = '18-hour slow steep.',
    p.description_ar = 'تخمير بطيء لمدة ١٨ ساعة.',
    p.description_bn = '১৮ ঘণ্টার ধীর ভিজানো কফি।',
    p.show_on_menu = 1
WHERE t.slug = 'fancy-coffee' AND p.is_active = 1 AND p.sku = 'COLD';

-- Demo Plumber (slug demo-plumber): categories

UPDATE product_categories pc
INNER JOIN tenants t ON t.id = pc.tenant_id
SET
    pc.name_bn = 'জরুরি',
    pc.description_en = 'Same-day emergency plumbing call-outs.',
    pc.description_ar = 'طلبات سباكة طارئة في نفس اليوم.',
    pc.description_bn = 'একই দিনে জরুরি প্লাম্বিং সেবা।'
WHERE t.slug = 'demo-plumber' AND pc.name_en = 'Emergency';

UPDATE product_categories pc
INNER JOIN tenants t ON t.id = pc.tenant_id
SET
    pc.name_bn = 'রক্ষণাবেক্ষণ',
    pc.description_en = 'Drains, fixtures, and preventive care.',
    pc.description_ar = 'مجاري وتجهيزات وصيانة وقائية.',
    pc.description_bn = 'নর্দা, ফিক্সচার ও প্রতিরোধমূলক রক্ষণাবেক্ষণ।'
WHERE t.slug = 'demo-plumber' AND pc.name_en = 'Maintenance';

UPDATE product_categories pc
INNER JOIN tenants t ON t.id = pc.tenant_id
SET
    pc.name_bn = 'ইনস্টলেশন',
    pc.description_en = 'Heaters, filtration, and new fixtures.',
    pc.description_ar = 'سخانات وتنقية وتركيب تجهيزات جديدة.',
    pc.description_bn = 'হিটার, ফিল্টার ও নতুন ফিক্সচার ইনস্টলেশন।'
WHERE t.slug = 'demo-plumber' AND pc.name_en = 'Installation';

UPDATE product_categories pc
INNER JOIN tenants t ON t.id = pc.tenant_id
SET
    pc.name_bn = 'যন্ত্রাংশ ও উপকরণ',
    pc.description_en = 'Common parts stocked for jobs.',
    pc.description_ar = 'قطع شائعة متوفرة للأعمال.',
    pc.description_bn = 'কাজের জন্য সাধারণ যন্ত্রাংশ।'
WHERE t.slug = 'demo-plumber' AND pc.name_en = 'Parts & materials';

-- Demo Plumber services (match sku)

UPDATE products p
INNER JOIN tenants t ON t.id = p.tenant_id
SET
    p.name_bn = 'লিক মেরামত ভিজিট',
    p.description_en = 'Diagnose active leaks under sinks, walls, or supply lines.',
    p.description_ar = 'تشخيص التسريبات النشطة تحت الأحواض أو الجدران أو خطوط التغذية.',
    p.description_bn = 'সিঙ্ক, দেয়াল বা সাপ্লাই লাইনের সক্রিয় ফুটো নির্ণয় ও মেরামত।',
    p.show_on_menu = 1
WHERE t.slug = 'demo-plumber' AND p.is_active = 1 AND p.sku = 'SRV-LEAK';

UPDATE products p
INNER JOIN tenants t ON t.id = p.tenant_id
SET
    p.name_bn = 'বিস্ফোরিত পাইপ বিচ্ছিন্ন',
    p.description_en = 'Emergency isolation for burst or cracked supply lines.',
    p.description_ar = 'عزل طارئ للأنابيب المنفجرة أو المتشققة.',
    p.description_bn = 'ফেটে যাওয়া বা ফাটল সাপ্লাই লাইনের জরুরি বিচ্ছিন্নকরণ।',
    p.show_on_menu = 1
WHERE t.slug = 'demo-plumber' AND p.is_active = 1 AND p.sku = 'SRV-BURST';

UPDATE products p
INNER JOIN tenants t ON t.id = p.tenant_id
SET
    p.name_bn = 'ড্রেন পরিষ্কার',
    p.description_en = 'Clear slow or blocked kitchen and bathroom drains.',
    p.description_ar = 'تسليك مجاري المطبخ والحمام البطيئة أو المسدودة.',
    p.description_bn = 'রান্নাঘর ও বাথরুমের ধীর বা বন্ধ নর্দা পরিষ্কার।',
    p.show_on_menu = 1
WHERE t.slug = 'demo-plumber' AND p.is_active = 1 AND p.sku = 'SRV-DRAIN';

UPDATE products p
INNER JOIN tenants t ON t.id = p.tenant_id
SET
    p.name_bn = 'ফিক্সচার টিউন-আপ',
    p.description_en = 'Tune-up for taps, mixers, showers, and shut-off valves.',
    p.description_ar = 'صيانة للمحابس والخلاطات والاستحمام وصمامات الإغلاق.',
    p.description_bn = 'ট্যাপ, মিক্সার, শাওয়ার ও শাট-অফ ভালভের টিউন-আপ।',
    p.show_on_menu = 1
WHERE t.slug = 'demo-plumber' AND p.is_active = 1 AND p.sku = 'SRV-FIX';

UPDATE products p
INNER JOIN tenants t ON t.id = p.tenant_id
SET
    p.name_bn = 'ওয়াটার হিটার ইনস্টল',
    p.description_en = 'Install or replace standard residential water heaters.',
    p.description_ar = 'تركيب أو استبدال سخانات المياه السكنية القياسية.',
    p.description_bn = 'স্ট্যান্ডার্ড আবাসিক ওয়াটার হিটার ইনস্টল বা প্রতিস্থাপন।',
    p.show_on_menu = 1
WHERE t.slug = 'demo-plumber' AND p.is_active = 1 AND p.sku = 'SRV-HEAT';

UPDATE products p
INNER JOIN tenants t ON t.id = p.tenant_id
SET
    p.name_bn = 'ফিল্ট্রেশন সিস্টেম ইনস্টল',
    p.description_en = 'Under-sink and point-of-use filtration installs.',
    p.description_ar = 'تركيب أنظمة تنقية تحت الحوض ونقاط الاستخدام.',
    p.description_bn = 'সিঙ্কের নিচে ও ব্যবহারের স্থানে ফিল্ট্রেশন ইনস্টল।',
    p.show_on_menu = 1
WHERE t.slug = 'demo-plumber' AND p.is_active = 1 AND p.sku = 'SRV-FILT';

-- Demo Plumber parts (match sku)

UPDATE products p
INNER JOIN tenants t ON t.id = p.tenant_id
SET
    p.name_bn = 'পিটিএফই টেপ রোল',
    p.description_en = 'Thread-sealing PTFE tape for joints.',
    p.description_ar = 'شريط تفلون لعزل الخيوط في الوصلات.',
    p.description_bn = 'জয়েন্টের জন্য থ্রেড সিলিং পিটিএফই টেপ।',
    p.show_on_menu = 1
WHERE t.slug = 'demo-plumber' AND p.is_active = 1 AND p.sku = 'PTFE';

UPDATE products p
INNER JOIN tenants t ON t.id = p.tenant_id
SET
    p.name_bn = 'রাবার ওয়াশার কিট',
    p.description_en = 'Assorted rubber washers for taps and valves.',
    p.description_ar = 'حشوات مطاطية متنوعة للمحابس والصمامات.',
    p.description_bn = 'ট্যাপ ও ভালভের বিভিন্ন রাবার ওয়াশার।',
    p.show_on_menu = 1
WHERE t.slug = 'demo-plumber' AND p.is_active = 1 AND p.sku = 'WASH-KIT';

UPDATE products p
INNER JOIN tenants t ON t.id = p.tenant_id
SET
    p.name_bn = '১৫ মিমি ব্রাস এলবো',
    p.description_en = '15mm brass elbow fitting.',
    p.description_ar = 'كوع نحاس ١٥ مم للوصلات.',
    p.description_bn = '১৫ মিমি পিতলের এলবো ফিটিং।',
    p.show_on_menu = 1
WHERE t.slug = 'demo-plumber' AND p.is_active = 1 AND p.sku = 'ELBOW-15';

UPDATE products p
INNER JOIN tenants t ON t.id = p.tenant_id
SET
    p.name_bn = 'অ্যাঙ্গেল স্টপ ভালভ',
    p.description_en = 'Angle stop valve for fixture supply.',
    p.description_ar = 'صمام زاوية لتغذية التجهيزات.',
    p.description_bn = 'ফিক্সচার সাপ্লাইয়ের জন্য অ্যাঙ্গেল স্টপ ভালভ।',
    p.show_on_menu = 1
WHERE t.slug = 'demo-plumber' AND p.is_active = 1 AND p.sku = 'VALVE-ANG';

UPDATE products p
INNER JOIN tenants t ON t.id = p.tenant_id
SET
    p.name_bn = 'পি-ট্র্যাপ অ্যাসেম্বলি',
    p.description_en = 'P-trap assembly for sink waste lines.',
    p.description_ar = 'مصيدة P لتصريف الحوض.',
    p.description_bn = 'সিঙ্কের নির্গমন লাইনের পি-ট্র্যাপ।',
    p.show_on_menu = 1
WHERE t.slug = 'demo-plumber' AND p.is_active = 1 AND p.sku = 'PTRAP';

UPDATE products p
INNER JOIN tenants t ON t.id = p.tenant_id
SET
    p.name_bn = 'প্লাম্বার পুটি (টাব)',
    p.description_en = 'Plumber''s putty for basin and tap sealing.',
    p.description_ar = 'معجون سباكة لإحكام إغلاق الأحواض والمحابس.',
    p.description_bn = 'বাথরুম বেসিন ও ট্যাপ সিলিংয়ের প্লাম্বার পুটি।',
    p.show_on_menu = 1
WHERE t.slug = 'demo-plumber' AND p.is_active = 1 AND (p.sku = 'SEALANT' OR p.name_en = 'Plumber''s putty (tub)' OR p.name_en = 'Plumber’s putty (tub)');

INSERT INTO migrations (migration, batch)
SELECT '2026_11_12_130000_add_catalog_i18n_columns', b.max_batch + 1
FROM (SELECT COALESCE(MAX(batch), 0) AS max_batch FROM migrations) AS b
WHERE NOT EXISTS (
    SELECT 1 FROM migrations
    WHERE migration = '2026_11_12_130000_add_catalog_i18n_columns'
);
