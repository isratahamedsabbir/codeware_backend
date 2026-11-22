{{-- The pages of the pages table, one nav button each. --}}
@foreach ($psPages as $psPage)
    @php $psActive = $psSelected && $psPage->id === $psSelected->id; @endphp
    <button type="button" wire:click="selectPage({{ $psPage->id }})" wire:key="ps-nav-{{ $psPage->id }}"
        @if ($psActive) aria-current="page" @endif
        @class([
            'group flex w-full items-center gap-3 rounded-lg border px-2.5 py-2 text-left transition',
            'border-primary/25 bg-primary/10 text-primary' => $psActive,
            'border-transparent text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800' => ! $psActive,
        ])>
        <span @class([
            'flex size-8 shrink-0 items-center justify-center rounded-md transition',
            'bg-primary text-white' => $psActive,
            'bg-zinc-100 text-zinc-500 dark:bg-zinc-800' => ! $psActive,
        ])>
            <flux:icon :name="$psPage->slug === 'home' ? 'home' : 'document-text'" variant="mini" class="size-4" />
        </span>
        <span class="min-w-0 flex-1">
            <span class="block truncate text-sm font-semibold">{{ $psPage->getTranslation('title', $psPrimary, false) ?: 'Untitled' }}</span>
            <span class="block truncate font-mono text-[10px] opacity-60">{{ $psPage->slug === 'home' ? '/' : '/'.$psPage->slug }}</span>
        </span>
        @if ($psPage->status !== 'active')
            <span class="shrink-0 rounded-full bg-zinc-200 px-1.5 py-0.5 text-[9px] font-bold uppercase text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">Off</span>
        @endif
    </button>
@endforeach
