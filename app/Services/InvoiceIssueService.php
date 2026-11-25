<?php

namespace App\Services;

use App\Business\BusinessContext;
use App\Enums\InvoiceDocumentType;
use App\Enums\InvoiceStatus;
use App\Models\Branch;
use App\Models\Invoice;
use App\Services\Invoicing\InvoiceIssueResult;
use App\Services\Invoicing\Signing\InvoiceSigningGatewayFactory;
use App\Services\Invoicing\Signing\InvoiceSigningRequest;
use App\Support\Invoicing\InvoiceDocumentTotals;
use App\Support\Invoicing\InvoicePayloadLines;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class InvoiceIssueService
{
    public function __construct(
        private readonly InvoiceSigningGatewayFactory $signingFactory,
        private readonly InvoiceAuditService $auditService,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $lineItems
     */
    public function issueDraft(
        ?Branch $branch,
        string $documentNumber,
        InvoiceDocumentType $type,
        array $lineItems,
        ?Invoice $referencedInvoice = null,
    ): Invoice {
        $invoice = Invoice::query()->create([
            'branch_id' => $branch?->id,
            'document_number' => $documentNumber,
            'document_type' => $type,
            'status' => InvoiceStatus::Draft,
            'referenced_invoice_id' => $referencedInvoice?->id,
            'payload' => [
                'lines' => $lineItems,
            ],
        ]);

        $this->auditService->record($invoice, 'invoice.draft_created', [
            'document_type' => $type->value,
        ]);

        return $invoice;
    }

    public function issue(Invoice $invoice): InvoiceIssueResult
    {
        if ($invoice->isIssued()) {
            return new InvoiceIssueResult($invoice, true);
        }

        $business = BusinessContext::require();
        $lines = InvoicePayloadLines::fromInvoice($invoice);

        $now = Carbon::now('UTC');
        $referenced = $invoice->referencedInvoice;
        $invoiceCategory = $this->resolveInvoiceCategory($invoice, $referenced);
        $payload = $invoice->payload ?? [];
        $invoiceUuid = (string) (($payload['invoice_uuid'] ?? null) ?: Str::uuid());
        $documentDiscount = (float) ($payload['invoice_discount_ex_vat'] ?? 0);
        $dueDate = $this->resolveDueDate($invoice, $now);

        $signingRequest = new InvoiceSigningRequest(
            business: $business,
            documentNumber: $invoice->document_number,
            documentType: $invoice->document_type,
            invoiceCategory: $invoiceCategory,
            lines: $lines,
            invoicePayload: $payload,
            issueDateTimeUtc: $now,
            invoiceUuid: $invoiceUuid,
            referencedInvoiceNumber: $referenced?->document_number,
            referencedInvoiceUuid: $referenced
                ? (string) ($referenced->payload['invoice_uuid'] ?? '')
                : null,
            noteReason: $this->resolveNoteReason($invoice),
            documentDiscountExVat: $documentDiscount,
        );

        $signed = $this->signingFactory->gateway()->signIssuedInvoice($signingRequest, $invoice);

        $totals = InvoiceDocumentTotals::compute($lines, $documentDiscount);

        $payloadMerge = array_merge($payload, [
            'lines' => $payload['lines'] ?? [],
            'invoice_uuid' => $signed['invoice_uuid'],
            'totals' => [
                'subtotal_ex_vat' => $totals['subtotal_ex_vat'],
                'total_vat' => $totals['total_vat'],
                'total_inc_vat' => $totals['total_inc_vat'],
            ],
        ]);

        if (! empty($signed['request_id'])) {
            $payloadMerge['zheart_request_id'] = $signed['request_id'];
        }

        if (! empty($signed['submission_skip_reason'])) {
            $payloadMerge['zheart_submission_skip'] = $signed['submission_skip_reason'];
        }

        if (! empty($signed['reporting_status'])) {
            $payloadMerge['zheart_reporting_status'] = $signed['reporting_status'];
        }

        $invoice->forceFill([
            'status' => InvoiceStatus::Issued,
            'icv' => $signed['icv'],
            'invoice_hash' => $signed['hash'],
            'previous_invoice_hash' => $signed['previous_invoice_hash'],
            'signed_xml' => $signed['cleared_xml'] ?? $signed['xml'],
            'qr_tlv_base64' => $signed['qr'],
            'zheart_seller' => is_array($signed['seller'] ?? null) ? $signed['seller'] : null,
            'issued_at' => $now,
            'due_date' => $dueDate,
            'payload' => $payloadMerge,
        ])->save();

        $this->auditService->record($invoice, 'invoice.issued', [
            'icv' => $signed['icv'],
            'invoice_hash' => $signed['hash'],
        ]);

        return new InvoiceIssueResult($invoice->fresh(), true);
    }

    private function resolveInvoiceCategory(Invoice $invoice, ?Invoice $referenced): string
    {
        $fromPayload = $invoice->payload['invoice_category'] ?? null;
        if (is_string($fromPayload) && $fromPayload !== '') {
            return $fromPayload;
        }

        return match ($invoice->document_type) {
            InvoiceDocumentType::Simplified => 'simplified',
            InvoiceDocumentType::Standard => 'standard',
            InvoiceDocumentType::CreditNote, InvoiceDocumentType::DebitNote => match ($referenced?->document_type) {
                InvoiceDocumentType::Simplified => 'simplified',
                default => 'standard',
            },
        };
    }

    private function resolveNoteReason(Invoice $invoice): ?string
    {
        $reason = $invoice->payload['refund_reason'] ?? $invoice->payload['note_reason'] ?? null;

        return is_string($reason) && $reason !== '' ? $reason : null;
    }

    private function resolveDueDate(Invoice $invoice, Carbon $now): string
    {
        $payloadDue = trim((string) (($invoice->payload['buyer_due_date'] ?? '')));
        if ($payloadDue !== '') {
            try {
                return Carbon::parse($payloadDue, 'Asia/Riyadh')->toDateString();
            } catch (\Throwable) {
                // fall through
            }
        }

        return $now->copy()->timezone('Asia/Riyadh')->toDateString();
    }
}
