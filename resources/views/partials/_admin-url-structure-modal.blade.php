{{-- The "URL Structure" modal of a list screen. Needs $type (a key of
     UrlStructure::TYPES) and a component using ManagesUrlPrefix. Gated as a
     whole so the markup never reaches a staff response. --}}
@can('access-admin-system')
    @php
        $urlLabel = \App\Support\UrlStructure::TYPES[$type][0];
        $urlPrefixNow = \App\Support\UrlStructure::normalize((string) $urlPrefix);
        $urlExample = rtrim((string) config('app.url'), '/').'/'.($urlPrefixNow === '' ? '' : $urlPrefixNow.'/').'my-'.$type;
    @endphp
    <flux:modal name="url-structure-settings" class="md:w-[28rem]"
        x-on:open-modal.window="if ($event.detail.name === 'url-structure-settings') $flux.modal('url-structure-settings').show()"
        x-on:close-modal.window="if ($event.detail.name === 'url-structure-settings') $flux.modal('url-structure-settings').close()">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
                    <flux:icon.link class="w-5 h-5 text-primary" />
                </div>
                <flux:heading>{{ $urlLabel }} URL structure</flux:heading>
            </div>
            <flux:text class="text-sm text-zinc-500">
                The address of each {{ strtolower($urlLabel) }} page on the site. Leave the prefix empty to use a short <span class="font-mono text-xs">/slug</span> address.
            </flux:text>

            <flux:field>
                <flux:label>{{ $urlLabel }} URL</flux:label>
                <div class="flex min-w-0 items-center gap-2">
                    <span class="shrink-0 rounded-md bg-zinc-50 px-2 py-2 font-mono text-xs text-zinc-500 ring-1 ring-zinc-200 dark:bg-zinc-900 dark:text-zinc-400 dark:ring-zinc-700">/</span>
                    <flux:input wire:model.live.debounce.300ms="urlPrefix" placeholder="No prefix" aria-label="{{ $urlLabel }} URL prefix" class="min-w-0 font-mono" maxlength="50" />
                    <span class="shrink-0 whitespace-nowrap font-mono text-xs text-zinc-500 dark:text-zinc-400">/slug</span>
                </div>
                <flux:error name="urlPrefix" />
            </flux:field>

            <div class="flex min-w-0 items-start gap-2 border-t border-zinc-200/80 pt-3 text-xs dark:border-zinc-700">
                <flux:icon.arrow-turn-down-right variant="mini" class="mt-0.5 size-3.5 shrink-0 text-zinc-400" />
                <span class="shrink-0 font-medium text-zinc-500 dark:text-zinc-400">Preview</span>
                <span class="min-w-0 break-all font-mono text-zinc-700 dark:text-zinc-300">{{ $urlExample }}</span>
            </div>

            <p class="text-xs text-zinc-500">
                Older <span class="font-mono">/products/…</span> and <span class="font-mono">/blog/…</span> links keep working.
                @if ($type === 'service') Service URLs take effect when the active theme supports service pages. @endif
            </p>

            <div class="flex gap-2 pt-1">
                <flux:button size="sm" variant="primary" wire:click="saveUrlPrefix" wire:loading.attr="disabled">Save</flux:button>
                <flux:modal.close>
                    <flux:button size="sm" variant="ghost">Cancel</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
@endcan
