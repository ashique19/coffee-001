<?php

namespace App\Support\Storefront;

use App\Models\BusinessSetting;
use App\Support\BusinessSettingDisplayName;
use Illuminate\Support\Facades\URL;

final class StorefrontSeo
{
    public const PAGE_HOME = 'home';

    public const PAGE_MENU = 'menu';

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    public static function forPage(
        BusinessSetting $tenant,
        string $page,
        string $locale,
        array $content,
    ): array {
        $displayName = BusinessSettingDisplayName::for($tenant, $locale);
        $title = self::pageTitle($page, $displayName, $locale);
        $description = self::pageDescription($page, $content, $locale);
        $canonical = self::absoluteUrl($tenant, $page, $locale);
        $ogImage = self::ogImage($content);
        $alternate = self::hreflangAlternates($tenant, $page);
        $jsonLd = self::jsonLd($tenant, $locale, $content, $canonical, $ogImage);
        $robots = 'index,follow,max-image-preview:large';

        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $canonical,
            'robots' => $robots,
            'og' => [
                'type' => 'website',
                'title' => $title,
                'description' => $description,
                'url' => $canonical,
                'image' => $ogImage,
                'locale' => StorefrontLocales::hreflang($locale),
                'site_name' => $displayName,
            ],
            'twitter' => [
                'card' => 'summary_large_image',
                'title' => $title,
                'description' => $description,
                'image' => $ogImage,
            ],
            'hreflang' => $alternate,
            'json_ld' => $jsonLd,
            'theme_color' => self::themeColor($tenant),
            'manifest_url' => route('storefront.manifest'),
            ...StorefrontAnalytics::forBusinessSetting($tenant),
        ];
    }

    public static function pageTitle(string $page, string $displayName, string $locale): string
    {
        if ($page === self::PAGE_MENU) {
            return $displayName.' — '.__('cafe.menu_title', [], $locale);
        }

        return $displayName.' — '.__('cafe.meta_suffix', [], $locale);
    }

    /**
     * @param  array<string, mixed>  $content
     */
    public static function pageDescription(string $page, array $content, string $locale): string
    {
        $rtl = BusinessSettingDisplayName::usesRtlName($locale);
        $tagline = $rtl ? (string) ($content['tagline_ar'] ?? '') : (string) ($content['tagline_en'] ?? '');

        if ($page === self::PAGE_MENU) {
            $menuIntro = __('cafe.seo_menu_description', [], $locale);
            if ($tagline !== '') {
                return $tagline.' '.$menuIntro;
            }

            return $menuIntro;
        }

        if ($tagline !== '') {
            return $tagline;
        }

        return __('cafe.meta_description', [], $locale);
    }

    public static function absoluteUrl(BusinessSetting $tenant, string $page, string $locale): string
    {
        $route = $page === self::PAGE_MENU ? 'cafe.menu' : 'cafe.home';
        $default = StorefrontLocales::defaultLocale($tenant);
        $params = ['tenant' => $tenant];
        if ($locale !== $default) {
            $params['locale'] = $locale;
        }

        return URL::to(route($route, $params, absolute: false));
    }

    /**
     * @param  array<string, mixed>  $content
     */
    public static function ogImage(array $content): string
    {
        $hero = data_get($content, 'media.hero');
        if (is_string($hero) && $hero !== '') {
            return asset($hero);
        }

        return asset('images/fancy-coffee/hero.webp');
    }

    /**
     * @return list<array{hreflang: string, href: string}>
     */
    public static function hreflangAlternates(BusinessSetting $tenant, string $page): array
    {
        $default = StorefrontLocales::defaultLocale($tenant);
        $rows = [];
        foreach (StorefrontLocales::enabledCodes() as $code) {
            $rows[] = [
                'hreflang' => StorefrontLocales::hreflang($code),
                'href' => self::absoluteUrl($tenant, $page, $code),
            ];
        }
        $rows[] = [
            'hreflang' => 'x-default',
            'href' => self::absoluteUrl($tenant, $page, $default),
        ];

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    public static function jsonLd(BusinessSetting $tenant, string $locale, array $content, string $url, string $image): array
    {
        $legal = (array) data_get($tenant->settings, 'legal_entity', []);
        $name = BusinessSettingDisplayName::for($tenant, $locale);
        $useRtl = BusinessSettingDisplayName::usesRtlName($locale);
        $address = $useRtl
            ? (string) ($legal['address_ar'] ?? $legal['address'] ?? '')
            : (string) ($legal['address'] ?? $legal['address_ar'] ?? '');

        $graph = [
            '@context' => 'https://schema.org',
            '@type' => 'CafeOrCoffeeShop',
            'name' => $name,
            'url' => $url,
            'image' => $image,
        ];

        if ($address !== '') {
            $graph['address'] = [
                '@type' => 'PostalAddress',
                'streetAddress' => $address,
                'addressCountry' => 'SA',
            ];
        }

        $phone = (string) data_get($content, 'contact.phone', '');
        if ($phone !== '') {
            $graph['telephone'] = $phone;
        }

        $email = (string) data_get($content, 'contact.email', '');
        if ($email !== '') {
            $graph['email'] = $email;
        }

        $hoursText = self::hoursSummary($content, $locale);
        if ($hoursText !== '') {
            $graph['description'] = $hoursText;
        }

        $lat = data_get($content, 'location.lat');
        $lng = data_get($content, 'location.lng');
        if (is_numeric($lat) && is_numeric($lng)) {
            $graph['geo'] = [
                '@type' => 'GeoCoordinates',
                'latitude' => (float) $lat,
                'longitude' => (float) $lng,
            ];
        }

        return $graph;
    }

    public static function themeColor(BusinessSetting $tenant): string
    {
        $colors = $tenant->themeColors();
        $key = $tenant->isCafeTheme() ? 'espresso' : 'navy';

        return (string) ($colors[$key] ?? $colors['espresso'] ?? '#2C1810');
    }

    /**
     * @param  array<string, mixed>  $content
     */
    private static function hoursSummary(array $content, string $locale): string
    {
        $rtl = BusinessSettingDisplayName::usesRtlName($locale);
        $parts = [];
        foreach ($content['hours'] ?? [] as $row) {
            $days = $rtl ? (string) ($row['label_ar'] ?? '') : (string) ($row['label_en'] ?? '');
            $hours = $rtl ? (string) ($row['value_ar'] ?? '') : (string) ($row['value_en'] ?? '');
            if ($days === '' || $hours === '') {
                continue;
            }
            $parts[] = $days.': '.$hours;
        }

        return implode(' · ', $parts);
    }
}
