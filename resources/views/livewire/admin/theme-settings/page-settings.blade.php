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
                </div>
            </nav>

            @if ($psGroup)
                <div class="min-w-0 space-y-6" wire:key="ps-group-{{ $psGroup['key'] }}">
                    <x-admin-collapse-section icon="magnifying-glass" title="SEO"
                        description="The title and description search engines and social media show for this page."
                        :open="$errors->has('groupSeo.'.$psGroup['key'])">
                        <flux:field>
                            <flux:label>Which SEO should this page use?</flux:label>
                            <flux:select wire:model="groupSeo.{{ $psGroup['key'] }}" wire:key="ps-group-seo-{{ $psGroup['key'] }}">
                                @foreach ($psSeoOptions as $optionId => $optionLabel)
                                    <flux:select.option value="{{ $optionId }}">{{ $optionLabel }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="groupSeo.{{ $psGroup['key'] }}" />
                        </flux:field>
                    </x-admin-collapse-section>

                    <x-admin-collapse-section icon="squares-2x2" title="Builder"
                        description="Show a page made in the Builder instead of this page's own design."
                        :open="$errors->has('groupBuilder.'.$psGroup['key'])">
                        <flux:field>
                            <flux:label>Which builder should this page show?</flux:label>
                            <flux:select wire:model="groupBuilder.{{ $psGroup['key'] }}" wire:key="ps-group-builder-{{ $psGroup['key'] }}">
                                <flux:select.option value="">{{ __('None - use the theme page') }}</flux:select.option>
                                @foreach ($psBuilderOptions as $optionId => $optionLabel)
                                    <flux:select.option value="{{ $optionId }}">{{ $optionLabel }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="groupBuilder.{{ $psGroup['key'] }}" />
                        </flux:field>
                    </x-admin-collapse-section>

                    @if ($psGroupView)
                        @include($psGroupView)
                    @endif
                </div>
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
                    @foreach ($psPages as $psPage)
                        @php $psActive = $psPage->id === $psSelected->id; @endphp
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
                </div>
            </nav>

            {{-- The picked page --}}
            <div class="min-w-0 space-y-6" wire:key="ps-page-{{ $psSelected->id }}">
                {{-- Every block of the page is its own collapsed section: SEO, the
                     theme's banners, then each CMS section. Closed unless something inside
                     it has a validation error, so the message is never hidden. --}}

                {{-- SEO. Pick another page and its SEO (meta, Open Graph, Twitter, robots,
                     canonical) is what search engines and social previews get for this one;
                     leave it empty and the page uses its own SEO, and anything blank there
                     falls back to the site-wide defaults (Admin → SEO). --}}
                <x-admin-collapse-section icon="magnifying-glass" title="SEO"
                    description="The title and description search engines and social media show for this page."
                    :open="$errors->has('seoSources.'.$psSelected->id)">
                    <flux:field>
                        <flux:label>Which SEO should this page use?</flux:label>
                        <flux:select wire:model="seoSources.{{ $psSelected->id }}" wire:key="ps-seo-{{ $psSelected->id }}">
                            @foreach ($psSeoOptions as $optionId => $optionLabel)
                                <flux:select.option value="{{ $optionId }}">{{ $optionLabel }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        <flux:error name="seoSources.{{ $psSelected->id }}" />
                    </flux:field>
                </x-admin-collapse-section>

                {{-- The theme's own built-in home sections (hero slider, promo tiles). --}}
                @if ($psBanners && $psSelected->slug === $psHomeSlug)
                    @include($psBanners, ['heroSlides' => $heroSlides, 'promo' => $promo])
                @endif

                {{-- Sections: one collapse card each, with its items listed inside. --}}
                @foreach ($psSections as $section)
                    @php
                        $constants = array_values($section->constant ?? []);
                        $sectionCards = $cards[$section->id] ?? [];
                        $sectionHasError = collect($errors->keys())->contains(fn ($key) => str_starts_with($key, "cards.{$section->id}.") || str_starts_with($key, "values.{$section->id}."));
                        $summary = count($sectionCards).' '.\Illuminate\Support\Str::plural('item', count($sectionCards))
                            .($constants !== [] ? ' · '.count($constants).' '.\Illuminate\Support\Str::plural('constant', count($constants)) : '');
                    @endphp

                    <x-admin-collapse-section wire:key="ps-section-{{ $section->id }}" icon="rectangle-group" :title="$section->name"
                        :description="$summary" :open="$sectionHasError">
                        @if ($section->status !== 'active')
                            <x-slot:meta>
                                <span class="shrink-0 rounded-full bg-zinc-200 px-2 py-0.5 text-[9px] font-bold uppercase text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">Off</span>
                            </x-slot:meta>
                        @endif

                        <div class="space-y-3">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Content</p>
                                <flux:button size="sm" variant="outline" icon="plus" wire:click="addCard({{ $section->id }})">Add item</flux:button>
                            </div>

                            @forelse ($sectionCards as $i => $card)
                                <div wire:key="ps-card-{{ $section->id }}-{{ $i }}"
                                    class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                                    {{-- Item header: where it sits, what it is, and the order/remove controls. --}}
                                    <div class="flex items-center gap-3 border-b border-zinc-200 bg-zinc-50/70 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800/40">
                                        <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-primary/10 text-[11px] font-bold text-primary">{{ $i + 1 }}</span>
                                        @if (filled($card['image'] ?? null))
                                            <img src="{{ $card['image'] }}" alt="" class="size-7 shrink-0 rounded object-cover ring-1 ring-zinc-200 dark:ring-zinc-700">
                                        @endif
                                        <span class="min-w-0 flex-1 truncate text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ filled($card['title'] ?? null) ? $card['title'] : 'Untitled item' }}</span>

                                        <div class="flex shrink-0 items-center gap-0.5">
                                            <button type="button" wire:click="moveCard({{ $section->id }}, {{ $i }}, 'up')" @disabled($i === 0)
                                                class="rounded-md p-1.5 text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-700 disabled:pointer-events-none disabled:opacity-30 dark:hover:bg-zinc-800" aria-label="Move item up">
                                                <flux:icon.chevron-up variant="mini" class="size-4" />
                                            </button>
                                            <button type="button" wire:click="moveCard({{ $section->id }}, {{ $i }}, 'down')" @disabled($i === count($sectionCards) - 1)
                                                class="rounded-md p-1.5 text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-700 disabled:pointer-events-none disabled:opacity-30 dark:hover:bg-zinc-800" aria-label="Move item down">
                                                <flux:icon.chevron-down variant="mini" class="size-4" />
                                            </button>
                                            <button type="button" wire:click="removeCard({{ $section->id }}, {{ $i }})"
                                                wire:confirm="Remove this item? It is deleted when you save."
                                                class="rounded-md p-1.5 text-zinc-400 transition hover:bg-rose-50 hover:text-rose-500 dark:hover:bg-rose-500/10" aria-label="Remove item">
                                                <flux:icon.trash variant="mini" class="size-4" />
                                            </button>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 gap-5 p-4 xl:grid-cols-[14rem_minmax(0,1fr)]">
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

                                            <p class="border-t border-zinc-100 pt-4 text-[11px] font-semibold uppercase tracking-wider text-zinc-400 dark:border-zinc-800 sm:col-span-2">Button &amp; style</p>

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
                                <button type="button" wire:click="addCard({{ $section->id }})"
                                    class="flex w-full flex-col items-center gap-1.5 rounded-lg border border-dashed border-zinc-300 px-4 py-6 text-zinc-400 transition hover:border-primary hover:bg-primary/5 hover:text-primary dark:border-zinc-600">
                                    <flux:icon.plus-circle class="size-6" />
                                    <span class="text-xs font-semibold">Add the first item</span>
                                </button>
                            @endforelse
                        </div>

                        {{-- Constants: the key is fixed (a template reads it by name),
                             so only the value is editable here. --}}
                        @if ($constants !== [])
                            <div class="space-y-3 border-t border-zinc-100 pt-5 dark:border-zinc-800">
                                <p class="text-[11px] font-semibold uppercase tracking-wider text-zinc-400">Constants</p>
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
                    </x-admin-collapse-section>
                @endforeach
            </div>
        </div>

    @endif
</div>
