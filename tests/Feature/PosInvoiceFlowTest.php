<?php

use App\Enums\InvoiceDocumentType;
use App\Enums\PosOrderStatus;
use App\Models\Branch;
use App\Models\PosOrder;
use App\Models\BusinessSetting;
use App\Services\PosInvoiceService;
use App\Support\Themes\CafeThemePreset;
use App\Business\BusinessContext;
use Tests\Support\InteractsWithBusiness;

uses(InteractsWithBusiness::class);

beforeEach(function () {
    $this->withoutVite();
});

afterEach(function () {
    BusinessContext::clear();
});

it('issues a simplified invoice from a paid POS order with loyalty discount affecting VAT base', function () {
    $tenant = BusinessSetting::query()->create([
        'name' => 'Fancy Coffee',
        'settings' => CafeThemePreset::settings('Fancy Coffee', 'فانسي كوفي'),
    ]);

    $branch = Branch::query()->create([
        'code' => 'jad-main',
        'name' => 'Jeddah Main',
    ]);

    $this->actingAsBusiness($tenant);

    $order = PosOrder::query()->create([
        'branch_id' => $branch->id,
        'order_number' => 'ORD-1001',
        'status' => PosOrderStatus::Paid,
        'payment_method' => 'card',
        'subtotal_ex_vat' => 100,
        'discount_ex_vat' => 0,
        'loyalty_discount_ex_vat' => 10,
        'vat_total' => 13.5,
        'total_inc_vat' => 103.5,
        'loyalty_points_redeemed' => 100,
        'line_items' => [
            [
                'name' => 'Latte',
                'quantity' => 2,
                'unit_price_ex_vat' => 50,
                'vat_rate' => 15,
                'discount_ex_vat' => 10,
            ],
        ],
        'paid_at' => now(),
    ]);

    /** @var PosInvoiceService $posInvoice */
    $posInvoice = app(PosInvoiceService::class);
    $invoice = $posInvoice->issueSimplifiedInvoiceForOrder($order);

    expect($invoice->status->value)->toBe('issued')
        ->and($invoice->document_type)->toBe(InvoiceDocumentType::Simplified)
        ->and($invoice->branch_id)->toBe($branch->id)
        ->and($invoice->source_type)->toBe(PosOrder::class)
        ->and($order->fresh()->invoice_id)->toBe($invoice->id);

    $credit = $posInvoice->issueRefundCreditNote($order->fresh());
    expect($credit->document_type->value)->toBe('credit_note')
        ->and($credit->referenced_invoice_id)->toBe($invoice->id);
});

it('puts modifier names on issued invoice payload line names and keeps Arabic names', function () {
    $tenant = BusinessSetting::query()->create([
        'name' => 'Fancy Coffee Mods',
        'settings' => CafeThemePreset::settings('Fancy Coffee Mods', 'فانسي كوفي'),
    ]);

    $branch = Branch::query()->create([
        'code' => 'jad-mods',
        'name' => 'Jeddah Mods',
    ]);

    $this->actingAsBusiness($tenant);

    $order = PosOrder::query()->create([
        'branch_id' => $branch->id,
        'order_number' => 'ORD-1002',
        'status' => PosOrderStatus::Paid,
        'payment_method' => 'card',
        'subtotal_ex_vat' => 20,
        'discount_ex_vat' => 0,
        'loyalty_discount_ex_vat' => 0,
        'vat_total' => 3,
        'total_inc_vat' => 23,
        'line_items' => [
            [
                'name' => 'Latte',
                'name_ar' => 'لاتيه',
                'quantity' => 1,
                'unit_price_ex_vat' => 20,
                'vat_rate' => 15,
                'discount_ex_vat' => 0,
                'modifier_ids' => [11, 12],
                'modifier_names' => ['Oat milk', 'Extra shot'],
            ],
        ],
        'paid_at' => now(),
    ]);

    $invoice = app(PosInvoiceService::class)->issueSimplifiedInvoiceForOrder($order);
    $line = $invoice->payload['lines'][0];

    expect($invoice->status->value)->toBe('issued')
        ->and($line['name'])->toBe('Latte (Oat milk, Extra shot)')
        ->and($line['name_ar'])->toBe('لاتيه')
        ->and($line['modifier_names'])->toBe(['Oat milk', 'Extra shot']);
});
