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
    $category = ($post->category?->page && $post->category->status === 'active') ? $post->category : null;
@endphp

<main>
    <article class="mx-auto max-w-3xl px-4 pt-10 sm:px-6">
        <header>
            @if ($category)
                <a href="{{ route('blog', ['category' => $category->page->slug]) }}"
                    class="text-xs font-bold uppercase tracking-widest hover:underline" style="color: var(--nw-accent)">{{ $category->name }}</a>
            @endif

            <h1 class="nw-serif mt-2 text-3xl font-black leading-tight text-zinc-900 sm:text-5xl">{{ $post->title }}</h1>

            <p class="mt-5 flex flex-wrap items-center gap-x-3 gap-y-1 border-y border-zinc-200 py-3 text-sm text-zinc-500">
                @if ($post->user?->name)
                    <span>{{ __('By') }} <span class="font-semibold text-zinc-800">{{ $post->user->name }}</span></span>
                @endif
                @if ($post->published_at)
                    <time datetime="{{ $post->published_at->toDateString() }}">{{ $post->published_at->toDisplay() }}</time>
                @endif
                @if ($post->reading_time)
                    <span>{{ __(':min min read', ['min' => $post->reading_time]) }}</span>
                @endif
            </p>
        </header>

        @if (filled($post->featured_image))
            <img src="{{ $post->featured_image }}" alt="{{ $post->title }}" fetchpriority="high" width="1200" height="675"
                class="mt-8 aspect-[16/9] w-full object-cover">
        @endif

        @if (filled($post->description))
            <div class="nw-prose mt-8">
                @richtext($post->description)
            </div>
        @endif

        @if ($post->tags->isNotEmpty())
            <div class="mt-10 flex flex-wrap items-center gap-2 border-t border-zinc-200 pt-6">
                @foreach ($post->tags as $tag)
                    <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-600">#{{ $tag->name }}</span>
                @endforeach
            </div>
        @endif
    </article>

    @foreach ($sections as $section)
        @continue(blank($section->localizedCards()))

        <section class="mx-auto mt-12 max-w-6xl border-t border-zinc-200 px-4 pt-10 sm:px-6">
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($section->localizedCards() as $card)
                    <div>
                        @if ($card['image'])
                            <img src="{{ $card['image'] }}" alt="{{ $card['title'] ?? '' }}" class="mb-3 aspect-[16/10] w-full object-cover" loading="lazy">
                        @endif
                        @if ($card['title'])
                            <h3 class="nw-serif text-lg font-bold text-zinc-900">{{ $card['title'] }}</h3>
                        @endif
                        @if ($card['description'])
                            <p class="mt-1 text-sm text-zinc-600">{{ $card['description'] }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach

    @if (\App\Support\Features::enabled('comments'))
        <div class="mx-auto mt-12 max-w-3xl border-t border-zinc-200 px-4 pt-10 sm:px-6">
            <livewire:frontend.blog-comments :post-id="$post->id" :key="'post-comments-'.$post->id" />
        </div>
    @endif

    @if ($related->isNotEmpty())
        <section class="mx-auto mt-14 max-w-6xl border-t-2 border-zinc-900 px-4 pt-4 sm:px-6">
            <h2 class="nw-serif text-2xl font-bold text-zinc-900">{{ __('More stories') }}</h2>
            <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($related as $relatedPost)
                    @include('theme-news::partials._post-card', ['post' => $relatedPost, 'variant' => 'card'])
                @endforeach
            </div>
        </section>
    @endif
</main>

@include('theme-news::partials._footer')

@include('frontend.partials._chat-widget')
@include('partials._custom-code-body')
</body>
</html>
