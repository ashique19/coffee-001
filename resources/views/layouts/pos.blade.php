@props(['branch' => null])

@php
    use App\Models\Branch;
    use App\Models\BusinessSetting;
    use App\Support\AppShellNav;
    use App\Support\BusinessSettingDisplayName;
    use App\Support\DocumentTitle;

    $tenant = \App\Business\BusinessContext::get() ?? BusinessSetting::query()->find(1);
    $branch = $branch ?? request()->route('branch');
    if ($branch !== null && ! $branch instanceof Branch) {
        $branch = Branch::query()->withoutGlobalScopes()->find($branch);
    }
    $locale = app()->getLocale();
    $dir = config("locales.supported.{$locale}.dir", 'ltr');
    $active = AppShellNav::tenantActiveTab();
    $displayName = $tenant ? BusinessSettingDisplayName::for($tenant, $locale) : config('app.name');
    $logoUrl = $tenant ? data_get($tenant->settings, 'theme.logo_url') : null;
    $logoAltKey = 'cafe.logo_alt';
    $colors = $tenant?->themeColors() ?? [];
    $branches = $tenant ? AppShellNav::branchesForSwitcher($tenant) : [];
    app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($tenant?->id ?? 1);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $dir }}" class="scroll-smooth"
      style="
        --cafe-espresso: {{ $colors['espresso'] ?? $colors['navy'] ?? '#2C1810' }};
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
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    @php
        $documentTitle = DocumentTitle::join(DocumentTitle::page($pageTitle ?? null), (string) $displayName);
    @endphp
    <title>{{ $documentTitle }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
    @livewireStyles
</head>
<body class="app-shell antialiased" x-data="{ sidebarCollapsed: false, moreOpen: false, userOpen: false }" @keydown.escape.window="moreOpen = false; userOpen = false">
    <div class="livewire-loading-bar" aria-hidden="true"></div>
    <x-toast-stack class="has-app-tab-bar" />

    @if($tenant)
        <aside class="app-sidebar" :class="sidebarCollapsed ? 'app-sidebar--collapsed' : ''" aria-label="{{ __('pos.title') }}">
            <div class="flex items-center gap-2 border-b px-3 py-3" style="border-color: var(--app-border)">
                @if ($logoUrl)
                    <img src="{{ asset($logoUrl) }}" alt="{{ __($logoAltKey, ['name' => $displayName]) }}" class="h-9 w-9 shrink-0 rounded-lg object-contain" width="36" height="36">
                @else
                    <x-cafe.logo-mark class="h-9 w-9 shrink-0 text-[var(--app-accent)]" :name="$displayName" />
                @endif
                <span class="app-sidebar__label truncate text-sm font-semibold">{{ $displayName }}</span>
            </div>
            <nav class="flex flex-1 flex-col gap-1 overflow-y-auto p-2">
                @php
                    $branchParam = $branch ?? ($branches[0] ?? null);
                @endphp
                @include('partials.admin-nav-groups')
            </nav>
            <div class="border-t p-2" style="border-color: var(--app-border)">
                <button type="button" class="app-nav-link w-full" @click="sidebarCollapsed = !sidebarCollapsed">
                    <x-app.icon name="menu" class="h-5 w-5 shrink-0" />
                    <span class="app-sidebar__label">{{ __('pos.collapse_sidebar') }}</span>
                </button>
                <form method="POST" action="{{ route('admin.logout') }}" class="mt-1">
                    @csrf
                    <button type="submit" class="app-nav-link w-full">
                        <x-app.icon name="logout" class="h-5 w-5 shrink-0" />
                        <span class="app-sidebar__label">{{ __('pos.logout') }}</span>
                    </button>
                </form>
            </div>
        </aside>

        <div class="app-main" :class="sidebarCollapsed ? 'app-main--sidebar-collapsed' : ''">
            <header class="app-topbar">
                <div class="mx-auto flex max-w-6xl items-center gap-2 px-3 py-2 sm:px-5 sm:py-3">
                    <div class="flex min-w-0 flex-1 items-center gap-2">
                        @if ($logoUrl)
                            <img src="{{ asset($logoUrl) }}" alt="{{ __($logoAltKey, ['name' => $displayName]) }}" class="h-8 w-8 rounded-lg object-contain lg:hidden" width="32" height="32">
                        @endif
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold">{{ $displayName }}</p>
                            @if($branch)
                                <p class="truncate text-xs text-[var(--app-muted)]">{{ $branch->name }}</p>
                            @elseif(count($branches) > 1)
                                <details class="relative text-xs">
                                    <summary class="cursor-pointer list-none text-[var(--app-accent)]">{{ __('pos.switch_branch') }}</summary>
                                    <ul class="app-card app-card--flat absolute z-20 mt-1 min-w-[12rem] p-1 shadow-lg">
                                        @foreach($branches as $b)
                                            <li>
                                                <a class="app-nav-link" href="{{ localized_route('admin.pos.terminal', [ $b]) }}">{{ $b->name }}</a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </details>
                            @endif
                        </div>
                    </div>
                    <x-app.locale-switch :current="$locale" class="hidden sm:flex" />
                    <div class="relative">
                        <button type="button" class="tap-action-muted min-h-11 min-w-11 rounded-full px-3" @click="userOpen = !userOpen" :aria-expanded="userOpen">
                            <span class="sr-only">{{ __('pos.user_menu') }}</span>
                            <x-app.icon name="dots" />
                        </button>
                        <div x-show="userOpen" x-cloak @click.outside="userOpen = false" class="app-card app-card--flat absolute end-0 z-30 mt-1 min-w-[11rem] p-1 shadow-xl">
                            <x-app.locale-switch :current="$locale" class="flex p-2 sm:hidden" />
                            @if ($tenant->hasThemedStorefront())
                                <a href="{{ localized_route('cafe.home') }}" target="_blank" rel="noopener" class="app-nav-link">
                                    <x-app.icon name="external" class="h-4 w-4" /> {{ __('pos.view_storefront') }}
                                </a>
                            @endif
                            <form method="POST" action="{{ route('admin.logout') }}">
                                @csrf
                                <button type="submit" class="app-nav-link w-full">{{ __('pos.logout') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <main class="app-content">
                {{ $slot }}
            </main>

            @if($branchParam)
                <nav class="app-tabbar" aria-label="{{ __('pos.mobile_nav') }}">
                    <a href="{{ localized_route('admin.pos.terminal', [ $branchParam]) }}" class="app-tab @if($active === 'pos') app-tab--active @endif">
                        <x-app.icon name="cart" />
                        {{ __('pos.nav_pos') }}
                    </a>
                    <a href="{{ localized_route('admin.pos.history', [ $branchParam]) }}" class="app-tab @if($active === 'orders') app-tab--active @endif">
                        <x-app.icon name="clipboard" />
                        {{ __('pos.nav_orders') }}
                    </a>
                    <a href="{{ localized_route('admin.pos.eod', [ $branchParam]) }}" class="app-tab @if($active === 'eod') app-tab--active @endif">
                        <x-app.icon name="clock" />
                        {{ __('pos.nav_eod') }}
                    </a>
                    <a href="{{ localized_route('admin.pos.ops', [ $branchParam]) }}" class="app-tab @if($active === 'ops') app-tab--active @endif">
                        <x-app.icon name="squares" />
                        {{ __('pos.nav_ops') }}
                    </a>
                    <button type="button" class="app-tab @if($active === 'more') app-tab--active @endif" @click="moreOpen = true">
                        <x-app.icon name="dots" />
                        {{ __('pos.nav_more') }}
                    </button>
                </nav>

                <div x-show="moreOpen" x-cloak class="app-sheet-backdrop" @click="moreOpen = false"></div>
                <div x-show="moreOpen" x-cloak class="app-sheet p-4 lg:hidden" role="dialog" aria-label="{{ __('pos.nav_more') }}">
                    <div class="mx-auto mb-3 h-1 w-10 rounded-full bg-[var(--app-border)]"></div>
                    <nav class="space-y-1" aria-label="{{ __('pos.nav_more') }}">
                        @include('partials.admin-nav-groups')
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button type="submit" class="app-nav-link w-full">
                                <x-app.icon name="logout" class="h-5 w-5 shrink-0" />
                                <span class="app-sidebar__label">{{ __('pos.logout') }}</span>
                            </button>
                        </form>
                    </nav>
                </div>
            @else
                <nav class="app-tabbar" aria-label="{{ __('pos.mobile_nav') }}">
                    <a href="{{ localized_route('admin.pos.home') }}" class="app-tab app-tab--active">
                        <x-app.icon name="home" />
                        {{ __('pos.nav_home') }}
                    </a>
                </nav>
            @endif
        </div>
    @else
        <main class="app-content">{{ $slot }}</main>
    @endif

    <x-toast-prime-script />
    @livewireScriptConfig
</body>
</html>
