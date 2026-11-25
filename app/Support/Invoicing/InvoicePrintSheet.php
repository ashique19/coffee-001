<?php

namespace App\Support\Invoicing;

use App\Enums\InvoiceDocumentType;
use App\Models\BusinessSetting;
use App\Models\Invoice;
use App\Models\PosOrder;
use Illuminate\Support\Carbon;

final class InvoicePrintSheet
{
    /**
     * Shared print-data array for the 80mm cash receipt (POS receipt and layout=cash).
     *
     * @return array<string, mixed>
     */
    public static function forInvoice(Invoice $invoice, ?PosOrder $order = null, ?BusinessSetting $business = null): array
    {
        $business ??= BusinessSetting::singleton();
        $theme = (array) data_get($business->settings, 'theme', []);
        $payload = is_array($invoice->payload) ? $invoice->payload : [];
        $rawLines = is_array($payload['lines'] ?? null) ? $payload['lines'] : [];

        $lines = [];
        foreach (InvoicePayloadLines::fromInvoice($invoice) as $index => $line) {
            $raw = is_array($rawLines[$index] ?? null) ? $rawLines[$index] : [];
            $modifiers = $raw['modifier_names'] ?? $raw['modifiers'] ?? [];
            if (! is_array($modifiers)) {
                $modifiers = [];
            }

            $lines[] = [
                'name_en' => $line->name,
                'name_ar' => $line->nameAr,
                'modifier_names' => array_values(array_filter(array_map(
                    static fn ($name): string => trim((string) $name),
                    $modifiers,
                ), static fn (string $name): bool => $name !== '')),
                'quantity' => $line->quantity,
                'quantity_display' => InvoiceLineMath::formatQuantity($line->quantity),
                'unit_price_ex_vat' => $line->unitPriceExVat,
                'taxable_ex_vat' => $line->netExVat(),
                'vat_rate' => $line->vatRate,
                'vat_amount' => $line->vatAmount(),
                'line_amount_inc_vat' => $line->lineIncVat(),
            ];
        }

        $totals = InvoicePayloadLines::totalsFromInvoice($invoice);
        $issued = $invoice->issued_at
            ? $invoice->issued_at->copy()->timezone('Asia/Riyadh')
            : Carbon::now('Asia/Riyadh');

        $qr = $invoice->qr_tlv_base64;
        $branch = $invoice->relationLoaded('branch') ? $invoice->branch : $invoice->branch()->first();
        if ($branch === null && $order !== null) {
            $branch = $order->branch;
        }

        $payment = self::paymentLabels($invoice, $order, $payload);
        $amountPaid = self::amountPaid($invoice, $order, $payload, (float) ($totals['total_inc_vat'] ?? 0));

        $customerName = trim((string) ($payload['buyer_name'] ?? $payload['customer_name'] ?? ''));
        $customerPhone = trim((string) ($payload['buyer_phone'] ?? $payload['customer_phone'] ?? ''));
        if ($order?->customer) {
            if ($customerName === '') {
                $customerName = trim((string) $order->customer->name);
            }
            if ($customerPhone === '') {
                $customerPhone = trim((string) $order->customer->phone);
            }
        }

        $docType = $invoice->document_type;
        $category = (string) ($payload['invoice_category'] ?? '');
        $noteUsesSimplified = in_array($docType, [InvoiceDocumentType::CreditNote, InvoiceDocumentType::DebitNote], true)
            && ($category === 'simplified' || $invoice->referencedInvoice?->document_type === InvoiceDocumentType::Simplified);

        $seller = InvoiceSellerBlock::forInvoice($invoice, $business);

        return [
            'seller_en' => $seller['name_en'],
            'seller_ar' => $seller['name_ar'],
            'trn' => $seller['vat_number'],
            'cr_number' => $seller['cr_number'],
            'seller_id_scheme' => $seller['id_scheme'],
            'seller_id' => $seller['id'],
            'seller_source' => $seller['source'],
            'national_address' => $seller['national_address'],
            'address_en' => $seller['address_en'],
            'address_ar' => $seller['address_ar'],
            'show_qr' => filled($qr),
            'qr_png' => PrintQrPng::dataUri($qr),
            'qr_tlv_base64' => $qr,
            'document_number' => $invoice->document_number,
            'issue_datetime_print' => $issued->format('Y-m-d H:i'),
            'issue_date' => $issued->toDateString(),
            'issue_time' => $issued->format('H:i'),
            'customer' => [
                'name' => $customerName,
                'phone' => $customerPhone,
            ],
            'lines' => $lines,
            'totals' => [
                'subtotal_ex_vat' => $totals['subtotal_ex_vat'] ?? 0,
                'total_vat' => $totals['total_vat'] ?? 0,
                'total_inc_vat' => $totals['total_inc_vat'] ?? 0,
            ],
            'payment_display_en' => $payment['en'],
            'payment_display_ar' => $payment['ar'],
            'branch' => $branch,
            'cashier_name' => trim((string) ($order?->cashier?->name ?? $payload['cashier_name'] ?? '')),
            'invoice_document_type' => $docType->value,
            'is_simplified' => $docType === InvoiceDocumentType::Simplified,
            'note_uses_simplified' => $noteUsesSimplified,
            'amount_paid' => $amountPaid,
            'theme_colors' => is_array($theme['colors'] ?? null) ? $theme['colors'] : [],
            'theme_font' => (string) ($theme['font'] ?? 'Instrument Sans'),
            'theme_font_ar' => (string) ($theme['font_ar'] ?? 'Tajawal'),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{en: string, ar: string}
     */
    private static function paymentLabels(Invoice $invoice, ?PosOrder $order, array $payload): array
    {
        $method = strtolower(trim((string) ($order?->payment_method?->value ?? $order?->payment_method ?? $payload['payment_method'] ?? 'cash')));

        return match ($method) {
            'mada' => ['en' => 'MADA', 'ar' => 'مدى'],
            'card', 'credit_card' => ['en' => 'Card', 'ar' => 'بطاقة'],
            'bank', 'transfer' => ['en' => 'Bank transfer', 'ar' => 'تحويل بنكي'],
            default => ['en' => 'Cash', 'ar' => 'نقداً'],
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private static function amountPaid(Invoice $invoice, ?PosOrder $order, array $payload, float $totalInc): float
    {
        foreach ([$payload['amount_paid'] ?? null, $payload['tendered'] ?? null, $order?->total_inc_vat] as $value) {
            if (is_numeric($value)) {
                return round((float) $value, 2);
            }
        }

        return round($totalInc, 2);
    }
}
