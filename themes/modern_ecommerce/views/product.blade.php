<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locale::direction() }}">
<head>
    @include('partials._head')
    @include('partials._seo-meta')
    @include('partials._custom-code-head')
    @include('theme-modern_ecommerce::partials._theme-tokens')
</head>
<body class="bg-white text-gray-900 antialiased">

@include('theme-modern_ecommerce::partials._header')

@php
    $visibleVariations = collect($product->variations ?? [])
        ->filter(fn (array $row) => $row['visible'] ?? true)
        ->values();

    $galleryImages = collect()
        ->push($product->featured_image)
        ->merge($product->gallery->pluck('url'))
        ->filter()
        ->values()
        ->all();

    $hasVariations = $visibleVariations->isNotEmpty();
    $baseDiscount = $product->effectiveDiscount();
    $baseDiscountLabel = $baseDiscount !== null ? format_money($baseDiscount) : null;

    $discountPercent = (! $hasVariations && $baseDiscount !== null && (float) $product->price > 0)
        ? (int) round(((float) $product->price - $baseDiscount) / (float) $product->price * 100)
        : null;

    $rating = null;
    if (\App\Support\Features::enabled('reviews') && $product->averageRating() !== null) {
        $rating = [
            'average' => $product->averageRating(),
            'count' => $product->approvedReviews()->count(),
        ];
    }

    $productDetailTab = filled($product->description) ? 'description' : 'specifications';

    $crumbs = [
        ['label' => __('Home'), 'url' => url('/')],
        ['label' => __('Shop'), 'url' => route('shop')],
    ];
    if ($category = $product->categories->first()) {
        $crumbs[] = ['label' => $category->name, 'url' => route('shop', ['category' => $category->slug])];
    }
    $crumbs[] = ['label' => $product->name, 'url' => null];
@endphp

<div class="border-b border-gray-100 bg-[#f8f9fa]">
    <nav aria-label="{{ __('Breadcrumb') }}" class="mx-auto max-w-[1400px] px-4 py-4 sm:px-8">
        <ol class="flex flex-wrap items-center gap-2 text-[0.65rem] font-bold uppercase tracking-widest text-gray-400">
            @foreach ($crumbs as $crumb)
                @if (! $loop->first)<li aria-hidden="true">/</li>@endif
                <li>
                    @if (! empty($crumb['url']))
                        <a href="{{ $crumb['url'] }}" class="transition-colors hover:text-[var(--me-accent)]">{{ $crumb['label'] }}</a>
                    @else
                        <span class="text-gray-800">{{ $crumb['label'] }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
</div>

<main class="mx-auto max-w-[1400px] px-4 pb-10 pt-8 sm:px-8">
    <div class="grid gap-8 md:grid-cols-2 lg:gap-12">
        {{-- Gallery --}}
        <div>
            @if ($galleryImages)
                <div
                    x-data="{
                        active: 0,
                        images: @js(array_values($galleryImages)),
                        zooming: false,
                        canZoom: window.matchMedia('(hover: hover) and (pointer: fine)').matches,
                        target: { x: 50, y: 50 },
                        origin: { x: 50, y: 50 },
                        frame: null,
                        show(index) { this.active = (index + this.images.length) % this.images.length; },
                        enter(e) { if (! this.canZoom) return; this.track(e); this.origin = { ...this.target }; this.zooming = true; this.loop(); },
                        track(e) { const r = this.$refs.stage.getBoundingClientRect(); this.target.x = Math.min(Math.max(((e.clientX - r.left) / r.width) * 100, 0), 100); this.target.y = Math.min(Math.max(((e.clientY - r.top) / r.height) * 100, 0), 100); },
                        loop() { cancelAnimationFrame(this.frame); const step = () => { this.origin.x += (this.target.x - this.origin.x) * 0.18; this.origin.y += (this.target.y - this.origin.y) * 0.18; this.$refs.zoomImg.style.transformOrigin = `${this.origin.x}% ${this.origin.y}%`; if (this.zooming) this.frame = requestAnimationFrame(step); }; step(); },
                        leave() { this.zooming = false; cancelAnimationFrame(this.frame); },
                    }"
                    class="flex flex-col-reverse gap-3 sm:flex-row"
                >
                    @if (count($galleryImages) > 1)
                        <div class="sm:relative sm:w-20 sm:shrink-0">
                            <div class="flex gap-2.5 overflow-x-auto pb-1 sm:absolute sm:inset-0 sm:flex-col sm:overflow-y-auto sm:overflow-x-hidden sm:pb-0">
                                <template x-for="(image, index) in images" :key="index">
                                    <button type="button" @click="show(index)" @mouseenter="show(index)"
                                        :class="active === index ? 'border-[var(--me-accent)]' : 'border-gray-200 opacity-70 hover:opacity-100'"
                                        class="aspect-square w-18 shrink-0 overflow-hidden rounded-xl border-2 bg-white transition sm:w-full">
                                        <img :src="image" alt="" loading="lazy" class="h-full w-full object-cover">
                                    </button>
                                </template>
                            </div>
                        </div>
                    @endif

                    <div class="group/stage relative min-w-0 flex-1">
                        <div x-ref="stage" @mouseenter="enter($event)" @mousemove="track($event)" @mouseleave="leave()"
                            :class="zooming ? 'cursor-zoom-out' : (canZoom ? 'cursor-zoom-in' : '')"
                            class="relative aspect-square overflow-hidden rounded-2xl border border-gray-100 bg-[#f8f9fa]">
                            <template x-for="(image, index) in images" :key="index">
                                <img :src="image" alt="{{ $product->name }}" x-show="active === index" class="absolute inset-0 h-full w-full object-cover">
                            </template>
                            <img x-ref="zoomImg" :src="images[active]" alt="" aria-hidden="true"
                                :style="zooming ? 'transform: scale(2.2); opacity: 1' : 'transform: scale(1); opacity: 0'"
                                class="pointer-events-none absolute inset-0 h-full w-full object-cover will-change-transform [transition:transform_450ms_cubic-bezier(0.22,1,0.36,1),opacity_200ms_ease-out]">
                        </div>
                        @if (count($galleryImages) > 1)
                            <button type="button" @click="show(active - 1)" aria-label="{{ __('Previous image') }}"
                                class="absolute left-3 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-gray-700 shadow-md backdrop-blur transition hover:bg-[var(--me-accent)] hover:text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                            </button>
                            <button type="button" @click="show(active + 1)" aria-label="{{ __('Next image') }}"
                                class="absolute right-3 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-white/90 text-gray-700 shadow-md backdrop-blur transition hover:bg-[var(--me-accent)] hover:text-white">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                            </button>
                        @endif
                    </div>
                </div>
            @else
                <div class="flex aspect-square items-center justify-center rounded-2xl border border-gray-100 bg-[#f8f9fa] text-gray-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 7 9-5 9 5 0 10-9 5-9-5V7Zm0 0 9 5m0 0 9-5m-9 5v10" /></svg>
                </div>
            @endif
        </div>

        {{-- Details --}}
        <div class="lg:pb-4">
            @php
                $firstVariation = $visibleVariations->first();
                $variantPrice = $firstVariation['price'] ?? $product->price;
                $initial = $hasVariations
                    ? [
                        'inStock' => (int) ($firstVariation['quantity'] ?? 0) > 0,
                        'priceLabel' => format_money($variantPrice),
                        'discountLabel' => ($firstVariationDiscount = $product->sellDiscount(
                            (float) $variantPrice,
                            isset($firstVariation['price'], $firstVariation['discount_price'])
                                && (float) $firstVariation['discount_price'] < (float) $firstVariation['price']
                                    ? (float) $firstVariation['discount_price']
                                    : null,
                        )) !== null ? format_money($firstVariationDiscount) : null,
                        'sku' => ($firstVariation['sku'] ?? null) ?: null,
                    ]
                    : [
                        'inStock' => $product->inStock(),
                        'priceLabel' => format_money($product->price),
                        'discountLabel' => $baseDiscountLabel,
                        'sku' => $product->sku ?: null,
                    ];
            @endphp

            <div x-data="{ selected: true, inStock: @js($initial['inStock']), priceLabel: @js($initial['priceLabel']), discountLabel: @js($initial['discountLabel']), sku: @js($initial['sku']) }"
                @if ($hasVariations)
                    @variant-selected.window="if ($event.detail.productId === {{ $product->id }}) { selected = $event.detail.selected; inStock = $event.detail.inStock; priceLabel = $event.detail.priceLabel; discountLabel = $event.detail.discountLabel; sku = $event.detail.sku; }"
                @endif>
                <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                    <h1 class="text-2xl font-black uppercase tracking-tight text-[#1a1a1a] sm:text-3xl">{{ $product->name }}</h1>

                    @if ($product->is_upcoming)
                        <span class="inline-flex shrink-0 items-center rounded-full bg-amber-100 px-3 py-1 text-[0.6rem] font-bold uppercase tracking-widest text-amber-700">{{ __('Upcoming') }}</span>
                    @else
                        <span x-show="selected && inStock" @unless ($initial['inStock']) x-cloak @endunless class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-[0.6rem] font-bold uppercase tracking-widest text-emerald-700">{{ __('In stock') }}</span>
                        <span x-show="selected && ! inStock" @if ($initial['inStock']) x-cloak @endif class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-red-50 px-3 py-1 text-[0.6rem] font-bold uppercase tracking-widest text-red-600">{{ __('Out of stock') }}</span>
                    @endif
                </div>

                @if ($rating)
                    <a href="#reviews" class="mt-3 inline-flex items-center gap-2 text-sm text-gray-500 transition hover:text-[#1a1a1a]">
                        <span class="flex gap-0.5">
                            @foreach (range(1, 5) as $i)
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-4 w-4 {{ $i <= round($rating['average']) ? 'fill-amber-400 text-amber-400' : 'fill-gray-200 text-gray-200' }}" stroke="currentColor" stroke-width="1"><path stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" /></svg>
                            @endforeach
                        </span>
                        <span class="font-bold text-[#1a1a1a]">{{ number_format($rating['average'], 1) }}</span>
                        <span>({{ trans_choice(':count review|:count reviews', $rating['count'], ['count' => $rating['count']]) }})</span>
                    </a>
                @endif

                <div class="mt-5 flex items-baseline gap-3">
                    <span class="text-3xl font-black" style="color: var(--me-accent)" x-text="discountLabel || priceLabel">{{ $initial['discountLabel'] ?: $initial['priceLabel'] }}</span>
                    <span x-show="discountLabel" @unless ($initial['discountLabel']) x-cloak @endunless class="text-lg font-medium text-gray-400 line-through" x-text="priceLabel">{{ $initial['priceLabel'] }}</span>
                    @if ($discountPercent !== null)
                        <span class="rounded-full bg-[var(--me-accent)]/10 px-2.5 py-1 text-xs font-black text-[var(--me-accent)]">-{{ $discountPercent }}%</span>
                    @endif
                </div>

                <p x-show="sku" @unless ($initial['sku']) x-cloak @endunless class="mt-2 text-[0.65rem] font-bold uppercase tracking-widest text-gray-400">
                    {{ __('SKU') }}: <span class="font-mono" x-text="sku">{{ $initial['sku'] }}</span>
                </p>

                @if (filled($product->excerpt))
                    <div class="mt-5 text-sm leading-relaxed text-gray-600">
                        <div class="rich-text whitespace-pre-line">@richtext($product->excerpt)</div>
                    </div>
                @endif

                @if ($product->warranty_months > 0 || ! $product->charge_shipping)
                    <div class="mt-5 flex flex-wrap gap-2 text-[0.65rem] font-bold uppercase tracking-widest text-gray-600">
                        @if ($product->warranty_months > 0)
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 px-3 py-1.5">{{ __(':months months warranty', ['months' => $product->warranty_months]) }}</span>
                        @endif
                        @unless ($product->charge_shipping)
                            <span class="inline-flex items-center gap-1.5 rounded-full border border-gray-200 px-3 py-1.5 text-emerald-600">{{ __('Free shipping') }}</span>
                        @endunless
                    </div>
                @endif

                <div class="mt-6">
                    <livewire:frontend.add-to-cart-button
                        :product-id="$product->id"
                        :adjustable="true"
                        :quantity="1"
                        :show-picker="$hasVariations"
                        :key="'add-to-cart-'.$product->id"
                    />
                </div>

                <div class="mt-8 flex flex-wrap items-center gap-2 border-t border-gray-100 pt-6">
                    @foreach ($product->categories as $category)
                        <a href="{{ route('shop', ['category' => $category->slug]) }}" class="rounded-full border border-gray-200 px-3 py-1 text-[0.65rem] font-bold uppercase tracking-wide text-gray-600 transition hover:border-[var(--me-accent)] hover:text-[var(--me-accent)]">{{ $category->name }}</a>
                    @endforeach
                    @if ($product->brand)
                        <a href="{{ route('shop', ['brand' => $product->brand->slug]) }}" class="rounded-full bg-gray-100 px-3 py-1 text-[0.65rem] font-bold uppercase tracking-wide text-gray-600 transition hover:bg-[var(--me-accent)]/10 hover:text-[var(--me-accent)]">{{ $product->brand->name }}</a>
                    @endif
                    <span class="rounded-full bg-gray-100 px-3 py-1 text-[0.65rem] font-bold uppercase tracking-wide text-gray-500">{{ $product->product_type === 'digital' ? __('Digital') : __('Physical') }}</span>
                    @foreach ($product->tags as $tag)
                        <a href="{{ route('shop.tag', $tag->slug) }}" class="rounded-full bg-gray-100 px-3 py-1 text-[0.65rem] font-bold uppercase tracking-wide text-gray-600 transition hover:bg-[var(--me-accent)]/10 hover:text-[var(--me-accent)]">#{{ $tag->name }}</a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    @php($hasProductDetails = filled($product->description) || filled($product->specifications))

    @if ($hasProductDetails)
        <section class="mt-12 max-w-3xl">
            <div x-data="{ tab: @js($productDetailTab) }">
                <div class="flex flex-wrap gap-2" role="tablist" aria-label="{{ __('Product details') }}">
                    @if (filled($product->description))
                        <button type="button" role="tab" @click="tab = 'description'" :aria-selected="tab === 'description'"
                            :class="tab === 'description' ? 'bg-[#1a1a1a] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                            class="rounded-full px-5 py-2.5 text-[0.7rem] font-bold uppercase tracking-widest transition">{{ __('Description') }}</button>
                    @endif
                    @if (filled($product->specifications))
                        <button type="button" role="tab" @click="tab = 'specifications'" :aria-selected="tab === 'specifications'"
                            :class="tab === 'specifications' ? 'bg-[#1a1a1a] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                            class="rounded-full px-5 py-2.5 text-[0.7rem] font-bold uppercase tracking-widest transition">{{ __('Specifications') }}</button>
                    @endif
                </div>
                <div class="mt-6">
                    @if (filled($product->description))
                        <div x-show="tab === 'description'" @if ($productDetailTab !== 'description') x-cloak @endif role="tabpanel" class="rich-text text-base leading-relaxed text-gray-600">
                            @richtext($product->description)
                        </div>
                    @endif
                    @if (filled($product->specifications))
                        <div x-show="tab === 'specifications'" @if ($productDetailTab !== 'specifications') x-cloak @endif role="tabpanel" class="rich-text text-sm leading-relaxed text-gray-600">
                            @richtext($product->specifications)
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @endif

    @if (\App\Support\Features::enabled('reviews'))
        <livewire:frontend.product-reviews :product-id="$product->id" :key="'product-reviews-'.$product->id" />
    @endif

    @if ($product->faqs->where('is_active', true)->isNotEmpty())
        <section class="mt-12 max-w-3xl">
            <h2 class="mb-5 text-xl font-black uppercase tracking-tight text-[#1a1a1a]">{{ __('Frequently asked questions') }}</h2>
            <div class="space-y-3">
                @foreach ($product->faqs->where('is_active', true) as $faq)
                    <details class="group rounded-2xl border border-gray-100 bg-white shadow-sm">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 font-bold text-[#1a1a1a]">
                            {{ $faq->question }}
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-gray-400 transition group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7" /></svg>
                        </summary>
                        <div class="px-5 pb-5 leading-relaxed text-gray-600">{{ $faq->answer }}</div>
                    </details>
                @endforeach
            </div>
        </section>
    @endif

    @foreach ($sections as $section)
        @continue(blank($section->localizedCards()))
        <section id="{{ $section->name }}" class="mt-12 border-t border-gray-100 pt-10">
            <h2 class="mb-6 text-xl font-black uppercase tracking-tight text-[#1a1a1a]">{{ $section->name }}</h2>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($section->localizedCards() as $card)
                    <div class="group overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm transition hover:-translate-y-1 hover:shadow-xl">
                        @if ($card['image'])
                            <div class="relative aspect-square overflow-hidden bg-gray-100">
                                <img src="{{ $card['image'] }}" alt="{{ $card['title'] }}" class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                            </div>
                        @endif
                        <div class="p-4">
                            @if ($card['title'])<h3 class="font-bold text-[#1a1a1a]">{{ $card['title'] }}</h3>@endif
                            @if ($card['description'])<p class="mt-1.5 line-clamp-2 text-sm text-gray-500">{{ $card['description'] }}</p>@endif
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach

    @include('theme-modern_ecommerce::partials._product-section', ['title' => __('You may also like'), 'products' => $related])
</main>

@include('theme-modern_ecommerce::partials._footer')

@include('frontend.partials._chat-widget')
@include('partials._custom-code-body')
</body>
</html>
