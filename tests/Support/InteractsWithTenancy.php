<?php

namespace Tests\Support;

use App\Business\BusinessContext;
use App\Models\Branch;
use App\Models\BusinessSetting;
use App\Support\Themes\CafeThemePreset;

/** @deprecated use InteractsWithBusiness */
trait InteractsWithTenancy
{
    use InteractsWithBusiness;

    protected function createTenant(string $slug = 'acme'): BusinessSetting
    {
        return $this->seedBusiness($slug);
    }

    protected function actingAsTenant(BusinessSetting $business): void
    {
        BusinessContext::set($business);
    }

    protected function createEgsDevice(BusinessSetting $business): void
    {
        // no-op: local EGS removed
    }
}
