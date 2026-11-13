{{--
    One theme's settings on a page of their own (Theme Settings → a card's
    Settings button): its Theme Settings (the global look) and its Page Settings
    (its pages and their sections). Same component state and the same Save as
    the Theme Settings screen — see App\Livewire\Admin\ThemeSettings\Edit.
--}}
@push('page-header-actions')
    {{-- Plain onclick, as on the theme list: this is rendered by the layout's header
         through @stack, outside the component, so it cannot call $wire. The root
         <div> below forwards the event to downloadSettings(). --}}
    <flux:button variant="outline" size="sm" icon="arrow-down-tray"
        onclick="window.dispatchEvent(new CustomEvent('download-theme-settings'))">
        Download settings
    </flux:button>
    <flux:button variant="outline" size="sm" icon="arrow-left" href="{{ route('admin.theme-settings') }}" wire:navigate>
        All themes
    </flux:button>
@endpush

<div class="space-y-5" x-on:download-theme-settings.window="$wire.downloadSettings()">
    {{-- ── Theme's own settings ──────────────────────────────────────────────
                 If the selected theme ships a settings.blade.php at its root, render it
                 inline (its fields bind to settings.theme_{slug}_* keys, which this
                 component hydrates from the theme's own theme.json on mount and writes
                 back to it on save).

                 The fields and the file are deliberately separate things. The form is
                 declared by the theme's settings.blade.php, so it renders whether or not
                 the theme has a theme.json - and when the file is missing, that is what
                 this card has to say out loud, because otherwise the owner edits a form
                 full of blank fields, presses Save, and concludes the panel threw their
                 settings away rather than that there was nowhere to put them. --}}

            @if ($selectedHasSettings)
                <x-admin-section-card plain header-border="border-zinc-100" icon="adjustments-horizontal" title="Theme Settings"
                    description="Everything the {{ $selectedSlug }} theme defines for itself. Stored in its own {{ \App\Support\ThemeSettings::FILE }} inside the theme folder, not in the database.">
                    <x-slot:titleActions>
                        {{-- Points into the Developer Guide rather than opening a copy of
                             it. Everything this button used to explain - how a theme
                             declares settings, where they are stored, how a theme folder
                             is laid out, why the routes file matters - is answered once in
                             the guide, so the modal was a second copy to keep true, and the
                             first one to go stale.

                             #theme-settings rather than the top of the Themes section: this
                             card is about the settings themselves, and the guide answers
                             that in three parts in order - where a setting lives, how to
                             read it, how to declare a new one - which is why those are
                             grouped under that one anchor.

                             A plain anchor rather than wire:navigate: the guide is another
                             page entirely, and a full load is what actually honours the
                             fragment and lands on the heading. --}}
                        <a href="{{ route('admin.developer-guide') }}#theme-settings"
                            title="How theme settings work and are declared — open the Developer Guide"
                            aria-label="How theme settings work and are declared — open the Developer Guide"
                            class="flex size-5 items-center justify-center text-zinc-400 transition-colors hover:text-primary">
                            <flux:icon.information-circle class="size-4" />
                        </a>
                    </x-slot:titleActions>

                    {{-- Right of the header: the file's state, and the button that puts
                         it back. A theme is a folder that can be re-downloaded, replaced
                         or checked out from an older branch, and the file goes with it —
                         so this is a state the owner reaches in the ordinary course of
                         managing themes, not a corruption to report. --}}
                    <x-slot:actions>
                        @if ($settingsFileExists)
                            <span class="hidden items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 font-mono text-[11px] font-medium text-emerald-700 sm:inline-flex dark:bg-emerald-500/10 dark:text-emerald-400"
                                title="{{ $settingsFilePath }}">
                                <flux:icon.document-text class="size-3.5" />
                                {{ $settingsFileCount }} {{ Str::plural('value', $settingsFileCount) }}
                            </span>
                        @else
                            <flux:button variant="primary" size="sm" icon="plus" wire:click="createSettingsFile"
                                wire:loading.attr="disabled">
                                <span wire:loading.remove>Create {{ \App\Support\ThemeSettings::FILE }}</span>
                                <span wire:loading>Creating…</span>
                            </flux:button>
                        @endif
                    </x-slot:actions>

                    @error('settingsFile')
                        <p class="mb-4 flex items-start gap-2 rounded-lg border border-red-200 bg-red-50/70 p-3 text-xs font-medium text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-400">
                            <flux:icon.exclamation-triangle class="mt-px size-4 shrink-0" />
                            {{ $message }}
                        </p>
                    @enderror

                    @unless ($settingsFileExists)
                        <div class="mb-5 flex flex-wrap items-start gap-4 rounded-lg border border-amber-200 bg-amber-50/70 p-4 dark:border-amber-500/30 dark:bg-amber-500/10">
                            <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400">
                                <flux:icon.document-plus class="size-5" />
                            </span>

                            <div class="min-w-0 flex-1 text-sm leading-relaxed text-amber-800 dark:text-amber-200">
                                <p class="font-semibold">This theme has no {{ \App\Support\ThemeSettings::FILE }} file.</p>
                                <p class="mt-1 text-xs text-amber-700/90 dark:text-amber-300/80">
                                    Its name and its settings both live in a
                                    <code class="rounded bg-amber-100/70 px-1 py-0.5 font-mono text-[10px] dark:bg-amber-500/15">{{ \App\Support\ThemeSettings::FILE }}</code>
                                    inside the theme folder, which means that file can be lost
                                    along with the folder - a re-uploaded theme, an older checkout,
                                    a deploy that shipped templates but not content. The fields below
                                    still render, but there is nowhere to save them until it is back.
                                    Create it and they will be written there on the next save.
                                </p>

                                <p class="mt-2 break-all font-mono text-[11px] text-amber-700/80 dark:text-amber-300/70">
                                    {{ $settingsFilePath ?? base_path('themes/'.$selectedSlug.'/'.App\Support\ThemeSettings::FILE) }}
                                </p>
                            </div>

                            <flux:button variant="primary" size="sm" icon="plus" wire:click="createSettingsFile"
                                wire:loading.attr="disabled" class="shrink-0">
                                <span wire:loading.remove>Create {{ \App\Support\ThemeSettings::FILE }}</span>
                                <span wire:loading>Creating…</span>
                            </flux:button>
                        </div>
                    @endunless

                    @include(App\Support\Themes::viewNamespace($selectedSlug).'::settings', [
                        'themeSlug' => $selectedSlug,
                        'settings' => $settings,
                    ])
                </x-admin-section-card>
            @endif

            {{-- ── Page Settings ──
                 Content, as opposed to the Theme Settings card above (the global look:
                 colours, typography): the pages the live theme serves on the left, and for
                 the page you pick, its sections — title, subtitle, image, button, text
                 colour — on the right. Same component state as the Theme Settings card, so the
                 one Save Changes bar below saves both. --}}
            <x-admin-section-card plain header-border="border-zinc-100" icon="document-text" title="Page Settings"
                description="The pages this theme serves. Pick a page to edit its sections — titles, subtitles, images, buttons and text colour.">
                @include('livewire.admin.theme-settings.page-settings')
            </x-admin-section-card>

    <div class="sticky bottom-0 z-10 flex flex-col gap-3 rounded-[5px] border border-zinc-200 bg-white/95 px-5 py-3.5 backdrop-blur dark:border-zinc-700 dark:bg-zinc-800/90 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-xs text-zinc-400">Your changes apply to the public site once saved.</p>
        <div class="flex items-center gap-2.5">
            <flux:button variant="outline" wire:click="resetSettings" size="sm">Discard</flux:button>
            <flux:button variant="primary" wire:click="save" wire:loading.attr="disabled" wire:target="save" size="sm">
                <span wire:loading.remove wire:target="save">Save Changes</span>
                <span wire:loading wire:target="save">Saving…</span>
            </flux:button>
        </div>
    </div>

    <livewire:admin.media-library.picker-modal key="theme-edit-picker-modal" />
</div>
