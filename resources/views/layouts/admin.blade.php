<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        function applyAdminTheme() {
            document.documentElement.classList.toggle('dark', localStorage.getItem('admin-theme') === 'dark');
        }
        applyAdminTheme();
        // wire:navigate swaps the page without a full reload and morphs <html> to
        // match the freshly-fetched (always light) markup, wiping the class — so
        // it must be re-applied after every Livewire navigation, not just on load.
        document.addEventListener('livewire:navigated', applyAdminTheme);
    </script>
    <title>{{ ($title ?? 'Admin') . ' — ' . config('app.name') }}</title>
    @php
        $siteIcon = \App\Models\Setting::get('site_icon');
        $favicon = \App\Models\Setting::get('favicon');
        $sidebarLogo = \App\Models\Setting::get('sidebar_logo');
        $adminPrimaryColor = \App\Models\Setting::get('primary_color', '#1e7bc4');
        $adminSecondaryColor = \App\Models\Setting::get('secondary_color', '#7cc242');
        $calculatorEnabled = (bool) \App\Models\Setting::get('calculator_enabled', true);
        $stickyNoteEnabled = (bool) \App\Models\Setting::get('sticky_note_enabled', true);
        $notificationsEnabled = (bool) \App\Models\Setting::get('notifications_enabled', true);
        $appNameLabelEnabled = (bool) \App\Models\Setting::get('app_name_label_enabled', true);
        // The header widgets that only make sense alongside a feature are gone
        // with it (Settings → Features), not just un-configurable: a Shop On/Off
        // button on a panel with no products, a language dropdown on a panel with
        // no languages. settingAvailable() is the same rule the Settings toggle
        // for each of them is hidden by, so the two can't disagree.
        $shopToggleEnabled = \App\Support\Features::settingAvailable('shop_toggle_enabled')
            && (bool) \App\Models\Setting::get('shop_toggle_enabled', false);
        $languageSwitcherEnabled = \App\Support\Features::settingAvailable('language_switcher_enabled')
            && (bool) \App\Models\Setting::get('language_switcher_enabled', true);

        // The panel's typeface (see App\Support\AdminFont). Resolved here rather
        // than in a view composer because this is the only place that applies
        // it. System font and Segoe UI are faces the OS already has, so they
        // need no declaration and no preload; Roboto is self-hosted and does, so
        // those two pieces are resolved alongside the stack.
        $adminFont = \App\Support\AdminFont::normalize(\App\Models\Setting::get('admin_font'));
        $adminFontPreload = \App\Support\AdminFont::preloadFor($adminFont);
        $adminFontFaces = \App\Support\AdminFont::facesFor($adminFont);
    @endphp
    @if ($favicon)
        <link rel="icon" href="{{ $favicon }}" type="{{ Str::endsWith($favicon, '.svg') ? 'image/svg+xml' : 'image/x-icon' }}" sizes="any">
    @else
        <link rel="icon" href="/favicon/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon/favicon-32x32.png" type="image/png" sizes="32x32">
        <link rel="icon" href="/favicon/favicon-16x16.png" type="image/png" sizes="16x16">
    @endif
    <link rel="apple-touch-icon" href="{{ $siteIcon ?: '/favicon/apple-touch-icon.png' }}">
    <link rel="manifest" href="/favicon/site.webmanifest">

    {{-- The panel's webfont, when the chosen option is one the OS does not have
         (currently only Roboto). The @font-face declarations are inlined at the
         bottom of this file rather than linked, so an unselected webfont costs
         no request and a selected one is discovered in the same response as the
         HTML — no stylesheet request to discover the file in, and no waterfall
         behind it. A linked stylesheet would have to be fetched and parsed
         before the browser learns a font exists.

         Only the latin subset is preloaded, and deliberately: preloading a
         subset a page may never use is a request the preloader cannot cancel
         once started, so the choice is made here. See AdminFont::preloadFor(). --}}
    @if ($adminFontPreload)
        <link rel="preload" href="{{ $adminFontPreload }}" as="font" type="font/woff2" crossorigin>
    @endif

    {{-- Connect to the CDN before the first byte of it is asked for. --}}
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    {{-- Pinned, not "latest": "latest" is a redirect to a version on every cold load. --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.1.4/toastr.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" crossorigin="anonymous"
        referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.1.4/toastr.min.js" crossorigin="anonymous"
        referrerpolicy="no-referrer"></script>
    {{-- Jodit (~600 KB) is only needed on the three pages with a rich-text editor, so
         it is fetched on demand by the editor component instead of blocking every
         admin page. window.loadJodit() resolves once the script and its stylesheet
         are in; calling it again returns the same promise. --}}
    <script>
        window.loadJodit = window.loadJodit || (function () {
            let promise = null;
            return function () {
                if (window.Jodit) return Promise.resolve();
                if (promise) return promise;
                promise = new Promise(function (resolve, reject) {
                    const css = document.createElement('link');
                    css.rel = 'stylesheet';
                    css.href = 'https://unpkg.com/jodit@4.1.16/es2021/jodit.min.css';
                    document.head.appendChild(css);

                    const js = document.createElement('script');
                    js.src = 'https://unpkg.com/jodit@4.1.16/es2021/jodit.min.js';
                    js.onload = resolve;
                    js.onerror = function () { promise = null; reject(new Error('Could not load the editor.')); };
                    document.head.appendChild(js);
                });
                return promise;
            };
        })();
    </script>

    <style>
        .nav-group-items {
            overflow: hidden;
            transition: max-height 0.25s ease, opacity 0.2s ease;
        }

        .nav-group-items.collapsed {
            max-height: 0 !important;
            opacity: 0;
        }

        [x-cloak] {
            display: none !important;
        }

        /* Header app name: faint text with a soft light sweep passing over it. */
        .admin-app-name {
            --shine-base: #d4d4d8;
            --shine-glow: #ffffff;
            background: linear-gradient(110deg, var(--shine-base) 35%, var(--shine-glow) 50%, var(--shine-base) 65%) 200% 0 / 200% 100% no-repeat;
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            animation: admin-app-name-shine 8s linear infinite;
        }

        .dark .admin-app-name {
            --shine-base: #52525b;
            --shine-glow: #a1a1aa;
        }

        @keyframes admin-app-name-shine {
            to { background-position: -100% 0; }
        }

        @media (prefers-reduced-motion: reduce) {
            .admin-app-name { animation: none; background-position: 0 0; }
        }

        /* flux:header ships with z-10, same as the sticky right-hand "Actions"
           column used by admin list tables — on equal z-index the later-DOM
           element (the table, below the header) wins the paint order and
           renders on top of the header bar while scrolling. Raise the header
           above it. */
        .admin-header {
            z-index: 20;
        }

        /* Admin brand colors — from Settings → Theme → Backend. Placed after the
           compiled app.css, so this wins over both the light and .dark token
           blocks there (equal specificity, later in source). Primary also drives
           --color-accent, which is what buttons/focus rings/active nav actually use. */
        {{-- The @font-face for whichever option is a webfont, inlined so it needs
             no request of its own. Outside the :root block because @font-face is
             a top-level at-rule and would be invalid nested inside a rule.
             Also printed unescaped, and for the same reason as the stack below. --}}
        @foreach ($adminFontFaces as $face)
            @font-face {
                font-family: '{!! $face['family'] !!}';
                font-style: {!! $face['style'] !!};
                font-weight: {!! $face['weight'] !!};
                font-display: swap;
                src: url('{!! $face['url'] !!}') format('{!! $face['format'] !!}');
                @if ($face['unicodeRange'])
                unicode-range: {!! $face['unicodeRange'] !!};
                @endif
            }
        @endforeach

        :root {
            --color-primary: {{ $adminPrimaryColor }};
            --color-secondary: {{ $adminSecondaryColor }};
            --color-accent: {{ $adminPrimaryColor }};
            --color-accent-content: {{ $adminPrimaryColor }};
            /* Admin-only typeface — overrides the global --font-sans from
                 app.css. Resolved from the "Backend font" setting: the OS's own
                 UI font by default, Segoe UI named first, or the self-hosted
                 Roboto declared above.

                 Printed unescaped, deliberately. A font stack is quoted with
                 single quotes and double-brace escaping would turn each one into
                 an HTML entity, and entities are not decoded inside a <style>
                 element — the declaration would reach the parser as an invalid
                 family and be dropped, leaving the panel with no font override at
                 all. Safe to do because both stackFor() and facesFor() return
                 values from hard-coded tables in AdminFont and never interpolate
                 the stored setting. */
            --font-sans: {!! \App\Support\AdminFont::stackFor($adminFont) !!};
        }

        /* Row expand controls are useful on narrow screens, but their first
           column is empty on desktop. Collapse that spacer in every admin list. */
        @media (min-width: 1024px) {
            body.admin-panel table:has(> tbody > tr > td:first-child button[aria-expanded]) > colgroup > col:first-child,
            body.admin-panel table:has(> tbody > tr > td:first-child button[aria-expanded]) > thead > tr > th:first-child,
            body.admin-panel table:has(> tbody > tr > td:first-child button[aria-expanded]) > tbody > tr > td:first-child {
                display: none;
            }
        }
    </style>
</head>

<body class="admin-panel min-h-screen bg-slate-50 antialiased font-sans">

    <script>
        @if (session('success'))
            toastr.success("{{ session('success') }}");
        @endif
        @if (session('error'))
            toastr.error("{{ session('error') }}");
        @endif
        @if (session('warning'))
            toastr.warning("{{ session('warning') }}");
        @endif
        @if (session('info'))
            toastr.info("{{ session('info') }}");
        @endif
    </script>

    @php
        // Precomputed as JSON and echoed (rather than using @js()/@directive calls inside the
        // x-data attribute below) because flux:sidebar is a Blade component tag — directives
        // with parentheses embedded in a component tag's attribute value get mangled by Blade's
        // attribute-string compilation and blow up Livewire's morph-marker precompiler regex.
        $sidebarMenu = \App\Models\MenuItem::menuForCurrentUser();

        $sidebarGroupItemsJson = json_encode(
            $sidebarMenu->filter(fn ($i) => $i->is_group)
                ->mapWithKeys(fn ($i) => [
                    $i->id => $i->children->map(fn ($c) => __($c->label))->values()->all(),
                ])
                ->all()
        );
        $sidebarSinglesJson = json_encode(
            $sidebarMenu->reject('is_group')->map(fn ($i) => __($i->label))->values()->all()
        );
        $sidebarOpenGroupId = optional($sidebarMenu->first(fn ($g) => $g->is_group && $g->children->contains(
            fn ($c) => $c->route_name && (request()->routeIs($c->route_name) || request()->routeIs($c->route_name.'.*'))
        )))->id; 
    @endphp
    <flux:sidebar sticky stashable collapsible="desktop" class="admin-sidebar pt-1.5" x-data="{  
        search: '',
        openGroup: {{ $sidebarOpenGroupId ?? 'null' }},
        groupItems: {{ $sidebarGroupItemsJson }}, 
        query() { return this.search.trim().toLowerCase(); },
        searching() { return this.query() !== ''; },
        matches(label) {
            const q = this.query();
            return !q || label.toLowerCase().includes(q);
        },
        groupMatches(group) {
            const q = this.query();
            if (!q) return true;
            return this.groupItems[group].some(l => l.toLowerCase().includes(q));
        },
        groupOpen(group) {
            return this.groupMatches(group) && (this.searching() ? true : this.openGroup === group);
        },
        anyMatch() {
            const q = this.query();
            if (!q) return true;
            const singles = {{ $sidebarSinglesJson }};
            return singles.some(l => l.toLowerCase().includes(q)) ||
                Object.values(this.groupItems).flat().some(l => l.toLowerCase().includes(q));
        },
        toggle(group) { this.openGroup = this.openGroup === group ? null : group }
    }">
        <flux:sidebar.toggle class="lg:hidden self-end m-2 text-zinc-400 hover:text-zinc-200" icon="x-mark" />

        {{-- Logo + search --}}
        <div class="px-0 py-3 border-b border-gray-100 shrink-0 flex items-center gap-3 pt-1 pb-2">   
            <a href="{{ route('admin.dashboard') }}" wire:navigate.hover title="{{ config('app.name') }}"
                class="flex items-center gap-3 admin-sidebar-logo shrink-0"> 
                <div class="rounded">  
                    <img src="{{ $sidebarLogo ?: ($siteIcon ?: '/default/logo.png') }}" alt="{{ config('app.name') }}" class="w-10">
                </div>  
                <!-- <div class="min-w-0">
                    <p class="truncate text-sm font-bold tracking-wide text-white">{{ config('app.name') }}</p>
                    <p class="mt-0.5 text-[11px] font-medium text-slate-300">Admin workspace</p>
                </div> -->
            </a>

            <div class="relative flex-1 min-w-0 admin-sidebar-search">  
                    <flux:icon.magnifying-glass
                        class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-gray-400 pointer-events-none" /> 
                <input type="text" x-model="search" placeholder="{{ __('Search menu...') }}" autocomplete="off"
                    name="sidebar_menu_filter_{{ \Illuminate\Support\Str::random(6) }}" autocapitalize="off" spellcheck="false" data-lpignore="true" data-1p-ignore
                    x-init="$nextTick(() => { search = ''; $el.value = ''; }); setTimeout(() => { search = ''; $el.value = ''; }, 150)"
                    x-on:pageshow.window="search = ''; $el.value = ''"
                    class="admin-sidebar-search-input w-full bg-gray-50 border border-primary! rounded! pl-9 pr-8 py-1.5 text-sm text-gray-800 placeholder:text-gray-400 outline-none transition"> 
                <button type="button" x-show="search" x-on:click="search = ''"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
                    <flux:icon.x-mark class="size-4" />
                </button>
            </div> 
        </div>

        {{-- Navigation --}}
        <nav id="admin-sidebar-nav" class="flex-1 overflow-y-auto px-2 py-2 custom-sidebar-nav pl-0.5">

            @foreach ($sidebarMenu as $item)
                @if ($item->is_group)
                    @php
                        $groupIcon = [
                            'Products' => 'cube',
                            'Accessories' => 'puzzle-piece',
                            'Service' => 'wrench-screwdriver',
                            'Vouchers' => 'gift',
                            'Sales' => 'shopping-bag',
                            'Blog' => 'pencil-square',
                            'Library & System' => 'folder',
                            'Inquiries' => 'inbox',
                            'Client Queries' => 'chat-bubble-left-right',
                            'Content' => 'document-text',
                            'Localization' => 'language',
                            'Access Control' => 'shield-check',
                            'Location' => 'map',
                            'Advance' => 'adjustments-horizontal',
                            // cube-transparent, not puzzle-piece: Accessories already
                            // took that one, and two sidebar groups wearing the same
                            // icon read as one. The outline cube says "a module you
                            // plug in" and stays clear of Products' solid cube.
                            'Plugins' => 'cube-transparent',
                        ][$item->label] ?? 'squares-2x2';
                    @endphp
                    <div class="nav-group" x-show="groupMatches({{ $item->id }})">
                        <button @click="toggle({{ $item->id }})" title="{{ __($item->label) }}"
                            class="admin-nav-group-label w-full flex items-center justify-between cursor-pointer select-none"
                            :class="{ 'admin-nav-group-active': groupOpen({{ $item->id }}) }">
                            <span class="flex items-center gap-2">
                                <span class="admin-nav-group-icon">
                                    <x-dynamic-component :component="'flux::icon.'.$groupIcon" class="size-4.5" />
                                </span>
                                <span>{{ __($item->label) }}</span>
                                @php $groupBadge = $item->children->sum(fn ($c) => $c->badgeCount()); @endphp
                                @if ($groupBadge > 0)
                                    <span class="inline-block size-2 rounded-full" style="background:#f43f5e" title="{{ $groupBadge }} {{ __('unread') }}"></span>
                                @endif
                            </span>
                            <span class="admin-nav-group-chevron-wrap flex shrink-0 items-center justify-center">
                                <flux:icon.chevron-right class="admin-nav-group-chevron size-4 transition-transform duration-200"
                                    x-bind:style="groupOpen({{ $item->id }}) ? 'transform: rotate(90deg)' : ''" />
                            </span>
                        </button>
                        <div class="nav-group-items" :class="{ 'collapsed': !groupOpen({{ $item->id }}) }"
                            :style="groupOpen({{ $item->id }}) ? 'max-height: 500px; opacity: 1;' : ''">
                            @foreach ($item->children as $child)
                                @include('partials._admin-nav-link', ['link' => $child])
                            @endforeach
                        </div>
                    </div>
                @else
                    <div x-show="matches({{ \Illuminate\Support\Js::from(__($item->label)) }})">
                        @include('partials._admin-nav-link', ['link' => $item, 'navigationStyle' => 'top-level'])
                    </div>
                @endif
            @endforeach

            <div x-show="searching() && !anyMatch()" x-cloak
                class="px-3 py-8 text-center text-sm text-zinc-500">
                {{ __('No menu items found for') }} "<span x-text="search" class="text-zinc-400 font-medium"></span>"
            </div>

        </nav>

        <div class="admin-sidebar-logout shrink-0">
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit"
                    class="admin-sidebar-logout-button"
                    title="{{ __('Log out') }}">
                    <span class="admin-sidebar-logout-icon">
                        <flux:icon.arrow-right-start-on-rectangle class="size-4.5" />
                    </span>
                    <span class="admin-sidebar-logout-copy">
                        <span class="admin-sidebar-logout-title">{{ __('Log out') }}</span>
                    </span>
                </button>
            </form>
        </div>

    </flux:sidebar>

    {{-- Main content --}}
    <flux:main class="admin-main" style="position: relative;"
        x-data="{
            screenOptionsOpen: false,
            screenOptionsDragging: false,
            screenOptionsHeight: 0,
            screenOptionsStartY: 0,
            screenOptionsStartHeight: 0,
            screenOptionsMoved: false,
            screenOptionsSuppressClick: false,
            beginScreenOptionsDrag(event) {
                if (event.button !== 0) return;
                this.screenOptionsDragging = true;
                this.screenOptionsMoved = false;
                this.screenOptionsStartY = event.clientY;
                this.screenOptionsStartHeight = this.screenOptionsOpen ? 105 : 0;
            },
            moveScreenOptionsDrag(event) {
                if (!this.screenOptionsDragging) return;
                const delta = event.clientY - this.screenOptionsStartY;
                if (Math.abs(delta) > 6) this.screenOptionsMoved = true;
                this.screenOptionsHeight = Math.max(0, Math.min(105, this.screenOptionsStartHeight + delta));
            },
            endScreenOptionsDrag() {
                if (!this.screenOptionsDragging) return;
                this.screenOptionsDragging = false;
                if (this.screenOptionsMoved) {
                    this.screenOptionsOpen = this.screenOptionsHeight >= 53;
                    this.screenOptionsSuppressClick = true;
                }
                this.screenOptionsHeight = this.screenOptionsOpen ? 105 : 0;
            },
            toggleScreenOptions() {
                if (this.screenOptionsSuppressClick) { this.screenOptionsSuppressClick = false; return; }
                this.screenOptionsOpen = !this.screenOptionsOpen;
                this.screenOptionsHeight = this.screenOptionsOpen ? 105 : 0;
            }
        }"
        x-on:pointermove.window="moveScreenOptionsDrag($event)"
        x-on:pointerup.window="endScreenOptionsDrag()"
        x-on:pointercancel.window="endScreenOptionsDrag()">
        <flux:header sticky class="admin-header shrink-0 gap-1 px-2! lg:px-3!">
            <flux:sidebar.toggle class="lg:hidden mr-1" icon="bars-3" inset="left" />
            <flux:sidebar.toggle class="max-lg:hidden" icon="bars-3" />
            <flux:button size="sm" variant="subtle" square x-data x-on:click="location.reload()" icon="arrow-path"
                aria-label="Refresh" class="max-lg:hidden" />
            <flux:button size="sm" variant="subtle" square :href="config('app.frontend_url')" icon="globe-alt"
                target="_blank" aria-label="Open frontend" class="max-lg:hidden" />

            <div class="flex-1"></div>

            @if ($appNameLabelEnabled)
                <div class="pointer-events-none min-w-0 max-w-[35%] lg:absolute lg:left-1/2 lg:top-1/2 lg:-translate-x-1/2 lg:-translate-y-1/2">
                    <span class="admin-app-name truncate text-xl font-extrabold tracking-wide">{{ config('app.name') }}</span>
                </div>
            @endif

            <div class="flex items-center gap-1.5">
                @include('partials._admin-command-palette')

                <x-admin-quick-menu />

                @if ($languageSwitcherEnabled)
                    <livewire:admin.locale-switcher />
                @endif

                @if ($shopToggleEnabled)
                    <livewire:admin.shop-toggle />
                @endif

                @if ($calculatorEnabled)
                    <button type="button" x-data
                        @click="$dispatch('toggle-calculator')"
                        title="{{ __('Calculator') }}" aria-label="{{ __('Calculator') }}"
                        class="inline-flex size-8 items-center justify-center rounded-lg text-zinc-500 transition-colors hover:bg-zinc-100 hover:text-zinc-800 focus:outline-none focus:ring-2 focus:ring-primary/30 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100">
                        <flux:icon.calculator class="size-5" />
                    </button>
                @endif

                @if ($stickyNoteEnabled)
                    <button type="button" x-data
                        @click="$dispatch('toggle-sticky-note')"
                        title="{{ __('Sticky Note') }}" aria-label="{{ __('Sticky Note') }}"
                        class="inline-flex size-8 items-center justify-center rounded-lg text-zinc-500 transition-colors hover:bg-zinc-100 hover:text-zinc-800 focus:outline-none focus:ring-2 focus:ring-primary/30 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100">
                        <flux:icon.document-text class="size-5" />
                    </button>
                @endif

                {{-- Header widgets contributed by active plugins (plugins/{slug}/header.blade.php) --}}
                @foreach (\App\Support\Plugins::headerViews() as $pluginHeaderView)
                    @include($pluginHeaderView)
                @endforeach

                <button type="button" x-data="{ dark: document.documentElement.classList.contains('dark') }"
                    @click="dark = !dark; document.documentElement.classList.toggle('dark', dark); localStorage.setItem('admin-theme', dark ? 'dark' : 'light')"
                    :title="dark ? '{{ __('Switch to light mode') }}' : '{{ __('Switch to dark mode') }}'"
                    :aria-label="dark ? '{{ __('Switch to light mode') }}' : '{{ __('Switch to dark mode') }}'"
                    class="inline-flex size-8 items-center justify-center rounded-lg text-zinc-500 transition-colors hover:bg-zinc-100 hover:text-zinc-800 focus:outline-none focus:ring-2 focus:ring-primary/30 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100">
                    <flux:icon.sun x-show="dark" x-cloak class="size-5" />
                    <flux:icon.moon x-show="!dark" class="size-5" />
                </button> 

                <button type="button" x-data="{ isFullscreen: false }"
                    x-init="isFullscreen = !!document.fullscreenElement; document.addEventListener('fullscreenchange', () => isFullscreen = !!document.fullscreenElement)"
                    @click="isFullscreen ? document.exitFullscreen() : document.documentElement.requestFullscreen()"
                    :aria-label="isFullscreen ? 'Exit full screen' : 'Enter full screen'"
                    :title="isFullscreen ? 'Exit full screen' : 'Full screen'" :aria-pressed="isFullscreen"
                    class="inline-flex size-8 items-center justify-center rounded-lg text-zinc-500 transition-colors hover:bg-zinc-100 hover:text-zinc-800 focus:outline-none focus:ring-2 focus:ring-primary/30 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100">
                    <svg x-show="!isFullscreen" class="size-5" fill="none" viewBox="0 0 24 24" stroke-width="2"
                        stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M8.25 3.75H5.625A1.875 1.875 0 0 0 3.75 5.625V8.25m16.5 0V5.625a1.875 1.875 0 0 0-1.875-1.875H15.75m0 16.5h2.625a1.875 1.875 0 0 0 1.875-1.875V15.75m-16.5 0v2.625a1.875 1.875 0 0 0 1.875 1.875H8.25" />
                    </svg>
                    <svg x-cloak x-show="isFullscreen" class="size-5" fill="none" viewBox="0 0 24 24"
                        stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M8.25 9.75H3.75m0 0v-4.5m0 4.5 5.25-5.25m6.75 5.25h4.5m0 0v-4.5m0 4.5-5.25-5.25m-6.75 9.75H3.75m0 0v4.5m0-4.5 5.25 5.25m6.75-5.25h4.5m0 0v4.5m0-4.5-5.25 5.25" />
                    </svg>
                </button> 

                @if ($notificationsEnabled)
                    <livewire:admin.notifications.bell />
                @endif

                <button type="button" aria-label="{{ __('Screen elements') }}" aria-controls="admin-screen-options-panel"
                    x-bind:aria-expanded="screenOptionsOpen.toString()"
                    x-on:pointerdown="beginScreenOptionsDrag($event)"
                    x-on:click="toggleScreenOptions()"
                    x-bind:style="'position: absolute; right: 0; bottom: -10px; z-index: 9999; transform: translateY(' + (screenOptionsDragging ? screenOptionsHeight : (screenOptionsOpen ? 105 : 0)) + 'px);'"
                    x-bind:class="screenOptionsDragging ? 'duration-0' : 'duration-300 ease-[cubic-bezier(0.22,1,0.36,1)]'"
                    class="group inline-flex h-4 cursor-grab touch-none select-none items-center justify-center rounded-bl-md border border-t-0 border-zinc-200/80 bg-white/95 px-1 text-zinc-500 shadow-[0_4px_12px_-6px_rgba(15,23,42,0.22)] backdrop-blur transition-[transform,color,background-color,border-color,box-shadow] hover:bg-white hover:text-zinc-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40 active:cursor-grabbing dark:border-zinc-700/80 dark:bg-zinc-900/95 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100">
                    <svg class="size-2.5 text-zinc-400 transition-transform duration-300 ease-out dark:text-zinc-500"
                        x-bind:class="screenOptionsOpen ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor"
                        aria-hidden="true">
                        <path fill-rule="evenodd"
                            d="M5.22 7.22a.75.75 0 0 1 1.06 0L10 10.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.28a.75.75 0 0 1 0-1.06Z"
                            clip-rule="evenodd" />
                    </svg>
                </button>
            </div> 

            <div class="w-px h-6 bg-zinc-200/80 mx-2 max-sm:hidden"></div>

            <x-admin-user-menu />
        </flux:header>

        {{-- The arrow stays attached to the panel's lower edge and moves with it. --}}
        <div
            x-bind:style="'height: ' + (screenOptionsDragging ? screenOptionsHeight : (screenOptionsOpen ? 105 : 0)) + 'px'"
            x-bind:class="screenOptionsDragging ? 'duration-0' : 'duration-300 ease-[cubic-bezier(0.22,1,0.36,1)]'"
            style="position: absolute; top: 56px; left: 0; right: 0; z-index: 45; height: 0;"
            class="overflow-hidden transition-[height] will-change-[height]">
            <div id="admin-screen-options-panel"
                x-bind:style="'height: ' + (screenOptionsDragging ? screenOptionsHeight : (screenOptionsOpen ? 105 : 0)) + 'px'"
                x-bind:class="screenOptionsDragging ? 'duration-0' : 'duration-300 ease-[cubic-bezier(0.22,1,0.36,1)]'"
                style="height: 0;"
                class="overflow-hidden border-b border-zinc-200 bg-white shadow-[0_18px_36px_-18px_rgba(15,23,42,0.28)] transition-[height] will-change-[height] dark:border-zinc-700 dark:bg-zinc-900">
                <div class="h-[105px] px-6 py-3">
                    <h2 class="flex items-center gap-2 text-sm font-semibold text-zinc-700 dark:text-zinc-200">
                        <flux:icon.adjustments-horizontal class="size-4 text-zinc-400 dark:text-zinc-500" />
                        {{ __('Screen elements') }}
                    </h2>
                    <p class="mt-2 text-xs leading-relaxed text-zinc-500 dark:text-zinc-400">
                        Choose which screen elements to show. More options will be added here later.
                    </p>
                </div>
            </div>
        </div>

        <div class="flex-1 p-3 max-w-[1600px] w-full mx-auto">
            @unless ($hidePageHeading ?? false)
                <div class="mb-3 flex items-center justify-between gap-4 flex-wrap">
                    <div>
                        @include('partials._admin-breadcrumbs')
                    </div> 
                    <div class="flex items-center gap-2 shrink-0 page-header-actions empty:hidden">
                        @stack('page-header-actions')
                    </div>
                </div>
            @endunless

            {{ $slot }}
        </div>

        <footer class="shrink-0 border-t border-zinc-200/70 bg-white/60 backdrop-blur px-4 py-4 md:px-6">
            <div
                class="max-w-[1600px] w-full mx-auto flex flex-col sm:flex-row items-center justify-between gap-2 text-xs font-medium text-zinc-400">
                <p>&copy; {{ date('Y') }} {{ \App\Models\Setting::get('copyright_name', 'Codeware Limited') }}. All rights reserved.</p>
                <p class="flex items-center gap-1.5 text-zinc-400">
                    <flux:icon.cube class="size-3.5" />
                    v{{ config('app.version') }}
                </p>
            </div>
        </footer>
    </flux:main>

    {{-- Honours the dispatcher's optional `type`, matching the auth split
         layout: a component that dispatches('notify', type: 'error') was being
         shown as a green success toast here, which mislabelled every failure
         message in the admin panel. Defaults to 'success' so the ~80 existing
         call sites that pass only a message are unaffected. --}}
    <div x-data x-on:notify.window="toastr[$event.detail.type || 'success']($event.detail.message)"></div>

    @if ($calculatorEnabled)
        @persist('admin-calculator')
            @include('partials._admin-calculator')
        @endpersist
    @endif

    @if ($stickyNoteEnabled)
        @persist('admin-sticky-note')
            @include('partials._admin-sticky-note')
        @endpersist
    @endif

    @include('partials._admin-floating-button')

    @include('partials._admin-loader-overlay')

    @fluxScripts

    {{-- Auto-scroll sidebar so active menu item is always visible --}}
    <script>
        function scrollActiveNavItem() {
            const nav = document.getElementById('admin-sidebar-nav');
            if (!nav) return;

            const activeItem = nav.querySelector('.admin-nav-active');
            if (!activeItem) return;

            // Get true position of active item inside the scrollable nav container
            const navRect = nav.getBoundingClientRect();
            const itemRect = activeItem.getBoundingClientRect();

            // item position relative to nav visible area + current scrollTop = absolute position in scroll container
            const absoluteTop = nav.scrollTop + (itemRect.top - navRect.top);

            // Scroll so active item is near top with 16px padding
            nav.scrollTop = absoluteTop - 16;
        }

        // 1. Normal full page load
        document.addEventListener('DOMContentLoaded', scrollActiveNavItem);

        // 2. Livewire wire:navigate — fires after each Livewire page navigation
        document.addEventListener('livewire:navigated', scrollActiveNavItem);
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            @if (Auth::check())
                window.Echo.private('test-private-channel.{{ Auth::id() }}').listen('TestPrivateChannel', (
                    e) => {
                    alert('Private channel event received: ' + JSON.stringify(e.msg));
                });
            @endif
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            window.Echo.channel('test-public-channel').listen('TestPublicChannel', (e) => {
                alert('Public channel event received: ' + JSON.stringify(e.msg));
            });
        });
    </script>
    
</body>

</html>
