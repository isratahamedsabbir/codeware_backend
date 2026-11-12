{{--
    Page Settings — pages on the left, the picked page's content on the right.
    See App\Livewire\Admin\ThemeSettings\Concerns\ManagesPageSettings.

    Included in the Theme Settings screen's Page Settings card; saved by the
    screen's one Save Changes bar. Image
    pickers dispatch a window event that the screen's one picker modal answers,
    and write the result back to the screen's component.
--}}
<div>
    @if (! $psFeatureOn)
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
            <nav aria-label="Pages" class="space-y-1 lg:sticky lg:top-14 lg:self-start">
                @foreach ($psPages as $psPage)
                    @php $psActive = $psPage->id === $psSelected->id; @endphp
                    <button type="button" wire:click="selectPage({{ $psPage->id }})" wire:key="ps-nav-{{ $psPage->id }}"
                        @class([
                            'flex w-full items-center gap-2.5 rounded-lg px-3 py-2.5 text-left text-sm font-medium transition',
                            'bg-primary/10 text-primary' => $psActive,
                            'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800' => ! $psActive,
                        ])>
                        <flux:icon :name="$psPage->slug === 'home' ? 'home' : 'document-text'" class="size-4 shrink-0" />
                        <span class="min-w-0 flex-1 truncate">{{ $psPage->getTranslation('title', $psPrimary, false) ?: 'Untitled' }}</span>
                        <span class="shrink-0 font-mono text-[10px] opacity-60">{{ $psPage->slug === 'home' ? '/' : '/'.$psPage->slug }}</span>
                    </button>
                @endforeach
            </nav>

            {{-- The picked page --}}
            <div class="min-w-0 space-y-6" wire:key="ps-page-{{ $psSelected->id }}">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-100">{{ $psSelected->getTranslation('title', $psPrimary, false) ?: 'Untitled' }}</h3>
                    <div class="flex items-center gap-2">
                        <flux:button size="sm" variant="ghost" icon="arrow-top-right-on-square" href="{{ $psSelected->slug === 'home' ? url('/') : url('/'.$psSelected->slug) }}" target="_blank">View</flux:button>
                        <flux:button size="sm" variant="ghost" icon="pencil-square" href="{{ route('admin.pages.edit', $psSelected->id) }}" wire:navigate>Full editor</flux:button>
                    </div>
                </div>

                {{-- Title, every language. --}}
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    @foreach ($psLanguages as $language)
                        <flux:field>
                            <flux:label :badge="$language->code">
                                Page title
                                @if ($language->code === $psPrimary)<span class="ml-0.5 text-red-500">*</span>@endif
                            </flux:label>
                            <flux:input wire:model="titles.{{ $psSelected->id }}.{{ $language->code }}" />
                            @if ($language->code === $psPrimary)
                                <flux:error name="titles.{{ $psSelected->id }}.{{ $language->code }}" />
                            @endif
                        </flux:field>
                    @endforeach
                </div>

                {{-- The theme's own built-in home sections (hero slider, promo tiles). --}}
                @if ($psBanners && $psSelected->slug === 'home')
                    <div class="space-y-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-zinc-400">Banners</p>
                        @include($psBanners, ['heroSlides' => $heroSlides, 'promo' => $promo])
                    </div>
                @endif

                {{-- Sections --}}
                @forelse ($psSections as $section)
                    @php $constants = array_values($section->constant ?? []); @endphp

                    <div wire:key="ps-section-{{ $section->id }}" x-data="{ open: {{ $loop->first && ! ($psBanners && $psSelected->slug === 'home') ? 'true' : 'false' }} }"
                        class="overflow-hidden rounded-[5px] border border-zinc-200 dark:border-zinc-700">
                        <button type="button" x-on:click="open = ! open"
                            class="flex w-full items-center justify-between gap-3 bg-zinc-50 px-4 py-3 text-left dark:bg-zinc-800/60">
                            <span class="flex min-w-0 items-center gap-2">
                                <flux:icon.rectangle-group class="size-4 shrink-0 text-zinc-400" />
                                <span class="truncate text-sm font-semibold text-zinc-800 dark:text-zinc-100">{{ $section->name }}</span>
                                <span class="shrink-0 font-mono text-[10px] text-zinc-400">
                                    {{ count($cards[$section->id] ?? []) }} {{ \Illuminate\Support\Str::plural('card', count($cards[$section->id] ?? [])) }}
                                    @if ($constants !== [])· {{ count($constants) }} {{ \Illuminate\Support\Str::plural('constant', count($constants)) }}@endif
                                </span>
                                @if ($section->status !== 'active')
                                    <span class="shrink-0 rounded-full bg-zinc-200 px-2 py-0.5 text-[9px] font-bold uppercase text-zinc-600">Off</span>
                                @endif
                            </span>
                            <flux:icon.chevron-down class="size-4 shrink-0 text-zinc-400 transition" ::class="open && 'rotate-180'" />
                        </button>

                        <div x-show="open" x-cloak class="space-y-5 border-t border-zinc-200 p-4 dark:border-zinc-700">
                            <div class="space-y-3">
                                <div class="flex items-center justify-between">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-zinc-400">Content</p>
                                    <flux:button size="sm" variant="outline" icon="plus" wire:click="addCard({{ $section->id }})">Add item</flux:button>
                                </div>

                                @forelse ($cards[$section->id] ?? [] as $i => $card)
                                    <div wire:key="ps-card-{{ $section->id }}-{{ $i }}"
                                        class="rounded-[5px] border border-zinc-200 bg-zinc-50/60 p-4 dark:border-zinc-700 dark:bg-zinc-800/30">
                                        <div class="mb-3 flex items-center justify-between">
                                            <span class="flex size-6 items-center justify-center rounded-full border border-zinc-300 text-[11px] font-semibold text-zinc-500">{{ $i + 1 }}</span>
                                            <button type="button" wire:click="removeCard({{ $section->id }}, {{ $i }})"
                                                wire:confirm="Remove this item? It is deleted when you save."
                                                class="rounded-lg p-1.5 text-zinc-400 transition hover:bg-rose-50 hover:text-rose-500" aria-label="Remove item">
                                                <flux:icon.trash class="size-4" />
                                            </button>
                                        </div>

                                        <div class="grid grid-cols-1 gap-4 xl:grid-cols-[14rem_minmax(0,1fr)]">
                                            <x-media-picker model="cards.{{ $section->id }}.{{ $i }}.image" label="Image" size-hint="600 × 400"
                                                mimes="jpg,jpeg,png,webp,avif" only-images dropzone />

                                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                                <flux:field>
                                                    <flux:label>Title</flux:label>
                                                    <flux:input wire:model="cards.{{ $section->id }}.{{ $i }}.title" />
                                                </flux:field>
                                                <flux:field>
                                                    <flux:label>Subtitle</flux:label>
                                                    <flux:input wire:model="cards.{{ $section->id }}.{{ $i }}.subtitle" />
                                                </flux:field>
                                                <flux:field class="sm:col-span-2">
                                                    <flux:label>Description</flux:label>
                                                    <flux:textarea wire:model="cards.{{ $section->id }}.{{ $i }}.description" class="h-24" />
                                                </flux:field>
                                                <flux:field>
                                                    <flux:label>Button label</flux:label>
                                                    <flux:input wire:model="cards.{{ $section->id }}.{{ $i }}.button_label" placeholder="e.g. Shop now" />
                                                </flux:field>
                                                <flux:field>
                                                    <flux:label>Button link</flux:label>
                                                    <flux:input wire:model="cards.{{ $section->id }}.{{ $i }}.button_url" icon="link" placeholder="/shop or https://…" />
                                                </flux:field>
                                                <flux:field>
                                                    <flux:label>Text color<x-field-hint text="Colour of this item's title. Leave empty for the theme's heading colour." /></flux:label>
                                                    <div class="flex items-center gap-2">
                                                        <input type="color" aria-label="Pick text color"
                                                            value="{{ preg_match('/^#[0-9a-fA-F]{6}$/', (string) ($card['text_color'] ?? '')) ? $card['text_color'] : '#171717' }}"
                                                            x-on:input="$wire.set('cards.{{ $section->id }}.{{ $i }}.text_color', $event.target.value)"
                                                            class="size-9 shrink-0 cursor-pointer rounded border border-zinc-300 bg-white p-0.5 dark:border-zinc-600" />
                                                        <flux:input wire:model="cards.{{ $section->id }}.{{ $i }}.text_color" placeholder="#171717" class="font-mono" />
                                                    </div>
                                                    <flux:error name="cards.{{ $section->id }}.{{ $i }}.text_color" />
                                                </flux:field>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <p class="rounded-lg border border-dashed border-zinc-300 p-4 text-center text-xs text-zinc-400">Nothing in this section yet.</p>
                                @endforelse
                            </div>

                            {{-- Constants: the key is fixed (a template reads it by name),
                                 so only the value is editable here. --}}
                            @if ($constants !== [])
                                <div class="space-y-3">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-zinc-400">Constants</p>
                                    @foreach ($constants as $i => $pair)
                                        <flux:field wire:key="ps-const-{{ $section->id }}-{{ $i }}">
                                            <flux:label><span class="font-mono text-xs">{{ $pair['key'] ?? '' }}</span></flux:label>
                                            @if (($pair['type'] ?? 'textarea') === 'file')
                                                <x-media-picker model="values.{{ $section->id }}.{{ $i }}" label="" mimes="jpg,jpeg,png,webp,avif,svg,pdf" dropzone />
                                            @else
                                                <flux:textarea wire:model="values.{{ $section->id }}.{{ $i }}" class="h-20" />
                                            @endif
                                        </flux:field>
                                    @endforeach
                                </div>
                            @endif

                            <div class="text-right">
                                <a href="{{ route('admin.cms.edit', ['pageId' => $psSelected->id, 'id' => $section->id]) }}" wire:navigate
                                    class="text-xs font-medium text-primary hover:underline">Edit structure in CMS &rarr;</a>
                            </div>
                        </div>
                    </div>
                @empty
                    @unless ($psBanners && $psSelected->slug === 'home')
                        <p class="rounded-lg border border-dashed border-zinc-300 p-4 text-center text-sm text-zinc-400">
                            This page has no sections yet.
                            <a href="{{ route('admin.cms', ['pageId' => $psSelected->id]) }}" wire:navigate class="font-medium text-primary hover:underline">Add one in CMS</a>
                        </p>
                    @endunless
                @endforelse
            </div>
        </div>

    @endif
</div>
