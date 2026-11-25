<?php

use App\Business\BusinessContext;
use App\Livewire\Pos\Terminal;
use App\Models\PosOrder;
use Livewire\Livewire;

afterEach(function () {
    BusinessContext::clear();
});

function mobileCartTerminal(): array
{
    $setup = posTenantSetup();
    BusinessContext::set($setup['tenant']);

    $component = Livewire::actingAs($setup['cashier'], 'tenant')
        ->test(Terminal::class, ['branch' => $setup['branch']]);

    return [$setup, $component];
}

it('hides the phone summary bar while the cart is empty', function () {
    [, $component] = mobileCartTerminal();

    $component->assertDontSeeHtml('data-pos-cart-bar')
        ->assertSeeHtml('data-testid="pos-tablet-cart-open"');
});

it('shows a compact phone summary bar with count, total and pay once items are added', function () {
    [$setup, $component] = mobileCartTerminal();

    $component->call('addProduct', $setup['product']->id)
        ->call('addProduct', $setup['product']->id);

    $order = PosOrder::query()->findOrFail($component->get('orderId'));

    $component->assertSeeHtml('data-pos-cart-bar')
        ->assertSeeHtml('data-testid="pos-mobile-cart-open"')
        ->assertSee('2 items')
        ->assertSeeHtml('data-pos-cart-bar-total')
        ->assertSee(number_format((float) $order->total_inc_vat, 2).' SAR')
        ->assertSeeHtml('data-pos-cart-bar-pay')
        // tablet/desktop opener is still rendered for md-lg
        ->assertSeeHtml('data-testid="pos-tablet-cart-open"');
});

it('renders the compact sheet: handle, small reset next to the order number, line prices and sticky footer', function () {
    [$setup, $component] = mobileCartTerminal();

    $component->call('addProduct', $setup['product']->id);
    $order = PosOrder::query()->findOrFail($component->get('orderId'));

    $component->assertSeeHtml('data-pos-cart-sheet-header')
        ->assertSeeHtml('data-pos-cart-handle')
        ->assertSeeHtml('data-pos-cart-reset-compact')
        ->assertSeeHtml('<bdi dir="ltr">#'.e($order->order_number).'</bdi>')
        ->assertSeeHtml('data-pos-cart-line-price')
        ->assertSeeHtml('data-pos-cart-sheet-footer')
        ->assertSeeHtml('data-pos-pay-total')
        ->assertSeeHtml('env(safe-area-inset-bottom)');
});

it('collapses the promo discount behind an Add discount toggle', function () {
    [$setup, $component] = mobileCartTerminal();

    $component->call('addProduct', $setup['product']->id)
        ->assertSeeHtml('data-pos-promo-toggle')
        ->assertSee('Add discount')
        ->assertSeeHtml('id="pos-promo-discount"');
});

it('offers payment method chips bound to the same payment method', function () {
    [$setup, $component] = mobileCartTerminal();

    $component->call('addProduct', $setup['product']->id)
        ->assertSeeHtml('data-pos-pay-chips')
        ->assertSeeHtml('data-pos-pay-chip="cash"')
        ->assertSeeHtml('data-pos-pay-chip="card"')
        ->assertSeeHtml('data-pos-pay-chip="mada"')
        ->assertSeeHtml('data-pos-pay-chip="other"')
        ->assertSeeHtml('id="pos-payment-method"')
        ->set('paymentMethod', 'mada')
        ->assertSet('paymentMethod', 'mada');
});

it('keeps the print-preview modal working when paying from the phone bar', function () {
    [$setup, $component] = mobileCartTerminal();

    $component->call('addProduct', $setup['product']->id)
        ->set('mobileCartOpen', false)
        ->set('paymentMethod', 'card')
        ->call('pay')
        ->assertHasNoErrors()
        ->assertNoRedirect()
        ->assertSeeHtml('data-pos-receipt-modal')
        ->call('closeReceipt')
        ->assertDontSeeHtml('data-pos-receipt-modal')
        ->assertDontSeeHtml('data-pos-cart-bar');
});

it('translates the phone cart labels', function (string $locale, string $pay, string $addDiscount, string $count) {
    config(['app.enabled_locales' => ['en', 'ar', 'bn', 'ur', 'hi']]);
    app()->setLocale($locale);

    [$setup, $component] = mobileCartTerminal();

    $component->call('addProduct', $setup['product']->id)
        ->call('addProduct', $setup['product']->id)
        ->assertSee($pay)
        ->assertSee($addDiscount)
        ->assertSee($count);
})->with([
    'en' => ['en', 'Pay', 'Add discount', '2 items'],
    'ar' => ['ar', 'ادفع', 'إضافة خصم', 'صنفان'],
    'bn' => ['bn', 'পেমেন্ট', 'ডিসকাউন্ট যোগ করুন', '2 টি আইটেম'],
    'ur' => ['ur', 'ادائیگی', 'ڈسکاؤنٹ شامل کریں', '2 آئٹمز'],
    'hi' => ['hi', 'भुगतान', 'छूट जोड़ें', '2 आइटम'],
]);
