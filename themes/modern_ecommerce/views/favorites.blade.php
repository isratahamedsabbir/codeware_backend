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
    'title' => __('My favorites'),
    'subtitle' => __('Products you have saved for later.'),
    'crumbs' => [['label' => __('Home'), 'url' => url('/')], ['label' => __('My favorites'), 'url' => null]],
])

<main class="mx-auto max-w-[1400px] px-4 pb-12 pt-8 sm:px-8">
    @if ($products->isEmpty())
        <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-gray-200 bg-white py-20 text-center">
            <span class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-[var(--me-accent)]/10 text-[var(--me-accent)]">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" /></svg>
            </span>
            <h2 class="text-lg font-extrabold uppercase tracking-wide text-[#1a1a1a]">{{ __('No favorites yet') }}</h2>
            <p class="mt-1 max-w-md text-sm text-gray-500">{{ __('Tap the heart on any product to save it here — it stays even before you sign in.') }}</p>
            <a href="{{ route('shop') }}" class="mt-6 inline-block bg-[#1a1a1a] px-8 py-3 text-xs font-bold uppercase tracking-widest text-white transition hover:bg-[var(--me-accent)]">{{ __('Browse products') }}</a>
        </div>
    @else
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($products as $product)
                @include('theme-modern_ecommerce::partials._product-card', ['product' => $product])
            @endforeach
        </div>
        <div class="mt-10">{{ $products->links() }}</div>
    @endif
</main>

@include('theme-modern_ecommerce::partials._footer')

@include('frontend.partials._chat-widget')
@include('partials._custom-code-body')
</body>
</html>
