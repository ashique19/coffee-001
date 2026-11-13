@props(['tenant', 'content'])
@php
    use App\Support\TenantDisplayName;

    $locale = app()->getLocale();
    $isAr = TenantDisplayName::usesRtlName($locale);
    $name = TenantDisplayName::for($tenant, $locale);
    $year = now()->year;
@endphp
<footer class="plumber-footer">
    <div class="plumber-section flex flex-col gap-6 pb-10 pt-8 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-lg font-bold text-plumber-navy">{{ $name }}</p>
            <p class="mt-1 max-w-md text-sm text-plumber-slate">
                {{ $isAr ? ($content['tagline_ar'] ?? '') : ($content['tagline_en'] ?? '') }}
            </p>
            <p class="mt-2 text-xs text-plumber-slate">{{ __('plumber.privacy_demo_note') }}</p>
            <p class="mt-3 text-xs text-plumber-slate">&copy; {{ $year }} {{ __('plumber.footer_rights') }}</p>
        </div>
        <div class="flex flex-col gap-2 text-sm">
            <a href="{{ route('plumber.services', $tenant) }}" class="font-semibold text-plumber-steel hover:text-plumber-navy">{{ __('plumber.nav_services') }}</a>
            <a href="{{ route('plumber.about', $tenant) }}" class="font-semibold text-plumber-steel hover:text-plumber-navy">{{ __('plumber.nav_about') }}</a>
            <a href="{{ route('plumber.contact', $tenant) }}" class="font-semibold text-plumber-steel hover:text-plumber-navy">{{ __('plumber.nav_contact') }}</a>
            <a href="{{ route('admin.login', $tenant) }}" class="font-semibold text-plumber-accent hover:underline">{{ __('plumber.footer_staff') }}</a>
        </div>
    </div>
</footer>
