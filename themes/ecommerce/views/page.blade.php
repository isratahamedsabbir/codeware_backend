<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locale::direction() }}">
<head>
    @include('partials._head')
    @include('partials._seo-meta')
    @include('partials._custom-code-head')
</head>
<body class="bg-page-bg font-storefront text-sf-text antialiased">

@include('theme-ecommerce::partials._header')

@include('theme-ecommerce::partials._breadcrumbs', [
    'crumbs' => [
        ['label' => __('Home'), 'url' => url('/')],
        ['label' => $page->getTranslation('title', 'en', false), 'url' => null],
    ],
])

<main>
    @php $block = \App\Support\PageBlocks::for($page->slug); @endphp

    @if ($block === 'frontend.contact-form')
        {{-- Contact gets its own layout: details beside the form. --}}
        @include('theme-ecommerce::partials._contact-page')
    @endif

    @foreach ($sections as $section)
        @continue(blank($section->localizedCards()))

        <section id="{{ $section->name }}" class="pb-8 pt-0 lg:pb-10">
            <div class="mx-auto max-w-7xl px-4 sm:px-6">
                @if ($page->slug === 'faq')
                    <div class="grid gap-6 lg:grid-cols-[minmax(15rem,0.8fr)_minmax(0,1.5fr)] lg:gap-12">
                        <div class="lg:pt-1">
                            <h2 class="text-xl font-bold tracking-tight text-sf-heading md:text-2xl">{{ $section->name }}</h2>
                            <p class="mt-2 max-w-sm text-sm leading-6 text-zinc-500">{{ __('Find answers to common questions. Can’t find what you need?') }}
                                <a href="{{ url('/contact-us') }}" class="font-medium text-brand underline decoration-brand/30 underline-offset-2 hover:decoration-brand">{{ __('Contact us') }}</a>.
                            </p>
                        </div>
                        <div class="divide-y divide-zinc-200 border-y border-zinc-200">
                            @foreach ($section->localizedCards() as $card)
                                <details class="group">
                                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 py-4 text-sm font-semibold text-sf-heading [&::-webkit-details-marker]:hidden">
                                        <span>{{ $card['title'] }}</span>
                                        <span class="flex size-5 shrink-0 items-center justify-center rounded-full border border-zinc-300 text-base font-normal leading-none text-zinc-500 transition group-open:border-brand group-open:text-brand" aria-hidden="true">
                                            <span class="group-open:hidden">+</span><span class="hidden group-open:inline">−</span>
                                        </span>
                                    </summary>
                                    <div class="pb-4 pr-9 text-sm leading-6 text-zinc-600">{{ $card['description'] }}</div>
                                </details>
                            @endforeach
                        </div>
                    </div>
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
