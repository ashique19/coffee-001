@php
    $locale = app()->getLocale();
    $formatted = number_format((float) $amount, 2, '.', '');
@endphp
<span class="inline-flex items-baseline gap-1" dir="ltr">
    @if ($locale === 'ar')
        <span>{{ $formatted }}</span>
        <span class="text-sm font-semibold" aria-hidden="true">ر.س</span>
        <span class="sr-only">{{ __('cafe.sar') }}</span>
    @else
        <span class="text-sm font-semibold" aria-hidden="true">SAR</span>
        <span class="sr-only">{{ __('cafe.sar') }}</span>
        <span>{{ $formatted }}</span>
    @endif
</span>
