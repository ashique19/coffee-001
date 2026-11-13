<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('registers public cafe and admin routes', function () {
    expect(route('cafe.home', absolute: false))->toBe('/')
        ->and(route('admin.login', absolute: false))->toBe('/login');
});
