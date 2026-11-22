<?php

use App\Enums\InvoiceDocumentType;
use App\Models\Branch;
use App\Models\BusinessSetting;
use App\Services\InvoiceIssueService;
use App\Business\BusinessContext;
use App\Support\Themes\CafeThemePreset;
use Illuminate\Support\Carbon;

it('snapshots document totals and due date when issuing an invoice', function () {
    $tenant = BusinessSetting::query()->create([
        'name' => 'Snapshot Cafe',
        'settings' => CafeThemePreset::settings('Snapshot Cafe', 'مقهى'),
    ]);
    BusinessContext::set($tenant);

    $branch = Branch::query()->create([
        'code' => 'snap-1',
        'name' => 'Snapshot Branch',
    ]);

    Carbon::setTestNow(Carbon::parse('2026-11-23 08:30:00', 'UTC'));

    $draft = app(InvoiceIssueService::class)->issueDraft(
        $branch,
        'INV-SNAP-1',
        InvoiceDocumentType::Simplified,
        [['name' => 'Latte', 'quantity' => 2, 'unit_price_ex_vat' => 20, 'vat_rate' => 15]],
    );

    $issued = app(InvoiceIssueService::class)->issue($draft)->invoice;

    expect($issued->status->value)->toBe('issued')
        ->and($issued->due_date?->toDateString())->toBe('2026-11-23')
        ->and($issued->payload['totals']['subtotal_ex_vat'])->toEqual(40)
        ->and($issued->payload['totals']['total_vat'])->toEqual(6)
        ->and($issued->payload['totals']['total_inc_vat'])->toEqual(46)
        ->and($issued->payload['invoice_uuid'])->not->toBeEmpty();

    Carbon::setTestNow();
    BusinessContext::clear();
});

it('uses buyer_due_date from the payload when present', function () {
    $tenant = BusinessSetting::query()->create([
        'name' => 'Due Date Cafe',
        'settings' => CafeThemePreset::settings('Due Date Cafe', 'مقهى'),
    ]);
    BusinessContext::set($tenant);

    $branch = Branch::query()->create([
        'code' => 'due-1',
        'name' => 'Due Branch',
    ]);

    $draft = app(InvoiceIssueService::class)->issueDraft(
        $branch,
        'INV-DUE-1',
        InvoiceDocumentType::Simplified,
        [['name' => 'Espresso', 'quantity' => 1, 'unit_price_ex_vat' => 10, 'vat_rate' => 15]],
    );
    $payload = $draft->payload;
    $payload['buyer_due_date'] = '2026-12-01';
    $draft->forceFill(['payload' => $payload])->save();

    $issued = app(InvoiceIssueService::class)->issue($draft->fresh())->invoice;

    expect($issued->due_date?->toDateString())->toBe('2026-12-01');

    BusinessContext::clear();
});
