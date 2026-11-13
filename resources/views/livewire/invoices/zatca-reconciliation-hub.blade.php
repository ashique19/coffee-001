<div class="zatca-reconcile pb-24 lg:pb-8" @if($drainBlocked) wire:poll.5s="resumeDrain" @endif>
    <header class="mb-4">
        <h1 class="app-page-title">{{ __('zatca_reconcile.title') }}</h1>
        <p class="app-page-lead">{{ __('zatca_reconcile.subtitle') }}</p>
    </header>

    @if ($drainRunning)
        <p class="app-card mb-4 border border-[var(--app-accent)]/40 bg-[var(--app-accent)]/10 p-4 text-sm">
            {{ __('zatca_reconcile.drain_running') }}
        </p>
    @elseif ($drainBlocked)
        <p class="app-card mb-4 border border-amber-500/40 bg-amber-500/10 p-4 text-sm text-amber-950">
            {{ __('zatca_reconcile.drain_locked') }}
        </p>
    @else
        <p class="app-card mb-4 border border-[var(--app-accent)]/30 bg-[var(--app-accent)]/5 p-4 text-sm text-[var(--app-muted)]">
            {{ __('zatca_reconcile.auto_push_notice') }}
            @if ($pendingCount > 0)
                <span class="mt-2 block font-medium text-[var(--app-text)]">{{ __('zatca_reconcile.pending_count', ['count' => $pendingCount]) }}</span>
            @endif
        </p>
    @endif

    <section class="app-filter-bar">
        <div class="app-section-tabs w-full flex-wrap" role="group" aria-label="{{ __('zatca_reconcile.date_filter_aria') }}">
            <button
                type="button"
                wire:click="clearDateFilter"
                @class(['app-segment-tab', 'app-segment-tab--active' => $datePreset === ''])
            >{{ __('zatca_reconcile.date_all') }}</button>
            <button
                type="button"
                wire:click="setDatePreset('today')"
                @class(['app-segment-tab', 'app-segment-tab--active' => $datePreset === 'today'])
            >{{ __('zatca_reconcile.date_today') }}</button>
            <button
                type="button"
                wire:click="setDatePreset('month')"
                @class(['app-segment-tab', 'app-segment-tab--active' => $datePreset === 'month'])
            >{{ __('zatca_reconcile.date_this_month') }}</button>
            <button
                type="button"
                wire:click="setDatePreset('30d')"
                @class(['app-segment-tab', 'app-segment-tab--active' => $datePreset === '30d'])
            >{{ __('zatca_reconcile.date_last_30_days') }}</button>
        </div>
        <p class="text-xs leading-relaxed text-[var(--app-muted)] sm:max-w-xs lg:ms-auto lg:text-end">
            {{ __('zatca_reconcile.date_filter_hint') }}
        </p>
    </section>

    <div class="space-y-2">
        @forelse ($rows as $invoice)
            @php
                $submission = $invoice->zatcaSubmission;
                $statusKey = $submission?->displayStatusKey() ?? 'none';
                $window = $this->submissionStatusLabel($submission, $invoice);
            @endphp
            <article class="app-list-card app-list-card--stacked" wire:key="recon-{{ $invoice->id }}">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <p class="font-semibold">{{ $invoice->document_number }}</p>
                        <p class="text-xs text-[var(--app-muted)]">
                            {{ __('invoices.type.'.$invoice->document_type->value) }}
                            · {{ $invoice->issued_at?->timezone(\App\Support\Reconcile\ReconcileIssueDateFilter::BUSINESS_TZ)->format('Y-m-d H:i') ?? '—' }}
                        </p>
                        @if ($window)
                            <p class="mt-1 text-xs text-[var(--app-muted)]">{{ $window }}</p>
                        @endif
                    </div>
                    <span @class([
                        'pos-status-pill',
                        'pos-status-pill--paid' => in_array($statusKey, ['reported', 'reported_with_warnings'], true),
                        'pos-status-pill--refunded' => $statusKey === 'pending',
                        'pos-status-pill--cancelled' => $statusKey === 'failed',
                        'pos-status-pill--partial' => $statusKey === 'reported_with_warnings',
                    ])>{{ __('zatca_reconcile.status.'.$statusKey) }}</span>
                </div>

                @if ($submission && $submission->last_error)
                    <p class="rounded-xl bg-red-500/10 p-3 text-sm text-red-800 dark:text-red-200">
                        {{ $submission->last_error }}
                    </p>
                    <p class="text-xs text-[var(--app-muted)]">{{ __('zatca_reconcile.failure_hint') }}</p>
                @endif

                @if ($submission && $submission->hasWarnings())
                    <ul class="rounded-xl bg-amber-500/10 p-3 text-sm text-amber-900 dark:text-amber-100">
                        @foreach ($submission->last_warnings as $warning)
                            <li>{{ $warning }}</li>
                        @endforeach
                    </ul>
                @endif

                <div class="flex flex-wrap gap-2">
                    @if ($submission && $submission->canRetry())
                        <button type="button" class="tap-action-primary min-h-11 px-4 text-sm" wire:click="retry({{ $submission->id }})" wire:loading.attr="disabled">
                            {{ __('zatca_reconcile.retry') }}
                        </button>
                    @endif
                    @if ($invoice->source_type)
                        <a class="tap-action-muted min-h-11 px-4 text-sm" href="{{ route('admin.invoices.print', [$tenant, $invoice]) }}" target="_blank" rel="noopener">{{ __('invoices.print') }}</a>
                    @endif
                </div>
            </article>
        @empty
            <p class="app-card p-6 text-center text-sm text-[var(--app-muted)]">{{ __('zatca_reconcile.empty') }}</p>
        @endforelse
    </div>

    <div class="mt-4">{{ $rows->links() }}</div>
</div>
