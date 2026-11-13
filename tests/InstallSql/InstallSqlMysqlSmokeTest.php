<?php

use App\Business\BusinessContext;
use App\Livewire\Catalog\InventoryManager;
use App\Livewire\Pos\Terminal;
use App\Models\Branch;
use App\Models\BusinessSetting;
use App\Models\Product;
use App\Models\StaffUser;
use Database\Seeders\DemoFancyCoffeeSeeder;
use Database\Seeders\OwnerAdminSeeder;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Tests\Support\AuthenticatedRouteCatalog;
use Tests\Support\InstallSqlMysqlDatabase;
use Tests\Support\RouteListSmoke;

beforeEach(function () {
    if (! InstallSqlMysqlDatabase::isAvailable()) {
        $this->markTestSkipped('MySQL install.sql integration requires INSTALL_SQL_MYSQL_* credentials.');
    }
    InstallSqlMysqlDatabase::ensureConnection();

    $this->withoutVite();
    (new OwnerAdminSeeder)->run();
    (new DemoFancyCoffeeSeeder)->run();

    $this->business = BusinessSetting::singleton();
    BusinessContext::set($this->business);
    $this->branch = Branch::query()->where('code', 'jad-main')->firstOrFail();
    $this->owner = StaffUser::query()->where('email', 'owner@fancy-coffee.test')->firstOrFail();
});

it('serves storefront and login without 500 on install.sql schema', function () {
    foreach (['/?locale=en', '/?locale=ar', '/?locale=bn', '/menu?locale=en', '/menu?locale=ar', '/login'] as $uri) {
        $this->get($uri)->assertSuccessful();
    }
});

it('serves catalog admin routes without 500 for owner on install.sql schema', function () {
    $this->actingAs($this->owner, 'tenant');

    foreach (AuthenticatedRouteCatalog::adminRoutes($this->branch) as [$name, $params, $status]) {
        $response = $this->get(route($name, $params));
        expect($response->status())->toBe($status, "GET {$name} returned {$response->status()}");
    }
});

it('serves resolved GET routes from route:list without 500 for guest and owner', function () {
    $guestOk = ['cafe.home', 'cafe.menu', 'admin.login', 'storefront.robots', 'storefront.sitemap', 'storefront.manifest', 'healthz'];
    foreach ($guestOk as $name) {
        if (Route::has($name)) {
            $this->get(route($name))->assertSuccessful();
        }
    }

    $this->actingAs($this->owner, 'tenant');
    foreach (RouteListSmoke::getRoutes($this->branch) as [$name, $params]) {
        if ($name === null || ! Route::has($name)) {
            continue;
        }
        $response = $this->get(route($name, $params));
        expect($response->status())->toBeLessThan(500, "GET route {$name} returned {$response->status()}");
    }
});

it('renders inventory manager and creates ingredient on install.sql schema', function () {
    $this->actingAs($this->owner, 'tenant');

    Livewire::test(InventoryManager::class)
        ->assertStatus(200)
        ->call('startCreate')
        ->set('nameEn', 'Oat milk')
        ->set('unit', 'ml')
        ->set('quantityOnHand', '1000')
        ->set('lowStockThreshold', '100')
        ->call('save')
        ->assertHasNoErrors();

    expect(\App\Models\Ingredient::query()->where('name_en', 'Oat milk')->exists())->toBeTrue();
});

it('runs pos pay flow on install.sql schema', function () {
    $product = Product::query()->where('is_active', true)->firstOrFail();

    Livewire::actingAs($this->owner, 'tenant')
        ->test(Terminal::class, ['branch' => $this->branch])
        ->call('addProduct', $product->id)
        ->set('paymentMethod', 'cash')
        ->call('pay')
        ->assertHasNoErrors();
});
