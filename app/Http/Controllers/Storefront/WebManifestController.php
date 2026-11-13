<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Support\Storefront\StorefrontLocales;
use App\Support\TenantDisplayName;
use Illuminate\Http\JsonResponse;

class WebManifestController extends Controller
{
    public function __invoke(Tenant $tenant): JsonResponse
    {
        abort_unless($tenant->hasThemedStorefront(), 404);

        $locale = app()->getLocale();
        $name = TenantDisplayName::for($tenant, $locale);
        $colors = $tenant->themeColors();
        $logo = data_get($tenant->settings, 'theme.logo_url');
        $favicon = data_get($tenant->settings, 'business.brand.favicon_path');

        $icons = [];
        foreach ([$favicon, $logo] as $src) {
            if (! is_string($src) || $src === '') {
                continue;
            }
            $icons[] = [
                'src' => asset($src),
                'sizes' => str_ends_with($src, 'favicon.png') ? '32x32' : '192x192',
                'type' => str_ends_with($src, '.svg') ? 'image/svg+xml' : 'image/png',
                'purpose' => 'any',
            ];
            break;
        }

        if ($tenant->isPlumberTheme()) {
            $background = $colors['ice'] ?? '#F4F8FC';
            $theme = $colors['navy'] ?? '#0B1F3A';
        } else {
            $background = $colors['cream'] ?? '#FAF6F0';
            $theme = $colors['espresso'] ?? '#2C1810';
        }

        return response()->json([
            'name' => $name,
            'short_name' => mb_strlen($name) > 12 ? mb_substr($name, 0, 12) : $name,
            'start_url' => '/'.$tenant->slug,
            'display' => 'standalone',
            'background_color' => $background,
            'theme_color' => $theme,
            'lang' => StorefrontLocales::hreflang($locale),
            'icons' => $icons,
        ], 200, [
            'Content-Type' => 'application/manifest+json',
        ]);
    }
}
