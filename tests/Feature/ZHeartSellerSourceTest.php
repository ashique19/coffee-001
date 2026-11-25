<?php

use App\Business\BusinessContext;
use App\Enums\InvoiceDocumentType;
use App\Enums\InvoiceStatus;
use App\Livewire\Admin\BusinessSettings;
use App\Livewire\Invoices\IssueInvoice;
use App\Models\Branch;
use App\Models\BusinessSetting;
use App\Models\Invoice;
use App\Models\StaffUser;
use Database\Seeders\DemoFancyCoffeeSeeder;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\Support\FakesZHeartSigning;

beforeEach(function () {
    $this->withoutVite();
    $this->fakeZHeartSigning();
    (new DemoFancyCoffeeSeeder)->run();
    $this->tenant = BusinessSetting::singleton();
    BusinessContext::set($this->tenant);
    $this->owner = StaffUser::query()->where('email', 'owner@fancy-coffee.test')->firstOrFail();
    $this->localVat = (string) data_get($this->tenant->settings, 'legal_entity.vat_number');
});

afterEach(function () {
    BusinessContext::clear();
});

it('sends no seller to Z-Heart and stores the returned seller block on the invoice', function () {
    Livewire::actingAs($this->owner, 'tenant')
        ->test(IssueInvoice::class, ['mode' => 'simplified'])
        ->call('addCustomLine')
        ->set('lines.0.name', 'Latte')
        ->set('lines.0.unit_price', '20')
        ->set('lines.0.quantity', '1')
        ->call('issue')
        ->assertHasNoErrors();

    Http::assertSent(function (Request $request) {
        if (! str_ends_with($request->url(), '/api/v1/invoices/sign')) {
            return false;
        }
        $invoice = $request->data()['invoice'] ?? [];

        return $invoice !== [] && ! array_key_exists('seller', $invoice);
    });

    $invoice = Invoice::query()->latest('id')->firstOrFail();
    expect($invoice->isIssued())->toBeTrue()
        ->and($invoice->zheart_seller)->toBe(FakesZHeartSigning::zheartSellerBlock());
});

it('renders the stored Z-Heart seller with the QR on the invoice print', function () {
    $invoice = Invoice::query()->create([
        'branch_id' => Branch::query()->first()?->id,
        'document_number' => 'SIMP-ZH-SELLER-1',
        'document_type' => InvoiceDocumentType::Simplified,
        'status' => InvoiceStatus::Issued,
        'issued_at' => now(),
        'qr_tlv_base64' => base64_encode('qr-demo'),
        'zheart_seller' => FakesZHeartSigning::zheartSellerBlock(),
        'payload' => ['payment_method' => 'cash', 'lines' => [[
            'name' => 'Latte', 'name_ar' => 'لاتيه', 'quantity' => 1, 'unit_price_ex_vat' => 20, 'vat_rate' => 15,
        ]]],
    ]);

    $html = (string) $this->actingAs($this->owner, 'tenant')
        ->get(route('admin.invoices.print', ['invoice' => $invoice, 'layout' => 'cash', 'locale' => 'en']))
        ->assertOk()
        ->getContent();

    expect($html)
        ->toContain('ZH Legal Placeholder Co')
        ->toContain('شركة زد هارت التجريبية')
        ->toContain('399999999900003')
        ->toContain('1010010000')
        ->toContain('4321, ZH Test Street')
        ->toContain('invoice-cash80__qr')
        ->not->toContain($this->localVat);
});

it('falls back to local legal settings only for old invoices without a stored seller', function () {
    $invoice = Invoice::query()->create([
        'branch_id' => Branch::query()->first()?->id,
        'document_number' => 'SIMP-OLD-1',
        'document_type' => InvoiceDocumentType::Simplified,
        'status' => InvoiceStatus::Issued,
        'issued_at' => now(),
        'qr_tlv_base64' => base64_encode('qr-demo'),
        'payload' => ['payment_method' => 'cash', 'lines' => [[
            'name' => 'Latte', 'quantity' => 1, 'unit_price_ex_vat' => 20, 'vat_rate' => 15,
        ]]],
    ]);

    $html = (string) $this->actingAs($this->owner, 'tenant')
        ->get(route('admin.invoices.print', ['invoice' => $invoice, 'layout' => 'cash', 'locale' => 'en']))
        ->assertOk()
        ->getContent();

    expect($this->localVat)->not->toBe('')
        ->and($html)->toContain($this->localVat)
        ->and($html)->not->toContain('ZH Legal Placeholder Co');
});

it('shows legal fields read-only as managed by Z-Heart and only saves the storefront address', function () {
    $legalBefore = (array) data_get($this->tenant->settings, 'legal_entity');

    $component = Livewire::actingAs($this->owner, 'tenant')
        ->test(BusinessSettings::class)
        ->set('section', 'legal');

    $html = $component->html();
    expect($html)
        ->toContain('Managed by Z-Heart. Contact us to change.')
        ->toContain('data-legal-readonly')
        ->not->toContain('name="legalNameEn"')
        ->not->toContain('name="legalNameAr"')
        ->not->toContain('name="vatNumber"')
        ->not->toContain('name="crNumber"')
        ->not->toContain('name="naBuilding"')
        ->toContain('name="addressEn"')
        ->toContain('name="addressAr"');

    $component
        ->set('legalNameEn', 'Hacked Name')
        ->set('vatNumber', '300000000000003')
        ->set('crNumber', '999')
        ->set('naPostal', '00000')
        ->set('addressEn', 'Storefront Road, Jeddah')
        ->set('addressAr', 'طريق المتجر، جدة')
        ->call('saveLegal')
        ->assertHasNoErrors();

    $legal = (array) data_get(BusinessSetting::singleton()->fresh()->settings, 'legal_entity');
    expect($legal['name_en'] ?? null)->toBe($legalBefore['name_en'] ?? null)
        ->and($legal['vat_number'] ?? null)->toBe($legalBefore['vat_number'] ?? null)
        ->and($legal['cr_number'] ?? null)->toBe($legalBefore['cr_number'] ?? null)
        ->and($legal['national_address'] ?? null)->toBe($legalBefore['national_address'] ?? null)
        ->and($legal['address'])->toBe('Storefront Road, Jeddah')
        ->and($legal['address_ar'])->toBe('طريق المتجر، جدة');
});

it('shows the seller from the latest Z-Heart invoice on the legal tab', function () {
    Invoice::query()->create([
        'branch_id' => Branch::query()->first()?->id,
        'document_number' => 'SIMP-ZH-SELLER-2',
        'document_type' => InvoiceDocumentType::Simplified,
        'status' => InvoiceStatus::Issued,
        'issued_at' => now(),
        'zheart_seller' => FakesZHeartSigning::zheartSellerBlock(),
        'payload' => ['lines' => []],
    ]);

    Livewire::actingAs($this->owner, 'tenant')
        ->test(BusinessSettings::class)
        ->set('section', 'legal')
        ->assertSee('As on your latest Z-Heart invoice.')
        ->assertSee('ZH Legal Placeholder Co')
        ->assertSee('399999999900003');
});
