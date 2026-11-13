<?php

namespace App\Support\Themes;

use App\Models\BusinessSetting;

final class ThemeStorefrontThumbnail
{
    public static function urlFor(Tenant $tenant): ?string
    {
        $path = match (true) {
            $tenant->isCafeTheme() => 'images/templates/cafe-storefront.webp',
            $tenant->isPlumberTheme() => 'images/templates/plumber-storefront.webp',
            default => null,
        };

        return $path !== null ? asset($path) : null;
    }

    public static function altFor(Tenant $tenant, string $locale): string
    {
        $name = $tenant->displayName($locale);

        return match (true) {
            $tenant->isCafeTheme() => __('invoicing.thumbnail_alt_cafe', ['name' => $name], $locale),
            $tenant->isPlumberTheme() => __('invoicing.thumbnail_alt_plumber', ['name' => $name], $locale),
            default => $name,
        };
    }

    public static function themeLabel(Tenant $tenant): string
    {
        return match (true) {
            $tenant->isCafeTheme() => __('invoicing.tenant_theme_cafe'),
            $tenant->isPlumberTheme() => __('invoicing.tenant_theme_plumber'),
            default => __('invoicing.tenant_theme_default'),
        };
    }
}
