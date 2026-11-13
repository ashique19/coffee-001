<?php

namespace Tests\Support;

use App\Models\Branch;

/**
 * Authenticated GET routes used for post-login smoke tests (single-client).
 */
final class AuthenticatedRouteCatalog
{
    /**
     * @return list<array{0: string, 1: array<string, mixed>, 2: int, 3?: string|null}>
     */
    public static function adminRoutes(Branch $branch): array
    {
        return [
            ['admin.dashboard', [], 200, null],
            ['admin.business', [], 200, null],
            ['admin.pos.home', [], 200, null],
            ['admin.pos.terminal', ['branch' => $branch], 200, null],
            ['admin.pos.history', ['branch' => $branch], 200, null],
            ['admin.pos.eod', ['branch' => $branch], 200, null],
            ['admin.pos.ops', ['branch' => $branch], 200, null],
            ['admin.reports', [], 200, null],
            ['admin.storefront', [], 200, null],
            ['admin.customers', [], 200, null],
            ['admin.invoices', [], 200, null],
            ['admin.invoices.issue', ['mode' => 'simplified'], 200, null],
            ['admin.catalog', [], 200, null],
            ['admin.inventory', [], 200, null],
            ['admin.team', [], 200, null],
        ];
    }
}
