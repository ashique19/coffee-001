<?php

use App\Enums\InvoiceDocumentType;
use App\Exceptions\InvoiceSigningException;
use App\Livewire\Invoices\InvoiceHub;
use App\Models\Branch;
use App\Models\Invoice;
use App\Services\InvoiceIssueService;
use App\Services\Invoicing\InvoiceCorrectedDocumentService;
use App\Services\Invoicing\InvoiceZatcaResubmitService;
use App\Support\Invoicing\InvoiceNumberSeries;
use App\Support\Invoicing\InvoicePrintSheet;
use App\Support\Invoicing\ZHeartApiUserMessage;
use App\Support\Invoicing\ZHeartSubmissionOutcome;
use App\Business\BusinessContext;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\Support\InteractsWithBusiness;

uses(InteractsWithBusiness::class);

afterEach(function () {
    BusinessContext::clear();
});

function wave25EnableSubmit($test, string $branchCode = 'JED'): array
{
    $tenant = \App\Models\BusinessSetting::query()->create([
        'name' => 'wave25-co',
        'settings' => \App\Support\Themes\CafeThemePreset::settings('wave25-co', 'wave25-co', 'ar'),
    ]);
    \App\Models\Branch::query()->create([
        'code' => 'main',
        'name' => 'Main Branch',
    ]);
    \App\Business\BusinessContext::set($tenant);
    $settings = $tenant->settings ?? [];
    $settings['invoicing'] = array_merge(is_array($settings['invoicing'] ?? null) ? $settings['invoicing'] : [], [
        'integration_enabled' => true,
        'default_document_type' => 'simplified',
    ]);
    $tenant->forceFill(['settings' => $settings])->save();
    BusinessContext::set($tenant);

    $branch = Branch::query()->first();
    $branch->forceFill(['code' => $branchCode])->save();

    config([
        'zheart.base_url' => 'https://z-heart.test',
        'zheart.api_key' => 'zh_test1234.AbcdEfGhIjKlMnOpQrStUvWxYz0123456789',
        'zheart.retry_times' => 0,
        'zheart.auto_submit_signed' => true,
    ]);

    return [$tenant, $branch];
}

function wave25Line(): array
{
    return [['name' => 'Latte', 'quantity' => 1, 'unit_price_ex_vat' => 18, 'vat_rate' => 15]];
}

function wave25OkData(string $uuid, array $extra = []): array
{
    return array_merge([
        'signed_xml' => base64_encode('<Invoice>signed</Invoice>'),
        'invoice_hash' => 'aGFzaA==',
        'qr' => 'cXI=',
        'uuid' => $uuid,
        'icv' => 7,
        'pih' => '',
        'invoice_number' => 'JED-000001',
        'number_series' => 'JED',
        'outcome' => 'accepted',
        'seller' => Tests\Support\FakesZHeartSigning::zheartSellerBlock(),
    ], $extra);
}

it('saves the invoice UUID on the draft before the first Z-Heart call and reuses it on resend', function () {
    [, $branch] = wave25EnableSubmit($this);
    $issuer = app(InvoiceIssueService::class);
    $draft = $issuer->issueDraft($branch, 'SIMP-LOCAL-1', InvoiceDocumentType::Simplified, wave25Line());
    $savedUuid = $draft->invoiceUuid();
    expect($savedUuid)->not->toBeEmpty()
        ->and($draft->issued_at)->toBeNull();

    $calls = 0;
    Http::fake(function ($request) use ($savedUuid, &$calls) {
        $calls++;
        $invoice = $request->data()['invoice'] ?? [];
        expect($invoice['uuid'] ?? null)->toBe($savedUuid)
            ->and($invoice['number_series'] ?? null)->toBe('JED')
            ->and($invoice)->not->toHaveKey('invoice_number')
            ->and($invoice)->not->toHaveKey('icv');

        if ($calls === 1) {
            return Http::response([
                'status' => 'error',
                'message' => 'Service unavailable',
                'data' => [
                    'outcome' => 'retry',
                    'retryable' => true,
                ],
                'request_id' => 'req-retry-1',
            ], 422);
        }

        return Http::response([
            'status' => 'ok',
            'data' => wave25OkData($savedUuid, ['outcome' => 'accepted', 'duplicate' => true]),
            'request_id' => 'req-accepted-1',
        ], 200);
    });

    $result = $issuer->issue($draft->fresh());
    $invoice = $result->invoice;

    expect($calls)->toBe(1)
        ->and($invoice->isIssued())->toBeTrue()
        ->and($invoice->isSubmissionRetry())->toBeTrue()
        ->and($invoice->invoiceUuid())->toBe($savedUuid)
        ->and($invoice->displayNumber())->toBe(__('invoices.pending_number'));

    $resent = app(InvoiceZatcaResubmitService::class)->resubmit($invoice->fresh());
    expect($calls)->toBe(2)
        ->and($resent->invoice->isSubmissionAccepted())->toBeTrue()
        ->and(data_get($resent->invoice->payload, 'zheart_duplicate'))->toBeTrue()
        ->and($resent->invoice->official_number)->toBe('JED-000001')
        ->and($resent->invoice->displayNumber())->toBe('JED-000001')
        ->and($resent->invoice->document_number)->toBe('SIMP-LOCAL-1');
});

it('stores cleared_xml when a standard invoice is accepted', function () {
    [, $branch] = wave25EnableSubmit($this);
    $cleared = base64_encode('<Invoice>cleared-copy</Invoice>');

    Http::fake([
        'https://z-heart.test/api/v1/invoices/clear' => function ($request) use ($cleared) {
            $uuid = $request['invoice']['uuid'] ?? 'missing';

            return Http::response([
                'status' => 'ok',
                'data' => array_merge(wave25OkData($uuid, [
                    'cleared_xml' => $cleared,
                    'invoice_number' => 'JED-000002',
                ]), []),
                'request_id' => 'req-clear-1',
            ], 200);
        },
    ]);

    $issuer = app(InvoiceIssueService::class);
    $draft = $issuer->issueDraft($branch, 'STD-LOCAL-1', InvoiceDocumentType::Standard, wave25Line());
    $draft->forceFill([
        'payload' => array_merge($draft->payload ?? [], [
            'buyer_name' => 'Buyer Co',
            'buyer_vat' => '300000000000003',
            'buyer_national_address' => [
                'building' => '1234',
                'street' => 'King Rd',
                'city' => 'Riyadh',
                'postal_code' => '12345',
            ],
        ]),
    ])->save();

    $issued = $issuer->issue($draft->fresh())->invoice;
    expect($issued->isSubmissionAccepted())->toBeTrue()
        ->and($issued->signed_xml)->toContain('cleared-copy')
        ->and($issued->official_number)->toBe('JED-000002');

    $sheet = InvoicePrintSheet::forInvoice($issued);
    expect($sheet['document_number'])->toBe('JED-000002')
        ->and($sheet['internal_document_number'])->toBe('STD-LOCAL-1');
});

it('sends CN-<branch> as the number series for credit notes', function () {
    [, $branch] = wave25EnableSubmit($this, 'RUH');
    $series = null;

    Http::fake(function ($request) use (&$series) {
        $invoice = $request->data()['invoice'] ?? [];
        $series = $invoice['number_series'] ?? null;
        $uuid = $invoice['uuid'] ?? 'missing';

        return Http::response([
            'status' => 'ok',
            'data' => wave25OkData($uuid, [
                'invoice_number' => 'CN-RUH-000001',
                'number_series' => 'CN-RUH',
            ]),
            'request_id' => 'req-cn-1',
        ], 200);
    });

    $issuer = app(InvoiceIssueService::class);
    $original = $issuer->issueDraft($branch, 'SIMP-ORIG-1', InvoiceDocumentType::Simplified, wave25Line());
    $original->forceFill([
        'official_number' => 'RUH-000010',
        'payload' => array_merge($original->payload ?? [], [
            'invoice_uuid' => $original->ensureInvoiceUuid(),
            'zheart_invoice_number' => 'RUH-000010',
            'zheart_outcome' => ZHeartSubmissionOutcome::ACCEPTED,
        ]),
        'status' => 'issued',
        'issued_at' => now(),
        'signed_xml' => '<Invoice/>',
    ]);
    Invoice::withoutIssuedImmutability(fn () => $original->save());

    $credit = $issuer->issueDraft($branch, 'CN-LOCAL-1', InvoiceDocumentType::CreditNote, wave25Line(), $original);
    $issuer->issue($credit->fresh());

    expect($series)->toBe('CN-RUH')
        ->and(InvoiceNumberSeries::forInvoice($credit->fresh()))->toBe('CN-RUH');
});

it('marks a rejected invoice and offers a corrected document with a new UUID', function () {
    [$tenant, $branch] = wave25EnableSubmit($this);
    $this->actingAsBusiness($tenant);

    $phase = 'reject';
    Http::fake(function ($request) use (&$phase) {
        $invoice = $request->data()['invoice'] ?? [];
        $uuid = $invoice['uuid'] ?? 'missing';
        $type = $invoice['document_type'] ?? '';

        if ($phase === 'reject') {
            return Http::response([
                'status' => 'error',
                'message' => 'Invalid buyer data',
                'data' => [
                    'outcome' => 'rejected',
                    'retryable' => false,
                ],
                'request_id' => 'req-rej-1',
            ], 422);
        }

        $number = $type === 'credit' ? 'CN-JED-000003' : 'JED-000099';

        return Http::response([
            'status' => 'ok',
            'data' => wave25OkData($uuid, ['invoice_number' => $number]),
            'request_id' => 'req-fix-1',
        ], 200);
    });

    $issuer = app(InvoiceIssueService::class);
    $draft = $issuer->issueDraft($branch, 'SIMP-REJ-1', InvoiceDocumentType::Simplified, wave25Line());
    $rejectedUuid = $draft->invoiceUuid();
    $rejected = $issuer->issue($draft->fresh())->invoice;

    expect($rejected->isSubmissionRejected())->toBeTrue()
        ->and($rejected->invoiceUuid())->toBe($rejectedUuid);

    Livewire::actingAs(auth('tenant')->user(), 'tenant')
        ->test(InvoiceHub::class)
        ->assertSee(__('invoices.signing.retry') === 'no' ? '' : __('invoices.signing.rejected'), false)
        ->assertSee(__('invoices.correction.simplified_guidance'), false)
        ->assertSee(__('invoices.correction.issue_credit'), false);

    $phase = 'correct';

    $credit = app(InvoiceCorrectedDocumentService::class)->issueCreditNote($rejected->fresh())->invoice;
    expect($credit->document_type)->toBe(InvoiceDocumentType::CreditNote)
        ->and($credit->invoiceUuid())->not->toBe($rejectedUuid)
        ->and($credit->official_number)->toBe('CN-JED-000003')
        ->and($credit->referenced_invoice_id)->toBe($rejected->id);

    $corrected = app(InvoiceCorrectedDocumentService::class)->issueCorrectedInvoice($rejected->fresh())->invoice;
    expect($corrected->invoiceUuid())->not->toBe($rejectedUuid)
        ->and($corrected->invoiceUuid())->not->toBe($credit->invoiceUuid())
        ->and($corrected->official_number)->toBe('JED-000099')
        ->and($corrected->document_number)->not->toBe($rejected->document_number);
});

it('shows the new 422 numbering messages clearly in English and Bangla', function () {
    $uniqueJson = [
        'status' => 'error',
        'message' => 'Invoice number SIMP-1 is already used by another document. Invoice numbers must be unique; send a new number or use invoice.number_series.',
        'data' => [
            'errors' => [
                'invoice.invoice_number' => [
                    'Invoice number SIMP-1 is already used by another document. Invoice numbers must be unique; send a new number or use invoice.number_series.',
                ],
            ],
        ],
    ];
    $bothJson = [
        'status' => 'error',
        'message' => 'Send either invoice.invoice_number or invoice.number_series, not both.',
        'data' => [
            'errors' => [
                'invoice.number_series' => [
                    'Send either invoice.invoice_number or invoice.number_series, not both.',
                ],
            ],
        ],
    ];

    app()->setLocale('en');
    expect(ZHeartApiUserMessage::fromResponse(422, $uniqueJson, 'req-a'))
        ->toContain('already used by another document')
        ->toContain('Request id req-a')
        ->and(ZHeartApiUserMessage::fromResponse(422, $bothJson, 'req-b'))
        ->toContain('invoice number or a number series')
        ->toContain('Request id req-b');

    app()->setLocale('bn');
    expect(ZHeartApiUserMessage::fromResponse(422, $uniqueJson, 'req-a'))
        ->toBe(__('invoices.errors.invoice_number_taken').' '.__('zheart_api.request_id_suffix', ['id' => 'req-a']))
        ->and(ZHeartApiUserMessage::fromResponse(422, $bothJson, 'req-b'))
        ->toBe(__('invoices.errors.number_series_with_number').' '.__('zheart_api.request_id_suffix', ['id' => 'req-b']));

    app()->setLocale('ar');
    expect(ZHeartApiUserMessage::fromResponse(422, $uniqueJson, 'req-a'))
        ->toContain(__('invoices.errors.invoice_number_taken'));
});

it('blocks sign-only mode in production', function () {
    [, $branch] = wave25EnableSubmit($this);
    config(['zheart.auto_submit_signed' => false]);
    $this->app['env'] = 'production';

    $issuer = app(InvoiceIssueService::class);
    $draft = $issuer->issueDraft($branch, 'SIMP-PROD-1', InvoiceDocumentType::Simplified, wave25Line());

    expect(fn () => $issuer->issue($draft->fresh()))
        ->toThrow(InvoiceSigningException::class, __('invoices.errors.sign_only_blocked_in_production'));

    expect($draft->fresh()->isIssued())->toBeFalse()
        ->and($draft->fresh()->invoiceUuid())->not->toBeEmpty();
});
