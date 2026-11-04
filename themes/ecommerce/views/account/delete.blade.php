<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locale::direction() }}">
<head>
    @include('partials._head')
    @include('partials._seo-meta')
    @include('partials._custom-code-head')
</head>
<body class="bg-page-bg font-storefront text-sf-text antialiased">

@include('theme-ecommerce::partials._header')

@php
    $crumbs = [
        ['label' => __('Home'), 'url' => route('home')],
        ['label' => __('My Account'), 'url' => route('account.dashboard')],
        ['label' => __('Delete account'), 'url' => null],
    ];
@endphp

<main>
    @include('theme-ecommerce::partials._breadcrumbs', ['crumbs' => $crumbs])

    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6">
        <div class="flex flex-col gap-6 md:flex-row">
            @if (auth()->check())
                @include('theme-ecommerce::account.partials._account-nav')
            @endif

            <div class="w-full max-w-xl flex-1">
                <div class="rounded-card border border-zinc-200 bg-white p-6">
                    <div class="mb-5 rounded-lg border border-amber-200 bg-amber-50 p-4">
                        <h2 class="text-sm font-bold text-amber-900">{{ __('Before you delete your account') }}</h2>
                        <ul class="mt-2 list-disc space-y-1.5 pl-5 text-sm leading-6 text-amber-950/80">
                            <li>{{ __('You will lose sign-in access and may no longer be able to view your account order history or track orders here.') }}</li>
                            <li>{{ __('Saved favorites and chat history linked to your account may be permanently deleted.') }}</li>
                            <li>{{ __('Deleting your account does not cancel existing or pending orders. Contact the store if you need help with an open order.') }}</li>
                            <li>{{ __('Order records may be retained by the store and will no longer be linked to your account.') }}</li>
                            <li>{{ __('Account deletion is permanent and cannot be undone.') }}</li>
                        </ul>
                    </div>

                    @auth
                        <livewire:frontend.account.delete-account />
                    @else
                        <h2 class="text-base font-bold text-sf-text">{{ __('How to delete your account') }}</h2>
                        <ol class="mt-3 list-decimal space-y-2 pl-5 text-sm leading-6 text-zinc-600">
                            <li>{{ __('Sign in to your :app account.', ['app' => config('app.name')]) }}</li>
                            <li>{{ __('Open the Delete account page from your account menu.') }}</li>
                            <li>{{ __('Confirm your current password and select Delete account permanently.') }}</li>
                        </ol>
                        <p class="mt-4 text-sm leading-6 text-zinc-600">
                            {{ __('Your profile and sign-in access will be deleted. Order records are kept for store records.') }}
                        </p>
                        <a href="{{ route('login') }}"
                            class="mt-5 inline-flex rounded-md bg-sf-button px-5 py-2.5 text-sm font-semibold text-sf-button-text transition hover:opacity-90">
                            {{ __('Sign in to delete your account') }}
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </div>
</main>

@include('theme-ecommerce::partials._footer')

@include('frontend.partials._chat-widget')
@include('partials._custom-code-body')
</body>
</html>
