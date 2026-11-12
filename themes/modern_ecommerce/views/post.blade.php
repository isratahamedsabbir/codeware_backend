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
    'title' => $post->title,
    'crumbs' => [
        ['label' => __('Home'), 'url' => url('/')],
        ['label' => __('Blog'), 'url' => route('blog')],
        ['label' => $post->title, 'url' => null],
    ],
])

<main class="mx-auto max-w-[1400px] px-4 pb-16 pt-10 sm:px-8">
    <article class="mx-auto max-w-3xl">
        @if ($post->category?->page && $post->category->status === 'active')
            <a href="{{ route('blog', ['category' => $post->category->slug]) }}"
                class="inline-block text-[0.7rem] font-bold uppercase tracking-widest text-[var(--me-accent)] hover:underline">
                {{ $post->category->name }}
            </a>
        @endif
        <h1 class="mt-3 text-3xl font-black leading-tight tracking-tight text-[#1a1a1a] sm:text-4xl">{{ $post->title }}</h1>

        @if (filled($post->getTranslation('short_description', app()->getLocale())))
            <p class="mt-4 text-lg leading-relaxed text-gray-500">{{ $post->getTranslation('short_description', app()->getLocale()) }}</p>
        @endif

        <div class="mt-5 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-gray-400">
            @if ($post->user?->name)
                <span class="inline-flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a8.25 8.25 0 0 1 15 0" /></svg>
                    {{ $post->user->name }}
                </span>
            @endif
            @if ($post->published_at)
                <span class="inline-flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                    {{ $post->published_at->toDisplay() }}
                </span>
            @endif
            <span class="inline-flex items-center gap-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                {{ __(':min min read', ['min' => $post->reading_time]) }}
            </span>
        </div>

        @if ($post->featured_image)
            <div class="mt-8 overflow-hidden rounded-2xl border border-gray-100 shadow-sm">
                <img src="{{ $post->featured_image }}" alt="{{ $post->title }}" fetchpriority="high" width="1600" height="900"
                    class="aspect-[16/9] w-full object-cover">
            </div>
        @endif

        @if (filled($post->description))
            <div class="rich-text mt-8 border-b border-gray-100 pb-8 text-lg leading-relaxed text-gray-700">
                @richtext($post->description)
            </div>
        @endif

        @if ($post->tags->isNotEmpty())
            <div class="mt-8 flex flex-wrap items-center gap-2">
                @foreach ($post->tags as $tag)
                    <a href="{{ route('shop.tag', $tag->slug) }}"
                        class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-600 transition hover:bg-[var(--me-accent)]/10 hover:text-[var(--me-accent)]">
                        #{{ $tag->name }}
                    </a>
                @endforeach
            </div>
        @endif
    </article>

    <livewire:frontend.post-reactions :post-id="$post->id" :key="'post-reactions-'.$post->id" />

    @foreach ($sections as $section)
        @continue(blank($section->localizedCards()))

        <section id="{{ $section->name }}" class="border-t border-gray-100 py-12">
            <h2 class="mb-6 text-2xl font-black uppercase tracking-tight text-[#1a1a1a]">{{ $section->name }}</h2>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($section->localizedCards() as $card)
                    <div class="group overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl">
                        @if ($card['image'])
                            <div class="aspect-[16/10] overflow-hidden bg-gray-100">
                                <img src="{{ $card['image'] }}" alt="{{ $card['title'] }}" loading="lazy" decoding="async" width="800" height="500"
                                    class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                            </div>
                        @endif
                        <div class="p-5">
                            @if ($card['title'])
                                <h3 class="font-bold text-[#1a1a1a]">{{ $card['title'] }}</h3>
                            @endif
                            @if ($card['description'])
                                <p class="mt-1.5 line-clamp-2 text-sm text-gray-500">{{ $card['description'] }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach

    @if (\App\Support\Features::enabled('comments'))
        <livewire:frontend.blog-comments :post-id="$post->id" :key="'post-comments-'.$post->id" />
    @endif

    @if ($related->isNotEmpty())
        <section class="border-t border-gray-100 py-12">
            <h2 class="mb-6 text-2xl font-black uppercase tracking-tight text-[#1a1a1a]">{{ __('Related posts') }}</h2>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($related as $relatedPost)
                    <a href="{{ \App\Support\UrlStructure::url('post', $relatedPost->slug) }}"
                        class="group flex flex-col overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl">
                        @if ($relatedPost->featured_image)
                            <div class="relative aspect-[16/10] overflow-hidden bg-gray-100">
                                <img src="{{ $relatedPost->featured_image }}" alt="{{ $relatedPost->title }}" loading="lazy" decoding="async" width="800" height="500"
                                    class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                            </div>
                        @endif
                        <div class="flex flex-1 flex-col p-5">
                            <h3 class="line-clamp-2 font-bold text-[#1a1a1a] transition-colors group-hover:text-[var(--me-accent)]">{{ $relatedPost->title }}</h3>
                            @if ($relatedPost->published_at)
                                <span class="mt-2 text-xs text-gray-400">{{ $relatedPost->published_at->toDisplay() }}</span>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</main>

@include('theme-modern_ecommerce::partials._footer')

@include('frontend.partials._chat-widget')
@include('partials._custom-code-body')
</body>
</html>
