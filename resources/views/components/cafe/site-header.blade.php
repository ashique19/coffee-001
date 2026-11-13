@props(['tenant', 'overlay' => true])
@php
    use App\Support\BusinessSettingDisplayName;

    $locale = app()->getLocale();
    $name = BusinessSettingDisplayName::for($tenant, $locale);
    $logoUrl = data_get($tenant->settings, 'theme.logo_url');
@endphp
<header
    data-cafe-header
    data-cafe-header-overlay="{{ $overlay ? '1' : '0' }}"
    @class([
        'cafe-header',
        'cafe-header--hero' => $overlay,
        'cafe-header--solid' => ! $overlay,
    ])
    x-data="{ navOpen: false }"
    @keydown.escape.window="navOpen = false"
>
    <div class="mx-auto flex max-w-6xl items-center gap-2 px-4 py-3 sm:gap-3 sm:px-6 lg:justify-between lg:px-8">
        <a href="{{ route('cafe.home') }}" class="flex min-h-11 min-w-0 flex-1 items-center gap-2 rounded-full focus-visible:outline focus-visible:outline-2 focus-visible:outline-cafe-accent sm:gap-3 lg:flex-initial">
            @if ($logoUrl)
                <img src="{{ asset($logoUrl) }}" alt="{{ __('cafe.logo_alt', ['name' => $name]) }}" class="h-10 w-10 rounded-xl object-contain ring-1 ring-white/20" width="40" height="40" loading="eager" decoding="async">
            @else
                <x-cafe.logo-mark class="h-10 w-10 text-current opacity-90" aria-hidden="true" />
            @endif
            <span class="truncate font-display text-base font-semibold tracking-tight sm:text-lg" style="font-family: var(--font-display)">{{ $name }}</span>
        </a>

        <nav class="hidden items-center gap-1 lg:flex" aria-label="{{ __('cafe.main_nav') }}">
            <a href="{{ route('cafe.menu') }}" class="cafe-header-link">{{ __('cafe.nav_menu') }}</a>
            <a href="{{ route('cafe.home') }}#about" class="cafe-header-link">{{ __('cafe.nav_about') }}</a>
            <a href="{{ route('cafe.home') }}#gallery" class="cafe-header-link">{{ __('cafe.nav_gallery') }}</a>
            <a href="{{ route('cafe.home') }}#visit" class="cafe-header-link">{{ __('cafe.nav_visit') }}</a>
            <a href="{{ route('cafe.home') }}#contact" class="cafe-header-link">{{ __('cafe.nav_contact') }}</a>
            <x-cafe.locale-switch :current="$locale" menu-id="cafe-locale-menu-desktop" class="ms-2" />
        </nav>

        <div class="flex shrink-0 items-center gap-1.5 sm:gap-2 lg:hidden">
            <x-cafe.locale-switch :current="$locale" />
            <button type="button" class="cafe-pill-cta cafe-pill-cta--ghost min-h-11 px-4 text-xs" @click="navOpen = !navOpen" :aria-expanded="navOpen">
                <span x-text="navOpen ? '{{ __('cafe.nav_close') }}' : '{{ __('cafe.nav_toggle') }}'"></span>
            </button>
        </div>
    </div>

    <nav
        id="cafe-mobile-nav"
        class="cafe-mobile-nav lg:hidden"
        x-show="navOpen"
        x-cloak
        @click.outside="navOpen = false"
        aria-label="{{ __('cafe.main_nav') }}"
    >
        <ul class="flex flex-col gap-1">
            <li><a href="{{ route('cafe.menu') }}" class="cafe-mobile-nav__link" @click="navOpen = false">{{ __('cafe.nav_menu') }}</a></li>
            <li><a href="{{ route('cafe.home') }}#about" class="cafe-mobile-nav__link" @click="navOpen = false">{{ __('cafe.nav_about') }}</a></li>
            <li><a href="{{ route('cafe.home') }}#gallery" class="cafe-mobile-nav__link" @click="navOpen = false">{{ __('cafe.nav_gallery') }}</a></li>
            <li><a href="{{ route('cafe.home') }}#visit" class="cafe-mobile-nav__link" @click="navOpen = false">{{ __('cafe.nav_visit') }}</a></li>
            <li><a href="{{ route('cafe.home') }}#contact" class="cafe-mobile-nav__link" @click="navOpen = false">{{ __('cafe.nav_contact') }}</a></li>
        </ul>
    </nav>
</header>
