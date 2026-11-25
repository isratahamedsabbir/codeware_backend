{{--
    One menu row, used for top-level items and for the children inside a group.
    Expects $item, $child (bool: rendered inside a group) and $searching (bool).
--}}
@php
    $iconName = $item->is_group ? 'folder' : (\App\Models\MenuItem::iconExists($item->icon) ? $item->icon : 'link');
    $target = $item->route_name ?: $item->url;
    $childCount = $item->is_group ? $item->children->count() : 0;
    $expanded = $item->is_group && $this->isGroupExpanded($item->id);
@endphp

{{-- A group row toggles open/closed on click anywhere on it; the handle and the action buttons stop the click. --}}
<div @class([
        'group/row flex items-center gap-2 transition-colors hover:bg-indigo-50/40',
        'rounded-lg px-2 py-2' => $child,
        'px-3 py-3' => ! $child,
        'cursor-pointer select-none' => $item->is_group && ! $searching,
    ])
    @if ($item->is_group && ! $searching) wire:click="toggleGroup({{ $item->id }})" @endif>

    {{-- Drag handle (off while searching: a filtered list cannot be reordered safely) --}}
    <span @click.stop @class([
            'shrink-0 flex items-center justify-center size-6 rounded',
            'drag-handle cursor-grab active:cursor-grabbing text-zinc-300 hover:text-zinc-600 hover:bg-zinc-100' => ! $searching,
            'text-zinc-200' => $searching,
        ])
        title="{{ $searching ? __('Clear the search to reorder') : __('Drag to reorder') }}">
        <flux:icon.bars-2 class="size-4" />
    </span>

    {{-- Icon + label (greyed when hidden) --}}
    <div @class(['flex flex-1 min-w-0 items-center gap-3', 'opacity-50' => ! $item->is_active])>
        <div @class([
            'shrink-0 flex items-center justify-center rounded-lg',
            'size-9' => ! $child,
            'size-7' => $child,
            'bg-indigo-50 text-indigo-600' => $item->is_group,
            'bg-zinc-100 text-zinc-500' => ! $item->is_group,
        ])>
            <x-dynamic-component :component="'flux::icon.'.$iconName" class="{{ $child ? 'size-4' : 'size-5' }}" />
        </div>

        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-x-2 gap-y-0.5">
                <span class="truncate text-sm font-medium text-zinc-800">{{ __($item->label) }}</span>

                @if ($item->is_group)
                    <span class="inline-flex items-center rounded-full bg-indigo-50 px-2 py-0.5 text-[10px] font-semibold text-indigo-600">
                        {{ __('Group') }} · {{ $childCount }}
                    </span>
                    <flux:icon.chevron-down @class(['size-3.5 text-zinc-400 transition-transform', 'rotate-180' => $expanded]) />
                    <span class="sr-only">{{ $expanded ? __('Collapse group') : __('Expand group') }}</span>
                @endif
            </div>

            @if ($target)
                <p class="truncate font-mono text-[11px] text-zinc-400">{{ $target }}</p>
            @endif
        </div>
    </div>

    {{-- Status chip --}}
    @unless ($item->is_active)
        <span class="hidden sm:inline-flex shrink-0 items-center gap-1 rounded-full bg-zinc-100 px-2 py-0.5 text-[10px] font-semibold text-zinc-500">
            <flux:icon.eye-slash class="size-3" /> {{ __('Hidden') }}
        </span>
    @endunless

    {{-- Actions --}}
    <div class="flex shrink-0 items-center gap-1" @click.stop>
        @if ($item->is_group)
            <flux:button size="xs" variant="outline" square icon="plus" wire:click="openCreate({{ $item->id }})"
                title="{{ __('Add item') }}" aria-label="{{ __('Add item') }}" />
        @else
            <button type="button" wire:click="toggleShortMenu({{ $item->id }})"
                title="{{ $item->is_short_menu ? __('Remove from short menu') : __('Add to short menu') }}"
                aria-label="{{ $item->is_short_menu ? __('Remove from short menu') : __('Add to short menu') }}"
                @class([
                    'inline-flex size-7 items-center justify-center rounded-md transition-colors',
                    'text-amber-500 hover:bg-amber-50' => $item->is_short_menu,
                    'text-zinc-300 hover:bg-zinc-100 hover:text-amber-500' => ! $item->is_short_menu,
                ])>
                <flux:icon.star class="size-4" variant="{{ $item->is_short_menu ? 'solid' : 'outline' }}" />
            </button>
        @endif

        <flux:button size="xs" variant="ghost" square icon="pencil-square"
            class="text-indigo-600!" wire:click="edit({{ $item->id }})"
            title="{{ __('Edit') }}" aria-label="{{ __('Edit') }}" />

        <flux:dropdown position="bottom" align="end">
            <flux:button size="xs" variant="ghost" square icon="ellipsis-horizontal" aria-label="{{ __('More actions') }}" />

            <flux:menu>
                <flux:menu.item :icon="$item->is_active ? 'eye-slash' : 'eye'" wire:click="toggleActive({{ $item->id }})">
                    {{ $item->is_active ? __('Deactivate') : __('Activate') }}
                </flux:menu.item>
                <flux:menu.separator />
                <flux:menu.item icon="trash" variant="danger" wire:click="confirmDelete({{ $item->id }})">
                    {{ __('Delete') }}
                </flux:menu.item>
            </flux:menu>
        </flux:dropdown>
    </div>
</div>
