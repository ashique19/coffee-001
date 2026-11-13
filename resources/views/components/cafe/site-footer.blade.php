@props(['tenant', 'content' => [], 'salesDemo' => false])
@php
    use App\Support\InstagramUrl;
    use App\Support\TenantDisplayName;

    $locale = app()->getLocale();
    $legal = data_get($tenant->settings, 'legal_entity', []);
    $contact = $content['contact'] ?? [];
    $name = TenantDisplayName::for($tenant, $locale);
    $instagramUrl = InstagramUrl::fromHandle($contact['instagram'] ?? null);
@endphp
<footer class="mt-8 border-t border-cafe-mocha/40 bg-cafe-espresso text-cafe-cream" role="contentinfo">
    <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 lg:px-8">
        <div class="grid gap-12 lg:grid-cols-4">
            <div class="lg:col-span-2">
                <p class="text-xs font-bold uppercase tracking-[0.25em] text-cafe-accent">{{ $name }}</p>
                <p class="mt-4 max-w-md text-sm leading-relaxed text-cafe-latte">
                    {{ TenantDisplayName::usesRtlName($locale) ? ($content['tagline_ar'] ?? '') : ($content['tagline_en'] ?? '') }}
                </p>
                <a href="{{ route('cafe.menu', $tenant) }}" class="cafe-pill-cta cafe-pill-cta--primary mt-6 inline-flex">{{ __('cafe.cta_menu') }}</a>
            </div>
            <div>
                <p class="text-sm font-semibold uppercase tracking-widest text-cafe-accent">{{ __('cafe.footer_explore') }}</p>
                <ul class="mt-4 space-y-2 text-sm text-cafe-latte">
                    <li><a href="{{ route('cafe.home', $tenant) }}#about" class="hover:text-cafe-cream">{{ __('cafe.nav_about') }}</a></li>
                    <li><a href="{{ route('cafe.home', $tenant) }}#gallery" class="hover:text-cafe-cream">{{ __('cafe.nav_gallery') }}</a></li>
                    <li><a href="{{ route('cafe.menu', $tenant) }}" class="hover:text-cafe-cream">{{ __('cafe.nav_menu') }}</a></li>
                    <li><a href="{{ route('cafe.home', $tenant) }}#visit" class="hover:text-cafe-cream">{{ __('cafe.nav_visit') }}</a></li>
                </ul>
            </div>
            <div>
                <p class="text-sm font-semibold uppercase tracking-widest text-cafe-accent">{{ __('cafe.section_contact') }}</p>
                <ul class="mt-4 space-y-2 text-sm text-cafe-latte">
                    <li>
                        <span class="font-semibold text-cafe-cream/90">{{ __('cafe.phone_label') }}</span>
                        <a href="tel:{{ preg_replace('/\s+/', '', $contact['phone'] ?? '') }}" class="hover:text-cafe-cream">{{ $contact['phone'] ?? '—' }}</a>
                        <x-cafe.sample-suffix :show="$salesDemo" />
                    </li>
                    <li>
                        <span class="font-semibold text-cafe-cream/90">{{ __('cafe.email_label') }}</span>
                        <a href="mailto:{{ $contact['email'] ?? '' }}" class="hover:text-cafe-cream">{{ $contact['email'] ?? '—' }}</a>
                        <x-cafe.sample-suffix :show="$salesDemo" />
                    </li>
                    @if (! empty($contact['whatsapp']))
                        <li>
                            <a href="https://wa.me/{{ preg_replace('/\D/', '', $contact['whatsapp']) }}" class="hover:text-cafe-cream" rel="noopener noreferrer" target="_blank">{{ $contact['whatsapp'] }}</a>
                        </li>
                    @endif
                    @if ($instagramUrl)
                        <li>
                            <span class="font-semibold text-cafe-cream/90">{{ __('cafe.social_label') }}</span>
                            <a href="{{ $instagramUrl }}" class="hover:text-cafe-cream" rel="noopener noreferrer" target="_blank">{{ $contact['instagram'] }}</a>
                            <x-cafe.sample-suffix :show="$salesDemo" />
                        </li>
                    @endif
                </ul>
            </div>
        </div>
        <div class="mt-12 grid gap-4 border-t border-cafe-mocha pt-8 text-xs text-cafe-latte sm:grid-cols-2">
            <dl class="space-y-1">
                <div>
                    <dt class="inline font-semibold">{{ __('cafe.vat_label') }}</dt>
                    <dd class="inline">{{ $legal['vat_number'] ?? '—' }}</dd>
                    <x-cafe.sample-suffix :show="$salesDemo" />
                </div>
                <div>
                    <dt class="inline font-semibold">{{ __('cafe.cr_label') }}</dt>
                    <dd class="inline">{{ $legal['cr_number'] ?? '—' }}</dd>
                    <x-cafe.sample-suffix :show="$salesDemo" />
                </div>
            </dl>
            <p class="sm:text-end">© {{ date('Y') }} {{ $name }}. {{ __('cafe.footer_rights') }}</p>
        </div>
    </div>
</footer>
