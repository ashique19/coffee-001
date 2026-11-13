# cPanel deploy — Fancy Coffee (single client)

## Pull & dependencies

```bash
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci && npm run build
```

## Permissions (cPanel)

```bash
chmod -R ug+rwx storage bootstrap/cache
find storage -type d -exec chmod 775 {} \;
find storage -type f -exec chmod 664 {} \;
```

## Environment keys (no values)

Copy `.env.example` to `.env` and set:

- `APP_NAME`
- `APP_ENV`
- `APP_KEY`
- `APP_DEBUG`
- `APP_URL`
- `APP_LOCALE`
- `APP_ENABLED_LOCALES`
- `DB_CONNECTION`
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`
- `SESSION_DRIVER`
- `QUEUE_CONNECTION`
- `CACHE_STORE`
- `ADMIN_EMAIL`
- `ADMIN_PASSWORD`
- `ZHEART_BASE_URL`
- `ZHEART_API_KEY`
- `ZHEART_SUBMIT_TO_ZATCA`
- `ZHEART_TIMEOUT_SIGN`
- `ZHEART_TIMEOUT_SUBMIT`
- `ZHEART_TIMEOUT_HEALTH`
- `ZHEART_RETRY_TIMES`
- `ZHEART_RETRY_SLEEP_MS`
- `ZHEART_ALLOW_SELLER_OVERRIDE`
- `MAIL_*` (as needed)

## Optimize & migrate

```bash
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Point the domain document root to `public/`. Schedule cron for `php artisan schedule:run` if you enable queued jobs.
