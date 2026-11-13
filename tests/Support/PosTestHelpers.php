<?php

use App\Models\Branch;
use App\Models\BusinessSetting;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StaffUser;
use App\Support\AuthRoleProvisioner;
use App\Support\Themes\CafeThemePreset;
use Illuminate\Support\Facades\Hash;

function posTenantSetup(): array
{
    AuthRoleProvisioner::seedStaffRoles();

    $tenant = BusinessSetting::query()->firstOrCreate(
        ['id' => 1],
        ['name' => 'POS Cafe', 'settings' => CafeThemePreset::settings('POS Cafe', 'مقهى', 'ar')],
    );

    $branch = Branch::query()->firstOrCreate(
        ['code' => 'main'],
        ['name' => 'Main', 'name_ar' => 'الرئيسي'],
    );

    $cashier = StaffUser::query()->firstOrCreate(
        ['email' => 'cashier@pos-cafe.test'],
        [
            'name' => 'Cashier',
            'password' => Hash::make('password'),
            'branch_id' => $branch->id,
            'is_active' => true,
        ],
    );
    if (! $cashier->hasRole('cashier')) {
        $cashier->assignRole('cashier');
    }

    $category = ProductCategory::query()->firstOrCreate(
        ['name_en' => 'Drinks'],
        ['name_ar' => 'مشروبات'],
    );

    $product = Product::query()->firstOrCreate(
        ['sku' => 'LATTE'],
        [
            'product_category_id' => $category->id,
            'name_en' => 'Latte',
            'name_ar' => 'لاتيه',
            'price_ex_vat' => 20,
            'vat_rate' => 15,
        ],
    );

    return ['tenant' => $tenant, 'branch' => $branch, 'cashier' => $cashier, 'product' => $product];
}
