<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locale::direction() }}">
<head>
    @include('partials._head')
    @include('partials._seo-meta')
    @include('partials._custom-code-head')
    @include('theme-modern_ecommerce::partials._theme-tokens')
</head>
<body class="bg-white text-gray-900 antialiased">

@php
    // Hero slides: the rows from Page Settings → Home (this theme's theme.json), or
    // the sample slides below until there is one.
    $heroSlides = collect(theme_rows('hero_slides', 'title'))
        ->map(fn (array $row) => [
            'image' => $row['image'] ?? '',
            'badge' => $row['badge'] ?? '',
            'title' => $row['title'] ?? '',
            'subtitle' => $row['subtitle'] ?? '',
            'button' => ($row['button'] ?? '') !== '' ? $row['button'] : __('Shop now'),
            'link' => \App\Support\HeroSlides::safeUrl($row['link'] ?? '') ?: route('shop'),
        ])
        ->values()
        ->all();

    if ($heroSlides === []) {
        $heroSlides = array_map(fn (array $slide) => $slide + ['link' => route('shop')], [
            ['image' => 'https://images.unsplash.com/photo-1509631179647-0177331693ae?q=80&w=2070&auto=format&fit=crop', 'badge' => 'NEW ARRIVALS', 'title' => 'LEATHER BACKPACK', 'subtitle' => 'LEATHER BAG', 'button' => 'SHOP NOW'],
            ['image' => 'https://images.unsplash.com/photo-1549298916-b41d501d3772?q=80&w=2070&auto=format&fit=crop', 'badge' => 'SPECIAL', 'title' => 'PREMIUM LOAFER', 'subtitle' => 'FOOTWEAR', 'button' => 'SHOP NOW'],
            ['image' => 'https://images.unsplash.com/photo-1560769629-975ec94e6a86?q=80&w=2070&auto=format&fit=crop', 'badge' => 'NEW ARRIVALS', 'title' => 'URBAN SNEAKERS', 'subtitle' => 'SNEAKERS', 'button' => 'DISCOVER'],
        ]);
    }

    // Promo tiles: the rows from Page Settings → Home, else the home promo banner
    // images from Settings (how this theme was fed before the editor existed).
    $promos = collect(theme_rows('promos', 'alt'))
        ->filter(fn (array $row) => filled($row['image'] ?? null))
        ->map(fn (array $row) => [
            'image' => $row['image'],
            'alt' => $row['alt'] ?? '',
            'link' => \App\Support\HeroSlides::safeUrl($row['link'] ?? '') ?: route('shop'),
        ])
        ->values()
        ->all();

    if ($promos === []) {
        $promos = collect([
            \App\Models\Setting::get('home_promo_banner_1'),
            \App\Models\Setting::get('home_promo_banner_2'),
            \App\Models\Setting::get('home_promo_banner_3'),
        ])->filter()->map(fn ($image) => ['image' => $image, 'link' => route('shop')])->values()->all();
    }

    $with = ['categories.page', 'brand', 'tags', 'page', 'flashDeals'];

    $newArrivals = \App\Models\Product::active()->with($with)->withSoldQuantity()->latest()->limit(8)->get();

    $bestSellers = \App\Models\Product::active()->with($with)->withSoldQuantity()
        ->whereHas('orderItems', fn ($q) => $q->whereHas('order', fn ($o) => $o->where('status', '!=', 'cancelled')))
        ->orderByDesc('sold_quantity')->limit(8)->get();

    $homeCategories = \App\Models\ProductCategory::active()
        ->where('featured', true)
        ->with(['page', 'products' => fn ($q) => $q->active()->with($with)->withSoldQuantity()->orderBy('sort_order')->limit(8)])
        ->orderBy('sort_order')
        ->get()
        ->filter(fn ($category) => $category->page !== null && $category->products->isNotEmpty())
        ->take(4);
@endphp

@include('theme-modern_ecommerce::partials._header')

<main>
    {{-- Hero slider --}}
    <section class="group relative flex h-[440px] items-center overflow-hidden bg-[#f8f9fa] px-6 sm:h-[520px] lg:h-[600px] lg:px-16"
        x-data="{ active: 0, count: {{ count($heroSlides) }}, timer: null,
            go(i) { this.active = (i + this.count) % this.count },
            start() { clearInterval(this.timer); this.timer = setInterval(() => this.go(this.active + 1), 6000) } }"
        x-init="start()" @mouseenter="clearInterval(timer)" @mouseleave="start()">
        <div class="absolute inset-0">
            @foreach ($heroSlides as $i => $slide)
                <div class="absolute inset-0 bg-cover bg-center transition-opacity duration-1000"
                    :class="active === {{ $i }} ? 'opacity-100' : 'opacity-0'"
                    style="background-image: url('{{ $slide['image'] }}')"></div>
            @endforeach
            <div class="absolute inset-0 bg-gradient-to-r from-white/90 via-white/60 to-transparent"></div>
        </div>

        <div class="relative z-10 flex-1">
            @foreach ($heroSlides as $i => $slide)
                <div x-show="active === {{ $i }}" @if ($i > 0) x-cloak @endif>
                    <div class="mb-6 inline-block bg-[#1a1a1a] px-5 py-2 text-[0.75rem] font-bold tracking-[0.2em] text-white">{{ $slide['badge'] }}</div>
                    <h1 class="mb-5 text-4xl font-black leading-[1.05] tracking-tight text-[#1a1a1a] sm:text-5xl lg:text-[4.25rem]">
                        {{ $slide['title'] }}<br><span style="color: var(--me-accent)">{{ $slide['subtitle'] }}</span>
                    </h1>
                    <p class="mb-9 text-sm font-bold uppercase tracking-[0.35em] text-gray-500">{{ __('Exclusive design') }}</p>
                    <a href="{{ $slide['link'] }}" class="inline-block bg-[#1a1a1a] px-10 py-4 text-sm font-bold uppercase tracking-widest text-white transition-all duration-300 hover:-translate-y-1 hover:bg-[var(--me-accent)]">{{ $slide['button'] }}</a>
                </div>
            @endforeach
        </div>

        <button type="button" @click="go(active - 1)" aria-label="{{ __('Previous slide') }}"
            class="pointer-events-none absolute left-4 top-1/2 z-20 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/80 text-[#1a1a1a] opacity-0 shadow-md backdrop-blur transition-all duration-300 hover:bg-[var(--me-accent)] hover:text-white group-hover:pointer-events-auto group-hover:opacity-100 sm:left-6">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6" /></svg>
        </button>
        <button type="button" @click="go(active + 1)" aria-label="{{ __('Next slide') }}"
            class="pointer-events-none absolute right-4 top-1/2 z-20 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/80 text-[#1a1a1a] opacity-0 shadow-md backdrop-blur transition-all duration-300 hover:bg-[var(--me-accent)] hover:text-white group-hover:pointer-events-auto group-hover:opacity-100 sm:right-6">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6" /></svg>
        </button>

        <div class="absolute bottom-8 left-1/2 z-20 flex -translate-x-1/2 items-center gap-2.5">
            @foreach ($heroSlides as $i => $slide)
                <button type="button" @click="go({{ $i }})" aria-label="{{ __('Go to slide') }} {{ $i + 1 }}"
                    class="h-2.5 rounded-full transition-all duration-300"
                    :class="active === {{ $i }} ? 'w-8 bg-[var(--me-accent)]' : 'w-2.5 bg-gray-400/50 hover:bg-gray-500/70'"></button>
            @endforeach
        </div>
    </section>

    {{-- Promo tiles (Admin → home promo banners) --}}
    @if ($promos !== [])
        <section class="mx-auto max-w-[1400px] px-4 py-12 sm:px-8">
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3 lg:gap-7">
                @foreach ($promos as $promo)
                    <a href="{{ $promo['link'] }}" class="group/promo relative block aspect-[16/9] overflow-hidden rounded-2xl transition-all duration-500 hover:-translate-y-2 hover:shadow-2xl">
                        <img src="{{ $promo['image'] }}" alt="{{ ($promo['alt'] ?? '') !== '' ? $promo['alt'] : __('Promotion').' '.$loop->iteration }}" loading="lazy"
                            class="h-full w-full object-cover transition-transform duration-700 group-hover/promo:scale-110">
                        <span class="absolute inset-0 bg-black/5 transition-colors duration-300 group-hover/promo:bg-black/0"></span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Shop by category --}}
    @if ($homeCategories->isNotEmpty())
        <section class="mx-auto max-w-[1400px] px-4 py-10 sm:px-8 sm:py-12">
            <div class="mb-7 flex flex-wrap items-end justify-between gap-3">
                <h2 class="relative pb-2.5 text-[1.35rem] font-extrabold uppercase tracking-tight text-[#1a1a1a] sm:text-[1.6rem]">
                    {{ __('Shop by category') }}
                    <span class="absolute bottom-0 left-0 h-[3px] w-[50px]" style="background: var(--me-accent)"></span>
                </h2>
                <a href="{{ route('shop') }}" class="group inline-flex items-center gap-1.5 pb-1 text-[0.7rem] font-bold uppercase tracking-widest text-gray-500 transition hover:text-[var(--me-accent)]">
                    {{ __('View all') }}
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="transition group-hover:translate-x-1"><path d="M5 12h14M12 5l7 7-7 7" /></svg>
                </a>
            </div>
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                @foreach ($homeCategories as $category)
                    @php($tile = $category->products->first()?->featured_image)
                    <a href="{{ route('shop', ['category' => $category->slug]) }}"
                        class="group/cat relative flex aspect-[4/5] flex-col justify-end overflow-hidden rounded-2xl bg-[#f1f2f4] p-5 sm:aspect-[4/3] lg:aspect-[3/4]">
                        @if ($tile)
                            <img src="{{ $tile }}" alt="{{ $category->name }}" loading="lazy"
                                class="absolute inset-0 h-full w-full object-cover opacity-90 transition-transform duration-700 group-hover/cat:scale-110">
                        @endif
                        <span class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/10 to-transparent"></span>
                        <span class="relative text-lg font-black uppercase tracking-tight text-white sm:text-xl">{{ $category->name }}</span>
                        <span class="relative mt-1 text-[0.65rem] font-bold uppercase tracking-[0.25em] text-white/70">{{ __('Shop now') }} →</span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    @include('theme-modern_ecommerce::partials._product-section', ['title' => __('New arrival'), 'products' => $newArrivals])
    @include('theme-modern_ecommerce::partials._product-section', ['title' => __('Best selling'), 'products' => $bestSellers])

    @foreach ($homeCategories as $category)
        @include('theme-modern_ecommerce::partials._product-section', [
            'title' => $category->name,
            'products' => $category->products,
            'viewAll' => route('shop', ['category' => $category->slug]),
        ])
    @endforeach

    {{-- Trust strip --}}
    <section class="border-y border-gray-100 bg-[#f8f9fa]">
        <div class="mx-auto grid max-w-[1400px] grid-cols-2 gap-6 px-4 py-10 sm:px-8 lg:grid-cols-4">
            @foreach ([
                ['title' => __('Free shipping'), 'text' => __('On orders over the minimum')],
                ['title' => __('Easy returns'), 'text' => __('7-day exchange policy')],
                ['title' => __('Secure payment'), 'text' => __('Protected checkout')],
                ['title' => __('Support'), 'text' => __('Here to help, every day')],
            ] as $item)
                <div class="flex flex-col gap-1.5">
                    <span class="text-[0.65rem] font-black uppercase tracking-[0.25em] text-[var(--me-accent)]">{{ $item['title'] }}</span>
                    <span class="text-sm font-medium text-gray-600">{{ $item['text'] }}</span>
                </div>
            @endforeach
        </div>
    </section>

    {{-- CMS sections of the "home" page --}}
    @foreach ($sections as $section)
        @continue(blank($section->localizedCards()))
        <section id="{{ $section->name }}" class="mx-auto max-w-[1400px] px-4 py-12 sm:px-8">
            <h2 class="mb-7 text-[1.35rem] font-extrabold uppercase tracking-tight text-[#1a1a1a] sm:text-[1.6rem]">{{ $section->name }}</h2>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($section->localizedCards() as $card)
                    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white transition hover:shadow-xl">
                        @if ($card['image'])
                            <img src="{{ $card['image'] }}" alt="{{ $card['title'] }}" loading="lazy" class="aspect-[4/3] w-full object-cover">
                        @endif
                        <div class="p-4">
                            @if ($card['title'])<h3 class="font-bold text-gray-900" @if ($card['text_color']) style="color: {{ $card['text_color'] }}" @endif>{{ $card['title'] }}</h3>@endif
                            @if ($card['subtitle'])<p class="text-xs font-medium text-gray-400">{{ $card['subtitle'] }}</p>@endif
                            @if ($card['description'])<p class="mt-1.5 line-clamp-2 text-sm text-gray-500">{{ $card['description'] }}</p>@endif
                            @if ($card['button_label'] && $card['button_url'])
                                <a href="{{ \App\Support\HeroSlides::safeUrl($card['button_url']) ?: url('/') }}" class="mt-3 inline-block rounded-full bg-[var(--me-accent)] px-4 py-1.5 text-xs font-bold uppercase tracking-wider text-white">{{ $card['button_label'] }}</a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach
</main>

@include('theme-modern_ecommerce::partials._footer')

@include('frontend.partials._chat-widget')
@include('partials._custom-code-body')
</body>
</html>
