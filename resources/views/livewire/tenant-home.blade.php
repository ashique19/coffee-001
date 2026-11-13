@php
    use App\Support\Themes\ThemeStorefrontThumbnail;
    $locale = app()->getLocale();
@endphp
<div>
    <section class="fl-hero" aria-labelledby="fl-hero-title">
        <div class="fl-main">
            <p class="fl-kicker">{{ __('invoicing.landing_company_kicker') }}</p>
            <h1 id="fl-hero-title" class="fl-hero__title">{{ __('invoicing.landing_hero_title') }}</h1>
            <p class="fl-hero__lead">{{ __('invoicing.landing_hero_lead') }}</p>
            <p class="fl-hero__purpose">{{ __('invoicing.landing_site_purpose') }}</p>
            <p class="fl-compliance" role="note">
                {{ __('invoicing.foundation_notice') }}
            </p>
        </div>
    </section>

    <section class="fl-section border-b border-[var(--fl-border)]" aria-labelledby="solution-heading">
        <div class="fl-main">
            <h2 id="solution-heading" class="fl-section__title">{{ __('invoicing.landing_solution_heading') }}</h2>
            <p class="fl-section__lead">{{ __('invoicing.landing_solution_summary') }}</p>
        </div>
    </section>

    <section class="fl-section border-b border-[var(--fl-border)] bg-[var(--fl-surface)]" aria-labelledby="offer-heading">
        <div class="fl-main">
            <h2 id="offer-heading" class="fl-section__title">{{ __('invoicing.landing_offer_heading') }}</h2>
            <ul class="fl-capability-grid" role="list">
                <li class="fl-capability">
                    <p class="fl-capability__label">{{ __('invoicing.landing_cap_invoicing') }}</p>
                    <p class="fl-capability__body">{{ __('invoicing.landing_offer_invoicing') }}</p>
                </li>
                <li class="fl-capability">
                    <p class="fl-capability__label">{{ __('invoicing.landing_cap_storefronts') }}</p>
                    <p class="fl-capability__body">{{ __('invoicing.landing_offer_storefronts') }}</p>
                </li>
                <li class="fl-capability">
                    <p class="fl-capability__label">{{ __('invoicing.landing_cap_templates') }}</p>
                    <p class="fl-capability__body">{{ __('invoicing.landing_offer_templates') }}</p>
                </li>
                <li class="fl-capability">
                    <p class="fl-capability__label">{{ __('invoicing.landing_cap_operations') }}</p>
                    <p class="fl-capability__body">{{ __('invoicing.landing_offer_operations') }}</p>
                </li>
            </ul>
        </div>
    </section>

    @if ($tenant)
        <section class="fl-section">
            <div class="fl-main">
                <p class="inline-flex min-h-11 items-center rounded-lg bg-[var(--fl-text)] px-4 text-sm font-semibold text-[var(--fl-bg)]">{{ $tenant->name }}</p>
                <p class="mt-2 text-sm text-[var(--fl-muted)]">{{ __('invoicing.tenant_slug') }}: {{ $tenant->slug }}</p>
            </div>
        </section>
    @else
        <section class="fl-section" aria-labelledby="tenants-heading">
            <div class="fl-main">
                <h2 id="tenants-heading" class="fl-section__title">{{ __('invoicing.landing_templates_heading') }}</h2>
                <p class="fl-section__lead">{{ __('invoicing.landing_templates_lead') }}</p>

                @if ($tenants->isEmpty())
                    <p class="mt-6 text-base text-[var(--fl-muted)]">{{ __('invoicing.no_tenants_yet') }}</p>
                @else
                    <ul class="fl-demo-grid" role="list">
                        @foreach ($tenants as $listed)
                            @php
                                $thumbUrl = ThemeStorefrontThumbnail::urlFor($listed);
                                $thumbAlt = ThemeStorefrontThumbnail::altFor($listed, $locale);
                                $storefrontUrl = $listed->hasThemedStorefront() ? route('cafe.home', $listed) : route('admin.login', $listed);
                            @endphp
                            <li>
                                <article class="fl-demo-card">
                                    <a href="{{ $storefrontUrl }}" class="fl-demo-card__thumb group">
                                        @if ($thumbUrl)
                                            <img
                                                src="{{ $thumbUrl }}"
                                                alt="{{ $thumbAlt }}"
                                                width="640"
                                                height="400"
                                                loading="lazy"
                                                decoding="async"
                                            >
                                        @else
                                            <div class="flex h-full min-h-[10rem] items-center justify-center bg-gradient-to-br from-[var(--fl-border)] to-[var(--fl-muted)]/30 text-4xl font-bold text-[var(--fl-text)]">
                                                {{ mb_substr($listed->displayName($locale), 0, 1) }}
                                            </div>
                                        @endif
                                        <div class="fl-demo-card__caption">
                                            <p class="text-xs font-bold uppercase tracking-widest opacity-80">{{ __('invoicing.landing_demo_badge') }}</p>
                                            <p class="text-lg font-semibold leading-snug">{{ $listed->displayName($locale) }}</p>
                                            <p class="text-sm opacity-90">{{ $listed->slug }}</p>
                                            <p class="mt-1 text-xs font-medium uppercase tracking-wide opacity-85">
                                                {{ ThemeStorefrontThumbnail::themeLabel($listed) }}
                                            </p>
                                        </div>
                                    </a>
                                    <div class="fl-demo-card__actions">
                                        @if ($listed->isCafeTheme())
                                            <a href="{{ route('cafe.home', $listed) }}" class="fl-btn fl-btn--primary">{{ __('invoicing.tenant_visit_storefront') }}</a>
                                            <a href="{{ route('cafe.menu', $listed) }}" class="fl-btn fl-btn--secondary">{{ __('invoicing.tenant_view_menu') }}</a>
                                        @elseif ($listed->isPlumberTheme())
                                            <a href="{{ route('cafe.home', $listed) }}" class="fl-btn fl-btn--primary">{{ __('invoicing.tenant_visit_storefront') }}</a>
                                            <a href="{{ route('plumber.services', $listed) }}" class="fl-btn fl-btn--secondary">{{ __('invoicing.tenant_view_services') }}</a>
                                        @endif
                                        <a href="{{ route('admin.login', $listed) }}" class="fl-btn fl-btn--ghost">{{ __('invoicing.tenant_staff_login') }}</a>
                                    </div>
                                </article>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>
    @endif
</div>
