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
    $facets = collect(['category', 'brand', 'tag', 'type', 'min_price', 'max_price']);
    $activeFacetCount = $facets->reject(fn ($facet) => blank($filters[$facet]))->count() + count($filters['attributes']);
    $hasActiveFilters = $activeFacetCount > 0 || filled($filters['search']);

    $bounds = $priceBounds ?? (object) ['min' => 0, 'max' => 100000];
    $priceMin = floor((float) $bounds->min);
    $priceMax = ceil((float) $bounds->max);
    $filterMin = is_numeric($filters['min_price']) ? (float) $filters['min_price'] : $priceMin;
    $filterMax = is_numeric($filters['max_price']) ? (float) $filters['max_price'] : $priceMax;
    $currencySymbol = \App\Models\Setting::get('currency_symbol', '৳');

    $without = fn (array $keys) => request()->fullUrlWithQuery(array_fill_keys([...$keys, 'page'], null));
    $chips = [];
    if (filled($filters['search'])) {
        $chips[] = ['label' => __('Search').': '.$filters['search'], 'url' => $without(['search'])];
    }
    if (filled($filters['category'])) {
        $chips[] = ['label' => $categories->firstWhere('slug', $filters['category'])?->name ?? $filters['category'], 'url' => $without(['category'])];
    }
    if (filled($filters['brand'])) {
        $chips[] = ['label' => $brands->firstWhere('slug', $filters['brand'])?->name ?? $filters['brand'], 'url' => $without(['brand'])];
    }
    if (filled($filters['tag'])) {
        $chips[] = ['label' => '#'.($tags->firstWhere('slug', $filters['tag'])?->name ?? $filters['tag']), 'url' => $without(['tag'])];
    }
    if (filled($filters['type'])) {
        $chips[] = ['label' => $filters['type'] === 'digital' ? __('Digital') : __('Physical'), 'url' => $without(['type'])];
    }
    if (filled($filters['min_price']) || filled($filters['max_price'])) {
        $chips[] = ['label' => $currencySymbol.number_format($filterMin).' – '.$currencySymbol.number_format($filterMax), 'url' => $without(['min_price', 'max_price'])];
    }
    foreach ($filters['attributes'] as $attrName => $attrValue) {
        $chips[] = [
            'label' => $attrName.': '.$attrValue,
            'url' => request()->fullUrlWithQuery(['attributes' => array_diff_key($filters['attributes'], [$attrName => true]) ?: null, 'page' => null]),
        ];
    }

    $summary = 'flex cursor-pointer list-none items-center justify-between py-4 text-[0.7rem] font-black uppercase tracking-[0.2em] text-gray-800 [&::-webkit-details-marker]:hidden';
    $chevron = '<svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-400 transition-transform duration-200 group-open:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7" /></svg>';
    $listRow = fn (bool $on) => 'group/row flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm transition '.($on
        ? 'bg-[var(--me-accent)]/5 font-bold text-[var(--me-accent)]'
        : 'text-gray-600 hover:bg-gray-50 hover:text-[#1a1a1a]');
    $radio = fn (bool $on) => 'flex h-4 w-4 shrink-0 items-center justify-center rounded-full border-2 transition '.($on
        ? 'border-[var(--me-accent)]'
        : 'border-gray-300 group-hover/row:border-gray-400');
    $pill = fn (bool $on) => 'rounded-full border px-3 py-1.5 text-xs font-bold uppercase tracking-wide transition '.($on
        ? 'border-[var(--me-accent)] bg-[var(--me-accent)] text-white'
        : 'border-gray-200 bg-white text-gray-600 hover:border-[var(--me-accent)] hover:text-[var(--me-accent)]');
@endphp

@include('theme-modern_ecommerce::partials._page-banner', [
    'title' => __('Shop'),
    'subtitle' => __('Browse the full collection and filter it to what you need.'),
    'crumbs' => [['label' => __('Home'), 'url' => url('/')], ['label' => __('Shop'), 'url' => null]],
])

<main class="mx-auto max-w-[1400px] px-4 pb-12 pt-8 sm:px-8">
    <div class="grid gap-7 lg:grid-cols-[17rem_1fr]" x-data="{ filtersOpen: false }">
        {{-- Mobile filter toggle --}}
        <div class="lg:hidden">
            <button type="button" @click="filtersOpen = ! filtersOpen" :aria-expanded="filtersOpen" aria-controls="shop-filters"
                class="flex w-full items-center justify-between rounded-xl border border-gray-200 bg-white px-4 py-3 text-[0.7rem] font-black uppercase tracking-widest text-gray-800 shadow-sm">
                <span class="flex items-center gap-2">
                    {{ __('Filters') }}
                    @if ($activeFacetCount > 0)
                        <span class="flex h-5 min-w-5 items-center justify-center rounded-full bg-[var(--me-accent)] px-1.5 text-[11px] font-bold text-white">{{ $activeFacetCount }}</span>
                    @endif
                </span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 transition-transform duration-200" :class="filtersOpen ? '-rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7" /></svg>
            </button>
        </div>

        <aside id="shop-filters" class="lg:sticky lg:top-28 lg:self-start" :class="filtersOpen ? 'block' : 'hidden lg:block'">
            <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-gray-100 bg-[#f8f9fa] px-5 py-4">
                    <h2 class="text-[0.7rem] font-black uppercase tracking-[0.2em] text-gray-800">{{ __('Filters') }}</h2>
                    @if ($hasActiveFilters)
                        <a href="{{ route('shop') }}" class="text-[0.65rem] font-bold uppercase tracking-widest text-gray-500 transition hover:text-[var(--me-accent)]">{{ __('Clear all') }}</a>
                    @endif
                </div>

                <div class="divide-y divide-gray-100 px-5">
                    {{-- Search --}}
                    <form action="{{ route('shop') }}" method="GET" role="search" class="py-4">
                        <label for="shop-search" class="sr-only">{{ __('Search') }}</label>
                        <div class="relative">
                            <input type="search" id="shop-search" name="search" value="{{ $filters['search'] }}" placeholder="{{ __('Search products...') }}"
                                class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 pl-4 pr-4 text-sm font-medium text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-[var(--me-accent)] focus:bg-white">
                        </div>
                    </form>

                    {{-- Price --}}
                    <style>
                        .me-range { appearance: none; -webkit-appearance: none; background: transparent; pointer-events: none; }
                        .me-range::-webkit-slider-thumb { appearance: none; -webkit-appearance: none; width: 18px; height: 18px; border-radius: 9999px; background: #fff; border: 2px solid var(--me-accent); box-shadow: 0 1px 4px rgba(0,0,0,.2); pointer-events: auto; cursor: grab; }
                        .me-range::-moz-range-thumb { width: 18px; height: 18px; border-radius: 9999px; background: #fff; border: 2px solid var(--me-accent); box-shadow: 0 1px 4px rgba(0,0,0,.2); pointer-events: auto; cursor: grab; }
                        .me-range::-moz-range-track { background: transparent; }
                    </style>
                    <details open class="group">
                        <summary class="{{ $summary }}">{{ __('Price') }} {!! $chevron !!}</summary>
                        <section class="pb-5"
                            x-data="{
                                min: {{ $filterMin }}, max: {{ $filterMax }},
                                appliedMin: {{ $filterMin }}, appliedMax: {{ $filterMax }},
                                boundsMin: {{ $priceMin }}, boundsMax: {{ $priceMax }}, timer: null,
                                pct(v) { const span = (this.boundsMax - this.boundsMin) || 1; return Math.round(((Math.min(Math.max(v, this.boundsMin), this.boundsMax) - this.boundsMin) / span) * 1000) / 10; },
                                onMin() { if (this.min > this.max) this.min = this.max; },
                                onMax() { if (this.max < this.min) this.max = this.min; },
                                setMin(v) { v = v === '' ? this.boundsMin : Number(v); this.min = Math.min(Math.max(isNaN(v) ? this.boundsMin : v, this.boundsMin), this.max); this.apply(); },
                                setMax(v) { v = v === '' ? this.boundsMax : Number(v); this.max = Math.max(Math.min(isNaN(v) ? this.boundsMax : v, this.boundsMax), this.min); this.apply(); },
                                apply() { clearTimeout(this.timer); this.timer = setTimeout(() => { if (this.min === this.appliedMin && this.max === this.appliedMax) return; this.$refs.priceForm.requestSubmit(); }, 450); },
                                format(v) { return Number(v).toLocaleString('en-US'); },
                            }">
                            <form x-ref="priceForm" action="{{ route('shop') }}" method="GET" class="space-y-4">
                                @foreach (request()->query() as $key => $value)
                                    @unless (in_array($key, ['min_price', 'max_price', 'page'], true))
                                        @if (is_array($value))
                                            @foreach ($value as $subKey => $subValue)
                                                <input type="hidden" name="{{ $key }}[{{ $subKey }}]" value="{{ $subValue }}">
                                            @endforeach
                                        @else
                                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                        @endif
                                    @endunless
                                @endforeach
                                <input type="hidden" name="min_price" :value="Math.round(min)" :disabled="min <= boundsMin">
                                <input type="hidden" name="max_price" :value="Math.round(max)" :disabled="max >= boundsMax">
                                <div class="relative h-6 px-1">
                                    <div class="absolute inset-x-0 top-1/2 h-1.5 -translate-y-1/2 rounded-full bg-gray-200"></div>
                                    <div class="absolute top-1/2 h-1.5 -translate-y-1/2 rounded-full bg-[var(--me-accent)]" :style="'left: ' + pct(min) + '%; right: ' + (100 - pct(max)) + '%'"></div>
                                    <input type="range" :min="boundsMin" :max="boundsMax" step="1" x-model.number="min" @input="onMin()" @change="apply()" class="me-range absolute inset-x-0 top-1/2 z-20 h-1.5 w-full -translate-y-1/2" aria-label="{{ __('Min price') }}">
                                    <input type="range" :min="boundsMin" :max="boundsMax" step="1" x-model.number="max" @input="onMax()" @change="apply()" class="me-range absolute inset-x-0 top-1/2 z-10 h-1.5 w-full -translate-y-1/2" aria-label="{{ __('Max price') }}">
                                </div>
                                <div class="flex items-center gap-2">
                                    <input type="number" min="0" step="1" :value="Math.round(min)" @change="setMin($event.target.value)" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm font-semibold text-gray-900 outline-none focus:border-[var(--me-accent)] focus:bg-white" aria-label="{{ __('Min') }}">
                                    <span class="text-gray-300">–</span>
                                    <input type="number" min="0" step="1" :value="Math.round(max)" @change="setMax($event.target.value)" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm font-semibold text-gray-900 outline-none focus:border-[var(--me-accent)] focus:bg-white" aria-label="{{ __('Max') }}">
                                </div>
                                <p class="text-xs text-gray-400">{{ __('Range') }}: {{ $currencySymbol }}<span x-text="format(boundsMin)"></span> – {{ $currencySymbol }}<span x-text="format(boundsMax)"></span></p>
                            </form>
                        </section>
                    </details>

                    {{-- Categories --}}
                    <details class="group">
                        <summary class="{{ $summary }}">{{ __('Categories') }} {!! $chevron !!}</summary>
                        <ul class="-mx-2.5 max-h-72 space-y-0.5 overflow-y-auto pb-4">
                            <li>
                                <a href="{{ $without(['category']) }}" class="{{ $listRow(blank($filters['category'])) }}">
                                    <span class="{{ $radio(blank($filters['category'])) }}">@if (blank($filters['category']))<span class="h-2 w-2 rounded-full bg-[var(--me-accent)]"></span>@endif</span>
                                    <span class="truncate">{{ __('All categories') }}</span>
                                </a>
                            </li>
                            @foreach (\App\Models\ProductCategory::tree($categories) as $category)
                                @php($on = $filters['category'] === $category->slug)
                                <li>
                                    <a href="{{ request()->fullUrlWithQuery(['category' => $on ? null : $category->slug, 'page' => null]) }}" class="{{ $listRow($on) }}" style="margin-left: {{ $category->depth * 0.9 }}rem">
                                        <span class="{{ $radio($on) }}">@if ($on)<span class="h-2 w-2 rounded-full bg-[var(--me-accent)]"></span>@endif</span>
                                        <span class="truncate">{{ $category->name }}</span>
                                        @if ($category->products_count > 0)
                                            <span class="ml-auto shrink-0 rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-semibold text-gray-500">{{ $category->products_count }}</span>
                                        @endif
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </details>

                    {{-- Brands --}}
                    @if ($brands->isNotEmpty())
                        <details class="group">
                            <summary class="{{ $summary }}">{{ __('Brands') }} {!! $chevron !!}</summary>
                            <ul class="-mx-2.5 max-h-60 space-y-0.5 overflow-y-auto pb-4">
                                @foreach ($brands as $brand)
                                    @php($on = $filters['brand'] === $brand->slug)
                                    <li>
                                        <a href="{{ request()->fullUrlWithQuery(['brand' => $on ? null : $brand->slug, 'page' => null]) }}" class="{{ $listRow($on) }}">
                                            <span class="flex h-4 w-4 shrink-0 items-center justify-center rounded border-2 transition {{ $on ? 'border-[var(--me-accent)] bg-[var(--me-accent)] text-white' : 'border-gray-300 group-hover/row:border-gray-400' }}">
                                                @if ($on)<svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="4"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>@endif
                                            </span>
                                            <span class="truncate">{{ $brand->name }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </details>
                    @endif

                    {{-- Type --}}
                    <details class="group">
                        <summary class="{{ $summary }}">{{ __('Type') }} {!! $chevron !!}</summary>
                        <div class="flex flex-wrap gap-2 pb-5">
                            @foreach (['physical' => __('Physical'), 'digital' => __('Digital')] as $type => $label)
                                @php($on = $filters['type'] === $type)
                                <a href="{{ request()->fullUrlWithQuery(['type' => $on ? null : $type, 'page' => null]) }}" class="{{ $pill($on) }}">{{ $label }}</a>
                            @endforeach
                        </div>
                    </details>

                    {{-- Variation options --}}
                    @if (! empty($attributeFacets))
                        @foreach ($attributeFacets as $name => $values)
                            <details class="group">
                                <summary class="{{ $summary }}">{{ $name }} {!! $chevron !!}</summary>
                                <div class="flex flex-wrap gap-2 pb-5">
                                    @foreach ($values as $value => $count)
                                        @php($on = ($filters['attributes'][$name] ?? null) === $value)
                                        <a href="{{ request()->fullUrlWithQuery(['attributes' => $on ? array_filter($filters['attributes'], fn ($_, $k) => $k !== $name, ARRAY_FILTER_USE_BOTH) : ($filters['attributes'] + [$name => $value]), 'page' => null]) }}" class="{{ $pill($on) }}">{{ $value }} <span class="ml-0.5 text-[10px] {{ $on ? 'text-white/80' : 'text-gray-400' }}">({{ $count }})</span></a>
                                    @endforeach
                                </div>
                            </details>
                        @endforeach
                    @endif

                    {{-- Tags --}}
                    @if ($tags->isNotEmpty())
                        <details class="group">
                            <summary class="{{ $summary }}">{{ __('Tags') }} {!! $chevron !!}</summary>
                            <div class="flex flex-wrap gap-2 pb-5">
                                @foreach ($tags as $tag)
                                    @php($on = $filters['tag'] === $tag->slug)
                                    <a href="{{ request()->fullUrlWithQuery(['tag' => $on ? null : $tag->slug, 'page' => null]) }}" class="{{ $pill($on) }}">#{{ $tag->name }}</a>
                                @endforeach
                            </div>
                        </details>
                    @endif
                </div>
            </div>
        </aside>

        <div class="min-w-0">
            {{-- Toolbar --}}
            <div class="mb-6 rounded-2xl border border-gray-100 bg-white px-4 py-3 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm text-gray-500">
                        @if ($products->total() > 0)
                            {!! __('Showing :from–:to of :total products', [
                                'from' => '<span class="font-bold text-[#1a1a1a]">'.$products->firstItem().'</span>',
                                'to' => '<span class="font-bold text-[#1a1a1a]">'.$products->lastItem().'</span>',
                                'total' => '<span class="font-bold text-[#1a1a1a]">'.$products->total().'</span>',
                            ]) !!}
                        @else
                            {{ __(':count products found', ['count' => 0]) }}
                        @endif
                    </p>

                    <form action="{{ route('shop') }}" method="GET" class="flex items-center gap-2">
                        @foreach (request()->query() as $key => $value)
                            @unless (in_array($key, ['sort', 'page'], true))
                                @if (is_array($value))
                                    @foreach ($value as $subKey => $subValue)
                                        <input type="hidden" name="{{ $key }}[{{ $subKey }}]" value="{{ $subValue }}">
                                    @endforeach
                                @else
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endif
                            @endunless
                        @endforeach
                        <label for="shop-sort" class="text-[0.65rem] font-bold uppercase tracking-widest text-gray-400">{{ __('Sort by') }}</label>
                        <div class="relative">
                            <select id="shop-sort" name="sort" onchange="this.form.submit()"
                                class="appearance-none rounded-xl border border-gray-200 bg-white py-2 pl-3.5 pr-9 text-sm font-semibold text-gray-900 outline-none transition focus:border-[var(--me-accent)]">
                                <option value="" {{ blank($filters['sort']) ? 'selected' : '' }}>{{ __('Default') }}</option>
                                <option value="newest" {{ $filters['sort'] === 'newest' ? 'selected' : '' }}>{{ __('Newest') }}</option>
                                <option value="price_asc" {{ $filters['sort'] === 'price_asc' ? 'selected' : '' }}>{{ __('Price: Low to High') }}</option>
                                <option value="price_desc" {{ $filters['sort'] === 'price_desc' ? 'selected' : '' }}>{{ __('Price: High to Low') }}</option>
                            </select>
                            <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7" /></svg>
                        </div>
                    </form>
                </div>

                @if ($chips)
                    <div class="mt-3 flex flex-wrap items-center gap-2 border-t border-gray-100 pt-3">
                        <span class="text-[0.6rem] font-black uppercase tracking-widest text-gray-400">{{ __('Active') }}:</span>
                        @foreach ($chips as $chip)
                            <a href="{{ $chip['url'] }}" title="{{ __('Remove filter') }}"
                                class="group/chip inline-flex items-center gap-1.5 rounded-full bg-[var(--me-accent)]/10 py-1 pl-3 pr-2 text-xs font-bold text-[var(--me-accent)] transition hover:bg-red-50 hover:text-red-600">
                                {{ $chip['label'] }}
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 opacity-70 group-hover/chip:opacity-100" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                            </a>
                        @endforeach
                        <a href="{{ route('shop') }}" class="ml-1 text-xs font-bold text-gray-500 underline-offset-4 hover:text-[var(--me-accent)] hover:underline">{{ __('Clear all') }}</a>
                    </div>
                @endif
            </div>

            @if ($products->isNotEmpty())
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($products as $product)
                        @include('theme-modern_ecommerce::partials._product-card', ['product' => $product])
                    @endforeach
                </div>

                <div class="mt-10">
                    {{ $products->links() }}
                </div>
            @else
                @include('theme-modern_ecommerce::partials._empty-state')
            @endif
        </div>
    </div>
</main>

@include('theme-modern_ecommerce::partials._footer')

@include('frontend.partials._chat-widget')
@include('partials._custom-code-body')
</body>
</html>
