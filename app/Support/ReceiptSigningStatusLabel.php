<?php

namespace App\Support;

use App\Models\Invoice;
use App\Support\Invoicing\InvoiceSubmissionStatus;

/**
 * Human-readable Z-Heart / signing submission state for receipts (no compliance claims).
 */
final class ReceiptSigningStatusLabel
{
    public static function forInvoice(Invoice $invoice): array
    {
        $status = InvoiceSubmissionStatus::forInvoice($invoice);

        return [
            'tone' => $status['tone'],
            'title' => $status['title'],
            'detail' => $status['detail'],
        ];
    }
}
