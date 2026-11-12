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
    ['lead' => $lead, 'secondary' => $secondary, 'latest' => $latest, 'sections' => $sections] = \Themes\News\Support\NewsFeed::front();
@endphp

<main class="mx-auto max-w-6xl px-4 pt-8 sm:px-6">
    @if ($lead)
        {{-- Top of the page: the lead story, two more beside it, and a column of
             the next headlines. --}}
        <section class="grid gap-8 lg:grid-cols-3">
            <div class="lg:col-span-2">
                @include('theme-news::partials._post-card', ['post' => $lead, 'variant' => 'lead'])
            </div>

            <div class="space-y-6 lg:border-l lg:border-zinc-200 lg:pl-8">
                @foreach ($secondary as $post)
                    @include('theme-news::partials._post-card', ['post' => $post, 'variant' => 'side'])
                    @unless ($loop->last)
                        <hr class="border-zinc-200">
                    @endunless
                @endforeach
            </div>
        </section>

        @if ($latest->isNotEmpty())
            <section class="mt-12 border-t-2 border-zinc-900 pt-4">
                <div class="flex items-baseline justify-between">
                    <h2 class="nw-serif text-2xl font-bold text-zinc-900">{{ __('Latest news') }}</h2>
                    <a href="{{ route('blog') }}" class="text-sm font-semibold hover:underline" style="color: var(--nw-accent)">{{ __('All stories') }} &rarr;</a>
                </div>
                <div class="mt-6 grid gap-x-10 gap-y-6 md:grid-cols-2">
                    @foreach ($latest as $post)
                        @include('theme-news::partials._post-card', ['post' => $post, 'variant' => 'row'])
                    @endforeach
                </div>
            </section>
        @endif

        @foreach ($sections as $category)
            <section class="mt-12 border-t-2 pt-4" style="border-top-color: var(--nw-accent)">
                <div class="flex items-baseline justify-between">
                    <h2 class="nw-serif text-2xl font-bold text-zinc-900">{{ $category->name }}</h2>
                    <a href="{{ route('blog', ['category' => $category->page->slug]) }}" class="text-sm font-semibold hover:underline" style="color: var(--nw-accent)">{{ __('More') }} &rarr;</a>
                </div>
                <div class="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($category->latestPosts as $post)
                        @include('theme-news::partials._post-card', ['post' => $post, 'variant' => 'card'])
                    @endforeach
                </div>
            </section>
        @endforeach
    @else
        <section class="py-24 text-center">
            <h1 class="nw-serif text-3xl font-bold text-zinc-900">{{ __('No stories yet') }}</h1>
            <p class="mt-3 text-zinc-500">{{ __('Published posts will appear here.') }}</p>
        </section>
    @endif
</main>

@include('theme-news::partials._footer')

@include('frontend.partials._chat-widget')
@include('partials._custom-code-body')
</body>
</html>
