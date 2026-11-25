@props(['scope', 'items'])

{{-- Hide/show chips for the Screen elements panel. Push this from a screen with
     @push('screen-options'). $items is [id => ['label' => ..., 'icon' => ...]];
     the screen hides each element with x-show="! $store.screenEls.isHidden(scope, id)".
     The array key is the element id the store tracks, so iterate $items as it is. --}}
<div class="flex flex-wrap items-center gap-1.5">
    @foreach ($items as $id => $item)
        <button type="button" role="switch"
                title="{{ $item['label'] }}"
                x-bind:aria-checked="! $store.screenEls.isHidden('{{ $scope }}', '{{ $id }}')"
                x-on:click="$store.screenEls.toggle('{{ $scope }}', '{{ $id }}')"
                x-bind:class="$store.screenEls.isHidden('{{ $scope }}', '{{ $id }}')
                    ? 'border-dashed border-zinc-300 bg-transparent text-zinc-400 hover:border-zinc-400 hover:text-zinc-600 dark:border-zinc-600 dark:text-zinc-500 dark:hover:text-zinc-300'
                    : 'border-zinc-200 bg-white text-zinc-700 shadow-xs hover:border-primary/40 hover:bg-primary/5 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100'"
                class="group inline-flex h-7 items-center gap-1.5 rounded-lg border ps-2 pe-1.5 text-xs font-medium transition-all duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40">
            <flux:icon :name="$item['icon']" class="size-3.5 shrink-0 opacity-70" />
            <span x-bind:class="$store.screenEls.isHidden('{{ $scope }}', '{{ $id }}') ? 'line-through decoration-zinc-300' : ''">{{ $item['label'] }}</span>

            {{-- On/off indicator: a tick when the element is on screen, an eye-slash when hidden. --}}
            <span x-bind:class="$store.screenEls.isHidden('{{ $scope }}', '{{ $id }}')
                    ? 'bg-zinc-100 text-zinc-400 dark:bg-zinc-700'
                    : 'bg-emerald-500 text-white'"
                  class="ms-0.5 flex size-4 shrink-0 items-center justify-center rounded-full transition-colors">
                <flux:icon.check x-show="! $store.screenEls.isHidden('{{ $scope }}', '{{ $id }}')" class="size-2.5" />
                <flux:icon.eye-slash x-cloak x-show="$store.screenEls.isHidden('{{ $scope }}', '{{ $id }}')" class="size-2.5" />
            </span>
        </button>
    @endforeach
</div>
