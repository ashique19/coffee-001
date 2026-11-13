<?php

use App\Data\InvoiceLineData;
use App\Enums\InvoiceDocumentType;
use App\Models\BusinessSetting;
use App\Services\Invoicing\Signing\InvoiceSigningRequest;
use App\Services\Invoicing\ZHeart\ZHeartInvoicePayloadBuilder;
use App\Support\Themes\CafeThemePreset;
use Illuminate\Support\Carbon;

it('includes buyer address vat and cr on standard invoices', function () {
    $business = new BusinessSetting([
        'name' => 'Payload Biz',
        'settings' => CafeThemePreset::settings('Payload Biz', 'ب'),
    ]);

    $request = new InvoiceSigningRequest(
        business: $business,
        documentNumber: 'STD-1',
        documentType: InvoiceDocumentType::Standard,
        invoiceCategory: 'standard',
        lines: [new InvoiceLineData('Item', 1, 100, 15)],
        invoicePayload: [
            'buyer_name' => 'Buyer Co',
            'buyer_vat' => '300000000000003',
            'buyer_cr' => '1234567890',
            'buyer_national_address' => [
                'building' => '1234',
                'street' => 'King Rd',
                'city' => 'Riyadh',
                'postal_code' => '12345',
            ],
        ],
        issueDateTimeUtc: Carbon::parse('2026-01-15 09:00:00', 'UTC'),
        invoiceUuid: '550e8400-e29b-41d4-a716-446655440010',
        referencedInvoiceNumber: null,
        referencedInvoiceUuid: null,
        noteReason: null,
    );

    $payload = app(ZHeartInvoicePayloadBuilder::class)->build($request);

    expect($payload['buyer']['vat_number'])->toBe('300000000000003')
        ->and($payload['buyer']['cr_number'])->toBe('1234567890')
        ->and($payload['buyer']['address']['building'])->toBe('1234');
});

it('adds billing_reference_uuid and payment_means_note on credit notes', function () {
    $business = new BusinessSetting([
        'name' => 'CN Biz',
        'settings' => CafeThemePreset::settings('CN Biz', 'ب'),
    ]);

    $request = new InvoiceSigningRequest(
        business: $business,
        documentNumber: 'CN-1',
        documentType: InvoiceDocumentType::CreditNote,
        invoiceCategory: 'simplified',
        lines: [new InvoiceLineData('Refund', 1, 50, 15)],
        invoicePayload: [],
        issueDateTimeUtc: Carbon::parse('2026-01-15 09:00:00', 'UTC'),
        invoiceUuid: '550e8400-e29b-41d4-a716-446655440011',
        referencedInvoiceNumber: 'POS-1',
        referencedInvoiceUuid: '550e8400-e29b-41d4-a716-446655440099',
        noteReason: 'Customer return',
    );

    $payload = app(ZHeartInvoicePayloadBuilder::class)->build($request);

    expect($payload['billing_reference_uuid'])->toBe('550e8400-e29b-41d4-a716-446655440099')
        ->and($payload['payment_means_note'])->toBe('Customer return')
        ->and($payload)->not->toHaveKey('billing_reference');
});

it('includes line discount when present', function () {
    $business = new BusinessSetting([
        'name' => 'Disc Biz',
        'settings' => CafeThemePreset::settings('Disc Biz', 'ب'),
    ]);

    $request = new InvoiceSigningRequest(
        business: $business,
        documentNumber: 'SIMP-1',
        documentType: InvoiceDocumentType::Simplified,
        invoiceCategory: 'simplified',
        lines: [new InvoiceLineData('Latte', 2, 50, 15, discountExVat: 10)],
        invoicePayload: [],
        issueDateTimeUtc: Carbon::parse('2026-01-15 09:00:00', 'UTC'),
        invoiceUuid: '550e8400-e29b-41d4-a716-446655440012',
        referencedInvoiceNumber: null,
        referencedInvoiceUuid: null,
        noteReason: null,
    );

    $payload = app(ZHeartInvoicePayloadBuilder::class)->build($request);

    expect($payload['lines'][0]['discount'])->toBe(10.0);
});

it('requires buyer vat on standard invoices', function () {
    $business = new BusinessSetting([
        'name' => 'No VAT',
        'settings' => CafeThemePreset::settings('No VAT', 'ب'),
    ]);

    $request = new InvoiceSigningRequest(
        business: $business,
        documentNumber: 'STD-2',
        documentType: InvoiceDocumentType::Standard,
        invoiceCategory: 'standard',
        lines: [new InvoiceLineData('Item', 1, 100, 15)],
        invoicePayload: [
            'buyer_name' => 'Buyer',
            'buyer_cr' => '123',
            'buyer_national_address' => ['building' => '1', 'street' => 'S', 'city' => 'Riyadh', 'postal_code' => '12345'],
        ],
        issueDateTimeUtc: Carbon::now('UTC'),
        invoiceUuid: '550e8400-e29b-41d4-a716-446655440013',
        referencedInvoiceNumber: null,
        referencedInvoiceUuid: null,
        noteReason: null,
    );

    expect(fn () => app(ZHeartInvoicePayloadBuilder::class)->build($request))
        ->toThrow(InvalidArgumentException::class);
});
