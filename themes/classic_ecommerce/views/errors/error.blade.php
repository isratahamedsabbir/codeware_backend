{{-- Generic error page for classic_ecommerce: same chrome as its 404, printing the $code/$title/$message Themes::errorData() hands it (403, 419, 429, 500, 503, ...). Themes::errorView() uses it for any status without its own errors/{code}.blade.php. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locale::direction() }}">
<head>
    @include('partials._head')
    {{-- A 404 must never be indexed, and must never claim a canonical URL for a
         page that does not exist — so partials._seo-meta is deliberately absent
         here (it derives both from $page). --}}
    <meta name="robots" content="noindex, nofollow">
    @include('partials._custom-code-head')
</head>
<body class="bg-page-bg font-storefront text-sf-text antialiased">

@include('theme-classic_ecommerce::partials._header', [
    'menuItems' => \App\Support\Frontend::menuItems(),
])

<main>
    <section class="mx-auto flex max-w-4xl flex-col items-center gap-10 px-4 py-20 text-center sm:flex-row sm:gap-16 sm:px-6 sm:text-left">
        <h1 class="text-8xl font-black leading-none tracking-tighter text-sf-heading">{{ $code }}</h1>

        <div>
            <h2 class="text-2xl font-bold text-sf-heading sm:text-3xl">{{ $title }}</h2>
            <p class="mt-3 text-sf-text/70">{{ $message }}</p>

            <div class="mt-8 flex flex-wrap items-center justify-center gap-3 sm:justify-start">
                <a href="{{ route('shop') }}" class="rounded-card bg-sf-button px-6 py-3 text-sm font-semibold text-sf-button-text transition hover:opacity-90">
                    {{ __('Browse the shop') }}
                </a>
                <a href="{{ url('/') }}" class="rounded-card border border-zinc-200 px-6 py-3 text-sm font-semibold text-sf-heading transition hover:bg-zinc-50">
                    {{ __('Back to homepage') }}
                </a>
            </div>
        </div>
    </section>
</main>

@include('theme-classic_ecommerce::partials._footer')

@include('frontend.partials._chat-widget')
@include('partials._custom-code-body')
</body>
</html>
