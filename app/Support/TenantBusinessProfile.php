<?php

namespace App\Support;

use App\Models\BusinessSetting;

/**
 * Canonical read/write paths for tenant business profile stored under settings.business,
 * synchronized with legacy legal_entity / content.* keys by TenantBusinessSettingsService.
 */
final class TenantBusinessProfile
{
  /**
   * @return array<string, mixed>
   */
  public static function contentSlice(Tenant $tenant): array
  {
      return $tenant->isCafeTheme()
          ? (array) data_get($tenant->settings, 'content.cafe', [])
          : (array) data_get($tenant->settings, 'content.plumber', []);
  }

  public static function contentKey(Tenant $tenant): string
  {
      return $tenant->isCafeTheme() ? 'content.cafe' : 'content.plumber';
  }

  /**
   * @return array<string, string>
   */
  public static function brandColorOptions(Tenant $tenant): array
  {
      $colors = $tenant->themeColors();
      $labels = $tenant->isCafeTheme()
          ? [
              'espresso' => __('business.colors.espresso'),
              'caramel' => __('business.colors.caramel'),
              'accent' => __('business.colors.accent'),
              'mocha' => __('business.colors.mocha'),
          ]
          : [
              'navy' => __('business.colors.navy'),
              'steel' => __('business.colors.steel'),
              'safety' => __('business.colors.safety'),
              'accent' => __('business.colors.accent'),
          ];

      $out = [];
      foreach ($labels as $key => $label) {
          if (isset($colors[$key])) {
              $out[$key] = $label;
          }
      }

      return $out;
  }

  public static function primaryColorKey(Tenant $tenant): string
  {
      return $tenant->isCafeTheme() ? 'espresso' : 'navy';
  }

    /**
     * @return list<array{key: string, label: string, closed: bool, open: string, close: string}>
     */
    public static function defaultWeekdays(): array
    {
        $keys = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        $out = [];
        foreach ($keys as $key) {
            $out[] = [
                'key' => $key,
                'closed' => $key === 'friday',
                'open' => $key === 'friday' ? '14:00' : '07:00',
                'close' => '23:00',
            ];
        }

        return $out;
    }

    public static function scalarString(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? '1' : '';
        }
        if (is_scalar($value)) {
            return (string) $value;
        }

        return '';
    }

  /**
   * @param  array<string, mixed>  $raw
   * @return array<string, string>
   */
  public static function normalizeContactBlock(array $raw): array
  {
      $keys = ['phone', 'whatsapp', 'email', 'website', 'instagram', 'x', 'tiktok', 'snapchat', 'maps_url', 'lat', 'lng'];
      $out = [];
      foreach ($keys as $key) {
          if (! array_key_exists($key, $raw)) {
              continue;
          }
          $value = $raw[$key];
          if (in_array($key, ['lat', 'lng'], true) && is_numeric($value)) {
              $out[$key] = (string) round((float) $value, 6);
          } else {
              $out[$key] = self::scalarString($value);
          }
      }

      return $out;
  }

  /**
   * @param  mixed  $raw
   * @return list<array{key: string, closed: bool, open: string, close: string}>
   */
  public static function normalizeWeekdays(mixed $raw): array
  {
      if (! is_array($raw)) {
          return self::defaultWeekdays();
      }

      $defaults = self::defaultWeekdays();
      $byKey = [];
      foreach ($defaults as $row) {
          $byKey[$row['key']] = $row;
      }

      foreach ($raw as $item) {
          if (! is_array($item)) {
              continue;
          }
          $key = self::scalarString($item['key'] ?? '');
          if ($key === '' || ! isset($byKey[$key])) {
              continue;
          }
          $closed = filter_var($item['closed'] ?? false, FILTER_VALIDATE_BOOLEAN);
          $open = self::scalarString($item['open'] ?? $byKey[$key]['open']);
          $close = self::scalarString($item['close'] ?? $byKey[$key]['close']);
          if ($open === '') {
              $open = $byKey[$key]['open'];
          }
          if ($close === '') {
              $close = $byKey[$key]['close'];
          }
          $byKey[$key] = [
              'key' => $key,
              'closed' => $closed,
              'open' => $open,
              'close' => $close,
          ];
      }

      return array_values($byKey);
  }

  /**
   * Pull contact fields from content slice (including map coords on location).
   *
   * @param  array<string, mixed>  $content
   * @return array<string, string>
   */
  public static function contactFromContent(array $content): array
  {
      $contact = self::normalizeContactBlock((array) ($content['contact'] ?? []));
      $location = (array) ($content['location'] ?? []);
      if (($contact['lat'] ?? '') === '' && isset($location['lat']) && is_numeric($location['lat'])) {
          $contact['lat'] = (string) round((float) $location['lat'], 6);
      }
      if (($contact['lng'] ?? '') === '' && isset($location['lng']) && is_numeric($location['lng'])) {
          $contact['lng'] = (string) round((float) $location['lng'], 6);
      }

      return $contact;
  }
}
