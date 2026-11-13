@props(['tenant', 'branches'])
@php
    $locale = app()->getLocale();
    $isRtl = in_array($locale, ['ar', 'ur'], true);
    $accent = $tenant->themeColors()['accent'] ?? '#D4A574';
    $markers = [];
    foreach ($branches as $branch) {
        $lat = $branch['lat'] ?? null;
        $lng = $branch['lng'] ?? null;
        if (! is_numeric($lat) || ! is_numeric($lng)) {
            continue;
        }
        $markers[] = [
            'slug' => $branch['slug'] ?? '',
            'name' => $isRtl ? ($branch['name_ar'] ?? $branch['name_en'] ?? '') : ($branch['name_en'] ?? ''),
            'address' => $isRtl ? ($branch['address_ar'] ?? $branch['address_en'] ?? '') : ($branch['address_en'] ?? ''),
            'lat' => (float) $lat,
            'lng' => (float) $lng,
        ];
    }
@endphp
@if ($markers !== [])
    <div
        data-cafe-branch-map
        data-markers='@json($markers)'
        data-map-rtl="{{ $isRtl ? '1' : '0' }}"
        data-map-labels='@json([
            'unlock' => __('cafe.map_tap_to_explore'),
            'directions' => __('cafe.map_directions'),
        ])'
        class="cafe-branch-map cafe-branch-map--locked relative overflow-hidden rounded-[1.5rem] border border-cafe-latte bg-cafe-cream shadow-xl"
        style="--cafe-map-accent: {{ $accent }}"
        role="region"
        aria-label="{{ __('cafe.map_region_label') }}"
    >
        <div data-cafe-map-host class="cafe-branch-map__host min-h-[17.5rem] w-full sm:min-h-[22.5rem]" tabindex="-1"></div>
        <button
            type="button"
            data-cafe-map-unlock
            class="cafe-branch-map__unlock absolute inset-0 z-10 flex min-h-11 items-center justify-center bg-cafe-espresso/35 px-4 text-center text-sm font-semibold text-cafe-cream backdrop-blur-[2px] transition hover:bg-cafe-espresso/45 focus-visible:outline focus-visible:outline-2 focus-visible:outline-cafe-accent"
        >
            {{ __('cafe.map_tap_to_explore') }}
        </button>
    </div>
@endif
