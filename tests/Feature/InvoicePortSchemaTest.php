<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

it('adds staff locale, invoice due date, and product units for the invoice ports', function () {
    expect(Schema::hasColumn('staff_users', 'locale'))->toBeTrue()
        ->and(Schema::hasColumn('invoices', 'due_date'))->toBeTrue()
        ->and(Schema::hasTable('product_units'))->toBeTrue()
        ->and(Schema::hasColumn('products', 'product_unit_id'))->toBeTrue();

    $pcs = DB::table('product_units')->where('code', 'PCS')->first();
    expect($pcs)->not->toBeNull()
        ->and($pcs->ubl_unit_code)->toBe('PCE')
        ->and(DB::table('product_units')->whereIn('code', ['KG', 'L'])->count())->toBe(2);
});

it('can run the invoice-port schema migration more than once', function () {
    $migration = require database_path('migrations/2026_11_23_120000_add_invoice_port_schema.php');
    $migration->up();
    $migration->up();

    expect(Schema::hasColumn('staff_users', 'locale'))->toBeTrue()
        ->and(DB::table('product_units')->where('code', 'PCS')->count())->toBe(1);
});

it('ships an idempotent phpMyAdmin SQL companion that matches this migration', function () {
    $path = base_path('docs/sql/2026-11-23-invoice-port-schema.sql');
    expect(is_file($path))->toBeTrue();

    $sql = (string) file_get_contents($path);

    expect($sql)
        ->toContain('staff_users')
        ->toContain('locale')
        ->toContain('due_date')
        ->toContain('product_units')
        ->toContain('product_unit_id')
        ->toContain("'PCS'")
        ->toContain('2026_11_23_120000_add_invoice_port_schema')
        ->not->toContain('CREATE TABLE IF NOT EXISTS `invoice_payments`')
        ->not->toContain('legal_name_ar')
        ->not->toContain('CREATE TABLE IF NOT EXISTS `product_sizes`');
});

it('adds official_number for the Z-Heart invoice number and ships companion SQL', function () {
    expect(Schema::hasColumn('invoices', 'official_number'))->toBeTrue();

    $migration = require database_path('migrations/2026_11_26_040000_add_official_number_to_invoices.php');
    $migration->up();
    $migration->up();

    expect(Schema::hasColumn('invoices', 'official_number'))->toBeTrue();

    $path = base_path('docs/sql/2026-11-26-invoice-official-number.sql');
    expect(is_file($path))->toBeTrue();
    $sql = file_get_contents($path);
    expect($sql)->toContain('official_number')
        ->and($sql)->toContain('ADD COLUMN IF NOT EXISTS')
        ->and($sql)->toContain('CREATE UNIQUE INDEX IF NOT EXISTS')
        ->and($sql)->toContain('2026_11_26_040000_add_official_number_to_invoices');
});
