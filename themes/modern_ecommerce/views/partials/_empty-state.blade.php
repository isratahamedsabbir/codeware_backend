@php
    $heading = $heading ?? __('No products found');
    $message = $message ?? __('Try adjusting your search or clearing the filters.');
@endphp

<div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-gray-200 bg-white py-20 text-center">
    <span class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-[var(--me-accent)]/10 text-[var(--me-accent)]">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7">
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
        </svg>
    </span>
    <h2 class="text-lg font-extrabold uppercase tracking-wide text-[#1a1a1a]">{{ $heading }}</h2>
    <p class="mt-1 max-w-sm text-sm text-gray-500">{{ $message }}</p>
    <a href="{{ route('shop') }}"
        class="mt-6 inline-block bg-[#1a1a1a] px-8 py-3 text-xs font-bold uppercase tracking-widest text-white transition hover:bg-[var(--me-accent)]">
        {{ __('View all products') }}
    </a>
</div>
