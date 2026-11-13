{{--
    A collapsible section inside an admin panel: an icon, a title and a one-line
    description in a clickable header, and the content underneath. Closed by default
    — the header alone says what is in it — and opened on arrival only when $open is
    true (a validation error inside it, say, so the message is not hidden).

    The content stays mounted when closed (x-show, not x-if), so media pickers and
    wire:model bindings inside it keep working while it is folded.

    @props icon       a flux icon name
    @props title      the heading
    @props description  one line under it
    @props badge      small mono text beside the title (e.g. "3 cards")
    @props open       start expanded
--}}
@props(['title', 'description' => null, 'icon' => 'rectangle-group', 'badge' => null, 'open' => false])

<section x-data="{ open: @js((bool) $open) }" {{ $attributes->class('overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700') }}>
    <button type="button" x-on:click="open = ! open" :aria-expanded="open"
        class="flex w-full items-center justify-between gap-3 bg-zinc-50/70 px-4 py-3 text-left transition hover:bg-zinc-50 dark:bg-zinc-800/40 dark:hover:bg-zinc-800/60">
        <span class="flex min-w-0 items-center gap-3">
            <span class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-zinc-100 text-zinc-500 dark:bg-zinc-800">
                <flux:icon :name="$icon" class="size-4" />
            </span>
            <span class="min-w-0">
                <span class="flex items-center gap-2">
                    <span class="truncate text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $title }}</span>
                    @if ($badge)
                        <span class="shrink-0 font-mono text-[10px] text-zinc-400">{{ $badge }}</span>
                    @endif
                    {{ $meta ?? '' }}
                </span>
                @if ($description)
                    <span class="block truncate text-xs text-zinc-500">{{ $description }}</span>
                @endif
            </span>
        </span>
        <flux:icon.chevron-down class="size-4 shrink-0 text-zinc-400 transition" ::class="open && 'rotate-180'" />
    </button>

    <div x-show="open" x-cloak class="space-y-5 border-t border-zinc-200 p-4 dark:border-zinc-700">
        {{ $slot }}
    </div>
</section>
