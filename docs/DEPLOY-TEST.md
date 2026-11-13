# Staging deploy (manual)

For **https://zatca.taskeenlab.duckdns.org** (or your host). No production ZATCA URLs in default config.

Tenant ZATCA credentials (per-tenant, per EGS device): `/app/{tenant-slug}/zatca` — see [ZATCA-ADMIN.md](./ZATCA-ADMIN.md).

Deploy artifact: **zip without `.env`** — copy `.env.example` locally on the server.

## One-shot production SQL (cPanel / phpMyAdmin)

After `git pull`, when you **cannot** run `php artisan migrate` reliably, import **one** file:

**`docs/sql/2026-11-12-combined-release.sql`**

It is idempotent and applies, in order: Fancy Coffee menu restore, ingredient inventory schema/permissions, demo-plumber greenfield tenant data, cashier RBAC fix, and matching `migrations` ledger rows. Re-run is safe.

If you already imported the individual patches, you may skip this combined file. For an existing `demo-plumber` deploy that still uses legacy LEAK/FILT SKUs, also run **`docs/sql/2026-11-11-plumber-job-catalog.sql`** after the combined file.

Regenerate locally: `php scripts/generate-combined-release-sql.php`

**Signing driver (`.env`):** `ZATCA_DRIVER=local` (default; Fancy Coffee) or `zheart` after onboarding. Optional per-tenant override in platform admin → **Z-Heart / signing**. Set `ZHEART_BASE_URL` and `ZHEART_API_KEY` (or per-tenant key only).

## Demo plumber (cPanel / phpMyAdmin — no artisan)

Production cPanel is **`git pull` only**.

**Greenfield (no `demo-plumber` tenant yet):** run **`docs/sql/2026-11-11-demo-plumber-tenant.sql`** (tenant, users, roles, baseline catalog).

**Existing deploy / job invoices + parts:** run **`docs/sql/2026-11-11-plumber-job-catalog.sql`** (adds `products.item_type`, job SKUs, staff roles, Spatie cache clear). Idempotent — only touches `demo-plumber`, not `fancy-coffee`.

After either script, if **`CACHE_STORE=file`**, delete files under `storage/framework/cache/data/` (not `bootstrap/cache`).

**Local / dev (optional):** `php artisan migrate` then `php artisan demo:seed-plumber`.

| Account | Email | Password (placeholder — change on server) |
|---------|-------|-------------------------------------------|
| Tenant owner | `owner@demo-plumber.test` | `TestPassword!2026` |
| Branch technician (POS) | `technician@demo-plumber.test` | `TestPassword!2026` |

Tenant slug: **`demo-plumber`**. Public site is **service-led** (`/demo-plumber/`, `/demo-plumber/services`). Parts are added on the **job** in POS. Staff login: `/app/demo-plumber/login`. Default invoice type is **standard**. ZATCA integration stays **off** until enabled in admin.

## Demo / import accounts (after `php artisan demo:seed-fancy-coffee`)

| Account | Email | Password (placeholder — change on server) |
|---------|-------|-------------------------------------------|
| Platform super-admin | `admin@taskeenlab.test` | `TestPassword!2026` |
| Tenant owner | `owner@fancy-coffee.test` | `TestPassword!2026` |
| Branch cashier | `cashier@fancy-coffee.test` | `TestPassword!2026` |

Tenant slug: `fancy-coffee`. No real ZATCA keys; `ZATCA_API_ENABLED=false` by default.

The **Fancy Coffee** storefront uses polished demo copy, Unsplash photography under `public/images/fancy-coffee/`, and a fuller menu layout. After deploy, run `php artisan demo:seed-fancy-coffee` once to refresh tenant JSON (or re-import an updated SQL dump).

## 1. Copy application

Production **cPanel** deploy is **`git pull` only** (no SSH, no npm, no Composer on the server). The repo ships **`public/build/`** (`npm ci && npm run build` — commit when CSS/JS changes). **`vendor/` is not in git** — when `composer.lock` changes, build `vendor/` locally with `composer install --no-dev --optimize-autoloader` (PHP 8.4) and upload/extract a **vendor zip** on the server. Never commit `.env`.

## 2. Environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env`: real `APP_KEY` (from generate), database credentials, `APP_URL` matching your public URL.

### Staging `.env` (no SSH — use file manager)

```env
APP_URL=https://zatca.taskeenlab.duckdns.org
APP_ENV=staging
APP_DEBUG=false

SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
SESSION_SECURE_COOKIE=true
```

Ensure `storage/` and `bootstrap/cache/` are writable (755 or 775). Wrong `APP_URL` or session driver causes **419** on login or “logged out” after sign-in.

### “Tenant authentication required” / auth errors

- Open **staff login first**: `/app/fancy-coffee/login` (not `/app/fancy-coffee/pos` while logged out).
- Credentials: `cashier@fancy-coffee.test` / `TestPassword!2026` (after demo SQL import).
- If login spins or **419**: fix `APP_URL`, use `SESSION_DRIVER=file`, clear `bootstrap/cache/config.php` if present.
- **403** with platform account on tenant URLs: use tenant staff login only.

## 3. Laravel setup

```bash
php artisan migrate --force
php artisan storage:link
chmod -R ug+rwx storage bootstrap/cache
```

## 4. Web root

Point the vhost **document root** at `public/` (not the repo root).

## 5. Queue & scheduler

```bash
php artisan queue:work --sleep=3 --tries=3
# cron: * * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
```

## 6. Smoke checks

- `GET /up` health
- Café: `/{tenant}/`
- Tenant login: `/app/{tenant}/login`
- POS: `/app/{tenant}/pos` after seeding catalog and provisioning branch/cashier
