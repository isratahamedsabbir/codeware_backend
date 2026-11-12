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
    $itemCount = (int) $order->items->sum('quantity');
    $paymentStatus = $order->payment_status ?: 'pending';
    $nextSteps = [
        ['title' => __('Order confirmed'), 'text' => __('We have received your order.'), 'done' => true],
        ['title' => __('Processing'), 'text' => __('We will call you on :phone to confirm the delivery details.', ['phone' => $order->customer_phone]), 'done' => false],
        ['title' => __('On the way'), 'text' => __('Your parcel is packed and handed over for delivery.'), 'done' => false],
        ['title' => __('Delivered'), 'text' => __('Enjoy your purchase!'), 'done' => false],
    ];
@endphp

<main class="mx-auto max-w-6xl px-4 pb-12 pt-8 sm:px-8">
    {{-- Success hero --}}
    <section class="relative overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
        <div class="absolute inset-x-0 top-0 h-1.5" style="background: linear-gradient(90deg, #10b981, var(--me-accent), #10b981)"></div>
        <div class="flex flex-col items-center gap-6 px-6 py-8 text-center md:flex-row md:justify-between md:px-10 md:text-left">
            <div class="flex flex-col items-center gap-5 md:flex-row">
                <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 ring-8 ring-emerald-50/50">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-9 w-9" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                </span>
                <div>
                    <h1 class="text-2xl font-black uppercase tracking-tight text-[#1a1a1a] md:text-3xl">{{ __('Order placed!') }}</h1>
                    <p class="mt-1 text-sm text-gray-500">{{ __('Thank you, :name. Your order has been placed successfully.', ['name' => $order->customer_name]) }}</p>
                    @if ($order->customer_email)
                        <p class="mt-0.5 text-xs text-gray-400">{{ __('Order updates will be sent to :email.', ['email' => $order->customer_email]) }}</p>
                    @endif
                </div>
            </div>

            <div class="shrink-0 rounded-2xl border border-dashed border-[var(--me-accent)]/30 bg-[var(--me-accent)]/5 px-5 py-3 text-center">
                <p class="text-[0.6rem] font-black uppercase tracking-widest text-gray-500">{{ __('Order number') }}</p>
                <p class="mt-0.5 font-mono text-xl font-black tracking-wide text-[var(--me-accent)]">{{ $order->order_number }}</p>
            </div>
        </div>

        <div class="flex flex-col gap-3 border-t border-gray-100 bg-[#f8f9fa] px-6 py-4 sm:flex-row sm:items-center sm:justify-between md:px-10">
            <p class="text-sm text-gray-600">{{ __('Keep a copy of your invoice for your records.') }}</p>
            <div class="flex flex-wrap gap-2.5">
                <a href="{{ $invoiceUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-full border border-gray-300 bg-white px-5 py-2.5 text-xs font-bold uppercase tracking-widest text-gray-700 transition hover:border-[var(--me-accent)] hover:text-[var(--me-accent)]">{{ __('View / print invoice') }}</a>
                <a href="{{ $invoiceDownloadUrl }}" class="inline-flex items-center gap-2 rounded-full bg-[#1a1a1a] px-5 py-2.5 text-xs font-bold uppercase tracking-widest text-white transition hover:bg-[var(--me-accent)]">{{ __('Download invoice (PDF)') }}</a>
            </div>
        </div>
    </section>

    <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_22rem] lg:items-start">
        <section class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
            <header class="flex items-center justify-between border-b border-gray-100 bg-[#f8f9fa] px-5 py-4">
                <h2 class="text-sm font-black uppercase tracking-widest text-[#1a1a1a]">{{ __('Order items') }}</h2>
                <span class="rounded-full bg-[var(--me-accent)]/10 px-2.5 py-0.5 text-[0.65rem] font-bold text-[var(--me-accent)]">{{ trans_choice(':count item|:count items', $itemCount, ['count' => $itemCount]) }}</span>
            </header>

            <ul class="divide-y divide-gray-100 px-5">
                @foreach ($order->items as $item)
                    @php($image = $item->product?->featured_image)
                    <li class="flex items-center gap-4 py-4">
                        <span class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-gray-100 bg-[#f8f9fa] text-gray-300">
                            @if ($image)
                                <img src="{{ $image }}" alt="{{ $item->item_name }}" class="h-full w-full object-cover">
                            @else
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 7 9-5 9 5 0 10-9 5-9-5V7Zm0 0 9 5m0 0 9-5m-9 5v10" /></svg>
                            @endif
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="line-clamp-2 text-sm font-bold text-[#1a1a1a]">{{ $item->item_name }}</p>
                            @if (! empty($item->variations))
                                <p class="mt-1 inline-flex rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">{{ \App\Support\Cart::optionsLabel($item->variations) }}</p>
                            @endif
                            <p class="mt-1 text-xs text-gray-500">{{ format_money($item->unit_price) }} &times; {{ $item->quantity }}</p>
                        </div>
                        <span class="shrink-0 text-sm font-bold tabular-nums text-[#1a1a1a]">{{ format_money($item->line_total) }}</span>
                    </li>
                @endforeach
            </ul>

            <dl class="space-y-3 border-t border-gray-100 px-5 py-5 text-sm">
                <div class="flex items-center justify-between">
                    <dt class="text-gray-500">{{ __('Subtotal') }}</dt>
                    <dd class="font-semibold tabular-nums text-[#1a1a1a]">{{ format_money($order->subtotal) }}</dd>
                </div>
                @if ((float) $order->discount > 0)
                    <div class="flex items-center justify-between">
                        <dt class="text-gray-500">{{ __('Discount') }}
                            @if ($order->coupon_code)<span class="ml-1 rounded-md bg-emerald-50 px-1.5 py-0.5 font-mono text-xs font-semibold text-emerald-700">{{ $order->coupon_code }}</span>@endif
                        </dt>
                        <dd class="font-semibold tabular-nums text-emerald-600">&minus; {{ format_money($order->discount) }}</dd>
                    </div>
                @endif
                @if ((float) $order->vat_amount > 0)
                    <div class="flex items-center justify-between">
                        <dt class="text-gray-500">{{ $order->vat_rate !== null ? $order->vat_rate.'% ' : '' }}{{ \App\Models\Setting::vatLabel() }}</dt>
                        <dd class="font-semibold tabular-nums text-[#1a1a1a]">{{ format_money($order->vat_amount) }}</dd>
                    </div>
                @endif
                @if ($order->shipping_method || (float) $order->shipping_cost > 0)
                    <div class="flex items-center justify-between">
                        <dt class="text-gray-500">{{ __('Shipping') }} @if ($order->shipping_method)<span class="text-xs text-gray-400">({{ $order->shipping_method }})</span>@endif</dt>
                        @if ((float) $order->shipping_cost > 0)
                            <dd class="font-semibold tabular-nums text-[#1a1a1a]">{{ format_money($order->shipping_cost) }}</dd>
                        @else
                            <dd class="font-semibold text-emerald-600">{{ __('Free') }}</dd>
                        @endif
                    </div>
                @endif
                <div class="flex items-center justify-between rounded-xl bg-[var(--me-accent)]/5 px-4 py-3">
                    <dt class="text-base font-black text-[#1a1a1a]">{{ __('Total') }}</dt>
                    <dd class="text-xl font-black tabular-nums" style="color: var(--me-accent)">{{ format_money($order->total) }}</dd>
                </div>
            </dl>
        </section>

        <div class="space-y-6">
            <section class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                <header class="border-b border-gray-100 bg-[#f8f9fa] px-5 py-4">
                    <h2 class="text-sm font-black uppercase tracking-widest text-[#1a1a1a]">{{ __('Order details') }}</h2>
                </header>
                <dl class="space-y-3 px-5 py-4 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-gray-500">{{ __('Date') }}</dt>
                        <dd class="font-medium text-gray-900">{{ $order->created_at?->format('d M Y, h:i A') }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-gray-500">{{ __('Status') }}</dt>
                        <dd>@include('theme-modern_ecommerce::account.partials._status-badge', ['status' => $order->status ?: 'pending'])</dd>
                    </div>
                    @if ($order->payment_method)
                        <div class="flex items-center justify-between gap-3">
                            <dt class="text-gray-500">{{ __('Payment') }}</dt>
                            <dd class="text-right font-medium text-gray-900">{{ \App\Support\PaymentMethods::label($order->payment_method) }}</dd>
                        </div>
                    @endif
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-gray-500">{{ __('Payment status') }}</dt>
                        <dd>@include('theme-modern_ecommerce::account.partials._status-badge', ['status' => $paymentStatus])</dd>
                    </div>
                </dl>
            </section>

            <section class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                <header class="border-b border-gray-100 bg-[#f8f9fa] px-5 py-4">
                    <h2 class="text-sm font-black uppercase tracking-widest text-[#1a1a1a]">{{ __('Delivery details') }}</h2>
                </header>
                <div class="space-y-3 px-5 py-4 text-sm">
                    <p class="font-bold text-[#1a1a1a]">{{ $order->customer_name }}</p>
                    <p class="whitespace-pre-line text-gray-600">{{ $order->shipping_address }}</p>
                    <p class="text-gray-600">{{ $order->customer_phone }}</p>
                    @if ($order->customer_email)<p class="break-all text-gray-600">{{ $order->customer_email }}</p>@endif
                    @if ($order->notes)
                        <p class="rounded-xl bg-[#f8f9fa] px-3 py-2 text-xs text-gray-600"><span class="font-bold text-gray-700">{{ __('Note') }}:</span> {{ $order->notes }}</p>
                    @endif
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                <header class="border-b border-gray-100 bg-[#f8f9fa] px-5 py-4">
                    <h2 class="text-sm font-black uppercase tracking-widest text-[#1a1a1a]">{{ __('What happens next?') }}</h2>
                </header>
                <ol class="px-5 py-4">
                    @foreach ($nextSteps as $step)
                        <li class="relative flex gap-3 pb-5 last:pb-0">
                            @unless ($loop->last)<span class="absolute left-2.75 top-7 h-[calc(100%-1.75rem)] w-px bg-gray-200"></span>@endunless
                            <span @class([
                                'relative flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[11px] font-bold',
                                'bg-emerald-500 text-white' => $step['done'],
                                'border border-gray-300 bg-white text-gray-400' => ! $step['done'],
                            ])>
                                @if ($step['done'])
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                @else
                                    {{ $loop->iteration }}
                                @endif
                            </span>
                            <div>
                                <p class="text-sm font-bold {{ $step['done'] ? 'text-[#1a1a1a]' : 'text-gray-700' }}">{{ $step['title'] }}</p>
                                <p class="mt-0.5 text-xs text-gray-500">{{ $step['text'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </section>

            <div class="flex flex-col gap-2.5">
                <a href="{{ route('shop') }}" class="flex items-center justify-center gap-2 rounded-full bg-[#1a1a1a] px-6 py-3 text-xs font-bold uppercase tracking-widest text-white transition hover:bg-[var(--me-accent)]">{{ __('Continue shopping') }}</a>
                @auth
                    <a href="{{ route('account.orders') }}" class="flex items-center justify-center rounded-full border border-gray-300 bg-white px-6 py-3 text-xs font-bold uppercase tracking-widest text-gray-700 transition hover:border-[var(--me-accent)] hover:text-[var(--me-accent)]">{{ __('My orders') }}</a>
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
