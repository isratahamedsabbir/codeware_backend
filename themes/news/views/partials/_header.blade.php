{{--
    News masthead: a thin top bar (date + tagline), the site name set large in
    serif, and a category nav. Shared by every page of the theme, 404 included,
    so a dead link still leaves the reader a way back to the news.
--}}
@php
    $siteName = \App\Models\Setting::get('site_name', config('app.name'));
    $siteIcon = \App\Models\Setting::get('site_icon');
    $tagline = \App\Support\ThemeSettings::text('news', 'theme_news_tagline');
    $blogOn = \App\Support\Features::enabled('blog');

    $navCategories = $blogOn
        ? \App\Models\PostCategory::where('status', 'active')->with('page')->orderBy('sort_order')->limit(7)->get()->filter(fn ($c) => $c->page !== null)
        : collect();

    $navPages = \App\Support\Frontend::navPages()
        ->filter(fn ($p) => in_array($p->slug, ['about', 'contact', 'faq'], true));

    $activeCategory = (string) request()->query('category', '');
    $linkBase = 'py-3 text-sm font-semibold';
@endphp

<header class="border-b border-zinc-200 bg-white" x-data="{ open: false }">
    <div class="border-b border-zinc-100 bg-zinc-50">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-1.5 text-xs text-zinc-500 sm:px-6">
            <time datetime="{{ now()->toDateString() }}">{{ now()->setTimezone(display_timezone())->translatedFormat('l, j F Y') }}</time>
            @if ($tagline)
                <span class="hidden truncate sm:block">{{ $tagline }}</span>
            @endif
        </div>
    </div>

    <div class="mx-auto max-w-6xl px-4 py-5 sm:px-6 sm:py-7">
        <a href="{{ url('/') }}" class="flex items-center justify-center gap-3">
            @if ($siteIcon)
                <img src="{{ $siteIcon }}" alt="" class="h-10 w-auto">
            @endif
            <span class="nw-serif text-3xl font-black tracking-tight text-zinc-900 sm:text-5xl">{{ $siteName }}</span>
        </a>
    </div>

    <div class="border-t-2" style="border-top-color: var(--nw-accent)">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 sm:px-6">
            <nav aria-label="{{ __('Sections') }}" class="hidden flex-wrap items-center gap-x-6 md:flex">
                <a href="{{ url('/') }}" class="{{ $linkBase }} {{ request()->routeIs('home') ? 'text-zinc-900' : 'text-zinc-600 hover:text-zinc-900' }}">{{ __('Home') }}</a>
                @if ($blogOn)
                    <a href="{{ route('blog') }}" class="{{ $linkBase }} {{ request()->routeIs('blog') && $activeCategory === '' ? 'text-zinc-900' : 'text-zinc-600 hover:text-zinc-900' }}">{{ __('Latest') }}</a>
                @endif
                @foreach ($navCategories as $category)
                    <a href="{{ route('blog', ['category' => $category->page->slug]) }}"
                        class="{{ $linkBase }} {{ $activeCategory === $category->page->slug ? 'text-zinc-900' : 'text-zinc-600 hover:text-zinc-900' }}">{{ $category->name }}</a>
                @endforeach
                @foreach ($navPages as $navPage)
                    <a href="{{ route('page', $navPage->slug) }}"
                        class="{{ $linkBase }} {{ ($currentSlug ?? null) === $navPage->slug ? 'text-zinc-900' : 'text-zinc-600 hover:text-zinc-900' }}">{{ $navPage->getTranslation('title', 'en', false) }}</a>
                @endforeach
            </nav>

            <button type="button" class="{{ $linkBase }} text-zinc-700 md:hidden" x-on:click="open = ! open" :aria-expanded="open">
                <span x-text="open ? '{{ __('Close') }}' : '{{ __('Menu') }}'">{{ __('Menu') }}</span>
            </button>

            @auth
                @can('access-admin')
                    <a href="{{ config('app.admin_url') }}" class="py-3 text-xs font-semibold text-zinc-500 hover:text-zinc-900">{{ __('Admin Dashboard') }}</a>
                @endcan
            @endauth
        </div>

        <nav x-show="open" x-cloak class="border-t border-zinc-100 bg-white px-4 py-2 md:hidden" aria-label="{{ __('Sections') }}">
            <a href="{{ url('/') }}" class="block py-2 text-sm font-semibold text-zinc-700">{{ __('Home') }}</a>
            @if ($blogOn)
                <a href="{{ route('blog') }}" class="block py-2 text-sm font-semibold text-zinc-700">{{ __('Latest') }}</a>
            @endif
            @foreach ($navCategories as $category)
                <a href="{{ route('blog', ['category' => $category->page->slug]) }}" class="block py-2 text-sm font-semibold text-zinc-700">{{ $category->name }}</a>
            @endforeach
            @foreach ($navPages as $navPage)
                <a href="{{ route('page', $navPage->slug) }}" class="block py-2 text-sm font-semibold text-zinc-700">{{ $navPage->getTranslation('title', 'en', false) }}</a>
            @endforeach
        </nav>
    </div>
</header>
