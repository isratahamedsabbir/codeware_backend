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

@include('theme-modern_ecommerce::partials._page-banner', [
    'title' => __('Flash deals'),
    'subtitle' => __('Limited-time prices — while stock lasts.'),
    'crumbs' => [['label' => __('Home'), 'url' => url('/')], ['label' => __('Flash deals'), 'url' => null]],
])

<main class="mx-auto max-w-[1400px] px-4 pb-12 pt-8 sm:px-8">
    @forelse ($deals as $deal)
        <section class="mb-12">
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-gray-100 bg-[#f8f9fa] px-5 py-4">
                <div>
                    <h2 class="text-lg font-black uppercase tracking-tight text-[#1a1a1a]">{{ $deal->name }}</h2>
                    <p class="text-sm font-bold" style="color: var(--me-accent)">{{ $deal->valueLabel() }}</p>
                </div>

                <div x-data="{
                        left: {{ max(0, (int) now()->diffInSeconds($deal->ends_at, false)) }},
                        init() { setInterval(() => { if (this.left > 0) this.left-- }, 1000) },
                        part(n) { return String(n).padStart(2, '0') },
                        get d() { return Math.floor(this.left / 86400) },
                        get h() { return Math.floor((this.left % 86400) / 3600) },
                        get m() { return Math.floor((this.left % 3600) / 60) },
                        get s() { return this.left % 60 },
                    }"
                    class="flex items-center gap-2 text-sm" role="timer" aria-label="{{ __('Time left') }}">
                    <span class="text-[0.65rem] font-bold uppercase tracking-widest text-gray-500">{{ __('Ends in') }}</span>
                    <template x-if="d > 0">
                        <span class="rounded bg-[#1a1a1a] px-2 py-1 font-mono font-bold text-white"><span x-text="d"></span>{{ __('d') }}</span>
                    </template>
                    <span class="rounded bg-[#1a1a1a] px-2 py-1 font-mono font-bold text-white" x-text="part(h)"></span>
                    <span class="font-bold text-gray-400">:</span>
                    <span class="rounded bg-[#1a1a1a] px-2 py-1 font-mono font-bold text-white" x-text="part(m)"></span>
                    <span class="font-bold text-gray-400">:</span>
                    <span class="rounded bg-[var(--me-accent)] px-2 py-1 font-mono font-bold text-white" x-text="part(s)"></span>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($deal->products as $product)
                    @include('theme-modern_ecommerce::partials._product-card', ['product' => $product])
                @endforeach
            </div>
        </section>
    @empty
        <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-gray-200 bg-white py-20 text-center">
            <h2 class="text-lg font-extrabold uppercase tracking-wide text-[#1a1a1a]">{{ __('No flash deals right now') }}</h2>
            <p class="mt-1 max-w-md text-sm text-gray-500">{{ __('Check back soon — new deals show up here the moment they start.') }}</p>
            <a href="{{ route('shop') }}" class="mt-6 inline-block bg-[#1a1a1a] px-8 py-3 text-xs font-bold uppercase tracking-widest text-white transition hover:bg-[var(--me-accent)]">{{ __('Browse products') }}</a>
        </div>
    @endforelse
</main>

@include('theme-modern_ecommerce::partials._footer')

@include('frontend.partials._chat-widget')
@include('partials._custom-code-body')
</body>
</html>
