<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locale::direction() }}">
<head>
    @include('partials._head')
    @include('partials._seo-meta')
    @include('partials._custom-code-head')
</head>
<body class="bg-page-bg font-storefront text-sf-text antialiased">

@include('theme-ecommerce::partials._header')

@unless (in_array($page->slug, ['about', 'faq'], true) || \App\Support\PageBlocks::for($page->slug) === 'frontend.contact-form')
@include('theme-ecommerce::partials._breadcrumbs', [
    'crumbs' => [
        ['label' => __('Home'), 'url' => url('/')],
        ['label' => $page->getTranslation('title', 'en', false), 'url' => null],
    ],
])
@endunless

<main>
    @php $block = \App\Support\PageBlocks::for($page->slug); @endphp

    @if ($block === 'frontend.contact-form')
        {{-- Contact gets its own layout: details beside the form. --}}
        @include('theme-ecommerce::partials._contact-page')
    @endif

    @if (in_array($page->slug, ['about', 'faq'], true))
        <div class="mx-auto max-w-7xl px-4 sm:px-6">
            @include('theme-ecommerce::partials._page-banner', [
                'title' => $page->getTranslation('title', app()->getLocale(), true),
                'crumbs' => [
                    ['label' => __('Home'), 'url' => url('/')],
                    ['label' => $page->getTranslation('title', 'en', false), 'url' => null],
                ],
                'icon' => $page->slug === 'faq' ? 'M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z' : 'M11.25 11.25l.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z',
            ])
        </div>
    @endif

    @foreach ($sections as $section)
        @continue(blank($section->localizedCards()))

        <section id="{{ $section->name }}" class="pb-8 pt-0 lg:pb-10">
            <div class="mx-auto max-w-7xl px-4 sm:px-6">
                @if ($page->slug === 'faq')
                    <div class="grid gap-6 lg:grid-cols-[minmax(15rem,0.8fr)_minmax(0,1.6fr)] lg:gap-12">
                        <div class="lg:sticky lg:top-24 lg:self-start">
                            <h2 class="text-2xl font-extrabold tracking-tight text-sf-heading md:text-3xl">{{ $section->name }}</h2>
                            <span class="mt-3 block h-1 w-10 rounded-full bg-brand" aria-hidden="true"></span>
                            <p class="mt-4 max-w-sm text-sm leading-6 text-zinc-500">{{ __('Find answers to common questions. Can’t find what you need?') }}
                                <a href="{{ url('/contact-us') }}" class="font-semibold text-brand underline decoration-brand/30 underline-offset-2 hover:decoration-brand">{{ __('Contact us') }}</a>.
                            </p>
                        </div>
                        <div class="divide-y divide-zinc-100 overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm">
                            @foreach ($section->localizedCards() as $card)
                                <details class="group" @if ($loop->first) open @endif>
                                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 px-5 py-4 text-[15px] font-semibold text-sf-heading transition hover:bg-zinc-50 group-open:text-brand sm:px-6 [&::-webkit-details-marker]:hidden">
                                        <span>{{ $card['title'] }}</span>
                                        <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-zinc-500 transition group-open:rotate-45 group-open:bg-brand group-open:text-white" aria-hidden="true">
                                            <svg class="size-4" viewBox="0 0 20 20" fill="currentColor"><path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z"/></svg>
                                        </span>
                                    </summary>
                                    <div class="px-5 pb-5 pr-14 text-sm leading-7 text-zinc-600 sm:px-6 sm:pr-16">{{ $card['description'] }}</div>
                                </details>
                            @endforeach
                        </div>
                    </div>
                @elseif ($page->slug === 'about')
                    @php
                        $aboutCards = array_values((array) $section->localizedCards());
                        $aboutIsMetrics = preg_match('/^[\d,]+\+?$/', (string) ($aboutCards[0]['title'] ?? '')) === 1;
                    @endphp
                    @if ($aboutIsMetrics)
                        {{-- Numbers: one solid brand band instead of four loose cards. --}}
                        <div class="relative overflow-hidden rounded-3xl bg-brand px-6 py-10 text-white shadow-lg sm:px-10 lg:py-12">
                            <div class="pointer-events-none absolute -right-16 -top-16 size-64 rounded-full bg-white/10" aria-hidden="true"></div>
                            <div class="pointer-events-none absolute -bottom-20 -left-10 size-56 rounded-full bg-white/5" aria-hidden="true"></div>
                            <div class="relative mb-8 text-center">
                                <span class="text-xs font-semibold uppercase tracking-[0.2em] text-white/70">{{ __('Our impact') }}</span>
                                <h2 class="mt-2 text-2xl font-extrabold tracking-tight md:text-3xl">{{ $section->name }}</h2>
                            </div>
                            <dl class="relative grid grid-cols-2 gap-y-8 lg:grid-cols-4 lg:divide-x lg:divide-white/20">
                                @foreach ($aboutCards as $card)
                                    <div class="px-4 text-center">
                                        <dt class="text-3xl font-extrabold tracking-tight sm:text-4xl lg:text-5xl">{{ $card['title'] }}</dt>
                                        <dd class="mt-2 text-sm font-medium text-white/80">{{ $card['description'] }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </div>
                    @else
                        {{-- Story: intro on the left, the rest as numbered cards on the right. --}}
                        <div class="grid items-start gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)] lg:gap-14">
                            <div>
                                <h2 class="text-3xl font-extrabold tracking-tight text-sf-heading md:text-4xl">{{ $section->name }}</h2>
                                <span class="mt-4 block h-1 w-12 rounded-full bg-brand" aria-hidden="true"></span>
                                @if (! empty($aboutCards[0]))
                                    <p class="mt-5 text-base leading-7 text-zinc-600">{{ $aboutCards[0]['description'] }}</p>
                                @endif
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                @foreach (array_slice($aboutCards, 1) as $card)
                                    <div class="group rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-brand/30 hover:shadow-md">
                                        <span class="flex size-10 items-center justify-center rounded-xl bg-brand/10 text-sm font-bold text-brand transition group-hover:bg-brand group-hover:text-white">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                        <h3 class="mt-4 text-base font-bold text-sf-heading">{{ $card['title'] }}</h3>
                                        <p class="mt-2 text-sm leading-6 text-zinc-600">{{ $card['description'] }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @else
                <div class="mb-5 flex items-center gap-3">
                    <span class="h-7 w-1 rounded-full bg-brand" aria-hidden="true"></span>
                    <h2 class="text-left text-xl font-bold tracking-tight text-sf-heading md:text-2xl">{{ $section->name }}</h2>
                </div>
                @php($sectionCards = $section->localizedCards())
                @if ($page->slug === 'about' && preg_match('/^[\d,]+\+?$/', (string) ($sectionCards[0]['title'] ?? '')) !== 1)
                    <p class="max-w-5xl text-[15px] leading-7 text-zinc-600">
                        @foreach ($sectionCards as $card)
                            @if ($card['title'])
                                <span class="font-semibold text-sf-heading">{{ $card['title'] }}:</span>
                            @endif
                            {{ $card['description'] }}@unless ($loop->last) <span class="mx-1 text-brand/50" aria-hidden="true">•</span> @endunless
                        @endforeach
                    </p>
                @else
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($sectionCards as $card)
                        @php($isAboutMetric = $page->slug === 'about' && preg_match('/^[\d,]+\+?$/', (string) ($card['title'] ?? '')) === 1)
                        <div @class([
                            'group overflow-hidden rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-brand/30 hover:shadow-md',
                            'min-h-32' => $isAboutMetric,
                        ])>
                            @if ($card['image'])
                                <div class="relative -mx-5 -mt-5 mb-4 aspect-square overflow-hidden bg-zinc-100">
                                    <img src="{{ $card['image'] }}" alt="{{ $card['title'] }}"
                                        class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                </div>
                            @endif
                            @if ($card['title'])
                                <h3 @class([
                                    'font-semibold text-sf-heading',
                                    'text-3xl font-extrabold tracking-tight text-brand' => $isAboutMetric,
                                ])>{{ $card['title'] }}</h3>
                            @endif
                            @if ($card['description'])
                                <p class="mt-2 text-sm leading-6 text-zinc-600">{{ $card['description'] }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
                @endif
                @endif
            </div>
        </section>
    @endforeach

    @if ($block && $block !== 'frontend.contact-form')
        <section class="mx-auto max-w-xl px-6 py-16">
            @livewire($block)
        </section>
    @endif
</main>

@include('theme-ecommerce::partials._footer')

@include('frontend.partials._chat-widget')
@include('partials._custom-code-body')
</body>
</html>
