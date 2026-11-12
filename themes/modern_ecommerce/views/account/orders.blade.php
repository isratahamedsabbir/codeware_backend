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
    'title' => __('My orders'),
    'subtitle' => __('Every order you have placed, newest first.'),
    'crumbs' => [
        ['label' => __('Home'), 'url' => url('/')],
        ['label' => __('My account'), 'url' => route('account.dashboard')],
        ['label' => __('My orders'), 'url' => null],
    ],
])

<main class="mx-auto max-w-[1400px] px-4 pb-16 pt-10 sm:px-8">
    <div class="flex flex-col gap-8 md:flex-row">
        @include('theme-modern_ecommerce::account.partials._account-nav')

        <div class="min-w-0 flex-1">
            @if ($orders->isEmpty())
                <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-gray-200 bg-white py-20 text-center">
                    <span class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-[var(--me-accent)]/10 text-[var(--me-accent)]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" /></svg>
                    </span>
                    <h2 class="text-lg font-extrabold uppercase tracking-wide text-[#1a1a1a]">{{ __('No orders yet') }}</h2>
                    <p class="mt-1 max-w-md text-sm text-gray-500">{{ __('When you place an order, it will show up here with its status and details.') }}</p>
                    <a href="{{ route('shop') }}" class="mt-6 inline-block bg-[#1a1a1a] px-8 py-3 text-xs font-bold uppercase tracking-widest text-white transition hover:bg-[var(--me-accent)]">{{ __('Browse products') }}</a>
                </div>
            @else
                <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100 text-sm">
                            <thead>
                                <tr class="bg-gray-50/70 text-left text-[0.65rem] font-bold uppercase tracking-widest text-gray-400">
                                    <th class="px-6 py-3.5">{{ __('Order') }}</th>
                                    <th class="px-6 py-3.5">{{ __('Date') }}</th>
                                    <th class="px-6 py-3.5">{{ __('Items') }}</th>
                                    <th class="px-6 py-3.5">{{ __('Total') }}</th>
                                    <th class="px-6 py-3.5">{{ __('Status') }}</th>
                                    <th class="px-6 py-3.5"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($orders as $order)
                                    <tr class="transition-colors hover:bg-gray-50/60">
                                        <td class="px-6 py-4 font-mono font-bold text-[#1a1a1a]">{{ $order->order_number }}</td>
                                        <td class="px-6 py-4 text-gray-500">{{ $order->created_at?->toDisplay() }}</td>
                                        <td class="px-6 py-4 text-gray-500">{{ $order->items->sum('quantity') }}</td>
                                        <td class="px-6 py-4 font-extrabold text-[#1a1a1a]">{{ format_money($order->total) }}</td>
                                        <td class="px-6 py-4">
                                            @include('theme-modern_ecommerce::account.partials._status-badge', ['status' => $order->status])
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <a href="{{ route('account.orders.show', $order->order_number) }}"
                                                class="inline-flex items-center gap-1.5 text-[0.65rem] font-bold uppercase tracking-widest text-[var(--me-accent)] hover:underline">
                                                {{ __('View') }}
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mt-8">{{ $orders->links() }}</div>
            @endif
        </div>
    </div>
</main>

@include('theme-modern_ecommerce::partials._footer')

@include('frontend.partials._chat-widget')
@include('partials._custom-code-body')
</body>
</html>
