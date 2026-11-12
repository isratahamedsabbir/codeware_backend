<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locale::direction() }}">
<head>
    @include('partials._head')
    {{-- A 404 must never be indexed, so partials._seo-meta is deliberately absent. --}}
    <meta name="robots" content="noindex, nofollow">
    @include('partials._custom-code-head')
    @include('theme-news::partials._theme-tokens')
</head>
<body class="bg-white text-zinc-800 antialiased">

@include('theme-news::partials._header')

<main class="mx-auto max-w-3xl px-4 py-24 text-center sm:px-6">
    <p class="nw-serif text-7xl font-black" style="color: var(--nw-accent)">404</p>
    <h1 class="nw-serif mt-4 text-3xl font-bold text-zinc-900">{{ __('This story could not be found') }}</h1>
    <p class="mt-3 text-zinc-600">{{ __('The page you requested may have been moved, renamed, or removed.') }}</p>
    <a href="{{ url('/') }}" class="mt-8 inline-block px-6 py-3 text-sm font-semibold text-white" style="background: var(--nw-accent)">{{ __('Back to the front page') }}</a>
</main>

@include('theme-news::partials._footer')

@include('frontend.partials._chat-widget')
@include('partials._custom-code-body')
</body>
</html>
