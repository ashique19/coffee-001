-- Idempotent patch for cPanel / phpMyAdmin (MySQL 8+ / MariaDB 10.4+)
-- Problem: Spatie teams use tenant_id on model_has_roles PK; NULL cannot be stored on MySQL.
-- Platform-scoped roles use tenant_id = 0 (not a real tenant).

SET @platform_team := 0;

-- 1) Normalize platform role rows (NULL -> 0)
UPDATE roles
SET tenant_id = @platform_team
WHERE guard_name = 'platform' AND (tenant_id IS NULL OR tenant_id = 0);

-- 2) Ensure platform permissions exist
INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'platform.manage', 'platform', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'platform.manage' AND guard_name = 'platform');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'support.access-tenant', 'platform', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'support.access-tenant' AND guard_name = 'platform');

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'billing.view', 'platform', NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM permissions WHERE name = 'billing.view' AND guard_name = 'platform');

-- 3) Ensure super-admin role (platform, team 0)
INSERT INTO roles (tenant_id, name, guard_name, created_at, updated_at)
SELECT @platform_team, 'super-admin', 'platform', NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM roles
    WHERE name = 'super-admin' AND guard_name = 'platform' AND tenant_id = @platform_team
);

UPDATE roles
SET tenant_id = @platform_team
WHERE name = 'super-admin' AND guard_name = 'platform' AND tenant_id IS NULL;

-- 4) Link all platform permissions to super-admin
INSERT INTO role_has_permissions (permission_id, role_id)
SELECT p.id, r.id
FROM permissions p
INNER JOIN roles r ON r.name = 'super-admin' AND r.guard_name = 'platform' AND r.tenant_id = @platform_team
WHERE p.guard_name = 'platform'
  AND NOT EXISTS (
    SELECT 1 FROM role_has_permissions x WHERE x.permission_id = p.id AND x.role_id = r.id
  );

-- 5) Ensure platform admin user exists (does not reset password if the row exists)
--    Password for new rows must be set via UI reset or: UPDATE platform_users SET password = ? WHERE email = 'admin@taskeenlab.test';
INSERT INTO platform_users (name, email, password, is_active, created_at, updated_at)
SELECT 'Platform Admin', 'admin@taskeenlab.test', '$2y$10$rU1Juu0yLx1GarXjGNEgMuHi0PG0ZSLdmUZUWKacf4TOLnCSZk9F.', 1, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM platform_users WHERE email = 'admin@taskeenlab.test');

-- 6) Assign super-admin to admin@taskeenlab.test (tenant_id = 0 on pivot)
INSERT INTO model_has_roles (tenant_id, role_id, model_id, model_type)
SELECT @platform_team, r.id, u.id, 'App\\Models\\PlatformUser'
FROM platform_users u
INNER JOIN roles r ON r.name = 'super-admin' AND r.guard_name = 'platform' AND r.tenant_id = @platform_team
WHERE u.email = 'admin@taskeenlab.test'
  AND NOT EXISTS (
    SELECT 1 FROM model_has_roles m
    WHERE m.model_type = 'App\\Models\\PlatformUser'
      AND m.model_id = u.id
      AND m.role_id = r.id
      AND m.tenant_id = @platform_team
  );

-- 7) Fix any legacy NULL pivots for platform users
UPDATE model_has_roles
SET tenant_id = @platform_team
WHERE model_type = 'App\\Models\\PlatformUser' AND tenant_id IS NULL;

UPDATE model_has_permissions
SET tenant_id = @platform_team
WHERE model_type = 'App\\Models\\PlatformUser' AND tenant_id IS NULL;
