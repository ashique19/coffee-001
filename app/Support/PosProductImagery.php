<?php

namespace App\Support;

use App\Support\Themes\FancyCoffeeImageCatalog;

/**
 * Maps demo POS SKUs / category slugs to public storefront image paths.
 */
final class PosProductImagery
{
    /** @var array<string, string> */
    private const SKU_FILES = [
        'LATTE' => 'cardamom-latte',
        'ESP' => 'espresso',
        'FLAT' => 'flat-white',
        'ARABIC' => 'arabic-coffee',
        'ICED' => 'iced-latte',
        'COLD' => 'cold-brew',
        'MAAMOUL' => 'date-maamoul',
        'SAMBUSA' => 'cheese-sambusa',
    ];

    /** @var array<string, string> */
    private const CATEGORY_ICONS = [
        'hot' => 'fire',
        'cold' => 'snow',
        'bites' => 'cookie',
        'drinks' => 'cup',
    ];

    public static function relativePathForSku(?string $sku, ?string $stored = null): ?string
    {
        if (is_string($stored) && $stored !== '' && FancyCoffeeImageCatalog::publicExists($stored)) {
            return FancyCoffeeImageCatalog::preferWebp($stored);
        }

        if ($sku === null || $sku === '') {
            return null;
        }

        $file = self::SKU_FILES[strtoupper($sku)] ?? null;
        if ($file === null) {
            return null;
        }

        $menuPath = FancyCoffeeImageCatalog::BASE.'/menu/'.$file.'.webp';
        if (FancyCoffeeImageCatalog::publicExists($menuPath)) {
            return $menuPath;
        }

        $path = FancyCoffeeImageCatalog::path($file);
        if (! FancyCoffeeImageCatalog::publicExists($path)) {
            return null;
        }

        return FancyCoffeeImageCatalog::preferWebp($path);
    }

    public static function imageForSku(?string $sku, ?string $stored = null): ?string
    {
        $relative = self::relativePathForSku($sku, $stored);

        return $relative !== null ? asset($relative) : null;
    }

    public static function assetFromRelative(?string $path): ?string
    {
        if (! is_string($path) || $path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '//')) {
            return $path;
        }

        return asset($path);
    }

    public static function iconForCategory(?string $iconKey, string $nameEn): string
    {
        if ($iconKey !== null && $iconKey !== '') {
            return $iconKey;
        }

        $slug = strtolower($nameEn);
        foreach (self::CATEGORY_ICONS as $needle => $icon) {
            if (str_contains($slug, $needle)) {
                return $icon;
            }
        }

        return 'cup';
    }
}
