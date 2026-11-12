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

@include('theme-modern_ecommerce::partials._page-banner', [
    'title' => __('Checkout'),
    'subtitle' => auth()->check() ? null : __('Checking out as a guest. We will create an account for the email you enter so you can track this order — you can set a password for it any time from the "Forgot password" link.'),
    'crumbs' => [
        ['label' => __('Home'), 'url' => url('/')],
        ['label' => __('My cart'), 'url' => route('cart')],
        ['label' => __('Checkout'), 'url' => null],
    ],
])

<main class="mx-auto max-w-[1400px] px-4 pb-12 pt-8 sm:px-8">
    <livewire:frontend.checkout />
</main>

@include('theme-modern_ecommerce::partials._footer')

@include('frontend.partials._chat-widget')
@include('partials._custom-code-body')
</body>
</html>
