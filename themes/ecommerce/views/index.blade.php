<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locale::direction() }}">
<head>
    @include('partials._head')
    @include('partials._seo-meta')
    @include('partials._custom-code-head')
</head>
<body class="bg-page-bg font-storefront text-sf-text antialiased">

@php
    $siteName = \App\Models\Setting::get('site_name', config('app.name'));
    // Hero slider (Theme Settings → Banners): each slide {image, title,
    // description, url}; older image-only data still renders.
    $heroSlides = \Themes\Ecommerce\Support\HeroSlides::forStorefront();
    $promoImage1 = \App\Models\Setting::get('home_promo_banner_1');
    $promoImage2 = \App\Models\Setting::get('home_promo_banner_2');

    // Promo tile links (Theme Settings): a site path or an http(s) URL ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â
    // anything else (blank, javascript:, …) falls back to the Shop page. Read
    // from the ecommerce theme's own theme.json.
    $promoLink = fn (string $key): string => \Themes\Ecommerce\Support\HeroSlides::safeUrl(\App\Support\ThemeSettings::text('ecommerce', $key));

    $featured = \App\Models\Product::active()
        ->featured()
        ->with(['categories.page', 'brand', 'tags', 'page'])
        ->withSoldQuantity()
        ->orderBy('sort_order')
        ->limit(10)
        ->get();

    $homeCategories = \App\Models\ProductCategory::active()
        ->where('featured', true)
        ->with([
            'page',
            'parent',
            'products' => fn ($q) => $q->active()
                ->with(['categories.page', 'brand', 'tags', 'page'])
                ->withSoldQuantity()
                ->orderBy('sort_order')
                ->limit(8),
        ])
        ->withCount(['products' => fn ($q) => $q->active()])
        ->orderBy('sort_order')
        ->get()
        ->filter(fn ($category) => $category->page !== null)
        ->take(20);

    $homeBrands = \App\Models\ProductBrand::active()
        ->orderBy('sort_order')
        ->take(10)
        ->get();

    $newArrivals = \App\Models\Product::active()
        ->with(['categories.page', 'brand', 'tags', 'page'])
        ->withSoldQuantity()
        ->latest()
        ->limit(8)
        ->get();

    // Ranked by total units sold on non-cancelled orders.
    $bestSellers = \App\Models\Product::active()
        ->with(['categories.page', 'brand', 'tags', 'page'])
        ->withSoldQuantity()
        ->whereHas('orderItems', fn ($q) => $q->whereHas('order', fn ($o) => $o->where('status', '!=', 'cancelled')))
        ->orderByDesc('sold_quantity')
        ->limit(8)
        ->get();
@endphp

@include('theme-ecommerce::partials._header')

<main>
    <div class="mx-auto w-full max-w-7xl px-4 sm:px-6">
        {{-- The 3-up hero (main banner + two stacked promos) starts at xl, not lg.
             At lg (1024px) the main banner was still two columns of a 3-column grid
             — about 590px wide and 440px tall — so a 1200x440 crop was being pushed
             through a portrait-shaped box and lost most of its middle. Between lg
             and xl the promos sit side by side under a full-width banner instead,
             which is what the stacking order already did below lg. --}}
        @php
            // A promo tile with no uploaded image is left out rather than drawn as
            // an empty gradient block; with neither, the main banner takes the full
            // width. One tile alone fills the whole side column.
            $promos = array_values(array_filter([
                ['image' => $promoImage1, 'label' => __('New arrivals'), 'url' => $promoLink('theme_ecommerce_promo_1_link')],
                ['image' => $promoImage2, 'label' => __('Best deals'), 'url' => $promoLink('theme_ecommerce_promo_2_link')],
            ], fn ($promo) => filled($promo['image'])));
        @endphp
        <section class="mt-6 grid w-full grid-cols-1 gap-4 xl:h-[440px] {{ $promos !== [] ? 'xl:grid-cols-3' : '' }}">
            <a href="{{ $heroSlides[0]['url'] ?? route('shop') }}"
                @if (count($heroSlides) > 1)
                    :href="links[active]"
                    x-data="{
                        active: 0,
                        count: {{ count($heroSlides) }},
                        links: @js(array_column($heroSlides, 'url')),
                        timer: null,
                        start() { this.stop(); this.timer = setInterval(() => this.go(this.active + 1), 5000); },
                        stop() { clearInterval(this.timer); },
                        go(i) { this.active = (i + this.count) % this.count; },
                        startX: null, moved: false,
                        down(e) { this.startX = e.clientX; this.moved = false; },
                        up(e) {
                            if (this.startX === null) return;
                            const dx = e.clientX - this.startX;
                            this.startX = null;
                            if (Math.abs(dx) > 40) { this.moved = true; this.go(this.active + (dx < 0 ? 1 : -1)); }
                        },
                    }"
                    x-init="start()"
                    @mouseenter="stop()" @mouseleave="start(); startX = null"
                    @pointerdown="down($event)" @pointerup="up($event)" @pointercancel="startX = null"
                    @dragstart.prevent
                    @click.capture="if (moved) { $event.preventDefault(); $event.stopPropagation(); moved = false; }"
                @endif
                class="group/hero relative block aspect-[1200/440] touch-pan-y select-none cursor-grab overflow-hidden rounded-card xl:aspect-auto xl:h-full {{ $promos !== [] ? 'xl:col-span-2' : '' }}">
                @if ($heroSlides !== [])
                    @foreach ($heroSlides as $i => $slide)
                        @php $hasText = filled($slide['title']) || filled($slide['description']); @endphp
                        <div
                            @if (count($heroSlides) > 1)
                                :class="active === {{ $i }} ? '!opacity-100 z-[1]' : 'opacity-0'"
                                :aria-hidden="active !== {{ $i }}"
                            @endif
                            class="absolute inset-0 transition-opacity duration-1000 ease-out {{ $i === 0 ? '' : 'opacity-0' }}">
                            <img src="{{ $slide['image'] }}" alt="{{ $slide['title'] ?: $siteName }}" @if ($i > 0) loading="lazy" @else fetchpriority="high" @endif
                                width="1200" height="440"
                                @if (count($heroSlides) > 1)
                                    :class="active === {{ $i }} ? 'scale-100' : 'scale-105'"
                                @endif
                                class="h-full w-full object-cover transition-transform duration-[1600ms] ease-out">

                            @if ($hasText)
                                {{-- Text only gets a shade behind it when there's text to read. --}}
                                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/15 to-transparent"></div>
                                <div class="absolute inset-x-0 bottom-0 p-3 pb-7 sm:p-6 sm:pb-12 md:p-10 md:pb-14">
                                    @if (filled($slide['title']))
                                        <h2 class="max-w-xl text-base font-bold leading-tight text-white drop-shadow sm:text-2xl md:text-4xl">{{ $slide['title'] }}</h2>
                                    @endif
                                    @if (filled($slide['description']))
                                        <p class="mt-1 line-clamp-2 max-w-lg text-xs text-white/90 drop-shadow sm:mt-2 sm:text-sm md:text-base">{{ $slide['description'] }}</p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach

                    @if (count($heroSlides) > 1)
                        <div class="absolute bottom-4 right-6 z-10 flex items-center gap-1.5 md:bottom-8 md:right-8">
                            @foreach ($heroSlides as $i => $slide)
                                <button type="button" @click.prevent="go({{ $i }})" aria-label="{{ __('Slide :n', ['n' => $i + 1]) }}"
                                    :class="active === {{ $i }} ? '!w-6 !bg-white' : 'hover:bg-white/80'"
                                    class="h-2 w-2 rounded-full bg-white/50 transition-all duration-300"></button>
                            @endforeach
                        </div>
                    @endif
                @else
                    {{-- No slides configured: show the site name as visible text so
                         the first screen has a real Largest Contentful Paint
                         element (a bare gradient gives Lighthouse NO_LCP, and
                         PageSpeed then reports no Performance score at all). --}}
                    <div class="flex h-full w-full items-center bg-gradient-to-br from-brand to-emerald-800 px-8 md:px-12">
                        <h1 class="max-w-xl text-3xl font-bold leading-tight text-white md:text-5xl">{{ $siteName }}</h1>
                    </div>
                @endif
                {{-- The hero is image-only; this keeps the page's main heading for
                     search engines and screen readers. --}}
                @if ($heroSlides !== [])
                    <h1 class="sr-only">{{ $siteName }}</h1>
                @endif
            </a>

            @if ($promos !== [])
                <div class="grid gap-4 xl:flex xl:flex-col {{ count($promos) > 1 ? 'grid-cols-2' : 'grid-cols-1' }}">
                    @foreach ($promos as $promo)
                        <a href="{{ $promo['url'] }}" class="group/promo relative block h-32 overflow-hidden rounded-card sm:h-44 lg:h-56 {{ count($promos) > 1 ? 'xl:h-1/2' : 'xl:h-full' }}">
                            <img src="{{ $promo['image'] }}" alt="{{ $promo['label'] }}" loading="lazy" decoding="async" width="600" height="400" class="h-full w-full object-cover transition duration-500 group-hover/promo:scale-105">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    @if ($featured->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 pt-8 sm:px-6">
            @include('theme-ecommerce::partials._section-heading', [
                'title' => __('Featured products'),
                'subtitle' => __('Hand-picked products for you'),
            ])
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 md:gap-4 xl:grid-cols-5">
                @foreach ($featured as $product)
                    @include('theme-ecommerce::partials._product-card', ['product' => $product])
                @endforeach
            </div>
        </section>
    @endif

    @if ($bestSellers->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 pt-10 sm:px-6">
            @include('theme-ecommerce::partials._section-heading', [
                'title' => __('Best sellers'),
                'subtitle' => __('Most loved by our customers'),
            ])
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 md:gap-4 xl:grid-cols-5">
                @foreach ($bestSellers as $product)
                    @include('theme-ecommerce::partials._product-card', ['product' => $product])
                @endforeach
            </div>
        </section>
    @endif

    @if ($newArrivals->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 pt-10 sm:px-6">
            <div x-data="{
                    overflow: false,
                    update() { this.overflow = this.$refs.track.scrollWidth > this.$refs.track.clientWidth + 1; },
                    slide(direction) {
                        const track = this.$refs.track;
                        const card = track.firstElementChild;
                        if (!card) return;
                        const gap = parseFloat(getComputedStyle(track).columnGap) || 0;
                        track.scrollBy({ left: (card.getBoundingClientRect().width + gap) * 2 * direction, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
                    },
                }"
                x-init="$nextTick(() => { update(); new ResizeObserver(() => update()).observe($refs.track); })">
                <div class="mb-6 flex items-center justify-between border-b border-zinc-200 pb-4">
                    <div>
                        <h2 class="text-xl font-bold tracking-tight text-sf-text md:text-2xl">{{ __('New arrivals') }}</h2>
                        <p class="mt-1 text-sm text-gray-600 md:text-base">{{ __('Just added to the store') }}</p>
                    </div>
                    <div class="flex gap-2" x-cloak x-show="overflow">
                        <button type="button" @click="slide(-1)" aria-label="{{ __('Previous products') }}"
                            class="flex h-9 w-9 items-center justify-center rounded-full bg-white text-brand shadow-sm transition hover:bg-brand hover:text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.79 15.71a1 1 0 0 1-1.42 0l-5-5a1 1 0 0 1 0-1.42l5-5a1 1 0 1 1 1.42 1.42L8.5 10l4.29 4.29a1 1 0 0 1 0 1.42Z" clip-rule="evenodd"/></svg>
                        </button>
                        <button type="button" @click="slide(1)" aria-label="{{ __('Next products') }}"
                            class="flex h-9 w-9 items-center justify-center rounded-full bg-white text-brand shadow-sm transition hover:bg-brand hover:text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.21 4.29a1 1 0 0 1 1.42 0l5 5a1 1 0 0 1 0 1.42l-5 5a1 1 0 1 1-1.42-1.42L11.5 10 7.21 5.71a1 1 0 0 1 0-1.42Z" clip-rule="evenodd"/></svg>
                        </button>
                    </div>
                </div>
                <div x-ref="track" class="no-scrollbar grid grid-flow-col auto-cols-[calc(50%_-_6px)] gap-3 overflow-x-auto overscroll-x-contain scroll-smooth pb-1 sm:auto-cols-[calc(33.333333%_-_8px)] md:auto-cols-[calc(25%_-_12px)] md:gap-4 xl:auto-cols-[calc(20%_-_12.8px)]">
                    @foreach ($newArrivals as $product)
                        @include('theme-ecommerce::partials._product-card', ['product' => $product])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($homeCategories->isNotEmpty())
        @foreach ($homeCategories as $category)
            @continue($category->products->isEmpty())
            <section class="mx-auto w-full min-w-0 max-w-7xl px-4 pt-10 sm:px-6" x-data="{
                overflow: false,
                update() { this.overflow = this.$refs.track.scrollWidth > this.$refs.track.clientWidth + 1; },
                slide(direction) {
                    const track = this.$refs.track;
                    const card = track.firstElementChild;
                    if (!card) return;
                    const gap = parseFloat(getComputedStyle(track).columnGap) || 0;
                    track.scrollBy({ left: (card.getBoundingClientRect().width + gap) * 2 * direction, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
                },
            }" x-init="$nextTick(() => { update(); new ResizeObserver(() => update()).observe($refs.track); })">
                <div class="mb-6 flex items-end justify-between gap-4 border-b border-zinc-200 pb-4">
                    <div class="min-w-0">
                        <h2 class="truncate text-xl font-bold tracking-tight text-sf-text md:text-2xl">{{ $category->name }}</h2>
                        <span class="mt-2 block h-1 w-12 rounded-full bg-brand"></span>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <div class="flex gap-2" x-cloak x-show="overflow">
                            <button type="button" @click="slide(-1)" aria-label="{{ __('Previous products') }}"
                                class="flex h-9 w-9 items-center justify-center rounded-full border border-zinc-200 bg-white text-brand shadow-sm transition hover:border-brand hover:bg-brand hover:text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.79 15.71a1 1 0 0 1-1.42 0l-5-5a1 1 0 0 1 0-1.42l5-5a1 1 0 1 1 1.42 1.42L8.5 10l4.29 4.29a1 1 0 0 1 0 1.42Z" clip-rule="evenodd"/></svg>
                            </button>
                            <button type="button" @click="slide(1)" aria-label="{{ __('Next products') }}"
                                class="flex h-9 w-9 items-center justify-center rounded-full border border-zinc-200 bg-white text-brand shadow-sm transition hover:border-brand hover:bg-brand hover:text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.21 4.29a1 1 0 0 1 1.42 0l5 5a1 1 0 0 1 0 1.42l-5 5a1 1 0 1 1-1.42-1.42L11.5 10 7.21 5.71a1 1 0 0 1 0-1.42Z" clip-rule="evenodd"/></svg>
                            </button>
                        </div>
                        <a href="{{ route('shop', ['category' => $category->slug]) }}" class="inline-flex h-9 items-center gap-1 rounded-full border border-brand/20 bg-white px-4 text-sm font-semibold text-brand transition hover:bg-brand hover:text-white">
                            {{ __('View all') }}
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.21 4.29a1 1 0 0 1 1.42 0l5 5a1 1 0 0 1 0 1.42l-5 5a1 1 0 1 1-1.42-1.42L11.5 10 7.21 5.71a1 1 0 0 1 0-1.42Z" clip-rule="evenodd"/></svg>
                        </a>
                    </div>
                </div>
                <div x-ref="track" class="no-scrollbar grid w-full min-w-0 grid-flow-col auto-cols-[calc(50%_-_6px)] gap-3 overflow-x-auto overscroll-x-contain scroll-smooth pb-1 sm:auto-cols-[calc(33.333333%_-_8px)] md:auto-cols-[calc(25%_-_12px)] md:gap-4 xl:auto-cols-[calc(20%_-_12.8px)]">
                    @foreach ($category->products as $product)
                        @include('theme-ecommerce::partials._product-card', ['product' => $product])
                    @endforeach
                </div>
            </section>
        @endforeach
    @endif
    @if ($homeBrands->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 pt-8 sm:px-6">
            @include('theme-ecommerce::partials._section-heading', [
                'title' => __('Shop by brand'),
                'subtitle' => __('Browse products by your favorite brands.'),
            ])
            <style>
                .home-brand-marquee {
                    will-change: transform;
                }
                .home-brand-viewport {
                    -webkit-mask-image: linear-gradient(90deg, transparent, #000 4%, #000 96%, transparent);
                    mask-image: linear-gradient(90deg, transparent, #000 4%, #000 96%, transparent);
                    cursor: grab;
                    touch-action: pan-y;
                    user-select: none;
                }
                .home-brand-viewport.is-dragging {
                    cursor: grabbing;
                }
                @media (prefers-reduced-motion: reduce) {
                    .home-brand-marquee { will-change: auto; }
                    .home-brand-viewport {
                        -webkit-mask-image: none;
                        mask-image: none;
                    }
                }
            </style>
            <div class="rounded-xl p-0.5 sm:p-1">
              <div
                x-data="{
                    offset: 0,
                    startX: 0,
                    startOffset: 0,
                    pointerId: null,
                    dragging: false,
                    moved: false,
                    autoMove: !window.matchMedia('(prefers-reduced-motion: reduce)').matches,
                    lastTime: 0,
                    frame: null,
                    initMarquee() {
                        if (this.autoMove) this.frame = requestAnimationFrame((time) => this.animateBrands(time));
                    },
                    animateBrands(time) {
                        if (this.lastTime && this.autoMove && !this.dragging) {
                            this.offset += (time - this.lastTime) * 0.024;
                            this.normalizeOffset();
                            this.renderBrands();
                        }
                        this.lastTime = time;
                        this.frame = requestAnimationFrame((nextTime) => this.animateBrands(nextTime));
                    },
                    normalizeOffset() {
                        const loopWidth = this.$refs.track.scrollWidth / 2;
                        if (loopWidth > 0) this.offset = ((this.offset % loopWidth) + loopWidth) % loopWidth;
                    },
                    renderBrands() {
                        this.$refs.track.style.transform = `translate3d(-${this.offset}px, 0, 0)`;
                    },
                    startBrandDrag(event) {
                        if (event.button !== undefined && event.button !== 0) return;
                        this.dragging = true;
                        this.moved = false;
                        this.pointerId = event.pointerId;
                        this.startX = event.clientX;
                        this.startOffset = this.offset;
                        event.currentTarget.classList.add('is-dragging');
                    },
                    moveBrandDrag(event) {
                        if (!this.dragging || event.pointerId !== this.pointerId) return;
                        const distance = this.startX - event.clientX;
                        if (Math.abs(distance) > 4) this.moved = true;
                        if (!this.moved) return;
                        this.offset = this.startOffset + distance;
                        this.normalizeOffset();
                        this.renderBrands();
                    },
                    stopBrandDrag(event) {
                        if (!this.dragging || (event && event.pointerId !== this.pointerId)) return;
                        this.dragging = false;
                        this.pointerId = null;
                        this.lastTime = 0;
                        this.$refs.viewport.classList.remove('is-dragging');
                        if (this.moved) window.setTimeout(() => { this.moved = false; }, 0);
                    },
                    preventBrandClick(event) {
                        if (!this.moved) return;
                        event.preventDefault();
                        event.stopPropagation();
                        this.moved = false;
                    },
                    destroy() {
                        if (this.frame) cancelAnimationFrame(this.frame);
                    },
                }"
                x-init="initMarquee()"
                @pointerdown="startBrandDrag($event)"
                @pointermove.window="moveBrandDrag($event)"
                @pointerup.window="stopBrandDrag($event)"
                @pointercancel.window="stopBrandDrag($event)"
                @click.capture="preventBrandClick($event)"
                x-ref="viewport"
                class="home-brand-viewport no-scrollbar overflow-x-auto overscroll-x-contain rounded-xl">
                <div x-ref="track" class="home-brand-marquee flex w-max">
                    @foreach ([false, true] as $duplicate)
                        <div @if ($duplicate) aria-hidden="true" inert @endif class="flex shrink-0 gap-3 py-1 pr-3 sm:gap-4 sm:py-1.5 sm:pr-4">
                            @foreach ($homeBrands as $brand)
                                <a href="{{ route('shop', ['brand' => $brand->slug]) }}"
                                    @if ($duplicate) tabindex="-1" @endif
                                    draggable="false"
                                    class="group flex min-h-[56px] w-[140px] shrink-0 items-center justify-center rounded-xl border border-zinc-200/80 bg-white px-3 py-2 shadow-[0_3px_10px_-6px_rgba(15,23,42,0.28)] transition duration-300 hover:-translate-y-1 hover:border-brand/30 hover:shadow-[0_12px_24px_-12px_rgba(4,91,48,0.35)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand sm:min-h-[64px] sm:w-[170px]">
                                    @if ($brand->logo)
                                        <img src="{{ $brand->logo }}" alt="{{ $brand->name }}" loading="lazy" decoding="async" draggable="false" width="160" height="48" class="max-h-10 w-auto object-contain grayscale transition duration-300 group-hover:scale-105 group-hover:grayscale-0">
                                    @else
                                        <span class="text-xs font-bold uppercase tracking-[0.12em] text-zinc-600 transition-colors group-hover:text-brand sm:text-sm">{{ $brand->name }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    @endforeach
                </div>
              </div>
            </div>
        </section>
    @endif

    @foreach ($sections as $section)
        @continue(blank($section->localizedCards()))

        <section id="{{ $section->name }}" class="mx-auto max-w-7xl px-4 py-10 sm:px-6">
            @include('theme-ecommerce::partials._section-heading', ['title' => $section->name])
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 md:gap-4">
                @foreach ($section->localizedCards() as $card)
                    <div class="group overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm transition duration-300 hover:-translate-y-0.5 hover:border-brand/40 hover:shadow-md">
                        @if ($card['image'])
                            <div class="relative aspect-[4/3] overflow-hidden bg-zinc-100">
                                <img src="{{ $card['image'] }}" alt="{{ $card['title'] }}" loading="lazy" decoding="async" width="800" height="600"
                                    class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                            </div>
                        @endif
                        <div class="p-3">
                            @if ($card['title'])
                                <h3 class="font-semibold text-sf-text">{{ $card['title'] }}</h3>
                            @endif
                            @if ($card['description'])
                                <p class="mt-1.5 text-sm text-zinc-500 line-clamp-2">{{ $card['description'] }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach
</main>

@include('theme-ecommerce::partials._footer')

@include('frontend.partials._chat-widget')
@include('partials._custom-code-body')
</body>
</html>
