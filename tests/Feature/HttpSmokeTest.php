<?php

use App\Models\Branch;
use App\Models\StaffUser;
use Database\Seeders\DemoFancyCoffeeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\AuthenticatedRouteCatalog;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    (new DemoFancyCoffeeSeeder)->run();
    $this->branch = Branch::query()->where('code', 'jad-main')->firstOrFail();
    $this->owner = StaffUser::query()->where('email', 'owner@fancy-coffee.test')->firstOrFail();
});

it('serves public storefront and login without server errors', function () {
    $this->get('/')->assertOk();
    $this->get('/menu')->assertOk();
    $this->get('/login')->assertOk();
});

it('serves main admin pages for the owner without server errors', function () {
    $this->actingAs($this->owner, 'tenant');

    foreach (AuthenticatedRouteCatalog::adminRoutes($this->branch) as [$routeName, $parameters, $expectedStatus]) {
        $response = $this->get(route($routeName, $parameters));
        expect($response->status())->toBe($expectedStatus, "GET route {$routeName} returned {$response->status()}");
    }
});
