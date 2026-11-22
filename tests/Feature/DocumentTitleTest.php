<?php

use App\Models\Branch;
use App\Models\BusinessSetting;
use App\Models\StaffUser;
use App\Support\BusinessSettingDisplayName;
use App\Support\DocumentTitle;
use Database\Seeders\DemoFancyCoffeeSeeder;

beforeEach(function () {
    $this->withoutVite();
    (new DemoFancyCoffeeSeeder)->run();
    $this->business = BusinessSetting::singleton();
    $this->owner = StaffUser::query()->where('email', 'owner@fancy-coffee.test')->firstOrFail();
    $this->branch = Branch::query()->where('code', 'jad-main')->firstOrFail();
});

function documentTitleFrom(string $html): string
{
    expect($html)->toMatch('/<title>([^<]*)<\/title>/i');
    preg_match('/<title>([^<]*)<\/title>/i', $html, $matches);

    return html_entity_decode(trim($matches[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

it('renders public and login titles with page label and brand in en, ar, and bn', function () {
    $settings = $this->business->settings;
    data_set($settings, 'business.brand.display_name_ar', '');
    $this->business->forceFill(['settings' => $settings])->save();
    $this->business->refresh();

    foreach (['en', 'ar', 'bn'] as $locale) {
        $brand = BusinessSettingDisplayName::for($this->business, $locale);
        expect($brand)->not->toBe('')
            ->and($brand)->not->toStartWith('—');

        $homeTitle = documentTitleFrom($this->get('/?locale='.$locale)->assertOk()->getContent());
        expect($homeTitle)->toBe(DocumentTitle::join($brand, __('cafe.meta_suffix', [], $locale)))
            ->and($homeTitle)->toContain($brand)
            ->and($homeTitle)->not->toStartWith('—')
            ->and($homeTitle)->not->toEndWith('—');

        $menuTitle = documentTitleFrom($this->get('/menu?locale='.$locale)->assertOk()->getContent());
        expect($menuTitle)->toBe(DocumentTitle::join($brand, __('cafe.menu_title', [], $locale)))
            ->and($menuTitle)->toContain($brand);

        $loginTitle = documentTitleFrom($this->get('/login?locale='.$locale)->assertOk()->getContent());
        expect($loginTitle)->toBe(DocumentTitle::join(__('pos.login_title', [], $locale), $brand))
            ->and($loginTitle)->toContain($brand);
    }
});

it('renders per-page admin titles with the brand instead of the POS label', function () {
    $this->actingAs($this->owner, 'tenant');
    $brand = BusinessSettingDisplayName::for($this->business, 'en');

    $pages = [
        ['/admin/dashboard', 'dashboard.title'],
        ['/admin/pos', 'pos.title'],
        ['/admin/invoices', 'invoices.title'],
        ['/admin/invoices/issue/simplified', 'invoices.issue_simplified_title'],
        ['/admin/catalog', 'catalog.title'],
        ['/admin/inventory', 'inventory.title'],
        ['/admin/business', 'business.title'],
    ];

    foreach ($pages as [$path, $key]) {
        $title = documentTitleFrom($this->get($path.'?locale=en')->assertOk()->getContent());
        $expectedPage = __($key, [], 'en');
        expect($title)->toBe(DocumentTitle::join($expectedPage, $brand), "Unexpected title for {$path}: {$title}")
            ->and($title)->toContain($brand)
            ->and($title)->toContain($expectedPage);
    }

    $dashboard = documentTitleFrom($this->get('/admin/dashboard?locale=en')->assertOk()->getContent());
    expect($dashboard)->not->toContain(__('pos.title', [], 'en'));
});

it('falls back to a filled legal or english name when the arabic brand is blank', function () {
    $settings = $this->business->settings;
    data_set($settings, 'business.brand.display_name_ar', '   ');
    data_set($settings, 'legal_entity.name_ar', '');
    data_set($settings, 'business.brand.display_name_en', 'Fancy Coffee');
    $this->business->forceFill(['settings' => $settings])->save();

    expect(BusinessSettingDisplayName::for($this->business->fresh(), 'ar'))->toBe('Fancy Coffee');
});
