<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\PosOrder;
use App\Support\EnabledLocales;
use App\Support\Invoicing\InvoicePrintSheet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoicePrintController extends Controller
{
    public function __invoke(Request $request, Invoice $invoice): View|RedirectResponse
    {
        $order = null;
        if ($invoice->source_type === PosOrder::class && $invoice->source_id !== null) {
            $order = PosOrder::query()->with(['branch', 'cashier', 'customer'])->find($invoice->source_id);
        }

        $layout = (string) $request->query('layout', '');
        $locale = EnabledLocales::resolve($request->query('locale', app()->getLocale()));

        if ($layout === '' && $order !== null) {
            return redirect()->route('admin.pos.receipt', [
                'order' => $order,
                'locale' => in_array($locale, ['en', 'ar'], true) ? $locale : 'en',
            ]);
        }

        if ($layout !== '' && $layout !== 'cash') {
            if ($order !== null) {
                return redirect()->route('admin.pos.receipt', [
                    'order' => $order,
                    'locale' => in_array($locale, ['en', 'ar'], true) ? $locale : 'en',
                ]);
            }

            abort(404, 'Only the cash (80mm) print layout is available.');
        }

        app()->setLocale(in_array($locale, ['en', 'ar'], true) ? $locale : 'en');
        $invoice->loadMissing(['branch', 'referencedInvoice']);

        $t = InvoicePrintSheet::forInvoice($invoice, $order);

        return view('admin.invoices.print-cash', [
            'invoice' => $invoice,
            'order' => $order,
            't' => $t,
            'receiptUrl' => $order !== null
                ? route('admin.pos.receipt', ['order' => $order, 'locale' => app()->getLocale()])
                : null,
            'cashUrl' => null,
            'a4Url' => null,
            'pdfUrl' => null,
            'canDownloadPdf' => false,
            'canResubmitZatca' => false,
            'zatcaNotSent' => false,
            'fallbackBackUrl' => route('admin.invoices'),
        ]);
    }
}
