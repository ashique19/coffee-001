<?php

use App\Support\EnabledLocales;

it('keeps urdu translation files while ui lists only enabled locales', function () {
    expect(config('locales.supported.ur.enabled'))->toBeTrue()
        ->and(config('locales.supported.ur.dir'))->toBe('rtl')
        ->and(__('cafe.nav_menu', [], 'ur'))->not->toBe('cafe.nav_menu')
        ->and(EnabledLocales::isEnabled('ur'))->toBeTrue();
});

it('enables bengali in the enabled locale list with café translations', function () {
    expect(EnabledLocales::isEnabled('bn'))->toBeTrue()
        ->and(is_file(lang_path('bn/cafe.php')))->toBeTrue();
});
