<?php

use App\Models\BusinessSetting;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StaffUser;
use App\Support\VatPricing;
use Database\Seeders\OwnerAdminSeeder;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->withoutVite();
    (new OwnerAdminSeeder)->run();
    $this->business = BusinessSetting::singleton();
    \App\Business\BusinessContext::set($this->business);
});

/**
 * @return list<array{sku: string, name: string, inc: float, image: string, home: bool}>
 */
function fancyCoffeeMenuSeed(): array
{
    return [
        ['sku' => 'FC-ESPRESSO', 'name' => 'Espresso', 'inc' => 14.00, 'image' => 'images/menu/espresso.webp', 'home' => true],
        ['sku' => 'FC-AMERICANO', 'name' => 'Americano', 'inc' => 14.00, 'image' => 'images/menu/americano.webp', 'home' => false],
        ['sku' => 'FC-CAPPUCCINO', 'name' => 'Cappuccino', 'inc' => 18.00, 'image' => 'images/menu/cappuccino.webp', 'home' => false],
        ['sku' => 'FC-LATTE', 'name' => 'Latte', 'inc' => 18.00, 'image' => 'images/menu/latte.webp', 'home' => true],
        ['sku' => 'FC-SPANISH-LATTE', 'name' => 'Spanish latte', 'inc' => 20.00, 'image' => 'images/menu/spanish-latte.webp', 'home' => true],
        ['sku' => 'FC-FLAT-WHITE', 'name' => 'Flat white', 'inc' => 18.00, 'image' => 'images/menu/flat-white.webp', 'home' => false],
        ['sku' => 'FC-ICED-LATTE', 'name' => 'Iced latte', 'inc' => 20.00, 'image' => 'images/menu/iced-latte.webp', 'home' => false],
        ['sku' => 'FC-QAHWA', 'name' => 'Saudi coffee (Qahwa)', 'inc' => 15.00, 'image' => 'images/menu/saudi-coffee.webp', 'home' => true],
        ['sku' => 'FC-CROISSANT', 'name' => 'Croissant', 'inc' => 14.00, 'image' => 'images/menu/croissant.webp', 'home' => false],
        ['sku' => 'FC-CAKE-SLICE', 'name' => 'Cake slice', 'inc' => 16.00, 'image' => 'images/menu/cake-slice.webp', 'home' => false],
    ];
}

function seedFancyCoffeeMenuCatalog(): void
{
    $hot = ProductCategory::query()->firstOrCreate(
        ['name_en' => 'Hot drinks'],
        ['name_ar' => 'مشروبات ساخنة', 'name_bn' => 'গরম পানীয়', 'icon_key' => 'cup', 'sort_order' => 10, 'is_active' => true],
    );
    $cold = ProductCategory::query()->firstOrCreate(
        ['name_en' => 'Cold drinks'],
        ['name_ar' => 'مشروبات باردة', 'name_bn' => 'ঠান্ডা পানীয়', 'icon_key' => 'snow', 'sort_order' => 20, 'is_active' => true],
    );
    $bites = ProductCategory::query()->firstOrCreate(
        ['name_en' => 'Pastries & bites'],
        ['name_ar' => 'مخبوزات ووجبات خفيفة', 'name_bn' => 'পেস্ট্রি ও স্ন্যাকস', 'icon_key' => 'cookie', 'sort_order' => 30, 'is_active' => true],
    );

    foreach (fancyCoffeeMenuSeed() as $i => $row) {
        $category = str_contains($row['sku'], 'ICED') ? $cold
            : (str_starts_with($row['sku'], 'FC-CROI') || str_starts_with($row['sku'], 'FC-CAKE') ? $bites : $hot);

        Product::query()->updateOrCreate(
            ['sku' => $row['sku']],
            [
                'product_category_id' => $category->id,
                'name_en' => $row['name'],
                'name_ar' => $row['name'],
                'image_url' => $row['image'],
                'price_ex_vat' => VatPricing::exFromInc($row['inc'], 15),
                'vat_rate' => 15,
                'sort_order' => ($i + 1) * 10,
                'is_active' => true,
                'show_on_menu' => true,
                'show_on_homepage' => $row['home'],
            ],
        );
    }
}

it('ships webp and jpg files for every representative menu product', function () {
    foreach (fancyCoffeeMenuSeed() as $row) {
        $relative = $row['image'];
        $jpg = preg_replace('/\.webp$/', '.jpg', $relative);
        expect(File::isFile(public_path($relative)))->toBeTrue($relative.' is missing')
            ->and(File::isFile(public_path($jpg)))->toBeTrue($jpg.' is missing');
    }

    expect(File::isFile(base_path('docs/sql/2026-11-23-fancy-coffee-menu.sql')))->toBeTrue()
        ->and(File::isFile(base_path('docs/sql/IMAGE-CREDITS.md')))->toBeTrue();
});

it('drives the public menu from catalog products and shows their images', function () {
    seedFancyCoffeeMenuCatalog();

    $html = $this->get('/menu?locale=en')->assertOk()->getContent();

    expect($html)->toContain('Espresso')
        ->and($html)->toContain('Spanish latte')
        ->and($html)->toContain('Saudi coffee (Qahwa)')
        ->and($html)->toContain('Croissant')
        ->and($html)->toContain('Cake slice')
        ->and($html)->toContain('images/menu/espresso.webp')
        ->and($html)->toContain('images/menu/croissant.webp')
        ->and($html)->not->toContain('Date maamoul');

    $latte = Product::query()->where('sku', 'FC-LATTE')->firstOrFail();
    expect(VatPricing::incFromEx((float) $latte->price_ex_vat, 15.0))->toBe(18.00);
});

it('lists the seeded catalog in the admin menu manager', function () {
    seedFancyCoffeeMenuCatalog();
    $owner = StaffUser::query()->where('email', 'owner@example.test')->firstOrFail();

    $this->actingAs($owner, 'tenant')
        ->get('/admin/catalog?locale=en')
        ->assertOk()
        ->assertSee('Espresso', false)
        ->assertSee('Cake slice', false)
        ->assertSee('images/menu/espresso.webp', false);
});

it('keeps the menu SQL scoped to Fancy Coffee and free of invoice writes', function () {
    $sql = File::get(base_path('docs/sql/2026-11-23-fancy-coffee-menu.sql'));

    expect($sql)->toContain("name = 'Fancy Coffee'")
        ->and($sql)->toContain("slug = 'fancy-coffee'")
        ->and($sql)->toContain('images/menu/espresso.webp')
        ->and($sql)->toContain('NOT EXISTS')
        ->and($sql)->not->toMatch('/\bINSERT INTO invoices\b/i')
        ->and($sql)->not->toMatch('/\bUPDATE invoices\b/i')
        ->and($sql)->not->toMatch('/\bDELETE FROM invoices\b/i');
});
