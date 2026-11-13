<?php

namespace App\Support\Themes;

/**
 * Demo stock imagery for {@see FancyCoffeeBrandContent::SLUG} (paths under public/).
 */
class FancyCoffeeImageCatalog
{
    public const BASE = 'images/fancy-coffee';

    /**
     * @return array<string, string> menu item slug => filename without extension
     */
    public static function menuItemFiles(): array
    {
        return [
            'espresso' => 'espresso',
            'flat-white' => 'flat-white',
            'cardamom-latte' => 'cardamom-latte',
            'arabic-coffee' => 'arabic-coffee',
            'iced-latte' => 'iced-latte',
            'cold-brew' => 'cold-brew',
            'date-maamoul' => 'date-maamoul',
            'cheese-sambusa' => 'cheese-sambusa',
        ];
    }

    /**
     * @return array<string, string> category slug => filename without extension
     */
    public static function categoryFiles(): array
    {
        return [
            'hot' => 'category-hot',
            'cold' => 'category-cold',
            'bites' => 'category-bites',
        ];
    }

    public static function path(string $basename, string $ext = 'webp'): string
    {
        return self::BASE.'/'.$basename.'.'.$ext;
    }

    public static function publicExists(?string $relativePath): bool
    {
        if ($relativePath === null || $relativePath === '') {
            return false;
        }

        return is_file(public_path($relativePath));
    }

    public static function preferWebp(string $path): string
    {
        if (str_ends_with($path, '.webp')) {
            return $path;
        }

        $webp = preg_replace('/\.jpe?g$/i', '.webp', $path) ?? $path;
        if (self::publicExists($webp)) {
            return $webp;
        }

        return $path;
    }

    public static function resolveMenuItemImage(string $itemSlug, ?string $categorySlug, ?string $stored = null): ?string
    {
        if (self::publicExists($stored)) {
            return self::preferWebp((string) $stored);
        }

        $file = self::menuItemFiles()[$itemSlug]
            ?? self::categoryFiles()[$categorySlug ?? '']
            ?? null;

        if ($file === null) {
            return null;
        }

        $webp = self::path($file);

        return self::publicExists($webp) ? $webp : null;
    }

    public static function resolveMediaKey(string $key, ?string $stored = null): ?string
    {
        $defaults = [
            'hero' => 'hero',
            'about' => 'about',
            'visit' => 'visit',
        ];

        if (self::publicExists($stored)) {
            return self::preferWebp((string) $stored);
        }

        $file = $defaults[$key] ?? null;
        if ($file === null) {
            return null;
        }

        $webp = self::path($file);

        return self::publicExists($webp) ? $webp : null;
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    public static function enrichContent(array $content): array
    {
        $media = $content['media'] ?? [];
        foreach (['hero', 'about', 'visit'] as $key) {
            $resolved = self::resolveMediaKey($key, $media[$key] ?? null);
            if ($resolved !== null) {
                $media[$key] = $resolved;
            }
        }
        $content['media'] = $media;

        $location = $content['location'] ?? [];
        $visitResolved = self::resolveMediaKey('visit', $location['image'] ?? null);
        if ($visitResolved !== null) {
            $location['image'] = $visitResolved;
        }
        $content['location'] = $location;

        if (empty($content['gallery'])) {
            $content['gallery'] = self::defaultGallery();
        } else {
            $content['gallery'] = self::normalizeGallery($content['gallery']);
        }

        if (empty($content['branches'])) {
            $content['branches'] = self::defaultBranches();
        } else {
            $content['branches'] = self::normalizeBranches($content['branches']);
        }

        if (empty($content['testimonials'])) {
            $content['testimonials'] = self::defaultTestimonials();
        }

        foreach ($content['menu_categories'] ?? [] as $ci => $category) {
            $catSlug = $category['slug'] ?? '';
            foreach ($category['items'] ?? [] as $ii => $item) {
                $slug = $item['slug'] ?? '';
                $img = self::resolveMenuItemImage($slug, $catSlug, $item['image'] ?? null);
                if ($img !== null) {
                    $content['menu_categories'][$ci]['items'][$ii]['image'] = $img;
                }
            }
        }

        return $content;
    }

    /**
     * @return list<array{image: string, alt_en: string, alt_ar: string}>
     */
    public static function defaultGallery(): array
    {
        return self::normalizeGallery([
            ['file' => 'gallery-pour', 'alt_en' => 'Freshly poured black coffee in a ceramic mug', 'alt_ar' => 'قهوة سوداء تُسكب في فنجان خزفي'],
            ['file' => 'gallery-latte-art', 'alt_en' => 'Latte with heart-shaped latte art in a glass mug', 'alt_ar' => 'لاتيه برسمة قلب في كوب زجاجي'],
            ['file' => 'gallery-beans', 'alt_en' => 'Roasted coffee beans in a wooden bowl', 'alt_ar' => 'حبوب قهوة محمصة في وعاء خشبي'],
            ['file' => 'gallery-pastries', 'alt_en' => 'Pastries and croissants on a café counter', 'alt_ar' => 'معجنات وكرواسون على طاولة المقهى'],
            ['file' => 'gallery-interior', 'alt_en' => 'Warm café seating with natural light', 'alt_ar' => 'جلسات مقهى دافئة بإضاءة طبيعية'],
            ['file' => 'gallery-team', 'alt_en' => 'Barista workspace with espresso equipment', 'alt_ar' => 'محطة تحضير مع معدات إسبريسو'],
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private static function normalizeGallery(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $file = $row['file'] ?? null;
            $image = $row['image'] ?? ($file ? self::path((string) $file) : null);
            if ($image !== null && self::publicExists($image)) {
                $out[] = [
                    'image' => self::preferWebp($image),
                    'alt_en' => (string) ($row['alt_en'] ?? 'Café photo'),
                    'alt_ar' => (string) ($row['alt_ar'] ?? 'صورة من المقهى'),
                ];
            }
        }

        return $out;
    }

    /**
     * @return list<array<string, mixed>>
     */
    /**
     * @return list<array<string, string>>
     */
    public static function defaultTestimonials(): array
    {
        return [
            [
                'quote_en' => 'The cardamom latte tastes like home, but the espresso bar could sit in any world capital.',
                'quote_ar' => 'لاتيه الهيل يذكّرنا بالبيت، ومقهى الإسبريسو بمستوى عالمي.',
                'author_en' => 'Guest review',
                'author_ar' => 'تقييم زائر',
            ],
            [
                'quote_en' => 'Warm service, beautiful space, and pastries that disappear too fast.',
                'quote_ar' => 'خدمة دافئة، أجواء رائعة، ومخبوزات تنتهي بسرعة.',
                'author_en' => 'Guest review',
                'author_ar' => 'تقييم زائر',
            ],
        ];
    }

    public static function defaultBranches(): array
    {
        return self::normalizeBranches([
            [
                'slug' => 'jeddah-main',
                'name_en' => 'Jeddah Main (sample)',
                'name_ar' => 'جدة الرئيسي (تجريبي)',
                'address_en' => 'Al Rawdah, Prince Sultan Rd. — sample address',
                'address_ar' => 'الروضة، طريق الأمير سلطان — عنوان تجريبي',
                'file' => 'branch-jeddah',
                'lat' => 21.543333,
                'lng' => 39.172778,
            ],
            [
                'slug' => 'riyadh-boulevard',
                'name_en' => 'Riyadh Boulevard (sample)',
                'name_ar' => 'الرياض بوليفارد (تجريبي)',
                'address_en' => 'Riyadh Boulevard — sample address',
                'address_ar' => 'بوليفارد الرياض — عنوان تجريبي',
                'file' => 'branch-riyadh',
                'lat' => 24.77012,
                'lng' => 46.638058,
            ],
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private static function normalizeBranches(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $file = $row['file'] ?? null;
            $image = $row['image'] ?? ($file ? self::path((string) $file) : null);
            if ($image === null || ! self::publicExists($image)) {
                continue;
            }
            $entry = [
                'slug' => $row['slug'] ?? '',
                'name_en' => (string) ($row['name_en'] ?? ''),
                'name_ar' => (string) ($row['name_ar'] ?? ''),
                'address_en' => (string) ($row['address_en'] ?? ''),
                'address_ar' => (string) ($row['address_ar'] ?? ''),
                'image' => self::preferWebp($image),
                'alt_en' => (string) ($row['alt_en'] ?? 'Café branch interior'),
                'alt_ar' => (string) ($row['alt_ar'] ?? 'داخل فرع المقهى'),
            ];
            if (isset($row['lat'], $row['lng']) && is_numeric($row['lat']) && is_numeric($row['lng'])) {
                $entry['lat'] = (float) $row['lat'];
                $entry['lng'] = (float) $row['lng'];
            }
            $out[] = $entry;
        }

        return $out;
    }
}
