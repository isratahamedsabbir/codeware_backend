{{-- Generic error page for default: same chrome as its 404, printing the $code/$title/$message Themes::errorData() hands it (403, 419, 429, 500, 503, ...). Themes::errorView() uses it for any status without its own errors/{code}.blade.php. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('partials._head')
    {{-- A 404 must never be indexed, and must never claim a canonical URL for a
         page that does not exist — so partials._seo-meta is deliberately absent
         here (it derives both from $page). --}}
    <meta name="robots" content="noindex, nofollow">
    @include('partials._custom-code-head')
</head>
<body class="bg-white text-zinc-800 antialiased">

@include('theme-default::partials._header', [
    'navPages' => \App\Support\Frontend::navPages(),
    'showVendorLogin' => \App\Support\Frontend::showVendorLogin(),
    'showDeliveryLogin' => \App\Support\Frontend::showDeliveryLogin(),
])

<main>
    <section class="mx-auto flex max-w-4xl flex-col items-center gap-8 px-6 py-24 text-center sm:flex-row sm:gap-16 sm:text-left">
        <h1 class="text-7xl font-extrabold leading-none tracking-tight text-zinc-900 sm:text-8xl">{{ $code }}</h1>

        <div>
            <h2 class="text-2xl font-bold text-zinc-900 sm:text-3xl">{{ $title }}</h2>
            <p class="mt-3 text-zinc-600">{{ $message }}</p>

            <a href="{{ url('/') }}" class="mt-8 inline-block rounded-lg bg-primary px-6 py-3 text-sm font-semibold text-white transition hover:opacity-90">
                {{ __('Back to homepage') }}
            </a>
        </div>
    </section>
</main>

@include('theme-default::partials._footer')

@include('frontend.partials._chat-widget')
@include('partials._custom-code-body')
</body>
</html>
