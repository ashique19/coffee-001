<?php

use App\Enums\PosOrderStatus;
use App\Livewire\Pos\Terminal;
use App\Models\PosOrder;
use App\Business\BusinessContext;
use Livewire\Livewire;

afterEach(function () {
    BusinessContext::clear();
});

function payOnTerminal(): array
{
    $setup = posTenantSetup();
    BusinessContext::set($setup['tenant']);

    $component = Livewire::actingAs($setup['cashier'], 'tenant')
        ->test(Terminal::class, ['branch' => $setup['branch']])
        ->call('addProduct', $setup['product']->id)
        ->set('paymentMethod', 'cash')
        ->call('pay')
        ->assertHasNoErrors()
        ->assertNoRedirect();

    return [$setup, $component];
}

it('opens a print-preview modal on the terminal after paying instead of leaving the page', function () {
    [, $component] = payOnTerminal();

    $paid = PosOrder::query()->where('status', PosOrderStatus::Paid)->latest('id')->firstOrFail();

    $component
        ->assertSet('receiptOrderId', $paid->id)
        ->assertSet('mobileCartOpen', false)
        ->assertDispatched('pos-receipt-opened')
        ->assertSeeHtml('data-pos-receipt-modal')
        ->assertSeeHtml('invoice-cash80')
        ->assertSeeHtml('<bdi dir="ltr" class="tabular-nums">'.e($paid->order_number).'</bdi>')
        ->assertSeeHtml('dir="ltr" data-pos-receipt-body')
        ->assertSeeHtml('data-pos-receipt-print')
        ->assertSeeHtml('onclick="window.print()"')
        ->assertSeeHtml('data-pos-receipt-new-order')
        ->assertSeeHtml('body > *:not(.pos-receipt-modal) { display: none !important; }')
        ->assertSee(__('pos.receipt_preview.paid'))
        ->assertDontSeeHtml('window.open');
});

it('starts a cleared order when the preview is closed', function () {
    [, $component] = payOnTerminal();

    $paidId = $component->get('receiptOrderId');

    $component->call('closeReceipt')
        ->assertSet('receiptOrderId', null)
        ->assertDontSeeHtml('data-pos-receipt-modal');

    $newOrder = PosOrder::query()->findOrFail($component->get('orderId'));

    expect($newOrder->id)->not->toBe($paidId)
        ->and($newOrder->status)->toBe(PosOrderStatus::Open)
        ->and($newOrder->line_items)->toBe([])
        ->and(PosOrder::query()->findOrFail($paidId)->status)->toBe(PosOrderStatus::Paid);
});

it('translates the preview and keeps it right-to-left aware', function (string $locale, string $paid, string $newOrder) {
    config(['app.enabled_locales' => ['en', 'ar', 'bn', 'ur', 'hi']]);
    app()->setLocale($locale);

    [, $component] = payOnTerminal();

    $component->assertSee($paid)->assertSee($newOrder);
})->with([
    'en' => ['en', 'Payment received', 'New order'],
    'ar' => ['ar', 'تم استلام الدفع', 'طلب جديد'],
    'bn' => ['bn', 'পেমেন্ট গ্রহণ করা হয়েছে', 'নতুন অর্ডার'],
    'ur' => ['ur', 'ادائیگی موصول ہو گئی', 'نیا آرڈر'],
    'hi' => ['hi', 'भुगतान प्राप्त हुआ', 'नया ऑर्डर'],
]);

it('does not show a receipt for an order from another branch', function () {
    [$setup, $component] = payOnTerminal();

    $other = $setup['branch']->replicate();
    $other->code = 'OTHER-'.$setup['branch']->id;
    $other->save();

    $paidId = $component->get('receiptOrderId');
    PosOrder::query()->whereKey($paidId)->update(['branch_id' => $other->id]);

    $component->call('$refresh')
        ->assertDontSeeHtml('data-pos-receipt-modal');
});

it('does not let the browser pick which receipt to show', function () {
    [, $component] = payOnTerminal();

    expect(fn () => $component->set('receiptOrderId', 999999))
        ->toThrow(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);
});
