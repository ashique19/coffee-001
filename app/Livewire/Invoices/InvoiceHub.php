<?php

namespace App\Livewire\Invoices;

use App\Enums\InvoiceDocumentType;
use App\Enums\ZatcaSubmissionStatus;
use App\Models\Invoice;
use App\Models\PosOrder;
use App\Models\BusinessSetting;
use App\Business\BusinessContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use App\Livewire\Concerns\ValidatesFieldsLive;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\PermissionRegistrar;

#[Layout('layouts.pos')]
class InvoiceHub extends Component
{
    use ValidatesFieldsLive;
    use WithPagination;

    public Tenant $tenant;

    #[Url]
    public string $search = '';

    #[Url]
    public string $typeFilter = 'all';

    #[Url]
    public string $zatcaFilter = 'all';

    public function mount(Tenant $tenant): void
    {
        $this->tenant = $tenant;
        BusinessContext::set($tenant);
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingZatcaFilter(): void
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
            ->with(['zatcaSubmission', 'referencedInvoice'])
            ->where('status', 'issued')
            ->orderByDesc('issued_at')
            ->orderByDesc('id');

        $term = trim($this->search);
        if ($term !== '') {
            $like = '%'.$term.'%';
            $query->where(function ($q) use ($like): void {
                $q->where('document_number', 'like', $like)
                    ->orWhere('invoice_hash', 'like', $like);
            });
        }

        if ($this->typeFilter !== 'all') {
            $query->where('document_type', $this->typeFilter);
        }

        if ($this->zatcaFilter !== 'all') {
            if ($this->zatcaFilter === 'reported') {
                $query->whereHas('zatcaSubmission', fn ($q) => $q
                    ->where('status', ZatcaSubmissionStatus::Accepted)
                    ->whereNull('last_warnings'));
            } elseif ($this->zatcaFilter === 'reported_with_warnings') {
                $query->whereHas('zatcaSubmission', fn ($q) => $q
                    ->where('status', ZatcaSubmissionStatus::Accepted)
                    ->whereNotNull('last_warnings'));
            } else {
                $status = ZatcaSubmissionStatus::from($this->zatcaFilter);
                $query->whereHas('zatcaSubmission', fn ($q) => $q->where('status', $status));
            }
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

    public function orderFor(Invoice $invoice): ?PosOrder
    {
        if ($invoice->source_type !== PosOrder::class || $invoice->source_id === null) {
            return null;
        }

        return PosOrder::query()->find($invoice->source_id);
    }
}
