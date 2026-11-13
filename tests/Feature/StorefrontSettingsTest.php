<?php

use App\Livewire\Admin\StorefrontSettings;
use App\Models\BusinessSetting;
use App\Models\StaffUser;
use Database\Seeders\DemoFancyCoffeeSeeder;
use Livewire\Livewire;

beforeEach(function () {
    (new DemoFancyCoffeeSeeder)->run();
    $this->tenant = BusinessSetting::query()->firstOrFail();
    $this->owner = StaffUser::query()
        ->where('email', 'owner@fancy-coffee.test')->firstOrFail();
});

it('lets owner save ga4 and verification tokens', function () {
    $this->actingAs($this->owner, 'tenant');

    Livewire::test(StorefrontSettings::class)
        ->set('gaMeasurementId', 'g-test1234abcd')
        ->set('googleSiteVerification', 'abc123token')
        ->set('bingSiteVerification', 'A1B2C3D4E5F6A1B2')
        ->call('save')
        ->assertHasNoErrors();

    $settings = $this->tenant->fresh()->settings;
    expect(data_get($settings, 'storefront.ga_measurement_id'))->toBe('G-TEST1234ABCD')
        ->and(data_get($settings, 'storefront.google_site_verification'))->toBe('abc123token');
});

it('rejects invalid ga4 ids', function () {
    $this->actingAs($this->owner, 'tenant');

    Livewire::test(StorefrontSettings::class)
        ->set('gaMeasurementId', 'UA-12345')
        ->call('save')
        ->assertHasErrors(['gaMeasurementId']);
});

it('forbids cashier from storefront settings', function () {
    $cashier = StaffUser::query()
        ->where('email', 'cashier@fancy-coffee.test')->firstOrFail();

    $this->actingAs($cashier, 'tenant')
        ->get(route('admin.storefront'))
        ->assertForbidden();
});
