<?php

namespace App\Http\Controllers\Admin\Pos;

use App\Http\Controllers\Controller;
use App\Models\PosOrder;
use App\Models\BusinessSetting;
use App\Support\EnabledLocales;
use App\Support\Pos\ReceiptThermalPresenter;
use App\Support\ReceiptComplianceLabel;
use App\Support\Zatca\TenantZatcaGate;
use Illuminate\View\View;

class ReceiptController extends Controller
{
    public function __invoke(Tenant $tenant, PosOrder $order): View
    {
        if ((int) $order->tenant_id !== (int) $tenant->id) {
            abort(404);
        }

        if ($order->invoice_id === null) {
            abort(404, 'Invoice not issued for this order.');
        }

        $order->loadMissing(['branch', 'cashier', 'customer']);
        $invoice = $order->invoice()->with(['zatcaSubmission', 'egsDevice'])->firstOrFail();
        $locale = EnabledLocales::resolve(request()->query('locale', app()->getLocale()));
        app()->setLocale($locale);

        $submission = $invoice->zatcaSubmission;
        $shouldDrainZatca = config('zheart.submit_to_zatca')
            && TenantZatcaGate::apiActiveForTenant($tenant)
            && $submission !== null
            && $submission->canRetry();

        return view('admin.pos.receipt', [
            'tenant' => $tenant,
            'order' => $order,
            'invoice' => $invoice,
            'locale' => $locale,
            'branch' => $order->branch,
            'compliance' => ReceiptComplianceLabel::forInvoice($invoice),
            'shouldDrainZatca' => $shouldDrainZatca,
            'zatcaReportUrl' => route('admin.pos.zatca.report-next', $tenant),
            'thermal' => ReceiptThermalPresenter::compose($tenant, $order, $invoice, $order->branch),
        ]);
    }
}
