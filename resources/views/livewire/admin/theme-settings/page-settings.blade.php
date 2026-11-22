{{--
    Page Settings — pages on the left, the picked page's content on the right.
    See App\Livewire\Admin\ThemeSettings\Concerns\ManagesPageSettings.

    Included in the Theme Settings screen's Page Settings card; saved by the
    screen's one Save Changes bar. Image
    pickers dispatch a window event that the screen's one picker modal answers,
    and write the result back to the screen's component.
--}}
<div>
    @if ($psGroups)
        {{-- Settings groups: the theme's own named blocks of settings (Home, About, FAQ…).
             Not rows of the pages table — each name owns one form, themes/{slug}/pages/{key}.blade.php,
             and its values are saved to the theme's theme.json by the one Save bar. --}}
        <div class="grid gap-5 lg:grid-cols-[15rem_minmax(0,1fr)] lg:gap-6">
            <nav aria-label="Pages" class="lg:sticky lg:top-14 lg:self-start">
                <div class="space-y-1">
                    @foreach ($psGroups as $group)
                        @php $groupActive = $psGroup && $group['key'] === $psGroup['key']; @endphp
                        <button type="button" wire:click="selectGroup('{{ $group['key'] }}')" wire:key="ps-group-nav-{{ $group['key'] }}"
                            @if ($groupActive) aria-current="page" @endif
                            @class([
                                'group flex w-full items-center gap-3 rounded-lg border px-2.5 py-2 text-left transition',
                                'border-primary/25 bg-primary/10 text-primary' => $groupActive,
                                'border-transparent text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800' => ! $groupActive,
                            ])>
                            <span @class([
                                'flex size-8 shrink-0 items-center justify-center rounded-md transition',
                                'bg-primary text-white' => $groupActive,
                                'bg-zinc-100 text-zinc-500 dark:bg-zinc-800' => ! $groupActive,
                            ])>
                                <flux:icon :name="$group['key'] === 'home' ? 'home' : 'document-text'" variant="mini" class="size-4" />
                            </span>
                            <span class="min-w-0 flex-1 truncate text-sm font-semibold">{{ $group['name'] }}</span>
                        </button>
                    @endforeach

                    @include('livewire.admin.theme-settings.partials._page-nav')
                </div>
            </nav>

            @if ($psGroup)
                <div class="min-w-0 space-y-6" wire:key="ps-group-{{ $psGroup['key'] }}">
                    @if ($psGroupView)
                        @include($psGroupView)
                    @endif
                </div>
            @elseif ($psSelected)
                @include('livewire.admin.theme-settings.partials._page-panel')
            @endif
        </div>
    @elseif (! $psFeatureOn)
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
            The Pages feature is switched off (Settings → Features), so there are no pages to edit here.
        </div>
    @elseif ($psPages->isEmpty() || ! $psSelected)
        <div class="rounded-lg border border-zinc-200 p-6 text-center text-sm text-zinc-500 dark:border-zinc-700">
            The <strong class="font-semibold text-zinc-700 dark:text-zinc-200">{{ \App\Support\Themes::manifest($psTheme)['name'] }}</strong> theme has no pages of its own to edit.
        </div>
    @else
        <div class="grid gap-5 lg:grid-cols-[15rem_minmax(0,1fr)] lg:gap-6">
            {{-- Pages --}}
            <nav aria-label="Pages" class="lg:sticky lg:top-14 lg:self-start">
                <div class="space-y-1">
                    @include('livewire.admin.theme-settings.partials._page-nav')
                </div>
            </nav>

            @include('livewire.admin.theme-settings.partials._page-panel')
        </div>

    @endif
</div>
