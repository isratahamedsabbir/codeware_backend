@php
    // format_money() returns "৳ 1,055.81" as plain text (it feeds admin and mail too), so the
    // symbol is split off here and enlarged — the taka glyph renders tiny next to the digits.
    $money = function ($amount): \Illuminate\Support\HtmlString {
        $text = format_money($amount);
        $cut = strrpos($text, ' ');

        return new \Illuminate\Support\HtmlString(
            $cut === false
                ? e($text)
                : '<span class="text-[1.2em] font-black leading-none [-webkit-text-stroke:0.6px_currentColor]">'.e(substr($text, 0, $cut)).'</span> '.e(substr($text, $cut + 1))
        );
    };

    $isUpcoming = $product->is_upcoming;
    $inStock = $product->inStock();
    $hasVariations = $product->hasVisibleVariations();
    $discount = $product->effectiveDiscount();
    $discountPercent = $discount && (float) $product->price > 0
        ? round((1 - $discount / (float) $product->price) * 100)
        : null;

    // Small eyebrow above the name: the brand when there is one, else the first category.
    $eyebrow = $product->brand?->name ?: $product->categories->first()?->name;
@endphp

<div class="group relative flex h-full flex-col overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-[0_1px_2px_rgba(16,24,40,0.04)] transition duration-300 hover:-translate-y-0.5 hover:border-brand/40 hover:shadow-[0_10px_24px_-8px_rgba(16,24,40,0.18)]">
    <a href="{{ \App\Support\UrlStructure::url('product', $product->slug) }}" class="relative block overflow-hidden bg-zinc-50" aria-label="{{ $product->name }}">
        @if ($product->featured_image)
            <img src="{{ $product->featured_image }}" alt="{{ $product->name }}"
                loading="lazy" decoding="async" width="380" height="285"
                class="aspect-[4/3] w-full object-cover transition duration-500 group-hover:scale-105 {{ ! $inStock && ! $isUpcoming ? 'opacity-60 grayscale' : '' }}">
        @else
            <div class="flex aspect-[4/3] w-full items-center justify-center bg-gradient-to-b from-zinc-50 to-zinc-100 text-zinc-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 7 9-5 9 5 0 10-9 5-9-5V7Zm0 0 9 5m0 0 9-5m-9 5v10" />
                </svg>
            </div>
        @endif

        @if ($discountPercent)
            <span class="absolute left-2.5 top-2.5 rounded-md bg-sale px-2 py-1 text-xs font-bold leading-none tracking-wide text-white shadow-sm">
                -{{ $discountPercent }}%
            </span>
        @elseif ($isUpcoming)
            <span class="absolute left-2.5 top-2.5 rounded-md bg-amber-500 px-2 py-1 text-xs font-bold uppercase leading-none tracking-wide text-white shadow-sm">
                {{ __('Upcoming') }}
            </span>
        @endif

        @if (filled($product->label))
            <span class="absolute inset-x-0 bottom-0 bg-brand/90 py-1.5 text-center text-[11px] font-semibold uppercase tracking-wider text-white backdrop-blur-sm">
                {{ $product->label }}
            </span>
        @elseif (! $inStock && ! $isUpcoming)
            <span class="absolute inset-x-0 bottom-0 bg-zinc-900/75 py-1 text-center text-[11px] font-semibold uppercase tracking-wider text-white backdrop-blur-sm">
                {{ __('Out of stock') }}
            </span>
        @endif
    </a>

    <livewire:frontend.wishlist-button
        :product-id="$product->id"
        :key="'wishlist-'.$product->id"
    />

    <div class="flex flex-1 flex-col gap-1 p-3.5">
        @if ($eyebrow)
            <span class="truncate text-[11px] font-semibold uppercase tracking-wider text-zinc-400">{{ $eyebrow }}</span>
        @endif

        <h3 class="text-sm font-semibold leading-snug text-sf-text">
            <a href="{{ \App\Support\UrlStructure::url('product', $product->slug) }}" title="{{ $product->name }}" class="line-clamp-2 min-h-[2.5rem] transition-colors hover:text-brand">
                {{ $product->name }}
            </a>
        </h3>

        <div class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5 pt-1.5">
            @if ($discount)
                <span class="text-base font-bold text-sf-price">{{ $money($discount) }}</span>
                <span class="text-xs text-zinc-400 line-through">{{ $money($product->price) }}</span>
            @else
                <span class="text-base font-bold text-sf-price">{{ $money($product->price) }}</span>
            @endif

            @if (($sold = $product->soldQuantity()) > 0)
                <span class="ms-auto inline-flex items-center gap-1 text-xs text-zinc-500" title="{{ __('Units sold') }}">
                    {{ $sold }} {{ __('sold') }}
                </span>
            @endif
        </div>

        @unless ($hasVariations)
            <div class="mt-2 flex min-h-[4.25rem] items-center gap-2.5 text-xs">
                <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-700">
                    <flux:icon.check class="size-4" />
                </span>
                <div class="min-w-0">
                    <p class="font-semibold text-zinc-700">{{ __('Standard item') }}</p>
                    <p class="mt-0.5 text-zinc-500">{{ __('No options to select') }}</p>
                </div>
            </div>
        @endunless

        <div class="mt-auto border-t border-zinc-100 pt-3">
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
