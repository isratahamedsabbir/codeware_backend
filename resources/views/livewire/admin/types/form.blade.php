<div class="max-w-[1600px] w-full mx-auto flex-1">

    @include('partials._admin-loader-target', ['target' => 'edit'])

    @push('page-header-actions')
        <flux:button variant="ghost" size="sm" class="admin-back-btn" icon="arrow-left" href="{{ route('admin.types') }}" wire:navigate>
            Back
        </flux:button>
    @endpush

    <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,5fr)_minmax(0,7fr)] gap-4 items-start">

    <x-admin-section-card variant="postbox" title="Type Details" :collapsible="false" class="w-full">
        <x-slot:actions>
            @if ($typeId)
                <flux:button size="sm" variant="ghost" icon="plus" wire:click="resetForm">New type</flux:button>
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
                        <flux:input wire:model.live.debounce.400ms="name.{{ $language->code }}"
                            placeholder="{{ $language->code === $this->primaryLocale ? 'e.g. Product, Post' : 'e.g. Product, Post ('.($language->native_name ?: $language->name).')' }}" />
                        @if ($language->code === $this->primaryLocale)<flux:error name="name.{{ $language->code }}" />@endif
                    </flux:field>
                </x-admin-locale-panel>
            @endforeach
        </x-admin-locale-tabs>

        {{-- Slug is the stable key the rest of the system keys off (Type::idFor(),
             pages.type, the product/post pool subqueries) rather than anything
             cosmetic, so the hint spells that out instead of just saying
             "auto-generated". --}}
        <flux:field>
            <flux:label>Slug<x-field-hint text="Leave blank to generate it from the primary language's name. The slug is what the code keys off to tell the product pool from the post pool, so renaming it later re-points that split." /></flux:label>
            <flux:input wire:model.live.debounce.400ms="slug" placeholder="auto-generated-from-name" />
            <flux:error name="slug" />
        </flux:field>

        {{-- Footer --}}
        <div class="-mx-3 -mb-3 mt-4 flex items-center gap-3 flex-wrap rounded-b-[3px] border-t border-zinc-200 bg-zinc-50 px-3 py-3 dark:border-zinc-700 dark:bg-zinc-800/40">
            <x-admin-save-button :label="$typeId ? 'Update Type' : 'Create Type'" />
        </div>

    </x-admin-section-card>

    {{-- Existing types, beside the form: same datatable as the old index. --}}
    <div class="bg-white rounded-[5px] shadow-sm overflow-hidden">

        <div class="flex items-center gap-3 p-4 flex-wrap">
            @if (count($selectedIds) > 0)
                <flux:button variant="danger" size="sm" icon="trash" wire:click="confirmBulkDelete">
                    Delete ({{ count($selectedIds) }})
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
            {{-- Search --}}
            <div class="relative max-w-[200px] ml-auto">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-zinc-600" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8" />
                    <line x1="21" y1="21" x2="16.65" y2="16.65" />
                </svg>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search types…"
                    class="w-full pl-9 pr-3 py-2 text-sm border border-zinc-200 rounded-lg outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition-all" />
            </div>
        </div>

        <div class="overflow-x-auto">
            <div class="border border-zinc-100 rounded-lg">
                <table class="w-full min-w-[780px] divide-y divide-gray-200 whitespace-nowrap">
                    <thead>
                        <tr class="bg-zinc-50">
                            <th class="px-2 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider w-8 admin-id-column">#</th>
                            <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Name</th>
                            <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Slug</th>
                            <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Used by</th>
                            <th class="px-4 py-2.5 text-left text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Status</th>
                            <th class="sticky right-0 z-10 bg-zinc-50 border-l border-zinc-100 px-4 py-2.5 text-center text-[10.5px] font-semibold text-zinc-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($types as $type)
                            <tr wire:key="type-{{ $type->id }}"
                                class="group/row hover:bg-indigo-50/30 transition-colors cursor-default {{ in_array($type->id, $selectedIds, true) ? 'bg-indigo-50 ring-1 ring-inset ring-indigo-300' : '' }}"
                                @contextmenu.prevent="$el.querySelector('[data-actions-trigger]')?.click()"
                                @click="if ($event.ctrlKey || $event.metaKey) { $event.preventDefault(); $wire.toggleSelect({{ $type->id }}) }">

                                <td class="px-2 py-2 text-center text-xs text-zinc-500">
                                    <x-copy-text :text="$type->id" class="text-xs text-zinc-500">{{ $type->id }}</x-copy-text>
                                </td>

                                <td class="px-4 py-2">
                                    <div class="font-medium text-zinc-900 text-sm leading-snug">
                                        <x-truncate :text="$type->getTranslation('name', 'en', false)" />
                                    </div>
                                    @if ($type->getTranslation('name', 'bn', false))
                                        <div class="text-xs text-zinc-600 mt-0.5">
                                            <x-truncate :text="$type->getTranslation('name', 'bn', false)" />
                                        </div>
                                    @endif
                                </td>

                                <td class="px-4 py-2">
                                    <x-copy-text :text="$type->slug" class="font-mono text-[11px] text-zinc-500 block">
                                        <x-truncate :text="$type->slug" />
                                    </x-copy-text>
                                </td>

                                {{-- Assignment counts. A type still carrying rows can only be
                                     deactivated, not deleted (the FK restricts), so this is
                                     what an admin needs in order to tell the two apart. --}}
                                <td class="px-4 py-2 text-xs text-zinc-600 space-y-0.5">
                                    @if ($type->categories_count || $type->brands_count || $type->tags_count || $type->groups_count)
                                        <div>{{ $type->categories_count }} {{ \Illuminate\Support\Str::plural('category', $type->categories_count) }}</div>
                                        <div>{{ $type->brands_count }} {{ \Illuminate\Support\Str::plural('brand', $type->brands_count) }}</div>
                                        <div>{{ $type->tags_count }} {{ \Illuminate\Support\Str::plural('tag', $type->tags_count) }}</div>
                                        <div>{{ $type->groups_count }} {{ \Illuminate\Support\Str::plural('group', $type->groups_count) }}</div>
                                    @else
                                        <span class="text-zinc-400">Unused</span>
                                    @endif
                                </td>

                                <td class="px-4 py-2">
                                    @if ($type->status === 'active')
                                        <button type="button" wire:click="toggleStatus({{ $type->id }})"
                                            aria-label="Deactivate"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-600 border border-green-200 cursor-pointer hover:bg-green-100">
                                            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                            Active
                                        </button>
                                    @else
                                        <button type="button" wire:click="toggleStatus({{ $type->id }})"
                                            aria-label="Activate"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-600 border border-red-200 cursor-pointer hover:bg-red-100">
                                            <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                            Inactive
                                        </button>
                                    @endif
                                </td>

                                @php
                                    $bulkActive = count($selectedIds) > 0;
                                    $inUse = $type->categories_count || $type->brands_count || $type->tags_count || $type->groups_count;
                                @endphp
                                <td class="sticky right-0 z-10 bg-white group-hover/row:bg-indigo-100 border-l border-zinc-100 px-4 py-2">
                                    <x-admin-row-actions :actions="[
                                        ['wireClick' => 'edit(' . $type->id . ')', 'icon' => 'pencil', 'label' => 'Edit', 'color' => 'primary', 'disabled' => $bulkActive],
                                        ['wireClick' => 'confirmDelete(' . $type->id . ')', 'icon' => 'trash', 'label' => $inUse ? 'Cannot delete while in use' : 'Delete', 'color' => 'rose-500', 'disabled' => $bulkActive || (bool) $inUse],
                                    ]" />
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-16 text-center">
                                    <flux:icon.tag class="w-10 h-10 text-zinc-200 mx-auto mb-3" />
                                    <p class="text-sm text-zinc-600">No types found.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="px-6 py-3">
            {{ $types->links() }}
        </div>
    </div>

    </div>

    {{-- Delete Modal --}}
    <flux:modal name="type-delete" class="md:w-80"
        x-on:open-modal.window="if ($event.detail.name === 'type-delete') $flux.modal('type-delete').show()"
        x-on:close-modal.window="if ($event.detail.name === 'type-delete') $flux.modal('type-delete').close()">
        <div class="space-y-4">
            <flux:heading>Delete type?</flux:heading>
            <flux:text class="text-sm text-zinc-500">
                This action cannot be undone. A type that is still assigned to categories, brands, tags or groups can't be deleted — deactivate it instead.
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
    <flux:modal name="type-bulk-delete" class="md:w-80"
        x-on:open-modal.window="if ($event.detail.name === 'type-bulk-delete') $flux.modal('type-bulk-delete').show()"
        x-on:close-modal.window="if ($event.detail.name === 'type-bulk-delete') $flux.modal('type-bulk-delete').close()">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-red-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2">
                        <polyline points="3 6 5 6 21 6" />
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                    </svg>
                </div>
                <flux:heading>Delete {{ count($selectedIds) }} {{ \Illuminate\Support\Str::plural('type', count($selectedIds)) }}?</flux:heading>
            </div>
            <flux:text class="text-sm text-zinc-500">
                Only unused types are deleted. Any type still assigned to categories, brands or tags is skipped and left in place.
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
