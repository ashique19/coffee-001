@props(['order'])
<div {{ $attributes->merge(['class' => 'space-y-3 max-md:space-y-0']) }}>
    <div class="max-md:sticky max-md:top-0 max-md:z-10 max-md:border-b max-md:border-[var(--app-border)] max-md:bg-[var(--app-surface)] max-md:px-4 max-md:pb-2" data-pos-cart-sheet-header>
        <button type="button" class="flex h-6 w-full items-center justify-center md:hidden" @click="cartOpen = false" aria-label="{{ __('pos.mobile_cart.collapse') }}" data-pos-cart-handle>
            <span class="block h-1.5 w-10 rounded-full bg-[var(--app-border)]" aria-hidden="true"></span>
        </button>
        <div class="flex items-center gap-2">
            <div class="min-w-0 flex-1">
                <h2 class="text-lg font-bold max-md:text-base">{{ __('pos.current_order') }}</h2>
                <p class="mt-0.5 truncate text-sm text-[var(--app-muted)] max-md:text-xs"><bdi dir="ltr">#{{ $order->order_number }}</bdi></p>
            </div>
            <button type="button" class="min-h-10 shrink-0 rounded-lg border border-[var(--app-border)] px-3 text-xs font-semibold text-[var(--app-muted)] md:hidden" wire:click="newOrder" wire:loading.attr="disabled" wire:target="newOrder" data-pos-cart-reset-compact>
                {{ __('pos.new_order') }}
            </button>
            <button type="button" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-[var(--app-muted)] hover:bg-[var(--app-bg)] md:hidden" @click="cartOpen = false" aria-label="{{ __('pos.mobile_cart.collapse') }}" data-pos-cart-collapse>
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
            </button>
        </div>
    </div>
    <div class="space-y-3 max-md:px-4 max-md:py-3" data-pos-cart-sheet-body>
    @php
        use App\Support\Invoicing\InvoiceLineGrouper;
        $groupedCart = InvoiceLineGrouper::sectionsForDisplay($order->line_items);
    @endphp

    <div class="pos-cart-undo-slot" aria-live="polite" @class(['hidden' => !($undoLineSnapshot ?? null)])>
        @if ($undoLineSnapshot ?? null)
            <div class="pos-cart-undo flex flex-wrap items-center justify-between gap-2 rounded-lg border px-2 py-1.5 text-xs" style="border-color: var(--app-border)">
                <span class="text-[var(--app-muted)]">{{ __('pos.line_removed_toast', ['name' => $undoLineSnapshot['line']['name'] ?? '']) }}</span>
                <button
                    type="button"
                    wire:click="undoRemoveLine"
                    wire:loading.attr="disabled"
                    wire:target="undoRemoveLine"
                    class="pos-cart-undo__btn font-semibold text-[var(--cafe-caramel)] underline"
                >
                    {{ __('pos.undo_remove') }}
                </button>
            </div>
        @endif
    </div>

    <div
        @class([
            'space-y-2',
            'max-h-44 overflow-y-auto sm:max-h-56 max-md:max-h-none max-md:overflow-visible' => $order->line_items !== [],
        ])
        wire:loading.class="opacity-60"
        wire:target="incrementLine,decrementLine,applyLineQuantity,removeLine,undoRemoveLine"
    >
        @if ($order->line_items === [])
            <p class="text-sm text-[var(--app-muted)]">{{ __('pos.empty_cart') }}</p>
        @else
            @foreach ($groupedCart['sections'] as $section)
                @if ($groupedCart['uses_groups'] && ($section['title'] ?? '') !== '')
                    <p class="text-xs font-semibold uppercase tracking-wide text-[var(--app-muted)]">
                        {{ InvoiceLineGrouper::sectionHeading($section, app()->getLocale()) }}
                    </p>
                @endif
                <ul role="list" class="space-y-1">
                    @foreach ($section['lines'] as $line)
                        @php
                            $idx = (int) ($line['_index'] ?? 0);
                            $qty = (int) max(1, floor((float) ($line['quantity'] ?? 1)));
                        @endphp
                        <li
                            class="pos-cart-line flex items-center gap-2 rounded-lg border px-2 py-1.5 text-sm"
                            style="border-color: var(--app-border)"
                            wire:key="pos-cart-line-{{ $order->id }}-{{ $idx }}"
                        >
                            @php
                                $unitInc = round((float) ($line['unit_price_ex_vat'] ?? 0) * (1 + (float) ($line['vat_rate'] ?? 0) / 100), 2);
                                $qtyExact = (float) ($line['quantity'] ?? 1);
                            @endphp
                            <span class="pos-cart-line__name min-w-0 flex-1 leading-snug">
                                <span class="block">{{ $line['name'] }}</span>
                                <span class="block text-xs tabular-nums text-[var(--app-muted)]" dir="ltr" data-pos-cart-line-price>{{ number_format($unitInc, 2) }} × {{ rtrim(rtrim(number_format($qtyExact, 3, '.', ''), '0'), '.') }} = <strong class="font-semibold text-[var(--app-text)]">{{ number_format($unitInc * $qtyExact, 2) }}</strong></span>
                            </span>

                            <div
                                class="pos-qty-stepper shrink-0"
                                dir="ltr"
                                wire:key="pos-qty-{{ $order->id }}-{{ $idx }}-{{ $qty }}"
                                wire:ignore
                                x-data="posQtyStepper(@js($idx), @js($qty))"
                            >
                                <button
                                    type="button"
                                    class="pos-qty-stepper__btn touch-target"
                                    wire:click="decrementLine({{ $idx }})"
                                    @mousedown.prevent
                                    wire:loading.attr="disabled"
                                    wire:target="decrementLine({{ $idx }})"
                                    aria-label="{{ __('pos.qty_decrease') }}"
                                >
                                    −
                                </button>

                                <button
                                    type="button"
                                    class="pos-qty-stepper__value touch-target"
                                    x-show="!editing"
                                    @click="openEdit()"
                                    aria-label="{{ __('pos.qty_edit') }}"
                                >
                                    <span x-text="displayQty"></span>
                                </button>

                                <input
                                    x-show="editing"
                                    x-cloak
                                    x-ref="qtyInput"
                                    type="number"
                                    min="0"
                                    max="999"
                                    inputmode="numeric"
                                    class="pos-qty-stepper__input field-touch"
                                    x-model.number="draft"
                                    @keydown.enter.prevent="commitEdit()"
                                    @keydown.escape.prevent="onEscape()"
                                    @blur="commitEdit()"
                                />

                                <button
                                    type="button"
                                    class="pos-qty-stepper__btn touch-target"
                                    wire:click="incrementLine({{ $idx }})"
                                    @mousedown.prevent
                                    wire:loading.class="opacity-70"
                                    wire:target="incrementLine({{ $idx }})"
                                    aria-label="{{ __('pos.qty_increase') }}"
                                    @if ($qty >= 999) disabled @endif
                                >
                                    +
                                </button>
                            </div>

                            <button
                                type="button"
                                wire:click="removeLine({{ $idx }})"
                                wire:loading.attr="disabled"
                                wire:target="removeLine({{ $idx }})"
                                class="touch-target pos-cart-line__remove shrink-0 rounded-lg px-2 text-lg font-bold text-red-700"
                                aria-label="{{ __('pos.remove_line') }}"
                            >
                                ×
                            </button>
                        </li>
                    @endforeach
                </ul>
            @endforeach
        @endif
    </div>
    <div
        x-data="{ open: @js((float) $order->promo_discount_ex_vat > 0), wide: window.matchMedia('(min-width: 768px)').matches }"
        x-init="window.matchMedia('(min-width: 768px)').addEventListener('change', (e) => wide = e.matches)"
        data-pos-promo
    >
        <button type="button" class="inline-flex min-h-10 items-center gap-1 text-sm font-semibold text-[var(--cafe-caramel,#c68b59)] md:hidden" x-show="!open && !wide" @click="open = true; $nextTick(() => $refs.promo.focus())" data-pos-promo-toggle>
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
            {{ __('pos.mobile_cart.add_discount') }}
        </button>
        <label class="block text-sm font-medium" x-show="open || wide">
            {{ __('pos.promo_discount_ex_vat') }}
            <input id="pos-promo-discount" x-ref="promo" name="promoDiscount" type="number" step="0.01" min="0" inputmode="decimal" wire:model.blur="promoDiscount" class="field-touch mt-1">
        </label>
    </div>
    <dl class="app-card app-card--flat space-y-2 p-3 text-sm max-md:space-y-1 max-md:p-2.5">
        <div class="flex justify-between gap-2"><dt>{{ __('pos.subtotal_ex_vat') }}</dt><dd class="tabular-nums font-medium">{{ number_format($order->subtotal_ex_vat, 2) }}</dd></div>
        <div class="flex justify-between gap-2"><dt>{{ __('pos.vat') }}</dt><dd class="tabular-nums">{{ number_format($order->vat_total, 2) }}</dd></div>
        <div class="flex justify-between gap-2 border-t pt-2 text-base font-bold max-md:pt-1.5" style="border-color: var(--app-border)">
            <dt>{{ __('pos.total_inc_vat') }}</dt>
            <dd class="tabular-nums" dir="ltr">{{ number_format($order->total_inc_vat, 2) }} SAR</dd>
        </div>
    </dl>
    {{-- Phones: payment method as chips. Tablet/desktop keep the select. Both drive the same property. --}}
    <fieldset class="md:hidden" data-pos-pay-chips>
        <legend class="text-sm font-medium">{{ __('pos.payment_method') }}</legend>
        <div class="mt-1 grid grid-cols-4 gap-1.5">
            @foreach (['cash', 'card', 'mada', 'other'] as $method)
                <label class="relative" wire:key="pos-pay-chip-{{ $method }}">
                    <input type="radio" name="pos-payment-method-chip" value="{{ $method }}" wire:model.live="paymentMethod" class="peer sr-only" data-pos-pay-chip="{{ $method }}">
                    <span class="flex min-h-11 cursor-pointer items-center justify-center rounded-xl border border-[var(--app-border)] bg-[var(--app-surface)] px-1 text-center text-sm font-semibold peer-checked:border-[var(--cafe-espresso,#2c1810)] peer-checked:bg-[var(--cafe-espresso,#2c1810)] peer-checked:text-[var(--cafe-cream,#faf6f0)] peer-focus-visible:ring-2 peer-focus-visible:ring-[var(--cafe-caramel,#c68b59)]">{{ __('pos.'.$method) }}</span>
                </label>
            @endforeach
        </div>
    </fieldset>
    <label class="block text-sm font-medium max-md:hidden">
        {{ __('pos.payment_method') }}
        <select id="pos-payment-method" wire:model="paymentMethod" class="field-touch mt-1">
                <option value="cash">{{ __('pos.cash') }}</option>
                <option value="card">{{ __('pos.card') }}</option>
                <option value="mada">{{ __('pos.mada') }}</option>
                <option value="other">{{ __('pos.other') }}</option>
            </select>
    </label>
    </div>
    <div class="space-y-2 max-md:sticky max-md:bottom-0 max-md:z-10 max-md:border-t max-md:border-[var(--app-border)] max-md:bg-[var(--app-surface)] max-md:px-4 max-md:pt-3 max-md:pb-[calc(0.75rem+env(safe-area-inset-bottom))]" data-pos-cart-sheet-footer>
        <x-ui.livewire-action wire:click="pay" target="pay" loading="processing_payment" class="pos-pay-gradient min-h-14 w-full text-base" :disabled="$order->line_items === []">
            <span>{{ __('pos.pay_and_print') }}</span>
            <span class="md:hidden" aria-hidden="true">·</span> <bdi class="tabular-nums md:hidden" dir="ltr" data-pos-pay-total>{{ number_format($order->total_inc_vat, 2) }} SAR</bdi>
        </x-ui.livewire-action>
        <x-ui.livewire-action wire:click="newOrder" target="newOrder" loading="new_order" variant="muted" class="w-full max-md:hidden">
            {{ __('pos.new_order') }}
        </x-ui.livewire-action>
    </div>
</div>
