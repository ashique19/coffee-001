@props([
    'src',
    'alt' => '',
    'class' => 'h-full w-full object-cover',
    'width' => null,
    'height' => null,
    'loading' => 'lazy',
    'fetchpriority' => null,
])
@if ($src)
    @php
        $webp = str_ends_with($src, '.webp')
            ? $src
            : (preg_replace('/\.jpe?g$/i', '.webp', $src) ?? $src);
        $jpg = str_ends_with($src, '.webp')
            ? (preg_replace('/\.webp$/i', '.jpg', $src) ?? $src)
            : $src;
        $hasWebp = is_file(public_path($webp));
        $hasJpg = is_file(public_path($jpg));
        $priority = $fetchpriority ? ' fetchpriority="'.$fetchpriority.'"' : '';
    @endphp
    @if ($hasWebp && $hasJpg)
        <picture>
            <source type="image/webp" srcset="{{ asset($webp) }}">
            <img
                src="{{ asset($jpg) }}"
                alt="{{ $alt }}"
                @if ($width) width="{{ $width }}" @endif
                @if ($height) height="{{ $height }}" @endif
                {{ $attributes->merge(['class' => $class]) }}
                loading="{{ $loading }}"
                decoding="async"{!! $priority !!}
            >
        </picture>
    @else
        <img
            src="{{ asset($hasWebp ? $webp : $src) }}"
            alt="{{ $alt }}"
            @if ($width) width="{{ $width }}" @endif
            @if ($height) height="{{ $height }}" @endif
            {{ $attributes->merge(['class' => $class]) }}
            loading="{{ $loading }}"
            decoding="async"{!! $priority !!}
        >
    @endif
@endif
