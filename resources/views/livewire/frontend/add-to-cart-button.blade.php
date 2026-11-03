@assets
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('productVariants', (data) => ({
            groups: data.groups,
            variations: data.variations,
            // Product cards start on the first available combination, while
            // product detail pages pass the first visible combination so price
            // and stock match a real option from the initial render.
            selection: (data.initial_selection && { ...data.initial_selection }) || {},

            init() {
                if (! data.announce_stock) return;

                // The product page prints the price under the name and the stock
                // badge beside it (outside this component) — keep both in step
                // with the picked combination.
                const announce = () => window.dispatchEvent(new CustomEvent('variant-selected', {
                    detail: {
                        productId: data.product_id,
                        selected: !! this.selected,
                        inStock: this.selected ? this.selected.in_stock : null,
                        priceLabel: this.selected ? this.selected.price_label : this.base_price_label,
                        discountLabel: this.selected ? this.selected.discount_label : this.base_discount_label,
                        sku: this.selected ? this.selected.sku : null,
                    },
                }));
                this.$watch('selected', announce);
                announce();
            },
            base_price_label: data.base_price_label,
            base_discount_label: data.base_discount_label,
            base_stock_label: data.base_stock_label,

            toggle(attribute, value) {
                if (this.selection[attribute] === value) {
                    delete this.selection[attribute];
                } else {
                    this.selection[attribute] = value;
                }
            },

            // A value button is disabled when, given the current selection, no
            // combination that includes it is actually in stock — the variant
            // stays visible but can't be bought.
            canPick(attribute, value) {
                const prospective = { ...this.selection, [attribute]: value };
                const matches = this.variations.filter((variation) =>
                    Object.keys(prospective).every((name) =>
                        prospective[name] !== undefined && variation.attributes[name] === prospective[name]
                    )
                );

                return matches.length > 0 && matches.some((variation) => variation.in_stock);
            },

            get pickedCount() {
                return Object.keys(this.selection).filter((name) => this.selection[name] !== undefined).length;
            },

            get ready() {
                return this.pickedCount === Object.keys(this.groups).length;
            },

            get selected() {
                const names = Object.keys(this.selection).filter((name) => this.selection[name] !== undefined);
                const namesCount = names.length;

                if (!namesCount) {
                    return null;
                }

                return this.variations.find((variation) => {
                    const attrs = variation.attributes;
                    const attrNames = Object.keys(attrs);

                    return namesCount === attrNames.length
                        && names.every((name) => this.selection[name] === attrs[name]);
                }) ?? null;
            },

            // How many of the selected combination are already in the cart —
            // read live from the component, so the stepper follows every change.
            get cartQty() {
                return this.selected ? (this.$wire.inCartByCombo[this.selected.key] ?? 0) : 0;
            },

            more() {
                if (this.selected) this.$wire.call('increment', { ...this.selection });
            },

            less() {
                if (this.selected) this.$wire.call('decrement', { ...this.selection });
            },

            async addToCart() {
                if (!this.ready || !this.selected || !this.selected.in_stock) {
                    return;
                }

                await this.$wire.call('add', this.selection);

            },
        }));
    });
</script>
@endassets

@php
    $variationGroups = [];
    $variationRows = [];
    $pickerBaseDiscount = null;
    $firstAvailableVariation = null;

    if ($product !== null) {
        foreach ($variations as $row) {
            foreach (($row['attributes'] ?? []) as $attribute => $value) {
                $variationGroups[$attribute] = array_values(array_unique(array_merge($variationGroups[$attribute] ?? [], [$value])));
            }
        }

        $variationRows = collect($variations)->map(fn (array $row) => [
            'attributes' => $row['attributes'] ?? [],
            'key' => \App\Support\Cart::signature(array_map('strval', $row['attributes'] ?? [])),
            'sku' => ($row['sku'] ?? null) ?: null,
            'price_label' => format_money($row['price'] ?? $product->price),
            'discount_label' => ($rowDiscount = $product->sellDiscount(
                (float) ($row['price'] ?? $product->price),
                (isset($row['price'], $row['discount_price']) && (float) $row['discount_price'] < (float) $row['price'])
                    ? (float) $row['discount_price']
                    : null,
            )) !== null ? format_money($rowDiscount) : null,
            'in_stock' => (int) ($row['quantity'] ?? 0) > 0,
            'stock_label' => (int) ($row['quantity'] ?? 0) > 0
                ? __('In stock')
                : __('Out of stock'),
        ])->values()->all();

        $firstAvailableVariation = collect($variationRows)->first(fn (array $row) => $row['in_stock'])
            ?? ($variationRows[0] ?? null);

        $pickerBaseDiscount = $product->effectiveDiscount() !== null ? format_money($product->effectiveDiscount()) : null;
    }

    $pickerWireData = \Illuminate\Support\Js::from([
        'groups' => $variationGroups,
        'variations' => $variationRows,
        'initial_selection' => $requiresOptions
            ? ($firstAvailableVariation['attributes'] ?? null)
            : ($variations[0]['attributes'] ?? null),
        'product_id' => $productId,
        // Only the product page's inline picker drives the price / stock by the name.
        'announce_stock' => $showPicker && ! $requiresOptions,
        'base_price_label' => $product !== null ? format_money($product->price) : null,
        'base_discount_label' => $pickerBaseDiscount,
        'base_stock_label' => ($product !== null && $product->inStock()) ? __('In stock') : __('Out of stock'),
    ]);
@endphp

<div class="w-full">
    @if ($isUpcoming)
        <button
            type="button"
            disabled
            class="w-full cursor-not-allowed rounded-full bg-zinc-100 px-4 py-2.5 text-center text-sm font-semibold text-zinc-400"
        >{{ __('Coming soon') }}</button>
    @elseif (($showPicker || $requiresOptions) && $hasVisibleVariations && $product)
        <div
            x-data="productVariants({!! $pickerWireData !!})"
            class="w-full"
        >
            @if ($requiresOptions)
                <div class="mt-2 space-y-1 rounded-xl bg-zinc-50/80 p-1.5">
            @endif
            <template x-for="(values, attribute) in groups" :key="attribute">
                <div class="{{ $requiresOptions ? 'flex min-w-0 items-center gap-1' : 'mt-4' }}">
                    @unless ($requiresOptions)
                        <h3 class="mb-2 text-sm font-semibold text-sf-heading" :title="attribute" x-text="attribute"></h3>
                    @endunless
                    @if ($requiresOptions)
                        <div class="flex min-w-0 flex-1 items-center gap-1"
                            x-data="{
                                overflow: false,
                                dragging: false,
                                moved: false,
                                startX: 0,
                                scrollStart: 0,
                                update() { this.overflow = this.$refs.track.scrollWidth > this.$refs.track.clientWidth + 1; },
                                scroll(direction) {
                                    const track = this.$refs.track;
                                    track.scrollBy({ left: direction * Math.max(72, track.clientWidth * 0.75), behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
                                },
                            }"
                            x-init="$nextTick(() => { update(); new ResizeObserver(() => update()).observe($refs.track); })">
                            <button type="button" x-cloak x-show="overflow" @click.stop="scroll(-1)"
                                :aria-label="`Scroll ${attribute} options left`"
                                class="flex size-5 shrink-0 items-center justify-center rounded-full text-zinc-500 transition hover:bg-zinc-200/70 hover:text-brand">
                                <flux:icon.chevron-left class="size-3.5" />
                            </button>
                            <div x-ref="track"
                                @pointerdown="if ($event.pointerType === 'mouse' && $event.button === 0) { dragging = true; moved = false; startX = $event.clientX; scrollStart = $el.scrollLeft; }"
                                @pointermove.window="if (dragging) { const distance = $event.clientX - startX; if (Math.abs(distance) > 4) moved = true; $refs.track.scrollLeft = scrollStart - distance; }"
                                @pointerup.window="dragging = false"
                                @pointercancel.window="dragging = false"
                                @click.capture="if (moved) { $event.preventDefault(); $event.stopPropagation(); moved = false; }"
                                :class="dragging ? 'cursor-grabbing' : 'cursor-grab'"
                                class="no-scrollbar flex min-w-0 flex-1 select-none items-center gap-1.5 overflow-x-auto scroll-smooth">
                                <template x-for="value in values" :key="value">
                                    <button type="button" @click="selection[attribute] = value"
                                        :disabled="!canPick(attribute, value)"
                                        :aria-pressed="selection[attribute] === value"
                                        :class="!canPick(attribute, value)
                                                ? 'cursor-not-allowed border-zinc-100 bg-zinc-100/70 text-zinc-300 line-through opacity-70'
                                            : (selection[attribute] === value
                                                ? 'border-brand bg-brand text-white shadow-sm'
                                                : 'border-zinc-200 bg-white text-zinc-700 hover:border-brand hover:bg-brand/5')"
                                        class="shrink-0 whitespace-nowrap rounded-md border px-2.5 py-1.5 text-xs font-semibold transition"
                                        x-text="value"></button>
                                </template>
                            </div>
                            <button type="button" x-cloak x-show="overflow" @click.stop="scroll(1)"
                                :aria-label="`Scroll ${attribute} options right`"
                                class="flex size-5 shrink-0 items-center justify-center rounded-full text-zinc-500 transition hover:bg-zinc-200/70 hover:text-brand">
                                <flux:icon.chevron-right class="size-3.5" />
                            </button>
                        </div>
                    @else
                        <div class="flex flex-wrap gap-2">
                            <template x-for="value in values" :key="value">
                                <button type="button" @click="toggle(attribute, value)"
                                    :disabled="!canPick(attribute, value)"
                                    :aria-pressed="selection[attribute] === value"
                                    :class="!canPick(attribute, value)
                                        ? 'cursor-not-allowed border-zinc-200 text-zinc-300 line-through'
                                        : (selection[attribute] === value
                                            ? 'border-brand bg-brand/10 text-brand'
                                            : 'border-zinc-200 text-zinc-700 hover:border-brand/50')"
                                    class="rounded-full border px-4 py-2 text-sm font-medium transition"
                                    x-text="value"></button>
                            </template>
                        </div>
                    @endif
                </div>
            </template>
            @if ($requiresOptions)
                </div>
            @endif

            <template x-if="cartQty > 0">
                <div class="{{ $requiresOptions ? 'mt-2' : 'mt-6' }} flex w-full items-center gap-2">
                    <div class="flex h-10 min-w-0 flex-1 items-stretch overflow-hidden rounded-full border border-brand bg-white shadow-sm" wire:loading.class="opacity-70" wire:target="increment,decrement">
                        <button type="button" @click="less()" wire:loading.attr="disabled" aria-label="{{ __('Remove one') }}" class="flex w-10 shrink-0 items-center justify-center border-r border-brand/20 bg-brand/5 text-brand transition !rounded-none hover:bg-red-50 hover:text-red-600 disabled:opacity-50">
                            <template x-if="cartQty === 1"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg></template>
                            <template x-if="cartQty > 1"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" /></svg></template>
                        </button>
                        <span class="flex min-w-0 flex-1 items-center justify-center gap-1 truncate text-sm font-medium text-zinc-600"><span class="tabular-nums font-bold text-brand" x-text="cartQty"></span> {{ __('in cart') }}</span>
                        <button type="button" @click="more()" wire:loading.attr="disabled" aria-label="{{ __('Add one more') }}" class="flex w-10 shrink-0 items-center justify-center bg-sf-button text-sf-button-text transition !rounded-none hover:opacity-90 disabled:opacity-50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        </button>
                    </div>
                </div>
            </template>
            <template x-if="cartQty === 0">
            <div class="{{ $requiresOptions ? 'mt-2' : 'mt-6' }} flex w-full items-center gap-2">
                <button
                    type="button"
                    @click="addToCart()"
                    :disabled="!ready || (selected && !selected.in_stock)"
                    :class="ready && selected && selected.in_stock ? 'bg-brand' : 'cursor-not-allowed bg-zinc-300'"
                    aria-live="polite"
                    class="inline-flex h-10 min-w-0 flex-1 items-center justify-center gap-2 whitespace-nowrap rounded-full px-3 text-sm font-semibold text-white transition hover:opacity-90"
                >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                        </svg>
                        <span>{{ __('Add to cart') }}</span>
                </button>
            </div>
            </template>

            @error('options')
                <p class="mt-2 text-sm text-red-500">{{ $message }}</p>
            @enderror
        </div>
    @elseif (! $inStock)
        <button
            type="button"
            disabled
            class="w-full cursor-not-allowed rounded-full bg-zinc-100 px-4 py-2.5 text-center text-sm font-semibold text-zinc-400"
        >{{ __('Out of stock') }}</button>
    @elseif ($inCart > 0)
        {{-- Already in the cart: a stepper replaces the button so the shopper
             can add more (or take one out — the last one empties the line). --}}
        <div class="flex w-full items-center gap-2">
            <div class="flex h-10 min-w-0 flex-1 items-stretch overflow-hidden rounded-full border border-brand bg-white shadow-sm" wire:loading.class="opacity-70" wire:target="increment,decrement">
                <button type="button" wire:click="decrement" wire:loading.attr="disabled" aria-label="{{ __('Remove one') }}" class="flex w-10 shrink-0 items-center justify-center border-r border-brand/20 bg-brand/5 text-brand transition !rounded-none hover:bg-red-50 hover:text-red-600 disabled:opacity-50">
                    @if ($inCart === 1)
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                    @else
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14" /></svg>
                    @endif
                </button>
                <span class="flex min-w-0 flex-1 items-center justify-center gap-1 truncate text-sm font-medium text-zinc-600"><span class="tabular-nums font-bold text-brand">{{ $inCart }}</span> {{ __('in cart') }}</span>
                <button type="button" wire:click="increment" wire:loading.attr="disabled" aria-label="{{ __('Add one more') }}" class="flex w-10 shrink-0 items-center justify-center bg-sf-button text-sf-button-text transition !rounded-none hover:opacity-90 disabled:opacity-50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                </button>
            </div>
            @if ($adjustable)
                <a href="{{ route('cart') }}"
                    class="inline-flex h-10 shrink-0 items-center gap-1.5 rounded-full border border-zinc-300 bg-white px-4 text-sm font-semibold text-zinc-700 shadow-sm transition hover:border-brand hover:text-brand">
                    {{ __('View cart') }}
                </a>
            @endif
        </div>
    @else
        <div class="flex w-full items-center gap-2">
            <button
                type="button"
                wire:click="add"
                aria-live="polite"
                class="inline-flex h-10 min-w-0 flex-1 items-center justify-center gap-2 whitespace-nowrap rounded-full px-3 text-sm font-semibold text-sf-button-text transition hover:opacity-90 bg-sf-button"
            >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                    </svg>
                    <span>{{ __('Add to cart') }}</span>
            </button>
        </div>
    @endif
</div>
