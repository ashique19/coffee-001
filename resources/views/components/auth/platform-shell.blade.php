@props(['title'])
@php
    $locale = app()->getLocale();
    $dir = config("locales.supported.{$locale}.dir", 'ltr');
    $publicUrl = route('foundation.home');
    $contactEmail = (string) config('mail.from.address', 'hello@taskeenlab.test');
    if ($contactEmail === '' || $contactEmail === 'hello@example.com') {
        $contactEmail = 'hello@taskeenlab.test';
    }
    $footerLinks = [
        ['label' => __('auth.footer_public'), 'href' => $publicUrl],
        ['label' => __('auth.footer_contact'), 'href' => 'mailto:'.$contactEmail],
    ];
    $colors = [
        'espresso' => '#2C1810',
        'cream' => '#FAF6F0',
        'caramel' => '#C68B59',
        'latte' => '#E8D4C4',
        'mocha' => '#5C3D2E',
        'accent' => '#D4A574',
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $dir }}" class="scroll-smooth"
      style="
        --cafe-espresso: {{ $colors['espresso'] }};
        --cafe-cream: {{ $colors['cream'] }};
        --cafe-caramel: {{ $colors['caramel'] }};
        --cafe-latte: {{ $colors['latte'] }};
        --cafe-mocha: {{ $colors['mocha'] }};
        --cafe-accent: {{ $colors['accent'] }};
      ">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="color-scheme" content="light dark">
    <title>{{ $title }} — {{ __('platform.title') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-shell auth-shell--platform min-h-dvh font-sans text-cafe-espresso antialiased">
    <x-toast-stack />
    <div class="auth-shell__bg" aria-hidden="true">
        <div class="auth-shell__gradient"></div>
    </div>

    <header class="auth-shell__header" role="banner">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-3 sm:px-6">
            <a href="{{ $publicUrl }}" class="flex min-h-11 min-w-0 items-center gap-2 rounded-full focus-visible:outline focus-visible:outline-2 focus-visible:outline-cafe-accent">
                <x-cafe.logo-mark class="h-9 w-9 text-cafe-cream" aria-hidden="true" />
                <span class="truncate text-sm font-semibold text-cafe-cream sm:text-base">{{ __('platform.login_title') }}</span>
            </a>
            <div class="flex items-center gap-2">
                <a href="{{ $publicUrl }}" class="hidden min-h-11 items-center rounded-full px-3 text-xs font-medium text-cafe-cream/90 underline-offset-4 hover:underline sm:inline-flex">
                    {{ __('auth.back_to_site') }}
                </a>
                <x-cafe.locale-switch :current="$locale" />
            </div>
        </div>
    </header>

    <main class="auth-shell__main flex flex-1 flex-col items-center justify-center px-4 py-8 sm:px-6">
        <div class="auth-glass-card w-full max-w-md p-6 sm:p-8">
            {{ $slot }}
        </div>
        <p class="mt-4 text-center text-xs text-cafe-cream/80 sm:hidden">
            <a href="{{ $publicUrl }}" class="underline underline-offset-2">{{ __('auth.back_to_site') }}</a>
        </p>
    </main>

    <footer class="auth-shell__footer px-4 py-6 text-center text-xs text-cafe-cream/75 sm:px-6" role="contentinfo">
        <p class="font-medium text-cafe-cream">{{ __('platform.title') }} · © {{ date('Y') }}</p>
        <nav class="mt-2 flex flex-wrap items-center justify-center gap-x-4 gap-y-1" aria-label="{{ __('auth.footer_nav') }}">
            @foreach ($footerLinks as $link)
                <a href="{{ $link['href'] }}" class="hover:text-cafe-accent">{{ $link['label'] }}</a>
            @endforeach
        </nav>
    </footer>
</body>
</html>
