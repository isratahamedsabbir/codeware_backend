<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locale::direction() }}">
<head>
    @include('partials._head')
    @include('partials._seo-meta')
    @include('partials._custom-code-head')
</head>
<body class="bg-page-bg font-storefront text-sf-text antialiased">

@include('theme-ecommerce::partials._header')

@php
    $crumbs = [
        ['label' => __('Home'), 'url' => url('/')],
        ['label' => __('Shop'), 'url' => route('shop')],
        ['label' => __('Brands'), 'url' => null],
        ['label' => $brand->name, 'url' => null],
    ];
@endphp

@include('theme-ecommerce::partials._breadcrumbs', ['crumbs' => $crumbs])

<main class="mx-auto max-w-7xl px-4 py-8 sm:px-6">
    <section>
        <div class="mb-6 flex flex-wrap items-end justify-between gap-x-4 gap-y-2">
            <div>
                <p class="text-sm text-zinc-500">{{ __(':count products found', ['count' => $products->total()]) }}</p>
            </div>
        </div>

        @if ($products->isNotEmpty())
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:gap-5 lg:grid-cols-4">
                @foreach ($products as $product)
                    @include('theme-ecommerce::partials._product-card', ['product' => $product])
                @endforeach
            </div>
            <div class="mt-10">
                {{ $products->links() }}
            </div>
        @else
            <div class="flex flex-col items-center justify-center rounded-card border border-dashed border-zinc-200 py-24 text-center">
                <h2 class="text-lg font-semibold text-sf-heading">{{ __('No products from this brand yet') }}</h2>
                <a href="{{ route('shop') }}" class="mt-5 rounded-full bg-brand px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90">
                    {{ __('View all products') }}
                </a>
            </div>
        @endif
    </section>
</main>

@include('theme-ecommerce::partials._footer')

@include('frontend.partials._chat-widget')
@include('partials._custom-code-body')
</body>
</html>
