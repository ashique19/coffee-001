<?php

namespace App\Support\Storefront;

use App\Models\BusinessSetting;
use App\Support\EnabledLocales;

final class StorefrontLocales
{
    /**
     * Locales shown in the plumber storefront header language control.
     *
     * @return list<string>
     */
    public static function plumberNavCodes(?BusinessSetting $tenant = null): array
    {
        $tenant ??= self::tenantFromRequest();

        if ($tenant instanceof BusinessSetting) {
            return self::enabledCodesForBusinessSetting($tenant);
        }

        return collect(EnabledLocales::codes())
            ->filter(fn (string $code) => self::hasPlumberTranslations($code))
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    public static function enabledCodes(): array
    {
        return collect(EnabledLocales::codes())
            ->filter(fn (string $code) => self::hasStorefrontTranslations($code))
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    public static function enabledCodesForBusinessSetting(BusinessSetting $tenant): array
    {
        return collect(EnabledLocales::codes())
            ->filter(function (string $code) use ($tenant) {
                if ($tenant->isPlumberTheme()) {
                    return self::hasPlumberTranslations($code);
                }

                if ($tenant->isCafeTheme()) {
                    return self::hasCafeTranslations($code);
                }

                return self::hasStorefrontTranslations($code);
            })
            ->values()
            ->all();
    }

    public static function hasCafeTranslations(string $locale): bool
    {
        return is_file(lang_path("{$locale}/cafe.php"));
    }

    public static function hasPlumberTranslations(string $locale): bool
    {
        return is_file(lang_path("{$locale}/plumber.php"));
    }

    public static function hasStorefrontTranslations(string $locale): bool
    {
        return self::hasCafeTranslations($locale) || self::hasPlumberTranslations($locale);
    }

    public static function defaultLocale(BusinessSetting $tenant): string
    {
        $fallback = (string) config('locales.default', 'en');
        $preferred = (string) data_get($tenant->settings, 'default_locale', $fallback);
        $enabled = self::enabledCodesForBusinessSetting($tenant);

        $resolvedPreferred = EnabledLocales::resolve($preferred, $enabled[0] ?? $fallback);

        if (in_array($resolvedPreferred, $enabled, true)) {
            return $resolvedPreferred;
        }

        return $enabled[0] ?? $fallback;
    }

    public static function hreflang(string $locale): string
    {
        return str_replace('_', '-', $locale);
    }

    private static function tenantFromRequest(): ?BusinessSetting
    {
        $tenant = request()->route('tenant');
        if ($tenant instanceof BusinessSetting) {
            return $tenant;
        }
        if (is_string($tenant) && $tenant !== '') {
            return BusinessSetting::query()->where('slug', $tenant)->first();
        }

        return null;
    }
}
