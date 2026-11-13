<?php

namespace App\Support\Themes;

/**
 * Polished go-live demo storefront content for tenant slug {@see fancy-coffee}.
 */
class FancyCoffeeBrandContent
{
    public const SLUG = 'fancy-coffee';

    /**
     * @return array<string, mixed>
     */
    public static function cafeContent(): array
    {
        $base = CafeThemePreset::demoCafeContent('Fancy Coffee', 'فانسي كوفي');

        $base['sales_demo'] = true;

        $base['tagline_en'] = 'Roasted in Jeddah. Served with Saudi warmth.';
        $base['tagline_ar'] = 'محمصة في جدة. نقدّمها بضيافة سعودية أصيلة.';

        $base['location_badge_en'] = 'Jeddah & Riyadh, Saudi Arabia';
        $base['location_badge_ar'] = 'جدة والرياض، المملكة العربية السعودية';

        $base['about_en'] = 'Fancy Coffee is a specialty roastery and neighbourhood café built for guests who love Arabic gahwa and third-wave espresso in equal measure. We source seasonal lots, roast in small batches at our Al Rawdah facility, and train every barista on consistency, speed, and hospitality.';
        $base['about_ar'] = 'فانسي كوفي مقهى ومحمّصة متخصصة لعشّاق القهوة العربية والإسبريسو الحديث. نختار حبوباً موسمية، نحمّصها على دفعات صغيرة في الروضة بجدة، ونُعد كل كوب بمعايير ثابتة وخدمة سعودية دافئة.';

        $base['highlights'] = [
            [
                'value_en' => '2019',
                'value_ar' => '٢٠١٩',
                'label_en' => 'Roasting since',
                'label_ar' => 'نحمّص منذ',
            ],
            [
                'value_en' => '12+',
                'value_ar' => '+١٢',
                'label_en' => 'Single origins',
                'label_ar' => 'أصول مختصة',
            ],
            [
                'value_en' => '2',
                'value_ar' => '٢',
                'label_en' => 'Saudi branches',
                'label_ar' => 'فروع في السعودية',
            ],
        ];

        $base['media'] = [
            'hero' => FancyCoffeeImageCatalog::path('hero'),
            'about' => FancyCoffeeImageCatalog::path('about'),
            'visit' => FancyCoffeeImageCatalog::path('visit'),
        ];

        $base['location'] = [
            'map_placeholder' => false,
            'lat' => 21.5433,
            'lng' => 39.1728,
            'image' => FancyCoffeeImageCatalog::path('visit'),
            'note_en' => 'Free guest Wi‑Fi · Family seating · Drive-through at Jeddah Main',
            'note_ar' => 'واي فاي للزوار · جلسات عائلية · خدمة سيارات في فرع جدة الرئيسي',
        ];

        $base['contact'] = [
            'phone' => '+966 12 655 0100',
            'whatsapp' => '+966 55 010 4422',
            'email' => 'hello@fancycoffee.sa',
            'instagram' => '@fancycoffee.sa',
        ];

        $base['hours'] = [
            [
                'label_en' => 'Jeddah — Sat – Thu',
                'label_ar' => 'جدة — السبت – الخميس',
                'value_en' => '6:30 AM – 12:00 AM',
                'value_ar' => '٦:٣٠ ص – ١٢:٠٠ ص',
            ],
            [
                'label_en' => 'Jeddah — Friday',
                'label_ar' => 'جدة — الجمعة',
                'value_en' => '2:00 PM – 12:00 AM',
                'value_ar' => '٢:٠٠ م – ١٢:٠٠ ص',
            ],
            [
                'label_en' => 'Riyadh Boulevard — Daily',
                'label_ar' => 'الرياض بوليفارد — يومياً',
                'value_en' => '8:00 AM – 11:00 PM',
                'value_ar' => '٨:٠٠ ص – ١١:٠٠ م',
            ],
        ];

        $base['gallery'] = FancyCoffeeImageCatalog::defaultGallery();
        $base['branches'] = FancyCoffeeImageCatalog::defaultBranches();

        $base = self::attachMenuImages($base);

        return FancyCoffeeImageCatalog::enrichContent($base);
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    private static function attachMenuImages(array $content): array
    {
        foreach (FancyCoffeeImageCatalog::menuItemFiles() as $slug => $file) {
            $path = FancyCoffeeImageCatalog::path($file);
            foreach ($content['menu_categories'] ?? [] as $ci => $category) {
                foreach ($category['items'] ?? [] as $ii => $item) {
                    if (($item['slug'] ?? '') === $slug) {
                        $content['menu_categories'][$ci]['items'][$ii]['image'] = $path;
                    }
                }
            }
        }

        return $content;
    }

    /**
     * @return array<string, mixed>
     */
    public static function tenantSettings(): array
    {
        $settings = CafeThemePreset::settings('Fancy Coffee', 'فانسي كوفي', 'en');
        $settings['default_locale'] = 'en';
        $settings['theme']['logo_url'] = null;
        $settings['legal_entity']['address'] = 'Al Rawdah, Prince Sultan Rd., Jeddah 23432, Saudi Arabia';
        $settings['legal_entity']['address_ar'] = 'الروضة، طريق الأمير سلطان، جدة ٢٣٤٣٢، المملكة العربية السعودية';
        $settings['content']['cafe'] = self::cafeContent();

        return $settings;
    }
}
