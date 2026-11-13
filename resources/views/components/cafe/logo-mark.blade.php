@props(['name' => null])

@php
    $logoLabel = $name
        ? __('cafe.logo_alt', ['name' => $name])
        : __('cafe.logo_alt', ['name' => config('app.name')]);
@endphp

<svg {{ $attributes->merge(['xmlns' => 'http://www.w3.org/2000/svg', 'viewBox' => '0 0 64 64', 'fill' => 'none']) }} role="img" aria-label="{{ $logoLabel }}">
    <circle cx="32" cy="32" r="30" stroke="currentColor" stroke-width="2" opacity="0.25"/>
    <path fill="currentColor" d="M20 26c0-4.4 4.5-8 14-8s14 3.6 14 8v2c0 8-5.2 14-14 14s-14-6-14-14v-2Z" opacity="0.9"/>
    <path fill="currentColor" d="M24 44h16v4c0 2.2-1.8 4-4 4H28c-2.2 0-4-1.8-4-4v-4Z" opacity="0.7"/>
    <path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M44 24c2 1.5 3 3.5 3 6"/>
    <path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M17 30c-1.5 2-2 4-2 6"/>
</svg>
