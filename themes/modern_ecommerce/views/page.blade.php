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
    $block = \App\Support\PageBlocks::for($page->slug);
    $pageTitle = $page->getTranslation('title', app()->getLocale(), false);
@endphp

@if ($block === 'frontend.contact-form')
    @include('theme-modern_ecommerce::partials._contact-page')
@else
    @include('theme-modern_ecommerce::partials._page-banner', [
        'title' => $pageTitle,
        'crumbs' => [
            ['label' => __('Home'), 'url' => url('/')],
            ['label' => $pageTitle, 'url' => null],
        ],
    ])

    <main class="mx-auto max-w-[1400px] px-4 pb-16 pt-12 sm:px-8">
        @foreach ($sections as $section)
            @continue(blank($section->localizedCards()))
            @php $cards = $section->localizedCards(); @endphp

            <section id="{{ $section->name }}" class="pb-12">
                @if ($page->slug === 'faq')
                    <div class="grid gap-8 lg:grid-cols-[minmax(15rem,0.8fr)_minmax(0,1.6fr)] lg:gap-14">
                        <div class="lg:sticky lg:top-24 lg:self-start">
                            <h2 class="text-3xl font-black uppercase tracking-tight text-[#1a1a1a]">{{ $section->name }}</h2>
                            <span class="mt-3 block h-1 w-12 bg-[var(--me-accent)]" aria-hidden="true"></span>
                            <p class="mt-4 max-w-sm text-sm leading-6 text-gray-500">
                                {{ __('Find answers to common questions. Can’t find what you need?') }}
                                <a href="{{ url('/contact') }}" class="font-bold text-[var(--me-accent)] hover:underline">{{ __('Contact us') }}</a>.
                            </p>
                        </div>
                        <div class="divide-y divide-gray-100 overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                            @foreach ($cards as $card)
                                <details class="group" @if ($loop->first) open @endif>
                                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-6 py-4 text-[15px] font-bold text-[#1a1a1a] transition hover:bg-gray-50 group-open:text-[var(--me-accent)] [&::-webkit-details-marker]:hidden">
                                        <span>{{ $card['title'] }}</span>
                                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-500 transition group-open:rotate-45 group-open:bg-[var(--me-accent)] group-open:text-white" aria-hidden="true">
                                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z"/></svg>
                                        </span>
                                    </summary>
                                    <div class="px-6 pb-5 pr-16 text-sm leading-7 text-gray-600">{{ $card['description'] }}</div>
                                </details>
                            @endforeach
                        </div>
                    </div>
                @elseif ($page->slug === 'about' && preg_match('/^[\d,]+\+?$/', (string) ($cards[0]['title'] ?? '')) === 1)
                    <div class="relative overflow-hidden rounded-3xl bg-[#1a1a1a] px-6 py-12 text-white sm:px-10 lg:py-16">
                        <div class="pointer-events-none absolute -right-16 -top-16 h-64 w-64 rounded-full bg-[var(--me-accent)]/20" aria-hidden="true"></div>
                        <div class="pointer-events-none absolute -bottom-20 -left-10 h-56 w-56 rounded-full bg-white/5" aria-hidden="true"></div>
                        <div class="relative mb-10 text-center">
                            <span class="text-[0.65rem] font-bold uppercase tracking-[0.25em] text-[var(--me-accent)]">{{ __('Our impact') }}</span>
                            <h2 class="mt-2 text-2xl font-black uppercase tracking-tight md:text-3xl">{{ $section->name }}</h2>
                        </div>
                        <dl class="relative grid grid-cols-2 gap-y-8 lg:grid-cols-4 lg:divide-x lg:divide-white/10">
                            @foreach ($cards as $card)
                                <div class="px-4 text-center">
                                    <dt class="text-4xl font-black tracking-tight sm:text-5xl">{{ $card['title'] }}</dt>
                                    <dd class="mt-2 text-sm font-medium text-white/60">{{ $card['description'] }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    </div>
                @else
                    <div class="mb-8 flex items-center gap-3">
                        <span class="h-7 w-1 bg-[var(--me-accent)]" aria-hidden="true"></span>
                        <h2 class="text-2xl font-black uppercase tracking-tight text-[#1a1a1a]">{{ $section->name }}</h2>
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ($cards as $card)
                            <div class="group overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl">
                                @if ($card['image'])
                                    <div class="aspect-[4/3] overflow-hidden bg-gray-100">
                                        <img src="{{ $card['image'] }}" alt="{{ $card['title'] }}" loading="lazy" decoding="async"
                                            class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                                    </div>
                                @endif
                                <div class="p-5">
                                    @if ($card['title'])
                                        <h3 class="font-extrabold text-[#1a1a1a]" @if ($card['text_color']) style="color: {{ $card['text_color'] }}" @endif>{{ $card['title'] }}</h3>
                                    @endif
                                    @if ($card['subtitle'])
                                        <p class="text-xs font-medium text-gray-400">{{ $card['subtitle'] }}</p>
                                    @endif
                                    @if ($card['description'])
                                        <p class="mt-2 text-sm leading-6 text-gray-500">{{ $card['description'] }}</p>
                                    @endif
                                    @if ($card['button_label'] && $card['button_url'])
                                        <a href="{{ \App\Support\HeroSlides::safeUrl($card['button_url']) ?: url('/') }}" class="mt-3 inline-block rounded-full bg-[var(--me-accent)] px-4 py-1.5 text-xs font-bold uppercase tracking-wider text-white">{{ $card['button_label'] }}</a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </section>
        @endforeach
    </main>
@endif

@include('theme-modern_ecommerce::partials._footer')

@include('frontend.partials._chat-widget')
@include('partials._custom-code-body')
</body>
</html>
