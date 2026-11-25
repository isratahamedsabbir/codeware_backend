@props(['scope', 'items'])

{{-- Hide/show chips for the Screen elements panel. Push this from a screen with
     @push('screen-options'). $items is [id => ['label' => ..., 'icon' => ...]];
     the screen hides each element with x-show="! $store.screenEls.isHidden(scope, id)". --}}
<div class="flex flex-wrap items-center gap-2">
    @foreach ($items as $id => $item)
        <button type="button" role="switch"
                x-bind:aria-checked="! $store.screenEls.isHidden('{{ $scope }}', '{{ $id }}')"
                x-on:click="$store.screenEls.toggle('{{ $scope }}', '{{ $id }}')"
                x-bind:class="$store.screenEls.isHidden('{{ $scope }}', '{{ $id }}')
                    ? 'border-zinc-200 bg-white text-zinc-400 dark:border-zinc-700 dark:bg-zinc-800'
                    : 'border-primary/30 bg-primary/10 text-zinc-700 dark:text-zinc-100'"
                class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40">
            <flux:icon :name="$item['icon']" class="size-3.5" />
            {{ $item['label'] }}
            <span x-text="$store.screenEls.isHidden('{{ $scope }}', '{{ $id }}') ? 'Hidden' : 'Shown'"
                  class="text-[10px] font-medium opacity-60"></span>
        </button>
    @endforeach
</div>
