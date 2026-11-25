@php
    /** @var array<string, mixed> $t */
    $cash = \App\Support\Invoicing\InvoiceCashReceiptView::fromPrintData($t, $order ?? null);
    $tot = $t['totals'] ?? [];
@endphp
<x-layouts.print>
    @push('head')
        <title>{{ $cash['titles']['en'] }} {{ $t['document_number'] }}</title>
        @vite(['resources/css/invoice-print.css', 'resources/js/receipt.js'])
        <style>
            @page { size: 80mm auto; margin: 0; }
            @media print {
                .invoice-print-root.invoice-print-sheet.invoice-print-sheet--cash {
                    width: 80mm !important;
                    max-width: 80mm !important;
                    min-width: 0 !important;
                    padding: 4mm !important;
                    zoom: normal !important;
                    transform: none !important;
                }
            }
        </style>

    @endpush

    @include('print.partials.toolbar-invoice', [
        'invoice' => $invoice,
        'receiptUrl' => $receiptUrl ?? null,
        'cashUrl' => $cashUrl ?? null,
        'a4Url' => $a4Url ?? null,
        'zatcaNotSent' => $zatcaNotSent ?? false,
        'fallbackBackUrl' => $fallbackBackUrl ?? route('admin.invoices'),
        'pdfUrl' => $pdfUrl ?? null,
        'canDownloadPdf' => $canDownloadPdf ?? true,
        'canResubmitZatca' => $canResubmitZatca ?? false,
        'zatcaResubmitUrl' => $zatcaResubmitUrl ?? null,
        'zatcaResubmitLabel' => $zatcaResubmitLabel ?? null,
        'toolbarKind' => isset($order) && $order !== null ? 'receipt' : 'invoice',
        'toolbarNumber' => isset($order) && $order !== null && filled($order->order_number) ? $order->order_number : $t['document_number'],
    ])

    @include('admin.invoices.partials.cash-receipt-sheet', ['t' => $t, 'cash' => $cash])
</x-layouts.print>
