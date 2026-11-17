<div>{{-- Livewire root. wire:ignore below: GrapesJS owns that whole subtree, a morph would wipe it. --}}
<div class="pb-root flex h-screen flex-col bg-white text-slate-800" wire:ignore id="page-builder"
     x-data="pageBuilder(@js(['project' => $project, 'blocks' => $blocks, 'assets' => $assets, 'locale' => $locale, 'pageTitle' => $page->getTranslation('title', $locale, false) ?: 'Untitled page', 'seo' => $seo, 'canonicalBases' => $canonicalBases, 'uploadUrl' => route('admin.pages.builder.assets'), 'csrf' => csrf_token()]))">

    {{-- ── Top bar ───────────────────────────────────────────── --}}
    <header class="pb-top">
        <div class="flex min-w-0 items-center gap-3">
            <button type="button" class="pb-icon" :class="{ 'pb-icon-on': showLeft }" title="Toggle components" @click="togglePanel('left')">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M9 4v16"/></svg>
            </button>
            <div class="pb-logo" x-text="pageTitle.slice(0, 2).toUpperCase()">{{ mb_strtoupper(mb_substr($page->getTranslation('title', $locale, false) ?: 'P', 0, 2)) }}</div>
            <div class="min-w-0 leading-tight">
                <div class="truncate text-[15px] font-semibold" x-text="pageTitle">{{ $page->getTranslation('title', $locale, false) ?: 'Untitled page' }}</div>
                <div class="flex items-center gap-1.5 text-xs text-slate-500">
                    <span class="inline-block h-2 w-2 rounded-full {{ $page->status === 'active' ? 'bg-emerald-500' : 'bg-amber-400' }}"></span>
                    {{ $page->status === 'active' ? 'Published' : 'Draft' }}
                    <span x-show="status" class="ml-2 text-slate-400" x-text="status"></span>
                </div>
            </div>
            <select class="pb-language" aria-label="Page content language" :value="locale" @change="switchLocale($event.target.value, $event.target)">
                @foreach ($locales as $language)
                    <option value="{{ $language['code'] }}">{{ $language['label'] }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex items-center gap-3">
            <div class="pb-pill">
                <button type="button" :disabled="!history.undo" title="Undo" @click="undo()">
                    <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 14 4 9l5-5"/><path d="M4 9h10a6 6 0 0 1 0 12h-3"/></svg>
                </button>
                <button type="button" :disabled="!history.redo" title="Redo" @click="redo()">
                    <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m15 14 5-5-5-5"/><path d="M20 9H10a6 6 0 0 0 0 12h3"/></svg>
                </button>
            </div>
            <div class="pb-pill pb-seg">
                <button type="button" :class="{ 'pb-seg-on': !previewing }" @click="setPreview(false)">
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg> Edit
                </button>
                <button type="button" :class="{ 'pb-seg-on': previewing }" @click="setPreview(true)">
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg> Preview
                </button>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" class="pb-icon" :class="{ 'pb-icon-on': showRight }" title="Toggle properties" @click="togglePanel('right')">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M15 4v16"/></svg>
            </button>
            <a href="{{ route('admin.pages') }}" class="pb-exit">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M19 12H5M12 19l-7-7 7-7"/></svg> Exit
            </a>
            <button type="button" class="pb-save" :disabled="saving || seoSaving" @click="saveAll()">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg> Save
            </button>
        </div>
    </header>

    <div class="flex min-h-0 flex-1">
        {{-- ── Left: components / outline ─────────────────────── --}}
        <aside class="pb-side border-r" x-show="showLeft && !previewing">
            <div class="pb-side-head">
                <button type="button" :class="{ 'pb-head-on': left === 'blocks' }" @click="left = 'blocks'">Components</button>
                <button type="button" :class="{ 'pb-head-on': left === 'layers' }" @click="left = 'layers'">Outline</button>
            </div>
            <div class="min-h-0 flex-1 overflow-y-auto" x-show="left === 'blocks'">
                <div class="p-4 pb-2">
                    <div class="pb-search-wrap">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                        <input type="search" x-model.debounce.150ms="search" placeholder="Search blocks…">
                    </div>
                    <p class="mt-3 text-xs text-slate-400">Drag a block onto the page</p>
                </div>
                <div x-ref="blocks"></div>
            </div>
            <div class="min-h-0 flex-1 overflow-y-auto" x-show="left === 'layers'"><div x-ref="layers"></div></div>
        </aside>

        {{-- ── Centre: device bar + canvas ────────────────────── --}}
        <main class="flex min-w-0 flex-1 flex-col bg-slate-100">
            <div class="pb-devbar" x-show="!previewing">
                <div class="pb-devbar-in">
                    <button type="button" :class="{ 'pb-dev-on': device === 'mobile' }" title="Mobile" @click="setDevice('mobile')">
                        <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="7" y="3" width="10" height="18" rx="2"/><path d="M11 18h2"/></svg>
                    </button>
                    <button type="button" :class="{ 'pb-dev-on': device === 'tablet' }" title="Tablet" @click="setDevice('tablet')">
                        <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="3" width="14" height="18" rx="2"/><path d="M11 18h2"/></svg>
                    </button>
                    <button type="button" :class="{ 'pb-dev-on': device === 'desktop' }" title="Desktop" @click="setDevice('desktop')">
                        <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/></svg>
                    </button>
                    <span class="pb-sep"></span>
                    <button type="button" title="Zoom out" @click="setZoom(zoom - 10)">
                        <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3M8 11h6"/></svg>
                    </button>
                    <button type="button" title="Zoom in" @click="setZoom(zoom + 10)">
                        <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3M8 11h6M11 8v6"/></svg>
                    </button>
                    <button type="button" class="pb-zoom" title="Reset zoom" @click="setZoom(100)" x-text="zoom + '%'"></button>
                    <span class="pb-sep"></span>
                    <button type="button" title="Show element outlines" @click="toggleOutline()">
                        <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8" stroke-dasharray="3 2"><rect x="4" y="4" width="16" height="16" rx="2"/></svg>
                    </button>
                    <button type="button" title="View code" @click="showCode()">
                        <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m8 7-5 5 5 5M16 7l5 5-5 5"/></svg>
                    </button>
                    <button type="button" title="Clear the page" @click="clearAll()">
                        <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>
                    </button>
                </div>
            </div>
            <div class="min-h-0 flex-1" x-ref="gjs"></div>
        </main>

        {{-- ── Right: page / element properties ───────────────── --}}
        <aside class="pb-side border-l" x-show="showRight && !previewing">
            <div class="pb-side-head">
                <span class="px-5 py-3 text-[11px] font-bold uppercase tracking-wider text-slate-500" x-text="hasSelection ? selectedName || 'Element' : 'Page'"></span>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto">
                <div x-show="!hasSelection" class="p-4">
                    <div class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <div class="pb-gear">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M4.2 4.2l2.1 2.1M17.7 17.7l2.1 2.1M2 12h3M19 12h3M4.2 19.8l2.1-2.1M17.7 6.3l2.1-2.1"/></svg>
                        </div>
                        <div class="min-w-0">
                            <div class="truncate font-semibold" x-text="pageTitle">{{ $page->getTranslation('title', $locale, false) ?: 'Page' }}</div>
                            <div class="text-xs text-slate-500">Select a block on the canvas to edit it</div>
                        </div>
                    </div>
                    <a href="{{ route('admin.pages.edit', $page->id) }}" target="_blank" class="mt-3 block text-sm text-blue-600 hover:underline">Edit title, slug &amp; SEO →</a>

                    <details open class="mt-4 overflow-hidden rounded-xl border border-slate-200 bg-white">
                        <summary class="cursor-pointer border-b border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700">Search Engine (SEO)</summary>
                        <div class="space-y-4 p-4">
                            <p class="text-xs leading-relaxed text-slate-500">Text fields are saved for the selected language.</p>
                            @if ($page->seo_page_id)
                                <p class="rounded-lg bg-amber-50 p-3 text-xs leading-relaxed text-amber-800">Public SEO is borrowed from another page. These fields edit this page’s own SEO.</p>
                            @endif

                            <label class="block space-y-1">
                                <span class="text-xs font-medium text-slate-600">Meta title</span>
                                <input type="text" maxlength="255" x-model="seo.seo_title" class="pb-seo-input">
                            </label>
                            <label class="block space-y-1">
                                <span class="text-xs font-medium text-slate-600">Meta description</span>
                                <textarea rows="3" x-model="seo.seo_description" class="pb-seo-input"></textarea>
                            </label>

                            <div class="border-t border-slate-100 pt-4">
                                <h3 class="mb-3 text-xs font-bold uppercase tracking-wide text-slate-500">Canonical URL</h3>
                                <label class="mb-3 block space-y-1">
                                    <span class="text-xs font-medium text-slate-600">Base URL</span>
                                    <select x-model="seo.canonical_base" class="pb-seo-input">
                                        <option value="">Select base…</option>
                                        @foreach ($canonicalBases as $base)
                                            <option value="{{ $base }}">{{ $base }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label class="block space-y-1">
                                    <span class="text-xs font-medium text-slate-600">Canonical path</span>
                                    <input type="text" maxlength="255" x-model="seo.canonical_slug" class="pb-seo-input">
                                </label>
                            </div>

                            <div class="border-t border-slate-100 pt-4">
                                <h3 class="mb-3 text-xs font-bold uppercase tracking-wide text-slate-500">Open Graph</h3>
                                <label class="mb-3 block space-y-1">
                                    <span class="text-xs font-medium text-slate-600">OG title</span>
                                    <input type="text" maxlength="255" x-model="seo.og_title" class="pb-seo-input">
                                </label>
                                <label class="mb-3 block space-y-1">
                                    <span class="text-xs font-medium text-slate-600">OG description</span>
                                    <textarea rows="3" x-model="seo.og_description" class="pb-seo-input"></textarea>
                                </label>
                                <div class="space-y-1">
                                    <span class="text-xs font-medium text-slate-600">OG image</span>
                                    <template x-if="seo.og_image">
                                        <img :src="seo.og_image" alt="" class="h-32 w-full rounded-lg border border-slate-200 bg-slate-50 object-cover">
                                    </template>
                                    <input type="text" maxlength="255" x-model="seo.og_image" placeholder="Choose from the library or paste a URL" class="pb-seo-input">
                                    <div class="flex items-center gap-2">
                                        <button type="button" class="pb-seo-pick" @click="pickSeoImage('og_image')">
                                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="1.6"/><path d="m21 16-5-5-9 9"/></svg>
                                            Choose from library
                                        </button>
                                        <button type="button" class="pb-seo-clear" x-show="seo.og_image" @click="seo.og_image = ''">Remove</button>
                                    </div>
                                </div>
                            </div>

                            <div class="border-t border-slate-100 pt-4">
                                <h3 class="mb-3 text-xs font-bold uppercase tracking-wide text-slate-500">Twitter Card</h3>
                                <label class="mb-3 block space-y-1">
                                    <span class="text-xs font-medium text-slate-600">Twitter title</span>
                                    <input type="text" maxlength="255" x-model="seo.twitter_title" class="pb-seo-input">
                                </label>
                                <label class="mb-3 block space-y-1">
                                    <span class="text-xs font-medium text-slate-600">Twitter description</span>
                                    <textarea rows="3" x-model="seo.twitter_description" class="pb-seo-input"></textarea>
                                </label>
                                <div class="space-y-1">
                                    <span class="text-xs font-medium text-slate-600">Twitter image</span>
                                    <template x-if="seo.twitter_image">
                                        <img :src="seo.twitter_image" alt="" class="h-32 w-full rounded-lg border border-slate-200 bg-slate-50 object-cover">
                                    </template>
                                    <input type="text" maxlength="255" x-model="seo.twitter_image" placeholder="Choose from the library or paste a URL" class="pb-seo-input">
                                    <div class="flex items-center gap-2">
                                        <button type="button" class="pb-seo-pick" @click="pickSeoImage('twitter_image')">
                                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="1.6"/><path d="m21 16-5-5-9 9"/></svg>
                                            Choose from library
                                        </button>
                                        <button type="button" class="pb-seo-clear" x-show="seo.twitter_image" @click="seo.twitter_image = ''">Remove</button>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-3 border-t border-slate-100 pt-4">
                                <label class="flex items-center justify-between text-sm text-slate-700">
                                    <span>No index</span>
                                    <input type="checkbox" x-model="seo.no_index" class="size-4 rounded border-slate-300 text-blue-600">
                                </label>
                                <label class="flex items-center justify-between text-sm text-slate-700">
                                    <span>No follow</span>
                                    <input type="checkbox" x-model="seo.no_follow" class="size-4 rounded border-slate-300 text-blue-600">
                                </label>
                            </div>

                            <div class="flex items-center justify-between gap-3 border-t border-slate-100 pt-4">
                                <span x-show="seoStatus" class="text-xs text-slate-500" x-text="seoStatus"></span>
                                <button type="button" class="pb-seo-save" :disabled="seoSaving" @click="saveSeo()">
                                    <span x-text="seoSaving ? 'Saving…' : 'Save SEO'"></span>
                                </button>
                            </div>
                        </div>
                    </details>
                </div>

                <div x-show="hasSelection">
                    <div class="pb-subtabs">
                        <button type="button" :class="{ 'pb-head-on': right === 'style' }" @click="right = 'style'">Style</button>
                        <button type="button" :class="{ 'pb-head-on': right === 'traits' }" @click="right = 'traits'">Settings</button>
                        <button type="button" class="pb-delete-element" title="Delete selected element" @click="deleteSelected()">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>
                            Delete
                        </button>
                    </div>
                    <div x-show="right === 'style'">
                        <div x-ref="styles"></div>
                    </div>
                    <div x-show="right === 'traits'"><div x-ref="traits"></div></div>
                </div>
            </div>
        </aside>
    </div>

    <style>
        .pb-top{display:flex;align-items:center;justify-content:space-between;gap:12px;height:64px;padding:0 16px;border-bottom:1px solid #e2e8f0;background:#fff}
        .pb-icon{display:inline-flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:10px;color:#475569;border:1px solid transparent}
        .pb-icon:hover{background:#f1f5f9}.pb-icon-on{background:#eff6ff;color:#2563eb;border-color:#dbeafe}
        .pb-logo{width:40px;height:40px;border-radius:12px;background:#1e3a8a;color:#fff;font-weight:800;display:flex;align-items:center;justify-content:center;font-size:15px;box-shadow:0 0 0 3px #e0e7ff}
        .pb-language{max-width:120px;padding:8px 10px;border:1px solid #e2e8f0;border-radius:9px;background:#fff;color:#334155;font-size:13px;font-weight:600}
        .pb-pill{display:flex;align-items:center;gap:2px;padding:4px;border-radius:12px;background:#f8fafc;border:1px solid #e2e8f0}
        .pb-pill button{display:inline-flex;align-items:center;gap:6px;padding:7px 12px;border-radius:9px;font-size:13px;font-weight:600;color:#64748b}
        .pb-pill button:disabled{opacity:.35}
        .pb-pill button:hover:not(:disabled){color:#0f172a}
        .pb-seg-on{background:#fff;color:#0f172a!important;box-shadow:0 1px 3px rgba(15,23,42,.12)}
        .pb-exit{display:inline-flex;align-items:center;gap:7px;padding:9px 16px;border-radius:10px;border:1px solid #e2e8f0;font-size:13px;font-weight:600;color:#334155}
        .pb-exit:hover{background:#f8fafc}
        .pb-save{display:inline-flex;align-items:center;gap:8px;padding:10px 22px;border-radius:10px;background:#1d4ed8;color:#fff;font-weight:700;font-size:14px;box-shadow:0 4px 14px -4px rgba(29,78,216,.6)}
        .pb-save:hover{background:#1e40af}.pb-save:disabled{opacity:.6}

        .pb-side{display:flex;flex-direction:column;width:340px;flex-shrink:0;background:#fff;border-color:#e2e8f0}
        .pb-side-head{display:flex;border-bottom:1px solid #e2e8f0}
        .pb-side-head button{padding:14px 20px;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#94a3b8;border-bottom:2px solid transparent}
        .pb-head-on{color:#0f172a!important;border-color:#2563eb!important}
        .pb-subtabs{display:flex;align-items:center;border-bottom:1px solid #e2e8f0}
        .pb-subtabs button{flex:1;padding:10px 0;font-size:12px;font-weight:700;color:#94a3b8;border-bottom:2px solid transparent}
        .pb-subtabs .pb-delete-element{display:inline-flex;flex:0 0 auto;align-items:center;gap:5px;padding:8px 10px;border:0;border-radius:7px;color:#dc2626;font-size:12px}
        .pb-subtabs .pb-delete-element:hover{background:#fef2f2}
        .pb-gear{width:40px;height:40px;border-radius:10px;background:#e2e8f0;color:#475569;display:flex;align-items:center;justify-content:center;flex-shrink:0}
        .pb-search-wrap{display:flex;align-items:center;gap:8px;background:#f1f5f9;border-radius:10px;padding:0 12px;color:#94a3b8}
        .pb-search-wrap input{flex:1;border:0;background:transparent;padding:10px 0;font-size:14px;outline:none;color:#0f172a}
        .pb-search-wrap input::-webkit-search-cancel-button{display:none}
        .pb-seo-input{width:100%;border:1px solid #cbd5e1;border-radius:8px;background:#fff;padding:8px 10px;color:#0f172a;font-size:13px;outline:none}
        .pb-seo-input:focus{border-color:#60a5fa;box-shadow:0 0 0 3px rgba(96,165,250,.2)}
        .pb-seo-pick{display:inline-flex;align-items:center;gap:6px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;padding:6px 10px;color:#334155;font-size:12px;font-weight:600}
        .pb-seo-pick:hover{background:#f1f5f9}
        .pb-seo-clear{font-size:12px;font-weight:600;color:#dc2626}
        .pb-seo-save{border-radius:8px;background:#1d4ed8;padding:8px 12px;color:#fff;font-size:12px;font-weight:700}
        .pb-seo-save:hover{background:#1e40af}.pb-seo-save:disabled{opacity:.6}

        .pb-devbar{padding:12px 24px}
        .pb-devbar-in{display:flex;align-items:center;justify-content:center;gap:4px;max-width:900px;margin:0 auto;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:6px 10px}
        .pb-devbar-in button{display:inline-flex;align-items:center;justify-content:center;min-width:34px;height:32px;border-radius:8px;color:#64748b}
        .pb-devbar-in button:hover{background:#f1f5f9}
        .pb-dev-on{color:#2563eb!important;background:#eff6ff}
        .pb-zoom{font-size:12px;font-weight:600;padding:0 8px}
        .pb-sep{width:1px;height:20px;background:#e2e8f0;margin:0 6px}

        /* Light skin for GrapesJS's own widgets (its defaults are dark). */
        .pb-root .gjs-one-bg{background-color:#fff!important}
        .pb-root .gjs-two-color{color:#334155!important}
        .pb-root .gjs-three-bg{background-color:#2563eb!important;color:#fff!important}
        .pb-root .gjs-four-color,.pb-root .gjs-four-color-h:hover{color:#2563eb!important}

        /* Component cards: icon · name + description · drag dots */
        .pb-root .gjs-block-categories{display:block}
        .pb-root .gjs-block-category{border:0}
        .pb-root .gjs-block-category .gjs-title{background:#fff;border:0;border-bottom:0;color:#64748b;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;padding:18px 20px 8px}
        .pb-root .gjs-blocks-c{display:block;padding:4px 16px 8px}
        .pb-root .gjs-block{position:relative;display:flex;flex-direction:row;align-items:center;gap:12px;width:100%;min-height:0;margin:0 0 10px;padding:10px 34px 10px 12px;border:1px solid #e2e8f0;border-radius:12px;background:#fff;box-shadow:0 1px 2px rgba(15,23,42,.04);color:#334155;text-align:left;transition:border-color .12s,box-shadow .12s}
        .pb-root .gjs-block:hover{border-color:#93c5fd;box-shadow:0 4px 14px -6px rgba(37,99,235,.35);color:#334155}
        .pb-root .gjs-block::after{content:"⋮⋮";position:absolute;right:12px;top:50%;transform:translateY(-50%);color:#cbd5e1;letter-spacing:-2px;font-size:14px}
        .pb-root .gjs-block__media{flex:0 0 40px;width:40px;height:40px;margin:0;border-radius:10px;background:#eff6ff;color:#2563eb;display:flex;align-items:center;justify-content:center}
        .pb-root .gjs-block__media svg{width:22px;height:22px;fill:none!important;stroke:currentColor}
        .pb-root .gjs-block-label{display:flex;flex-direction:column;align-items:flex-start;flex:1;min-width:0;font-size:14px;line-height:1.25;text-align:left;padding:0}
        .pb-root .gjs-block-label b{font-weight:600;color:#0f172a;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        .pb-root .gjs-block-label small{font-size:12px;color:#64748b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        .pb-root .pb-del{position:absolute;z-index:1;right:30px;top:50%;transform:translateY(-50%);width:22px;height:22px;border-radius:6px;color:#94a3b8;font-size:18px;line-height:1;display:flex;align-items:center;justify-content:center;pointer-events:auto}
        .pb-root .pb-del:hover{background:#fee2e2;color:#dc2626}

        .pb-root .gjs-sm-sector-title,.pb-root .gjs-layer-title{background:#fff!important;border-bottom:1px solid #e2e8f0!important;color:#0f172a!important;font-size:12px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;padding:12px 16px}
        .pb-root .gjs-sm-sector-title{display:flex;align-items:center}
        .pb-root .gjs-clm-tags{padding:6px 4px}
        .pb-root .gjs-field{background:#f1f5f9;color:#0f172a;border-radius:8px}
        .pb-root .gjs-field input,.pb-root .gjs-field select{color:#0f172a}
        .pb-root .gjs-layer{color:#334155}
        .pb-root .gjs-layer.gjs-selected .gjs-layer-title{background:#dbeafe}
        .pb-root .gjs-trt-trait{padding:8px 14px}

        /* The page sits on the grey stage as a rounded card. */
        .pb-root .gjs-editor-cont{background:#f1f5f9}
        .pb-root .gjs-cv-canvas{background:transparent;width:calc(100% - 48px);height:calc(100% - 24px);top:12px;left:24px}
        .pb-root .gjs-frame-wrapper{background:#fff;border-radius:12px;box-shadow:0 10px 30px -12px rgba(15,23,42,.25);overflow:hidden}
        .pb-root .gjs-toolbar{background-color:#2563eb;border-radius:8px}
        .pb-root .gjs-badge{background-color:#2563eb;color:#fff;border-radius:4px}
    </style>
</div>
    <livewire:admin.media-library.picker-modal key="builder-picker-modal" />
</div>
