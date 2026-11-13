<?php

namespace App\Services\Invoicing\ZHeart;

use App\Data\InvoiceLineData;
use App\Services\Invoicing\Signing\InvoiceSigningRequest;

final class ZHeartInvoicePayloadBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function build(InvoiceSigningRequest $request): array
    {
        $issued = $request->issueDateTimeUtc->copy()->timezone('Asia/Riyadh');
        $legal = $request->business->settings['legal_entity'] ?? [];

        $type = $request->invoiceCategory === 'simplified' ? 'simplified' : 'standard';
        $documentType = match ($request->documentType->value) {
            'credit_note' => 'credit',
            'debit_note' => 'debit',
            default => 'invoice',
        };

        $payload = [
            'uuid' => $request->invoiceUuid,
            'invoice_number' => $request->documentNumber,
            'issue_date' => $issued->format('Y-m-d'),
            'issue_time' => $issued->format('H:i:s'),
            'supply_date' => $issued->format('Y-m-d'),
            'currency' => 'SAR',
            'type' => $type,
            'document_type' => $documentType,
            'lines' => $this->mapLines($request->lines),
            'totals' => $this->totals($request->lines),
        ];

        if (in_array($documentType, ['credit', 'debit'], true)) {
            if (filled($request->referencedInvoiceUuid)) {
                $payload['billing_reference_uuid'] = $request->referencedInvoiceUuid;
            }
            if (filled($request->noteReason)) {
                $payload['payment_means_note'] = $request->noteReason;
            }
        }

        if ($type === 'standard') {
            $buyerVat = preg_replace('/\D/', '', (string) ($request->invoicePayload['buyer_vat'] ?? '')) ?? '';
            if ($buyerVat === '') {
                throw new \InvalidArgumentException(__('invoices.errors.standard_buyer_vat_required'));
            }

            $buyerCr = trim((string) ($request->invoicePayload['buyer_cr'] ?? ''));
            $address = $this->buyerAddress($request->invoicePayload);

            $payload['buyer'] = array_filter([
                'name' => $request->invoicePayload['buyer_name'] ?? null,
                'vat_number' => $buyerVat,
                'cr_number' => $buyerCr !== '' ? $buyerCr : null,
                'address' => $address,
            ], fn ($value) => $value !== null && $value !== [] && $value !== '');
        } elseif (filled($request->invoicePayload['buyer_name'] ?? null)) {
            $payload['buyer'] = [
                'name' => (string) $request->invoicePayload['buyer_name'],
            ];
        }

        if (config('zheart.allow_seller_override', false)) {
            $payload['seller'] = array_filter([
                'name' => $legal['name_en'] ?? $request->business->name,
                'vat_number' => $legal['vat_number'] ?? null,
            ]);
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $invoicePayload
     * @return array<string, string|null>
     */
    private function buyerAddress(array $invoicePayload): array
    {
        $na = (array) ($invoicePayload['buyer_national_address'] ?? []);

        return array_filter([
            'building' => isset($na['building']) ? trim((string) $na['building']) : null,
            'street' => isset($na['street']) ? trim((string) $na['street']) : null,
            'district' => isset($na['district']) ? trim((string) $na['district']) : null,
            'city' => isset($na['city']) ? trim((string) $na['city']) : null,
            'postal_code' => isset($na['postal_code']) ? trim((string) $na['postal_code']) : null,
            'additional' => isset($na['additional']) ? trim((string) $na['additional']) : null,
        ], fn ($value) => $value !== null && $value !== '');
    }

    /**
     * @param  list<InvoiceLineData>  $lines
     * @return list<array<string, mixed>>
     */
    private function mapLines(array $lines): array
    {
        return array_map(function (InvoiceLineData $line) {
            $row = [
                'name' => $line->name,
                'quantity' => $line->quantity,
                'unit_price' => round($line->unitPriceExVat, 2),
                'vat_rate' => $line->vatRate,
            ];

            if ($line->discountExVat > 0) {
                $row['discount'] = round($line->discountExVat, 2);
            }

            return $row;
        }, $lines);
    }

    /**
     * @param  list<InvoiceLineData>  $lines
     * @return array{tax_exclusive: float, tax: float, tax_inclusive: float}
     */
    private function totals(array $lines): array
    {
        $ex = 0.0;
        $vat = 0.0;

        foreach ($lines as $line) {
            $net = $line->netExVat();
            $ex += $net;
            $vat += $net * ($line->vatRate / 100);
        }

        $ex = round($ex, 2);
        $vat = round($vat, 2);

        return [
            'tax_exclusive' => $ex,
            'tax' => $vat,
            'tax_inclusive' => round($ex + $vat, 2),
        ];
    }
}
