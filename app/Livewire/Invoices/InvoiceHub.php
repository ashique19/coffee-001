<?php

namespace App\Livewire\Invoices;

use App\Enums\InvoiceDocumentType;
use App\Exceptions\InvoiceSigningException;
use App\Livewire\Concerns\DispatchesToasts;
use App\Models\Invoice;
use App\Models\PosOrder;
use App\Models\BusinessSetting;
use App\Business\BusinessContext;
use App\Services\Invoicing\InvoiceCorrectedDocumentService;
use App\Services\Invoicing\InvoiceZatcaResubmitService;
use App\Support\Invoicing\InvoiceSubmissionStatus;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use App\Livewire\Concerns\ValidatesFieldsLive;
use Livewire\Component;
use App\Livewire\Concerns\ResolvesBusinessContext;
use Livewire\WithPagination;
use Spatie\Permission\PermissionRegistrar;

#[Layout('layouts.pos')]
class InvoiceHub extends Component
{
    use AuthorizesRequests;
    use ResolvesBusinessContext;
    use DispatchesToasts;
    use ValidatesFieldsLive;
    use WithPagination;


    #[Url]
    public string $search = '';

    #[Url]
    public string $typeFilter = 'all';

    #[Url]
    public string $signingFilter = 'all';

    public function mount(): void
    {
        $this->resolveBusiness();
        BusinessContext::set($this->tenant);
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->tenant->id);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSigningFilter(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        return view('livewire.invoices.invoice-hub', [
            'invoices' => $this->invoicePage(),
        ]);
    }

    private function invoicePage(): LengthAwarePaginator
    {
        $query = Invoice::query()
            ->with(['referencedInvoice'])
            ->where('status', 'issued')
            ->orderByDesc('issued_at')
            ->orderByDesc('id');

        $term = trim($this->search);
        if ($term !== '') {
            $like = '%'.$term.'%';
            $query->where(function ($q) use ($like): void {
                $q->where('document_number', 'like', $like)
                    ->orWhere('official_number', 'like', $like)
                    ->orWhere('invoice_hash', 'like', $like);
            });
        }

        if ($this->typeFilter !== 'all') {
            $query->where('document_type', $this->typeFilter);
        }

        return $query->paginate(15);
    }

    /**
     * @return array<string, mixed>
     */
    protected function liveValidationRules(): array
    {
        return [];
    }

    public function submissionStatus(Invoice $invoice): array
    {
        return InvoiceSubmissionStatus::forInvoice($invoice);
    }

    public function resubmit(int $invoiceId): void
    {
        $this->authorize('invoices.issue');
        $invoice = Invoice::query()->findOrFail($invoiceId);

        try {
            $result = app(InvoiceZatcaResubmitService::class)->resubmit($invoice);
        } catch (InvoiceSigningException $e) {
            $this->toastError($e->getMessage());

            return;
        }

        $fresh = $result->invoice;
        $number = $fresh->displayNumber();
        if ($fresh->isSubmissionAccepted()) {
            $this->toastSuccess(__('invoices.resubmit_accepted', ['number' => $number]));
        } elseif ($fresh->isSubmissionRetry()) {
            $this->toastInfo(__('invoices.issue_sending', ['number' => $number]));
        } elseif ($fresh->isSubmissionRejected()) {
            $this->toastError(__('invoices.issue_rejected', [
                'number' => $number,
                'reason' => $result->signingFailureMessage ?: __('invoices.signing.rejected_detail'),
            ]));
        }
    }

    public function issueCorrectiveCreditNote(int $invoiceId): void
    {
        $this->authorize('invoices.issue');
        $invoice = Invoice::query()->findOrFail($invoiceId);

        try {
            $result = app(InvoiceCorrectedDocumentService::class)->issueCreditNote($invoice);
        } catch (\Throwable $e) {
            $this->toastError($e->getMessage());

            return;
        }

        $this->toastForCorrection($result->invoice, $result->signingFailureMessage, credit: true);
    }

    public function issueCorrectedInvoice(int $invoiceId): void
    {
        $this->authorize('invoices.issue');
        $invoice = Invoice::query()->findOrFail($invoiceId);

        try {
            $result = app(InvoiceCorrectedDocumentService::class)->issueCorrectedInvoice($invoice);
        } catch (\Throwable $e) {
            $this->toastError($e->getMessage());

            return;
        }

        $this->toastForCorrection($result->invoice, $result->signingFailureMessage, credit: false);
    }

    private function toastForCorrection(\App\Models\Invoice $issued, ?string $reason, bool $credit): void
    {
        $number = $issued->displayNumber();
        $key = $credit ? 'invoices.correction.credit_issued' : 'invoices.correction.invoice_issued';
        if ($issued->isSubmissionRetry()) {
            $this->toastInfo(__('invoices.issue_sending', ['number' => $number]));
        } elseif ($issued->isSubmissionRejected()) {
            $this->toastError(__('invoices.issue_rejected', [
                'number' => $number,
                'reason' => $reason ?: __('invoices.signing.rejected_detail'),
            ]));
        } else {
            $this->toastSuccess(__($key, ['number' => $number]));
        }
    }

    public function orderFor(Invoice $invoice): ?PosOrder
    {
        if ($invoice->source_type !== PosOrder::class || $invoice->source_id === null) {
            return null;
        }

        return PosOrder::query()->find($invoice->source_id);
    }
}
