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

@php
    $activeCategory = (string) request()->query('category', '');
@endphp

@include('theme-modern_ecommerce::partials._page-banner', [
    'title' => __('Blog'),
    'subtitle' => __('Stories, guides and updates from the team.'),
    'crumbs' => [['label' => __('Home'), 'url' => url('/')], ['label' => __('Blog'), 'url' => null]],
])

<main class="mx-auto max-w-[1400px] px-4 pb-16 pt-10 sm:px-8">
    @if ($categories->isNotEmpty())
        <nav aria-label="{{ __('Blog categories') }}" class="mb-10 flex flex-wrap items-center gap-2">
            <a href="{{ route('blog') }}"
                class="rounded-full px-5 py-2 text-[0.7rem] font-bold uppercase tracking-widest transition {{ $activeCategory === '' ? 'bg-[var(--me-accent)] text-white shadow-sm' : 'bg-white text-gray-500 ring-1 ring-gray-200 hover:text-[var(--me-accent)] hover:ring-[var(--me-accent)]/40' }}">
                {{ __('All') }}
            </a>
            @foreach ($categories as $category)
                <a href="{{ route('blog', ['category' => $category->slug]) }}"
                    class="rounded-full px-5 py-2 text-[0.7rem] font-bold uppercase tracking-widest transition {{ $activeCategory === $category->slug ? 'bg-[var(--me-accent)] text-white shadow-sm' : 'bg-white text-gray-500 ring-1 ring-gray-200 hover:text-[var(--me-accent)] hover:ring-[var(--me-accent)]/40' }}">
                    {{ $category->name }}
                    <span class="ml-1 font-normal opacity-60">{{ $category->posts_count }}</span>
                </a>
            @endforeach
        </nav>
    @endif

    @if ($posts->isNotEmpty())
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($posts as $post)
                <article class="group flex flex-col overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl">
                    <a href="{{ \App\Support\UrlStructure::url('post', $post->slug) }}" class="flex flex-1 flex-col">
                        <div class="relative aspect-[16/10] overflow-hidden bg-gray-100">
                            @if ($post->featured_image)
                                <img src="{{ $post->featured_image }}" alt="{{ $post->title }}" loading="lazy" decoding="async" width="800" height="500"
                                    class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                            @else
                                <div class="flex h-full items-center justify-center bg-[var(--me-accent)]/5 text-[var(--me-accent)]">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5A2.25 2.25 0 0 0 21 18.75" /></svg>
                                </div>
                            @endif
                        </div>
                        <div class="flex flex-1 flex-col p-6">
                            <div class="mb-3 flex flex-wrap items-center gap-2">
                                @if ($post->category?->page)
                                    <span class="rounded-full bg-[var(--me-accent)]/10 px-2.5 py-0.5 text-[0.65rem] font-bold uppercase tracking-widest text-[var(--me-accent)]">{{ $post->category->name }}</span>
                                @endif
                                @if ($post->published_at)
                                    <span class="text-xs text-gray-400">{{ $post->published_at->toDisplay() }}</span>
                                @endif
                            </div>
                            <h2 class="text-lg font-extrabold leading-snug text-[#1a1a1a] transition-colors group-hover:text-[var(--me-accent)]">{{ $post->title }}</h2>
                            @php
                                $postShortDescription = $post->getTranslation('short_description', app()->getLocale(), false);
                                $postLegacyDescription = is_array($post->description)
                                    ? ($post->description[app()->getLocale()] ?? $post->description['en'] ?? reset($post->description))
                                    : $post->description;
                                $postDescriptionText = trim(html_entity_decode(strip_tags((string) ($postShortDescription ?: $postLegacyDescription))));
                            @endphp
                            @if ($postDescriptionText !== '')
                                <p class="mt-2 line-clamp-3 text-sm leading-relaxed text-gray-500">{{ \Illuminate\Support\Str::limit($postDescriptionText, 180) }}</p>
                            @endif
                            @if ($post->user?->name)
                                <p class="mt-3 inline-flex items-center gap-1.5 text-xs text-gray-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a8.25 8.25 0 0 1 15 0" /></svg>
                                    {{ $post->user->name }}
                                </p>
                            @endif
                            <div class="mt-auto flex items-center justify-between border-t border-gray-100 pt-5 text-xs text-gray-400">
                                <span>{{ __(':min min read', ['min' => $post->reading_time]) }}</span>
                                <span class="inline-flex items-center gap-1 font-bold uppercase tracking-widest text-[var(--me-accent)]">
                                    {{ __('Read more') }}
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                                </span>
                            </div>
                        </div>
                    </a>
                </article>
            @endforeach
        </div>
        <div class="mt-12">{{ $posts->links() }}</div>
    @else
        <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-gray-200 py-24 text-center">
            <h2 class="text-lg font-extrabold uppercase tracking-wide text-[#1a1a1a]">{{ __('No posts published yet') }}</h2>
            <a href="{{ route('shop') }}" class="mt-6 inline-block bg-[#1a1a1a] px-8 py-3 text-xs font-bold uppercase tracking-widest text-white transition hover:bg-[var(--me-accent)]">{{ __('Continue shopping') }}</a>
        </div>
    @endif
</main>

@include('theme-modern_ecommerce::partials._footer')

@include('frontend.partials._chat-widget')
@include('partials._custom-code-body')
</body>
</html>
