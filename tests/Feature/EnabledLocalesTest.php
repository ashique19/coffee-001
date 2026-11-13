<?php

use App\Support\EnabledLocales;

it('lists only en ar and bn in ui-enabled locales', function () {
    expect(EnabledLocales::codes())->toBe(['en', 'ar', 'bn']);
});

it('falls back disabled locale query codes to english', function () {
    expect(EnabledLocales::resolve('ur'))->toBe('en')
        ->and(EnabledLocales::resolve('hi'))->toBe('en')
        ->and(EnabledLocales::resolve('ar'))->toBe('ar')
        ->and(EnabledLocales::resolve('bn'))->toBe('bn');
});

it('keeps ur and hi metadata in supported config but marked disabled', function () {
    expect(config('locales.supported.ar.dir'))->toBe('rtl')
        ->and(config('locales.supported.ur.dir'))->toBe('rtl')
        ->and(config('locales.supported.ur.enabled'))->toBeFalse()
        ->and(config('locales.supported.hi.enabled'))->toBeFalse();
});

it('defaults APP_ENABLED_LOCALES to en ar bn when env unset', function () {
    expect(config('app.enabled_locales'))->toBe(['en', 'ar', 'bn']);
});
