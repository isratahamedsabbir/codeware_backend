@push('page-header-actions')
    {{-- Environment — sits in the layout's header row, beside the breadcrumb,
         like Theme Settings' header buttons. Rendered through @push/@stack, so
         it has no wire:id ancestor: a plain window event opens the modal. Not
         saved on change like the widgets — a wrong APP_ENV can take the site
         down, so it keeps an explicit Save plus a confirmation. --}}
    <flux:button size="sm" variant="primary" icon="rocket-launch"
        onclick="window.dispatchEvent(new CustomEvent('open-modal', { detail: { name: 'settings-env' } }))">
        {{ __('Environment') }}: {{ config('app.env') }}
    </flux:button>
@endpush

<div>
    {{-- Alpine tab switcher: General | Currency | ... --}}
    <div x-data="{
        tab: new URLSearchParams(location.search).get('tab') || localStorage.getItem('admin-settings-tab') || 'general',
        init() {
            const validTabs = ['general', 'widgets'];
            // Custom Code and Constant moved to Developer Tools — keep old links working.
            if (this.tab === 'custom-code' || this.tab === 'constant') {
                location.replace('{{ route('admin.env') }}?tab=' + this.tab);
                return;
            }
            // 'other' was this tab's old name — keep old links/bookmarks working.
            if (this.tab === 'other') this.tab = 'widgets';
            // 'security' held the MFA policy switches, which moved to the per-role
            // toggles on Roles — a bookmark to it falls back to General.
            if (!validTabs.includes(this.tab)) this.tab = 'general';
            this.$watch('tab', (value) => {
                try { localStorage.setItem('admin-settings-tab', value) } catch (e) {}
                // Keep the address shareable: ?tab=… reopens the same tab.
                const url = new URL(location.href);
                url.searchParams.set('tab', value);
                history.replaceState(history.state, '', url);
            });
        }
    }">

        {{-- Tab nav — a card of icon tabs, each with a one-line summary. --}}
        @php
            $settingsTabs = [
                'general' => ['General', 'cog-6-tooth', 'Currency, VAT, site identity, images'],
                'widgets' => ['Widgets', 'squares-2x2', 'Header widgets, toggles, admin tools'],
            ];
        @endphp
        <div role="tablist" aria-label="Settings sections"
            class="mb-6 flex gap-1 overflow-x-auto rounded-xl border border-zinc-200 bg-white p-1.5 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            @foreach ($settingsTabs as $key => [$label, $icon, $summary])
                <button type="button" role="tab" @click="tab = '{{ $key }}'"
                    :aria-selected="tab === '{{ $key }}'"
                    :class="tab === '{{ $key }}'
                        ? 'bg-primary/10 text-primary ring-1 ring-primary/20'
                        : 'text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-zinc-100'"
                    class="group flex min-w-44 flex-1 items-center gap-3 rounded-lg! px-3.5 py-2.5 text-left transition-colors">
                    <span :class="tab === '{{ $key }}'
                            ? 'bg-primary text-white shadow-sm'
                            : 'bg-zinc-100 text-zinc-500 group-hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-400'"
                        class="flex size-9 shrink-0 items-center justify-center rounded-lg transition-colors">
                        <flux:icon :name="$icon" variant="mini" class="size-4.5" />
                    </span>
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-semibold">{{ $label }}</span>
                        <span class="block truncate text-[11px] font-normal text-zinc-400">{{ $summary }}</span>
                    </span>
                </button>
            @endforeach
        </div>

        {{-- General tab --}}
        <div x-show="tab === 'general'">
            <div class="max-w-[1600px] space-y-5">
                {{-- General (site name, tagline, contact …) leads the tab. --}}
                @if (isset($groupedSettings['general']))
                    @include('partials._admin-settings-group-card', ['group' => 'general', 'items' => $groupedSettings['general']])
                @endif

                {{-- Currency, VAT / Tax, Localization, Pagination, Newsletter, Backend and
                     Telegram live on Developer Tools → Site Settings now. --}}
                <div class="grid grid-cols-1 gap-5 items-start">
                    <div class="space-y-5">
                        {{-- Image uploads sit right under General so the space they
                             would otherwise leave empty is put to use. --}}
                        @foreach ($groupedSettings as $group => $items)
                            @continue ($group !== 'images')
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 items-start">
                                @foreach ($items as $setting)
                                    @continue (! in_array($setting->key, ['site_icon', 'site_icon_white', 'favicon', 'loader', 'sidebar_logo'], true))
                                    @php
$imageMeta = match ($setting->key) {
                                            'favicon' => ['title' => 'Favicon', 'hint' => '32×32px, square', 'size' => '32 × 32', 'placeholder' => 'Choose a favicon from the library', 'mimes' => 'ico,png', 'maxSize' => 1],
                                            'loader' => ['title' => 'Loader', 'hint' => '200×200px, square', 'size' => '200 × 200', 'placeholder' => 'Choose a loading animation from the library', 'mimes' => 'gif,png,jpg', 'maxSize' => 2],
                                            'site_icon_white' => ['title' => 'White Icon', 'hint' => '512×512px, transparent', 'size' => '512 × 512', 'placeholder' => 'Choose a white icon from the library', 'mimes' => 'png,webp,avif', 'maxSize' => 2],
                                            'sidebar_logo' => ['title' => 'Sidebar Logo', 'hint' => 'Admin panel sidebar brand image', 'size' => 'logo', 'placeholder' => 'Choose a logo from the library', 'mimes' => 'png,webp,avif,jpg,svg', 'maxSize' => 2],
                                            default => ['title' => 'Site Icon', 'hint' => '512×512px, transparent', 'size' => '512 × 512', 'placeholder' => 'Choose a site icon from the library', 'mimes' => 'png,webp,avif', 'maxSize' => 2],
                                        };
                                    @endphp
                                    <x-admin-section-card header-border="border-zinc-100" icon="photo" :title="$imageMeta['title']"
                                        :description="$imageMeta['hint']">
                                        <x-media-picker model="settings.{{ $setting->key }}" label="" :size-hint="$imageMeta['size']"
                                            :placeholder="$imageMeta['placeholder']" :mimes="$imageMeta['mimes']"
                                            :max-size-mb="$imageMeta['maxSize']" only-images dropzone />
                                    </x-admin-section-card>
                                @endforeach
                            </div>
                        @endforeach
                    </div>

                    @foreach ($groupedSettings as $group => $items)
                        @continue (in_array($group, ['general', 'localization', 'pagination', 'newsletter', 'images'], true))
                        <div class="lg:col-span-2">
                            @include('partials._admin-settings-group-card', ['group' => $group, 'items' => $items])
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Widgets tab — each widget is its own card, straight in the grid.

             A toggle whose feature is off (Settings → Features) drops out here
             rather than configuring something the panel no longer has. The
             header widget each one enables asks the same question, so the two
             never disagree — see Features::settingAvailable(). --}}
        @php
            $shopToggleAvailable = \App\Support\Features::settingAvailable('shop_toggle_enabled');
            $languageSwitcherAvailable = \App\Support\Features::settingAvailable('language_switcher_enabled');
        @endphp
        <div x-show="tab === 'widgets'">
            <div class="max-w-[1600px]">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 items-start">
                {{-- Floating action button --}}
                <x-admin-section-card header-border="border-zinc-100" x-data icon="cursor-arrow-rays" title="Floating Button"
                    description="Shows a floating button in the corner of every admin page.">
                    <x-slot:actions>
                        <label class="flex items-center gap-2 text-sm text-zinc-600 cursor-pointer">
                            <input type="checkbox" wire:model.live="settings.floating_button_enabled" class="rounded border-zinc-300 text-primary" />
                            Enable
                        </label>
                    </x-slot:actions>

                    <flux:field>
                        <flux:label>Action</flux:label>
                        <select wire:model.live="settings.floating_button_action"
                            class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm text-zinc-700">
                            <option value="link">Open link</option>
                            <option value="back">Go back</option>
                            <option value="top">Go to top</option>
                        </select>
                    </flux:field>
                    <flux:field x-show="$wire.settings.floating_button_action === 'link'">
                        <flux:label>Link URL</flux:label>
                        <flux:input wire:model.blur="settings.floating_button_link" placeholder="https://example.com" />
                    </flux:field>
                </x-admin-section-card>

                {{-- Table actions display --}}
                <x-admin-section-card header-border="border-zinc-100" icon="ellipsis-horizontal" title="Table Actions"
                    description="How action buttons (Edit, Delete, ...) appear on admin list tables.">
                    <flux:field>
                        <flux:label>Display style</flux:label>
                        <select wire:model.live="settings.admin_actions_display"
                            class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm text-zinc-700">
                            <option value="inline">Inline icon buttons</option>
                            <option value="dropdown">Three-dot dropdown menu</option>
                        </select>
                    </flux:field>
                </x-admin-section-card>

                {{-- Watermark was here — moved to the Media Library, where a
                     button in the header opens a modal for configuring it. --}}

                {{-- Calculator widget --}}
                <x-admin-section-card header-border="border-zinc-100" icon="calculator" title="Calculator"
                    description="Adds a calculator icon to the admin header for quick math.">
                    <x-slot:actions>
                        <label class="flex items-center gap-2 text-sm text-zinc-600 cursor-pointer">
                            <input type="checkbox" wire:model.live="settings.calculator_enabled" class="rounded border-zinc-300 text-primary" />
                            Enable
                        </label>
                    </x-slot:actions>

                    <p class="text-xs text-zinc-400">
                        Shortcut: <span class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1.5 py-0.5 rounded">Ctrl</span> +
                        <span class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1.5 py-0.5 rounded">Alt</span> +
                        <span class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1.5 py-0.5 rounded">C</span>
                        opens or closes it from anywhere.
                    </p>
                </x-admin-section-card>

                {{-- Sticky note widget --}}
                <x-admin-section-card header-border="border-zinc-100" icon="document-text" title="Sticky Note"
                    description="Adds a note icon to the admin header for quick reminders.">
                    <x-slot:actions>
                        <label class="flex items-center gap-2 text-sm text-zinc-600 cursor-pointer">
                            <input type="checkbox" wire:model.live="settings.sticky_note_enabled" class="rounded border-zinc-300 text-primary" />
                            Enable
                        </label>
                    </x-slot:actions>

                    <p class="text-xs text-zinc-400">
                        Shortcut: <span class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1.5 py-0.5 rounded">Ctrl</span> +
                        <span class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1.5 py-0.5 rounded">Alt</span> +
                        <span class="font-mono text-xs bg-zinc-100 dark:bg-zinc-800 px-1.5 py-0.5 rounded">N</span>
                        opens or closes it from anywhere.
                    </p>
                </x-admin-section-card>

                {{-- App name label --}}
                <x-admin-section-card header-border="border-zinc-100" icon="tag" title="App Name"
                    description="Shows the app name, with a soft shine, in the middle of the admin header.">
                    <x-slot:actions>
                        <label class="flex items-center gap-2 text-sm text-zinc-600 cursor-pointer">
                            <input type="checkbox" wire:model.live="settings.app_name_label_enabled" class="rounded border-zinc-300 text-primary" />
                            Enable
                        </label>
                    </x-slot:actions>

                    <p class="text-xs text-zinc-400">
                        The name comes from the app name in the environment settings.
                    </p>
                </x-admin-section-card>

                {{-- Notification bell --}}
                <x-admin-section-card header-border="border-zinc-100" icon="bell" title="Notifications"
                    description="Shows the notification bell in the admin header.">
                    <x-slot:actions>
                        <label class="flex items-center gap-2 text-sm text-zinc-600 cursor-pointer">
                            <input type="checkbox" wire:model.live="settings.notifications_enabled" class="rounded border-zinc-300 text-primary" />
                            Enable
                        </label>
                    </x-slot:actions>

                    <p class="text-xs text-zinc-400">
                        Turning this off only hides the bell. Notifications are still recorded, so turning it back on finds them all still there.
                    </p>
                </x-admin-section-card>

                {{-- Shop toggle --}}
                @if ($shopToggleAvailable)
                <x-admin-section-card header-border="border-zinc-100" icon="building-storefront" title="Shop Toggle"
                    description="Adds a Shop On/Off button to the admin header for quickly closing the storefront to new orders.">
                    <x-slot:actions>
                        <label class="flex items-center gap-2 text-sm text-zinc-600 cursor-pointer">
                            <input type="checkbox" wire:model.live="settings.shop_toggle_enabled" class="rounded border-zinc-300 text-primary" />
                            Enable
                        </label>
                    </x-slot:actions>

                    <p class="text-xs text-zinc-400">
                        While the shop is off, customers cannot place new orders.
                    </p>
                </x-admin-section-card>
                @endif

                {{-- Language switcher --}}
                @if ($languageSwitcherAvailable)
                <x-admin-section-card header-border="border-zinc-100" icon="language" title="Language Switcher"
                    description="Shows the language dropdown in the admin header.">
                    <x-slot:actions>
                        <label class="flex items-center gap-2 text-sm text-zinc-600 cursor-pointer">
                            <input type="checkbox" wire:model.live="settings.language_switcher_enabled" class="rounded border-zinc-300 text-primary" />
                            Enable
                        </label>
                    </x-slot:actions>

                    <p class="text-xs text-zinc-400">
                        Turning this off does not change the admin panel's language, only hides the switcher itself.
                    </p>
                </x-admin-section-card>
                @endif

                </div>
            </div>
        </div>

        {{-- Widgets save on change (see updatedSettings()), so only General needs the button. --}}
        <div class="mt-6" x-show="tab === 'general'">
            <flux:button variant="primary" size="sm" wire:click="save" wire:loading.attr="disabled">
                Save Settings
            </flux:button>
        </div>
    </div>

    {{-- Environment editor (opened from the button above the tabs) --}}
    <flux:modal name="settings-env" class="md:w-[28rem]"
        x-on:open-modal.window="if ($event.detail.name === 'settings-env') $flux.modal('settings-env').show()"
        x-on:close-modal.window="if ($event.detail.name === 'settings-env') $flux.modal('settings-env').close()">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                    <flux:icon.rocket-launch class="size-5" />
                </div>
                <div>
                    <flux:heading>{{ __('Environment') }}</flux:heading>
                    <flux:text class="text-xs">{{ __('Which environment this install runs as.') }}</flux:text>
                </div>
            </div>

            <flux:field>
                <flux:label>{{ __('Runtime environment') }}</flux:label>
                <select wire:model="appEnv" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm text-zinc-700">
                    <option value="local">local</option>
                    <option value="staging">staging</option>
                    <option value="production">production</option>
                    <option value="testing">testing</option>
                    <option value="developer">developer</option>
                </select>
                <flux:error name="appEnv" />
            </flux:field>

            <flux:text class="text-xs text-amber-600 dark:text-amber-400">
                {{ __("A wrong value can take the site down until it is fixed. The rest of the app's .env-backed settings live on Developer Tools.") }}
            </flux:text>

            <div class="flex gap-2 pt-1">
                <flux:button size="sm" variant="primary" wire:click="confirmSaveEnvironment" wire:loading.attr="disabled">
                    {{ __('Save Environment') }}
                </flux:button>
                <flux:modal.close>
                    <flux:button size="sm" variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

        {{-- Environment save confirmation --}}
    <flux:modal name="settings-env-confirm" class="md:w-96"
        x-on:open-modal.window="if ($event.detail.name === 'settings-env-confirm') $flux.modal('settings-env-confirm').show()"
        x-on:close-modal.window="if ($event.detail.name === 'settings-env-confirm') $flux.modal('settings-env-confirm').close()">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-amber-50 flex items-center justify-center shrink-0">
                    <flux:icon.exclamation-triangle class="w-5 h-5 text-amber-500" />
                </div>
                <flux:heading>{{ __('Save environment?') }}</flux:heading>
            </div>
            <flux:text class="text-sm text-zinc-500">
                {{ __('This overwrites the live .env file and clears the configuration cache. If this is wrong, the site may stop working until it is corrected.') }}
            </flux:text>
            <div class="flex gap-2 pt-1">
                <button wire:click="saveEnvironment" wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 px-4 h-8 text-sm font-medium rounded-lg text-white bg-amber-600 hover:bg-amber-700 transition-colors border-none cursor-pointer">
                    {{ __('Save anyway') }}
                </button>
                <flux:modal.close>
                    <flux:button size="sm" variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

    <livewire:admin.media-library.picker-modal key="settings-picker-modal" />
</div>
