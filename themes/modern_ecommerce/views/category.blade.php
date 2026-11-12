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
    'title' => $category->name,
    'subtitle' => $category->page?->excerpt,
    'crumbs' => [
        ['label' => __('Home'), 'url' => url('/')],
        ['label' => __('Shop'), 'url' => route('shop')],
        ['label' => $category->name, 'url' => null],
    ],
])

<main class="mx-auto max-w-[1400px] px-4 pb-12 pt-8 sm:px-8">
    @if ($children->isNotEmpty())
        <div class="mb-8 flex flex-wrap gap-2">
            @foreach ($children as $child)
                @if ($child->page)
                    <a href="{{ route('shop', ['category' => $child->slug]) }}"
                        class="rounded-full border border-gray-200 bg-white px-4 py-2 text-[0.7rem] font-bold uppercase tracking-widest text-gray-600 transition hover:border-[var(--me-accent)] hover:text-[var(--me-accent)]">
                        {{ $child->name }}
                    </a>
                @endif
            @endforeach
        </div>
    @endif

    @if (filled($category->page?->content))
        <div class="rich-text mb-10 max-w-3xl leading-relaxed text-gray-600">@richtext($category->page->content)</div>
    @endif

    @foreach ($sections as $section)
        @continue(blank($section->localizedCards()))
        <section id="{{ $section->name }}" class="mb-10">
            <h2 class="mb-5 text-xl font-black uppercase tracking-tight text-[#1a1a1a]">{{ $section->name }}</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($section->localizedCards() as $card)
                    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white transition hover:shadow-xl">
                        @if ($card['image'])
                            <img src="{{ $card['image'] }}" alt="{{ $card['title'] }}" loading="lazy" class="aspect-[4/3] w-full object-cover">
                        @endif
                        <div class="p-4">
                            @if ($card['title'])<h3 class="font-bold text-[#1a1a1a]">{{ $card['title'] }}</h3>@endif
                            @if ($card['description'])<p class="mt-1.5 line-clamp-2 text-sm text-gray-500">{{ $card['description'] }}</p>@endif
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach

    @if ($products->isNotEmpty())
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($products as $product)
                @include('theme-modern_ecommerce::partials._product-card', ['product' => $product])
            @endforeach
        </div>
        <div class="mt-10">{{ $products->links() }}</div>
    @else
        @include('theme-modern_ecommerce::partials._empty-state', ['heading' => __('No products in this category'), 'message' => __('Check back soon — new items are added regularly.')])
    @endif
</main>

@include('theme-modern_ecommerce::partials._footer')

@include('frontend.partials._chat-widget')
@include('partials._custom-code-body')
</body>
</html>
