@php
    // CMS is switched on Settings → Widgets, not here (see Settings\Index::updatedCmsEnabled()).
    $all = \Illuminate\Support\Arr::except(\App\Support\Features::ALL, ['cms']);
    $parents = \App\Support\Features::PARENTS;

    // Presentation only: which section each feature is listed under. A key that is
    // not mapped here still shows up, under "Other", so a new feature is never lost.
    $groups = [
        'Content & Pages' => ['pages', 'blog', 'media-library', 'file-manager', 'menu', 'about', 'advance'],
        'Catalog & Commerce' => ['products', 'brands', 'services', 'orders', 'discounts', 'flash-deals', 'vouchers', 'advertisements'],
        'Taxonomy' => ['types', 'categories', 'tags', 'groups'],
        'Engagement & Communication' => ['contacts', 'comments', 'reviews', 'newsletter', 'chat', 'careers', 'email-templates'],
        'Localization' => ['localization', 'location'],
        'System & Developer' => ['access-control', 'audit-log', 'plugins', 'crud', 'env', 'theme-settings', 'developer-guide'],
    ];

    $mapped = collect($groups)->flatten()->all();
    $leftover = array_values(array_diff(array_keys($all), $mapped));

    if ($leftover !== []) {
        $groups['Other'] = $leftover;
    }

    $total = count($all);
    $on = collect($all)->keys()->filter(fn ($k) => $features[$k] ?? false)->count();
@endphp

<div class="max-w-[1600px]" x-data="{ q: '' }">
    {{-- Summary + search --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <div class="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary">
                <flux:icon.squares-2x2 class="size-5" />
            </div>
            <div>
                <div class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">{{ __('Modules') }}</div>
                <div class="text-xs text-zinc-500">
                    <span class="font-medium text-primary">{{ $on }}</span> {{ __('of') }} {{ $total }} {{ __('enabled') }}
                </div>
            </div>
        </div>

        <div class="relative w-full sm:w-72">
            <flux:icon.magnifying-glass class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-zinc-400" />
            <input type="search" x-model="q" placeholder="{{ __('Search features...') }}"
                class="w-full rounded-lg border border-zinc-200 bg-white py-2 pl-9 pr-3 text-sm text-zinc-700 placeholder-zinc-400 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-zinc-700 dark:bg-zinc-800/40 dark:text-zinc-200" />
        </div>
    </div>

    {{-- Sections --}}
    <div class="space-y-8">
        @foreach ($groups as $groupLabel => $keys)
            @php
                $keys = array_values(array_filter($keys, fn ($k) => isset($all[$k])));
                $groupOn = collect($keys)->filter(fn ($k) => $features[$k] ?? false)->count();
                $haystack = collect($keys)->map(fn ($k) => strtolower($all[$k].' '.$k))->values()->all();
            @endphp
            @continue($keys === [])

            <section x-show="! q || @js($haystack).some(t => t.includes(q.toLowerCase()))">
                <div class="mb-3 flex items-center gap-3">
                    <h2 class="text-xs font-semibold uppercase tracking-wider text-zinc-500">{{ __($groupLabel) }}</h2>
                    <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] font-medium text-zinc-500 dark:bg-zinc-700/50">
                        {{ $groupOn }}/{{ count($keys) }}
                    </span>
                    <div class="h-px flex-1 bg-zinc-200 dark:bg-zinc-700"></div>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($keys as $key)
                        @php
                            $label = $all[$key];
                            $parent = $parents[$key] ?? null;
                            $parentOff = $parent !== null && ! ($features[$parent] ?? true);
                            // "Blog (Posts)" -> title "Blog", hint "Posts"; a label without parentheses has no hint.
                            $title = trim(preg_replace('/\s*\(.*\)\s*$/', '', $label));
                            $hint = preg_match('/\((.*)\)\s*$/', $label, $m) ? $m[1] : null;
                            $isOn = (bool) ($features[$key] ?? false);
                        @endphp

                        <label wire:key="feature-{{ $key }}" data-feature
                            x-show="! q || @js(strtolower($label.' '.$key)).includes(q.toLowerCase())"
                            class="group flex cursor-pointer items-start justify-between gap-4 rounded-xl border bg-white p-4 shadow-sm transition
                                {{ $isOn && ! $parentOff ? 'border-primary/30 ring-1 ring-primary/10' : 'border-zinc-200 dark:border-zinc-700' }}
                                hover:border-primary/40 hover:shadow dark:bg-zinc-800/40
                                {{ $parentOff ? 'opacity-60' : '' }}">
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-zinc-800 dark:text-zinc-100">{{ __($title) }}</span>

                                @if ($hint)
                                    <span class="mt-1 block text-xs leading-relaxed text-zinc-500">{{ __($hint) }}</span>
                                @endif

                                @if ($parent !== null)
                                    <span class="mt-2 inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[11px] font-medium
                                        {{ $parentOff ? 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400' : 'bg-zinc-100 text-zinc-500 dark:bg-zinc-700/50' }}">
                                        <flux:icon.link class="size-3" />
                                        {{ $parentOff
                                            ? __('Hidden because :parent is off', ['parent' => __(trim(preg_replace('/\s*\(.*\)\s*$/', '', $all[$parent])))])
                                            : __('Part of :parent', ['parent' => __(trim(preg_replace('/\s*\(.*\)\s*$/', '', $all[$parent])))]) }}
                                    </span>
                                @endif
                            </span>

                            <flux:switch wire:model.live="features.{{ $key }}" aria-label="{{ $title }}" class="mt-0.5 shrink-0" />
                        </label>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</div>
