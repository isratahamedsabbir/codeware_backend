<div data-cart-drawer
    x-data="{ open: false }"
    x-on:open-cart-drawer.window="open = true"
    x-on:keydown.escape.window="open = false"
    x-effect="document.body.classList.toggle('overflow-hidden', open)"
>
    {{-- Floating button, vertically centred on the right edge. --}}
    <button type="button" x-on:click="open = true" aria-label="{{ __('Open cart') }}"
        class="fixed right-0 top-1/2 z-40 flex w-12 -translate-y-1/2 flex-col items-center gap-1 rounded-l-xl bg-brand px-1 py-3 text-white shadow-lg transition hover:opacity-95 sm:w-[4.5rem] sm:gap-1.5 sm:px-2 sm:pb-2 sm:pt-3">
        <span class="relative">
            <x-storefront.icon name="bag" stroke="1.8" class="h-6 w-6 sm:h-7 sm:w-7" />
            @if ($count > 0)
                <span class="absolute -right-2.5 -top-2 flex h-4 min-w-4 items-center justify-center rounded-full bg-white px-1 text-[10px] font-bold leading-none text-brand sm:hidden">{{ $count }}</span>
            @endif
        </span>
        <span class="hidden text-[11px] font-semibold leading-none sm:block">{{ trans_choice(':count item|:count items', $count, ['count' => $count]) }}</span>
        <span class="hidden w-full rounded-full bg-white/20 px-1 py-1 text-center text-[11px] font-semibold leading-none sm:block">{{ format_money($subtotal) }}</span>
    </button>

    {{-- Backdrop --}}
    <div x-cloak x-show="open" x-transition.opacity x-on:click="open = false"
        class="fixed inset-0 z-[60] bg-black/50"></div>

    {{-- Panel --}}
    <aside x-cloak x-show="open"
        x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
        x-transition:leave="transition duration-200 ease-in" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
        class="fixed inset-y-0 right-0 z-[61] flex w-full max-w-sm flex-col bg-white shadow-2xl"
        role="dialog" aria-modal="true" aria-label="{{ __('My cart') }}">

        <div class="flex items-center justify-between border-b border-zinc-100 px-5 py-4">
            <div class="flex items-center gap-2 text-base font-semibold text-sf-heading">
                <x-storefront.icon name="cart" stroke="1.8" class="h-5 w-5 text-zinc-500" />
                {{ trans_choice(':count Item|:count Items', $count, ['count' => $count]) }}
            </div>
            <button type="button" x-on:click="open = false" aria-label="{{ __('Close') }}"
                class="flex h-8 w-8 items-center justify-center rounded-full border border-red-300 text-red-500 transition hover:bg-red-50">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18" /></svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto" wire:loading.class="opacity-60" wire:target="increase,decrease,remove">
            @forelse ($items as $item)
                <div wire:key="drawer-line-{{ $item['key'] }}" class="flex gap-3 border-b border-zinc-100 px-5 py-4">
                    <a href="{{ $item['url'] }}" class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-lg border border-zinc-100 bg-zinc-50 text-zinc-300">
                        @if ($item['image'])
                            <img src="{{ $item['image'] }}" alt="{{ $item['name'] }}" class="h-full w-full object-cover">
                        @else
                            <x-storefront.icon name="box" stroke="1.5" class="h-6 w-6" />
                        @endif
                    </a>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-2">
                            <a href="{{ $item['url'] }}" class="line-clamp-2 text-sm font-semibold text-sf-text hover:text-brand">{{ $item['name'] }}</a>
                            <button type="button" wire:click="remove('{{ $item['key'] }}')" aria-label="{{ __('Remove :name', ['name' => $item['name']]) }}"
                                class="shrink-0 text-zinc-400 transition hover:text-red-600">
                                <x-storefront.icon name="trash" stroke="1.8" class="h-4 w-4" />
                            </button>
                        </div>
                        @if ($item['options_label'])
                            <p class="mt-0.5 text-xs text-zinc-500">{{ $item['options_label'] }}</p>
                        @endif
                        <div class="mt-2 flex items-center justify-between">
                            <div class="flex items-center rounded-full border border-zinc-300 bg-white">
                                <button type="button" wire:click="decrease('{{ $item['key'] }}')" wire:loading.attr="disabled" aria-label="{{ __('Decrease quantity') }}"
                                    class="flex h-7 w-7 items-center justify-center rounded-l-full text-zinc-600 hover:bg-zinc-50 hover:text-brand disabled:opacity-50">
                                    <x-storefront.icon name="minus" stroke="2.5" class="h-3 w-3" />
                                </button>
                                <span class="w-7 text-center text-sm font-bold tabular-nums text-sf-heading">{{ $item['quantity'] }}</span>
                                <button type="button" wire:click="increase('{{ $item['key'] }}')" wire:loading.attr="disabled" aria-label="{{ __('Increase quantity') }}"
                                    class="flex h-7 w-7 items-center justify-center rounded-r-full text-zinc-600 hover:bg-zinc-50 hover:text-brand disabled:opacity-50">
                                    <x-storefront.icon name="plus" stroke="2.5" class="h-3 w-3" />
                                </button>
                            </div>
                            <span class="text-sm font-bold tabular-nums text-brand">{{ $item['line_total_label'] }}</span>
                        </div>
                    </div>
                </div>
            @empty
                <div class="flex h-full flex-col items-center justify-center gap-3 px-6 py-16 text-center">
                    <x-storefront.icon name="cart" stroke="1.3" class="h-12 w-12 text-zinc-300" />
                    <p class="text-sm text-zinc-500">{{ __('Your cart is empty') }}</p>
                    <a href="{{ route('shop') }}" class="text-sm font-semibold text-brand hover:underline">{{ __('Browse products') }}</a>
                </div>
            @endforelse
        </div>

        <div class="space-y-2 border-t border-zinc-100 px-5 py-4 text-sm">
            <div class="flex items-center justify-between">
                <span class="text-zinc-600">{{ __('Sub-Total') }}</span>
                <span class="font-bold tabular-nums text-brand">{{ format_money($subtotal) }}</span>
            </div>
            @if ($vatEnabled && $vat > 0)
                <div class="flex items-center justify-between">
                    <span class="text-zinc-600">{{ $vatLabel }}</span>
                    <span class="font-bold tabular-nums text-brand">{{ format_money($vat) }}</span>
                </div>
            @endif
            <div class="flex items-center justify-between">
                <span class="text-zinc-600">{{ __('Total') }}</span>
                <span class="font-bold tabular-nums text-brand">{{ format_money($total) }}</span>
            </div>

            @if ($count > 0)
                <a href="{{ route('checkout') }}"
                    class="mt-2 block rounded-md bg-brand px-4 py-3 text-center text-sm font-semibold text-white transition hover:opacity-90">{{ __('Checkout') }}</a>
                <a href="{{ route('cart') }}" class="block pt-1 text-center text-xs font-semibold text-zinc-500 hover:text-brand">{{ __('View cart') }}</a>
            @endif
        </div>
    </aside>
</div>
