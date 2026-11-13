-- Idempotent Business settings release (MySQL 8+ / MariaDB 10.4+)
-- Adds tenant_settings_audit_logs, business.manage permission, manager/owner grants.
-- After running SQL, clear Spatie permission cache: php artisan permission:cache-reset
-- (or restart PHP-FPM / queue workers if artisan is unavailable on host).

SET @db := DATABASE();

-- ═══ 1. Audit log table ═══
SET @ddl := (
    SELECT IF(
        EXISTS(
            SELECT 1 FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'tenant_settings_audit_logs'
        ),
        'SELECT 1',
        'CREATE TABLE tenant_settings_audit_logs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            tenant_id BIGINT UNSIGNED NOT NULL,
            tenant_user_id BIGINT UNSIGNED NULL,
            event VARCHAR(120) NOT NULL,
            payload JSON NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX tenant_settings_audit_logs_tenant_id_created_at_index (tenant_id, created_at),
            CONSTRAINT tenant_settings_audit_logs_tenant_id_foreign
                FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE,
            CONSTRAINT tenant_settings_audit_logs_tenant_user_id_foreign
                FOREIGN KEY (tenant_user_id) REFERENCES tenant_users (id) ON DELETE SET NULL
        )'
    )
);
PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ═══ 2. Permission business.manage (global tenant guard) ═══
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'business.manage', 'tenant', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM permissions WHERE name = 'business.manage' AND guard_name = 'tenant'
);

SET @perm_id := (SELECT id FROM permissions WHERE name = 'business.manage' AND guard_name = 'tenant' LIMIT 1);

-- Owner on every tenant: all permissions (idempotent)
INSERT INTO role_has_permissions (permission_id, role_id)
SELECT @perm_id, r.id
FROM roles r
WHERE r.guard_name = 'tenant' AND r.name = 'owner'
  AND NOT EXISTS (
    SELECT 1 FROM role_has_permissions x WHERE x.permission_id = @perm_id AND x.role_id = r.id
  );

-- Manager on every tenant
INSERT INTO role_has_permissions (permission_id, role_id)
SELECT @perm_id, r.id
FROM roles r
WHERE r.guard_name = 'tenant' AND r.name = 'manager'
  AND NOT EXISTS (
    SELECT 1 FROM role_has_permissions x WHERE x.permission_id = @perm_id AND x.role_id = r.id
  );

-- Cashier must NOT receive business.manage (no insert).

-- ═══ 3. Migrations ledger ═══
INSERT INTO migrations (migration, batch)
SELECT '2026_11_12_210000_create_tenant_settings_audit_logs_table', COALESCE((SELECT MAX(batch) FROM migrations), 0) + 1
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM migrations WHERE migration = '2026_11_12_210000_create_tenant_settings_audit_logs_table'
);

-- ═══ 4. Prefill settings.business from existing JSON (fancy-coffee + demo-plumber) ═══
UPDATE tenants t
SET settings = JSON_SET(
    COALESCE(t.settings, JSON_OBJECT()),
    '$.business.brand.display_name_en',
    COALESCE(JSON_UNQUOTE(JSON_EXTRACT(t.settings, '$.legal_entity.name_en')), t.name),
    '$.business.brand.display_name_ar',
    COALESCE(JSON_UNQUOTE(JSON_EXTRACT(t.settings, '$.legal_entity.name_ar')), ''),
    '$.business.legal.name_en',
    COALESCE(JSON_UNQUOTE(JSON_EXTRACT(t.settings, '$.legal_entity.name_en')), ''),
    '$.business.legal.name_ar',
    COALESCE(JSON_UNQUOTE(JSON_EXTRACT(t.settings, '$.legal_entity.name_ar')), ''),
    '$.business.legal.vat_number',
    COALESCE(JSON_UNQUOTE(JSON_EXTRACT(t.settings, '$.legal_entity.vat_number')), ''),
    '$.business.legal.cr_number',
    COALESCE(JSON_UNQUOTE(JSON_EXTRACT(t.settings, '$.legal_entity.cr_number')), ''),
    '$.business.legal.address_en',
    COALESCE(JSON_UNQUOTE(JSON_EXTRACT(t.settings, '$.legal_entity.address')), ''),
    '$.business.legal.address_ar',
    COALESCE(JSON_UNQUOTE(JSON_EXTRACT(t.settings, '$.legal_entity.address_ar')), '')
),
updated_at = NOW()
WHERE t.slug IN ('fancy-coffee', 'demo-plumber')
  AND JSON_EXTRACT(t.settings, '$.business.brand.display_name_en') IS NULL;

-- ═══ 5. Backfill business.contact phone/email from storefront content when missing ═══
UPDATE tenants t
SET settings = JSON_SET(
    COALESCE(t.settings, JSON_OBJECT()),
    '$.business.contact.phone',
    COALESCE(
        NULLIF(JSON_UNQUOTE(JSON_EXTRACT(t.settings, '$.business.contact.phone')), ''),
        NULLIF(JSON_UNQUOTE(JSON_EXTRACT(t.settings, '$.content.cafe.contact.phone')), ''),
        NULLIF(JSON_UNQUOTE(JSON_EXTRACT(t.settings, '$.content.plumber.contact.phone')), ''),
        ''
    ),
    '$.business.contact.email',
    COALESCE(
        NULLIF(JSON_UNQUOTE(JSON_EXTRACT(t.settings, '$.business.contact.email')), ''),
        NULLIF(JSON_UNQUOTE(JSON_EXTRACT(t.settings, '$.content.cafe.contact.email')), ''),
        NULLIF(JSON_UNQUOTE(JSON_EXTRACT(t.settings, '$.content.plumber.contact.email')), ''),
        ''
    ),
    '$.business.contact.lat',
    COALESCE(
        NULLIF(JSON_UNQUOTE(JSON_EXTRACT(t.settings, '$.business.contact.lat')), ''),
        CAST(JSON_EXTRACT(t.settings, '$.content.cafe.location.lat') AS CHAR),
        CAST(JSON_EXTRACT(t.settings, '$.content.plumber.location.lat') AS CHAR),
        ''
    ),
    '$.business.contact.lng',
    COALESCE(
        NULLIF(JSON_UNQUOTE(JSON_EXTRACT(t.settings, '$.business.contact.lng')), ''),
        CAST(JSON_EXTRACT(t.settings, '$.content.cafe.location.lng') AS CHAR),
        CAST(JSON_EXTRACT(t.settings, '$.content.plumber.location.lng') AS CHAR),
        ''
    )
),
updated_at = NOW()
WHERE t.slug IN ('fancy-coffee', 'demo-plumber')
  AND JSON_TYPE(JSON_EXTRACT(t.settings, '$.business.contact')) IS NULL;
