<nav class="invoice-print-toolbar invoice-print-toolbar--screen no-print" aria-label="{{ __('Print actions') }}">
    <a class="invoice-print-toolbar__back" href="{{ $fallbackBackUrl ?? url()->previous() }}">{{ __('Back') }}</a>
    <button type="button" class="invoice-print-toolbar__print" onclick="window.print()">{{ __('Print') }}</button>
    @if (! empty($receiptUrl))
        <a href="{{ $receiptUrl }}">{{ __('POS receipt') }}</a>
    @endif
    @if (! empty($cashUrl))
        <a href="{{ $cashUrl }}">{{ __('Cash receipt') }}</a>
    @endif
</nav>
