@props(['branch' => null])

@php
    use App\Models\Branch;
    use App\Models\BusinessSetting;
    use App\Support\AppShellNav;
    use App\Support\TenantDisplayName;

    $tenant = request()->route('tenant');
    if (is_string($tenant)) {
        $tenant = Tenant::query()->where('slug', $tenant)->first();
    }
    $branch = $branch ?? request()->route('branch');
    if ($branch !== null && ! $branch instanceof Branch) {
        $branch = Branch::query()->withoutGlobalScopes()->find($branch);
    }
    $locale = app()->getLocale();
    $dir = config("locales.supported.{$locale}.dir", 'ltr');
    $active = AppShellNav::tenantActiveTab();
    $displayName = $tenant ? TenantDisplayName::for($tenant, $locale) : config('app.name');
    $logoUrl = $tenant ? data_get($tenant->settings, 'theme.logo_url') : null;
    $logoAltKey = ($tenant && $tenant->isPlumberTheme()) ? 'plumber.logo_alt' : 'cafe.logo_alt';
    $colors = $tenant?->themeColors() ?? [];
    $branches = $tenant ? AppShellNav::branchesForSwitcher($tenant) : [];
    app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId(auth('tenant')->user()?->tenant_id);
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
        $documentTitle = $pageTitle ?? __('pos.title');
    @endphp
    <title>{{ $documentTitle }} — {{ $displayName }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
    @livewireStyles
</head>
<body class="app-shell antialiased" x-data="{ sidebarCollapsed: false, moreOpen: false, userOpen: false }" @keydown.escape.window="moreOpen = false; userOpen = false">
    <div class="livewire-loading-bar" aria-hidden="true"></div>
    <x-toast-stack class="has-app-tab-bar" />

    @if($tenant)
        <aside class="app-sidebar" :class="sidebarCollapsed ? 'app-sidebar--collapsed' : ''" aria-label="{{ __('pos.title') }}">
            <div class="flex items-center gap-2 border-b px-3 py-4" style="border-color: var(--app-border)">
                @if ($logoUrl)
                    <img src="{{ asset($logoUrl) }}" alt="{{ __($logoAltKey, ['name' => $displayName]) }}" class="h-9 w-9 shrink-0 rounded-lg object-contain" width="36" height="36">
                @else
                    <x-cafe.logo-mark class="h-9 w-9 shrink-0 text-[var(--app-accent)]" :name="$displayName" />
                @endif
                <span class="app-sidebar__label truncate text-sm font-semibold">{{ $displayName }}</span>
            </div>
            <nav class="flex flex-1 flex-col gap-1 p-2">
                @php
                    $branchParam = $branch ?? ($branches[0] ?? null);
                @endphp
                @can('reports.view')
                    <a href="{{ route('admin.dashboard', $tenant) }}" @class(['app-nav-link', 'app-nav-link--active' => $active === 'dashboard'])>
                        <x-app.icon name="home" class="h-5 w-5 shrink-0" />
                        <span class="app-sidebar__label">{{ __('dashboard.nav') }}</span>
                    </a>
                @endcan
                <a href="{{ route('admin.pos.home', $tenant) }}" @class(['app-nav-link', 'app-nav-link--active' => $active === 'pos'])>
                    <x-app.icon name="chart" class="h-5 w-5 shrink-0" />
                    <span class="app-sidebar__label">{{ __('pos.nav_pos') }}</span>
                </a>
                @if($branchParam)
                    <a href="{{ route('admin.pos.history', [$tenant, $branchParam]) }}" @class(['app-nav-link', 'app-nav-link--active' => $active === 'orders'])>
                        <x-app.icon name="clipboard" class="h-5 w-5 shrink-0" />
                        <span class="app-sidebar__label">{{ __('pos.nav_orders') }}</span>
                    </a>
                    <a href="{{ route('admin.pos.eod', [$tenant, $branchParam]) }}" @class(['app-nav-link', 'app-nav-link--active' => $active === 'eod'])>
                        <x-app.icon name="clock" class="h-5 w-5 shrink-0" />
                        <span class="app-sidebar__label">{{ __('pos.nav_eod') }}</span>
                    </a>
                    <a href="{{ route('admin.pos.ops', [$tenant, $branchParam]) }}" @class(['app-nav-link', 'app-nav-link--active' => $active === 'ops'])>
                        <x-app.icon name="chart" class="h-5 w-5 shrink-0" />
                        <span class="app-sidebar__label">{{ __('pos.nav_ops') }}</span>
                    </a>
                @endif
                @can('business.manage')
                    <a href="{{ route('admin.business', $tenant) }}" @class(['app-nav-link', 'app-nav-link--active' => $active === 'business'])>
                        <x-app.icon name="paint" class="h-5 w-5 shrink-0" />
                        <span class="app-sidebar__label">{{ __('business.nav') }}</span>
                    </a>
                @endcan
                @can('staff.manage')
                    <a href="{{ route('admin.team', $tenant) }}" @class(['app-nav-link', 'app-nav-link--active' => $active === 'team'])>
                        <x-app.icon name="clipboard" class="h-5 w-5 shrink-0" />
                        <span class="app-sidebar__label">{{ __('operations.nav') }}</span>
                    </a>
                @endcan
                @can('inventory.view')
                    <a href="{{ route('admin.catalog', $tenant) }}" @class(['app-nav-link', 'app-nav-link--active' => $active === 'catalog'])>
                        <x-app.icon name="paint" class="h-5 w-5 shrink-0" />
                        <span class="app-sidebar__label">{{ __('catalog.nav') }}</span>
                    </a>
                    <a href="{{ route('admin.inventory', $tenant) }}" @class(['app-nav-link', 'app-nav-link--active' => $active === 'inventory'])>
                        <x-app.icon name="shield" class="h-5 w-5 shrink-0" />
                        <span class="app-sidebar__label">{{ __('inventory.nav') }}</span>
                    </a>
                @endcan
                @can('reports.view')
                    <a href="{{ route('admin.reports', $tenant) }}" @class(['app-nav-link', 'app-nav-link--active' => $active === 'analytics'])>
                        <x-app.icon name="chart" class="h-5 w-5 shrink-0" />
                        <span class="app-sidebar__label">{{ __('reports.nav') }}</span>
                    </a>
                    @if ($tenant->isCafeTheme())
                        <a href="{{ route('admin.storefront', $tenant) }}" @class(['app-nav-link', 'app-nav-link--active' => $active === 'storefront'])>
                            <x-app.icon name="external" class="h-5 w-5 shrink-0" />
                            <span class="app-sidebar__label">{{ __('storefront.nav') }}</span>
                        </a>
                    @elseif ($tenant->hasThemedStorefront())
                        <a href="{{ route('cafe.home', $tenant) }}" target="_blank" rel="noopener" class="app-nav-link">
                            <x-app.icon name="external" class="h-5 w-5 shrink-0" />
                            <span class="app-sidebar__label">{{ __('pos.view_storefront') }}</span>
                        </a>
                    @endif
                    <a href="{{ route('admin.customers', $tenant) }}" @class(['app-nav-link', 'app-nav-link--active' => $active === 'customers'])>
                        <x-app.icon name="users" class="h-5 w-5 shrink-0" />
                        <span class="app-sidebar__label">{{ __('customers.nav') }}</span>
                    </a>
                @endcan
                @if (auth('tenant')->user()?->can('invoices.issue') || auth('tenant')->user()?->can('reports.view'))
                    <a href="{{ route('admin.invoices', $tenant) }}" @class(['app-nav-link', 'app-nav-link--active' => $active === 'invoices'])>
                        <x-app.icon name="clipboard" class="h-5 w-5 shrink-0" />
                        <span class="app-sidebar__label">{{ __('invoices.nav') }}</span>
                    </a>
                    @can('invoices.issue')
                        @php $altInvoiceMode = $tenant->alternateInvoiceDocumentType()->value; @endphp
                        <a href="{{ route('admin.invoices.issue', [$tenant, $altInvoiceMode]) }}" @class(['app-nav-link', 'app-nav-link--active' => $active === 'invoices-issue'])>
                            <x-app.icon name="clipboard" class="h-5 w-5 shrink-0" />
                            <span class="app-sidebar__label">{{ __('invoices.nav_issue_'.$altInvoiceMode) }}</span>
                        </a>
                    @endcan
                @endif
                @can('zatca.manage')
                    <a href="{{ route('tenant.zatca', $tenant) }}" @class(['app-nav-link', 'app-nav-link--active' => $active === 'more'])>
                        <x-app.icon name="shield" class="h-5 w-5 shrink-0" />
                        <span class="app-sidebar__label">{{ __('zatca_admin.title') }}</span>
                    </a>
                @endcan
            </nav>
            <div class="border-t p-2" style="border-color: var(--app-border)">
                <button type="button" class="app-nav-link w-full" @click="sidebarCollapsed = !sidebarCollapsed">
                    <x-app.icon name="menu" class="h-5 w-5 shrink-0" />
                    <span class="app-sidebar__label">{{ __('pos.collapse_sidebar') }}</span>
                </button>
                <form method="POST" action="{{ route('admin.logout', $tenant) }}" class="mt-1">
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
                                                <a class="app-nav-link" href="{{ route('admin.pos.terminal', [$tenant, $b]) }}">{{ $b->name }}</a>
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
                                <a href="{{ route('cafe.home', $tenant) }}" target="_blank" rel="noopener" class="app-nav-link">
                                    <x-app.icon name="external" class="h-4 w-4" /> {{ __('pos.view_storefront') }}
                                </a>
                            @endif
                            <form method="POST" action="{{ route('admin.logout', $tenant) }}">
                                @csrf
                                <button type="submit" class="app-nav-link w-full">{{ __('pos.logout') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            @if (($zatcaPendingReportingCount ?? 0) > 0)
                <div class="border-b border-amber-400/40 bg-amber-50 px-3 py-2 text-sm text-amber-950 sm:px-5" role="status">
                    {{ __('zatca_reconcile.reminder_banner', ['count' => $zatcaPendingReportingCount]) }}
                    <a class="font-semibold underline" href="{{ route('admin.invoices.reconcile', $tenant) }}">{{ __('zatca_reconcile.nav') }}</a>
                </div>
            @endif
            <main class="app-content">
                {{ $slot }}
            </main>

            @if($branchParam)
                <nav class="app-tabbar" aria-label="{{ __('pos.mobile_nav') }}">
                    <a href="{{ route('admin.pos.terminal', [$tenant, $branchParam]) }}" class="app-tab @if($active === 'pos') app-tab--active @endif">
                        <x-app.icon name="cart" />
                        {{ __('pos.nav_pos') }}
                    </a>
                    <a href="{{ route('admin.pos.history', [$tenant, $branchParam]) }}" class="app-tab @if($active === 'orders') app-tab--active @endif">
                        <x-app.icon name="clipboard" />
                        {{ __('pos.nav_orders') }}
                    </a>
                    <a href="{{ route('admin.pos.eod', [$tenant, $branchParam]) }}" class="app-tab @if($active === 'eod') app-tab--active @endif">
                        <x-app.icon name="clock" />
                        {{ __('pos.nav_eod') }}
                    </a>
                    <a href="{{ route('admin.pos.ops', [$tenant, $branchParam]) }}" class="app-tab @if($active === 'ops') app-tab--active @endif">
                        <x-app.icon name="chart" />
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
                    <ul class="space-y-1">
                        <li><a class="app-nav-link" href="{{ route('admin.pos.home', $tenant) }}">{{ __('pos.nav_home') }}</a></li>
                        @can('business.manage')
                            <li><a class="app-nav-link" href="{{ route('admin.business', $tenant) }}">{{ __('business.nav') }}</a></li>
                        @endcan
                        @can('staff.manage')
                            <li><a class="app-nav-link" href="{{ route('admin.team', $tenant) }}">{{ __('operations.nav') }}</a></li>
                        @endcan
                        @can('inventory.view')
                            <li><a class="app-nav-link" href="{{ route('admin.catalog', $tenant) }}">{{ __('catalog.nav') }}</a></li>
                            <li><a class="app-nav-link" href="{{ route('admin.inventory', $tenant) }}">{{ __('inventory.nav') }}</a></li>
                        @endcan
                        @can('reports.view')
                            <li><a class="app-nav-link" href="{{ route('admin.dashboard', $tenant) }}">{{ __('dashboard.nav') }}</a></li>
                            <li><a class="app-nav-link" href="{{ route('admin.reports', $tenant) }}">{{ __('reports.nav') }}</a></li>
                            <li><a class="app-nav-link" href="{{ route('admin.customers', $tenant) }}">{{ __('customers.nav') }}</a></li>
                        @endcan
                        @if (auth('tenant')->user()?->can('invoices.issue') || auth('tenant')->user()?->can('reports.view'))
                            <li><a class="app-nav-link" href="{{ route('admin.invoices', $tenant) }}">{{ __('invoices.nav') }}</a></li>
                        @endif
                        @can('invoices.issue')
                            @php $altInvoiceMode = $tenant->alternateInvoiceDocumentType()->value; @endphp
                            <li><a class="app-nav-link" href="{{ route('admin.invoices.issue', [$tenant, $altInvoiceMode]) }}">{{ __('invoices.nav_issue_'.$altInvoiceMode) }}</a></li>
                        @endcan
                        @can('zatca.manage')
                            <li><a class="app-nav-link" href="{{ route('tenant.zatca', $tenant) }}">{{ __('zatca_admin.title') }}</a></li>
                        @endcan
                        <li>
                            <form method="POST" action="{{ route('admin.logout', $tenant) }}">
                                @csrf
                                <button type="submit" class="app-nav-link w-full">{{ __('pos.logout') }}</button>
                            </form>
                        </li>
                    </ul>
                </div>
            @else
                <nav class="app-tabbar" aria-label="{{ __('pos.mobile_nav') }}">
                    <a href="{{ route('admin.pos.home', $tenant) }}" class="app-tab app-tab--active">
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
