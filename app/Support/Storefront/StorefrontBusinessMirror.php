<?php

namespace App\Support\Storefront;

use App\Models\BusinessSetting;
use App\Support\TenantBusinessHours;
use App\Support\TenantBusinessProfile;

/**
 * Publishes settings.business onto legacy content.* blocks used by storefront views.
 */
final class StorefrontBusinessMirror
{
    /**
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    public static function overlay(BusinessSetting $tenant, array $content): array
    {
        $settings = $tenant->settings ?? BusinessSetting::defaultSettings();
        $business = (array) data_get($settings, 'business', []);

        $hours = (array) ($business['hours'] ?? []);
        $weekdays = TenantBusinessProfile::normalizeWeekdays((array) ($hours['weekdays'] ?? []));
        if ($weekdays !== []) {
            $note = TenantBusinessProfile::scalarString($hours['note'] ?? '');
            $content['hours'] = TenantBusinessHours::toLegacyRows(
                $weekdays,
                filled($note) ? $note : null,
            );
        }

        $contact = (array) ($business['contact'] ?? []);
        $legacyContact = (array) ($content['contact'] ?? []);
        foreach (['phone', 'whatsapp', 'email', 'instagram'] as $key) {
            if (array_key_exists($key, $contact) && (string) $contact[$key] !== '') {
                $legacyContact[$key] = (string) $contact[$key];
            }
        }
        if ($legacyContact !== []) {
            $content['contact'] = $legacyContact;
        }

        $lat = TenantBusinessProfile::scalarString($contact['lat'] ?? '');
        $lng = TenantBusinessProfile::scalarString($contact['lng'] ?? '');
        if ($lat !== '' && $lng !== '' && is_numeric($lat) && is_numeric($lng)) {
            $content['location'] = array_merge((array) ($content['location'] ?? []), [
                'lat' => round((float) $lat, 6),
                'lng' => round((float) $lng, 6),
                'map_placeholder' => false,
            ]);
        }

        return $content;
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public static function persistToContent(BusinessSetting $tenant, array &$settings): void
    {
        $business = (array) data_get($settings, 'business', []);
        $contentKey = 'content.cafe';
        $content = (array) data_get($settings, $contentKey, []);

        $hours = (array) ($business['hours'] ?? []);
        $weekdays = TenantBusinessProfile::normalizeWeekdays((array) ($hours['weekdays'] ?? []));
        if ($weekdays !== []) {
            $note = TenantBusinessProfile::scalarString($hours['note'] ?? '');
            $content['hours'] = TenantBusinessHours::toLegacyRows(
                $weekdays,
                filled($note) ? $note : null,
            );
        }

        $contact = (array) ($business['contact'] ?? []);
        $legacyContact = (array) ($content['contact'] ?? []);
        foreach (['phone', 'whatsapp', 'email', 'instagram'] as $key) {
            if (array_key_exists($key, $contact)) {
                $legacyContact[$key] = (string) $contact[$key];
            }
        }
        $content['contact'] = $legacyContact;

        $lat = TenantBusinessProfile::scalarString($contact['lat'] ?? '');
        $lng = TenantBusinessProfile::scalarString($contact['lng'] ?? '');
        if ($lat !== '' && $lng !== '' && is_numeric($lat) && is_numeric($lng)) {
            $content['location'] = array_merge((array) ($content['location'] ?? []), [
                'lat' => round((float) $lat, 6),
                'lng' => round((float) $lng, 6),
                'map_placeholder' => false,
            ]);
        }

        data_set($settings, $contentKey, $content);
    }
}
