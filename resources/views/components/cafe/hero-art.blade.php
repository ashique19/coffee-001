@props(['image' => null, 'alt' => null, 'banner' => false])
@if ($image)
    <figure @class([
        'cafe-hero-banner relative w-full overflow-hidden shadow-lg ring-1 ring-cafe-latte/80',
        'aspect-[4/3] rounded-3xl' => ! $banner,
        'min-h-[14rem] rounded-b-3xl sm:min-h-[18rem] sm:rounded-3xl' => $banner,
    ])>
        <div class="cafe-hero-media absolute inset-0">
            <x-cafe.photo
                :src="$image"
                :alt="$alt ?? __('cafe.hero_image_alt')"
                class="h-full w-full object-cover"
                width="1600"
                height="1067"
                loading="eager"
                fetchpriority="high"
            />
        </div>
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-cafe-espresso/55 via-cafe-espresso/10 to-cafe-cream/10" aria-hidden="true"></div>
        <div class="cafe-hero-steam-anchor pointer-events-none absolute inset-x-[18%] bottom-[32%] h-32 sm:inset-x-[22%] sm:bottom-[34%] sm:h-40">
            <x-cafe.hero-steam />
        </div>
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-tr from-transparent via-white/5 to-white/10 opacity-60 mix-blend-overlay cafe-hero-shine" aria-hidden="true"></div>
    </figure>
@else
    <div class="relative aspect-[4/3] w-full overflow-hidden rounded-3xl bg-gradient-to-br from-cafe-latte via-cafe-cream to-cafe-accent/40 shadow-inner" role="img" aria-label="{{ __('cafe.hero_image_alt') }}">
        <div class="absolute inset-0 opacity-50" style="background-image: radial-gradient(circle at 25% 75%, var(--cafe-caramel) 0%, transparent 55%), radial-gradient(circle at 75% 25%, var(--cafe-mocha) 0%, transparent 50%);"></div>
        <div class="cafe-hero-steam-anchor pointer-events-none absolute inset-x-1/3 bottom-1/2 h-28">
            <x-cafe.hero-steam />
        </div>
        <div class="absolute inset-0 flex items-center justify-center p-8">
            <svg class="h-40 w-40 text-cafe-espresso drop-shadow-md sm:h-52 sm:w-52" viewBox="0 0 120 140" aria-hidden="true">
                <ellipse cx="60" cy="42" rx="38" ry="14" fill="currentColor" opacity=".12"/>
                <path fill="currentColor" d="M35 38c0-12 11-22 25-22s25 10 25 22v4c0 16-10 28-25 28S35 58 35 42v-4Z"/>
                <path fill="var(--cafe-caramel)" d="M42 70h36v8c0 6-5 10-11 10H53c-6 0-11-4-11-10v-8Z"/>
            </svg>
        </div>
    </div>
@endif
