<?php

namespace Tests\Support;

use App\Business\BusinessContext;
use App\Models\Branch;
use App\Models\BusinessSetting;
use App\Support\Themes\CafeThemePreset;

trait InteractsWithBusiness
{
    protected function seedBusiness(string $name = 'Test Café'): BusinessSetting
    {
        $business = BusinessSetting::query()->create([
            'name' => $name,
            'settings' => CafeThemePreset::settings($name, $name, 'ar'),
        ]);

        Branch::query()->create([
            'code' => 'main',
            'name' => 'Main Branch',
        ]);

        BusinessContext::set($business);

        return $business;
    }
}
