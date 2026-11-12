@php
    $siteName = \App\Models\Setting::get('site_name', config('app.name'));
    $siteIcon = \App\Models\Setting::get('site_icon');
    $currentUrl = url()->current();
    $menuItems = \App\Support\Frontend::menuItems();
    $productsEnabled = \App\Support\Features::enabled('products');
    $megaCategories = $productsEnabled
        ? \App\Models\ProductCategory::active()->with('page')->orderBy('sort_order')->limit(8)->get()->filter(fn ($category) => $category->page !== null)
        : collect();
@endphp

<header
    x-data="{ open: false, search: false, account: false, scrolled: false }"
    x-init="scrolled = window.scrollY > 50"
    @scroll.window="scrolled = window.scrollY > 50"
    class="sticky top-0 z-50 border-b border-gray-100 bg-white/95 shadow-sm backdrop-blur-md transition-all duration-300"
>
    <div class="mx-auto flex max-w-[1400px] items-center justify-between gap-4 px-4 sm:px-8" :class="scrolled ? 'py-1.5' : 'py-3'">
        {{-- Logo --}}
        <a href="{{ url('/') }}" class="flex min-w-0 items-center gap-2">
            @if ($siteIcon)
                <img src="{{ $siteIcon }}" alt="{{ $siteName }}" width="120" height="48"
                    class="w-auto object-contain transition-all duration-300" :class="scrolled ? 'h-9' : 'h-11 sm:h-12'">
            @else
                <span class="truncate text-2xl font-black italic tracking-tighter text-[#1a1a1a] transition-all duration-300 sm:text-3xl">{{ $siteName }}<span style="color: var(--me-accent)">.</span></span>
            @endif
        </a>

        {{-- Desktop nav --}}
        <nav class="hidden md:block">
            <ul class="m-0 flex list-none gap-7 p-0">
                @if ($productsEnabled && $megaCategories->isNotEmpty())
                    <li class="group relative">
                        <a href="{{ route('shop') }}"
                            class="flex items-center gap-1 py-4 text-[0.8rem] font-bold uppercase tracking-widest transition-colors hover:text-[var(--me-accent)] {{ request()->routeIs('shop', 'products.show', 'shop.category', 'shop.brand', 'shop.tag') ? 'text-[var(--me-accent)]' : 'text-gray-800' }}">
                            {{ __('Shop') }}
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6" /></svg>
                        </a>
                        <div class="invisible absolute left-0 top-full z-50 w-56 translate-y-2 rounded-xl border border-gray-100 bg-white p-3 opacity-0 shadow-xl transition-all duration-300 group-hover:visible group-hover:translate-y-0 group-hover:opacity-100">
                            <ul class="m-0 flex list-none flex-col gap-1 p-0">
                                @foreach ($megaCategories as $category)
                                    <li>
                                        <a href="{{ route('shop', ['category' => $category->slug]) }}"
                                            class="block rounded-lg px-3 py-2 text-xs font-bold uppercase tracking-widest text-gray-600 transition hover:bg-gray-50 hover:text-[var(--me-accent)]">
                                            {{ $category->name }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </li>
                @endif

                @foreach ($menuItems as $menuItem)
                    <li>
                        <a href="{{ url($menuItem->url) }}"
                            class="block py-4 text-[0.8rem] font-bold uppercase tracking-widest transition-colors hover:text-[var(--me-accent)] {{ url($menuItem->url) === $currentUrl ? 'text-[var(--me-accent)]' : 'text-gray-800' }}">
                            {{ $menuItem->label }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        {{-- Actions --}}
        <div class="flex shrink-0 items-center gap-0.5">
            @if ($productsEnabled)
                <button type="button" @click="search = ! search" aria-label="{{ __('Search') }}"
                    class="rounded-full p-2.5 text-gray-800 transition hover:scale-110 hover:text-[var(--me-accent)]">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                </button>
            @endif

            <div class="relative">
                <button type="button" @click="account = ! account" aria-label="{{ __('Account') }}"
                    class="rounded-full p-2.5 text-gray-800 transition hover:scale-110 hover:text-[var(--me-accent)]">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                </button>

                <div x-cloak x-show="account" @click.outside="account = false" x-transition
                    class="absolute right-0 top-full z-50 mt-3 w-72 overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-2xl">
                    @auth
                        <div class="p-5">
                            <div class="mb-4 flex items-center gap-3">
                                <span class="flex h-11 w-11 items-center justify-center rounded-full bg-gray-900 text-lg font-black text-white">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                                <div class="min-w-0">
                                    <p class="text-[0.6rem] font-black uppercase tracking-widest text-gray-400">{{ __('Welcome back') }},</p>
                                    <p class="truncate text-base font-black text-gray-900">{{ auth()->user()->name }}</p>
                                </div>
                            </div>
                            <ul class="m-0 flex list-none flex-col gap-1 p-0">
                                <li><a href="{{ route('account.dashboard') }}" class="block rounded-xl px-3 py-2.5 text-sm font-bold text-gray-700 transition hover:bg-gray-50">{{ __('Dashboard') }}</a></li>
                                <li><a href="{{ route('account.orders') }}" class="block rounded-xl px-3 py-2.5 text-sm font-bold text-gray-700 transition hover:bg-gray-50">{{ __('My Orders') }}</a></li>
                                <li><a href="{{ route('account.profile') }}" class="block rounded-xl px-3 py-2.5 text-sm font-bold text-gray-700 transition hover:bg-gray-50">{{ __('Profile') }}</a></li>
                            </ul>
                            <form method="POST" action="{{ route('logout') }}" class="mt-2 border-t border-gray-100 pt-2">
                                @csrf
                                <button type="submit" class="w-full rounded-xl px-3 py-2.5 text-left text-sm font-bold text-red-600 transition hover:bg-red-50">{{ __('Sign out') }}</button>
                            </form>
                        </div>
                    @else
                        <div class="p-5">
                            <h3 class="mb-4 text-lg font-black tracking-tight text-gray-900">{{ __('Sign in') }}</h3>
                            <p class="mb-4 text-xs font-medium leading-relaxed text-gray-500">{{ __('Access your account, orders and saved items.') }}</p>
                            <a href="{{ route('login') }}" class="block w-full rounded-xl bg-[var(--me-accent)] px-4 py-3 text-center text-xs font-bold uppercase tracking-widest text-white transition hover:opacity-90">{{ __('Sign in') }}</a>
                            <a href="{{ route('register') }}" class="mt-2 block w-full rounded-xl bg-gray-50 px-4 py-3 text-center text-xs font-bold uppercase tracking-widest text-gray-800 transition hover:bg-gray-100">{{ __('Create account') }}</a>
                        </div>
                    @endauth
                </div>
            </div>

            @if (\App\Support\Features::enabled('orders'))
                <div class="me-cart-link">
                    <livewire:frontend.cart-count :key="'header-cart-count'" />
                </div>
            @endif

            <button type="button" class="rounded-full p-2.5 text-gray-800 md:hidden" aria-label="{{ __('Menu') }}" @click="open = ! open">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
            </button>
        </div>
    </div>

    {{-- Search panel --}}
    @if ($productsEnabled)
        <div x-cloak x-show="search" x-transition class="border-t border-gray-100 bg-white">
            <form action="{{ route('shop') }}" method="GET" role="search" class="mx-auto flex max-w-[1400px] items-center gap-3 px-4 py-4 sm:px-8">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="shrink-0 text-gray-400"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="search" name="search" value="{{ request('search') }}" placeholder="{{ __('Search products...') }}" autofocus
                    class="w-full border-0 bg-transparent p-0 text-lg font-bold text-gray-900 outline-none placeholder:font-medium placeholder:text-gray-300 focus:ring-0">
                <button type="submit" class="shrink-0 bg-[#1a1a1a] px-6 py-2.5 text-xs font-bold uppercase tracking-widest text-white transition hover:bg-[var(--me-accent)]">{{ __('Search') }}</button>
            </form>
        </div>
    @endif

    {{-- Mobile nav --}}
    <nav x-cloak x-show="open" x-transition class="border-t border-gray-100 bg-white px-4 py-3 md:hidden">
        @if ($productsEnabled)
            <a href="{{ route('shop') }}" class="block rounded-lg px-3 py-2.5 text-sm font-bold uppercase tracking-widest text-gray-800 hover:bg-gray-50">{{ __('Shop') }}</a>
        @endif
        @foreach ($menuItems as $menuItem)
            <a href="{{ url($menuItem->url) }}" class="block rounded-lg px-3 py-2.5 text-sm font-bold uppercase tracking-widest text-gray-800 hover:bg-gray-50">{{ $menuItem->label }}</a>
        @endforeach
        @if (\App\Support\Features::enabled('orders'))
            <a href="{{ route('cart') }}" class="block rounded-lg px-3 py-2.5 text-sm font-bold uppercase tracking-widest text-gray-800 hover:bg-gray-50">{{ __('My cart') }}</a>
        @endif
        @auth
            <a href="{{ route('account.dashboard') }}" class="block rounded-lg px-3 py-2.5 text-sm font-bold uppercase tracking-widest text-gray-800 hover:bg-gray-50">{{ __('My account') }}</a>
        @else
            <a href="{{ route('login') }}" class="mt-2 block rounded-lg bg-[var(--me-accent)] px-3 py-2.5 text-center text-sm font-bold uppercase tracking-widest text-white">{{ __('Sign in') }}</a>
        @endauth
    </nav>
</header>

@if (\App\Support\Features::enabled('orders'))
    <livewire:frontend.cart-drawer :key="'theme-cart-drawer'" />
@endif
