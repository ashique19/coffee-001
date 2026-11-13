<?php

use App\Enums\InvoiceStatus;
use App\Enums\PosOrderStatus;
use App\Models\Branch;
use App\Models\Invoice;
use App\Models\PosOrder;
use App\Models\StaffUser;
use Database\Seeders\OwnerAdminSeeder;
use Tests\Support\AuthenticatedRouteCatalog;

beforeEach(function () {
    $this->withoutVite();
    (new OwnerAdminSeeder)->run();

    $this->owner = StaffUser::query()->whereHas('roles', fn ($q) => $q->where('name', 'owner'))->firstOrFail();
    $this->branch = Branch::query()->firstOrFail();

    $this->order = PosOrder::query()->create([
        'branch_id' => $this->branch->id,
        'cashier_id' => $this->owner->id,
        'order_number' => 'T-1001',
        'status' => PosOrderStatus::Paid,
        'payment_method' => 'cash',
        'subtotal_ex_vat' => 20,
        'discount_ex_vat' => 0,
        'promo_discount_ex_vat' => 0,
        'loyalty_discount_ex_vat' => 0,
        'vat_total' => 3,
        'total_inc_vat' => 23,
        'line_items' => [],
        'metadata' => [],
        'paid_at' => now(),
    ]);

    $this->invoice = Invoice::query()->create([
        'branch_id' => $this->branch->id,
        'document_number' => 'INV-1001',
        'document_type' => 'simplified',
        'status' => InvoiceStatus::Issued,
        'payload' => ['lines' => []],
        'issued_at' => now(),
    ]);
});

it('allows the owner through authenticated admin GET routes', function () {
    $this->actingAs($this->owner, 'tenant');

    foreach (AuthenticatedRouteCatalog::adminRoutes($this->branch) as [$name, $params, $status, $fragment]) {
        $url = route($name, $params);
        $response = $this->get($url);

        expect($response->status())->toBe($status, "Route {$name} returned {$response->status()} for {$url}");
    }
});
