@php
    use App\Support\TenantDisplayName;

    $locale = app()->getLocale();
    $dir = config("locales.supported.{$locale}.dir", 'ltr');
    $colors = $tenant->themeColors();
    $content = $footerContent ?? $tenant->plumberContent();
    $heroHeader = $heroHeader ?? true;
    $displayName = TenantDisplayName::for($tenant, $locale);
    $logoUrl = data_get($tenant->settings, 'theme.logo_url');
    $iconUrl = $logoUrl ? asset($logoUrl) : asset('images/plumber/hero.webp');
    $seo = $seo ?? null;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $dir }}" class="scroll-smooth"
      style="
        --plumber-navy: {{ $colors['navy'] ?? '#0B1F3A' }};
        --plumber-steel: {{ $colors['steel'] ?? '#2D4A6F' }};
        --plumber-sky: {{ $colors['sky'] ?? '#E8F1FA' }};
        --plumber-ice: {{ $colors['ice'] ?? '#F4F8FC' }};
        --plumber-safety: {{ $colors['safety'] ?? '#E87722' }};
        --plumber-accent: {{ $colors['accent'] ?? '#1B8A8A' }};
        --plumber-slate: {{ $colors['slate'] ?? '#475569' }};
      ">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <link rel="icon" href="{{ $iconUrl }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ $iconUrl }}">
    @if (is_array($seo) && $seo !== [])
        <x-plumber.seo-head :seo="$seo" />
    @else
        @php
            $tagline = TenantDisplayName::usesRtlName($locale)
                ? ($content['tagline_ar'] ?? '')
                : ($content['tagline_en'] ?? '');
            $pageTitle = $displayName.' — '.__('plumber.meta_suffix');
        @endphp
        <title>{{ $pageTitle }}</title>
        <meta name="description" content="{{ $tagline !== '' ? $tagline : __('plumber.meta_description') }}">
    @endif
    @vite(['resources/css/plumber-storefront.css', 'resources/js/plumber-storefront.js'])
    @livewireStyles
</head>
<body
    data-plumber-storefront
    class="plumber-body min-h-screen antialiased is-loading"
    data-dir="{{ $dir }}"
>
    @if (is_array($seo ?? null) && filled($seo['gtm_container_id'] ?? null))
        <noscript>
            <iframe src="https://www.googletagmanager.com/ns.html?id={{ urlencode($seo['gtm_container_id']) }}"
                    height="0" width="0" style="display:none;visibility:hidden" title=""></iframe>
        </noscript>
    @endif
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:z-[70] focus:m-4 focus:rounded-lg focus:bg-plumber-navy focus:px-4 focus:py-2 focus:text-white">
        {{ __('plumber.skip_to_content') }}
    </a>

    @if ($tenant->showsPlumberDemoBanner())
        <p class="plumber-demo-banner relative z-[45] px-4 py-2" role="note">
            {{ __('plumber.demo_banner') }}
        </p>
    @endif

    <x-plumber.site-header :tenant="$tenant" :overlay="$heroHeader" />

    <main id="main" class="outline-none">
        {{ $slot }}
    </main>

    <x-plumber.site-footer :tenant="$tenant" :content="$content" />

    @livewireScripts
</body>
</html>
