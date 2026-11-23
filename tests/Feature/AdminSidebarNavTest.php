<?php

use App\Models\StaffUser;
use Database\Seeders\OwnerAdminSeeder;

beforeEach(function () {
    $this->withoutVite();
    (new OwnerAdminSeeder)->run();
});

it('groups the admin sidebar with distinct labels on desktop and in the mobile drawer', function () {
    $owner = StaffUser::query()->where('email', 'owner@example.test')->firstOrFail();

    $html = $this->actingAs($owner, 'tenant')
        ->get('/admin/dashboard?locale=en')
        ->assertOk()
        ->getContent();

    expect($html)
        ->not->toContain('nav.group_sell')
        ->toContain('>Sell<')
        ->toContain('>Invoices<')
        ->toContain('>Catalog<')
        ->toContain('>Reports<')
        ->toContain('>Settings<')
        ->toContain('End of day')
        ->toContain('New invoice')
        ->toContain('app-sidebar__group')
        ->not->toContain('Simplified invoice');

    expect(preg_match('/>EOD</', $html))->toBe(0);
});

it('renders Arabic group headings for RTL locales', function () {
    $owner = StaffUser::query()->where('email', 'owner@example.test')->firstOrFail();

    $this->actingAs($owner, 'tenant')
        ->get('/admin/dashboard?locale=ar')
        ->assertOk()
        ->assertSee('بيع', false)
        ->assertSee('الفواتير', false)
        ->assertSee('الكتالوج', false)
        ->assertSee('التقارير', false)
        ->assertSee('الإعدادات', false)
        ->assertSee('نهاية اليوم', false)
        ->assertSee('فاتورة جديدة', false)
        ->assertSee('dir="rtl"', false);
});
