<?php

use App\Models\BusinessSetting;
use Database\Seeders\DemoFancyCoffeeSeeder;

beforeEach(function () {
    $this->withoutVite();
    (new DemoFancyCoffeeSeeder)->run();
});

it('renders inside-the-cafe gallery with the responsive grid markup', function () {
    $galleryCount = count(BusinessSetting::singleton()->cafeContent()['gallery'] ?? []);
    expect($galleryCount)->toBeGreaterThan(0);

    $html = $this->get('/?locale=en')->assertOk()->getContent();

    expect($html)->toContain('cafe-gallery-grid');
    expect($html)->toContain('cafe-gallery-grid__media');
    expect($html)->toContain('data-gallery-count="'.$galleryCount.'"');
});

it('renders gallery grid on rtl locale without error', function () {
    $this->get('/?locale=ar')->assertOk()->assertSee('cafe-gallery-grid', false);
});
