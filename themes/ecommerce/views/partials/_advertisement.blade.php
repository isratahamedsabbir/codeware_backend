@if (! empty($advertisement))
    <section class="mx-auto w-full max-w-7xl px-4 pt-8 sm:px-6">
        <a href="{{ route('advertisements.click', $advertisement->code) }}" target="_blank"
            rel="noopener noreferrer nofollow" class="group block overflow-hidden rounded-card border border-zinc-200 bg-white shadow-sm">
            <span class="relative block">
                @if ($advertisement->image)
                    <img src="{{ $advertisement->image }}" alt="{{ $advertisement->name }}" loading="lazy" decoding="async"
                        class="aspect-[16/9] w-full object-cover transition duration-500 group-hover:scale-[1.02] sm:aspect-[21/8]" />
                @endif
                <span class="absolute left-4 top-4 inline-flex items-center gap-1.5 rounded-full bg-white/90 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wider text-zinc-600 shadow-sm backdrop-blur">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 0-.59-4.59c.18-1.964.597-3.88 1.227-5.68M10.34 15.84a18.046 18.046 0 0 0 3.51.659c3.563.06 6.744 1.507 8.505 3.832.15.195.238.434.256.679.04.574-.387 1.108-.96 1.155-2.129.11-4.899.362-7.707.511M7.5 11.999h.01" />
                    </svg>
                    {{ __('Sponsored') }}
                </span>
            </span>
            @if ($advertisement->name || $advertisement->url)
                <span class="flex flex-wrap items-center justify-between gap-3 border-t border-zinc-100 px-5 py-4 sm:px-6">
                    @if ($advertisement->name)
                        <span class="text-sm font-semibold text-sf-heading">{{ $advertisement->name }}</span>
                    @endif
                    @if ($advertisement->url)
                        <span class="text-xs font-medium text-sf-primary">
                            {{ __('Learn more') }} <span aria-hidden="true">→</span>
                            @if ($host = parse_url($advertisement->url, PHP_URL_HOST))
                                <span class="font-normal text-zinc-400">{{ $host }}</span>
                            @endif
                        </span>
                    @endif
                </span>
            @endif
        </a>
    </section>
@endif
