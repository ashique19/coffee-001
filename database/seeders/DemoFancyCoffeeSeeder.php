<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StaffUser;
use App\Support\AuthRoleProvisioner;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Rich demo dataset for tests and local demos (single business).
 */
class DemoFancyCoffeeSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(OwnerAdminSeeder::class);

        AuthRoleProvisioner::seedStaffRoles();

        StaffUser::query()->firstOrCreate(
            ['email' => 'owner@fancy-coffee.test'],
            [
                'name' => 'Owner',
                'password' => Hash::make('TestPassword!2026'),
                'is_active' => true,
            ],
        )->assignRole('owner');

        $jadMain = Branch::query()->where('code', 'jad-main')->first();

        StaffUser::query()->firstOrCreate(
            ['email' => 'cashier@fancy-coffee.test'],
            [
                'name' => 'Cashier',
                'password' => Hash::make('TestPassword!2026'),
                'branch_id' => $jadMain?->id,
                'is_active' => true,
            ],
        )->assignRole('cashier');

        if ($jadMain !== null) {
            StaffUser::query()->where('email', 'cashier@fancy-coffee.test')->update(['branch_id' => $jadMain->id]);
        }

        Branch::query()->firstOrCreate(
            ['code' => 'ruh-mall'],
            ['name' => 'Riyadh Mall', 'name_ar' => 'الرياض مول'],
        );

        $hot = ProductCategory::query()->firstOrCreate(
            ['name_en' => 'Hot drinks'],
            ['name_ar' => 'مشروبات ساخنة', 'icon_key' => 'cup'],
        );

        Product::query()->firstOrCreate(
            ['sku' => 'LATTE-DEMO'],
            [
                'product_category_id' => $hot->id,
                'name_en' => 'Latte',
                'name_ar' => 'لاتيه',
                'price_ex_vat' => 18,
                'vat_rate' => 15,
                'show_on_menu' => true,
            ],
        );

        Customer::query()->firstOrCreate(
            ['phone' => '0501000001'],
            [
                'name' => 'Regular Guest',
                'referral_code' => 'FANCY100',
                'points_balance' => 120,
            ],
        );
    }
}
