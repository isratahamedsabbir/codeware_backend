@if ($featured->isNotEmpty())
    <section class="mx-auto max-w-7xl px-4 pt-8 sm:px-6">
        @include('theme-ecommerce::partials._section-heading', [
            'title' => __('Featured products'),
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
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-xl font-bold tracking-tight text-sf-text md:text-2xl">{{ __('New arrivals') }}</h2>
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
