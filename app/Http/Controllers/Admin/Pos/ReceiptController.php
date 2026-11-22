<?php

namespace App\Http\Controllers\Admin\Pos;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Models\PosOrder;
use App\Support\EnabledLocales;
use App\Support\Invoicing\InvoicePrintSheet;
use Illuminate\View\View;

class ReceiptController extends Controller
{
    public function __invoke(PosOrder $order): View
    {
        if ($order->invoice_id === null) {
            abort(404, 'Invoice not issued for this order.');
        }

        $order->loadMissing(['branch', 'cashier', 'customer']);
        $invoice = $order->invoice()->firstOrFail();
        $locale = EnabledLocales::resolve(request()->query('locale', app()->getLocale()));
        app()->setLocale($locale);

        $t = InvoicePrintSheet::forInvoice($invoice, $order, BusinessSetting::singleton());

        return view('admin.invoices.print-cash', [
            'invoice' => $invoice,
            'order' => $order,
            't' => $t,
            'receiptUrl' => null,
            'cashUrl' => route('admin.invoices.print', ['invoice' => $invoice, 'layout' => 'cash', 'locale' => $locale]),
            'a4Url' => null,
            'pdfUrl' => null,
            'canDownloadPdf' => false,
            'canResubmitZatca' => false,
            'zatcaNotSent' => false,
            'fallbackBackUrl' => route('admin.pos.home'),
        ]);
    }
}
