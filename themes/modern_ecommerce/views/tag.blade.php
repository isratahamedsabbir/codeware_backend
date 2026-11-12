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
    'title' => '#'.$tag->name,
    'subtitle' => __('Everything tagged :tag.', ['tag' => $tag->name]),
    'crumbs' => [
        ['label' => __('Home'), 'url' => url('/')],
        ['label' => __('Shop'), 'url' => route('shop')],
        ['label' => '#'.$tag->name, 'url' => null],
    ],
])

<main class="mx-auto max-w-[1400px] px-4 pb-12 pt-8 sm:px-8">
    @if ($products->isNotEmpty())
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($products as $product)
                @include('theme-modern_ecommerce::partials._product-card', ['product' => $product])
            @endforeach
        </div>
        <div class="mt-10">{{ $products->links() }}</div>
    @else
        @include('theme-modern_ecommerce::partials._empty-state', ['heading' => __('No products with this tag'), 'message' => __('Check back soon — new items are added regularly.')])
    @endif
</main>

@include('theme-modern_ecommerce::partials._footer')

@include('frontend.partials._chat-widget')
@include('partials._custom-code-body')
</body>
</html>
