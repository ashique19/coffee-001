<?php

namespace Tests\Support;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Z-Heart invoice API required-field rules, used to assert a locally built
 * payload would be accepted on field presence (not signing or UBL precheck).
 *
 * Mirrors the public API rules. Z-Heart builds the seller from its client
 * profile, so invoice.seller is not part of the contract (and is ignored if sent).
 */
final class ZHeartRequiredInvoiceFieldRules
{
    /**
     * @param  array<string, mixed>  $payload  Full API body, including the invoice key.
     * @return array<string, mixed>
     */
    public static function validate(array $payload): array
    {
        $invoiceType = is_array($payload['invoice'] ?? null)
            ? (string) ($payload['invoice']['type'] ?? '')
            : '';

        $validator = Validator::make($payload, self::rules($invoiceType));

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        return $validator->validated();
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(string $invoiceType = ''): array
    {
        $isSimplified = $invoiceType === 'simplified';

        return [
            'force' => ['sometimes', 'boolean'],
            'invoice' => ['required', 'array'],
            'invoice.uuid' => ['required', 'uuid'],
            'invoice.invoice_number' => ['nullable', 'string', 'max:127'],
            'invoice.issue_date' => ['required', 'date_format:Y-m-d'],
            'invoice.issue_time' => ['required', 'date_format:H:i:s'],
            'invoice.supply_date' => ['nullable', 'date_format:Y-m-d', 'required_if:invoice.type,standard'],
            'invoice.delivery_date' => ['nullable', 'date_format:Y-m-d'],
            'invoice.currency' => ['required', 'string', 'size:3', Rule::in(['SAR'])],
            'invoice.type' => ['required', 'string', Rule::in(['standard', 'simplified'])],
            'invoice.document_type' => ['nullable', 'string', Rule::in(['invoice', 'credit', 'debit'])],
            'invoice.icv' => ['nullable', 'integer', 'min:1'],
            'invoice.billing_reference' => ['nullable', 'string', 'max:127', 'required_if:invoice.document_type,credit', 'required_if:invoice.document_type,debit'],
            'invoice.billing_reference_uuid' => ['nullable', 'uuid'],
            'invoice.buyer' => ['required_if:invoice.type,standard', 'array'],
            'invoice.buyer.name' => ['required_if:invoice.type,standard', 'string', 'max:255'],
            'invoice.buyer.vat_number' => [
                'required_if:invoice.type,standard',
                'string',
                'regex:/^3\d{13}3$/',
            ],
            'invoice.buyer.cr_number' => ['nullable', 'string', 'max:32'],
            ...self::buyerAddressRules($isSimplified),
            'invoice.lines' => ['required', 'array', 'min:1'],
            'invoice.lines.*.name' => ['required', 'string', 'max:255'],
            'invoice.lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'invoice.lines.*.unit_price' => ['required', 'numeric', 'gte:0'],
            'invoice.lines.*.vat_rate' => ['required', 'numeric', 'gte:0'],
            'invoice.totals' => ['required', 'array'],
            'invoice.totals.tax_exclusive' => ['required', 'numeric'],
            'invoice.totals.tax' => ['required', 'numeric'],
            'invoice.totals.tax_inclusive' => ['required', 'numeric'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function buyerAddressRules(bool $isSimplified): array
    {
        if ($isSimplified) {
            return [
                'invoice.buyer.address' => ['nullable', 'array'],
                'invoice.buyer.address.street' => ['nullable', 'string', 'max:255'],
                'invoice.buyer.address.building' => ['nullable', 'string', 'max:16'],
                'invoice.buyer.address.buildingNumber' => ['nullable', 'string', 'max:16'],
                'invoice.buyer.address.district' => ['nullable', 'string', 'max:128'],
                'invoice.buyer.address.subdivision' => ['nullable', 'string', 'max:128'],
                'invoice.buyer.address.city' => ['nullable', 'string', 'max:128'],
                'invoice.buyer.address.postal' => ['nullable', 'string', 'regex:/^\d{5}$/'],
                'invoice.buyer.address.postalZone' => ['nullable', 'string', 'regex:/^\d{5}$/'],
                'invoice.buyer.address.country' => ['nullable', 'string', 'size:2', Rule::in(['SA'])],
            ];
        }

        return [
            'invoice.buyer.address' => ['required_if:invoice.type,standard', 'array'],
            'invoice.buyer.address.street' => ['required_if:invoice.type,standard', 'string', 'max:255'],
            'invoice.buyer.address.building' => ['nullable', 'string', 'max:16', 'required_without:invoice.buyer.address.buildingNumber'],
            'invoice.buyer.address.buildingNumber' => ['nullable', 'string', 'max:16', 'required_without:invoice.buyer.address.building'],
            'invoice.buyer.address.district' => ['nullable', 'string', 'max:128', 'required_without:invoice.buyer.address.subdivision'],
            'invoice.buyer.address.subdivision' => ['nullable', 'string', 'max:128', 'required_without:invoice.buyer.address.district'],
            'invoice.buyer.address.city' => ['required_if:invoice.type,standard', 'string', 'max:128'],
            'invoice.buyer.address.postal' => ['nullable', 'string', 'regex:/^\d{5}$/', 'required_without:invoice.buyer.address.postalZone'],
            'invoice.buyer.address.postalZone' => ['nullable', 'string', 'regex:/^\d{5}$/', 'required_without:invoice.buyer.address.postal'],
            'invoice.buyer.address.country' => ['required_if:invoice.type,standard', 'string', 'size:2', Rule::in(['SA'])],
        ];
    }
}
