<?php

namespace App\Livewire\Admin;

use App\Models\BusinessSetting;
use App\Services\Inventory\IngredientStockService;
use App\Services\Tenant\TenantDashboardService;
use App\Support\ChartScale;
use App\Business\BusinessContext;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use App\Livewire\Concerns\ResolvesBusinessContext;
use Spatie\Permission\PermissionRegistrar;

#[Layout('layouts.pos')]
class AdminDashboard extends Component
{
    use ResolvesBusinessContext;

    public function mount(): void
    {
        $this->resolveBusiness();
        BusinessContext::set($this->tenant);
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
        $this->authorize('reports.view');
    }

    public function render(TenantDashboardService $dashboard, IngredientStockService $stock): View
    {
        $snapshot = $dashboard->snapshot($this->tenant);
        $lowStock = $stock->lowStockForTenant();
        $series = $snapshot['recent_sales_series'];
        $seriesTotals = array_map(fn (array $row) => (float) $row['total'], $series);
        $maxTotal = ChartScale::floorMax(1.0, $seriesTotals);

        $invoiceMax = ChartScale::floorMax(1, array_column($snapshot['invoices_by_type'], 'count'));

        $zatca = $snapshot['zatca_status'];
        $zatcaMax = max(1, $zatca['pending'], $zatca['reported'], $zatca['failed']);

        return view('livewire.tenant.tenant-dashboard', [
            'snapshot' => $snapshot,
            'series' => $series,
            'maxTotal' => $maxTotal,
            'invoiceMax' => $invoiceMax,
            'zatca' => $zatca,
            'zatcaMax' => $zatcaMax,
            'lowStock' => $lowStock,
        ]);
    }
}
