@php
    use App\Support\PosProductImagery;
    $locale = app()->getLocale();
    $isAr = \App\Support\TenantDisplayName::usesRtlName($locale);
    $catalogLabel = function (array $row, string $field = 'name') use ($locale) {
        if (filled($row[$field] ?? null)) {
            return $row[$field];
        }

        return \App\Support\Catalog\CatalogLocalizedText::pick(
            (string) ($row[$field.'_en'] ?? ''),
            (string) ($row[$field.'_ar'] ?? ''),
            (string) ($row[$field.'_bn'] ?? ''),
            $locale,
        );
    };
    $heroImage = data_get($content, 'media.hero');
    $displayName = \App\Support\TenantDisplayName::for($tenant, $locale);
    $salesDemo = ! empty($content['sales_demo']);
@endphp
<div
    class="pb-24 pt-20"
    data-cafe-menu
    data-menu-source="{{ $menuSource ?? 'marketing' }}"
    x-data="{
        sheet: null,
        openItem(item) { this.sheet = item; document.body.style.overflow = 'hidden' },
        closeSheet() { this.sheet = null; document.body.style.overflow = '' }
    }"
    @keydown.escape.window="closeSheet()"
>
    <header class="relative overflow-hidden bg-cafe-espresso text-cafe-cream">
        <div class="absolute inset-0 opacity-50" aria-hidden="true">
            @if ($heroImage)
                <x-cafe.photo :src="$heroImage" alt="" class="h-full w-full object-cover" width="1200" height="600" loading="eager" />
            @endif
        </div>
        <div class="relative mx-auto max-w-6xl px-4 py-14 sm:px-6 lg:px-8" data-reveal>
            <p class="text-xs font-bold uppercase tracking-[0.3em] text-cafe-accent">{{ $displayName }}</p>
            <h1 class="cafe-display mt-3">{{ __('cafe.menu_title') }}</h1>
            <p class="mt-3 max-w-xl text-cafe-latte">{{ __('cafe.menu_intro') }}</p>
            @if ($salesDemo)
                <p class="mt-2 text-sm text-cafe-accent" role="note">{{ __('cafe.menu_demo_note') }}</p>
            @endif
        </div>
    </header>

    <div class="cafe-menu-sticky-chips-bar">
        <nav class="cafe-menu-sticky-chips mx-auto max-w-6xl px-4 sm:px-6 lg:px-8" aria-label="{{ __('cafe.menu_categories_aria') }}">
            @foreach ($menuCategories ?? ($content['menu_categories'] ?? []) as $category)
                <button type="button" data-menu-chip="{{ $category['slug'] ?? 'x' }}" @class(['cafe-chip', 'cafe-chip--active' => $loop->first])>
                    {{ $catalogLabel($category) }}
                </button>
            @endforeach
        </nav>
    </div>

    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <p class="mt-4 inline-block rounded-full border border-cafe-caramel/40 bg-cafe-latte/50 px-4 py-2 text-xs font-medium text-cafe-mocha" role="note">
            {{ __('cafe.vat_inclusive') }}
        </p>

        <div class="mt-8 space-y-14">
            @foreach ($menuCategories ?? ($content['menu_categories'] ?? []) as $category)
                <section
                    id="menu-{{ $category['slug'] ?? 'x' }}"
                    data-menu-section="{{ $category['slug'] ?? 'x' }}"
                    class="cafe-menu-section"
                    aria-labelledby="menu-cat-{{ $category['slug'] ?? 'x' }}"
                    data-reveal
                >
                    <h2 id="menu-cat-{{ $category['slug'] ?? 'x' }}" class="cafe-display text-2xl text-cafe-espresso sm:text-3xl">
                        {{ $catalogLabel($category) }}
                    </h2>
                    <ul class="mt-6 grid gap-4 sm:grid-cols-2" role="list" data-reveal-stagger>
                        @foreach ($category['items'] ?? [] as $item)
                            @php
                                $payload = [
                                    'name' => $catalogLabel($item),
                                    'desc' => $catalogLabel($item, 'desc'),
                                    'category' => $catalogLabel($category),
                                    'price_display' => number_format((float) ($item['price_sar'] ?? 0), 2).' '.__('cafe.sar'),
                                    'image' => PosProductImagery::assetFromRelative($item['image'] ?? null),
                                ];
                            @endphp
                            <li data-reveal-child>
                                <article class="cafe-menu-card flex h-full flex-col sm:flex-row">
                                    @if (! empty($item['image']))
                                        <button type="button" class="relative aspect-[4/3] w-full shrink-0 overflow-hidden sm:aspect-auto sm:h-auto sm:w-40" @click="openItem(@js($payload))">
                                            <x-cafe.photo :src="$item['image']" :alt="$payload['name']" class="h-full w-full object-cover transition duration-500 hover:scale-105" width="320" height="240" />
                                        </button>
                                    @endif
                                    <div class="flex flex-1 flex-col gap-2 p-5">
                                        <p class="cafe-product-eyebrow">{{ $payload['category'] }}</p>
                                        <div class="flex items-start justify-between gap-3">
                                            <h3 class="text-lg font-semibold text-cafe-espresso">{{ $payload['name'] }}</h3>
                                            <span class="cafe-price-chip shrink-0">@include('partials.cafe.price', ['amount' => $item['price_sar'] ?? 0])</span>
                                        </div>
                                        @if (! empty($item['sold_out']))
                                            <p class="text-xs font-semibold uppercase tracking-wide text-rose-700">{{ __('inventory.sold_out') }}</p>
                                        @endif
                                        <p class="text-sm leading-relaxed text-cafe-mocha">{{ $payload['desc'] }}</p>
                                        <button type="button" class="mt-auto text-start text-sm font-semibold text-cafe-caramel underline underline-offset-4 lg:hidden" @click="openItem(@js($payload))">
                                            {{ __('cafe.quick_view') }}
                                        </button>
                                    </div>
                                </article>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        </div>
    </div>

    <template x-teleport="body">
        <div
            x-show="sheet"
            x-transition
            class="fixed inset-0 z-[70] bg-cafe-espresso/60 backdrop-blur-sm lg:hidden"
            @click="closeSheet()"
            x-cloak
        ></div>
        <div x-show="sheet" x-transition class="cafe-bottom-sheet lg:hidden" x-cloak role="dialog" aria-modal="true">
            <div class="mx-auto mb-3 h-1.5 w-12 rounded-full bg-cafe-latte" aria-hidden="true"></div>
            <template x-if="sheet">
                <div>
                    <img x-show="sheet?.image" x-bind:src="sheet?.image" alt="" class="mb-4 aspect-video w-full rounded-2xl object-cover" width="400" height="225" loading="lazy">
                    <p class="cafe-product-eyebrow" x-show="sheet?.category" x-text="sheet.category"></p>
                    <h2 class="cafe-display text-2xl text-cafe-espresso" x-text="sheet.name"></h2>
                    <p class="mt-3 text-sm text-cafe-mocha" x-text="sheet.desc"></p>
                    <p class="mt-4 cafe-price-chip inline-flex" x-text="sheet.price_display"></p>
                    <button type="button" class="cafe-pill-cta cafe-pill-cta--primary mt-6 w-full" @click="closeSheet()">{{ __('cafe.sheet_close') }}</button>
                </div>
            </template>
        </div>
    </template>
</div>
