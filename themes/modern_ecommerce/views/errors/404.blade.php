{{--
    The modern theme's 404 — its own chrome, accent and type, so a dead product
    or page URL still looks like the shop. A 404 must never be indexed or claim
    a canonical URL, so partials._seo-meta is deliberately absent.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locale::direction() }}">
<head>
    @include('partials._head')
    <meta name="robots" content="noindex, nofollow">
    @include('partials._custom-code-head')
    @include('theme-modern_ecommerce::partials._theme-tokens')
</head>
<body class="bg-white text-gray-900 antialiased">

@include('theme-modern_ecommerce::partials._header')

<main>
    <section class="mx-auto flex max-w-4xl flex-col items-center gap-10 px-4 py-24 text-center sm:flex-row sm:gap-16 sm:px-8 sm:text-left">
        <h1 class="text-8xl font-black leading-none tracking-tighter text-[#1a1a1a] sm:text-9xl">404</h1>

        <div>
            <h2 class="text-2xl font-black uppercase tracking-tight text-[#1a1a1a] sm:text-3xl">{{ __('Sorry, page not found') }}</h2>
            <p class="mt-3 leading-relaxed text-gray-500">{{ __('The page you requested could not be found. It may have been moved, renamed, or no longer in stock.') }}</p>

            <div class="mt-8 flex flex-wrap items-center justify-center gap-3 sm:justify-start">
                <a href="{{ route('shop') }}" class="bg-[var(--me-accent)] px-8 py-3 text-xs font-bold uppercase tracking-widest text-white transition hover:opacity-90">
                    {{ __('Browse the shop') }}
                </a>
                <a href="{{ url('/') }}" class="border border-gray-200 px-8 py-3 text-xs font-bold uppercase tracking-widest text-[#1a1a1a] transition hover:bg-gray-50">
                    {{ __('Back to homepage') }}
                </a>
            </div>
        </div>
    </section>
</main>

@include('theme-modern_ecommerce::partials._footer')

@include('frontend.partials._chat-widget')
@include('partials._custom-code-body')
</body>
</html>
