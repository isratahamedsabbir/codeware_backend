<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locale::direction() }}">
<head>
    @include('partials._head')
    @include('partials._seo-meta')
    @include('partials._custom-code-head')
</head>
<body class="bg-page-bg font-storefront text-sf-text antialiased">

@include('theme-classic_ecommerce::partials._header')

@php
    $crumbs = [
        ['label' => __('Home'), 'url' => route('home')],
        ['label' => __('My cart'), 'url' => route('cart')],
        ['label' => __('Checkout'), 'url' => null],
    ];
@endphp

<div class="mx-auto max-w-7xl px-4 sm:px-6">
    @include('theme-classic_ecommerce::partials._page-banner', ['title' => __('Checkout'), 'crumbs' => $crumbs, 'aside' => auth()->check() ? null : __('Checking out as a guest. We will create an account for the email you enter so you can track this order — you can set a password for it any time from the "Forgot password" link.'), 'icon' => 'M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z'])
</div>

<livewire:frontend.checkout />

@include('theme-classic_ecommerce::partials._footer')

@include('frontend.partials._chat-widget')
@include('partials._custom-code-body')
</body>
</html>