<?php

namespace App\Livewire\Cafe;

use App\Business\BusinessContext;
use App\Models\BusinessSetting;
use App\Support\Storefront\StorefrontCatalog;
use App\Support\Storefront\StorefrontSeo;
use Livewire\Component;

class MenuPage extends Component
{
    public BusinessSetting $business;

    public function mount(): void
    {
        $this->business = BusinessContext::require();
        abort_unless($this->business->isCafeTheme(), 404);
    }

    public function render()
    {
        $locale = app()->getLocale();
        $content = $this->business->cafeContent();
        $catalogSections = StorefrontCatalog::menuCategoryGroups($locale);
        $menuCategories = $catalogSections !== [] ? $catalogSections : ($content['menu_categories'] ?? []);

        return view('livewire.cafe.menu-page', [
            'tenant' => $this->business,
            'content' => $content,
            'menuCategories' => $menuCategories,
        ])->layout('layouts.cafe', [
            'tenant' => $this->business,
            'heroHeader' => false,
            'footerContent' => $content,
            'seo' => StorefrontSeo::forPage($this->business, StorefrontSeo::PAGE_MENU, $locale, $content),
        ]);
    }
}
