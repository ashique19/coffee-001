@props(['tenant', 'content', 'legal'])
@php
    $locale = app()->getLocale();
    $isRtl = in_array($locale, ['ar', 'ur'], true);
    $displayName = \App\Support\TenantDisplayName::for($tenant, $locale);
    $lat = data_get($content, 'location.lat');
    $lng = data_get($content, 'location.lng');
    $address = $isRtl
        ? ($legal['address_ar'] ?? $legal['address'] ?? '')
        : ($legal['address'] ?? '');
    $markers = [];
    if (is_numeric($lat) && is_numeric($lng)) {
        $markers[] = [
            'slug' => 'hub',
            'name' => $displayName,
            'address' => $address,
            'lat' => (float) $lat,
            'lng' => (float) $lng,
        ];
    }
    $accent = $tenant->themeColors()['accent'] ?? '#1B8A8A';
@endphp
@if ($markers !== [])
    <div
        data-cafe-branch-map
        data-markers='@json($markers)'
        data-map-rtl="{{ $isRtl ? '1' : '0' }}"
        data-map-labels='@json([
            'unlock' => __('plumber.map_tap_to_explore'),
            'directions' => __('plumber.map_directions'),
        ])'
        class="plumber-contact-map cafe-branch-map cafe-branch-map--locked relative overflow-hidden rounded-2xl border border-plumber-sky bg-plumber-ice shadow-lg"
        style="--cafe-map-accent: {{ $accent }}"
        role="region"
        aria-label="{{ __('plumber.map_region_label') }}"
    >
        <div data-cafe-map-host class="cafe-branch-map__host min-h-[14rem] w-full sm:min-h-[18rem]" tabindex="-1"></div>
        <button
            type="button"
            data-cafe-map-unlock
            class="cafe-branch-map__unlock absolute inset-0 z-10 flex min-h-11 items-center justify-center bg-plumber-navy/40 px-4 text-center text-sm font-semibold text-white backdrop-blur-[2px]"
        >
            {{ __('plumber.map_tap_to_explore') }}
        </button>
    </div>
@endif
