<?php

use App\Enums\InvoiceDocumentType;
use App\Enums\InvoiceStatus;
use App\Models\Branch;
use App\Models\BusinessSetting;
use App\Models\Invoice;
use App\Models\PosOrder;
use App\Models\StaffUser;
use App\Business\BusinessContext;
use Database\Seeders\DemoFancyCoffeeSeeder;
use Illuminate\Support\Facades\Auth;

beforeEach(function () {
    $this->withoutVite();
    (new DemoFancyCoffeeSeeder)->run();
    $business = BusinessSetting::singleton();
    $settings = $business->settings;
    data_set($settings, 'legal_entity.address', 'Prince Sultan Road, Al Rawdah, Jeddah 23432, Kingdom of Saudi Arabia');
    data_set($settings, 'legal_entity.national_address', [
        'building' => '7098',
        'street' => 'Ahmad Ibn Al Hussain Ibn Shuqair St',
        'district' => 'Al Malaz',
        'city' => 'Riyadh',
        'postal_code' => '12629',
        'additional' => '3652',
        'country' => 'SA',
        'street_ar' => 'أحمد بن الحسين بن شقير',
        'district_ar' => 'حي الملز',
        'city_ar' => 'الرياض',
    ]);
    data_set($settings, 'theme.logo_url', 'img/tenants/demo-logo.webp');
    $business->settings = $settings;
    $business->save();
    BusinessContext::set($business);
    $this->owner = StaffUser::query()->where('email', 'owner@fancy-coffee.test')->firstOrFail();
    $this->branchId = Branch::query()->first()?->id;
});

afterEach(function () {
    Auth::guard('tenant')->logout();
    BusinessContext::clear();
});

it('renders an 80mm cash receipt from the national address without a logo or bangla', function () {
    $invoice = Invoice::query()->create([
        'branch_id' => $this->branchId,
        'document_number' => 'SIMP-CASH80-001',
        'document_type' => InvoiceDocumentType::Simplified,
        'status' => InvoiceStatus::Issued,
        'issued_at' => now(),
        'qr_tlv_base64' => base64_encode('phase1-demo'),
        'payload' => [
            'payment_method' => 'cash',
            'buyer_name' => 'Shah Jamal',
            'buyer_phone' => '05012340244',
            'lines' => [
                [
                    'name' => 'Latte',
                    'name_ar' => 'لاتيه',
                    'quantity' => 1,
                    'unit_price_ex_vat' => 20,
                    'vat_rate' => 15,
                    'modifier_names' => ['Oat milk', 'Extra shot'],
                ],
            ],
        ],
    ]);

    $html = (string) $this->actingAs($this->owner, 'tenant')
        ->get(route('admin.invoices.print', ['invoice' => $invoice, 'layout' => 'cash', 'locale' => 'bn']))
        ->assertOk()
        ->getContent();

    expect($html)
        ->toContain('invoice-print-sheet--cash')
        ->toContain('invoice-cash80')
        ->toContain('@page { size: 80mm auto; margin: 0; }')
        ->toContain('SIMPLIFIED TAX INVOICE')
        ->toContain('فاتورة ضريبية مبسطة')
        ->toContain('7098, Ahmad Ibn Al Hussain Ibn Shuqair St')
        ->toContain('أحمد بن الحسين بن شقير')
        ->not->toContain('Prince Sultan Road')
        ->not->toContain('invoice-cash80__logo')
        ->not->toContain('img/tenants/demo-logo.webp')
        ->not->toContain('receipt-thermal__logo')
        ->toContain('Shah Jamal')
        ->toContain('Latte')
        ->toContain('Oat milk, Extra shot')
        ->toContain('لاتيه')
        ->toContain('1 x 23.00')
        ->toContain('Tax inclusive:')
        ->toContain('الضريبة شاملة')
        ->toContain('Before VAT')
        ->toContain('Thank you for shopping')
        ->toContain('شكراً لتسوقكم')
        ->toContain('id="receipt-barcode"')
        ->toContain('data-value="SIMP-CASH80-001"')
        ->toContain('<span class="invoice-sar-symbol">SAR</span>')
        ->toContain('invoice-print-toolbar--screen')
        ->toContain('no-print')
        ->not->toContain('invoice-a4__table--product-lines')
        ->not->toContain('invoice-print-info-grid')
        ->not->toContain('পৃষ্ঠা');
});

it('uses the same 80mm cash layout for the POS receipt', function () {
    $order = seedDemoPaidPosOrder();
    $invoice = $order->invoice()->firstOrFail();

    $html = (string) $this->actingAs($this->owner, 'tenant')
        ->get(route('admin.pos.receipt', ['order' => $order, 'locale' => 'en']))
        ->assertOk()
        ->getContent();

    expect($html)
        ->toContain('invoice-cash80')
        ->toContain($invoice->document_number)
        ->toContain('SIMPLIFIED TAX INVOICE')
        ->not->toContain('receipt-thermal__logo')
        ->not->toContain('img/tenants/demo-logo.webp');
});
