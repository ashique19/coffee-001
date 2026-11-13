<?php

use App\Models\BusinessSetting;
use Database\Seeders\DemoFancyCoffeeSeeder;

beforeEach(function () {
    $this->withoutVite();
    (new DemoFancyCoffeeSeeder)->run();
    $this->business = BusinessSetting::singleton();
});

it('renders visit-band phone CTA with on-dark pill classes for contrast', function () {
    $phone = (string) data_get($this->business->cafeContent(), 'contact.phone');

    $html = $this->get('/?locale=en')->assertOk()->getContent();

    expect($phone)->not->toBe('');
    expect($html)->toContain('cafe-pill-cta--on-dark');
    expect($html)->toContain('tel:');
    expect($html)->toContain(e($phone));
});

it('renders visit-band phone CTA on rtl locale without server error', function () {
    $this->get('/?locale=ar')->assertOk()->assertSee('cafe-pill-cta--on-dark', false);
});
