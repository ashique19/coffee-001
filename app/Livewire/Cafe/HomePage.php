<?php

namespace App\Livewire\Cafe;

use App\Business\BusinessContext;
use App\Models\BusinessSetting;
use App\Support\Storefront\StorefrontCatalog;
use App\Support\Storefront\StorefrontSeo;
use App\Support\Themes\CafeThemePreset;
use Livewire\Component;

class HomePage extends Component
{
    public BusinessSetting $business;

    public function mount(): void
    {
        $this->business = BusinessContext::require();
        abort_unless($this->business->isCafeTheme(), 404);
    }

    public function render()
    {
        $content = $this->business->cafeContent();
        $catalogFeatured = StorefrontCatalog::featuredProducts();
        if ($catalogFeatured->isNotEmpty()) {
            $featured = StorefrontCatalog::cafeFeaturedCards($catalogFeatured);
            $featuredSource = 'catalog';
        } else {
            $featured = [];
            foreach ($content['featured_menu'] ?? [] as $ref) {
                $item = CafeThemePreset::findMenuItem($content, $ref['key'] ?? '');
                if ($item) {
                    $featured[] = array_merge($item, [
                        'badge_en' => $ref['badge_en'] ?? '',
                        'badge_ar' => $ref['badge_ar'] ?? '',
                    ]);
                }
            }
            $featuredSource = 'content';
        }

        return view('livewire.cafe.home-page', [
            'business' => $this->business,
            'content' => $content,
            'featured' => $featured,
            'featuredSource' => $featuredSource,
        ])->layout('layouts.cafe', [
            'tenant' => $this->business,
            'seo' => StorefrontSeo::forPage($this->business, StorefrontSeo::PAGE_HOME, app()->getLocale(), $content),
        ]);
    }
}
