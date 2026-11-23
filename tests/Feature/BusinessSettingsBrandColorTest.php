<?php

use App\Livewire\Admin\BusinessSettings;
use App\Models\BusinessSetting;
use App\Models\StaffUser;
use App\Support\TenantBusinessProfile;
use Database\Seeders\DemoFancyCoffeeSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->withoutVite();
    (new DemoFancyCoffeeSeeder)->run();
    $this->business = BusinessSetting::singleton();
    $this->owner = StaffUser::query()->where('email', 'owner@fancy-coffee.test')->firstOrFail();
    $this->actingAs($this->owner, 'tenant');
});

it('renders cafe brand colour options and a default selection even when theme colors are empty', function () {
    $settings = $this->business->settings ?? [];
    data_set($settings, 'theme.colors', []);
    data_set($settings, 'business.brand.brand_color_key', '');
    $this->business->forceFill(['settings' => $settings])->save();

    $options = TenantBusinessProfile::brandColorOptions($this->business->fresh());
    expect($options)->not->toBeEmpty()
        ->and(array_keys($options))->toBe(['espresso', 'caramel', 'accent', 'mocha']);

    $html = Livewire::test(BusinessSettings::class)
        ->assertSet('brandColorKey', 'espresso')
        ->assertSeeHtml('data-testid="business-brand-color"')
        ->assertSeeHtml('value="espresso"')
        ->assertSeeHtml('value="caramel"')
        ->assertSeeHtml('value="accent"')
        ->assertSeeHtml('value="mocha"')
        ->assertSee(__('business.colors.espresso'), false)
        ->assertSee(__('business.colors.caramel'), false)
        ->html();

    expect($html)->toContain('>Espresso<')
        ->and(substr_count($html, '<option'))->toBeGreaterThanOrEqual(4)
        ->and($html)->toContain('data-testid="business-brand-color-swatch"')
        ->and(preg_match('/id="brandColorKey"[^>]*\ssize=/', $html))->toBe(0)
        ->and(preg_match('/id="brandColorKey"[^>]*\smultiple/', $html))->toBe(0);
});

it('saves a selected brand colour when the stored palette was empty', function () {
    $settings = $this->business->settings ?? [];
    data_set($settings, 'theme.colors', []);
    $this->business->forceFill(['settings' => $settings])->save();

    Livewire::test(BusinessSettings::class)
        ->set('section', 'brand')
        ->set('brandColorKey', 'caramel')
        ->call('saveBrand')
        ->assertHasNoErrors()
        ->assertDispatched('toast', type: 'success');

    $fresh = $this->business->fresh();
    expect(data_get($fresh->settings, 'business.brand.brand_color_key'))->toBe('caramel')
        ->and(data_get($fresh->settings, 'theme.colors.espresso'))->toBe('#C68B59')
        ->and(data_get($fresh->settings, 'theme.colors.caramel'))->toBe('#C68B59')
        ->and(data_get($fresh->settings, 'theme.colors.mocha'))->toBe('#5C3D2E');
});
