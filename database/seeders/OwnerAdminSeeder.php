<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\BusinessSetting;
use App\Models\StaffUser;
use App\Support\AuthRoleProvisioner;
use App\Support\Themes\CafeThemePreset;
use App\Support\Themes\FancyCoffeeBrandContent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OwnerAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) env('ADMIN_EMAIL', 'owner@example.test');
        $password = (string) env('ADMIN_PASSWORD', 'ChangeMe!2026');

        AuthRoleProvisioner::seedStaffRoles();

        $preset = FancyCoffeeBrandContent::tenantSettings();
        $business = BusinessSetting::query()->firstOrCreate(
            ['id' => 1],
            [
                'name' => 'Fancy Coffee',
                'settings' => array_merge(CafeThemePreset::settings('Fancy Coffee', 'فانسي كوفي', 'ar'), $preset, [
                    'invoicing' => ['default_document_type' => 'simplified', 'integration_enabled' => false],
                ]),
            ],
        );

        $settings = $business->settings;
        $settings['content']['cafe'] = FancyCoffeeBrandContent::cafeContent();
        $business->forceFill(['settings' => $settings])->save();

        $branch = Branch::query()->firstOrCreate(
            ['code' => 'jad-main'],
            ['name' => 'Jeddah Main', 'name_ar' => 'جدة الرئيسي'],
        );

        $owner = StaffUser::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Owner',
                'password' => Hash::make($password),
                'branch_id' => $branch->id,
                'is_active' => true,
            ],
        );

        if (! $owner->hasRole('owner')) {
            $owner->assignRole('owner');
        }
    }
}
