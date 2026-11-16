<div class="max-w-[1600px] w-full mx-auto flex-1">

    @include('partials._admin-loader-target', ['target' => 'edit'])

    @push('page-header-actions')
        <flux:button variant="ghost" size="sm" class="admin-back-btn" icon="arrow-left" href="{{ route('admin.tags') }}" wire:navigate>
            Back
        </flux:button>
    @endpush

    <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] gap-4 items-start">

    <x-admin-section-card variant="postbox" title="Tag Details" :collapsible="false" class="w-full" body-class="p-3">
        <x-slot:actions>
            @if ($tagId)
                <flux:button size="sm" variant="ghost" icon="plus" wire:click="resetForm">New tag</flux:button>
            @endif
        </x-slot:actions>


            <x-admin-locale-tabs>
                @foreach (\App\Support\Locale::translatable() as $language)
                    <x-admin-locale-panel :code="$language->code">
                        <flux:field>
                            <flux:label :badge="$language->code">
                                Name
                                @if ($language->code === $this->primaryLocale)<span class="text-red-500 ml-0.5">*</span>@endif
                            </flux:label>
                            <flux:input wire:model="name.{{ $language->code }}"
                                placeholder="{{ $language->code === $this->primaryLocale ? 'Tag name' : 'Tag name ('.($language->native_name ?: $language->name).')' }}" />
                            @if ($language->code === $this->primaryLocale)<flux:error name="name.{{ $language->code }}" />@endif
                        </flux:field>
                    </x-admin-locale-panel>
                @endforeach

                <flux:field>
                    <flux:label>Type<span class="text-red-500 ml-0.5">*</span><x-field-hint text="Post tags show on the Post form, product tags on the Product form. Manage the list of types under Accessories → Types" /></flux:label>
                    <flux:select wire:model="typeId" placeholder="Select a type">
                        @foreach ($this->typeOptions as $type)
                            <flux:select.option value="{{ $type->id }}">{{ $type->getTranslation('name', $this->primaryLocale, false) ?: $type->getTranslation('name', 'en', false) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="typeId" />
                </flux:field>
            </x-admin-locale-tabs>

        {{-- Footer --}}
        <div class="-mx-3 -mb-3 mt-4 flex items-center gap-3 flex-wrap rounded-b-[3px] border-t border-zinc-200 bg-zinc-50 px-3 py-3 dark:border-zinc-700 dark:bg-zinc-800/40">
            <button wire:click="save" wire:loading.attr="disabled" wire:target="save"
                class="admin-btn-save inline-flex items-center gap-2 px-5 h-8 text-sm font-medium rounded-lg text-white disabled:opacity-60 transition-colors">
                <svg wire:loading.remove wire:target="save" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
                    <polyline points="17 21 17 13 7 13 7 21" />
                    <polyline points="7 3 7 8 15 8" />
                </svg>
                <svg wire:loading wire:target="save" class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="9" stroke-opacity="0.25" />
                    <path d="M21 12a9 9 0 0 0-9-9" stroke-opacity="1" />
                </svg>
                <span wire:loading.remove wire:target="save">{{ $tagId ? 'Update Tag' : 'Create Tag' }}</span>
                <span wire:loading wire:target="save">Saving...</span>
            </button>
        </div>

    </x-admin-section-card>

    {{-- Every tag, beside the form. Edit loads a row into the form; no separate list page. --}}
    <div class="bg-white rounded-[5px] shadow-sm overflow-hidden">
    
        {{-- Header --}}
        <div class="flex items-center gap-3 p-4 flex-wrap">
                    @if (count($selectedIds) > 0)
                        <flux:button variant="danger" size="sm" icon="trash" wire:click="confirmBulkDelete">
                            Delete ({{ count($selectedIds) }})
                        </flux:button>
                        <flux:button variant="outline" size="sm" icon="arrow-down-tray"
                            href="{{ route('admin.tags.export', ['ids' => $selectedIds]) }}">
                            Export ({{ count($selectedIds) }})
                        </flux:button>
                    @endif
            <x-per-page-select :options="$this->perPageOptions()" />
            {{-- Status filter --}}
            <select wire:model.live="statusFilter"
                class="px-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 bg-white appearance-none pr-8 min-w-[140px] transition-all"
                style="background-image:url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 24 24' fill='none' stroke='%23aaa' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E&quot;);background-repeat:no-repeat;background-position:right 10px center">
                <option value="">All statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
            {{-- Type filter --}}
            <select wire:model.live="typeFilter"
                class="px-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 bg-white appearance-none pr-8 min-w-[140px] transition-all"
                style="background-image:url(&quot;data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='11' height='11' viewBox='0 0 24 24' fill='none' stroke='%23aaa' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E&quot;);background-repeat:no-repeat;background-position:right 10px center">
                <option value="">All types</option>
                @foreach ($types as $type)
                    <option value="{{ $type->id }}">{{ $type->getTranslation('name', 'en', false) ?: $type->getTranslation('name', 'bn', false) }}</option>
                @endforeach
            </select>
            {{-- Search --}}
            <div class="relative max-w-[200px] ml-auto">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-600" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8" />
                    <line x1="21" y1="21" x2="16.65" y2="16.65" />
                </svg>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search tags…"
                    class="w-full pl-9 pr-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all" />
            </div>
        </div>
    
        {{-- Table --}}
        <div class="overflow-x-auto">
            <div class="border border-zinc-100 rounded-lg">
                <table class="w-full min-w-[780px] divide-y divide-gray-200 whitespace-nowrap">
                    <thead>
                        <tr class="bg-zinc-50">
                            <th class="px-2 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider w-8 admin-id-column">#</th>
                            <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Name</th>
                            <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Type</th>
                            <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Status</th>
                            <th class="sticky right-0 z-10 bg-zinc-50 border-l border-zinc-100 px-4 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($tags as $tag)
                            <tr wire:key="tag-{{ $tag->id }}"
                                class="group/row hover:bg-indigo-50/30 transition-colors cursor-default {{ $tag->id === $tagId ? 'bg-indigo-50' : '' }} {{ in_array($tag->id, $selectedIds, true) ? 'bg-indigo-50 ring-1 ring-inset ring-indigo-300' : '' }}"
                                @contextmenu.prevent="$el.querySelector('[data-actions-trigger]')?.click()"
                                @click="if ($event.ctrlKey || $event.metaKey) { $event.preventDefault(); $wire.toggleSelect({{ $tag->id }}) }">
    
                                {{-- ID --}}
                                <td class="px-2 py-2 text-center text-xs text-zinc-500">
                                    <x-copy-text :text="$tag->id" class="text-xs text-zinc-500">{{ $tag->id }}</x-copy-text>
                                </td>
    
                                {{-- Name --}}
                                <td class="px-4 py-2">
                                    <div class="font-medium text-zinc-900 text-sm leading-snug">
                                        <x-truncate :text="$tag->getTranslation('name', 'en', false)" />
                                    </div>
                                    @if ($tag->getTranslation('name', 'bn', false))
                                        <div class="text-xs text-zinc-600 mt-0.5">
                                            <x-truncate :text="$tag->getTranslation('name', 'bn', false)" />
                                        </div>
                                    @endif
                                </td>
    
                                {{-- Type --}}
                                <td class="px-4 py-2">
                                    @if ($tag->type)
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-indigo-50 text-indigo-600 border border-indigo-200">
                                            <x-truncate :text="$tag->type->getTranslation('name', 'en', false) ?: $tag->type->getTranslation('name', 'bn', false)" />
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-zinc-100 text-zinc-500 border border-zinc-200">Unassigned</span>
                                    @endif
                                </td>
    
                                {{-- Status --}}
                                <td class="px-4 py-2">
                                    @if ($tag->status === 'active')
                                        <button type="button" wire:click="toggleStatus({{ $tag->id }})"
                                            aria-label="Deactivate"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-600 border border-green-200 cursor-pointer hover:bg-green-100">
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                            Active
                                        </button>
                                    @else
                                        <button type="button" wire:click="toggleStatus({{ $tag->id }})"
                                            aria-label="Activate"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-600 border border-red-200 cursor-pointer hover:bg-red-100">
                                            <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                            Inactive
                                        </button>
                                    @endif
                                </td>
    
                                {{-- Actions — disabled while a bulk selection is active, so the
                                     per-row actions can't conflict with the bulk toolbar above. --}}
                                <td class="sticky right-0 z-10 bg-white group-hover/row:bg-indigo-100 border-l border-zinc-100 px-4 py-2">
                                    @php $bulkActive = count($selectedIds) > 0; @endphp
                                    <x-admin-row-actions :actions="[
                                        ['wireClick' => 'edit(' . $tag->id . ')', 'icon' => 'pencil', 'label' => 'Edit', 'color' => 'primary', 'disabled' => $bulkActive],
                                        ['wireClick' => 'confirmDelete(' . $tag->id . ')', 'icon' => 'trash', 'label' => 'Delete', 'color' => 'rose-500', 'disabled' => $bulkActive],
                                    ]" />
                                </td>
    
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-16 text-center">
                                    <svg class="w-10 h-10 text-zinc-200 mx-auto mb-3" viewBox="0 0 24 24" fill="none"
                                        stroke="currentColor" stroke-width="1.5">
                                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z" />
                                    </svg>
                                    <p class="text-sm text-zinc-600">No tags found.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    
        {{-- Pagination --}}
        <div class="px-6 py-3">
            {{ $tags->links() }}
        </div>
    
        {{-- Delete Modal --}}
        <flux:modal name="tag-delete" class="md:w-80"
            x-on:open-modal.window="if ($event.detail.name === 'tag-delete') $flux.modal('tag-delete').show()"
            x-on:close-modal.window="if ($event.detail.name === 'tag-delete') $flux.modal('tag-delete').close()">
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-red-50 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <polyline points="3 6 5 6 21 6" />
                            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                        </svg>
                    </div>
                    <flux:heading>Delete tag?</flux:heading>
                </div>
                <flux:text class="text-sm text-zinc-500">This action cannot be undone. Posts will no longer reference this
                    tag.
                </flux:text>
                <div class="flex gap-2 pt-1">
                    <button wire:click="delete"
                        class="inline-flex items-center gap-2 px-4 h-8 text-sm font-medium rounded-lg text-white bg-red-600 hover:bg-red-700 transition-colors border-none cursor-pointer">
                        Delete
                    </button>
                    <flux:modal.close>
                        <flux:button size="sm" variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                </div>
            </div>
        </flux:modal>
    
        {{-- Bulk Delete Modal --}}
        <flux:modal name="tag-bulk-delete" class="md:w-80"
            x-on:open-modal.window="if ($event.detail.name === 'tag-bulk-delete') $flux.modal('tag-bulk-delete').show()"
            x-on:close-modal.window="if ($event.detail.name === 'tag-bulk-delete') $flux.modal('tag-bulk-delete').close()">
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-red-50 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <polyline points="3 6 5 6 21 6" />
                            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                        </svg>
                    </div>
                    <flux:heading>Delete {{ count($selectedIds) }} {{ \Illuminate\Support\Str::plural('tag', count($selectedIds)) }}?</flux:heading>
                </div>
                <flux:text class="text-sm text-zinc-500">This action cannot be undone. Posts will no longer reference these
                    tags.
                </flux:text>
                <div class="flex gap-2 pt-1">
                    <button wire:click="bulkDelete"
                        class="inline-flex items-center gap-2 px-4 h-8 text-sm font-medium rounded-lg text-white bg-red-600 hover:bg-red-700 transition-colors border-none cursor-pointer">
                        Delete
                    </button>
                    <flux:modal.close>
                        <flux:button size="sm" variant="ghost">Cancel</flux:button>
                    </flux:modal.close>
                </div>
            </div>
        </flux:modal>
    
    </div>
    

    </div>
</div>
