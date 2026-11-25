<div class="invoice-hub pb-24 lg:pb-8">
    <header class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="app-page-title">{{ __('invoices.title') }}</h1>
            <p class="app-page-lead">{{ __('invoices.subtitle') }}</p>
        </div>
        @can('invoices.issue')
            <a href="{{ route('admin.invoices.issue.default') }}" class="tap-action-primary min-h-11 text-sm font-semibold" data-testid="issue-invoice">
                {{ __('invoices.issue_submit') }}
            </a>
        @endcan
    </header>

    <section class="app-card mb-4 grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-3">
        <x-form.input :label="__('invoices.search')" name="search" wire:model.live.debounce.300ms="search" :hint="__('invoices.search_hint')" autocomplete="off" wrapperClass="sm:col-span-2" />
        <x-form.select :label="__('invoices.type_filter')" name="typeFilter" wire:model.live="typeFilter">
            <option value="all">{{ __('invoices.filter_all') }}</option>
            <option value="simplified">{{ __('invoices.type.simplified') }}</option>
            <option value="credit_note">{{ __('invoices.type.credit_note') }}</option>
            <option value="debit_note">{{ __('invoices.type.debit_note') }}</option>
            <option value="standard">{{ __('invoices.type.standard') }}</option>
        </x-form.select>
    </section>

    <div class="space-y-2">
        @forelse ($invoices as $invoice)
            @php
                $status = $this->submissionStatus($invoice);
                $order = $this->orderFor($invoice);
            @endphp
            <article class="app-list-card" wire:key="inv-{{ $invoice->id }}" data-testid="invoice-row-{{ $invoice->id }}">
                <div>
                    <p class="font-semibold" data-testid="invoice-display-number">{{ $invoice->displayNumber() }}</p>
                    <p class="text-xs text-[var(--app-muted)]">
                        {{ __('invoices.type.'.$invoice->document_type->value) }}
                        · {{ $invoice->issued_at?->format('Y-m-d H:i') ?? '—' }}
                        @if ($invoice->referencedInvoice)
                            · {{ __('invoices.ref') }} {{ $invoice->referencedInvoice->officialOrDocumentNumber() }}
                        @endif
                        @if ($order)
                            · {{ __('invoices.order') }} {{ $order->order_number }}
                        @endif
                    </p>
                    <p class="mt-2 flex flex-wrap gap-2 text-xs">
                        <span
                            @class([
                                'pos-status-pill',
                                'pos-status-pill--paid' => in_array($status['key'], ['accepted', 'submitted', 'signed'], true),
                                'pos-status-pill--refunded' => in_array($status['key'], ['none', 'not_reported', 'sandbox', 'integration_off'], true),
                                'pos-status-pill--open' => $status['key'] === 'retry',
                                'pos-status-pill--cancelled' => $status['key'] === 'rejected',
                            ])
                            data-testid="invoice-submission-status"
                            data-status="{{ $status['key'] }}"
                        >{{ $status['title'] }}</span>
                        @if ($invoice->document_type->value === 'credit_note')
                            <span class="pos-status-pill pos-status-pill--cancelled">{{ __('invoices.credit_badge') }}</span>
                        @endif
                    </p>
                    @if (filled($status['detail']))
                        <p class="mt-1 text-xs text-[var(--app-muted)]" data-testid="invoice-submission-detail">{{ $status['detail'] }}</p>
                    @endif
                    @if ($status['show_simplified_correction_guidance'])
                        <p class="mt-2 text-xs" data-testid="invoice-simplified-correction-guidance">{{ __('invoices.correction.simplified_guidance') }}</p>
                    @endif
                </div>
                <div class="flex flex-wrap gap-2">
                    @if ($order)
                        <a class="tap-action-muted min-h-11 px-4 text-sm" href="{{ route('admin.pos.history', [$order->branch_id]) }}">{{ __('invoices.view_order') }}</a>
                    @endif
                    <a class="tap-action-muted min-h-11 px-4 text-sm" href="{{ route('admin.invoices.print', [$invoice]) }}" target="_blank" rel="noopener">{{ __('invoices.print') }}</a>
                    @can('invoices.issue')
                        @if ($status['can_resubmit'])
                            <button
                                type="button"
                                class="tap-action-primary min-h-11 px-4 text-sm"
                                wire:click="resubmit({{ $invoice->id }})"
                                wire:loading.attr="disabled"
                                data-testid="invoice-resubmit"
                            >{{ __('invoices.resubmit') }}</button>
                        @endif
                        @if ($status['can_correct'] && $status['show_simplified_correction_guidance'])
                            <button
                                type="button"
                                class="tap-action-primary min-h-11 px-4 text-sm"
                                wire:click="issueCorrectiveCreditNote({{ $invoice->id }})"
                                wire:loading.attr="disabled"
                                data-testid="invoice-correct-credit"
                            >{{ __('invoices.correction.issue_credit') }}</button>
                            <button
                                type="button"
                                class="tap-action-muted min-h-11 px-4 text-sm"
                                wire:click="issueCorrectedInvoice({{ $invoice->id }})"
                                wire:loading.attr="disabled"
                                data-testid="invoice-correct-new"
                            >{{ __('invoices.correction.issue_new') }}</button>
                        @elseif ($status['can_correct'])
                            <button
                                type="button"
                                class="tap-action-primary min-h-11 px-4 text-sm"
                                wire:click="issueCorrectedInvoice({{ $invoice->id }})"
                                wire:loading.attr="disabled"
                                data-testid="invoice-correct-new"
                            >{{ __('invoices.correction.issue_new') }}</button>
                        @endif
                    @endcan
                </div>
            </article>
        @empty
            <p class="app-card p-6 text-center text-sm text-[var(--app-muted)]">{{ __('invoices.empty') }}</p>
        @endforelse
    </div>

    <div class="mt-4">{{ $invoices->links() }}</div>
</div>
