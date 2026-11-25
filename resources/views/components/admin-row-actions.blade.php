@props(['actions' => []])

@php
    // Full literal utility strings so Tailwind's scanner can see them (dynamic
    // "border-{$color}" concatenation would be invisible to the JIT compiler).
    $palettes = [
        'primary' => 'bg-primary/10 text-primary ring-primary/20 hover:bg-primary hover:text-white hover:ring-primary',
        'secondary' => 'bg-secondary/10 text-secondary ring-secondary/20 hover:bg-secondary hover:text-white hover:ring-secondary',
        'rose-500' => 'bg-rose-500/10 text-rose-600 ring-rose-500/20 hover:bg-rose-500 hover:text-white hover:ring-rose-500',
        'emerald-500' => 'bg-emerald-500/10 text-emerald-600 ring-emerald-500/20 hover:bg-emerald-500 hover:text-white hover:ring-emerald-500',
        'cyan-500' => 'bg-cyan-500/10 text-cyan-600 ring-cyan-500/20 hover:bg-cyan-500 hover:text-white hover:ring-cyan-500',
        'amber-500' => 'bg-amber-500/10 text-amber-600 ring-amber-500/20 hover:bg-amber-500 hover:text-white hover:ring-amber-500',
        'zinc-500' => 'bg-zinc-500/10 text-zinc-600 ring-zinc-500/20 hover:bg-zinc-600 hover:text-white hover:ring-zinc-600',
    ];

    // Icon tint inside the dropdown menu, so each row is recognisable by colour as well as label.
    $menuTints = [
        'primary' => 'text-primary', 'secondary' => 'text-secondary', 'rose-500' => 'text-rose-500',
        'emerald-500' => 'text-emerald-500', 'cyan-500' => 'text-cyan-500', 'amber-500' => 'text-amber-500', 'zinc-500' => 'text-zinc-500',
    ];

    $btn = 'inline-flex items-center justify-center size-7 rounded-md ring-1 ring-inset transition-colors duration-150 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary';

    // Exact SVG markup reused from the previous per-page inline buttons, so
    // switching to this shared component doesn't change how any icon looks.
    $icons = [
        'pencil' => '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" /><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />',
        'document' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" /><polyline points="14 2 14 8 20 8" />',
        'squares' => '<rect x="3" y="3" width="7" height="7" /><rect x="14" y="3" width="7" height="7" /><rect x="14" y="14" width="7" height="7" /><rect x="3" y="14" width="7" height="7" />',
        'copy' => '<rect x="9" y="9" width="13" height="13" rx="2" /><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />',
        'trash' => '<polyline points="3 6 5 6 21 6" /><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6" /><path d="M10 11v6" /><path d="M14 11v6" /><path d="M9 6V4h6v2" />',
        'grid-cross' => '<rect x="3" y="3" width="7" height="7" /><rect x="14" y="3" width="7" height="7" /><rect x="3" y="14" width="7" height="7" /><line x1="17.5" y1="14" x2="17.5" y2="21" /><line x1="14" y1="17.5" x2="21" y2="17.5" />',
        'star' => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />',
        'eye' => '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" /><circle cx="12" cy="12" r="3" />',
        'eye-slash' => '<path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.5 18.5 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24" /><line x1="1" y1="1" x2="23" y2="23" />',
        'envelope' => '<rect x="2" y="4" width="20" height="16" rx="2" /><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7" />',
        'external-link' => '<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" /><polyline points="15 3 21 3 21 9" /><line x1="10" y1="14" x2="21" y2="3" />',
        'arrow-down-tray' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" /><polyline points="7 10 12 15 17 10" /><line x1="12" y1="15" x2="12" y2="3" />',
    ];

    $visible = collect($actions)->filter(fn ($action) => $action['visible'] ?? true)->values();
    $mode = \App\Models\Setting::get('admin_actions_display', 'inline');
    // Every action disabled (e.g. a bulk selection is active elsewhere on the
    // page) — disable the trigger itself too, not just the menu items inside
    // it, so it can't even be opened.
    $allDisabled = $visible->isNotEmpty() && $visible->every(fn ($action) => $action['disabled'] ?? false);
@endphp

@if ($mode === 'dropdown')
    {{-- flux:dropdown (native popover-backed), not a hand-rolled absolute
         panel — same clipping problem as the tooltip below: this cell sits
         inside a horizontally-scrolling, sticky-column table, where
         `overflow-x-auto` implicitly clips vertical overflow too. A native
         popover also auto-closes any other open one, so no shared Alpine
         store is needed to keep only one row's menu open at a time. The
         <tr> in each index.blade.php right-clicks by dispatching a real
         click at [data-actions-trigger] below to open it. --}}
    <div class="flex items-center justify-center">
    <flux:dropdown position="bottom" align="end">
        <button type="button" data-actions-trigger
            aria-label="Actions"
            @disabled($allDisabled)
            class="{{ $btn }} {{ $allDisabled ? 'bg-zinc-50 text-zinc-300 ring-zinc-100 cursor-not-allowed' : 'bg-zinc-100 text-zinc-600 ring-zinc-200 hover:bg-zinc-200 cursor-pointer' }}">
            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor">
                <circle cx="5" cy="12" r="2" /><circle cx="12" cy="12" r="2" /><circle cx="19" cy="12" r="2" />
            </svg>
        </button>

        <flux:menu>
            @foreach ($visible as $action)
                @if ($action['disabled'] ?? false)
                    <flux:menu.item disabled>
                        <svg class="w-4 h-4 shrink-0 me-2 {{ ($action['disabled'] ?? false) ? '' : ($menuTints[$action['color'] ?? 'primary'] ?? '') }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $icons[$action['icon']] !!}</svg>
                        {{ $action['label'] }}
                    </flux:menu.item>
                @elseif (isset($action['href']) && ($action['external'] ?? false))
                    <flux:menu.item :href="$action['href']" target="_blank" rel="noopener" :variant="($action['color'] ?? null) === 'rose-500' ? 'danger' : 'default'">
                        <svg class="w-4 h-4 shrink-0 me-2 {{ ($action['disabled'] ?? false) ? '' : ($menuTints[$action['color'] ?? 'primary'] ?? '') }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $icons[$action['icon']] !!}</svg>
                        {{ $action['label'] }}
                    </flux:menu.item>
                @elseif (isset($action['href']))
                    <flux:menu.item :href="$action['href']" wire:navigate :variant="($action['color'] ?? null) === 'rose-500' ? 'danger' : 'default'">
                        <svg class="w-4 h-4 shrink-0 me-2 {{ ($action['disabled'] ?? false) ? '' : ($menuTints[$action['color'] ?? 'primary'] ?? '') }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $icons[$action['icon']] !!}</svg>
                        {{ $action['label'] }}
                    </flux:menu.item>
                @else
                    <flux:menu.item wire:click="{{ $action['wireClick'] }}" :variant="($action['color'] ?? null) === 'rose-500' ? 'danger' : 'default'">
                        <svg class="w-4 h-4 shrink-0 me-2 {{ ($action['disabled'] ?? false) ? '' : ($menuTints[$action['color'] ?? 'primary'] ?? '') }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $icons[$action['icon']] !!}</svg>
                        {{ $action['label'] }}
                    </flux:menu.item>
                @endif
            @endforeach
        </flux:menu>
    </flux:dropdown>
    </div>
@else
    <div class="flex items-center justify-center gap-1.5">
        @foreach ($visible as $action)
            @php
                $tone = $palettes[$action['color'] ?? 'primary'] ?? $palettes['primary'];
                // A destructive action sits behind a thin divider so it is never
                // mistaken for (or mis-clicked as) its neighbour.
                $danger = ($action['color'] ?? null) === 'rose-500' && $loop->index > 0;
                $svg = '<svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'.$icons[$action['icon']].'</svg>';
            @endphp
            @if ($danger)
                <span class="mx-0.5 h-4 w-px bg-zinc-200" aria-hidden="true"></span>
            @endif
            @if ($action['disabled'] ?? false)
                <flux:tooltip :content="$action['label']" wire:key="tip-{{ $loop->index }}-{{ \Illuminate\Support\Str::slug($action['label']) }}">
                    <span aria-label="{{ $action['label'] }}" class="{{ $btn }} bg-zinc-50 text-zinc-300 ring-zinc-100 cursor-not-allowed">{!! $svg !!}</span>
                </flux:tooltip>
            @else
                {{-- flux:tooltip (not a hand-rolled absolute span) — this cell sits
                     inside a horizontally-scrolling, sticky-column table, where
                     `overflow-x-auto` implicitly forces `overflow-y` to clip too,
                     cutting off a plain absolute tooltip. --}}
                <flux:tooltip :content="$action['label']" wire:key="tip-{{ $loop->index }}-{{ \Illuminate\Support\Str::slug($action['label']) }}">
                    @if (isset($action['href']) && ($action['external'] ?? false))
                        <a href="{{ $action['href'] }}" target="_blank" rel="noopener" aria-label="{{ $action['label'] }}" class="{{ $btn }} {{ $tone }}">{!! $svg !!}</a>
                    @elseif (isset($action['href']))
                        <a href="{{ $action['href'] }}" wire:navigate aria-label="{{ $action['label'] }}" class="{{ $btn }} {{ $tone }}">{!! $svg !!}</a>
                    @else
                        <button type="button" wire:click="{{ $action['wireClick'] }}" aria-label="{{ $action['label'] }}" class="{{ $btn }} {{ $tone }} cursor-pointer">{!! $svg !!}</button>
                    @endif
                </flux:tooltip>
            @endif
        @endforeach
    </div>
@endif
