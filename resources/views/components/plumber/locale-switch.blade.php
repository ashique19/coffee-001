@props(['current', 'menuId' => 'plumber-locale-menu', 'tenant' => null])
@php
    use App\Models\BusinessSetting;
    use App\Support\Storefront\StorefrontLocales;
    use Illuminate\Support\Str;

    $query = request()->query();
    $tenant = $tenant instanceof Tenant
        ? $tenant
        : (request()->route('tenant') instanceof Tenant
            ? request()->route('tenant')
            : null);
    $locales = StorefrontLocales::plumberNavCodes($tenant);

    $labelFor = function (string $code): string {
        $labelKey = 'plumber.locale_'.$code;
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
    {{ $attributes->class(['relative shrink-0']) }}
    x-data="{ localeOpen: false }"
    @keydown.escape.window="localeOpen = false"
>
    <button
        type="button"
        class="plumber-locale-trigger"
        @click="localeOpen = !localeOpen"
        :aria-expanded="localeOpen"
        aria-haspopup="listbox"
        aria-controls="{{ $menuId }}"
    >
        <span class="tabular-nums font-bold">{{ $currentShort }}</span>
        <span class="plumber-locale-trigger__label hidden max-w-[5.5rem] truncate sm:inline">{{ $currentLabel }}</span>
        <svg class="h-4 w-4 shrink-0 opacity-70" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.084l3.71-3.853a.75.75 0 111.08 1.04l-4.24 4.4a.75.75 0 01-1.08 0l-4.24-4.4a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
        </svg>
        <span class="sr-only">{{ __('plumber.locale_switch') }}</span>
    </button>
    <div
        id="{{ $menuId }}"
        x-show="localeOpen"
        x-cloak
        x-transition.origin.top.end
        @click.outside="localeOpen = false"
        class="plumber-locale-menu absolute end-0 top-full z-50 mt-2 min-w-[10.5rem] max-w-[min(100vw-2rem,14rem)] rounded-xl border border-plumber-navy/10 bg-white p-1.5 shadow-lg ring-1 ring-black/5"
        role="listbox"
        aria-label="{{ __('plumber.locale_switch') }}"
    >
        @foreach ($locales as $code)
            @php $label = $labelFor($code); $active = $current === $code; @endphp
            <a href="{{ request()->url().'?'.http_build_query(array_merge($query, ['locale' => $code])) }}"
               class="block rounded-lg px-3 py-2.5 text-sm font-semibold {{ $active ? 'bg-plumber-sky text-plumber-navy' : 'text-plumber-slate hover:bg-plumber-sky/60' }}"
               @if ($active) aria-current="true" @endif
               @click="localeOpen = false">
                {{ $label }}
            </a>
        @endforeach
    </div>
</div>
