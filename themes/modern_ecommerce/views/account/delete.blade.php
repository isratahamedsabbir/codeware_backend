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
    'title' => __('Delete account'),
    'subtitle' => __('Permanently remove your profile and sign-in access.'),
    'crumbs' => [
        ['label' => __('Home'), 'url' => url('/')],
        ['label' => __('My account'), 'url' => route('account.dashboard')],
        ['label' => __('Delete account'), 'url' => null],
    ],
])

<main class="mx-auto max-w-[1400px] px-4 pb-16 pt-10 sm:px-8">
    <div class="flex flex-col gap-8 md:flex-row">
        @if (auth()->check())
            @include('theme-modern_ecommerce::account.partials._account-nav')
        @endif

        <div class="w-full max-w-xl flex-1">
            <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm sm:p-8">
                <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-5">
                    <h2 class="text-[0.7rem] font-bold uppercase tracking-widest text-amber-900">{{ __('Before you delete your account') }}</h2>
                    <ul class="mt-3 list-disc space-y-1.5 pl-5 text-sm leading-6 text-amber-950/80">
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
                    <h2 class="text-base font-black uppercase tracking-wide text-[#1a1a1a]">{{ __('How to delete your account') }}</h2>
                    <ol class="mt-3 list-decimal space-y-2 pl-5 text-sm leading-6 text-gray-600">
                        <li>{{ __('Sign in to your :app account.', ['app' => config('app.name')]) }}</li>
                        <li>{{ __('Open the Delete account page from your account menu.') }}</li>
                        <li>{{ __('Confirm your current password and select Delete account permanently.') }}</li>
                    </ol>
                    <p class="mt-4 text-sm leading-6 text-gray-600">
                        {{ __('Your profile and sign-in access will be deleted. Order records are kept for store records.') }}
                    </p>
                    <a href="{{ route('login') }}"
                        class="mt-6 inline-block bg-[#1a1a1a] px-8 py-3 text-xs font-bold uppercase tracking-widest text-white transition hover:bg-[var(--me-accent)]">
                        {{ __('Sign in to delete your account') }}
                    </a>
                @endauth
            </div>
        </div>
    </div>
</main>

@include('theme-modern_ecommerce::partials._footer')

@include('frontend.partials._chat-widget')
@include('partials._custom-code-body')
</body>
</html>
