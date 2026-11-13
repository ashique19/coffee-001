<?php

namespace App\Services\Tenant;

use App\Models\BusinessSetting;
use App\Models\BusinessSettingSettingsAuditLog;
use App\Models\BusinessSettingUser;
use Illuminate\Support\Facades\Schema;

class TenantSettingsAuditService
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function record(Tenant $tenant, string $event, array $payload = [], ?StaffUser $actor = null): void
    {
        if (! Schema::hasTable('tenant_settings_audit_logs')) {
            return;
        }

        TenantSettingsAuditLog::query()->create([
            'tenant_id' => $tenant->id,
            'tenant_user_id' => $actor?->id,
            'event' => $event,
            'payload' => $payload,
            'created_at' => now(),
        ]);
    }
}
