<?php

use App\Livewire\Invoices\IssueInvoice;
use App\Models\BusinessSetting;
use App\Models\Invoice;
use App\Models\StaffUser;
use App\Business\BusinessContext;
use Database\Seeders\DemoFancyCoffeeSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->withoutVite();
    $this->fakeZHeartSigning();
    (new DemoFancyCoffeeSeeder)->run();
    $this->tenant = BusinessSetting::singleton();
    BusinessContext::set($this->tenant);
    $this->owner = StaffUser::query()->where('email', 'owner@fancy-coffee.test')->firstOrFail();
});

afterEach(function () {
    BusinessContext::clear();
});

it('redirects to the cash print view with autoprint after a successful issue and print', function () {
    $component = Livewire::actingAs($this->owner, 'tenant')
        ->test(IssueInvoice::class, ['mode' => 'simplified'])
        ->call('addCustomLine')
        ->set('lines.0.name', 'Latte')
        ->set('lines.0.unit_price', '20')
        ->set('lines.0.quantity', '1')
        ->call('issueAndPrint')
        ->assertHasNoErrors();

    $invoice = Invoice::query()->latest('id')->firstOrFail();

    expect($invoice->isIssued())->toBeTrue();

    $component->assertRedirect(route('admin.invoices.print', [
        'invoice' => $invoice,
        'layout' => 'cash',
        'autoprint' => 1,
    ]));
});

it('behaves like issue when issue and print validation fails', function () {
    $before = Invoice::query()->where('status', 'issued')->count();

    Livewire::actingAs($this->owner, 'tenant')
        ->test(IssueInvoice::class, ['mode' => 'simplified'])
        ->call('issueAndPrint')
        ->assertHasErrors(['document']);

    expect(Invoice::query()->where('status', 'issued')->count())->toBe($before);
});

it('renders the issue and print action on the invoice create footer', function () {
    $html = Livewire::actingAs($this->owner, 'tenant')
        ->test(IssueInvoice::class, ['mode' => 'simplified'])
        ->html();

    expect($html)
        ->toContain('data-testid="issue-and-print"')
        ->toContain('Issue &amp; print');
});

it('defaults the issue entry route to simplified', function () {
    expect(BusinessSetting::singleton()->defaultInvoiceDocumentType()->value)->toBe('simplified');

    $this->actingAs($this->owner, 'tenant')
        ->get(route('admin.invoices.issue.default'))
        ->assertRedirect(route('admin.invoices.issue', ['mode' => 'simplified']));
});
