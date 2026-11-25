<?php

use App\Data\InvoiceLineData;
use App\Enums\InvoiceDocumentType;
use App\Models\BusinessSetting;
use App\Services\Invoicing\Signing\InvoiceSigningRequest;
use App\Services\Invoicing\ZHeart\ZHeartInvoicePayloadBuilder;
use App\Support\Themes\CafeThemePreset;
use Illuminate\Support\Carbon;
use Tests\Support\ZHeartRequiredInvoiceFieldRules;

function sellerPayloadBusiness(?array $legal = null, string $name = 'Fancy Coffee'): BusinessSetting
{
    $settings = CafeThemePreset::settings('Fancy Coffee', 'فانسي كوفي');
    if ($legal !== null) {
        $settings['legal_entity'] = array_merge($settings['legal_entity'] ?? [], $legal);
    }

    return new BusinessSetting([
        'name' => $name,
        'settings' => $settings,
    ]);
}

function sellerSigningRequest(BusinessSetting $business, string $category = 'simplified'): InvoiceSigningRequest
{
    $standard = $category === 'standard';

    return new InvoiceSigningRequest(
        business: $business,
        documentNumber: $standard ? 'STD-1' : 'POS-1',
        documentType: $standard ? InvoiceDocumentType::Standard : InvoiceDocumentType::Simplified,
        invoiceCategory: $category,
        lines: [new InvoiceLineData('Latte', 1, 15.65, 15)],
        invoicePayload: $standard ? [
            'buyer_name' => 'Buyer Co',
            'buyer_vat' => '300000000000003',
            'buyer_cr' => '1234567890',
            'buyer_national_address' => [
                'building' => '1234',
                'street' => 'King Rd',
                'city' => 'Riyadh',
                'district' => 'Al Olaya',
                'postal_code' => '12345',
            ],
        ] : [],
        issueDateTimeUtc: Carbon::parse('2026-01-15 09:00:00', 'UTC'),
        invoiceUuid: '550e8400-e29b-41d4-a716-446655440010',
        referencedInvoiceNumber: null,
        referencedInvoiceUuid: null,
        noteReason: null,
    );
}

it('sends no seller block for simplified invoices and passes Z-Heart required-field rules', function () {
    $payload = app(ZHeartInvoicePayloadBuilder::class)->build(
        sellerSigningRequest(sellerPayloadBusiness()),
    );

    expect($payload)->not->toHaveKey('seller');

    $validated = ZHeartRequiredInvoiceFieldRules::validate(['invoice' => $payload]);
    expect($validated['invoice'])->not->toHaveKey('seller');
});

it('sends no seller block for standard invoices', function () {
    $payload = app(ZHeartInvoicePayloadBuilder::class)->build(
        sellerSigningRequest(sellerPayloadBusiness(), 'standard'),
    );

    expect($payload)->not->toHaveKey('seller')
        ->and($payload['buyer']['name'])->toBe('Buyer Co');

    ZHeartRequiredInvoiceFieldRules::validate(['invoice' => $payload]);
});

it('issues without local legal name or VAT, since Z-Heart owns the seller', function () {
    $business = sellerPayloadBusiness([
        'name_en' => '',
        'name_ar' => '',
        'vat_number' => '',
        'cr_number' => '',
    ], name: '');

    $payload = app(ZHeartInvoicePayloadBuilder::class)->build(sellerSigningRequest($business));

    expect($payload)->not->toHaveKey('seller')
        ->and(json_encode($payload))->not->toContain('310122393500003');
});
