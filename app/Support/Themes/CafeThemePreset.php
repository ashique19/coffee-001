<?php

namespace App\Support\Themes;

use App\Models\BusinessSetting;

class CafeThemePreset
{
    public static function settings(string $nameEn, string $nameAr, string $locale = 'ar'): array
    {
        $base = BusinessSetting::defaultSettings();

        $base['default_locale'] = $locale;
        $base['theme'] = [
            'layout_key' => 'cafe',
            'logo_url' => null,
            'colors' => [
                'espresso' => '#2C1810',
                'cream' => '#FAF6F0',
                'caramel' => '#C68B59',
                'latte' => '#E8D4C4',
                'mocha' => '#5C3D2E',
                'accent' => '#D4A574',
            ],
            'font' => 'Instrument Sans',
            'font_ar' => 'Tajawal',
        ];

        $base['invoicing'] = array_merge($base['invoicing'] ?? [], [
            'default_document_type' => 'simplified',
        ]);

        $base['legal_entity'] = [
            'name_en' => $nameEn,
            'name_ar' => $nameAr,
            'vat_number' => '310122393500003',
            'cr_number' => '1010456789',
            'address' => 'Prince Sultan Road, Al Rawdah, Jeddah 23432, Kingdom of Saudi Arabia',
            'address_ar' => 'طريق الأمير سلطان، الروضة، جدة ٢٣٤٣٢، المملكة العربية السعودية',
        ];

        $base['content']['cafe'] = self::demoCafeContent($nameEn, $nameAr);

        return $base;
    }

    /**
     * @return array<string, mixed>
     */
    public static function demoCafeContent(string $nameEn, string $nameAr): array
    {
        $contactSlug = str_replace('-', '', \Illuminate\Support\Str::slug($nameEn));

        return [
            'tagline_en' => 'Specialty coffee rooted in Saudi hospitality',
            'tagline_ar' => 'قهوة مختصة بروح الضيافة السعودية',
            'about_en' => "{$nameEn} roasts single-origin beans and serves traditional Arabic coffee alongside modern espresso drinks. Every cup is prepared with care at our Jeddah roastery café.",
            'about_ar' => "يقدّم {$nameAr} حبوباً مختصة من مصادر موثوقة مع قهوة عربية أصيلة ومشروبات إسبريسو حديثة. نُحضّر كل كوب بحب في مقهانا في جدة.",
            'hours' => [
                ['label_en' => 'Sat – Thu', 'label_ar' => 'السبت – الخميس', 'value_en' => '7:00 AM – 11:00 PM', 'value_ar' => '٧:٠٠ ص – ١١:٠٠ م'],
                ['label_en' => 'Friday', 'label_ar' => 'الجمعة', 'value_en' => '2:00 PM – 11:00 PM', 'value_ar' => '٢:٠٠ م – ١١:٠٠ م'],
            ],
            'location' => [
                'map_placeholder' => true,
                'lat' => 21.5433,
                'lng' => 39.1728,
            ],
            'contact' => [
                'phone' => '+966 12 600 8842',
                'email' => "hello@{$contactSlug}.sa",
                'instagram' => '@'.$contactSlug,
            ],
            'featured_menu' => [
                ['key' => 'arabic-coffee', 'badge_en' => 'Signature', 'badge_ar' => 'مميز'],
                ['key' => 'flat-white', 'badge_en' => 'Best seller', 'badge_ar' => 'الأكثر طلباً'],
                ['key' => 'cardamom-latte', 'badge_en' => 'Seasonal', 'badge_ar' => 'موسمي'],
            ],
            'menu_categories' => [
                [
                    'slug' => 'hot',
                    'name_en' => 'Hot drinks',
                    'name_ar' => 'مشروبات ساخنة',
                    'items' => [
                        ['slug' => 'espresso', 'name_en' => 'Espresso', 'name_ar' => 'إسبريسو', 'desc_en' => 'Double shot, medium roast', 'desc_ar' => 'جرعتان، تحميص متوسط', 'price_sar' => 12.00],
                        ['slug' => 'flat-white', 'name_en' => 'Flat white', 'name_ar' => 'فلات وايت', 'desc_en' => 'Velvety microfoam', 'desc_ar' => 'رغوة حريرية', 'price_sar' => 18.00],
                        ['slug' => 'cardamom-latte', 'name_en' => 'Cardamom latte', 'name_ar' => 'لاتيه هيل', 'desc_en' => 'With lightly crushed Saudi cardamom', 'desc_ar' => 'بهيل سعودي مطحون', 'price_sar' => 22.00],
                        ['slug' => 'arabic-coffee', 'name_en' => 'Arabic coffee (Gahwa)', 'name_ar' => 'قهوة عربية', 'desc_en' => 'Lightly spiced, served with dates', 'desc_ar' => 'بهارات خفيفة مع تمر', 'price_sar' => 15.00],
                    ],
                ],
                [
                    'slug' => 'cold',
                    'name_en' => 'Cold drinks',
                    'name_ar' => 'مشروبات باردة',
                    'items' => [
                        ['slug' => 'iced-latte', 'name_en' => 'Iced latte', 'name_ar' => 'آيس لاتيه', 'desc_en' => 'Chilled milk over espresso', 'desc_ar' => 'حليب بارد مع إسبريسو', 'price_sar' => 20.00],
                        ['slug' => 'cold-brew', 'name_en' => 'Cold brew', 'name_ar' => 'كولد برو', 'desc_en' => '18-hour steep', 'desc_ar' => 'تخمير ١٨ ساعة', 'price_sar' => 19.00],
                    ],
                ],
                [
                    'slug' => 'bites',
                    'name_en' => 'Pastries & bites',
                    'name_ar' => 'مخبوزات ووجبات خفيفة',
                    'items' => [
                        ['slug' => 'date-maamoul', 'name_en' => 'Date maamoul', 'name_ar' => 'معمول تمر', 'desc_en' => 'Buttery cookie with Ajwa dates', 'desc_ar' => 'مع تمر عجوة', 'price_sar' => 14.00],
                        ['slug' => 'cheese-sambusa', 'name_en' => 'Cheese sambusa', 'name_ar' => 'سمبوسة جبن', 'desc_en' => 'Crisp pastry, three pieces', 'desc_ar' => 'ثلاث قطع مقرمشة', 'price_sar' => 16.00],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $content
     */
    public static function findMenuItem(array $content, string $key): ?array
    {
        foreach ($content['menu_categories'] ?? [] as $category) {
            foreach ($category['items'] ?? [] as $item) {
                if (($item['slug'] ?? '') === $key) {
                    return $item + ['category' => $category];
                }
            }
        }

        return null;
    }
}
