@props(['seo'])
@php
    $seo = is_array($seo ?? null) ? $seo : [];
@endphp
@if ($seo !== [])
    <title>{{ $seo['title'] ?? config('app.name') }}</title>
    <meta name="description" content="{{ $seo['description'] ?? '' }}">
    <meta name="robots" content="{{ $seo['robots'] ?? 'index,follow' }}">
    <link rel="canonical" href="{{ $seo['canonical'] ?? url()->current() }}">
    <meta name="theme-color" content="{{ $seo['theme_color'] ?? '#2C1810' }}">
    @if (! empty($seo['manifest_url']))
        <link rel="manifest" href="{{ $seo['manifest_url'] }}">
    @endif
    @foreach ($seo['hreflang'] ?? [] as $alt)
        <link rel="alternate" hreflang="{{ $alt['hreflang'] }}" href="{{ $alt['href'] }}">
    @endforeach
    <meta property="og:type" content="{{ data_get($seo, 'og.type', 'website') }}">
    <meta property="og:title" content="{{ data_get($seo, 'og.title') }}">
    <meta property="og:description" content="{{ data_get($seo, 'og.description') }}">
    <meta property="og:url" content="{{ data_get($seo, 'og.url') }}">
    <meta property="og:image" content="{{ data_get($seo, 'og.image') }}">
    <meta property="og:locale" content="{{ data_get($seo, 'og.locale') }}">
    <meta property="og:site_name" content="{{ data_get($seo, 'og.site_name') }}">
    <meta name="twitter:card" content="{{ data_get($seo, 'twitter.card', 'summary_large_image') }}">
    <meta name="twitter:title" content="{{ data_get($seo, 'twitter.title') }}">
    <meta name="twitter:description" content="{{ data_get($seo, 'twitter.description') }}">
    <meta name="twitter:image" content="{{ data_get($seo, 'twitter.image') }}">
    @if (! empty($seo['json_ld']))
        <script type="application/ld+json">{!! json_encode($seo['json_ld'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
    @endif
    <x-storefront.seo-verification-analytics :seo="$seo" />
@endif
