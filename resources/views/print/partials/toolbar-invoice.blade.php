@php
    $tbLocale = app()->getLocale();
    $tbRtl = \App\Support\EnabledLocales::direction($tbLocale) === 'rtl' || in_array($tbLocale, ['ar', 'ur'], true);
    $tbNumber = (string) ($toolbarNumber ?? $invoice->document_number ?? '');
    $tbTitleKey = ($toolbarKind ?? 'invoice') === 'receipt' ? 'invoices.print_toolbar.receipt_title' : 'invoices.print_toolbar.invoice_title';
    $tbTitle = str_replace(
        '%NUM%',
        '<bdi dir="ltr" class="rcpt-toolbar__number">'.e($tbNumber).'</bdi>',
        e(__($tbTitleKey, ['number' => '%NUM%'])),
    );
@endphp
<nav
    class="invoice-print-toolbar invoice-print-toolbar--screen rcpt-toolbar no-print"
    dir="{{ $tbRtl ? 'rtl' : 'ltr' }}"
    lang="{{ $tbLocale }}"
    aria-label="{{ __('invoices.print_toolbar.aria') }}"
    data-receipt-toolbar
>
    <div class="rcpt-toolbar__inner">
        <div class="rcpt-toolbar__lead">
            <a class="rcpt-toolbar__btn rcpt-toolbar__btn--ghost invoice-print-toolbar__back" href="{{ $fallbackBackUrl ?? url()->previous() }}" data-receipt-toolbar-back>
                <svg class="rcpt-toolbar__icon rcpt-toolbar__icon--back" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>
                <span>{{ __('invoices.print_toolbar.back') }}</span>
            </a>
            <h1 class="rcpt-toolbar__title" data-receipt-toolbar-title>{!! $tbTitle !!}</h1>
        </div>
        <div class="rcpt-toolbar__actions">
            @if (! empty($receiptUrl))
                <a class="rcpt-toolbar__btn rcpt-toolbar__btn--secondary" href="{{ $receiptUrl }}" data-receipt-toolbar-switch>
                    <svg class="rcpt-toolbar__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 2h12v20l-3-2-3 2-3-2-3 2z"/><path d="M9 7h6M9 11h6M9 15h4"/></svg>
                    <span>{{ __('invoices.print_toolbar.pos_receipt') }}</span>
                </a>
            @endif
            @if (! empty($cashUrl))
                <a class="rcpt-toolbar__btn rcpt-toolbar__btn--secondary" href="{{ $cashUrl }}" data-receipt-toolbar-switch>
                    <svg class="rcpt-toolbar__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/></svg>
                    <span>{{ __('invoices.print_toolbar.cash_receipt') }}</span>
                </a>
            @endif
            <button type="button" class="rcpt-toolbar__btn rcpt-toolbar__btn--primary invoice-print-toolbar__print" onclick="window.print()" data-receipt-toolbar-print>
                <svg class="rcpt-toolbar__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9V2h12v7"/><rect x="2" y="9" width="20" height="9" rx="2"/><path d="M6 14h12v8H6z"/></svg>
                <span>{{ __('invoices.print_toolbar.print') }}</span>
            </button>
        </div>
    </div>
</nav>
