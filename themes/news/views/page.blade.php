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

<main class="mx-auto max-w-4xl px-4 pt-10 sm:px-6">
    <h1 class="nw-serif border-b-2 border-zinc-900 pb-3 text-3xl font-bold text-zinc-900 sm:text-4xl">{{ $page->getTranslation('title', 'en', false) }}</h1>

    @foreach ($sections as $section)
        @continue(blank($section->localizedCards()))

        <section id="{{ $section->name }}" class="mt-8">
            <div class="grid gap-6 sm:grid-cols-2">
                @foreach ($section->localizedCards() as $card)
                    <div>
                        @if ($card['image'])
                            <img src="{{ $card['image'] }}" alt="{{ $card['title'] ?? '' }}" class="mb-3 aspect-[16/10] w-full object-cover" loading="lazy">
                        @endif
                        @if ($card['title'])
                            <h2 class="nw-serif text-xl font-bold text-zinc-900" @if ($card['text_color']) style="color: {{ $card['text_color'] }}" @endif>{{ $card['title'] }}</h2>
                        @endif
                        @if ($card['subtitle'])
                            <p class="text-xs font-medium text-zinc-500">{{ $card['subtitle'] }}</p>
                        @endif
                        @if ($card['description'])
                            <p class="mt-1 text-zinc-600">{{ $card['description'] }}</p>
                        @endif
                        @if ($card['button_label'] && $card['button_url'])
                            <a href="{{ \App\Support\HeroSlides::safeUrl($card['button_url']) ?: url('/') }}" class="mt-3 inline-block px-4 py-2 text-xs font-semibold text-white" style="background: var(--nw-accent)">{{ $card['button_label'] }}</a>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach

    @if ($block = \App\Support\PageBlocks::for($page->slug))
        <section class="mx-auto mt-10 max-w-xl">
            @livewire($block)
        </section>
    @endif
</main>

@include('theme-news::partials._footer')

@include('frontend.partials._chat-widget')
@include('partials._custom-code-body')
</body>
</html>
