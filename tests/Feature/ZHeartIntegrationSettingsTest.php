<?php

use App\Enums\InvoiceDocumentType;
use App\Models\Branch;
use App\Models\BusinessSetting;
use App\Models\Invoice;
use App\Services\InvoiceIssueService;
use App\Business\BusinessContext;
use App\Support\ReceiptSigningStatusLabel;
use App\Support\Themes\CafeThemePreset;
use Illuminate\Support\Facades\Http;

it('stores submission skip when integration is disabled but env submit is on', function () {
    config([
        'zheart.base_url' => 'https://z-heart.test',
        'zheart.api_key' => 'zh_test1234.AbcdEfGhIjKlMnOpQrStUvWxYz0123456789',
        'zheart.retry_times' => 0,
        'zheart.auto_submit_signed' => true,
    ]);

    Http::fake([
        'https://z-heart.test/api/v1/invoices/sign' => Http::response([
            'status' => 'ok',
            'data' => [
                'signed_xml' => base64_encode('<Invoice/>'),
                'invoice_hash' => 'aGFzaA==',
                'qr' => 'cXI=',
                'uuid' => '550e8400-e29b-41d4-a716-446655440099',
                'icv' => 1,
                'pih' => '',
            ],
            'request_id' => 'req-sign-only',
        ], 200),
    ]);

    $settings = CafeThemePreset::settings('Integration Off', 'إيقاف');
    data_set($settings, 'invoicing.integration_enabled', false);

    $tenant = BusinessSetting::query()->create([
        'name' => 'Integration Off',
        'settings' => $settings,
    ]);
    BusinessContext::set($tenant);

    $branch = Branch::query()->create(['code' => 'int-off', 'name' => 'Main']);

    $draft = app(InvoiceIssueService::class)->issueDraft(
        $branch,
        'POS-INT-1',
        InvoiceDocumentType::Simplified,
        [['name' => 'Coffee', 'quantity' => 1, 'unit_price_ex_vat' => 10, 'vat_rate' => 15]],
    );

    $issued = app(InvoiceIssueService::class)->issue($draft);

    expect($issued->payload['zheart_submission_skip'])->toBe('integration_disabled');

    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/invoices/sign'));

    $label = ReceiptSigningStatusLabel::forInvoice(Invoice::query()->findOrFail($issued->id));
    expect($label['tone'])->toBe('skipped');

    BusinessContext::clear();
});
