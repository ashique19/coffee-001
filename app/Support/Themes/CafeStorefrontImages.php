<?php

namespace App\Support\Themes;

use App\Models\BusinessSetting;

class CafeStorefrontImages
{
    /**
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    public static function enrich(BusinessSetting $business, array $content): array
    {
        if ($business->name !== 'Fancy Coffee') {
            return $content;
        }

        return FancyCoffeeImageCatalog::enrichContent($content);
    }
}
