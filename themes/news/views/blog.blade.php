<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locale::direction() }}">
<head>
    @include('partials._head')
    @include('partials._seo-meta')
    @include('partials._custom-code-head')
    @include('theme-news::partials._theme-tokens')
</head>
<body class="bg-white text-zinc-800 antialiased">

@include('theme-news::partials._header')

@php
    $activeCategory = (string) request()->query('category', '');
    $activeName = $activeCategory !== '' ? $categories->first(fn ($c) => $c->page->slug === $activeCategory)?->name : null;
@endphp

<main class="mx-auto max-w-6xl px-4 pt-8 sm:px-6">
    <div class="border-b-2 border-zinc-900 pb-3">
        <h1 class="nw-serif text-3xl font-bold text-zinc-900 sm:text-4xl">{{ $activeName ?: __('Latest news') }}</h1>
    </div>

    @if ($categories->isNotEmpty())
        <nav aria-label="{{ __('Blog categories') }}" class="mt-5 flex flex-wrap gap-2">
            <a href="{{ route('blog') }}"
                class="rounded-full border px-4 py-1.5 text-xs font-semibold transition {{ $activeCategory === '' ? 'border-transparent text-white' : 'border-zinc-200 text-zinc-600 hover:border-zinc-400' }}"
                @if ($activeCategory === '') style="background: var(--nw-accent)" @endif>{{ __('All') }}</a>
            @foreach ($categories as $category)
                <a href="{{ route('blog', ['category' => $category->page->slug]) }}"
                    class="rounded-full border px-4 py-1.5 text-xs font-semibold transition {{ $activeCategory === $category->page->slug ? 'border-transparent text-white' : 'border-zinc-200 text-zinc-600 hover:border-zinc-400' }}"
                    @if ($activeCategory === $category->page->slug) style="background: var(--nw-accent)" @endif>
                    {{ $category->name }} <span class="opacity-60">{{ $category->posts_count }}</span>
                </a>
            @endforeach
        </nav>
    @endif

    @if ($posts->isNotEmpty())
        <div class="mt-8 grid gap-x-8 gap-y-10 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($posts as $post)
                @include('theme-news::partials._post-card', ['post' => $post, 'variant' => 'card'])
            @endforeach
        </div>

        <div class="mt-12">
            {{ $posts->onEachSide(1)->links() }}
        </div>
    @else
        <p class="py-20 text-center text-zinc-500">{{ __('No stories in this section yet.') }}</p>
    @endif
</main>

@include('theme-news::partials._footer')

@include('frontend.partials._chat-widget')
@include('partials._custom-code-body')
</body>
</html>
