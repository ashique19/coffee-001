<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;

final class DocumentTitle
{
    /**
     * Per-page admin document title (without the brand suffix).
     */
    public static function page(?string $override = null): string
    {
        if (filled($override)) {
            return trim((string) $override);
        }

        $name = Route::currentRouteName() ?? '';
        $mode = (string) (request()->route('mode') ?? '');

        $key = match (true) {
            $name === 'admin.dashboard' => 'dashboard.title',
            $name === 'admin.pos.home' => 'pos.title',
            str_contains($name, 'admin.pos.terminal') => 'pos.title',
            str_contains($name, 'admin.pos.history') => 'pos.history',
            str_contains($name, 'admin.pos.receipt') => 'pos.receipt',
            str_contains($name, 'admin.pos.eod') => 'pos.end_of_day',
            str_contains($name, 'admin.pos.ops') => 'pos.ops_dashboard',
            str_contains($name, 'admin.reports') => 'reports.title',
            str_contains($name, 'admin.storefront') => 'storefront.title',
            str_contains($name, 'admin.customers') => 'customers.title',
            $name === 'admin.invoices.issue' && $mode === 'standard' => 'invoices.issue_standard_title',
            str_contains($name, 'admin.invoices.issue') => 'invoices.issue_simplified_title',
            str_contains($name, 'admin.invoices.print') => 'invoices.print',
            str_contains($name, 'admin.invoices') => 'invoices.title',
            str_contains($name, 'admin.catalog') => 'catalog.title',
            str_contains($name, 'admin.inventory') => 'inventory.title',
            str_contains($name, 'admin.team') => 'operations.title',
            str_contains($name, 'admin.business') => 'business.title',
            default => null,
        };

        return $key === null ? '' : trim((string) __($key));
    }

    /**
     * Join a page label and brand with an em dash, omitting empty sides.
     */
    public static function join(string $left, string $right): string
    {
        $left = trim($left);
        $right = trim($right);

        if ($left !== '' && $right !== '') {
            return $left.' — '.$right;
        }

        return $left !== '' ? $left : $right;
    }
}
