@php
    use App\Support\Storefront\CafeMapsUrl;
    $locale = app()->getLocale();
    $isAr = \App\Support\TenantDisplayName::usesRtlName($locale);
    $preferArabic = in_array($locale, ['ar', 'bn'], true);
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
    $legal = data_get($tenant->settings, 'legal_entity', []);
    $heroImage = data_get($content, 'media.hero');
    $aboutImage = data_get($content, 'media.about');
    $displayName = \App\Support\TenantDisplayName::for($tenant, $locale);
    $tagline = $isAr ? ($content['tagline_ar'] ?? '') : ($content['tagline_en'] ?? '');
    $locationBadge = $isAr
        ? ($content['location_badge_ar'] ?? $legal['address_ar'] ?? $legal['address'] ?? '')
        : ($content['location_badge_en'] ?? $legal['address'] ?? '');
    $lat = data_get($content, 'location.lat');
    $lng = data_get($content, 'location.lng');
    $mapUrl = filled($lat) && filled($lng)
        ? 'https://www.google.com/maps/search/?api=1&query='.urlencode($lat.','.$lng)
        : 'https://www.google.com/maps/search/?api=1&query='.urlencode($isAr ? ($legal['address_ar'] ?? $legal['address'] ?? '') : ($legal['address'] ?? ''));
    $visitImage = data_get($content, 'location.image') ?? data_get($content, 'media.visit');
    $mapBranches = $content['branches'] ?? [];
    $hasMap = collect($mapBranches)->contains(fn ($b) => is_numeric($b['lat'] ?? null) && is_numeric($b['lng'] ?? null));
    $locationNote = $isAr ? ($content['location']['note_ar'] ?? null) : ($content['location']['note_en'] ?? null);
@endphp
<div class="pb-8">
    <section class="cafe-premium-hero" data-parallax-root aria-label="{{ __('cafe.hero_photo_alt', ['name' => $displayName]) }}">
        <div class="cafe-hero-bg" data-parallax data-parallax-speed="0.35">
            @if ($heroImage)
                <x-cafe.photo :src="$heroImage" :alt="__('cafe.hero_photo_alt', ['name' => $displayName])" class="h-full w-full object-cover" width="1600" height="1067" loading="eager" fetchpriority="high" />
            @endif
        </div>
        <div class="cafe-hero-overlay" aria-hidden="true"></div>
        <div class="cafe-hero-floating" aria-hidden="true">
            <span class="cafe-float-bean left-[8%] top-[22%] h-16 w-16" style="animation-delay: 0s"></span>
            <span class="cafe-float-bean left-[72%] top-[30%] h-10 w-10 opacity-60" style="animation-delay: 1.2s"></span>
            <span class="cafe-float-bean left-[55%] top-[18%] h-20 w-20 opacity-40" style="animation-delay: 2.4s"></span>
            <div class="absolute inset-x-[20%] bottom-[38%] h-32 opacity-70 sm:h-40">
                <x-cafe.hero-steam />
            </div>
        </div>
        <div class="cafe-hero-inner">
            @if (filled($locationBadge))
                <p data-reveal class="mb-4 inline-flex max-w-full items-center gap-2 rounded-full border border-cafe-cream/25 bg-cafe-espresso/30 px-4 py-2 text-xs font-medium text-cafe-cream backdrop-blur sm:text-sm">
                    <span class="h-1.5 w-1.5 rounded-full bg-cafe-accent" aria-hidden="true"></span>
                    <span class="truncate">{{ $locationBadge }}</span>
                </p>
            @endif
            <h1 data-hero-headline class="cafe-display max-w-4xl text-cafe-cream">{{ $displayName }}</h1>
            <p data-reveal class="mt-5 max-w-xl text-lg leading-relaxed text-cafe-cream/85 sm:text-xl">{{ $tagline }}</p>
            <div data-reveal class="mt-8 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                <a href="{{ route('cafe.menu') }}" class="cafe-pill-cta cafe-pill-cta--primary w-full sm:w-auto">
                    {{ __('cafe.cta_menu') }}
                </a>
                <a href="#visit" class="cafe-pill-cta cafe-pill-cta--ghost w-full sm:w-auto">
                    {{ __('cafe.cta_visit') }}
                </a>
            </div>
            <div class="cafe-scroll-cue hidden sm:flex" aria-hidden="true">
                {{ __('cafe.scroll_cue') }}
                <span></span>
            </div>
        </div>
    </section>

    @if (! empty($content['highlights']))
        <section class="relative z-10 -mt-10 px-4 sm:px-6 lg:px-8" aria-label="{{ __('cafe.highlights_aria') }}" data-reveal-stagger>
            <ul class="mx-auto grid max-w-6xl gap-4 sm:grid-cols-3" role="list">
                @foreach ($content['highlights'] as $row)
                    @php
                        $valDisplay = $isAr ? ($row['value_ar'] ?? '') : ($row['value_en'] ?? '');
                        $counterTarget = preg_replace('/[^\d+]/', '', $valDisplay) !== '' ? $valDisplay : null;
                    @endphp
                    <li data-reveal-child class="cafe-stat-card">
                        <p class="cafe-stat-value" @if($counterTarget && ! str_contains($valDisplay, '—')) data-counter="{{ $valDisplay }}" @endif>
                            {{ $valDisplay }}
                        </p>
                        <p class="mt-2 text-xs font-semibold uppercase tracking-widest text-cafe-mocha">
                            {{ $isAr ? ($row['label_ar'] ?? '') : ($row['label_en'] ?? '') }}
                            @if (! empty($content['sales_demo']))
                                <span class="text-cafe-caramel">· {{ __('cafe.sample_label') }}</span>
                            @endif
                        </p>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    @if (! empty($featured))
    <section id="signature" class="mx-auto max-w-6xl px-4 py-20 sm:px-6 lg:px-8" data-reveal data-featured-source="{{ $featuredSource ?? 'marketing' }}">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <h2 class="cafe-display text-cafe-espresso">{{ __('cafe.section_featured') }}</h2>
            <a href="{{ route('cafe.menu') }}" class="text-sm font-semibold text-cafe-caramel underline underline-offset-4">{{ __('cafe.view_full_menu') }}</a>
        </div>
        <div class="cafe-carousel mt-8" data-reveal-stagger role="list">
            @foreach ($featured as $item)
                <article data-reveal-child class="cafe-carousel-card" role="listitem">
                    @if (! empty($item['image']))
                        <div class="aspect-[4/5] overflow-hidden" data-clip-reveal>
                            <x-cafe.photo :src="$item['image']" :alt="$catalogLabel($item)" class="h-full w-full object-cover transition duration-700 hover:scale-105" width="400" height="500" />
                        </div>
                    @endif
                    <div class="cafe-carousel-card__body">
                        @php($eyebrow = $preferArabic ? ($item['badge_ar'] ?? $item['badge_en'] ?? '') : ($item['badge_en'] ?? ''))
                        @if ($eyebrow !== '')
                            <p class="cafe-product-eyebrow">{{ $eyebrow }}</p>
                        @endif
                        <h3 class="text-lg font-semibold text-cafe-espresso">{{ $catalogLabel($item) }}</h3>
                        <p class="text-sm leading-relaxed text-cafe-mocha line-clamp-2">{{ $catalogLabel($item, 'desc') }}</p>
                        <p class="cafe-price-chip mt-3 inline-flex">@include('partials.cafe.price', ['amount' => $item['price_sar'] ?? 0])</p>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
    @endif

    <section class="cafe-parallax-band" data-parallax-root>
        <div class="cafe-parallax-band__media" data-parallax data-parallax-speed="0.45" aria-hidden="true">
            @if ($aboutImage)
                <x-cafe.photo :src="$aboutImage" alt="" class="h-[140%] w-full object-cover" width="1200" height="800" loading="lazy" />
            @endif
        </div>
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div id="about" data-reveal class="grid items-center gap-10 rounded-[2rem] bg-cafe-cream/92 p-8 shadow-2xl backdrop-blur-md lg:grid-cols-12 lg:gap-0 lg:p-0">
                @if ($aboutImage)
                    <figure class="cafe-clip-reveal relative lg:col-span-5 lg:-ms-8 lg:mb-8 lg:mt-8" data-clip-reveal>
                        <x-cafe.photo :src="$aboutImage" :alt="__('cafe.about_photo_alt', ['name' => $displayName])" class="aspect-[4/5] w-full rounded-[1.5rem] object-cover shadow-xl lg:aspect-[3/4]" width="800" height="1000" />
                    </figure>
                @endif
                <div class="lg:col-span-7 lg:p-12" data-reveal>
                    <p class="text-xs font-bold uppercase tracking-[0.25em] text-cafe-caramel">{{ __('cafe.section_about') }}</p>
                    <h2 class="cafe-display mt-4 text-cafe-espresso">{{ $displayName }}</h2>
                    <p class="mt-6 text-lg leading-relaxed text-cafe-mocha">
                        {{ $isAr ? ($content['about_ar'] ?? '') : ($content['about_en'] ?? '') }}
                    </p>
                </div>
            </div>
        </div>
    </section>

    @if (! empty($content['gallery']))
        <section id="gallery" class="mx-auto max-w-6xl px-4 py-20 sm:px-6 lg:px-8" data-cafe-gallery>
            <h2 data-reveal class="cafe-display text-cafe-espresso">{{ __('cafe.section_gallery') }}</h2>
            @if (! empty($content['sales_demo']))
                <p data-reveal class="mt-2 text-sm text-cafe-mocha">{{ __('cafe.gallery_intro') }}</p>
            @endif
            <div class="cafe-masonry mt-10" data-reveal-stagger>
                @foreach ($content['gallery'] as $shot)
                    <figure data-reveal-child class="cafe-masonry-item group">
                        <button
                            type="button"
                            class="block w-full overflow-hidden rounded-2xl ring-1 ring-cafe-latte transition hover:ring-cafe-caramel"
                            data-gallery-open="{{ e(asset($shot['image'])) }}"
                            data-gallery-alt="{{ $isAr ? ($shot['alt_ar'] ?? '') : ($shot['alt_en'] ?? '') }}"
                        >
                            <x-cafe.photo
                                :src="$shot['image']"
                                :alt="$isAr ? ($shot['alt_ar'] ?? '') : ($shot['alt_en'] ?? '')"
                                class="w-full object-cover transition duration-500 group-hover:scale-105"
                                width="800"
                                height="600"
                            />
                        </button>
                    </figure>
                @endforeach
            </div>
            <dialog data-lightbox class="fixed inset-0 z-[80] m-0 max-h-none max-w-none border-0 bg-cafe-espresso/95 p-4 backdrop:bg-black/60 open:flex open:flex-col open:items-center open:justify-center">
                <button type="button" data-lightbox-close class="absolute end-4 top-4 rounded-full bg-white/10 px-4 py-2 text-sm text-white">{{ __('cafe.lightbox_close') }}</button>
                <img data-lightbox-img src="" alt="" class="max-h-[85vh] max-w-full rounded-2xl object-contain shadow-2xl" width="1200" height="900">
            </dialog>
        </section>
    @endif

    @if (! empty($content['testimonials']))
        <section class="bg-cafe-latte/35 py-20" aria-labelledby="testimonials-heading">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <h2 id="testimonials-heading" data-reveal class="cafe-display text-cafe-espresso">{{ __('cafe.section_testimonials') }}</h2>
                <p data-reveal class="mt-2 text-sm text-cafe-mocha">{{ __('cafe.testimonials_sample') }}</p>
                <ul class="mt-10 grid gap-6 md:grid-cols-2" data-reveal-stagger role="list">
                    @foreach ($content['testimonials'] as $t)
                        <li data-reveal-child class="cafe-testimonial">
                            <p class="text-lg leading-relaxed text-cafe-espresso">“{{ $isAr ? ($t['quote_ar'] ?? '') : ($t['quote_en'] ?? '') }}”</p>
                            <p class="mt-4 text-sm font-semibold text-cafe-mocha">
                                {{ $isAr ? ($t['author_ar'] ?? '') : ($t['author_en'] ?? '') }}
                                @if (! empty($content['sales_demo']))
                                    <span class="text-cafe-caramel">— {{ __('cafe.sample_label') }}</span>
                                @endif
                            </p>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    @if (! empty($content['branches']))
        <section id="branches" class="mx-auto max-w-6xl px-4 py-20 sm:px-6 lg:px-8">
            <h2 data-reveal class="cafe-display text-cafe-espresso">{{ __('cafe.section_branches') }}</h2>
            @if (! empty($content['sales_demo']))
                <p data-reveal class="mt-2 text-sm text-cafe-mocha">{{ __('cafe.branches_sample_note') }}</p>
            @endif
            <ul class="mt-10 grid gap-8 lg:grid-cols-2" data-reveal-stagger role="list">
                @foreach ($content['branches'] as $branch)
                    <li data-reveal-child class="cafe-menu-card overflow-hidden">
                        <x-cafe.photo :src="$branch['image']" :alt="$isAr ? ($branch['alt_ar'] ?? '') : ($branch['alt_en'] ?? '')" class="aspect-[16/10] w-full object-cover" width="800" height="500" />
                        <div class="space-y-3 p-6">
                            <h3 class="text-xl font-semibold">{{ $isAr ? ($branch['name_ar'] ?? '') : ($branch['name_en'] ?? '') }}</h3>
                            <p class="text-sm text-cafe-mocha">{{ $isAr ? ($branch['address_ar'] ?? '') : ($branch['address_en'] ?? '') }}</p>
                            <a href="{{ CafeMapsUrl::branchDirections($branch, $mapUrl) }}" target="_blank" rel="noopener noreferrer" class="cafe-pill-cta cafe-pill-cta--ghost inline-flex border-cafe-latte bg-cafe-cream text-cafe-espresso">
                                {{ __('cafe.open_maps') }}
                            </a>
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <section id="visit" class="mx-auto max-w-6xl px-4 py-20 sm:px-6 lg:px-8" data-reveal>
        <h2 class="cafe-display text-cafe-espresso">{{ __('cafe.section_visit') }}</h2>
        <div class="mt-10 grid gap-8 lg:grid-cols-2">
            <div class="rounded-[1.5rem] border border-cafe-latte bg-white p-8 shadow-lg">
                <h3 class="text-lg font-semibold text-cafe-mocha">{{ __('cafe.hours_title') }}</h3>
                <dl class="mt-4 space-y-3">
                    @foreach ($content['hours'] ?? [] as $row)
                        <div class="flex justify-between gap-4 border-b border-cafe-cream pb-2 text-sm">
                            <dt class="font-medium">{{ $isAr ? ($row['label_ar'] ?? '') : ($row['label_en'] ?? '') }}</dt>
                            <dd class="text-end tabular-nums text-cafe-mocha">{{ $isAr ? ($row['value_ar'] ?? '') : ($row['value_en'] ?? '') }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
            @if ($hasMap)
                <div>
                    <x-cafe.branch-map :tenant="$tenant" :branches="$mapBranches" />
                    @if (filled($locationNote))
                        <p class="mt-3 text-sm leading-relaxed text-cafe-mocha" role="note">{{ $locationNote }}</p>
                    @endif
                </div>
            @else
                <figure class="cafe-clip-reveal overflow-hidden rounded-[1.5rem] shadow-xl" data-clip-reveal>
                    @if ($visitImage)
                        <x-cafe.photo :src="$visitImage" :alt="__('cafe.visit_photo_alt', ['name' => $displayName])" class="aspect-[4/3] w-full object-cover" width="800" height="600" />
                    @endif
                    <figcaption class="bg-cafe-espresso px-6 py-4 text-sm text-cafe-cream">
                        {{ $isAr ? ($legal['address_ar'] ?? $legal['address'] ?? '') : ($legal['address'] ?? '') }}
                    </figcaption>
                </figure>
            @endif
        </div>
    </section>

    <section id="contact" class="mx-auto max-w-6xl px-4 pb-4 sm:px-6 lg:px-8" data-reveal>
        <div class="cafe-cta-band">
            <h2 class="cafe-display text-cafe-cream">{{ __('cafe.cta_band_title', ['name' => $displayName]) }}</h2>
            <p class="mx-auto mt-4 max-w-lg text-cafe-latte">{{ $tagline }}</p>
            <div class="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
                <a href="{{ route('cafe.menu') }}" class="cafe-pill-cta cafe-pill-cta--primary">{{ __('cafe.cta_menu') }}</a>
                <a href="tel:{{ preg_replace('/\s+/', '', $content['contact']['phone'] ?? '') }}" class="cafe-pill-cta cafe-pill-cta--ghost">{{ $content['contact']['phone'] ?? '' }}</a>
            </div>
        </div>
    </section>
</div>
