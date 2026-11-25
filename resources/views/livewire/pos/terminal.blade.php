@php
    use App\Support\PosProductImagery;
@endphp
<div class="pos-terminal-rich pb-20 lg:pb-0" x-data="{ cartOpen: @entangle('mobileCartOpen').live }">
    <header class="pos-hero mb-4">
        <p class="relative text-xs font-semibold uppercase tracking-widest text-cafe-cream/80">{{ __('pos.shift_today') }}</p>
        <h1 class="relative text-xl font-bold sm:text-2xl">{{ $branch->name }}</h1>
        <p class="relative text-sm text-cafe-cream/90">
            @if($activeShift)
                {{ __('operations.shift_status_open') }}
                · {{ number_format((float) $activeShift->opening_cash_float, 2) }} SAR
            @else
                {{ __('operations.shift_status_closed') }}
            @endif
        </p>
        <div class="pos-hero__stats relative">
            <div class="pos-hero__stat">
                <span class="block text-[0.65rem] uppercase opacity-80">{{ __('pos.orders_paid') }}</span>
                <span class="text-lg font-bold tabular-nums">{{ $shift['order_count'] }}</span>
            </div>
            <div class="pos-hero__stat">
                <span class="block text-[0.65rem] uppercase opacity-80">{{ __('pos.gross_inc_vat') }}</span>
                <span class="text-lg font-bold tabular-nums">{{ number_format($shift['gross_inc_vat'], 2) }}</span>
            </div>
            <div class="pos-hero__stat col-span-2 sm:col-span-1">
                <span class="block text-[0.65rem] uppercase opacity-80">{{ __('pos.vat') }}</span>
                <span class="text-lg font-bold tabular-nums">{{ number_format($shift['vat_total'], 2) }}</span>
            </div>
        </div>
    </header>

    <div class="flex flex-col gap-4 lg:grid lg:grid-cols-[1fr_22rem] lg:gap-5">
        <section class="app-card border-0 bg-transparent p-0 shadow-none lg:order-1">
            @if ($tradesCatalog)
                <div class="app-section-tabs mb-3 w-full flex-wrap" role="tablist" aria-label="{{ __('pos.job_catalog_filter') }}">
                    <button type="button" wire:click="selectCatalogKind('service')" role="tab"
                        @class(['app-segment-tab', 'app-segment-tab--active' => $catalogKind === 'service'])>
                        {{ __('pos.catalog_services') }}
                    </button>
                    <button type="button" wire:click="selectCatalogKind('part')" role="tab"
                        @class(['app-segment-tab', 'app-segment-tab--active' => $catalogKind === 'part'])>
                        {{ __('pos.catalog_parts') }}
                    </button>
                </div>
            @else
            <div class="flex gap-2 overflow-x-auto pb-2 [-webkit-overflow-scrolling:touch]" role="tablist">
                @foreach($categories as $cat)
                    @php
                        $icon = PosProductImagery::iconForCategory($cat->icon_key, $cat->name_en);
                        $chipClass = str_contains(strtolower($cat->name_en), 'cold') ? 'pos-chip--cold' : (str_contains(strtolower($cat->name_en), 'bite') ? 'pos-chip--bites' : 'pos-chip--hot');
                    @endphp
                    <button type="button" wire:click="selectCategory({{ $cat->id }})" role="tab"
                        wire:loading.attr="disabled" wire:target="selectCategory({{ $cat->id }})"
                        class="app-chip inline-flex items-center gap-1.5 {{ $categoryId === $cat->id ? 'app-chip--active '.$chipClass : '' }}">
                        <span class="pos-chip-icon"><x-app.icon :name="$icon" class="h-3.5 w-3.5" /></span>
                        {{ app()->getLocale() === 'ar' && $cat->name_ar ? $cat->name_ar : $cat->name_en }}
                    </button>
                @endforeach
            </div>
            @endif
            <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-3" wire:loading.class="opacity-60" aria-live="polite">
                @forelse($products as $product)
                    @php
                        $img = PosProductImagery::imageForSku($product->sku, $product->image_url);
                        $stock = $stockStates[$product->id] ?? null;
                        $out = $stock === 'out';
                        $low = $stock === 'low';
                    @endphp
                    <article class="pos-product-card--rich" wire:key="product-{{ $product->id }}">
                        <div class="pos-product-media relative">
                            @if($img)
                                <img src="{{ $img }}" alt="" loading="lazy" decoding="async">
                            @else
                                <span class="flex h-full items-center justify-center text-3xl font-bold opacity-40">{{ mb_substr($product->displayName(), 0, 1) }}</span>
                            @endif
                            <span class="pos-price-badge">{{ number_format((float) $product->price_ex_vat * 1.15, 2) }}</span>
                            @if($low)
                                <span class="absolute start-2 top-2 rounded-full bg-amber-500 px-2 py-0.5 text-[0.6rem] font-bold uppercase text-white">{{ __('inventory.pos_low_stock') }}</span>
                            @endif
                        </div>
                        <div class="flex flex-1 flex-col gap-2 p-3">
                            <strong class="text-sm leading-snug">{{ $product->displayName() }}</strong>
                            @if($product->modifiers->isNotEmpty())
                                <div class="space-y-1 text-xs">
                                    @foreach($product->modifiers as $mod)
                                        <label class="flex min-h-10 items-center gap-2 rounded-lg border bg-[var(--app-bg)] px-2" style="border-color: var(--app-border)">
                                            <input type="checkbox" class="h-4 w-4"
                                                wire:click="toggleModifier({{ $product->id }}, {{ $mod->id }})"
                                                wire:loading.attr="disabled"
                                                wire:target="toggleModifier({{ $product->id }}, {{ $mod->id }})"
                                                {{ in_array($mod->id, $selectedModifiers[$product->id] ?? [], true) ? 'checked' : '' }}>
                                            <span>{{ $mod->displayName() }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @endif
                            <div class="pos-stepper">
                                @if($out)
                                    <span class="flex min-h-11 flex-1 items-center justify-center rounded-xl border border-rose-200 bg-rose-50 px-3 text-center text-xs font-semibold text-rose-800">
                                        {{ __('inventory.out_of_stock') }}
                                    </span>
                                @else
                                    <button type="button" data-testid="pos-product-add" class="pos-product-add tap-action-primary flex-1 text-sm" wire:click="addProduct({{ $product->id }})" wire:loading.attr="disabled" wire:loading.class="opacity-60" wire:target="addProduct({{ $product->id }})">
                                        {{ __('pos.add') }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    </article>
                @empty
                    <x-app.empty-state class="col-span-full"><p>{{ __('pos.empty_catalog') }}</p></x-app.empty-state>
                @endforelse
            </div>
        </section>

        <aside
            class="pos-cart-glass self-start max-lg:fixed max-lg:inset-x-0 max-lg:bottom-[calc(4.25rem+env(safe-area-inset-bottom))] max-lg:z-50 max-lg:mx-3 max-lg:max-h-[min(70vh,32rem)] max-lg:overflow-y-auto max-lg:rounded-t-3xl max-lg:transition-transform max-lg:duration-300 lg:order-2 lg:sticky lg:top-20 lg:mx-0 lg:max-h-none lg:overflow-visible lg:rounded-2xl"
            :class="cartOpen ? 'max-lg:block max-lg:translate-y-0' : 'max-lg:hidden'"
            role="region"
            aria-label="{{ __('pos.cart_sheet') }}"
        >
            @include('livewire.pos.partials.cart-panel', ['order' => $order])
        </aside>
    </div>

    <div class="fixed inset-x-0 bottom-[calc(4.25rem+env(safe-area-inset-bottom))] z-30 px-3 lg:hidden">
        <button type="button" data-testid="pos-mobile-cart-open" class="pos-pay-gradient flex min-h-14 w-full items-center justify-between gap-3 rounded-2xl px-4 shadow-lg" @click="cartOpen = true">
            <span class="font-semibold">{{ __('pos.open_cart') }}</span>
            <span class="tabular-nums text-lg">{{ number_format($order->total_inc_vat, 2) }} SAR</span>
        </button>
    </div>

    <div x-show="cartOpen" x-cloak class="app-sheet-backdrop lg:hidden" @click="cartOpen = false"></div>

    @assets
        @vite(['resources/css/invoice-print.css', 'resources/js/receipt.js'])
    @endassets

    @if ($receipt)
        @teleport('body')
            <div
                class="pos-receipt-modal fixed inset-0 z-[90] flex items-end justify-center bg-black/55 sm:items-center sm:p-4"
                role="dialog"
                aria-modal="true"
                aria-labelledby="pos-receipt-modal-title"
                data-pos-receipt-modal
                wire:key="pos-receipt-modal-{{ $receipt['order']->id }}"
                x-data
                x-init="$nextTick(() => { window.renderReceiptGraphics?.(); $refs.print?.focus(); })"
                @keydown.escape.window="$wire.closeReceipt()"
            >
                <style>
                    @media print {
                        body > *:not(.pos-receipt-modal) { display: none !important; }
                        html, body { background: #fff !important; height: auto !important; min-height: 0 !important; overflow: visible !important; }
                        .pos-receipt-modal { position: static !important; display: block !important; background: none !important; padding: 0 !important; }
                        .pos-receipt-modal__panel { position: static !important; display: block !important; width: auto !important; max-width: none !important; max-height: none !important; border: 0 !important; border-radius: 0 !important; box-shadow: none !important; background: none !important; }
                        .pos-receipt-modal__body { overflow: visible !important; max-height: none !important; padding: 0 !important; background: none !important; }
                        .pos-receipt-modal .no-print { display: none !important; }
                        .pos-receipt-modal .invoice-print-sheet--cash { width: 80mm !important; max-width: 80mm !important; min-width: 0 !important; margin: 0 !important; padding: 4mm !important; box-shadow: none !important; outline: 0 !important; zoom: normal !important; transform: none !important; }
                    }
                </style>
                <div class="pos-receipt-modal__panel flex max-h-[94dvh] w-full flex-col overflow-hidden rounded-t-3xl bg-[var(--app-bg)] text-[var(--app-text)] shadow-2xl sm:max-h-[90dvh] sm:max-w-lg sm:rounded-3xl">
                    <header class="no-print flex items-center gap-3 border-b border-[var(--app-border)] bg-[var(--app-surface)] px-4 py-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700" aria-hidden="true">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 13l4 4L19 7"/></svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700" data-pos-receipt-paid>{{ __('pos.receipt_preview.paid') }}</p>
                            <h2 id="pos-receipt-modal-title" class="line-clamp-2 break-words text-base font-bold leading-snug">
                                {!! str_replace('%NUM%', '<bdi dir="ltr" class="tabular-nums">'.e($receipt['order']->order_number).'</bdi>', e(__('pos.receipt_preview.title', ['number' => '%NUM%']))) !!}
                            </h2>
                        </div>
                        <button type="button" class="tap-action-muted h-12 w-12 shrink-0 rounded-full p-0" wire:click="closeReceipt" aria-label="{{ __('pos.receipt_preview.close') }}" title="{{ __('pos.receipt_preview.close') }}">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>
                        </button>
                    </header>

                    <div class="pos-receipt-modal__body flex-1 overflow-y-auto overscroll-contain px-2 py-3 sm:px-4" dir="ltr" data-pos-receipt-body>
                        @include('admin.invoices.partials.cash-receipt-sheet', ['t' => $receipt['t'], 'cash' => $receipt['cash'], 'autoPrint' => false])
                    </div>

                    <footer class="no-print grid grid-cols-2 gap-2 border-t border-[var(--app-border)] bg-[var(--app-surface)] px-4 pt-3 pb-[calc(0.75rem+env(safe-area-inset-bottom))]">
                        <button type="button" class="tap-action-muted min-h-14 w-full text-base" wire:click="closeReceipt" wire:loading.attr="disabled" wire:target="closeReceipt" data-pos-receipt-new-order>
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                            <span>{{ __('pos.receipt_preview.new_order') }}</span>
                        </button>
                        <button type="button" x-ref="print" class="tap-action-primary min-h-14 w-full text-base" onclick="window.print()" data-pos-receipt-print>
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9V2h12v7"/><rect x="2" y="9" width="20" height="9" rx="2"/><path d="M6 14h12v8H6z"/></svg>
                            <span>{{ __('pos.receipt_preview.print') }}</span>
                        </button>
                    </footer>
                </div>
            </div>
        @endteleport
    @endif
</div>
