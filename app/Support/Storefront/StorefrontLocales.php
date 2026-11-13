<?php

namespace App\Support\Storefront;

use App\Models\BusinessSetting;
use App\Support\EnabledLocales;

final class StorefrontLocales
{
  /**
   * @return list<string>
   */
  public static function enabledCodes(): array
  {
    return collect(EnabledLocales::codes())
      ->filter(fn (string $code) => self::hasCafeTranslations($code))
      ->values()
      ->all();
  }

  /**
   * @return list<string>
   */
  public static function enabledCodesForBusinessSetting(BusinessSetting $business): array
  {
    return collect(EnabledLocales::codes())
      ->filter(fn (string $code) => self::hasCafeTranslations($code))
      ->values()
      ->all();
  }

  public static function hasCafeTranslations(string $locale): bool
  {
    return is_file(lang_path("{$locale}/cafe.php"));
  }

  public static function defaultLocale(BusinessSetting $business): string
  {
    $fallback = (string) config('locales.default', 'en');
    $preferred = (string) data_get($business->settings, 'default_locale', $fallback);
    $enabled = self::enabledCodesForBusinessSetting($business);

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
}
