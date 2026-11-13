-- Idempotent: ensure demo-plumber owner can open Business settings (business.manage).
-- Global owner role already receives business.manage from docs/sql/2026-11-12-business-settings.sql.
-- This block is a safety net when RBAC was seeded before business.manage existed.
-- After running: php artisan permission:cache-reset

SET @demo_slug := 'demo-plumber';

INSERT INTO permissions (name, guard_name, created_at, updated_at)
SELECT 'business.manage', 'tenant', NOW(), NOW() FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM permissions WHERE name = 'business.manage' AND guard_name = 'tenant'
);

SET @perm_id := (SELECT id FROM permissions WHERE name = 'business.manage' AND guard_name = 'tenant' LIMIT 1);
SET @tenant_id := (SELECT id FROM tenants WHERE slug = @demo_slug LIMIT 1);

INSERT INTO role_has_permissions (permission_id, role_id)
SELECT @perm_id, r.id
FROM roles r
WHERE r.guard_name = 'tenant'
  AND r.name = 'owner'
  AND r.tenant_id = @tenant_id
  AND @tenant_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM role_has_permissions x WHERE x.permission_id = @perm_id AND x.role_id = r.id
  );

INSERT INTO role_has_permissions (permission_id, role_id)
SELECT @perm_id, r.id
FROM roles r
WHERE r.guard_name = 'tenant'
  AND r.name = 'manager'
  AND r.tenant_id = @tenant_id
  AND @tenant_id IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM role_has_permissions x WHERE x.permission_id = @perm_id AND x.role_id = r.id
  );
