@props(['tenant', 'title'])
@php
    $locale = app()->getLocale();
    $isRtl = in_array($locale, ['ar', 'ur'], true);
    $dir = config("locales.supported.{$locale}.dir", 'ltr');
    $displayName = \App\Support\TenantDisplayName::for($tenant, $locale);
    $colors = $tenant->themeColors();
    $logoUrl = data_get($tenant->settings, 'theme.logo_url');
    $hero = $tenant->isCafeTheme() ? data_get($tenant->cafeContent(), 'media.hero') : null;
    $publicUrl = $tenant->isCafeTheme() ? route('cafe.home', $tenant) : url('/');
    $footerLinks = [
        ['label' => __('auth.footer_public'), 'href' => $publicUrl],
    ];
    if ($tenant->isCafeTheme()) {
        $footerLinks[] = ['label' => __('cafe.nav_menu'), 'href' => route('cafe.menu', $tenant)];
    }
    $footerLinks[] = ['label' => __('auth.footer_contact'), 'href' => $publicUrl.'#contact'];
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
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="color-scheme" content="light dark">
    <title>{{ $title }} — {{ $displayName }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body @class([
    'auth-shell min-h-dvh font-sans text-cafe-espresso antialiased',
    'cafe-body' => $tenant->isCafeTheme(),
])>
    <div class="auth-shell__bg" aria-hidden="true">
        @if ($hero)
            <img src="{{ asset($hero) }}" alt="" class="h-full w-full object-cover" width="1600" height="900" loading="eager" decoding="async">
        @endif
        <div class="auth-shell__gradient"></div>
    </div>

    <header class="auth-shell__header" role="banner">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-3 sm:px-6">
            <a href="{{ $publicUrl }}" class="flex min-h-11 min-w-0 items-center gap-2 rounded-full focus-visible:outline focus-visible:outline-2 focus-visible:outline-cafe-accent">
                @if ($logoUrl)
                    <img src="{{ asset($logoUrl) }}" alt="" class="h-9 w-9 rounded-lg object-contain ring-1 ring-white/30" width="36" height="36">
                @else
                    <x-cafe.logo-mark class="h-9 w-9 text-cafe-cream" aria-hidden="true" />
                @endif
                <span class="truncate text-sm font-semibold text-cafe-cream sm:text-base" style="font-family: var(--font-display, Georgia, serif)">{{ $displayName }}</span>
            </a>
            <div class="flex items-center gap-2">
                <a href="{{ $publicUrl }}" class="hidden min-h-11 items-center rounded-full px-3 text-xs font-medium text-cafe-cream/90 underline-offset-4 hover:underline sm:inline-flex">
                    {{ __('auth.back_to_site') }}
                </a>
                <x-cafe.locale-switch :current="$locale" />
            </div>
        </div>
    </header>

    <x-toast-stack />
    <main class="auth-shell__main flex flex-1 flex-col items-center justify-center px-4 py-8 sm:px-6">
        <div class="auth-glass-card w-full max-w-md p-6 sm:p-8">
            {{ $slot }}
        </div>
        <p class="mt-4 text-center text-xs text-cafe-cream/80 sm:hidden">
            <a href="{{ $publicUrl }}" class="underline underline-offset-2">{{ __('auth.back_to_site') }}</a>
        </p>
    </main>

    <footer class="auth-shell__footer px-4 py-6 text-center text-xs text-cafe-cream/75 sm:px-6" role="contentinfo">
        <p class="font-medium text-cafe-cream">{{ $displayName }} · © {{ date('Y') }}</p>
        <nav class="mt-2 flex flex-wrap items-center justify-center gap-x-4 gap-y-1" aria-label="{{ __('auth.footer_nav') }}">
            @foreach ($footerLinks as $link)
                <a href="{{ $link['href'] }}" class="hover:text-cafe-accent">{{ $link['label'] }}</a>
            @endforeach
        </nav>
    </footer>
</body>
</html>
