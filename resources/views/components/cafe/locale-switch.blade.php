@props(['current', 'menuId' => 'cafe-locale-menu-toolbar'])
@php
    use Illuminate\Support\Str;

    use App\Support\EnabledLocales;
    use App\Support\Storefront\StorefrontLocales;
    use App\Models\BusinessSetting;

    $query = request()->query();
    $routeTenant = request()->route('tenant');
    $tenant = $routeTenant instanceof Tenant
        ? $routeTenant
        : (is_string($routeTenant) ? Tenant::query()->where('slug', $routeTenant)->first() : null);
    $locales = $tenant instanceof Tenant
        ? StorefrontLocales::enabledCodesForTenant($tenant)
        : EnabledLocales::codesWithLangFile('cafe');

    $labelFor = function (string $code): string {
        $labelKey = 'cafe.locale_'.$code;
        $label = __($labelKey);
        if ($label === $labelKey) {
            $label = config("locales.supported.{$code}.name", strtoupper($code));
        }

        return $label;
    };

    $currentLabel = $labelFor($current);
    $currentShort = Str::upper($current);
@endphp
<div
    {{ $attributes->class(['cafe-locale-switch relative shrink-0']) }}
    data-cafe-locale-switch
    x-data="{ localeOpen: false }"
    @keydown.escape.window="localeOpen = false"
>
    <button
        type="button"
        class="cafe-locale-trigger lg:hidden"
        @click="localeOpen = !localeOpen"
        :aria-expanded="localeOpen"
        aria-haspopup="listbox"
        aria-controls="{{ $menuId }}"
    >
        <span class="cafe-locale-trigger__code tabular-nums">{{ $currentShort }}</span>
        <span class="cafe-locale-trigger__label max-w-[4.5rem] truncate sm:max-w-[5.5rem]">{{ $currentLabel }}</span>
        <span class="sr-only">{{ __('cafe.locale_switch') }}</span>
    </button>

  <div
        id="{{ $menuId }}"
        x-show="localeOpen"
        x-cloak
        x-transition.origin-top.end
        @click.outside="localeOpen = false"
        class="cafe-locale-menu lg:hidden"
        role="listbox"
        aria-label="{{ __('cafe.locale_switch') }}"
    >
        @foreach ($locales as $code)
            @php
                $label = $labelFor($code);
                $active = $current === $code;
            @endphp
            <a href="{{ request()->url() . '?' . http_build_query(array_merge($query, ['locale' => $code])) }}"
               hreflang="{{ $code }}"
               lang="{{ $code }}"
               dir="{{ config("locales.supported.{$code}.dir", 'ltr') }}"
               role="option"
               class="cafe-locale-menu__item {{ $active ? 'cafe-locale-menu__item--active' : '' }}"
               @if ($active) aria-current="true" @endif
               @click="localeOpen = false">
                <span class="cafe-locale-menu__code">{{ Str::upper($code) }}</span>
                <span class="min-w-0 truncate">{{ $label }}</span>
            </a>
        @endforeach
    </div>

    <nav class="cafe-locale-bar hidden lg:flex" aria-label="{{ __('cafe.locale_switch') }}">
        @foreach ($locales as $code)
            @php
                $label = $labelFor($code);
                $active = $current === $code;
            @endphp
            <a href="{{ request()->url() . '?' . http_build_query(array_merge($query, ['locale' => $code])) }}"
               hreflang="{{ $code }}"
               lang="{{ $code }}"
               dir="{{ config("locales.supported.{$code}.dir", 'ltr') }}"
               class="cafe-locale-bar__link {{ $active ? 'cafe-locale-bar__link--active' : '' }}"
               @if ($active) aria-current="true" @endif>
                {{ $label }}
            </a>
        @endforeach
    </nav>
</div>
