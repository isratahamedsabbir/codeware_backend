{{-- Site Settings tab of Developer Tools (moved from Settings → General). --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 items-start">
<div class="space-y-5">
                        <x-admin-section-card header-border="border-zinc-100" icon="banknotes" title="Currency"
                            description="Set the currency used across the site for product pricing and payments.">
                            <x-slot:titleActions>
                                <button type="button" @click="$dispatch('open-modal', { name: 'settings-currency-preview' })" title="Live preview"
                                    class="flex size-5 items-center justify-center text-zinc-400 transition-colors hover:text-primary cursor-pointer">
                                    <flux:icon.information-circle class="size-4" />
                                </button>
                            </x-slot:titleActions>

                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-400">Currency</p>
                            <flux:field>
                                <flux:label>Currency Code</flux:label>
                                <flux:input wire:model="settings.currency_code" placeholder="BDT, USD, EUR" class="uppercase" maxlength="3" />
                                <flux:error name="settings.currency_code" />
                            </flux:field>
                            <flux:field>
                                <flux:label>Currency Symbol</flux:label>
                                <flux:input wire:model="settings.currency_symbol" placeholder="৳, $, €" maxlength="4" />
                                <flux:error name="settings.currency_symbol" />
                            </flux:field>

                            <p class="mt-5 mb-2 text-xs font-semibold uppercase tracking-wide text-zinc-400">Formatting</p>
                            <flux:field>
                                <flux:label>Symbol Position</flux:label>
                                <div class="relative">
                                    <select wire:model="settings.currency_position"
                                        class="w-full appearance-none rounded-lg border border-zinc-300 bg-white pl-3 pr-9 py-2 text-sm text-zinc-700 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200">
                                        <option value="left">Before amount — ৳1,250.00</option>
                                        <option value="right">After amount — 1,250.00 ৳</option>
                                    </select>
                                    <flux:icon.chevron-down class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-zinc-400" />
                                </div>
                            </flux:field>
                            <flux:field>
                                <flux:label>Decimal Places</flux:label>
                                <flux:input type="number" wire:model="settings.decimal_places" min="0" max="4" />
                                <flux:error name="settings.decimal_places" />
                            </flux:field>
                        </x-admin-section-card>
@foreach (['localization'] as $siteGroup)
    @continue (! isset($groupedSettings[$siteGroup]))
    @include('partials._admin-settings-group-card', ['group' => $siteGroup, 'items' => $groupedSettings[$siteGroup]])
@endforeach
                        {{-- Backend (admin panel colors) — shared the old Theme tab with the
                             frontend Site Design picker, which moved to the dedicated Theme
                             Settings screen. The colors themselves belong here in General. --}}
                        <x-admin-section-card header-border="border-zinc-100" icon="swatch" title="Backend"
                            description="Colors and typeface used across the admin panel, including buttons.">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                @foreach ($colorSettings as $setting)
                                    <flux:field>
                                        <flux:label>{{ ucwords(str_replace('_', ' ', $setting->key)) }}</flux:label>
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-lg border border-zinc-300 shrink-0"
                                                 style="background-color: {{ $settings[$setting->key] ?? '#ffffff' }}"
                                                 x-data
                                                 :style="'background-color: ' + ($wire.settings['{{ $setting->key }}'] || '#ffffff')"></div>
                                            <flux:input wire:model="settings.{{ $setting->key }}" placeholder="#000000" class="flex-1 font-mono" />
                                        </div>
                                    </flux:field>
                                @endforeach
                                {{-- The panel's typeface. Admin-only, and nothing to do with the
                                     storefront themes. System font and Segoe UI are faces the
                                     machine already has, so they cost nothing; Roboto is
                                     self-hosted and the only one that downloads a file. System
                                     font is the default. See App\Support\AdminFont. --}}
                                <flux:field class="sm:col-span-2">
                                    <flux:label>Backend Font</flux:label>
                                    <flux:select wire:model="settings.admin_font" class="w-full">
                                        @foreach ($adminFontOptions as $value => $label)
                                            <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    <flux:description>
                                        Typeface for the admin panel only. System font follows the
                                        device. To add a font, put its files in a new folder inside
                                        public/fonts/ (e.g. public/fonts/inter/) and it will appear here.
                                    </flux:description>
                                </flux:field>
                            </div>
                        </x-admin-section-card>
</div>
<div class="space-y-5">
                        {{-- VAT / Tax --}}
                        <x-admin-section-card header-border="border-zinc-100" icon="receipt-percent" title="VAT / Tax"
                            description="Optional tax added on top of every order's discounted subtotal.">
                            <flux:field>
                                <flux:label>Apply VAT</flux:label>
                                <div class="relative">
                                    <select wire:model="settings.vat_enabled"
                                        class="w-full appearance-none rounded-lg border border-zinc-300 bg-white pl-3 pr-9 py-2 text-sm text-zinc-700 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200">
                                        <option value="1">Yes — add VAT to orders</option>
                                        <option value="0">No — exclude VAT</option>
                                    </select>
                                    <flux:icon.chevron-down class="pointer-events-none absolute right-3 top-1/2 size-4 -translate-y-1/2 text-zinc-400" />
                                </div>
                                <flux:error name="settings.vat_enabled" />
                            </flux:field>
                            <flux:field>
                                <flux:label>VAT Rate (%)</flux:label>
                                <flux:input type="number" wire:model="settings.vat_rate" min="0" max="100" step="0.01" />
                                <flux:error name="settings.vat_rate" />
                            </flux:field>
                            <flux:field>
                                <flux:label>VAT Label</flux:label>
                                <flux:input wire:model="settings.vat_label" placeholder="VAT, Tax, GST..." maxlength="50" />
                                <flux:error name="settings.vat_label" />
                            </flux:field>
                            <p class="text-xs text-zinc-400 dark:text-zinc-500">
                                When enabled, the rate is applied to each order's discounted subtotal and added on top of the total.
                            </p>
                        </x-admin-section-card>
@foreach (['pagination', 'newsletter'] as $siteGroup)
    @continue (! isset($groupedSettings[$siteGroup]))
    @include('partials._admin-settings-group-card', ['group' => $siteGroup, 'items' => $groupedSettings[$siteGroup]])
@endforeach
                        {{-- Telegram bot — sits under Backend in the right-hand stack like
                             every other settings card, rather than as a full-width strip. --}}
                        <x-admin-section-card header-border="border-zinc-100" icon="paper-airplane" title="Telegram"
                            description="Bot that sends new contact messages to a Telegram chat.">
                            <x-slot:actions>
                                <flux:button size="sm" variant="outline" icon="paper-airplane"
                                    wire:click="sendTelegramTest" wire:loading.attr="disabled" wire:target="sendTelegramTest">
                                    Test
                                </flux:button>
                            </x-slot:actions>

                            <div class="space-y-4">
                                <flux:field>
                                    <flux:label>Bot token</flux:label>
                                    <flux:input type="password" viewable wire:model="telegramBotToken"
                                        placeholder="123456789:AA…" autocomplete="off" class="font-mono" />
                                    <flux:description>
                                        From @@BotFather. Treat it like a password. Saved to .env when you click Save Settings; Test uses what is typed here.
                                    </flux:description>
                                </flux:field>

                                <flux:field>
                                    <flux:label>Chat ID</flux:label>
                                    <flux:input wire:model="telegramChatId" placeholder="-1001234567890" class="font-mono" />
                                    <flux:description>
                                        User, group or channel id; the bot must be in that chat. Turn it on under Contacts → Notifications.
                                    </flux:description>
                                </flux:field>
                            </div>
                        </x-admin-section-card>
</div>
</div>
