{{--
    Ecommerce theme settings — bound to the Theme Settings screen (Admin →
    Theme Settings) via wire:model="settings.*". Values persist to this theme's
    own theme.json beside this file and are read by ecommerce/index.blade.php
    and partials/_head.blade.php through App\Support\ThemeSettings.

    Global look only: Colors (one per storefront area, with a live preview) and
    Typography. The home page's hero slider and promo tiles are page content and
    live in page-settings.blade.php, edited under Page Settings.
--}}
{{-- Tabs: one section at a time. Both stay mounted (x-show) so every media
     picker keeps its Livewire binding; the last tab is remembered per browser.

     The wire:key is load-bearing. This partial and portfolio/settings.blade.php
     are swapped into one slot by a plain @include, and both roots are an
     unkeyed <div x-data="{ tab, open }">. Unkeyed, Livewire morphs one into the
     other in place and Alpine keeps whichever x-data it parsed first — arriving
     from portfolio leaves `tab` at 'profile', which matches neither 'banners'
     nor 'colors', so this screen comes up looking empty and only the tab you
     click afterwards appears. Keying on the slug forces a real replacement. --}}
<div
    wire:key="theme-settings-{{ $themeSlug }}"
    x-data="{
        tab: (() => { try { return localStorage.getItem('theme-classic_ecommerce-tab') || 'colors' } catch (e) { return 'colors' } })(),
        open(name) { this.tab = name; try { localStorage.setItem('theme-classic_ecommerce-tab', name) } catch (e) {} },
    }"
    class="grid gap-5 lg:grid-cols-[15rem_minmax(0,1fr)] lg:gap-6"
>
    {{-- The sections are a menu, not a tab strip: a strip at the top of a form
         this long scrolls out of reach, this holds its place beside the form.
         `lg:top-14` clears the 56px sticky admin header. --}}
    <x-admin-settings-nav class="lg:sticky lg:top-14 lg:self-start" :tabs="[
        'colors' => ['Colors', 'swatch'],
        'typography' => ['Typography', 'bars-3-bottom-left'],
    ]" />

    {{-- Both panels stay mounted (x-show, not x-if) so every media picker keeps
         its Livewire binding. `x-cloak` on the column stops them all flashing
         at once before Alpine boots and picks the active one. --}}
    <div class="min-w-0 space-y-5" x-cloak>


    {{-- Colors — one per storefront area. Blank means "use the default"
         (partials/_head.blade.php turns each set value into the matching
         --color-* CSS variable). The mini storefront on the right previews
         the current values live, before saving. --}}
    @php
        // [key, label, default, hint] — keys are written out in full so the
        // Theme Settings component discovers them (see declaredScalarKeys()).
        $colorGroups = [
            'Header' => ['icon' => 'bars-3-bottom-left', 'fields' => [
                ['theme_classic_ecommerce_header_bg_color', 'Background', '#045b30', 'Logo, search and account bar'],
                ['theme_classic_ecommerce_header_text_color', 'Text & icons', '#ffffff', 'Site name, phone, icons'],
            ]],
            'Menu bar' => ['icon' => 'bars-3', 'fields' => [
                ['theme_classic_ecommerce_nav_bg_color', 'Background', '#ffffff', 'Categories / Brands / pages row'],
                ['theme_classic_ecommerce_nav_text_color', 'Links', '#222222', 'Menu link text'],
            ]],
            'Buttons' => ['icon' => 'cursor-arrow-rays', 'fields' => [
                ['theme_classic_ecommerce_button_bg_color', 'Background', '#045b30', 'Add to cart, Checkout, Place order'],
                ['theme_classic_ecommerce_button_text_color', 'Text', '#ffffff', 'Button labels'],
            ]],
            'Text' => ['icon' => 'language', 'fields' => [
                ['theme_classic_ecommerce_heading_color', 'Headings', '#171717', 'Titles, product names, totals'],
                ['theme_classic_ecommerce_text_color', 'Body text', '#262626', 'Regular copy and labels'],
                ['theme_classic_ecommerce_price_color', 'Prices', '#045b30', 'Prices on cards and product page'],
            ]],
            'Accents' => ['icon' => 'sparkles', 'fields' => [
                ['theme_classic_ecommerce_accent_color', 'Links & highlights', '#045b30', 'Links, badges, active states'],
                ['theme_classic_ecommerce_sale_color', 'Sale badge', '#c01616', 'The "-30%" discount badge'],
                ['theme_classic_ecommerce_page_bg_color', 'Page background', '#f2f4f8', 'Behind every storefront page'],
            ]],
            'Footer' => ['icon' => 'window', 'fields' => [
                ['theme_classic_ecommerce_footer_bg_color', 'Background', '#045b30', 'Main footer area'],
                ['theme_classic_ecommerce_footer_text_color', 'Text', '#ffffff', 'Headings, links, contact details'],
                ['theme_classic_ecommerce_footer_bottom_color', 'Copyright bar', '#1d2327', 'Thin strip at the very bottom'],
            ]],
        ];
    @endphp

    <div
        x-data="{
            // Current value of a color, or its default. Areas that default to
            // the accent follow it, exactly like the storefront CSS does.
            c(key, fallback) {
                const v = ($wire.settings[key] || '').trim();
                return /^#[0-9a-fA-F]{3,8}$/.test(v) ? v : fallback;
            },
            get accent() { return this.c('theme_classic_ecommerce_accent_color', this.c('theme_classic_ecommerce_primary_color', '#045b30')); },
        }"
        role="tabpanel" x-show="tab === 'colors'" x-cloak
    >
        <div class="grid grid-cols-1 gap-5 xl:grid-cols-[1fr_19rem] xl:items-start">
            {{-- Color groups --}}
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                @foreach ($colorGroups as $group => $meta)
                    <section class="overflow-hidden rounded-xl bg-white dark:bg-zinc-900">
                        {{-- Which part of the storefront these colours belong to.
                             Not a panel title — the fields below are labelled one
                             by one, so without the group a colour has nothing
                             saying whether it is the header, a product card or
                             the footer. --}}
                        <header class="flex items-center justify-between gap-3 bg-zinc-50/70 px-4 py-2.5 dark:bg-zinc-800/50">
                            <span class="flex items-center gap-2 text-sm font-semibold text-zinc-800 dark:text-zinc-100">
                                <flux:icon :name="$meta['icon']" variant="mini" class="size-4 text-zinc-400" />
                                {{ $group }}
                            </span>
                            {{-- The group's palette at a glance --}}
                            <span class="flex -space-x-1">
                                @foreach ($meta['fields'] as [$key, , $default])
                                    <span class="size-4 rounded-full ring-2 ring-white dark:ring-zinc-800"
                                        :style="`background: ${c('{{ $key }}', '{{ $default }}')}`"></span>
                                @endforeach
                            </span>
                        </header>

                        <div class="px-4">
                            @foreach ($meta['fields'] as [$key, $label, $default, $hint])
                                <div class="flex items-center justify-between gap-3 py-2.5">
                                    <label for="{{ $key }}" class="min-w-0">
                                        <span class="block truncate text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ $label }}</span>
                                        <span class="block truncate text-[11px] text-zinc-400">{{ $hint }}</span>
                                    </label>

                                    <div class="flex h-9 w-36 shrink-0 items-center gap-2 rounded-lg border border-zinc-200 bg-white pl-1.5 pr-1 transition focus-within:border-primary focus-within:ring-2 focus-within:ring-primary/15 dark:border-zinc-700 dark:bg-zinc-800">
                                        <span class="relative size-6 shrink-0 overflow-hidden rounded-md shadow-inner ring-1 ring-black/10"
                                            :style="`background: ${c('{{ $key }}', '{{ $default }}')}`">
                                            <input type="color" title="Pick a color"
                                                :value="c('{{ $key }}', '{{ $default }}')"
                                                @input="$wire.set('settings.{{ $key }}', $event.target.value, false)"
                                                class="absolute inset-0 size-full cursor-pointer opacity-0">
                                        </span>
                                        <input id="{{ $key }}" type="text" maxlength="9" spellcheck="false"
                                            wire:model="settings.{{ $key }}" placeholder="{{ $default }}"
                                            class="h-full min-w-0 flex-1 border-0! bg-transparent p-0 font-mono text-xs uppercase text-zinc-700 shadow-none! outline-none placeholder:normal-case placeholder:text-zinc-400 focus:ring-0 dark:text-zinc-200">
                                        <button type="button" title="Reset to default"
                                            x-show="$wire.settings['{{ $key }}']" x-cloak
                                            @click="$wire.set('settings.{{ $key }}', '', false)"
                                            class="flex size-6 shrink-0 items-center justify-center rounded-md text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-700">
                                            <flux:icon.arrow-uturn-left variant="micro" class="size-3.5" />
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>

            {{-- Live preview — a miniature storefront painted with the values above. --}}
            <aside class="overflow-hidden rounded-xl bg-white shadow-sm xl:sticky xl:top-24 dark:bg-zinc-900">
                <div class="p-3">
                    <div class="overflow-hidden rounded-lg" style="font-family: {{ \App\Support\ThemeFont::stackFor($settings['theme_classic_ecommerce_font'] ?? '') ?? "'Trebuchet MS', 'Segoe UI', sans-serif" }}">
                        {{-- Header --}}
                        <div class="flex items-center gap-2 px-2.5 py-2"
                            :style="`background: ${c('theme_classic_ecommerce_header_bg_color', accent)}; color: ${c('theme_classic_ecommerce_header_text_color', '#ffffff')}`">
                            <span class="size-3 rounded-full bg-current opacity-80"></span>
                            <span class="text-[10px] font-bold">Codeware</span>
                            <span class="ml-1 h-3.5 flex-1 rounded bg-white/90"></span>
                            <span class="size-2.5 rounded-sm border border-current opacity-80"></span>
                            <span class="size-2.5 rounded-full border border-current opacity-80"></span>
                        </div>
                        {{-- Menu bar --}}
                        <div class="flex gap-2.5 border-b border-black/5 px-2.5 py-1.5 text-[9px] font-semibold"
                            :style="`background: ${c('theme_classic_ecommerce_nav_bg_color', '#ffffff')}; color: ${c('theme_classic_ecommerce_nav_text_color', '#222222')}`">
                            <span>Categories</span><span>Brands</span><span :style="`color: ${accent}`">Home</span><span>Shop</span>
                        </div>
                        {{-- Page --}}
                        <div class="space-y-2 p-2.5" :style="`background: ${c('theme_classic_ecommerce_page_bg_color', '#f2f4f8')}`">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-bold" :style="`color: ${c('theme_classic_ecommerce_heading_color', '#171717')}`">Best sellers</span>
                                <span class="text-[9px] font-semibold" :style="`color: ${accent}`">View all →</span>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                @foreach (['Spearmint tea', 'Green tea'] as $i => $name)
                                    <div class="overflow-hidden rounded-md bg-white shadow-sm">
                                        <div class="relative h-12 bg-zinc-100">
                                            @if ($i === 0)
                                                <span class="absolute left-1 top-1 rounded px-1 text-[8px] font-bold text-white"
                                                    :style="`background: ${c('theme_classic_ecommerce_sale_color', '#c01616')}`">-30%</span>
                                            @endif
                                        </div>
                                        <div class="space-y-1 p-1.5">
                                            <p class="truncate text-[9px] font-semibold" :style="`color: ${c('theme_classic_ecommerce_heading_color', '#171717')}`">{{ $name }}</p>
                                            <p class="truncate text-[8px]" :style="`color: ${c('theme_classic_ecommerce_text_color', '#262626')}`">100 gm · Organic</p>
                                            <p class="text-[10px] font-bold" :style="`color: ${c('theme_classic_ecommerce_price_color', accent)}`">৳450</p>
                                            <span class="block rounded py-1 text-center text-[8px] font-semibold"
                                                :style="`background: ${c('theme_classic_ecommerce_button_bg_color', accent)}; color: ${c('theme_classic_ecommerce_button_text_color', '#ffffff')}`">Add to cart</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        {{-- Footer --}}
                        <div class="space-y-1 px-2.5 py-2" :style="`background: ${c('theme_classic_ecommerce_footer_bg_color', accent)}; color: ${c('theme_classic_ecommerce_footer_text_color', '#ffffff')}`">
                            <p class="text-[9px] font-bold">Quick Links</p>
                            <p class="text-[8px] opacity-75">About Us · Contact · FAQ</p>
                        </div>
                        <div class="px-2.5 py-1 text-[8px]" :style="`background: ${c('theme_classic_ecommerce_footer_bottom_color', '#1d2327')}; color: ${c('theme_classic_ecommerce_footer_text_color', '#ffffff')}`">
                            <span class="opacity-75">© Codeware. All rights reserved.</span>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    {{-- The face this theme's pages render in. Its own section rather than a
         field on Colors because a colour picker cannot show a typeface, and the
         live preview above belongs to the colours panel. A sibling of the
         colours panel, inside the stacked column, so it replaces it in place
         rather than becoming a third item in the outer grid. --}}
    <section role="tabpanel" x-show="tab === 'typography'" x-cloak
        class="rounded-xl bg-white p-5 dark:bg-zinc-900">
        <x-admin-font-picker
            model="settings.theme_classic_ecommerce_font"
            :value="$settings['theme_classic_ecommerce_font'] ?? ''"
            own-face="trebuchet"
            own-label="Trebuchet MS"
            hint="Applies to this theme's public pages only. Every theme keeps its own typeface until you say otherwise."
            description="Theme default keeps Trebuchet MS, the font this theme ships with. Anything else replaces it for this theme alone — the other themes are untouched." />
    </section>
    </div>
</div>

@fluxScripts
