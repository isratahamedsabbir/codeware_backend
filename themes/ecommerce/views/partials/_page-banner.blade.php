{{--
    The compact title banner at the top of a page: icon, page title and, right
    under it, the breadcrumb trail. Shared by shop, blog, about, contact, faq,
    favorites, post and product.
    $title, $icon (an outline-icon path `d`), $crumbs (the last one is the
    current page), $description (fallback when there are no crumbs), $class (optional),
    $tag (heading element, default h1; use p when the page already has its own h1).
--}}
<header class="relative my-3 overflow-hidden rounded-xl bg-gradient-to-r from-brand/10 via-brand/5 to-white px-3 py-2.5 sm:px-4 sm:py-3 {{ $class ?? '' }}">
    <div class="pointer-events-none absolute -right-6 -top-8 size-24 rounded-full bg-brand/10" aria-hidden="true"></div>
    <div class="relative flex min-w-0 flex-wrap items-center gap-x-3 gap-y-2">
        <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-brand text-white shadow-sm" aria-hidden="true">
            <svg class="size-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/></svg>
        </span>
        <div class="min-w-0">
            <{{ $tag ?? 'h1' }} class="truncate text-base font-extrabold leading-tight tracking-tight text-sf-heading sm:text-lg">{{ $title }}</{{ $tag ?? 'h1' }}>

            @if (! empty($crumbs))
                <nav class="-ml-2 mt-0.5" aria-label="{{ __('Breadcrumb') }}">
                    <ol class="flex flex-wrap items-center gap-1 text-[11px] font-medium sm:text-xs">
                        @foreach ($crumbs as $index => $crumb)
                            @if ($index > 0)
                                <li aria-hidden="true" class="text-zinc-300">
                                    <svg class="size-3 rtl:rotate-180" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.21 4.29a1 1 0 0 1 1.42 0l5 5a1 1 0 0 1 0 1.42l-5 5a1 1 0 1 1-1.42-1.42L11.5 10 7.21 5.71a1 1 0 0 1 0-1.42Z" clip-rule="evenodd"/></svg>
                                </li>
                            @endif
                            <li class="min-w-0">
                                @if (filled($crumb['url'] ?? null) && $index < count($crumbs) - 1)
                                    <a href="{{ $crumb['url'] }}" class="inline-flex items-center gap-1.5 rounded-full px-1.5 py-px text-zinc-500 transition hover:bg-brand/10 hover:text-brand">
                                        @if ($index === 0)
                                            <svg class="size-3" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10.707 2.293a1 1 0 0 0-1.414 0l-7 7a1 1 0 0 0 1.414 1.414L4 10.414V17a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1v-2a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v2a1 1 0 0 0 1 1h2a1 1 0 0 0 1-1v-6.586l.293.293a1 1 0 0 0 1.414-1.414l-7-7Z"/></svg>
                                        @endif
                                        {{ $crumb['label'] }}
                                    </a>
                                @else
                                    <span aria-current="page" class="inline-block max-w-[12rem] truncate rounded-full bg-brand/10 px-1.5 py-px align-bottom font-semibold text-brand sm:max-w-xs">{{ $crumb['label'] }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </nav>
            @elseif (filled($description ?? null))
                <p class="mt-0.5 text-xs text-zinc-500 sm:text-[13px]">{{ $description }}</p>
            @endif
        </div>

        {{-- Optional notice on the right ($aside, plain text); drops below the title on a phone. --}}
        @if (filled($aside ?? null))
            <p class="flex w-full items-start gap-2 text-[11px] leading-snug text-zinc-600 sm:text-xs md:ml-auto md:w-auto md:max-w-xl">
                <svg class="mt-0.5 size-3.5 shrink-0 text-brand" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/></svg>
                <span>{{ $aside }}</span>
            </p>
        @endif
    </div>
</header>
