    {{-- Currency live preview — opened from the info button on the Currency
         card in the General tab. Shows sample pricing with the current
         currency + VAT settings. --}}
    <flux:modal name="settings-currency-preview" class="md:w-[420px]"
        x-on:open-modal.window="if ($event.detail.name === 'settings-currency-preview') $flux.modal('settings-currency-preview').show()"
        x-on:close-modal.window="if ($event.detail.name === 'settings-currency-preview') $flux.modal('settings-currency-preview').close()">
        <div class="rounded-xl bg-zinc-50 px-5 py-7 text-center dark:bg-zinc-800/50"
            x-data="{
                fmt(value) {
                    const dec = parseInt($wire.settings.decimal_places || '0', 10);
                    const num = Number(value || 0).toLocaleString('en-US', { minimumFractionDigits: dec, maximumFractionDigits: dec });
                    const sym = $wire.settings.currency_symbol || '৳';
                    return ($wire.settings.currency_position || 'left') === 'right' ? num + ' ' + sym : sym + num;
                }
            }">
            <p class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400">Live Preview</p>
            <p class="mt-2 text-3xl font-bold tabular-nums text-zinc-900 dark:text-white" x-text="fmt(1250)"></p>
            <span class="mt-1 inline-block text-[10px] font-medium uppercase tracking-wider text-zinc-400"
                x-text="$wire.settings.currency_code || 'BDT'"></span>

            <div class="mx-auto my-6 h-px w-full bg-zinc-200 dark:bg-zinc-700"></div>

            <div class="space-y-1.5 text-sm">
                <div class="flex items-center justify-between">
                    <span class="text-zinc-500 dark:text-zinc-400">Subtotal</span>
                    <span class="tabular-nums text-zinc-800 dark:text-zinc-200" x-text="fmt(899.5)"></span>
                </div>
                <div class="flex items-center justify-between" x-show="$wire.settings.vat_enabled === '1' || $wire.settings.vat_enabled === true">
                    <span class="text-zinc-500 dark:text-zinc-400" x-text="$wire.settings.vat_label || 'VAT'"></span>
                    <span class="tabular-nums text-zinc-800 dark:text-zinc-200" x-text="fmt(Math.round((899.5 * (Number($wire.settings.vat_rate) || 0)) / 100 * 100) / 100)"></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-zinc-500 dark:text-zinc-400">Shipping</span>
                    <span class="text-emerald-600">Free</span>
                </div>
                <div class="flex items-center justify-between border-t border-zinc-200 pt-2 font-semibold dark:border-zinc-700">
                    <span class="text-zinc-800 dark:text-zinc-100">Total</span>
                    <span class="tabular-nums text-zinc-900 dark:text-white" x-text="fmt($wire.settings.vat_enabled === '1' || $wire.settings.vat_enabled === true ? 899.5 + Math.round((899.5 * (Number($wire.settings.vat_rate) || 0)) / 100 * 100) / 100 : 899.5)"></span>
                </div>
            </div>
        </div>
    </flux:modal>
