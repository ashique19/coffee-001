<?php

namespace App\Support\Invoicing;

use App\Models\Invoice;

/**
 * Human-readable Z-Heart submission state for the invoice hub and receipts.
 */
final class InvoiceSubmissionStatus
{
    /**
     * @return array{
     *     key: string,
     *     tone: string,
     *     title: string,
     *     detail: string,
     *     can_resubmit: bool,
     *     can_correct: bool,
     *     show_simplified_correction_guidance: bool
     * }
     */
    public static function forInvoice(Invoice $invoice): array
    {
        $payload = $invoice->payload ?? [];
        $outcome = ZHeartSubmissionOutcome::normalize($payload['zheart_outcome'] ?? null);
        $skip = $payload['zheart_submission_skip'] ?? null;
        $reportingStatus = is_string($payload['zheart_reporting_status'] ?? null)
            ? (string) $payload['zheart_reporting_status']
            : null;
        $requestId = is_string($payload['zheart_request_id'] ?? null) ? (string) $payload['zheart_request_id'] : null;
        $reason = is_string($payload['zheart_last_error'] ?? null) ? trim((string) $payload['zheart_last_error']) : '';

        if ($outcome === ZHeartSubmissionOutcome::RETRY) {
            return self::pack(
                key: 'retry',
                tone: 'retry',
                title: __('invoices.signing.retry'),
                detail: $reason !== '' ? $reason : __('invoices.signing.retry_detail'),
                canResubmit: true,
                canCorrect: false,
                simplifiedGuidance: false,
            );
        }

        if ($outcome === ZHeartSubmissionOutcome::REJECTED) {
            $simplified = self::isSimplifiedDocument($invoice);

            return self::pack(
                key: 'rejected',
                tone: 'rejected',
                title: __('invoices.signing.rejected'),
                detail: $reason !== '' ? $reason : ($simplified
                    ? __('invoices.signing.rejected_simplified_detail')
                    : __('invoices.signing.rejected_detail')),
                canResubmit: false,
                canCorrect: true,
                simplifiedGuidance: $simplified,
            );
        }

        if ($skip === 'integration_disabled') {
            return self::pack(
                key: 'integration_off',
                tone: 'skipped',
                title: __('pos.receipt_status_integration_off_title'),
                detail: __('pos.receipt_status_integration_off_detail'),
            );
        }

        if ($skip === 'submit_disabled' || ($outcome === null && ! config('zheart.auto_submit_signed'))) {
            return self::pack(
                key: 'sandbox',
                tone: 'sandbox',
                title: __('pos.receipt_status_sandbox_title'),
                detail: __('pos.receipt_status_sandbox_detail'),
            );
        }

        if ($outcome === ZHeartSubmissionOutcome::ACCEPTED) {
            $detail = $reportingStatus
                ? __('pos.receipt_status_reporting_detail', ['status' => $reportingStatus])
                : __('invoices.signing.accepted_detail');

            return self::pack(
                key: 'accepted',
                tone: 'accepted',
                title: __('invoices.signing.accepted'),
                detail: $detail,
            );
        }

        if ($invoice->signed_xml === null || $invoice->signed_xml === '') {
            return self::pack(
                key: 'not_reported',
                tone: 'not_reported',
                title: __('pos.receipt_status_not_reported_title'),
                detail: __('pos.receipt_status_not_reported_detail'),
            );
        }

        if (is_string($reportingStatus) && $reportingStatus !== '') {
            return self::pack(
                key: 'accepted',
                tone: 'accepted',
                title: __('invoices.signing.accepted'),
                detail: __('pos.receipt_status_reporting_detail', ['status' => $reportingStatus]),
            );
        }

        if (is_string($requestId) && $requestId !== '') {
            return self::pack(
                key: 'accepted',
                tone: 'accepted',
                title: __('invoices.signing.accepted'),
                detail: __('pos.receipt_status_reported_detail'),
            );
        }

        return self::pack(
            key: 'not_reported',
            tone: 'not_reported',
            title: __('pos.receipt_status_not_reported_title'),
            detail: __('pos.receipt_status_pending_detail'),
        );
    }

    public static function isSimplifiedDocument(Invoice $invoice): bool
    {
        $category = (string) ($invoice->payload['invoice_category'] ?? '');
        if ($category === 'simplified') {
            return true;
        }

        return $invoice->document_type->value === 'simplified';
    }

    /**
     * @return array{
     *     key: string,
     *     tone: string,
     *     title: string,
     *     detail: string,
     *     can_resubmit: bool,
     *     can_correct: bool,
     *     show_simplified_correction_guidance: bool
     * }
     */
    private static function pack(
        string $key,
        string $tone,
        string $title,
        string $detail,
        bool $canResubmit = false,
        bool $canCorrect = false,
        bool $simplifiedGuidance = false,
    ): array {
        return [
            'key' => $key,
            'tone' => $tone,
            'title' => $title,
            'detail' => $detail,
            'can_resubmit' => $canResubmit,
            'can_correct' => $canCorrect,
            'show_simplified_correction_guidance' => $simplifiedGuidance,
        ];
    }
}
