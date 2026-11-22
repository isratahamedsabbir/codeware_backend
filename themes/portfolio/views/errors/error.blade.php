{{-- Generic error page for portfolio: same chrome as its 404, printing the $code/$title/$message Themes::errorData() hands it (403, 419, 429, 500, 503, ...). Themes::errorView() uses it for any status without its own errors/{code}.blade.php. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials._head')
    <link rel="stylesheet" href="{{ asset('themes/portfolio/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('themes/portfolio/css/polish.css') }}?v=9">
    {{-- A 404 must never be indexed, and must never claim a canonical URL for a
         page that does not exist � so partials._seo-meta is deliberately absent
         here (it derives both from $page). --}}
    <meta name="robots" content="noindex, nofollow">
    <style>[x-cloak]{display:none!important}</style>
    @include('partials._custom-code-head')
</head>
<body class="theme-portfolio antialiased">

@php
    $siteName = \App\Models\Setting::get('site_name', config('app.name'));
    $siteIcon = \App\Models\Setting::get('site_icon_white') ?: \App\Models\Setting::get('site_icon');

    // Read by the shared header and footer partials - see
    // Themes\Portfolio\Support\PortfolioSocials.
    $socials = \Themes\Portfolio\Support\PortfolioSocials::all();
@endphp

@include('theme-portfolio::partials._header')

<main>
    <section class="pf-grid-bg relative flex min-h-[80vh] items-center overflow-hidden px-6 pt-32 pb-20">
        <div class="pf-glow pointer-events-none absolute top-0 left-1/2 h-105 w-105 -translate-x-1/2 rounded-full opacity-50" aria-hidden="true"></div>

        <div class="relative mx-auto grid max-w-4xl items-center gap-12 md:grid-cols-2">
            <h1 class="pf-gradient-text pf-mono text-8xl leading-none font-bold sm:text-9xl">{{ $code }}</h1>

            <div>
                <span class="pf-badge pf-mono mb-6 inline-flex px-4 py-2 text-[11px] font-medium tracking-wide">
                    {{ __('Error') }} &middot; {{ $code }}
                </span>
                <h2 class="pf-heading text-2xl font-bold sm:text-3xl">{{ $title }}</h2>
                <p class="mt-3 text-sm leading-relaxed text-(--pf-text-muted)">{{ $message }}</p>

                <a href="{{ url('/') }}" class="pf-btn-solid pf-mono mt-8 px-7 py-3 text-xs tracking-wider uppercase">
                    {{ __('Back to homepage') }}
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6" />
                    </svg>
                </a>
            </div>
        </div>
    </section>
</main>

@include('theme-portfolio::partials._footer')
@include('partials._custom-code-body')
</body>
</html>
