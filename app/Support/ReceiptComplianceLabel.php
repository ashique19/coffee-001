<?php

namespace App\Support;

use App\Enums\ZatcaSubmissionStatus;
use App\Models\Invoice;

/**
 * Human-readable ZATCA reporting state for receipts — never implies full compliance.
 */
final class ReceiptComplianceLabel
{
    public static function forInvoice(Invoice $invoice): array
    {
        $submission = $invoice->relationLoaded('zatcaSubmission')
            ? $invoice->zatcaSubmission
            : $invoice->zatcaSubmission()->first();

        if (! config('zheart.submit_to_zatca') || config('zatca.http_driver') === 'fake') {
            return [
                'tone' => 'sandbox',
                'title' => __('pos.receipt_status_sandbox_title'),
                'detail' => __('pos.receipt_status_sandbox_detail'),
            ];
        }

        if ($submission === null) {
            return [
                'tone' => 'not_reported',
                'title' => __('pos.receipt_status_not_reported_title'),
                'detail' => __('pos.receipt_status_not_reported_detail'),
            ];
        }

        if ($submission->status === ZatcaSubmissionStatus::Accepted) {
            return [
                'tone' => 'reported',
                'title' => __('pos.receipt_status_reported_title'),
                'detail' => __('pos.receipt_status_reported_detail'),
            ];
        }

        return [
            'tone' => 'not_reported',
            'title' => __('pos.receipt_status_not_reported_title'),
            'detail' => __('pos.receipt_status_pending_detail'),
        ];
    }
}
