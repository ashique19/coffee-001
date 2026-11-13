<?php

use Database\Seeders\DemoFancyCoffeeSeeder;

beforeEach(function () {
    $this->withoutVite();
    (new DemoFancyCoffeeSeeder)->run();
});

it('shows a staff login link in the storefront footer on home and menu', function (string $path, string $locale) {
    $label = __('cafe.footer_staff_login', [], $locale);
    $loginUrl = route('admin.login');

    $html = $this->get("{$path}?locale={$locale}")->assertOk()->getContent();

    expect($html)->toContain($label)
        ->and($html)->toContain($loginUrl)
        ->and($html)->toContain('cafe-footer-staff-link');
})->with([
    ['/', 'en'],
    ['/menu', 'en'],
    ['/', 'ar'],
    ['/menu', 'bn'],
]);
