<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\PosOrder;
use App\Models\BusinessSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InvoicePrintController extends Controller
{
    public function __invoke(Request $request, Tenant $tenant, Invoice $invoice): RedirectResponse
    {
        if ((int) $invoice->tenant_id !== (int) $tenant->id) {
            abort(404);
        }

        if ($invoice->source_type === PosOrder::class && $invoice->source_id !== null) {
            $order = PosOrder::query()
                ->where('tenant_id', $tenant->id)
                ->findOrFail($invoice->source_id);

            $locale = $request->query('locale', app()->getLocale());

            return redirect()->route('admin.pos.receipt', [
                'tenant' => $tenant,
                'order' => $order,
                'locale' => in_array($locale, ['en', 'ar'], true) ? $locale : 'en',
            ]);
        }

        abort(404, 'Print view is only available for POS-originated invoices.');
    }
}
