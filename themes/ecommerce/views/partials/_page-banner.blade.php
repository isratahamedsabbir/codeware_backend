{{--
    The compact title banner at the top of a page: icon, page title and, right
    under it, the breadcrumb trail. Shared by shop, blog, about, contact, faq,
    favorites, post and product.
    $title, $icon (an outline-icon path `d`), $crumbs (the last one is the
    current page), $description (fallback when there are no crumbs), $class (optional),
    $tag (heading element, default h1; use p when the page already has its own h1).
--}}
<header class="relative mb-8 mt-4 overflow-hidden rounded-2xl bg-gradient-to-r from-brand/10 via-brand/5 to-white px-4 py-4 sm:px-6 sm:py-5 {{ $class ?? '' }}">
    <div class="pointer-events-none absolute -right-8 -top-10 size-32 rounded-full bg-brand/10" aria-hidden="true"></div>
    <div class="relative flex min-w-0 items-center gap-3 sm:gap-4">
        <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-brand text-white shadow-sm sm:size-12" aria-hidden="true">
            <svg class="size-5 sm:size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
        </span>
        <div class="min-w-0">
            <{{ $tag ?? 'h1' }} class="truncate text-xl font-extrabold leading-tight tracking-tight text-sf-heading sm:text-2xl">{{ $title }}</{{ $tag ?? 'h1' }}>

            @if (! empty($crumbs))
                <nav class="-ml-2 mt-1" aria-label="{{ __('Breadcrumb') }}">
                    <ol class="flex flex-wrap items-center gap-1 text-xs font-medium sm:text-[13px]">
                        @foreach ($crumbs as $index => $crumb)
                            @if ($index > 0)
                                <li aria-hidden="true" class="text-zinc-300">
                                    <svg class="size-3.5 rtl:rotate-180" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.21 4.29a1 1 0 0 1 1.42 0l5 5a1 1 0 0 1 0 1.42l-5 5a1 1 0 1 1-1.42-1.42L11.5 10 7.21 5.71a1 1 0 0 1 0-1.42Z" clip-rule="evenodd"/></svg>
                                </li>
                            @endif
                            <li class="min-w-0">
                                @if (filled($crumb['url'] ?? null) && $index < count($crumbs) - 1)
                                    <a href="{{ $crumb['url'] }}" class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-zinc-500 transition hover:bg-brand/10 hover:text-brand">
                                        @if ($index === 0)
                                            <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10.707 2.293a1 1 0 0 0-1.414 0l-7 7a1 1 0 0 0 1.414 1.414L4 10.414V17a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1v-2a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1v-6.586l.293.293a1 1 0 0 0 1.414-1.414l-7-7Z"/></svg>
                                        @endif
                                        {{ $crumb['label'] }}
                                    </a>
                                @else
                                    <span aria-current="page" class="inline-block max-w-[12rem] truncate rounded-full bg-brand/10 px-2 py-0.5 align-bottom font-semibold text-brand sm:max-w-xs">{{ $crumb['label'] }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </nav>
            @elseif (filled($description ?? null))
                <p class="mt-0.5 text-xs text-zinc-500 sm:text-[13px]">{{ $description }}</p>
            @endif
        </div>
    </div>
</header>
