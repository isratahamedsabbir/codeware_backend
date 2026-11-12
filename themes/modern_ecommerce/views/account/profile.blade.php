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
    'title' => __('Profile'),
    'subtitle' => __('Update your name, email and password.'),
    'crumbs' => [
        ['label' => __('Home'), 'url' => url('/')],
        ['label' => __('My account'), 'url' => route('account.dashboard')],
        ['label' => __('Profile'), 'url' => null],
    ],
])

<main class="mx-auto max-w-[1400px] px-4 pb-16 pt-10 sm:px-8">
    <div class="flex flex-col gap-8 md:flex-row">
        @include('theme-modern_ecommerce::account.partials._account-nav')

        <div class="w-full max-w-xl flex-1">
            <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm sm:p-8">
                <livewire:frontend.account.profile />
            </div>
        </div>
    </div>
</main>

@include('theme-modern_ecommerce::partials._footer')

@include('frontend.partials._chat-widget')
@include('partials._custom-code-body')
</body>
</html>
