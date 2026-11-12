@php
    $items = [
        [
            'key' => 'dashboard',
            'label' => __('My Account'),
            'href' => route('account.dashboard'),
            'active' => request()->routeIs('account.dashboard'),
        ],
        [
            'key' => 'orders',
            'label' => __('My Orders'),
            'href' => route('account.orders'),
            'active' => request()->routeIs('account.orders', 'account.orders.show'),
        ],
        [
            'key' => 'profile',
            'label' => __('Profile'),
            'href' => route('account.profile'),
            'active' => request()->routeIs('account.profile'),
        ],
        [
            'key' => 'delete',
            'label' => __('Delete account'),
            'href' => route('account.delete'),
            'active' => request()->routeIs('account.delete'),
        ],
    ];
@endphp

<aside class="shrink-0 md:w-64">
    <nav class="flex flex-col rounded-2xl border border-gray-100 bg-white p-2.5 shadow-sm md:sticky md:top-24">
        @foreach ($items as $item)
            <a href="{{ $item['href'] }}"
                class="flex items-center gap-3 rounded-xl px-4 py-3 text-[0.7rem] font-bold uppercase tracking-widest transition-colors {{ $item['active'] ? 'bg-[var(--me-accent)] text-white' : 'text-gray-600 hover:bg-gray-50 hover:text-[var(--me-accent)]' }}">
                @switch($item['key'])
                    @case('dashboard')
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                        @break
                    @case('orders')
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg>
                        @break
                    @case('profile')
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                        @break
                    @default
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                @endswitch
                {{ $item['label'] }}
            </a>
        @endforeach

        <form method="POST" action="{{ route('logout') }}" class="mt-1 border-t border-gray-100 pt-2">
            @csrf
            <button type="submit"
                class="flex w-full items-center gap-3 rounded-xl px-4 py-3 text-left text-[0.7rem] font-bold uppercase tracking-widest text-red-600 transition-colors hover:bg-red-50">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" /></svg>
                {{ __('Log out') }}
            </button>
        </form>
    </nav>
</aside>
