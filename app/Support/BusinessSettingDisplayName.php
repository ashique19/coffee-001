<?php

namespace App\Support;

use App\Models\BusinessSetting;

final class BusinessSettingDisplayName
{
    /**
     * Locale-aware legal / brand name for public café surfaces and tenant auth.
     * Empty strings are skipped so a missing Arabic (or Bangla) name falls back
     * to another filled name rather than leaving document titles as "— page".
     */
    public static function for(BusinessSetting $tenant, ?string $locale = null): string
    {
        $locale ??= app()->getLocale();
        $brand = data_get($tenant->settings, 'business.brand', []);
        $legal = data_get($tenant->settings, 'legal_entity', []);

        $candidates = match (true) {
            $locale === 'bn' => [
                $brand['display_name_bn'] ?? null,
                $legal['name_bn'] ?? null,
                $brand['display_name_en'] ?? null,
                $legal['name_en'] ?? null,
                $brand['display_name_ar'] ?? null,
                $legal['name_ar'] ?? null,
                $tenant->name,
            ],
            self::usesRtlName($locale) => [
                $brand['display_name_ar'] ?? null,
                $legal['name_ar'] ?? null,
                $brand['display_name_en'] ?? null,
                $legal['name_en'] ?? null,
                $tenant->name,
            ],
            default => [
                $brand['display_name_en'] ?? null,
                $legal['name_en'] ?? null,
                $brand['display_name_bn'] ?? null,
                $legal['name_bn'] ?? null,
                $brand['display_name_ar'] ?? null,
                $legal['name_ar'] ?? null,
                $tenant->name,
            ],
        };

        foreach ($candidates as $value) {
            if (filled($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return (string) (config('app.name') ?: 'Fancy Coffee');
    }

    public static function usesRtlName(?string $locale = null): bool
    {
        return in_array($locale ?? app()->getLocale(), ['ar', 'ur'], true);
    }
}
