-- Storefront SEO & tenant analytics (2026-11-09)
-- No relational schema change: settings live in tenants.settings JSON.
--
-- After git pull, tenant owners configure:
--   settings.storefront.ga_measurement_id
--   settings.storefront.google_site_verification
--   settings.storefront.bing_site_verification
--
-- Idempotent: safe to run; does not ALTER tables.
SELECT 1;
