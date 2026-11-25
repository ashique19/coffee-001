<?php

namespace App\Services\Invoicing;

use App\Business\BusinessContext;
use App\Exceptions\InvoiceSigningException;
use App\Models\Invoice;
use App\Services\InvoiceAuditService;
use App\Services\Invoicing\Signing\InvoiceSigningGatewayFactory;
use App\Services\Invoicing\Signing\InvoiceSigningRequestFactory;
use App\Support\Invoicing\ZHeartSubmissionOutcome;

final class InvoiceZatcaResubmitService
{
    public function __construct(
        private readonly InvoiceSigningGatewayFactory $signingFactory,
        private readonly InvoiceAuditService $auditService,
    ) {}

    public function resubmit(Invoice $invoice): InvoiceIssueResult
    {
        if (! $invoice->isIssued()) {
            throw new InvoiceSigningException(__('invoices.errors.resubmit_not_issued'));
        }

        if ($invoice->isSubmissionRejected()) {
            throw new InvoiceSigningException(__('invoices.errors.resubmit_rejected'));
        }

        if ($invoice->isSubmissionAccepted()) {
            return new InvoiceIssueResult($invoice, true);
        }

        $uuid = $invoice->invoiceUuid();
        if ($uuid === null || $uuid === '') {
            throw new InvoiceSigningException(__('invoices.errors.uuid_missing'));
        }

        $business = BusinessContext::require();
        $request = InvoiceSigningRequestFactory::fromIssuedInvoice($invoice, $business);
        $signed = $this->signingFactory->gateway()->submitInvoiceToZatca($request);

        $invoice->applySubmissionResult($signed);
        $fresh = $invoice->fresh();

        $this->auditService->record($fresh, 'invoice.zatca_resubmit', [
            'outcome' => $signed['outcome'] ?? null,
            'invoice_uuid' => $uuid,
            'request_id' => $signed['request_id'] ?? null,
            'official_number' => $fresh->official_number,
        ]);

        $outcome = $signed['outcome'] ?? null;
        $ok = $outcome === ZHeartSubmissionOutcome::ACCEPTED;

        return new InvoiceIssueResult($fresh, $ok, $signed['message'] ?? null);
    }
}
