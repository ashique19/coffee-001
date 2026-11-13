<?php

use App\Models\BusinessSetting;
use App\Business\BusinessContext;
use Database\Seeders\OwnerAdminSeeder;

beforeEach(function () {
    (new OwnerAdminSeeder)->run();
    $this->business = BusinessSetting::singleton();
    BusinessContext::set($this->business);
});

afterEach(function () {
    BusinessContext::clear();
});

it('loads cafe themed business settings', function () {
    expect($this->business->isCafeTheme())->toBeTrue()
        ->and($this->business->displayName('ar'))->not->toBe('');
});

it('marks arabic as rtl in locale metadata', function () {
    expect(config('locales.supported.ar.dir'))->toBe('rtl');
});

it('rejects non-cafe theme for storefront gate', function () {
    $settings = $this->business->settings;
    $settings['theme']['layout_key'] = 'default';
    $this->business->forceFill(['settings' => $settings])->save();
    expect($this->business->fresh()->hasThemedStorefront())->toBeFalse();
});
