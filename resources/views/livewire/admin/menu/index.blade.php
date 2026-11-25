<div>

    <div class="mb-3 flex items-center justify-between gap-4 flex-wrap">
        <div>
            @include('partials._admin-breadcrumbs', ['routeName' => 'admin.menu'])
        </div>
        <div class="flex items-center gap-3 shrink-0 flex-wrap">
            @php
                $activeMenuModel = $menus->firstWhere('slug', $activeGroup);
                $canDeleteActive = ! \App\Livewire\Admin\Menu\Index::isProtectedMenu($activeGroup);
            @endphp
            <flux:dropdown position="bottom" align="end">
                <flux:button size="sm" variant="outline" icon:trailing="chevron-down">
                    {{ $activeMenuModel?->name ?? __('Select menu') }}
                </flux:button>

                <flux:menu>
                    <flux:menu.heading>{{ __('Menus') }}</flux:menu.heading>
                    @foreach ($menus as $menu)
                        <flux:menu.item wire:key="menu-tab-{{ $menu->slug }}"
                            wire:click="selectMenu('{{ $menu->slug }}')"
                            :icon="$menu->slug === \App\Models\MenuItem::GROUP_ADMIN_SIDEBAR ? 'lock-closed' : ($activeGroup === $menu->slug ? 'check' : null)">
                            {{ $menu->name }}
                        </flux:menu.item>
                    @endforeach

                    <flux:menu.separator />
                    <flux:menu.item icon="plus" wire:click="openNewMenu">{{ __('New menu') }}</flux:menu.item>
                    @if ($canDeleteActive)
                        <flux:menu.item icon="trash" variant="danger" wire:click="confirmDeleteMenu">{{ __('Delete this menu') }}</flux:menu.item>
                    @endif
                </flux:menu>
            </flux:dropdown>
            <flux:button size="sm" variant="primary" class="admin-btn-success" icon="plus" wire:click="openCreate()">
                {{ __('New menu item') }}
            </flux:button>
        </div>
    </div>

    <div class="bg-white rounded-[5px] border border-zinc-100 shadow-sm overflow-hidden">

    @php
        $searching = trim($search) !== '';
        $hasGroups = $groups->isNotEmpty();
    @endphp

    {{-- Toolbar: search + expand/collapse --}}
    <div class="flex flex-wrap items-center gap-3 border-b border-zinc-100 px-4 py-3">
        <div class="min-w-0 flex-1 basis-56">
            <flux:input size="sm" icon="magnifying-glass" wire:model.live.debounce.250ms="search"
                placeholder="{{ __('Search items by name or link…') }}" clearable />
        </div>

        <div class="flex items-center gap-1.5">
            @if ($hasGroups && ! $searching)
                <flux:button size="xs" variant="ghost" icon="chevron-double-down" wire:click="expandAll">{{ __('Expand all') }}</flux:button>
                <flux:button size="xs" variant="ghost" icon="chevron-double-up" wire:click="collapseAll">{{ __('Collapse all') }}</flux:button>
            @endif
            <span class="text-xs text-zinc-400">
                {{ trans_choice(':count item|:count items', $totalItems, ['count' => $totalItems]) }}
            </span>
        </div>
    </div>

    {{-- Sortable tree --}}
    <div x-data="{
            initTop() {
                if (typeof Sortable === 'undefined') return;
                new Sortable(this.$refs.topSortable, {
                    animation: 150,
                    handle: '.drag-handle',
                    ghostClass: 'bg-blue-50',
                    onEnd: () => {
                        const rows = [...this.$refs.topSortable.querySelectorAll(':scope > [data-item-id]')];
                        $wire.reorderTopLevel(rows.map(r => parseInt(r.dataset.itemId)));
                    },
                });
            },
            initGroup(el, groupId) {
                if (typeof Sortable === 'undefined') return;
                new Sortable(el, {
                    animation: 150,
                    handle: '.drag-handle',
                    ghostClass: 'bg-blue-50',
                    onEnd: () => {
                        const rows = [...el.querySelectorAll(':scope > [data-item-id]')];
                        $wire.reorderChildren(groupId, rows.map(r => parseInt(r.dataset.itemId)));
                    },
                });
            },
        }"
        x-init="initTop()">
        <div class="divide-y divide-zinc-100" x-ref="topSortable">
            @forelse ($topLevel as $item)
                <div data-item-id="{{ $item->id }}" wire:key="top-{{ $item->id }}">

                    @include('livewire.admin.menu.partials._row', ['item' => $item, 'child' => false, 'searching' => $searching])

                    {{-- Children --}}
                    @if ($item->is_group && $this->isGroupExpanded($item->id))
                        <div class="ml-[2.9rem] mr-3 mb-3 space-y-0.5 border-l-2 border-indigo-100 pl-3" x-init="initGroup($el, {{ $item->id }})">
                            @forelse ($item->children as $childItem)
                                <div data-item-id="{{ $childItem->id }}" wire:key="child-{{ $childItem->id }}">
                                    @include('livewire.admin.menu.partials._row', ['item' => $childItem, 'child' => true, 'searching' => $searching])
                                </div>
                            @empty
                                <p class="px-2 py-2 text-xs text-zinc-400">
                                    {{ __('No items in this group yet.') }}
                                    <button type="button" wire:click="openCreate({{ $item->id }})" class="font-medium text-indigo-600 hover:underline">{{ __('Add the first one') }}</button>
                                </p>
                            @endforelse
                        </div>
                    @endif

                </div>
            @empty
                <div class="px-6 py-16 text-center">
                    <flux:icon.bars-3 class="mx-auto mb-3 size-10 text-zinc-200" />
                    @if ($searching)
                        <p class="text-sm text-zinc-600">{{ __('No items match ":term".', ['term' => $search]) }}</p>
                        <flux:button size="sm" variant="ghost" class="mt-3" wire:click="$set('search', '')">{{ __('Clear search') }}</flux:button>
                    @else
                        <p class="text-sm text-zinc-600">{{ __('No menu items found.') }}</p>
                        <flux:button size="sm" variant="primary" icon="plus" class="mt-3" wire:click="openCreate()">{{ __('New menu item') }}</flux:button>
                    @endif
                </div>
            @endforelse
        </div>
    </div>

    {{-- Create / Edit Modal --}}
    <flux:modal name="menu-item-form" class="md:w-md"
        x-on:open-modal.window="if ($event.detail.name === 'menu-item-form') $flux.modal('menu-item-form').show()"
        x-on:close-modal.window="if ($event.detail.name === 'menu-item-form') $flux.modal('menu-item-form').close()">
        <div class="space-y-4">
            <flux:heading>{{ $editingId ? __('Edit menu item') : __('New menu item') }}</flux:heading>

            <flux:field>
                <flux:label>{{ __('Label') }}</flux:label>
                <flux:input wire:model="label" placeholder="{{ __('e.g. Reports') }}" />
                <flux:error name="label" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Icon') }}</flux:label>
                <div class="flex items-center gap-2">
                    <flux:input wire:model.live="icon" placeholder="home" class="font-mono" />
                    <div class="w-9 h-9 shrink-0 rounded-lg border border-zinc-200 flex items-center justify-center text-zinc-500">
                        @if (\App\Models\MenuItem::iconExists($icon))
                            <x-dynamic-component :component="'flux::icon.'.$icon" class="size-4.5" />
                        @else
                            <flux:icon.question-mark-circle class="size-4.5 text-zinc-300" />
                        @endif
                    </div>
                </div>
                <flux:description>{{ __('A Flux icon name, e.g. home, cube, users.') }}</flux:description>
                <flux:error name="icon" />
            </flux:field>

            @unless ($editingId || $parent_id)
                <flux:field variant="inline">
                    <flux:switch wire:model.live="is_group" />
                    <flux:label>{{ __('This is a group (holds other items)') }}</flux:label>
                </flux:field>
            @endunless

            @unless ($is_group)
                <flux:field>
                    <flux:label>{{ __('Link to') }}</flux:label>
                    <flux:select wire:model.live="linkType">
                        <flux:select.option value="custom">{{ __('Custom link') }}</flux:select.option>
                        <flux:select.option value="brand">{{ __('Brand') }}</flux:select.option>
                        <flux:select.option value="category">{{ __('Category') }}</flux:select.option>
                        <flux:select.option value="page">{{ __('Page') }}</flux:select.option>
                    </flux:select>
                    <flux:error name="linkType" />
                </flux:field>

                @if ($linkType === 'custom')
                    <flux:field>
                        <flux:label>{{ __('Link') }}</flux:label>
                        <flux:input wire:model="url" placeholder="/about or https://example.com" />
                        <flux:description>{{ __('A path (e.g. /about) or a full URL.') }}</flux:description>
                        <flux:error name="url" />
                    </flux:field>
                @elseif ($linkType === 'brand')
                    <flux:field>
                        <flux:label>{{ __('Brand') }}</flux:label>
                        <flux:select wire:model="linkedBrandId">
                            <flux:select.option value="">{{ __('Select a brand') }}</flux:select.option>
                            @foreach ($brands as $brand)
                                <flux:select.option value="{{ $brand->id }}">{{ $brand->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="linkedBrandId" />
                    </flux:field>
                @elseif ($linkType === 'category')
                    <flux:field>
                        <flux:label>{{ __('Category') }}</flux:label>
                        <flux:select wire:model="linkedCategoryId">
                            <flux:select.option value="">{{ __('Select a category') }}</flux:select.option>
                            @foreach ($categories as $category)
                                <flux:select.option value="{{ $category->id }}">{{ str_repeat('— ', max(0, $category->depth)) }}{{ $category->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="linkedCategoryId" />
                    </flux:field>
                @elseif ($linkType === 'page')
                    <flux:field>
                        <flux:label>{{ __('Page') }}</flux:label>
                        <flux:select wire:model="linkedPageId">
                            <flux:select.option value="">{{ __('Select a page') }}</flux:select.option>
                            @foreach ($pages as $page)
                                <flux:select.option value="{{ $page->id }}">{{ $page->title }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="linkedPageId" />
                    </flux:field>
                @endif

                @if ($linkType !== 'custom' && $url)
                    <p class="mt-1 font-mono text-[11px] text-zinc-500">{{ $url }}</p>
                @endif

                <flux:field>
                    <flux:label>{{ __('Group') }}</flux:label>
                    <flux:select wire:model="parent_id">
                        <flux:select.option value="">{{ __('— Top level —') }}</flux:select.option>
                        @foreach ($groups as $group)
                            <flux:select.option value="{{ $group->id }}">{{ __($group->label) }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="parent_id" />
                </flux:field>
            @endunless

            <div class="flex gap-2 pt-1">
                <button wire:click="save" wire:loading.attr="disabled"
                    class="admin-btn-save inline-flex items-center gap-2 px-5 h-8 text-sm font-medium rounded-lg text-white disabled:opacity-60 transition-colors">
                    {{ $editingId ? __('Update') : __('Create') }}
                </button>
                <flux:modal.close>
                    <flux:button variant="ghost" size="sm">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

    {{-- New Menu Modal --}}
    <flux:modal name="new-menu-form" class="md:w-96"
        x-on:open-modal.window="if ($event.detail.name === 'new-menu-form') $flux.modal('new-menu-form').show()"
        x-on:close-modal.window="if ($event.detail.name === 'new-menu-form') $flux.modal('new-menu-form').close()">
        <div class="space-y-4">
            <flux:heading>{{ __('New menu') }}</flux:heading>
            <flux:text class="text-sm text-zinc-500">
                {{ __('Give it a name — e.g. Frontend Menu, Footer Menu.') }}
            </flux:text>

            <flux:field>
                <flux:label>{{ __('Name') }}</flux:label>
                <flux:input wire:model="newMenuLabel" placeholder="{{ __('e.g. Frontend Menu') }}" />
                <flux:error name="newMenuLabel" />
            </flux:field>

            <div class="flex gap-2 pt-1">
                <button wire:click="createMenu" wire:loading.attr="disabled"
                    class="admin-btn-save inline-flex items-center gap-2 px-5 h-8 text-sm font-medium rounded-lg text-white disabled:opacity-60 transition-colors">
                    {{ __('Create') }}
                </button>
                <flux:modal.close>
                    <flux:button variant="ghost" size="sm">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

    {{-- Delete Menu Modal --}}
    <flux:modal name="menu-delete" class="md:w-96"
        x-on:open-modal.window="if ($event.detail.name === 'menu-delete') $flux.modal('menu-delete').show()"
        x-on:close-modal.window="if ($event.detail.name === 'menu-delete') $flux.modal('menu-delete').close()">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-red-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6" />
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <flux:heading>{{ __('Delete menu?') }}</flux:heading>
                    @if ($activeMenu)
                        <p class="text-sm font-medium text-zinc-700 truncate">{{ $activeMenu->name }}</p>
                    @endif
                </div>
            </div>
            @if ($activeItemCount > 0)
                <div class="flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800">
                    <flux:icon.exclamation-triangle class="size-4 shrink-0 mt-0.5" />
                    <span>{{ trans_choice('This menu has :count item, which will also be deleted.|This menu has :count items, which will also be deleted.', $activeItemCount, ['count' => $activeItemCount]) }}</span>
                </div>
            @endif
            <flux:text class="text-sm text-zinc-500">
                {{ __('This action cannot be undone.') }}
            </flux:text>
            <div class="flex gap-2 pt-1">
                <button wire:click="deleteMenu"
                    class="inline-flex h-8 items-center gap-2 px-4 text-sm font-medium rounded-lg text-white bg-red-600 hover:bg-red-700 transition-colors border-none cursor-pointer">
                    {{ __('Delete') }}
                </button>
                <flux:modal.close>
                    <flux:button variant="ghost" size="sm">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

    {{-- Delete Item Modal --}}
    <flux:modal name="menu-item-delete" class="md:w-96"
        x-on:open-modal.window="if ($event.detail.name === 'menu-item-delete') $flux.modal('menu-item-delete').show()"
        x-on:close-modal.window="if ($event.detail.name === 'menu-item-delete') $flux.modal('menu-item-delete').close()">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-red-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3 6 5 6 21 6" />
                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" />
                    </svg>
                </div>
                <flux:heading>{{ __('Delete menu item?') }}</flux:heading>
            </div>
            <flux:text class="text-sm text-zinc-500">
                {{ __('This action cannot be undone. Groups with items inside cannot be deleted until those items are moved or removed.') }}
            </flux:text>
            <div class="flex gap-2 pt-1">
                <button wire:click="delete"
                    class="inline-flex h-8 items-center gap-2 px-4 text-sm font-medium rounded-lg text-white bg-red-600 hover:bg-red-700 transition-colors border-none cursor-pointer">
                    {{ __('Delete') }}
                </button>
                <flux:modal.close>
                    <flux:button variant="ghost" size="sm">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>

</div>
</div>
