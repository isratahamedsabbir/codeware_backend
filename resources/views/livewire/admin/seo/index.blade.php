<div class="w-full min-w-0 max-w-[1600px] space-y-5">
    {{-- ── SEO of every page ──
         One row per page in the `seos` table; a page with none inherits the
         site-wide defaults below. The strip counts what is missing and filters to
         it, the table shows what each page currently tells a search engine, and a
         row opens the same fields the page forms use. --}}
    <x-admin-section-card plain header-border="border-zinc-100" icon="document-magnifying-glass" title="Pages SEO"
        icon-color="bg-sky-500/10 text-sky-600"
        description="The search and social metadata of every page, post, product and category — stored in its own SEO record, one per page.">

        <div class="min-w-0 space-y-4">
            {{-- Summary strip: each chip is also a filter. --}}
            <div class="flex flex-wrap gap-2">
                @foreach ([
                    '' => ['All pages', $counts['total'], 'zinc'],
                    'missing_title' => ['No meta title', $counts['missing_title'], 'amber'],
                    'missing_description' => ['No meta description', $counts['missing_description'], 'amber'],
                    'noindex' => ['Hidden from search', $counts['noindex'], 'rose'],
                ] as $filterKey => [$chipLabel, $chipCount, $tone])
                    <button type="button" wire:click="$set('statusFilter', '{{ $filterKey }}')"
                        @class([
                            'inline-flex items-center gap-2 rounded-full border px-3.5 py-1.5 text-xs font-medium transition',
                            'border-primary bg-primary/10 text-primary' => $statusFilter === $filterKey,
                            'border-zinc-200 text-zinc-600 hover:border-zinc-400 dark:border-zinc-700 dark:text-zinc-300' => $statusFilter !== $filterKey,
                        ])>
                        {{ $chipLabel }}
                        <span @class([
                            'rounded-full px-1.5 py-0.5 font-mono text-[10px] font-semibold',
                            'bg-amber-100 text-amber-700' => $tone === 'amber' && $chipCount > 0,
                            'bg-rose-100 text-rose-700' => $tone === 'rose' && $chipCount > 0,
                            'bg-zinc-100 text-zinc-500 dark:bg-zinc-800' => $chipCount === 0 || $tone === 'zinc',
                        ])>{{ $chipCount }}</span>
                    </button>
                @endforeach
            </div>

            <div class="flex flex-col gap-3 sm:flex-row">
                <div class="flex-1">
                    <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Search by title, address or meta title…" />
                </div>
                <div class="sm:w-56">
                    <select wire:model.live="typeFilter" aria-label="Filter by type"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-700 dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-200">
                        <option value="">All types</option>
                        @foreach ($types as $typeKey => $typeLabel)
                            <option value="{{ $typeKey }}">{{ $typeLabel }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="relative max-w-full min-w-0 overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                    <thead class="bg-zinc-50 text-left text-[11px] font-semibold uppercase tracking-wider text-zinc-500 dark:bg-zinc-800/60">
                        <tr>
                            <th class="px-4 py-2.5">Page</th>
                            <th class="px-4 py-2.5">Meta title</th>
                            <th class="px-4 py-2.5">Meta description</th>
                            <th class="px-4 py-2.5">Robots</th>
                            <th class="px-4 py-2.5 text-right"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-800 dark:bg-zinc-900">
                        @forelse ($pages as $row)
                            @php
                                $metaTitle = (string) $row->getTranslation('seo_title', \App\Support\Locale::primary(), true);
                                $metaDescription = (string) $row->getTranslation('seo_description', \App\Support\Locale::primary(), true);
                                $titleLength = mb_strlen($metaTitle);
                                $descriptionLength = mb_strlen($metaDescription);
                            @endphp
                            <tr wire:key="seo-row-{{ $row->id }}" class="align-top hover:bg-zinc-50/70 dark:hover:bg-zinc-800/40">
                                <td class="max-w-64 px-4 py-3">
                                    <p class="truncate font-medium text-zinc-800 dark:text-zinc-100">{{ $row->getTranslation('title', \App\Support\Locale::primary(), true) ?: 'Untitled' }}</p>
                                    <p class="mt-0.5 flex items-center gap-2 text-xs text-zinc-400">
                                        <span class="rounded bg-zinc-100 px-1.5 py-0.5 text-[10px] font-medium uppercase tracking-wide text-zinc-500 dark:bg-zinc-800">{{ $types[$row->type] ?? $row->type }}</span>
                                        <span class="truncate font-mono">/{{ $row->slug === 'home' ? '' : $row->slug }}</span>
                                    </p>
                                </td>
                                <td class="max-w-72 px-4 py-3">
                                    @if ($metaTitle === '')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-700">Missing</span>
                                    @else
                                        <p class="line-clamp-2 text-zinc-700 dark:text-zinc-200">{{ $metaTitle }}</p>
                                        <p @class(['mt-0.5 font-mono text-[10px]', 'text-emerald-600' => $titleLength <= 60, 'text-amber-600' => $titleLength > 60])>{{ $titleLength }}/60</p>
                                    @endif
                                </td>
                                <td class="max-w-80 px-4 py-3">
                                    @if ($metaDescription === '')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-700">Missing</span>
                                    @else
                                        <p class="line-clamp-2 text-zinc-600 dark:text-zinc-300">{{ $metaDescription }}</p>
                                        <p @class(['mt-0.5 font-mono text-[10px]', 'text-emerald-600' => $descriptionLength <= 160, 'text-amber-600' => $descriptionLength > 160])>{{ $descriptionLength }}/160</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1">
                                        @if ($row->no_index)
                                            <span class="rounded-full bg-rose-50 px-2 py-0.5 text-[11px] font-medium text-rose-700">noindex</span>
                                        @endif
                                        @if ($row->no_follow)
                                            <span class="rounded-full bg-rose-50 px-2 py-0.5 text-[11px] font-medium text-rose-700">nofollow</span>
                                        @endif
                                        @if (! $row->no_index && ! $row->no_follow)
                                            <span class="text-xs text-zinc-400">Indexed</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">
                                    <flux:button size="sm" variant="outline" icon="pencil-square" wire:click="editPage({{ $row->id }})">Edit SEO</flux:button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10 text-center text-sm text-zinc-400">No pages match.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>{{ $pages->links() }}</div>
        </div>
    </x-admin-section-card>

    {{-- ── Edit one page's SEO ── --}}
    @if ($editingPageId)
        <div class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-zinc-900/50 p-3 sm:p-6" role="dialog" aria-modal="true"
            x-data x-on:keydown.escape.window="$wire.closeEdit()">
            <div class="my-auto w-full max-w-4xl rounded-xl border border-zinc-200 bg-white shadow-xl dark:border-zinc-700 dark:bg-zinc-900">
                <div class="flex items-center justify-between gap-3 border-b border-zinc-200 px-5 py-3.5 dark:border-zinc-700">
                    <div class="min-w-0">
                        <h2 class="truncate text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ $editingTitle ?: 'Untitled' }} <span class="font-normal text-zinc-400">· SEO</span></h2>
                        <p class="font-mono text-xs text-zinc-400">{{ $types[$editingType] ?? $editingType }} · /{{ $slug === 'home' ? '' : $slug }}</p>
                    </div>
                    <button type="button" wire:click="closeEdit" aria-label="Close"
                        class="flex size-8 shrink-0 items-center justify-center rounded-lg text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-800 dark:hover:bg-zinc-800">
                        <flux:icon.x-mark class="size-5" />
                    </button>
                </div>

                <div class="p-4 sm:p-5">
                    @include('partials._admin-seo-fields', ['seoCollapsed' => false])
                </div>

                <div class="flex items-center justify-end gap-2.5 rounded-b-xl border-t border-zinc-200 bg-zinc-50 px-5 py-3.5 dark:border-zinc-700 dark:bg-zinc-800/60">
                    <flux:button variant="outline" size="sm" wire:click="closeEdit">Cancel</flux:button>
                    <flux:button variant="primary" size="sm" wire:click="savePageSeo" wire:loading.attr="disabled" wire:target="savePageSeo">
                        <span wire:loading.remove wire:target="savePageSeo">Save SEO</span>
                        <span wire:loading wire:target="savePageSeo">Saving…</span>
                    </flux:button>
                </div>
            </div>
        </div>
    @endif

    <div class="pt-2">
        <h2 class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">Site-wide defaults</h2>
        <p class="text-xs text-zinc-500">What every page without its own SEO copy inherits, plus the domains canonical links are built on.</p>
    </div>

    <div class="space-y-5">

    <x-admin-section-card icon="magnifying-glass" title="Search Engine (SEO) Settings"
        icon-color="bg-sky-500/10 text-sky-600" body-class="px-6 py-5"
        description="What search engines show in results, and what appears when your site is shared on social media."
        collapsible :collapsed="true">

        {{-- These are the fallbacks every page without its own SEO copy inherits,
             and they are read in the reader's language: a Bengali visitor is
             served a Bengali page, so an English meta description underneath it
             is a wasted impression. One strip for the whole section, same as the
             SEO block on the Page form, so a switch moves the meta, Open Graph
             and Twitter copy together instead of one toggle per block. --}}
        <x-admin-locale-tabs>
            @php($locales = \App\Support\Locale::translatable())

            <div class="space-y-5">
                {{-- Meta tags --}}
                @foreach ($locales as $language)
                    <x-admin-locale-panel :code="$language->code" class="space-y-4 min-w-0">
                        <flux:field>
                            @include('partials._seo-char-counter', [
                                'path' => 'settings.seo_meta_title.'.$language->code,
                                'max' => 60,
                                'label' => 'Meta Title',
                                'locale' => $language->code,
                            ])
                            <flux:input wire:model="settings.seo_meta_title.{{ $language->code }}"
                                placeholder="Title shown in search engine results" />
                        </flux:field>
                        <flux:field>
                            @include('partials._seo-char-counter', [
                                'path' => 'settings.seo_meta_description.'.$language->code,
                                'max' => 160,
                                'label' => 'Meta Description',
                                'locale' => $language->code,
                            ])
                            <flux:textarea wire:model="settings.seo_meta_description.{{ $language->code }}" class="h-24"
                                placeholder="Short summary shown in search engine results" />
                        </flux:field>
                    </x-admin-locale-panel>
                @endforeach

                {{-- Open Graph --}}
                <div class="min-w-0">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-7 gap-y-4">
                        @foreach ($locales as $language)
                            <x-admin-locale-panel :code="$language->code" class="space-y-4 min-w-0">
                                <flux:field>
                                    @include('partials._seo-char-counter', [
                                        'path' => 'settings.seo_og_title.'.$language->code,
                                        'max' => 70,
                                        'label' => 'OG Title',
                                        'locale' => $language->code,
                                    ])
                                    <flux:input wire:model="settings.seo_og_title.{{ $language->code }}"
                                        placeholder="Defaults to Meta Title if left blank" />
                                </flux:field>
                                <flux:field>
                                    @include('partials._seo-char-counter', [
                                        'path' => 'settings.seo_og_description.'.$language->code,
                                        'max' => 200,
                                        'label' => 'OG Description',
                                        'locale' => $language->code,
                                    ])
                                    <flux:textarea wire:model="settings.seo_og_description.{{ $language->code }}" class="h-24"
                                        placeholder="Defaults to Meta Description if left blank" />
                                </flux:field>
                            </x-admin-locale-panel>
                        @endforeach
                        <div class="min-w-0">
                            <flux:field>
                                <flux:label>OG Image</flux:label>
                                <flux:text class="-mt-1! mb-1 block text-[11px] text-zinc-400">1200×630px</flux:text>
                                {{-- Not translated: one image has one URL, and pointing the
                                     Bengali card at a different file would just be a second
                                     image to keep uploaded. --}}
                                <x-media-picker model="settings.seo_og_image" label=""
                                    placeholder="Select OG image from library" mimes="jpg,jpeg,png,webp,avif" only-images dropzone />
                            </flux:field>
                        </div>
                    </div>
                </div>

                {{-- Twitter Card --}}
                <div class="min-w-0">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-7 gap-y-4">
                        <div class="space-y-4 min-w-0">
                            {{-- No card type and no @username input: the seeded card
                                 type is already summary_large_image, so a site with
                                 a Twitter image gets the large card without being
                                 told to, and the site handle belongs to the social
                                 account rather than to the SEO copy. Both settings
                                 stay in the database, so save() still round-trips
                                 whatever they were already set to. --}}
                            @foreach ($locales as $language)
                                <x-admin-locale-panel :code="$language->code" class="space-y-4 min-w-0">
                                    <flux:field>
                                        @include('partials._seo-char-counter', [
                                            'path' => 'settings.seo_twitter_title.'.$language->code,
                                            'max' => 70,
                                            'label' => 'Twitter Title',
                                            'locale' => $language->code,
                                        ])
                                        <flux:input wire:model="settings.seo_twitter_title.{{ $language->code }}"
                                            placeholder="Defaults to Meta Title if left blank" />
                                    </flux:field>
                                    <flux:field>
                                        @include('partials._seo-char-counter', [
                                            'path' => 'settings.seo_twitter_description.'.$language->code,
                                            'max' => 200,
                                            'label' => 'Twitter Description',
                                            'locale' => $language->code,
                                        ])
                                        <flux:textarea wire:model="settings.seo_twitter_description.{{ $language->code }}" class="h-24"
                                            placeholder="Defaults to Meta Description if left blank" />
                                    </flux:field>
                                </x-admin-locale-panel>
                            @endforeach
                        </div>
                        <div class="min-w-0">
                            <flux:field>
                                <flux:label>Twitter Image</flux:label>
                                <flux:text class="-mt-1! mb-1 block text-[11px] text-zinc-400">1200×675px</flux:text>
                                <x-media-picker model="settings.seo_twitter_image" label=""
                                    placeholder="Select Twitter image from library" mimes="jpg,jpeg,png,webp,avif" only-images dropzone />
                            </flux:field>
                        </div>
                    </div>
                </div>
            </div>
        </x-admin-locale-tabs>
    </x-admin-section-card>

    </div>

    <x-admin-section-card icon="link" title="Canonical Base Links" icon-color="bg-emerald-500/10 text-emerald-600"
        description="Base URLs used to build canonical link tags. Add one for every domain your site is reachable on.">
        <div class="space-y-3 max-w-2xl">
            @foreach ($canonicalUrls as $index => $url)
                <div class="flex items-center gap-2">
                    <flux:input wire:model="canonicalUrls.{{ $index }}" placeholder="https://example.com"
                        class="flex-1" />
                    <flux:button size="sm" variant="subtle" square icon="trash"
                        wire:click="removeCanonicalUrl({{ $index }})" aria-label="Remove link" />
                </div>
            @endforeach
        </div>
        <flux:button size="sm" variant="ghost" icon="plus" wire:click="addCanonicalUrl">
            Add Link
        </flux:button>
    </x-admin-section-card>

    <div>
        <flux:button variant="primary" size="sm" wire:click="save" wire:loading.attr="disabled">
            Save Settings
        </flux:button>
    </div>

    <livewire:admin.media-library.picker-modal key="seo-picker-modal" />
</div>
