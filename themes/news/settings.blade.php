{{--
    News theme settings — the global look: masthead tagline, brand colour and
    typography. Bound to the Theme Settings screen via wire:model="settings.*" and
    stored in this theme's theme.json.

    The wire:key is load-bearing: theme settings partials are swapped into one
    slot, and an unkeyed root keeps whichever x-data came first.

    The preview on the right is drawn from the same values the form edits ($wire
    reads), so a colour or tagline shows up in the masthead before saving.
--}}
@php
    $newsSiteName = \App\Models\Setting::get('site_name', config('app.name'));

    // Brand colour presets: [hex, label]. The theme default comes first.
    $newsPresets = [
        ['#b91c1c', 'Crimson'],
        ['#1d4ed8', 'Royal blue'],
        ['#0f766e', 'Teal'],
        ['#c2410c', 'Ember'],
        ['#7e22ce', 'Violet'],
        ['#18181b', 'Ink'],
    ];
@endphp
<div
    wire:key="theme-settings-{{ $themeSlug }}"
    x-data="{
        get accent() {
            const v = ($wire.settings.theme_news_accent_color || '').trim();
            return /^#[0-9a-fA-F]{3,8}$/.test(v) ? v : '#b91c1c';
        },
        get tagline() { return ($wire.settings.theme_news_tagline || '').trim(); },
        get validHex() {
            const v = ($wire.settings.theme_news_accent_color || '').trim();
            return v === '' || /^#[0-9a-fA-F]{3,8}$/.test(v);
        },
    }"
    class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]"
>
    {{-- ── Form ── --}}
    <div class="min-w-0 space-y-6">

        {{-- Masthead --}}
        <section class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <header class="flex items-center gap-3 border-b border-zinc-100 px-5 py-3.5 dark:border-zinc-800">
                <span class="flex size-8 items-center justify-center rounded-lg bg-zinc-100 text-zinc-500 dark:bg-zinc-800"><flux:icon.newspaper class="size-4" /></span>
                <div>
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Masthead</h3>
                    <p class="text-xs text-zinc-500">The strip above the site name on every page.</p>
                </div>
            </header>
            <div class="p-5">
                <flux:field>
                    <flux:label>Tagline<x-field-hint text="A short line beside today's date, e.g. 'Independent reporting since 2020'. Leave empty to show only the date." /></flux:label>
                    <flux:input wire:model.live.debounce.300ms="settings.theme_news_tagline" maxlength="80" placeholder="Independent reporting you can trust" />
                    <div class="mt-1.5 flex items-center justify-between text-[11px] text-zinc-400">
                        <span>Shown beside the date on tablet and desktop.</span>
                        <span x-text="tagline.length + ' / 80'" class="font-mono"></span>
                    </div>
                </flux:field>
            </div>
        </section>

        {{-- Brand colour. The swatches restate their pill radius inline: app.css sets a bare
             `button { border-radius: 5px }` that outranks the rounded-full utility. --}}
        <section class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <header class="flex items-center gap-3 border-b border-zinc-100 px-5 py-3.5 dark:border-zinc-800">
                <span class="flex size-8 items-center justify-center rounded-lg bg-zinc-100 text-zinc-500 dark:bg-zinc-800"><flux:icon.swatch class="size-4" /></span>
                <div>
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Brand colour</h3>
                    <p class="text-xs text-zinc-500">Category labels, links, buttons and the masthead rule.</p>
                </div>
            </header>
            <div class="space-y-5 p-5">
                <div>
                    <p class="mb-2 text-xs font-medium text-zinc-600 dark:text-zinc-300">Presets</p>
                    <div class="flex flex-wrap gap-2.5">
                        @foreach ($newsPresets as [$hex, $name])
                            <button type="button" title="{{ $name }} · {{ $hex }}" aria-label="{{ $name }}"
                                x-on:click="$wire.set('settings.theme_news_accent_color', '{{ $hex }}')"
                                :class="accent.toLowerCase() === '{{ $hex }}' ? 'ring-2 ring-offset-2 ring-zinc-900 dark:ring-white dark:ring-offset-zinc-900' : 'ring-1 ring-zinc-200 hover:ring-zinc-400 dark:ring-zinc-700'"
                                class="size-9 transition" style="background: {{ $hex }}; border-radius: 9999px"></button>
                        @endforeach
                    </div>
                </div>

                <flux:field>
                    <flux:label>Custom colour<x-field-hint text="Any hex value. Leave empty for the default crimson." /></flux:label>
                    <div class="flex items-center gap-3">
                        <label class="relative size-10 shrink-0 cursor-pointer overflow-hidden rounded-lg ring-1 ring-zinc-300 dark:ring-zinc-600" :style="'background:' + accent">
                            <span class="sr-only">Pick a colour</span>
                            <input type="color" :value="accent.length === 7 ? accent : '#b91c1c'"
                                x-on:input="$wire.set('settings.theme_news_accent_color', $event.target.value)"
                                class="absolute inset-0 size-full cursor-pointer opacity-0" />
                        </label>
                        <div class="w-40">
                            <flux:input wire:model.live.debounce.300ms="settings.theme_news_accent_color" placeholder="#b91c1c" maxlength="9" class="font-mono uppercase" />
                        </div>
                        <flux:button type="button" size="sm" variant="ghost" icon="arrow-uturn-left"
                            x-on:click="$wire.set('settings.theme_news_accent_color', '#b91c1c')">Reset</flux:button>
                    </div>
                    <p x-show="! validHex" x-cloak class="mt-1.5 text-xs font-medium text-red-600">Use a hex colour like #b91c1c — anything else falls back to the default.</p>
                </flux:field>
            </div>
        </section>

        {{-- Typography --}}
        <section class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
            <header class="flex items-center gap-3 border-b border-zinc-100 px-5 py-3.5 dark:border-zinc-800">
                <span class="flex size-8 items-center justify-center rounded-lg bg-zinc-100 text-zinc-500 dark:bg-zinc-800"><flux:icon.bars-3-bottom-left class="size-4" /></span>
                <div>
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Typography</h3>
                    <p class="text-xs text-zinc-500">Headlines always use a serif face; this is the body text.</p>
                </div>
            </header>
            <div class="p-5">
                <x-admin-font-picker
                    model="settings.theme_news_font"
                    :value="$settings['theme_news_font'] ?? ''"
                    own-face="plus-jakarta"
                    own-label="Plus Jakarta Sans"
                    hint="Applies to this theme's public pages only."
                    description="Theme default keeps Plus Jakarta Sans. Anything else replaces it for this theme alone." />
            </div>
        </section>
    </div>

    {{-- ── Live preview ── --}}
    <aside class="min-w-0 xl:sticky xl:top-14 xl:self-start" aria-label="Masthead preview">
        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700">
            <div class="flex items-center justify-between border-b border-zinc-100 bg-zinc-50 px-4 py-2.5 dark:border-zinc-800 dark:bg-zinc-800/60">
                <span class="text-[11px] font-semibold uppercase tracking-wider text-zinc-500">Live preview</span>
                <span class="flex gap-1"><i class="size-2 rounded-full bg-rose-400"></i><i class="size-2 rounded-full bg-amber-400"></i><i class="size-2 rounded-full bg-emerald-400"></i></span>
            </div>

            <div class="bg-white text-zinc-900">
                <div class="flex items-center justify-between gap-3 border-b border-zinc-100 bg-zinc-50 px-4 py-1.5 text-[10px] text-zinc-500">
                    <span>{{ now()->translatedFormat('l, j F Y') }}</span>
                    <span x-show="tagline" x-text="tagline" class="truncate"></span>
                </div>
                <div class="px-4 py-5 text-center">
                    <span class="text-2xl font-black tracking-tight" style="font-family: Georgia, 'Times New Roman', serif">{{ $newsSiteName }}</span>
                </div>
                <div class="border-t-2 px-4 py-2" :style="'border-top-color:' + accent">
                    <div class="flex gap-3 text-[10px] font-semibold text-zinc-500">
                        <span class="text-zinc-900">Home</span><span>Latest</span><span>Politics</span><span>Sports</span><span>About</span>
                    </div>
                </div>

                {{-- A story, so the accent is seen where it is actually used. --}}
                <div class="space-y-2 border-t border-zinc-100 p-4">
                    <div class="aspect-[16/9] rounded bg-gradient-to-br from-zinc-200 to-zinc-300"></div>
                    <p class="text-[10px] font-bold uppercase tracking-widest" :style="'color:' + accent">Politics</p>
                    <p class="text-base font-bold leading-snug" style="font-family: Georgia, 'Times New Roman', serif">Your headline appears here, set in a serif face</p>
                    <p class="text-[11px] text-zinc-500">By Newsroom · 12 min read</p>
                    <span class="inline-block rounded px-3 py-1.5 text-[11px] font-semibold text-white" :style="'background:' + accent">Read story</span>
                </div>
            </div>
        </div>
        <p class="mt-2 px-1 text-[11px] text-zinc-400">Changes show here instantly; they go live when you save.</p>
    </aside>
</div>

@fluxScripts
