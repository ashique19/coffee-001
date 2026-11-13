<div class="pb-24 lg:pb-8">
    <header class="mb-4">
        <h1 class="app-page-title">{{ __('storefront.title') }}</h1>
        <p class="app-page-lead">{{ __('storefront.subtitle') }}</p>
    </header>

    <section class="app-card mb-4 p-4 text-sm text-[var(--app-muted)]">
        <p>{{ __('storefront.public_links_intro') }}</p>
        <ul class="mt-2 list-inside list-disc space-y-1">
            <li><a href="{{ $publicHomeUrl }}" target="_blank" rel="noopener noreferrer" class="font-medium text-[var(--cafe-accent)] underline">{{ $publicHomeUrl }}</a></li>
            <li><a href="{{ $sitemapUrl }}" target="_blank" rel="noopener noreferrer" class="font-medium text-[var(--cafe-accent)] underline">{{ __('storefront.sitemap_link') }}</a></li>
        </ul>
    </section>

    <form wire:submit="save" class="app-card flex flex-col gap-4 p-4">
        <h2 class="text-base font-semibold text-[var(--app-text)]">{{ __('storefront.analytics_heading') }}</h2>
        <x-form.input
            :label="__('storefront.ga_measurement_id')"
            name="gaMeasurementId"
            wire:model.live.debounce.400ms="gaMeasurementId"
            placeholder="G-XXXXXXXXXX"
            autocomplete="off"
            :hint="__('storefront.ga_hint')"
            wrapperClass="sm:col-span-2"
        />
        <p class="text-xs text-[var(--app-muted)]">{{ __('storefront.privacy_notice') }}</p>

        <h2 class="mt-2 text-base font-semibold text-[var(--app-text)]">{{ __('storefront.search_console_heading') }}</h2>
        <x-form.input
            :label="__('storefront.google_site_verification')"
            name="googleSiteVerification"
            wire:model.blur="googleSiteVerification"
            autocomplete="off"
            :hint="__('storefront.google_site_verification_hint')"
            wrapperClass="sm:col-span-2"
        />
        <x-form.input
            :label="__('storefront.bing_site_verification')"
            name="bingSiteVerification"
            wire:model.blur="bingSiteVerification"
            autocomplete="off"
            :hint="__('storefront.bing_site_verification_hint')"
            wrapperClass="sm:col-span-2"
        />

        <div class="flex justify-end">
            <button type="submit" class="tap-action-primary min-h-11 w-full sm:w-auto" wire:loading.attr="disabled">
                {{ __('storefront.save') }}
            </button>
        </div>
    </form>
</div>
