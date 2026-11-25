@php
    $user = auth('tenant')->user();
    $canReports = $user?->can('reports.view');
    $canBusiness = $user?->can('business.manage');
    $canStaff = $user?->can('staff.manage');
    $canInventory = $user?->can('inventory.view');
    $canIssueInvoices = $user?->can('invoices.issue');
    $canInvoicesHub = $canIssueInvoices || $canReports;
    $showStorefront = $canReports && $tenant && ($tenant->isCafeTheme() || $tenant->hasThemedStorefront());
    $showSettings = $canBusiness || $canStaff || $showStorefront;
    $showCatalog = $canInventory || $canReports;
    $showReports = $canReports || $branchParam;
    $altInvoiceMode = $tenant?->defaultInvoiceDocumentType()->value ?? 'simplified';
@endphp

@if ($canReports)
    <a href="{{ localized_route('admin.dashboard') }}" @class(['app-nav-link', 'app-nav-link--active' => $active === 'dashboard'])>
        <x-app.icon name="home" class="h-5 w-5 shrink-0" />
        <span class="app-sidebar__label">{{ __('dashboard.nav') }}</span>
    </a>
@endif

<p class="app-sidebar__group">{{ __('nav.group_sell') }}</p>
<a href="{{ localized_route('admin.pos.home') }}" @class(['app-nav-link', 'app-nav-link--active' => $active === 'pos'])>
    <x-app.icon name="cart" class="h-5 w-5 shrink-0" />
    <span class="app-sidebar__label">{{ __('pos.nav_pos') }}</span>
</a>
@if ($branchParam)
    <a href="{{ localized_route('admin.pos.history', [$branchParam]) }}" @class(['app-nav-link', 'app-nav-link--active' => $active === 'orders'])>
        <x-app.icon name="clipboard" class="h-5 w-5 shrink-0" />
        <span class="app-sidebar__label">{{ __('pos.nav_orders') }}</span>
    </a>
    <a href="{{ localized_route('admin.pos.eod', [$branchParam]) }}" @class(['app-nav-link', 'app-nav-link--active' => $active === 'eod'])>
        <x-app.icon name="clock" class="h-5 w-5 shrink-0" />
        <span class="app-sidebar__label">{{ __('pos.nav_eod') }}</span>
    </a>
@endif

@if ($canInvoicesHub)
    <p class="app-sidebar__group">{{ __('nav.group_invoices') }}</p>
    <a href="{{ localized_route('admin.invoices') }}" @class(['app-nav-link', 'app-nav-link--active' => $active === 'invoices'])>
        <x-app.icon name="document" class="h-5 w-5 shrink-0" />
        <span class="app-sidebar__label">{{ __('invoices.nav') }}</span>
    </a>
    @if ($canIssueInvoices)
        <a href="{{ localized_route('admin.invoices.issue', [$altInvoiceMode]) }}" @class(['app-nav-link', 'app-nav-link--active' => $active === 'invoices-issue'])>
            <x-app.icon name="document-plus" class="h-5 w-5 shrink-0" />
            <span class="app-sidebar__label">{{ __('invoices.nav_issue_'.$altInvoiceMode) }}</span>
        </a>
    @endif
@endif

@if ($showCatalog)
    <p class="app-sidebar__group">{{ __('nav.group_catalog') }}</p>
    @if ($canInventory)
        <a href="{{ localized_route('admin.catalog') }}" @class(['app-nav-link', 'app-nav-link--active' => $active === 'catalog'])>
            <x-app.icon name="cup" class="h-5 w-5 shrink-0" />
            <span class="app-sidebar__label">{{ __('catalog.nav') }}</span>
        </a>
        <a href="{{ localized_route('admin.inventory') }}" @class(['app-nav-link', 'app-nav-link--active' => $active === 'inventory'])>
            <x-app.icon name="cube" class="h-5 w-5 shrink-0" />
            <span class="app-sidebar__label">{{ __('inventory.nav') }}</span>
        </a>
    @endif
    @if ($canReports)
        <a href="{{ localized_route('admin.customers') }}" @class(['app-nav-link', 'app-nav-link--active' => $active === 'customers'])>
            <x-app.icon name="users" class="h-5 w-5 shrink-0" />
            <span class="app-sidebar__label">{{ __('customers.nav') }}</span>
        </a>
    @endif
@endif

@if ($showReports)
    <p class="app-sidebar__group">{{ __('nav.group_reports') }}</p>
    @if ($canReports)
        <a href="{{ localized_route('admin.reports') }}" @class(['app-nav-link', 'app-nav-link--active' => $active === 'analytics'])>
            <x-app.icon name="chart" class="h-5 w-5 shrink-0" />
            <span class="app-sidebar__label">{{ __('reports.nav') }}</span>
        </a>
    @endif
    @if ($branchParam)
        <a href="{{ localized_route('admin.pos.ops', [$branchParam]) }}" @class(['app-nav-link', 'app-nav-link--active' => $active === 'ops'])>
            <x-app.icon name="squares" class="h-5 w-5 shrink-0" />
            <span class="app-sidebar__label">{{ __('pos.nav_ops') }}</span>
        </a>
    @endif
@endif

@if ($showSettings)
    <p class="app-sidebar__group">{{ __('nav.group_settings') }}</p>
    @if ($canBusiness)
        <a href="{{ localized_route('admin.business') }}" @class(['app-nav-link', 'app-nav-link--active' => $active === 'business'])>
            <x-app.icon name="briefcase" class="h-5 w-5 shrink-0" />
            <span class="app-sidebar__label">{{ __('business.nav') }}</span>
        </a>
    @endif
    @if ($canStaff)
        <a href="{{ localized_route('admin.team') }}" @class(['app-nav-link', 'app-nav-link--active' => $active === 'team'])>
            <x-app.icon name="identification" class="h-5 w-5 shrink-0" />
            <span class="app-sidebar__label">{{ __('operations.nav') }}</span>
        </a>
    @endif
    @if ($canReports && $tenant?->isCafeTheme())
        <a href="{{ localized_route('admin.storefront') }}" @class(['app-nav-link', 'app-nav-link--active' => $active === 'storefront'])>
            <x-app.icon name="storefront" class="h-5 w-5 shrink-0" />
            <span class="app-sidebar__label">{{ __('storefront.nav') }}</span>
        </a>
    @elseif ($canReports && $tenant?->hasThemedStorefront())
        <a href="{{ localized_route('cafe.home') }}" target="_blank" rel="noopener" class="app-nav-link">
            <x-app.icon name="external" class="h-5 w-5 shrink-0" />
            <span class="app-sidebar__label">{{ __('pos.view_storefront') }}</span>
        </a>
    @endif
@endif
