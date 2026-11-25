{{-- 80mm cash receipt sheet. Shared by the print page and the POS terminal print preview. Expects $t, $cash. --}}
@php
    $colors = $t['theme_colors'] ?? [];
    $ink = $colors['espresso'] ?? '#2C1810';
    $rule = $colors['mocha'] ?? '#5C3D2E';
    $accent = $colors['caramel'] ?? '#C68B59';
    $font = $t['theme_font'] ?? 'Instrument Sans';
    $fontAr = $t['theme_font_ar'] ?? 'Tajawal';
@endphp
<style>
    .invoice-print-sheet--cash.invoice-cash80 {
        --cash80-ink: {{ $ink }};
        --cash80-rule: {{ $rule }};
        --cash80-accent: {{ $accent }};
        color: var(--cash80-ink);
        font-family: "{{ $font }}", "DejaVu Sans", system-ui, sans-serif;
    }
    .invoice-print-sheet--cash.invoice-cash80 .invoice-print-ar {
        font-family: "{{ $fontAr }}", "{{ $font }}", sans-serif;
    }
</style>
<div class="invoice-print-root invoice-print-sheet invoice-print-sheet--cash invoice-cash80" data-invoice-print data-auto-print="{{ ($autoPrint ?? request()->boolean('autoprint')) ? '1' : '0' }}" data-document-number="{{ $t['document_number'] }}">

    <header class="invoice-cash80__seller">
        <div class="invoice-cash80__seller-name">{{ $t['seller_en'] }}</div>
        <div class="invoice-cash80__seller-name invoice-cash80__seller-name--ar invoice-print-ar" dir="rtl">{{ $t['seller_ar'] }}</div>
        @if ($cash['seller_en_lines'] !== [])
            <div class="invoice-cash80__seller-address" data-receipt-seller-address>
                @foreach ($cash['seller_en_lines'] as $line)
                    <div class="invoice-cash80__seller-line">{{ $line }}</div>
                @endforeach
            </div>
        @endif
        @if ($cash['seller_ar_lines'] !== [])
            <div class="invoice-national-address-ar invoice-national-address-ar-lines invoice-cash80__seller-ar" lang="ar">
                @foreach ($cash['seller_ar_lines'] as $line)
                    <div class="invoice-national-address-ar__line invoice-cash__seller-ar invoice-print-ar" dir="rtl">{!! $line !!}</div>
                @endforeach
            </div>
        @endif
        @if ($t['trn'] || $t['cr_number'] || filled($t['seller_id'] ?? null))
        <div class="invoice-cash80__seller-ids" data-receipt-seller-ids>
        @endif
        @if ($t['trn'])
            <div class="invoice-cash80__row invoice-cash80__row--id">
                <span class="invoice-cash80__label">VAT No.</span>
                <span class="invoice-cash80__value invoice-print-bidi-value" dir="ltr">{{ $t['trn'] }}</span>
                <span class="invoice-cash80__label-ar invoice-print-ar" dir="rtl">الرقم الضريبي</span>
            </div>
        @endif
        @if ($t['cr_number'])
            <div class="invoice-cash80__row invoice-cash80__row--id">
                <span class="invoice-cash80__label">CR No.</span>
                <span class="invoice-cash80__value invoice-print-bidi-value" dir="ltr">{{ $t['cr_number'] }}</span>
                <span class="invoice-cash80__label-ar invoice-print-ar" dir="rtl">السجل التجاري</span>
            </div>
        @elseif (filled($t['seller_id'] ?? null))
            <div class="invoice-cash80__row invoice-cash80__row--id">
                <span class="invoice-cash80__label">ID ({{ $t['seller_id_scheme'] }})</span>
                <span class="invoice-cash80__value invoice-print-bidi-value" dir="ltr">{{ $t['seller_id'] }}</span>
                <span class="invoice-cash80__label-ar invoice-print-ar" dir="rtl">رقم المعرف</span>
            </div>
        @endif
        @if ($t['trn'] || $t['cr_number'] || filled($t['seller_id'] ?? null))
        </div>
        @endif
    </header>

    @if (($t['show_qr'] ?? false) && (filled($t['qr_png'] ?? null) || filled($t['qr_tlv_base64'] ?? null)))
        <div class="invoice-cash80__qr">
            @if (filled($t['qr_png'] ?? null))
                <img src="{{ $t['qr_png'] }}" alt="" width="200" height="200" class="invoice-cash80__qr-img">
            @else
                <canvas id="receipt-qr" data-tlv="{{ $t['qr_tlv_base64'] }}" width="200" height="200"></canvas>
            @endif
        </div>
    @endif

    <h1 class="invoice-cash80__title">
        <span class="invoice-cash80__title-en">{{ $cash['titles']['en'] }}</span>
        <span class="invoice-cash80__title-ar invoice-print-ar" dir="rtl">{{ $cash['titles']['ar'] }}</span>
    </h1>

    <div class="invoice-cash80__rule" aria-hidden="true"></div>

    <section class="invoice-cash80__meta">
        <div class="invoice-cash80__row invoice-cash80__row--stack">
            <span class="invoice-cash80__label">Invoice No.</span>
            <span class="invoice-cash80__label-ar invoice-print-ar" dir="rtl">رقم الفاتورة</span>
            <span class="invoice-cash80__value invoice-print-bidi-value" dir="ltr">{{ $t['document_number'] }}</span>
        </div>
        @if ($t['issue_datetime_print'] ?? $t['issue_date'] ?? null)
            <div class="invoice-cash80__row">
                <span class="invoice-cash80__label">Date &amp; time</span>
                <span class="invoice-cash80__value invoice-print-bidi-value" dir="ltr">{{ $t['issue_datetime_print'] ?? trim(($t['issue_date'] ?? '').' '.($t['issue_time'] ?? '')) }}</span>
                <span class="invoice-cash80__label-ar invoice-print-ar" dir="rtl">التاريخ والوقت</span>
            </div>
        @endif
        @if ($cash['customer_name'])
            <div class="invoice-cash80__row">
                <span class="invoice-cash80__label">Customer</span>
                <span class="invoice-cash80__value">{{ $cash['customer_name'] }}</span>
                <span class="invoice-cash80__label-ar invoice-print-ar" dir="rtl">العميل</span>
            </div>
        @endif
        @if ($cash['customer_phone'])
            <div class="invoice-cash80__row">
                <span class="invoice-cash80__label">Phone</span>
                <span class="invoice-cash80__value invoice-print-bidi-value" dir="ltr">{{ $cash['customer_phone'] }}</span>
                <span class="invoice-cash80__label-ar invoice-print-ar" dir="rtl">الجوال</span>
            </div>
        @endif
    </section>

    <div class="invoice-cash80__rule" aria-hidden="true"></div>

    <section class="invoice-cash80__lines" aria-label="Items">
        @foreach ($cash['lines'] as $line)
            <div class="invoice-cash80__item">
                <div class="invoice-cash80__item-name">{{ $line['name_en'] }}</div>
                @if ($line['name_ar'])
                    <div class="invoice-cash80__item-name invoice-cash80__item-name--ar invoice-print-ar" dir="rtl">{{ $line['name_ar'] }}</div>
                @endif
                @if (! empty($line['modifiers']))
                    <div class="invoice-cash80__item-mods">{{ implode(', ', $line['modifiers']) }}</div>
                @endif
                <div class="invoice-cash80__item-row">
                    <span class="invoice-cash80__qty" dir="ltr">{{ $line['quantity_display'] }} x {{ number_format($line['unit_price'], 2) }} <span class="invoice-cash80__vat-rate">({{ number_format($line['vat_rate'], 0) }}%)</span></span>
                    <span class="invoice-cash80__item-amt" dir="ltr">{{ number_format($line['line_amount'], 2) }}</span>
                </div>
            </div>
        @endforeach
    </section>

    <div class="invoice-cash80__rule" aria-hidden="true"></div>

    <section class="invoice-cash80__totals">
        <div class="invoice-cash80__grand">
            <span class="invoice-cash80__grand-label">Total <span class="invoice-print-ar" dir="rtl">الإجمالي</span></span>
            <span class="invoice-cash80__grand-amt invoice-print-bidi-value" dir="ltr"><span class="invoice-sar-symbol">SAR</span> {{ number_format($cash['total_inc_vat'], 2) }}</span>
        </div>
        <div class="invoice-cash80__row invoice-cash80__row--plain">
            <span class="invoice-cash80__label">Items</span>
            <span class="invoice-cash80__value" dir="ltr">{{ $cash['item_count_display'] }}</span>
            <span class="invoice-cash80__label-ar invoice-print-ar" dir="rtl">عدد الأصناف</span>
        </div>
        <div class="invoice-cash80__row invoice-cash80__row--plain">
            <span class="invoice-cash80__label">{{ $cash['payment_en'] }}</span>
            <span class="invoice-cash80__value invoice-print-bidi-value" dir="ltr">{{ number_format($cash['amount_paid'], 2) }}</span>
            <span class="invoice-cash80__label-ar invoice-print-ar" dir="rtl">{{ $cash['payment_ar'] }}</span>
        </div>
        @if ($cash['change'] > 0)
            <div class="invoice-cash80__row invoice-cash80__row--plain">
                <span class="invoice-cash80__label">Change</span>
                <span class="invoice-cash80__value invoice-print-bidi-value" dir="ltr">{{ number_format($cash['change'], 2) }}</span>
                <span class="invoice-cash80__label-ar invoice-print-ar" dir="rtl">الباقي</span>
            </div>
        @endif
    </section>

    <div class="invoice-cash80__rule" aria-hidden="true"></div>

    <section class="invoice-cash80__vat" aria-label="VAT summary">
        <div class="invoice-cash80__vat-heading">
            <span>Tax inclusive:</span>
            <span class="invoice-print-ar" dir="rtl">الضريبة شاملة</span>
        </div>
        <div class="invoice-cash80__vat-head">
            <span>VAT%</span>
            <span>Before VAT</span>
            <span>Incl. VAT</span>
            <span>VAT</span>
        </div>
        @foreach ($cash['vat_rows'] as $row)
            <div class="invoice-cash80__vat-row">
                <span dir="ltr">{{ $row['vat_percent'] }}</span>
                <span dir="ltr">{{ number_format($row['before_vat'], 2) }}</span>
                <span dir="ltr">{{ number_format($row['incl_vat'], 2) }}</span>
                <span dir="ltr">{{ number_format($row['vat'], 2) }}</span>
            </div>
        @endforeach
    </section>

    <div class="invoice-cash80__rule" aria-hidden="true"></div>

    <footer class="invoice-cash80__footer invoice-cash__footer">
        @if ($cash['cashier_name'])
            <div class="invoice-cash80__footer-line">Served by: {{ $cash['cashier_name'] }}</div>
        @endif
        @if ($cash['branch_name'])
            <div class="invoice-cash80__footer-line">{{ $cash['branch_name'] }}</div>
        @endif
        <div class="invoice-cash80__footer-meta">
            <div class="invoice-cash80__footer-when" dir="ltr">{{ $t['issue_date'] ?? '' }} {{ $t['issue_time'] ?? '' }}</div>
            <div class="invoice-cash80__footer-no" dir="ltr">{{ $t['document_number'] }}</div>
        </div>
        <div class="invoice-cash80__thanks">Thank you for shopping</div>
        <div class="invoice-cash80__thanks invoice-print-ar" dir="rtl">شكراً لتسوقكم</div>
        @if ($cash['barcode_value'] !== '')
            <svg id="receipt-barcode" class="invoice-cash80__barcode" data-value="{{ $cash['barcode_value'] }}" role="img" aria-label="{{ $cash['barcode_value'] }}"></svg>
        @endif
    </footer>
</div>
