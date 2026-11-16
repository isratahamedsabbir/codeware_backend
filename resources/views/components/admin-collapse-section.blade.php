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
    @props locales    list of language codes (['en', 'bn']): puts a language switch in the
                      header and exposes `locale` to the content, so a field per language can
                      sit in <x-admin-locale-panel :code="'bn'"> (x-show="locale === 'bn'")
--}}
@props(['title', 'description' => null, 'icon' => 'rectangle-group', 'badge' => null, 'open' => false, 'locales' => []])

{{-- `validation-failed` is dispatched by a form whose save was refused: a card holding
     an error message opens, so the message is never hidden inside a folded card. --}}
<section x-data="{ open: @js((bool) $open), locale: @js($locales[0] ?? '') }"
    x-on:validation-failed.window="$nextTick(() => { if ($el.querySelector('[data-flux-error]:not(:empty)')) open = true })" {{ $attributes->class('overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700') }}>
    <div class="flex items-center gap-2 bg-zinc-50/70 pr-4 transition hover:bg-zinc-50 dark:bg-zinc-800/40 dark:hover:bg-zinc-800/60">
        <button type="button" x-on:click="open = ! open" :aria-expanded="open"
            class="flex min-w-0 flex-1 items-center justify-between gap-3 px-4 py-3 text-left">
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
        </button>

        @if (count($locales) > 1)
            <div class="flex shrink-0 gap-1">
                @foreach ($locales as $code)
                    <button type="button" x-on:click="locale = '{{ $code }}'; open = true"
                        :class="locale === '{{ $code }}' ? 'bg-zinc-900 text-white' : 'bg-zinc-100 text-zinc-500 hover:bg-zinc-200'"
                        class="rounded-md px-3 py-1 text-xs font-medium transition-colors">{{ strtoupper($code) }}</button>
                @endforeach
            </div>
        @endif

        <button type="button" x-on:click="open = ! open" :aria-expanded="open" aria-label="Toggle section" class="shrink-0 p-1">
            <flux:icon.chevron-down class="size-4 text-zinc-400 transition" ::class="open && 'rotate-180'" />
        </button>
    </div>

    <div x-show="open" x-cloak class="space-y-5 border-t border-zinc-200 p-4 dark:border-zinc-700">
        {{ $slot }}
    </div>
</section>
