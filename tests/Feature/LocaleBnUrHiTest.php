<?php

use App\Support\EnabledLocales;
use Database\Seeders\OwnerAdminSeeder;

beforeEach(function () {
    $this->withoutVite();
    (new OwnerAdminSeeder)->run();
});

it('keeps urdu and hindi translation files but hides them from enabled locales', function () {
    expect(is_file(lang_path('ur/cafe.php')))->toBeTrue()
        ->and(is_file(lang_path('hi/cafe.php')))->toBeTrue()
        ->and(__('cafe.nav_menu', [], 'ur'))->not->toBe('cafe.nav_menu')
        ->and(EnabledLocales::isEnabled('ur'))->toBeFalse()
        ->and(EnabledLocales::isEnabled('hi'))->toBeFalse();
});

it('enables bengali in the enabled locale list with café translations', function () {
    expect(EnabledLocales::isEnabled('bn'))->toBeTrue()
        ->and(is_file(lang_path('bn/cafe.php')))->toBeTrue();
});

it('falls back storefront to english when locale query is ur or hi', function () {
    $enQuote = __('cafe.default_story.pull_quote', [], 'en');

    $settings = \App\Models\BusinessSetting::singleton()->settings;
    data_set($settings, 'content.cafe.about_en', '');
    data_set($settings, 'content.cafe.about_ar', '');
    \App\Models\BusinessSetting::singleton()->forceFill(['settings' => $settings])->save();

    $this->get('/?locale=ur')->assertOk()->assertSee($enQuote, false);
    $this->get('/?locale=hi')->assertOk()->assertSee($enQuote, false);
    expect(session('locale'))->toBe('en');
});

it('does not expose ur or hi in public storefront locale switcher', function () {
    $html = $this->get('/?locale=en')->assertOk()->getContent();

    expect($html)->not->toMatch('/locale=ur/')
        ->and($html)->not->toMatch('/locale=hi/');
    expect($html)->toMatch('/locale=en/')
        ->and($html)->toMatch('/locale=ar/')
        ->and($html)->toMatch('/locale=bn/');
});
