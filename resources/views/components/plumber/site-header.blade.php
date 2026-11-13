@props(['tenant', 'overlay' => true])
@php
    use App\Support\TenantDisplayName;

    $locale = app()->getLocale();
    $name = TenantDisplayName::for($tenant, $locale);
    $logoUrl = data_get($tenant->settings, 'theme.logo_url');
    $phone = data_get($tenant->plumberContent(), 'contact.phone');
    $navSection = match (true) {
        request()->routeIs('plumber.services') => 'services',
        request()->routeIs('plumber.about') => 'about',
        request()->routeIs('plumber.contact') => 'contact',
        request()->routeIs('cafe.home') => 'home',
        default => null,
    };

    $navLinkClass = fn (string $section): string => 'plumber-header-link'
        .($navSection === $section ? ' plumber-header-link--active' : '');
@endphp
<header
    data-plumber-header
    data-plumber-header-overlay="{{ $overlay ? '1' : '0' }}"
    @class([
        'plumber-header z-40',
        'plumber-header--hero' => $overlay,
    ])
    x-data="{ navOpen: false }"
    @keydown.escape.window="navOpen = false"
>
    <div class="plumber-header__bar mx-auto flex max-w-6xl items-center gap-2 px-3 py-2.5 sm:gap-3 sm:px-6 sm:py-3 lg:gap-4 lg:px-8">
        <a href="{{ route('cafe.home', $tenant) }}" class="flex min-h-11 min-w-0 flex-1 items-center gap-2 sm:gap-3 lg:flex-initial lg:flex-none">
            @if ($logoUrl)
                <img src="{{ asset($logoUrl) }}" alt="{{ __('plumber.logo_alt', ['name' => $name]) }}" class="h-10 w-10 rounded-xl object-contain" width="40" height="40" loading="eager">
            @else
                <x-plumber.logo-mark class="h-10 w-10 text-plumber-accent" />
            @endif
            <span class="truncate text-sm font-bold tracking-tight sm:text-base lg:text-lg">{{ $name }}</span>
        </a>

        <nav class="hidden flex-1 items-center justify-center gap-1 lg:flex" aria-label="{{ __('plumber.main_nav') }}">
            <a href="{{ route('plumber.services', $tenant) }}" @class([$navLinkClass('services')])>{{ __('plumber.nav_services') }}</a>
            <a href="{{ route('plumber.about', $tenant) }}" @class([$navLinkClass('about')])>{{ __('plumber.nav_about') }}</a>
            <a href="{{ route('plumber.contact', $tenant) }}" @class([$navLinkClass('contact')])>{{ __('plumber.nav_contact') }}</a>
        </nav>

        <div class="plumber-header__actions ms-auto flex shrink-0 items-center gap-1.5 sm:gap-2 lg:gap-3">
            <x-plumber.locale-switch :current="$locale" :tenant="$tenant" />
            @if ($phone)
                <span class="plumber-header-divider hidden h-9 w-px shrink-0 lg:block" aria-hidden="true"></span>
                <a href="tel:{{ preg_replace('/\s+/', '', $phone) }}" class="plumber-btn plumber-btn--primary hidden min-h-11 px-4 text-xs whitespace-nowrap lg:inline-flex">
                    {{ __('plumber.cta_call') }}
                </a>
            @endif
            <button type="button" class="plumber-header-menu-btn plumber-btn plumber-btn--navy text-xs lg:hidden" @click="navOpen = !navOpen" :aria-expanded="navOpen" :aria-label="navOpen ? '{{ __('plumber.nav_close') }}' : '{{ __('plumber.nav_toggle') }}'">
                <span class="hidden min-[375px]:inline" x-text="navOpen ? '{{ __('plumber.nav_close') }}' : '{{ __('plumber.nav_toggle') }}'"></span>
                <span class="min-[375px]:hidden" aria-hidden="true" x-text="navOpen ? '✕' : '☰'"></span>
            </button>
        </div>
    </div>
    <nav class="plumber-mobile-nav lg:hidden" x-show="navOpen" x-cloak aria-label="{{ __('plumber.main_nav') }}">
        <a href="{{ route('plumber.services', $tenant) }}" @class(['plumber-mobile-nav__link', 'plumber-mobile-nav__link--active' => $navSection === 'services']) @click="navOpen = false">{{ __('plumber.nav_services') }}</a>
        <a href="{{ route('plumber.about', $tenant) }}" @class(['plumber-mobile-nav__link', 'plumber-mobile-nav__link--active' => $navSection === 'about']) @click="navOpen = false">{{ __('plumber.nav_about') }}</a>
        <a href="{{ route('plumber.contact', $tenant) }}" @class(['plumber-mobile-nav__link', 'plumber-mobile-nav__link--active' => $navSection === 'contact']) @click="navOpen = false">{{ __('plumber.nav_contact') }}</a>
        @if ($phone)
            <a href="tel:{{ preg_replace('/\s+/', '', $phone) }}" class="plumber-mobile-nav__link font-bold text-plumber-safety" @click="navOpen = false">{{ __('plumber.cta_call') }}</a>
        @endif
    </nav>
</header>
