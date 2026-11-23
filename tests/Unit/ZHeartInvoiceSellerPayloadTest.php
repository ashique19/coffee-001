<?php

use App\Data\InvoiceLineData;
use App\Enums\InvoiceDocumentType;
use App\Models\BusinessSetting;
use App\Services\Invoicing\Signing\InvoiceSigningRequest;
use App\Services\Invoicing\ZHeart\ZHeartInvoicePayloadBuilder;
use App\Support\Themes\CafeThemePreset;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
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

it('builds a seller block from business settings that passes Z-Heart required-field rules', function () {
    $payload = app(ZHeartInvoicePayloadBuilder::class)->build(
        sellerSigningRequest(sellerPayloadBusiness()),
    );

    expect($payload['seller']['name'])->toBe('Fancy Coffee')
        ->and($payload['seller']['vat_number'])->toBe('310122393500003')
        ->and($payload)->not->toHaveKey('allow_seller_override');

    $validated = ZHeartRequiredInvoiceFieldRules::validate(['invoice' => $payload]);

    expect($validated['invoice']['seller']['name'])->toBe('Fancy Coffee')
        ->and($validated['invoice']['seller']['vat_number'])->toBe('310122393500003');
});

it('builds a standard invoice payload that also passes Z-Heart required-field rules', function () {
    $payload = app(ZHeartInvoicePayloadBuilder::class)->build(
        sellerSigningRequest(sellerPayloadBusiness(), 'standard'),
    );

    expect($payload['seller'])->toMatchArray([
        'name' => 'Fancy Coffee',
        'vat_number' => '310122393500003',
    ])->and($payload['buyer']['name'])->toBe('Buyer Co');

    ZHeartRequiredInvoiceFieldRules::validate(['invoice' => $payload]);
});

it('rejects a payload that omits seller under the same required-field rules', function () {
    $payload = app(ZHeartInvoicePayloadBuilder::class)->build(
        sellerSigningRequest(sellerPayloadBusiness()),
    );
    unset($payload['seller']);

    try {
        ZHeartRequiredInvoiceFieldRules::validate(['invoice' => $payload]);
        expect()->fail('Expected seller to be required');
    } catch (ValidationException $e) {
        $messages = collect($e->errors())->flatten()->implode(' ');
        expect($messages)->toContain('seller');
    }
});

it('maps seller fields from the business.legal settings fallback', function () {
    $settings = ['business' => ['legal' => [
        'name_en' => 'Fallback Legal Name',
        'vat_number' => '399999999800003',
    ]]];
    $business = new BusinessSetting([
        'name' => '',
        'settings' => $settings,
    ]);

    $payload = app(ZHeartInvoicePayloadBuilder::class)->build(sellerSigningRequest($business));

    expect($payload['seller'])->toMatchArray([
        'name' => 'Fallback Legal Name',
        'vat_number' => '399999999800003',
    ]);

    ZHeartRequiredInvoiceFieldRules::validate(['invoice' => $payload]);
});

it('names the empty seller legal name when business settings omit it', function () {
    $business = sellerPayloadBusiness([
        'name_en' => '',
        'name_ar' => '',
        'vat_number' => '310122393500003',
    ], name: '');

    expect(fn () => app(ZHeartInvoicePayloadBuilder::class)->build(sellerSigningRequest($business)))
        ->toThrow(InvalidArgumentException::class, __('invoices.errors.seller_name_required'));
});

it('names the empty seller VAT number when business settings omit it', function () {
    $business = sellerPayloadBusiness([
        'name_en' => 'Fancy Coffee',
        'vat_number' => '',
    ]);

    expect(fn () => app(ZHeartInvoicePayloadBuilder::class)->build(sellerSigningRequest($business)))
        ->toThrow(InvalidArgumentException::class, __('invoices.errors.seller_vat_required'));
});
