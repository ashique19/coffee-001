<?php

namespace Database\Seeders;

use App\Enums\PosOrderStatus;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\EgsDevice;
use App\Models\Ingredient;
use App\Models\PlatformUser;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\BusinessSetting;
use App\Models\BusinessSettingUser;
use App\Support\AuthRoleProvisioner;
use App\Support\PermissionTeams;
use App\Support\Themes\CafeThemePreset;
use App\Support\Themes\FancyCoffeeBrandContent;
use App\Support\Themes\FancyCoffeeImageCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

class DemoFancyCoffeeSeeder extends Seeder
{
    public function run(): void
    {
        AuthRoleProvisioner::seedPlatform();

        PermissionTeams::forPlatform();
        $platformAdmin = PlatformUser::query()->firstOrCreate(
            ['email' => 'admin@taskeenlab.test'],
            ['name' => 'Platform Admin', 'password' => Hash::make('TestPassword!2026'), 'is_active' => true],
        );
        if (! $platformAdmin->hasRole('super-admin')) {
            $platformAdmin->assignRole('super-admin');
        }

        $presetSettings = FancyCoffeeBrandContent::tenantSettings();
        $tenant = Tenant::query()->firstOrCreate(
            ['slug' => 'fancy-coffee'],
            [
                'name' => 'Fancy Coffee',
                'settings' => array_merge($presetSettings, [
                    'loyalty' => [
                        'earn_rate' => 1,
                        'redemption_value_ex_vat' => 0.1,
                        'minimum_redeem_points' => 50,
                        'referrer_bonus_points' => 25,
                    ],
                ]),
            ]
        );

        $settings = $tenant->settings;
        $settings['content']['cafe'] = FancyCoffeeBrandContent::cafeContent();
        $settings['legal_entity'] = array_merge($settings['legal_entity'] ?? [], $presetSettings['legal_entity']);
        $settings['zatca'] = array_merge($settings['zatca'] ?? [], [
            'default_document_type' => 'simplified',
        ]);
        $tenant->forceFill(['settings' => $settings])->save();

        AuthRoleProvisioner::seedTenant($tenant);
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);

        $jad = Branch::query()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'jad-main'],
            ['name' => 'Jeddah Main', 'name_ar' => 'جدة الرئيسي'],
        );

        $riy = Branch::query()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'ruh-mall'],
            ['name' => 'Riyadh Mall', 'name_ar' => 'الرياض مول'],
        );

        EgsDevice::query()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'uuid' => '00000000-0000-4000-8000-000000000001'],
            ['branch_id' => $jad->id, 'name' => 'EGS Jeddah']
        );

        $owner = StaffUser::query()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'email' => 'owner@fancy-coffee.test'],
            ['name' => 'Owner', 'password' => Hash::make('TestPassword!2026'), 'branch_id' => null],
        );
        $owner->assignRole('owner');

        $cashier = StaffUser::query()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'email' => 'cashier@fancy-coffee.test'],
            ['name' => 'Cashier', 'password' => Hash::make('TestPassword!2026'), 'branch_id' => $jad->id],
        );
        $cashier->assignRole('cashier');

        $hot = ProductCategory::query()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'name_en' => 'Hot drinks'],
            ['name_ar' => 'مشروبات ساخنة', 'icon_key' => 'cup'],
        );

        $cold = ProductCategory::query()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'name_en' => 'Cold drinks'],
            ['name_ar' => 'مشروبات باردة', 'icon_key' => 'cup'],
        );

        $catalog = [
            ['sku' => 'LATTE', 'cat' => $hot, 'name_en' => 'Latte', 'name_ar' => 'لاتيه', 'price' => 18, 'img' => 'cardamom-latte'],
            ['sku' => 'ESP', 'cat' => $hot, 'name_en' => 'Espresso', 'name_ar' => 'إسبريسو', 'price' => 12, 'img' => 'espresso'],
            ['sku' => 'ICED', 'cat' => $cold, 'name_en' => 'Iced latte', 'name_ar' => 'آيس لاتيه', 'price' => 20, 'img' => 'iced-latte'],
            ['sku' => 'COLD', 'cat' => $cold, 'name_en' => 'Cold brew', 'name_ar' => 'كولد برو', 'price' => 22, 'img' => 'cold-brew'],
        ];

        $latte = null;
        foreach ($catalog as $row) {
            $product = Product::query()->firstOrCreate(
                ['tenant_id' => $tenant->id, 'sku' => $row['sku']],
                [
                    'product_category_id' => $row['cat']->id,
                    'name_en' => $row['name_en'],
                    'name_ar' => $row['name_ar'],
                    'price_ex_vat' => $row['price'],
                    'image_url' => FancyCoffeeImageCatalog::path($row['img']),
                ],
            );
            $product->update(['image_url' => FancyCoffeeImageCatalog::path($row['img'])]);
            if ($row['sku'] === 'LATTE') {
                $latte = $product;
            }
        }

        $milk = Ingredient::query()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'branch_id' => $jad->id, 'name' => 'Milk'],
            ['quantity_on_hand' => 5000, 'reorder_level' => 500, 'unit' => 'ml'],
        );

        $recipe = Recipe::query()->firstOrCreate(['tenant_id' => $tenant->id, 'product_id' => $latte->id]);
        RecipeIngredient::query()->firstOrCreate(
            ['recipe_id' => $recipe->id, 'ingredient_id' => $milk->id],
            ['quantity' => 250],
        );

        $referrer = Customer::query()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'referral_code' => 'FANCY100'],
            ['name' => 'Regular Guest', 'phone' => '0501000001', 'points_balance' => 120],
        );

        Customer::query()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'phone' => '0501000002'],
            ['name' => 'New Guest', 'referral_code' => 'GUEST200', 'referred_by_customer_id' => $referrer->id, 'points_balance' => 20],
        );

        \App\Models\PosOrder::query()->firstOrCreate(
            ['tenant_id' => $tenant->id, 'order_number' => 'jad-main-'.now()->format('Ymd').'-0001'],
            [
                'branch_id' => $jad->id,
                'status' => PosOrderStatus::Paid,
                'payment_method' => 'card',
                'cashier_id' => $cashier->id,
                'subtotal_ex_vat' => 36,
                'vat_total' => 5.4,
                'total_inc_vat' => 41.4,
                'line_items' => [[
                    'product_id' => $latte->id,
                    'name' => 'Latte',
                    'quantity' => 2,
                    'unit_price_ex_vat' => 18,
                    'vat_rate' => 15,
                ]],
                'paid_at' => now()->subHour(),
            ],
        );
    }
}
