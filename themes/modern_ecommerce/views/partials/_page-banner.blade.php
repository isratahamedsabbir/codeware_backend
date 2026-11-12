@php
    $crumbs = $crumbs ?? [];
@endphp

<section class="border-b border-gray-100 bg-[#f8f9fa]">
    <div class="mx-auto max-w-[1400px] px-4 py-10 sm:px-8 sm:py-14">
        @if (! empty($crumbs))
            <nav aria-label="{{ __('Breadcrumb') }}" class="mb-3">
                <ol class="flex flex-wrap items-center gap-2 text-[0.7rem] font-bold uppercase tracking-widest text-gray-400">
                    @foreach ($crumbs as $crumb)
                        @if (! $loop->first)
                            <li aria-hidden="true">/</li>
                        @endif
                        <li>
                            @if (! empty($crumb['url']))
                                <a href="{{ $crumb['url'] }}" class="transition-colors hover:text-[var(--me-accent)]">{{ $crumb['label'] }}</a>
                            @else
                                <span class="text-gray-800">{{ $crumb['label'] }}</span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </nav>
        @endif

        <h1 class="text-3xl font-black uppercase tracking-tight text-[#1a1a1a] sm:text-4xl">{{ $title }}</h1>

        @isset($subtitle)
            @if (filled($subtitle))
                <p class="mt-2 max-w-2xl text-sm font-medium leading-relaxed text-gray-500">{{ $subtitle }}</p>
            @endif
        @endisset
    </div>
</section>
