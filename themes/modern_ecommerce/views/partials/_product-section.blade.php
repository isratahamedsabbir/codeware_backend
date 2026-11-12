@if ($products->isNotEmpty())
    <section class="mx-auto max-w-[1400px] px-4 py-10 sm:px-8 sm:py-12">
        <div class="mb-7 flex flex-wrap items-end justify-between gap-3">
            <h2 class="relative pb-2.5 text-[1.35rem] font-extrabold uppercase tracking-tight text-[#1a1a1a] sm:text-[1.6rem]">
                {{ $title }}
                <span class="absolute bottom-0 left-0 h-[3px] w-[50px]" style="background: var(--me-accent)"></span>
            </h2>
            <a href="{{ $viewAll ?? route('shop') }}"
                class="group inline-flex items-center gap-1.5 pb-1 text-[0.7rem] font-bold uppercase tracking-widest text-gray-500 transition hover:text-[var(--me-accent)]">
                {{ __('View all') }}
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="transition group-hover:translate-x-1"><path d="M5 12h14M12 5l7 7-7 7" /></svg>
            </a>
        </div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($products as $product)
                @include('theme-modern_ecommerce::partials._product-card', ['product' => $product])
            @endforeach
        </div>
    </section>
@endif
