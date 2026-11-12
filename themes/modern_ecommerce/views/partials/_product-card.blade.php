@php
    $inStock = $product->inStock();
    $isUpcoming = $product->is_upcoming;
    $hasVariations = $product->hasVisibleVariations();
    $discount = $product->effectiveDiscount();
    $discountPercent = $discount && (float) $product->price > 0
        ? round((1 - $discount / (float) $product->price) * 100)
        : null;
    $url = \App\Support\UrlStructure::url('product', $product->slug);
@endphp

<div class="group relative flex w-full flex-col overflow-hidden rounded-2xl border border-gray-100 bg-white transition-all duration-300 hover:shadow-xl">
    @if ($discountPercent)
        <div class="absolute left-3 top-3 z-10 flex h-14 w-14 flex-col items-center justify-center rounded-full bg-[#222] text-[0.75rem] font-bold leading-none text-white shadow-md">
            <span>-{{ $discountPercent }}%</span>
            <span class="mt-0.5 text-[0.6rem] font-normal">{{ __('off') }}</span>
        </div>
    @endif

    @if ($isUpcoming)
        <span class="absolute right-3 top-3 z-10 rounded-full bg-amber-100 px-3 py-1 text-[0.6rem] font-bold uppercase tracking-widest text-amber-700">{{ __('Upcoming') }}</span>
    @elseif (! $inStock)
        <span class="absolute right-3 top-3 z-10 rounded-full bg-gray-900/80 px-3 py-1 text-[0.6rem] font-bold uppercase tracking-widest text-white backdrop-blur">{{ __('Sold out') }}</span>
    @endif

    <a href="{{ $url }}" class="block h-[300px] overflow-hidden bg-[#f8f9fa]" aria-label="{{ $product->name }}">
        @if ($product->featured_image)
            <img src="{{ $product->featured_image }}" alt="{{ $product->name }}" loading="lazy" decoding="async" width="380" height="300"
                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105 {{ ! $inStock && ! $isUpcoming ? 'opacity-60 grayscale' : '' }}">
        @else
            <div class="flex h-full w-full items-center justify-center text-gray-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-14 w-14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 7 9-5 9 5 0 10-9 5-9-5V7Zm0 0 9 5m0 0 9-5m-9 5v10" /></svg>
            </div>
        @endif
    </a>

    <div class="flex flex-1 flex-col items-center p-4 text-center">
        <h3 class="mb-2 px-2 text-lg font-medium leading-tight text-gray-800">
            <a href="{{ $url }}" class="line-clamp-2 transition-colors hover:text-[var(--me-accent)]">{{ $product->name }}</a>
        </h3>
        <div class="mt-1 flex items-center justify-center gap-3">
            @if ($discount)
                <span class="text-base text-gray-400 line-through">{{ format_money($product->price) }}</span>
                <span class="text-xl font-bold" style="color: var(--me-accent)">{{ format_money($discount) }}</span>
            @else
                <span class="text-xl font-bold" style="color: var(--me-accent)">{{ format_money($product->price) }}</span>
            @endif
        </div>
        <div class="mt-4 w-full border-t border-gray-100 pt-3">
            <livewire:frontend.add-to-cart-button
                :product-id="$product->id"
                :slug="$product->slug"
                :in-stock="$inStock"
                :is-upcoming="$isUpcoming"
                :requires-options="$hasVariations"
                :has-variations="$hasVariations"
                :key="'add-to-cart-'.$product->id"
            />
        </div>
    </div>
</div>
