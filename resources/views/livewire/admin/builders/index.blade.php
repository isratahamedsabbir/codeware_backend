<div class="max-w-[1600px] w-full mx-auto flex-1">

<div class="grid grid-cols-1 xl:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] gap-4 items-start">

    {{-- Create form, beside the list (same layout as Accessories → Types). --}}
    <x-admin-section-card variant="postbox" :title="__('Builder Details')" :collapsible="false" class="w-full">
        <flux:field>
            <flux:label>{{ __('Name') }}<span class="text-red-500 ml-0.5">*</span></flux:label>
            <flux:input wire:model="name" wire:keydown.enter="create" placeholder="{{ __('e.g. Landing page') }}" />
            <flux:error name="name" />
        </flux:field>

        <flux:field>
            <flux:label>{{ __('Language') }}<x-field-hint :text="__('The language you start designing in. You can add the other languages from the editor later.')" /></flux:label>
            <select wire:model="locale"
                class="w-full px-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 bg-white">
                @foreach ($this->localeOptions() as $code => $label)
                    <option value="{{ $code }}">{{ $label }}</option>
                @endforeach
            </select>
            <flux:error name="locale" />
        </flux:field>

        <div class="-mx-3 -mb-3 mt-4 flex items-center gap-3 flex-wrap rounded-b-[3px] border-t border-zinc-200 bg-zinc-50 px-3 py-3 dark:border-zinc-700 dark:bg-zinc-800/40">
            <x-admin-save-button :label="__('Create Builder')" method="create" />
        </div>
    </x-admin-section-card>

    {{-- Existing builders --}}
    <div class="bg-white rounded-[5px] shadow-sm overflow-hidden">

    {{-- Toolbar --}}
    <div class="flex items-center gap-3 p-4 flex-wrap">
        <x-per-page-select :options="$this->perPageOptions()" />
        <div class="relative max-w-xs ml-auto">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-600" viewBox="0 0 24 24"
                fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8" />
                <line x1="21" y1="21" x2="16.65" y2="16.65" />
            </svg>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="{{ __('Search builders…') }}"
                class="w-full pl-9 pr-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all" />
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto">
        <div class="border border-zinc-100 rounded-lg">
            <table class="w-full divide-y divide-gray-200">
                <colgroup>
                    <col class="lg:hidden" style="width:5%">
                    <col class="hidden lg:table-column" style="width:5%">
                    <col style="width:55%">
                    <col class="hidden lg:table-column" style="width:25%">
                    <col style="width:15%">
                </colgroup>
                <thead>
                    <tr class="bg-zinc-50">
                        <th class="lg:hidden px-2 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider w-8"></th>
                        <th class="hidden lg:table-cell px-2 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider w-8 admin-id-column">#</th>
                        <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">{{ __('Name') }}</th>
                        <th class="hidden lg:table-cell px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">{{ __('Languages') }}</th>
                        <th class="sticky right-0 z-10 bg-zinc-50 border-l border-zinc-100 px-4 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($designs as $design)
                        @php $langs = collect($design->locales())->map(fn ($l) => strtoupper($l))->implode(', ') ?: '—'; @endphp
                        <tr wire:key="builder-{{ $design->id }}" class="group/row hover:bg-indigo-50/30 transition-colors" @contextmenu.prevent="$el.querySelector('[data-actions-trigger]')?.click()">

                            {{-- Expand toggle (small screens only, where columns are hidden) --}}
                            <td class="lg:hidden px-2 py-2 text-center">
                                <x-admin-row-expand-toggle class="lg:hidden" :expanded="$viewingId === $design->id"
                                    wire:click="{{ $viewingId === $design->id ? 'closeDetails' : 'viewDetails('.$design->id.')' }}" />
                            </td>

                            {{-- Id --}}
                            <td class="hidden lg:table-cell px-2 py-2 text-center text-xs text-zinc-500">
                                <x-copy-text :text="$design->id" class="text-xs text-zinc-500">{{ $design->id }}</x-copy-text>
                            </td>

                            {{-- Name --}}
                            <td class="px-4 py-2">
                                <div class="font-medium text-zinc-900 text-sm leading-snug truncate">
                                    <x-truncate :text="$design->name" />
                                </div>
                            </td>

                            {{-- Languages --}}
                            <td class="hidden lg:table-cell px-4 py-2 text-xs text-zinc-600">{{ $langs }}</td>

                            {{-- Actions --}}
                            <td class="sticky right-0 z-10 bg-white group-hover/row:bg-indigo-100 border-l border-zinc-100 px-4 py-2">
                                <x-admin-row-actions :actions="[
                                    ['href' => rtrim((string) config('app.url'), '/').'/'.$design->slug, 'icon' => 'external-link', 'label' => __('View'), 'color' => 'cyan-500', 'external' => true],
                                    ['href' => route('admin.builders.edit', $design->id), 'icon' => 'pencil', 'label' => __('Open builder'), 'color' => 'primary', 'external' => true],
                                    ['wireClick' => 'duplicate(' . $design->id . ')', 'icon' => 'copy', 'label' => __('Duplicate'), 'color' => 'amber-500'],
                                    ['wireClick' => 'confirmDelete(' . $design->id . ')', 'icon' => 'trash', 'label' => __('Delete'), 'color' => 'rose-500'],
                                ]" />
                            </td>

                        </tr>
                        @if ($viewingId === $design->id)
                            <x-admin-row-details colspan="5">
                                <x-admin-row-details.item label="Languages">{{ $langs }}</x-admin-row-details.item>
                            </x-admin-row-details>
                        @endif
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center">
                                <flux:icon.squares-2x2 class="w-10 h-10 text-zinc-200 mx-auto mb-3" />
                                <p class="text-sm text-zinc-600">{{ __('No builders found.') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination --}}
    <div class="px-6 py-3">
        {{ $designs->links() }}
    </div>
    </div>

</div>

    {{-- Delete Modal --}}
    <flux:modal name="builder-delete" class="md:w-96"
        x-on:open-modal.window="if ($event.detail.name === 'builder-delete') $flux.modal('builder-delete').show()"
        x-on:close-modal.window="if ($event.detail.name === 'builder-delete') $flux.modal('builder-delete').close()">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-red-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2">
                        <polyline points="3 6 5 6 21 6" />
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                    </svg>
                </div>
                <flux:heading>{{ __('Delete builder?') }}</flux:heading>
            </div>
            <flux:text class="text-sm text-zinc-500">
                {{ __('This action cannot be undone. The design in every language and its address are removed.') }}
            </flux:text>
            <div class="flex gap-2 pt-1">
                <button wire:click="delete"
                    class="inline-flex items-center gap-2 px-4 h-8 text-sm font-medium rounded-lg text-white bg-red-600 hover:bg-red-700 transition-colors border-none cursor-pointer">
                    {{ __('Delete') }}
                </button>
                <flux:modal.close>
                    <flux:button size="sm" variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>


</div>
