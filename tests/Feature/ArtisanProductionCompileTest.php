<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
});

afterEach(function () {
    $this->artisan('optimize:clear')->assertSuccessful();
});

it('runs production optimize and view cache without compile errors', function () {
    $this->artisan('optimize')->assertSuccessful();
    $this->artisan('view:cache')->assertSuccessful();
});
