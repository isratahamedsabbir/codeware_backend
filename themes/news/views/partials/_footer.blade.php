@php
    $siteName = \App\Models\Setting::get('site_name', config('app.name'));

    $socials = collect([
        'facebook' => 'Facebook',
        'twitter' => 'Twitter / X',
        'instagram' => 'Instagram',
        'youtube' => 'YouTube',
        'linkedin' => 'LinkedIn',
    ])->map(fn ($label, $platform) => ['url' => \App\Models\SocialLink::url($platform), 'label' => $label])
      ->filter(fn ($social) => filled($social['url']));
@endphp

<footer class="mt-16 border-t-4 bg-zinc-950 px-4 py-10 text-zinc-400 sm:px-6" style="border-top-color: var(--nw-accent)">
    <div class="mx-auto flex max-w-6xl flex-col items-center gap-4 text-center sm:flex-row sm:justify-between sm:text-left">
        <div>
            <p class="nw-serif text-xl font-bold text-white">{{ $siteName }}</p>
            <p class="mt-1 text-xs">&copy; {{ now()->setTimezone(display_timezone())->year }} {{ $siteName }}. {{ __('All rights reserved.') }}</p>
        </div>
        @if ($socials->isNotEmpty())
            <div class="flex flex-wrap justify-center gap-4 text-sm">
                @foreach ($socials as $social)
                    <a href="{{ $social['url'] }}" target="_blank" rel="noopener" class="hover:text-white">{{ $social['label'] }}</a>
                @endforeach
            </div>
        @endif
    </div>
</footer>
