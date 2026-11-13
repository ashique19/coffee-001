<?php

namespace App\Http\Controllers\Storefront;

use App\Business\BusinessContext;
use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Support\Storefront\StorefrontLocales;
use App\Support\BusinessSettingDisplayName;
use Illuminate\Http\JsonResponse;

class WebManifestController extends Controller
{
  public function __invoke(): JsonResponse
  {
    $business = BusinessContext::get() ?? BusinessSetting::singleton();
    abort_unless($business->hasThemedStorefront(), 404);

    $locale = app()->getLocale();
    $name = BusinessSettingDisplayName::for($business, $locale);
    $colors = $business->themeColors();
    $logo = data_get($business->settings, 'theme.logo_url');
    $favicon = data_get($business->settings, 'business.brand.favicon_path');

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

    $background = $colors['cream'] ?? '#FAF6F0';
    $theme = $colors['espresso'] ?? '#2C1810';

    return response()->json([
      'name' => $name,
      'short_name' => mb_strlen($name) > 12 ? mb_substr($name, 0, 12) : $name,
      'start_url' => '/',
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
