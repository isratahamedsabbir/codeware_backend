@php
    $siteName = \App\Models\Setting::get('site_name', config('app.name'));
    $siteTagline = \App\Models\Setting::get('site_tagline');
    $contactPhone = \App\Models\Setting::get('contact_phone');
    $contactEmail = \App\Models\Setting::get('contact_email');
    $quickLinks = \App\Support\Frontend::quickLinks();
    $socials = collect(['facebook' => 'Facebook', 'twitter' => 'Twitter', 'instagram' => 'Instagram', 'youtube' => 'YouTube', 'linkedin' => 'LinkedIn'])
        ->map(fn ($label, $platform) => ['url' => \App\Models\SocialLink::url($platform), 'label' => $label])
        ->filter(fn ($social) => filled($social['url']));
    $corporateLinks = collect([
        ['label' => __('About us'), 'url' => url('/about')],
        ['label' => __('Contact'), 'url' => url('/contact')],
        ['label' => __('FAQ'), 'url' => url('/faq')],
        ['label' => __('Careers'), 'url' => \App\Support\Features::enabled('careers') ? route('careers') : null],
    ])->filter(fn ($link) => filled($link['url']));
@endphp

<footer class="mt-16 border-t border-gray-100 bg-white pt-16 font-sans">
    <div class="mx-auto max-w-[1400px] px-4 pb-16 sm:px-8">
        <div class="grid grid-cols-1 gap-12 md:grid-cols-2 lg:grid-cols-4 lg:gap-16">
            {{-- Brand & newsletter --}}
            <div>
                <h2 class="mb-6 text-3xl font-black italic tracking-tighter text-gray-900">{{ $siteName }}<span style="color: var(--me-accent)">.</span></h2>
                <p class="mb-8 text-sm font-medium leading-relaxed text-gray-500">
                    {{ $siteTagline ?: __('Join our exclusive club for priority access to new collections, limited releases, and premium lifestyle updates.') }}
                </p>
                <div class="me-newsletter">
                    <livewire:frontend.newsletter-subscribe :key="'footer-newsletter'" />
                </div>
            </div>

            {{-- Customer care --}}
            <div class="flex flex-col gap-6">
                <h3 class="text-[0.65rem] font-black uppercase tracking-[0.3em] text-gray-400">{{ __('Customer care') }}</h3>
                <ul class="flex flex-col gap-3.5">
                    @foreach ($quickLinks as $link)
                        <li>
                            <a href="{{ url($link->url) }}" class="group flex items-center gap-2 text-sm font-bold text-gray-900 transition hover:text-[var(--me-accent)]">
                                <span class="h-1.5 w-1.5 -ml-3 rounded-full bg-[var(--me-accent)] opacity-0 transition-all group-hover:opacity-100"></span>
                                {{ $link->label }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Corporate --}}
            <div class="flex flex-col gap-6">
                <h3 class="text-[0.65rem] font-black uppercase tracking-[0.3em] text-gray-400">{{ __('Corporate') }}</h3>
                <ul class="flex flex-col gap-3.5">
                    @foreach ($corporateLinks as $link)
                        <li>
                            <a href="{{ $link['url'] }}" class="group flex items-center gap-2 text-sm font-bold text-gray-900 transition hover:text-[var(--me-accent)]">
                                <span class="h-1.5 w-1.5 -ml-3 rounded-full bg-[var(--me-accent)] opacity-0 transition-all group-hover:opacity-100"></span>
                                {{ $link['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Connect & support --}}
            <div class="flex flex-col gap-8">
                @if ($socials->isNotEmpty())
                    <div class="flex flex-col gap-4">
                        <h3 class="text-[0.65rem] font-black uppercase tracking-[0.3em] text-gray-400">{{ __('Connect') }}</h3>
                        <div class="flex flex-wrap gap-3">
                            @foreach ($socials as $social)
                                <a href="{{ $social['url'] }}" target="_blank" rel="noopener" aria-label="{{ $social['label'] }}"
                                    class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gray-50 text-gray-900 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:bg-[var(--me-accent)] hover:text-white">
                                    <span class="text-[0.6rem] font-black uppercase">{{ mb_substr($social['label'], 0, 2) }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($contactPhone || $contactEmail)
                    <div class="rounded-lg border border-gray-100 bg-gray-50 p-5 transition hover:border-[var(--me-accent)]">
                        <span class="mb-1.5 block text-[0.6rem] font-black uppercase tracking-widest text-gray-400">{{ __('Quick support') }}</span>
                        @if ($contactPhone)<p class="text-lg font-black text-gray-900">{{ $contactPhone }}</p>@endif
                        @if ($contactEmail)<p class="text-sm font-semibold text-gray-600">{{ $contactEmail }}</p>@endif
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="border-t border-gray-50 py-8">
        <p class="text-center text-[0.7rem] font-bold tracking-widest text-gray-400">&copy; {{ date('Y') }} {{ $siteName }}. {{ __('All rights reserved.') }}</p>
    </div>
</footer>
