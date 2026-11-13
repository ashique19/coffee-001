@php
    use App\Support\TenantDisplayName;

    $locale = app()->getLocale();
    $dir = config("locales.supported.{$locale}.dir", 'ltr');
    $colors = $tenant->themeColors();
    $content = $footerContent ?? $tenant->cafeContent();
    $heroHeader = $heroHeader ?? true;
    $salesDemo = (bool) (data_get($content, 'sales_demo') ?? data_get($tenant->settings, 'content.cafe.sales_demo'));
    $displayName = TenantDisplayName::for($tenant, $locale);
    $logoUrl = data_get($tenant->settings, 'theme.logo_url');
    $iconUrl = $logoUrl ? asset($logoUrl) : asset('favicon.ico');
    $seo = $seo ?? null;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $dir }}" class="scroll-smooth"
      style="
        --cafe-espresso: {{ $colors['espresso'] ?? '#2C1810' }};
        --cafe-cream: {{ $colors['cream'] ?? '#FAF6F0' }};
        --cafe-caramel: {{ $colors['caramel'] ?? '#C68B59' }};
        --cafe-latte: {{ $colors['latte'] ?? '#E8D4C4' }};
        --cafe-mocha: {{ $colors['mocha'] ?? '#5C3D2E' }};
        --cafe-accent: {{ $colors['accent'] ?? '#D4A574' }};
      ">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <link rel="icon" href="{{ $iconUrl }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ $iconUrl }}">
    @if (is_array($seo) && $seo !== [])
        <x-cafe.seo-head :seo="$seo" />
    @else
        @php
            $tagline = TenantDisplayName::usesRtlName($locale)
                ? ($content['tagline_ar'] ?? '')
                : ($content['tagline_en'] ?? '');
            $metaDescription = $tagline !== '' ? $tagline : __('cafe.meta_description');
            $heroImage = data_get($content, 'media.hero');
            $ogImage = $heroImage ? asset($heroImage) : asset('images/fancy-coffee/hero.webp');
            $pageTitle = $displayName.' — '.__('cafe.meta_suffix');
        @endphp
        <title>{{ $pageTitle }}</title>
        <meta name="description" content="{{ $metaDescription }}">
        <meta property="og:type" content="website">
        <meta property="og:title" content="{{ $pageTitle }}">
        <meta property="og:description" content="{{ $metaDescription }}">
        <meta property="og:image" content="{{ $ogImage }}">
    @endif
    @vite(['resources/css/app.css', 'resources/js/cafe-storefront.js'])
    @livewireStyles
</head>
<body
    data-cafe-storefront
    class="cafe-body cafe-grain is-loading min-h-screen bg-cafe-cream font-sans text-cafe-espresso antialiased"
    data-dir="{{ $dir }}"
>
    @if (is_array($seo ?? null) && filled($seo['gtm_container_id'] ?? null))
        <noscript>
            <iframe src="https://www.googletagmanager.com/ns.html?id={{ urlencode($seo['gtm_container_id']) }}"
                    height="0" width="0" style="display:none;visibility:hidden" title=""></iframe>
        </noscript>
    @endif
    <div data-cursor-glow class="cafe-cursor-glow" aria-hidden="true"></div>

    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:z-[70] focus:m-4 focus:rounded-lg focus:bg-cafe-espresso focus:px-4 focus:py-2 focus:text-cafe-cream">
        {{ __('cafe.skip_to_content') }}
    </a>

    @if ($salesDemo)
        <p class="cafe-demo-banner relative z-[45] border-b border-amber-200/80 bg-amber-50 px-4 py-2 text-center text-xs font-medium text-amber-950 sm:text-sm" role="note">
            {{ __('cafe.demo_banner') }}
        </p>
    @endif

    <x-cafe.site-header :tenant="$tenant" :overlay="$heroHeader" />

    <main id="main" class="outline-none">
        {{ $slot }}
    </main>

    <x-cafe.site-footer :tenant="$tenant" :content="$content" :sales-demo="$salesDemo" />

    @livewireScripts
</body>
</html>
