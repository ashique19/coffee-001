<?php

use App\Models\Branch;
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

it('translates sidebar, bottom nav and the page title for Bangla, and keeps locale on links', function () {
    $owner = StaffUser::query()->where('email', 'owner@example.test')->firstOrFail();
    $branch = Branch::query()->firstOrFail();

    $html = $this->actingAs($owner, 'tenant')
        ->get('/admin/pos/branches/'.$branch->id.'/terminal?locale=bn')
        ->assertOk()
        ->assertSee('lang="bn"', false)
        ->getContent();

    expect(__('pos.switch_branch'))->toBe('শাখা পরিবর্তন');

    expect($html)
        ->not->toContain('dir="rtl"')
        ->not->toContain('nav.group_sell')
        ->toContain('>বিক্রি<')
        ->toContain('>ইনভয়েস<')
        ->toContain('>ক্যাটালগ<')
        ->toContain('>রিপোর্ট<')
        ->toContain('>সেটিংস<')
        ->toContain('দিনের সমাপ্তি')
        ->toContain('নতুন ইনভয়েস')
        ->toContain('>পস<')
        ->toContain('>অর্ডার<')
        ->toContain('>অপারেশন<')
        ->toContain('আরও')
        ->toContain('ড্যাশবোর্ড')
        ->toContain('পয়েন্ট অফ সেল')
        ->not->toContain('>Sell<')
        ->not->toContain('End of day')
        ->not->toContain('>More<')
        ->toContain('locale=bn');
});

it('keeps Arabic sidebar labels and RTL when locale=ar', function () {
    $owner = StaffUser::query()->where('email', 'owner@example.test')->firstOrFail();

    $html = $this->actingAs($owner, 'tenant')
        ->get('/admin/dashboard?locale=ar')
        ->assertOk()
        ->assertSee('dir="rtl"', false)
        ->assertSee('بيع', false)
        ->assertSee('الفواتير', false)
        ->assertSee('نهاية اليوم', false)
        ->assertSee('فاتورة جديدة', false)
        ->getContent();

    expect($html)->toContain('locale=ar')
        ->not->toContain('>Sell<');
});
