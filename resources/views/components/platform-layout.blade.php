@props(['title' => null, 'previewUrl' => null])
@php
    use App\Support\AppShellNav;

    $locale = app()->getLocale();
    $dir = config("locales.supported.{$locale}.dir", 'ltr');
    $active = AppShellNav::platformActiveTab();
    $pageTitle = $title ?? __('platform.title');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $dir }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="color-scheme" content="light dark">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $pageTitle }} — {{ __('platform.title') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{ $head ?? '' }}
    @livewireStyles
</head>
<body class="app-shell antialiased" x-data="{ sidebarCollapsed: false }">
    <x-toast-stack class="has-app-tab-bar" />
    <aside class="app-sidebar" :class="sidebarCollapsed ? 'app-sidebar--collapsed' : ''">
        <div class="flex items-center gap-2 border-b px-3 py-4" style="border-color: var(--app-border)">
            <x-cafe.logo-mark class="h-9 w-9 text-[var(--app-accent)]" />
            <span class="app-sidebar__label truncate text-sm font-semibold">{{ __('platform.login_title') }}</span>
        </div>
        <nav class="flex flex-1 flex-col gap-1 p-2">
            <a href="{{ route('platform.dashboard') }}" @class(['app-nav-link', 'app-nav-link--active' => $active === 'tenants'])>
                <x-app.icon name="users" class="h-5 w-5 shrink-0" />
                <span class="app-sidebar__label">{{ __('platform.nav_tenants') }}</span>
            </a>
            <a href="{{ route('platform.clients.export') }}" @class(['app-nav-link', 'app-nav-link--active' => $active === 'export'])>
                <x-app.icon name="cube" class="h-5 w-5 shrink-0" />
                <span class="app-sidebar__label">{{ __('platform.nav_export') }}</span>
            </a>
        </nav>
        <p class="app-sidebar__label px-3 pb-1 text-[0.65rem] text-[var(--app-muted)]">{{ __('platform.nav_storefront') }}</p>
        <div class="border-t p-2" style="border-color: var(--app-border)">
            <form method="POST" action="{{ route('platform.logout') }}">
                @csrf
                <button type="submit" class="app-nav-link w-full">
                    <x-app.icon name="logout" class="h-5 w-5 shrink-0" />
                    <span class="app-sidebar__label">{{ __('platform.logout') }}</span>
                </button>
            </form>
        </div>
    </aside>

    <div class="app-main" :class="sidebarCollapsed ? 'app-main--sidebar-collapsed' : ''">
        <header class="app-topbar">
            <div class="mx-auto flex max-w-6xl items-center gap-3 px-3 py-2 sm:px-5 sm:py-3">
                <h1 class="min-w-0 flex-1 truncate text-sm font-semibold sm:text-base">{{ $pageTitle }}</h1>
                @if($previewUrl)
                    <a href="{{ $previewUrl }}" target="_blank" rel="noopener" class="tap-action-muted hidden min-h-11 gap-2 sm:inline-flex">
                        <x-app.icon name="external" class="h-4 w-4" />
                        {{ __('platform.storefront_preview') }}
                    </a>
                @endif
                <x-app.locale-switch :current="$locale" />
            </div>
        </header>
        <main class="app-content !max-w-5xl">
            {{ $slot }}
        </main>
        <nav class="app-tabbar lg:hidden" aria-label="{{ __('platform.title') }}">
            <a href="{{ route('platform.dashboard') }}" @class(['app-tab', 'app-tab--active' => $active === 'tenants'])>
                <x-app.icon name="users" />
                {{ __('platform.nav_tenants') }}
            </a>
            <a href="{{ route('platform.clients.export') }}" @class(['app-tab', 'app-tab--active' => $active === 'export'])>
                <x-app.icon name="cube" />
                {{ __('platform.nav_export') }}
            </a>
        </nav>
    </div>
    <x-toast-prime-script />
    @livewireScriptConfig
    {{ $scripts ?? '' }}
</body>
</html>
