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

@php
    $user = auth()->user();
@endphp

@include('theme-modern_ecommerce::partials._page-banner', [
    'title' => __('My account'),
    'subtitle' => __('Track orders, update your details and manage your account.'),
    'crumbs' => [['label' => __('Home'), 'url' => url('/')], ['label' => __('My account'), 'url' => null]],
])

<main class="mx-auto max-w-[1400px] px-4 pb-16 pt-10 sm:px-8">
    <div class="flex flex-col gap-8 md:flex-row">
        @include('theme-modern_ecommerce::account.partials._account-nav')

        <div class="min-w-0 flex-1 space-y-8">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="relative overflow-hidden rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                    <span class="absolute right-5 top-5 flex h-11 w-11 items-center justify-center rounded-full bg-[var(--me-accent)]/10 text-[var(--me-accent)]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                    </span>
                    <h2 class="text-[0.7rem] font-bold uppercase tracking-widest text-gray-400">{{ __('Profile') }}</h2>
                    <p class="mt-3 text-lg font-extrabold text-[#1a1a1a]">{{ $user->name }}</p>
                    <p class="text-sm text-gray-500">{{ $user->email }}</p>
                    <a href="{{ route('account.profile') }}" class="mt-4 inline-flex items-center gap-1.5 text-[0.7rem] font-bold uppercase tracking-widest text-[var(--me-accent)] hover:underline">
                        {{ __('Edit profile') }}
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                    </a>
                </div>

                <div class="relative overflow-hidden rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                    <span class="absolute right-5 top-5 flex h-11 w-11 items-center justify-center rounded-full bg-[var(--me-accent)]/10 text-[var(--me-accent)]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007Z" /></svg>
                    </span>
                    <h2 class="text-[0.7rem] font-bold uppercase tracking-widest text-gray-400">{{ __('Orders') }}</h2>
                    <p class="mt-3 text-3xl font-black text-[#1a1a1a]">{{ $orders->count() }}</p>
                    <p class="text-sm text-gray-500">{{ __('items in your recent orders') }}</p>
                    <a href="{{ route('account.orders') }}" class="mt-4 inline-flex items-center gap-1.5 text-[0.7rem] font-bold uppercase tracking-widest text-[var(--me-accent)] hover:underline">
                        {{ __('View all orders') }}
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                    </a>
                </div>
            </div>

            <section class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                    <h2 class="text-[0.7rem] font-bold uppercase tracking-widest text-gray-400">{{ __('Recent orders') }}</h2>
                    @if ($orders->isNotEmpty())
                        <a href="{{ route('account.orders') }}" class="text-[0.7rem] font-bold uppercase tracking-widest text-[var(--me-accent)] hover:underline">{{ __('View all') }}</a>
                    @endif
                </div>

                @if ($orders->isEmpty())
                    <div class="px-6 py-16 text-center">
                        <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-gray-50 text-gray-300">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" /></svg>
                        </span>
                        <p class="mt-4 text-sm text-gray-500">{{ __('You have not placed any orders yet.') }}</p>
                        <a href="{{ route('shop') }}" class="mt-6 inline-block bg-[#1a1a1a] px-8 py-3 text-xs font-bold uppercase tracking-widest text-white transition hover:bg-[var(--me-accent)]">{{ __('Start shopping') }}</a>
                    </div>
                @else
                    <ul class="divide-y divide-gray-100">
                        @foreach ($orders as $order)
                            <li class="flex flex-col gap-3 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <a href="{{ route('account.orders.show', $order->order_number) }}"
                                        class="font-mono text-sm font-bold text-[#1a1a1a] transition-colors hover:text-[var(--me-accent)]">
                                        {{ $order->order_number }}
                                    </a>
                                    <p class="mt-0.5 text-xs text-gray-400">{{ $order->created_at?->toDisplay() }}</p>
                                </div>
                                <div class="flex items-center gap-4">
                                    @include('theme-modern_ecommerce::account.partials._status-badge', ['status' => $order->status])
                                    <span class="text-sm font-extrabold text-[#1a1a1a]">{{ format_money($order->total) }}</span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    </div>
</main>

@include('theme-modern_ecommerce::partials._footer')

@include('frontend.partials._chat-widget')
@include('partials._custom-code-body')
</body>
</html>
