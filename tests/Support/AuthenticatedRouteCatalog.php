<?php

namespace Tests\Support;

use App\Models\Branch;
use App\Models\PosOrder;
use App\Models\BusinessSetting;

/**
 * Authenticated GET routes used for post-login smoke tests.
 */
final class AuthenticatedRouteCatalog
{
    /**
     * @return list<array{0: string, 1: array<string, mixed>, 2: int, 3?: string|null}>
     */
    public static function tenantRoutes(Tenant $tenant, Branch $branch, PosOrder $order): array
    {
        $t = ['tenant' => $tenant];

        return [
            ['admin.dashboard', $t, 200, null],
            ['tenant.zatca', $t, 200, null],
            ['admin.pos.home', $t, 200, null],
            ['admin.pos.terminal', ['tenant' => $tenant, 'branch' => $branch], 200, null],
            ['admin.pos.history', ['tenant' => $tenant, 'branch' => $branch], 200, null],
            ['admin.pos.eod', ['tenant' => $tenant, 'branch' => $branch], 200, null],
            ['admin.pos.ops', ['tenant' => $tenant, 'branch' => $branch], 200, null],
            ['admin.reports', $t, 200, null],
            ['admin.storefront', $t, 200, null],
            ['admin.customers', $t, 200, null],
            ['admin.invoices', $t, 200, null],
            ['admin.invoices.reconcile', $t, 200, null],
            ['admin.catalog', $t, 200, null],
            ['admin.team', $t, 200, null],
            ['admin.pos.receipt', ['tenant' => $tenant, 'order' => $order], 200, null],
        ];
    }

    /**
     * @return list<array{0: string, 1: array<string, mixed>, 2: int, 3?: string|null}>
     */
    public static function platformRoutes(Tenant $tenant): array
    {
        return [
            ['platform.dashboard', [], 200, null],
            ['platform.clients.export', [], 200, null],
            ['platform.tenants.storefront', ['tenant' => $tenant], 200, null],
            ['platform.support.tenant', ['tenant' => $tenant], 200, null],
            ['platform.tenants.export', ['tenant' => $tenant, 'type' => 'bundle'], 200, null],
        ];
    }
}
