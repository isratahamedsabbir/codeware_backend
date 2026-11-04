@php
    $crumbs = $crumbs ?? [['label' => __('Home'), 'url' => url('/')]];
@endphp

<nav aria-label="{{ __('Breadcrumb') }}" class="mx-auto max-w-7xl px-4 py-2 sm:px-6">
    <ol class="inline-flex max-w-full flex-wrap items-center gap-2.5 text-[13px]">
        @foreach ($crumbs as $index => $crumb)
            @if ($index > 0)
                <li aria-hidden="true" class="text-zinc-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5 rtl:rotate-180" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M7.21 4.29a1 1 0 0 1 1.42 0l5 5a1 1 0 0 1 0 1.42l-5 5a1 1 0 1 1-1.42-1.42L11.5 10 7.21 5.71a1 1 0 0 1 0-1.42Z" clip-rule="evenodd" />
                    </svg>
                </li>
            @endif
            <li>
                @if (filled($crumb['url'] ?? null) && $index < count($crumbs) - 1)
                    <a href="{{ $crumb['url'] }}" class="font-medium text-zinc-500 transition-colors hover:text-brand">{{ $crumb['label'] }}</a>
                @else
                    <span aria-current="page" class="font-semibold text-brand">{{ $crumb['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
