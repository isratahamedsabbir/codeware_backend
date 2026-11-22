<div>{{-- Livewire root. wire:ignore below: GrapesJS owns that whole subtree, a morph would wipe it. --}}
<div class="pb-root flex h-screen flex-col bg-white text-slate-800" wire:ignore id="page-builder"
     x-data="pageBuilder(@js(['project' => $project, 'blocks' => $blocks, 'assets' => $assets, 'dynamicBlocks' => $dynamicBlocks, 'locale' => $locale, 'languages' => $locales, 'pageTitle' => $design->name ?: 'Untitled', 'uploadUrl' => route('admin.builders.assets'), 'csrf' => csrf_token()]))">

    {{-- Shown until GrapesJS has mounted, so the editor never flashes half-built. --}}
    <div class="pb-boot" x-show="!ready" x-transition.opacity.duration.300ms>
        <div class="pb-spin"></div><span>Loading editor…</span>
    </div>

    {{-- ── Top bar ───────────────────────────────────────────── --}}
    <header class="pb-top">
        <div class="flex min-w-0 items-center gap-3">
            <div class="pb-logo" x-text="pageTitle.slice(0, 2).toUpperCase()">{{ mb_strtoupper(mb_substr($design->name ?: 'B', 0, 2)) }}</div>
            <div class="min-w-0 leading-tight">
                <div class="truncate text-[13px] font-semibold" x-text="pageTitle">{{ $design->name ?: 'Untitled' }}</div>
                <div class="flex items-center gap-1.5 text-xs text-slate-500">
                    <span x-show="status" class="ml-2 text-slate-400" x-text="status"></span>
                </div>
            </div>
            <div class="pb-pill pb-seg" x-show="langs.length > 1" title="Language you are editing">
                <template x-for="l in langs" :key="l.code">
                    <button type="button" :class="{ 'pb-seg-on': lang === l.code }" @click="switchLanguage(l.code)" x-text="l.code.toUpperCase()"></button>
                </template>
            </div>
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
            <a href="{{ route('admin.builders') }}" class="pb-exit">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M19 12H5M12 19l-7-7 7-7"/></svg> Exit
            </a>
            <button type="button" class="pb-save" :disabled="saving" @click="save()">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2Z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg> Save
            </button>
        </div>
    </header>

    <div class="flex min-h-0 flex-1">
        {{-- ── Left: components / outline ─────────────────────── --}}
        <aside class="pb-side border-r" x-show="showLeft && !previewing">
            <div class="pb-side-head pb-tabs-2">
                <button type="button" :class="{ 'pb-head-on': left === 'blocks' }" @click="left = 'blocks'">Components</button>
                <button type="button" :class="{ 'pb-head-on': left === 'layers' }" @click="left = 'layers'">Outline</button>
            </div>
            <div class="min-h-0 flex-1 overflow-y-auto" x-show="left === 'blocks'">
                <div class="p-4 pb-2">
                    <div class="pb-search-wrap">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                        <input type="search" x-model.debounce.150ms="search" placeholder="Search blocks…">
                    </div>
                </div>
                <div x-ref="blocks"></div>
            </div>
            <div class="min-h-0 flex-1 overflow-y-auto" x-show="left === 'layers'"><div x-ref="layers"></div></div>
        </aside>

        {{-- ── Centre: device bar + canvas ────────────────────── --}}
        <main class="flex min-w-0 flex-1 flex-col bg-slate-100">
            <div class="pb-devbar" x-show="!previewing">
                <button type="button" class="pb-icon" :class="{ 'pb-icon-on': showLeft }" title="Toggle components" @click="togglePanel('left')">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M9 4v16"/></svg>
                </button>
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
                <button type="button" class="pb-icon" :class="{ 'pb-icon-on': showRight }" title="Toggle properties" @click="togglePanel('right')">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M15 4v16"/></svg>
                </button>
            </div>
            <div class="min-h-0 flex-1" x-ref="gjs"></div>
        </main>

        {{-- ── Right: page / element properties ───────────────── --}}
        <aside class="pb-side border-l" x-show="showRight && !previewing" x-transition.opacity.duration.150ms>
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
                            <div class="truncate font-semibold" x-text="pageTitle">{{ $design->name ?: 'Builder' }}</div>
                            <div class="text-xs text-slate-500">Select a block on the canvas to edit it</div>
                        </div>
                    </div>

                </div>

                <div x-show="hasSelection">
                    <div class="pb-subtabs">
                        <button type="button" :class="{ 'pb-head-on': right === 'style' }" @click="right = 'style'">Style</button>
                        <button type="button" x-show="!isPage" :class="{ 'pb-head-on': right === 'traits' }" @click="right = 'traits'">Settings</button>
                        <button type="button" x-show="!isPage" class="pb-delete-element" title="Delete selected element" @click="deleteSelected()">
                            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>
                            Delete
                        </button>
                    </div>
                    <div x-show="right === 'style'">
                        <div x-ref="styles"></div>
                    </div>
                    <div x-show="right === 'traits' && !isPage"><div x-ref="traits"></div></div>
                </div>
            </div>
        </aside>
    </div>

    <style>
        .pb-top{display:flex;align-items:center;justify-content:space-between;gap:12px;height:48px;padding:0 12px;border-bottom:1px solid #e2e8f0;background:#fff}
        .pb-icon{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:8px;color:#475569;border:1px solid transparent}
        .pb-icon:hover{background:#f1f5f9}.pb-icon-on{background:#eff6ff;color:#2563eb;border-color:#dbeafe}
        .pb-logo{width:30px;height:30px;border-radius:9px;background:#1e3a8a;color:#fff;font-weight:800;display:flex;align-items:center;justify-content:center;font-size:12px;box-shadow:0 0 0 2px #e0e7ff}
        .gjs-toolbar .pb-lang-btn{display:inline-flex;align-items:center;justify-content:center;width:auto!important;min-width:28px;height:26px;box-sizing:border-box;vertical-align:top;align-self:center;line-height:1;padding:0 6px;margin:0;font-size:11px;font-weight:700;letter-spacing:.04em}
        .pb-top svg{width:15px;height:15px}
        .pb-pill{display:flex;align-items:center;gap:2px;padding:3px;border-radius:10px;background:#f8fafc;border:1px solid #e2e8f0}
        .pb-pill button{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:7px;font-size:12px;font-weight:600;color:#64748b}
        .pb-pill button:disabled{opacity:.35}
        .pb-pill button:hover:not(:disabled){color:#0f172a}
        .pb-seg-on{background:#fff;color:#0f172a!important;box-shadow:0 1px 3px rgba(15,23,42,.12)}
        .pb-exit{display:inline-flex;align-items:center;gap:6px;height:32px;padding:0 12px;border-radius:8px;border:1px solid #fecaca;background:#fef2f2;font-size:12px;font-weight:600;color:#dc2626}
        .pb-exit:hover{background:#dc2626;border-color:#dc2626;color:#fff}
        .pb-save{display:inline-flex;align-items:center;gap:6px;height:32px;padding:0 16px;border-radius:8px;background:#1d4ed8;color:#fff;font-weight:700;font-size:12px;box-shadow:0 4px 14px -4px rgba(29,78,216,.6)}
        .pb-save:hover{background:#1e40af}.pb-save:disabled{opacity:.6}

        .pb-side{display:flex;flex-direction:column;width:340px;flex-shrink:0;background:#fff;border-color:#e2e8f0}
        .pb-side-head{display:flex;border-bottom:1px solid #e2e8f0}
        .pb-side-head button{padding:14px 20px;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#94a3b8;border-bottom:2px solid transparent}
        .pb-head-on{color:#0f172a!important;border-color:#2563eb!important}
        .pb-side-head.pb-tabs-2 button{flex:1 1 0;text-align:center}
        .pb-side-head.pb-tabs-2 button:hover:not(.pb-head-on){color:#334155;background:#f8fafc}
        .pb-subtabs{display:flex;align-items:center;border-bottom:1px solid #e2e8f0}
        .pb-subtabs button{flex:1;padding:10px 0;font-size:12px;font-weight:700;color:#94a3b8;border-bottom:2px solid transparent}
        .pb-subtabs .pb-delete-element{display:inline-flex;flex:0 0 auto;align-items:center;gap:5px;padding:8px 10px;border:0;border-radius:7px;color:#dc2626;font-size:12px}
        .pb-subtabs .pb-delete-element:hover{background:#fef2f2}
        .pb-gear{width:40px;height:40px;border-radius:10px;background:#e2e8f0;color:#475569;display:flex;align-items:center;justify-content:center;flex-shrink:0}
        .pb-search-wrap{display:flex;align-items:center;gap:8px;background:#f1f5f9;border-radius:10px;padding:0 12px;color:#94a3b8}
        .pb-search-wrap input{flex:1;border:0;background:transparent;padding:10px 0;font-size:14px;outline:none;color:#0f172a}
        .pb-search-wrap input::-webkit-search-cancel-button{display:none}

        .pb-devbar{display:flex;align-items:center;gap:12px;padding:12px 16px}
        .pb-devbar-in{display:flex;align-items:center;justify-content:center;gap:4px;flex:1;min-width:0;max-width:900px;margin:0 auto;background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:6px 10px}
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
        .pb-root .gjs-block-categories{display:block;padding:4px 16px 16px}
        .pb-root .gjs-block-category{margin:0 0 8px;border:1px solid #e2e8f0;border-radius:12px;background:#fff;overflow:hidden;transition:border-color .12s,box-shadow .12s}
        .pb-root .gjs-block-category:hover{border-color:#cbd5e1}
        .pb-root .gjs-block-category.gjs-open{border-color:#bfdbfe;box-shadow:0 4px 14px -8px rgba(37,99,235,.35)}
        .pb-root .gjs-block-category .gjs-title{display:flex;align-items:center;gap:10px;background:#fff;border:0;color:#334155;font-size:12px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;padding:13px 14px;cursor:pointer;user-select:none;transition:background .12s,color .12s}
        .pb-root .gjs-block-category .gjs-title:hover{background:#f8fafc;color:#0f172a}
        .pb-root .gjs-block-category.gjs-open .gjs-title{background:#eff6ff;color:#1d4ed8;border-bottom:1px solid #dbeafe}
        .pb-root .gjs-block-category .gjs-caret-icon{display:none}
        .pb-root .gjs-block-category .gjs-title::before{content:"";flex:none;width:6px;height:6px;border-radius:50%;background:#cbd5e1}
        .pb-root .gjs-block-category.gjs-open .gjs-title::before{background:#2563eb}
        .pb-root .gjs-block-category .gjs-title::after{content:"";flex:none;margin-left:auto;width:7px;height:7px;border-right:2px solid #94a3b8;border-bottom:2px solid #94a3b8;transform:rotate(-45deg);transition:transform .15s}
        .pb-root .gjs-block-category.gjs-open .gjs-title::after{transform:rotate(45deg);border-color:#2563eb}
        .pb-root .gjs-blocks-c{display:block;padding:10px 10px 2px}
        .pb-root .gjs-block{position:relative;display:flex;flex-direction:row;align-items:center;gap:12px;width:100%;min-height:0;margin:0 0 10px;padding:10px 34px 10px 12px;border:1px solid #e2e8f0;border-radius:12px;background:#fff;box-shadow:0 1px 2px rgba(15,23,42,.04);color:#334155;text-align:left;transition:border-color .12s,box-shadow .12s}
        .pb-root .gjs-block:hover{border-color:#93c5fd;box-shadow:0 4px 14px -6px rgba(37,99,235,.35);color:#334155}
        .pb-root .gjs-block::after{content:"⋮⋮";position:absolute;right:12px;top:50%;transform:translateY(-50%);color:#cbd5e1;letter-spacing:-2px;font-size:14px}
        .pb-root .gjs-block__media{flex:0 0 40px;width:40px;height:40px;margin:0;border-radius:10px;background:#eff6ff;color:#2563eb;display:flex;align-items:center;justify-content:center}
        .pb-root .gjs-block__media svg{width:22px;height:22px;fill:none!important;stroke:currentColor}
        .pb-root .gjs-block-label{display:flex;flex-direction:column;align-items:flex-start;flex:1;min-width:0;font-size:14px;line-height:1.25;text-align:left;padding:0}
        .pb-root .gjs-block-label b{font-weight:600;color:#0f172a;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        .pb-root .gjs-block-label small{font-size:12px;color:#64748b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
        .pb-root .pb-more{position:absolute;z-index:1;right:6px;top:50%;transform:translateY(-50%);width:24px;height:28px;border-radius:6px;color:#94a3b8;display:flex;align-items:center;justify-content:center;pointer-events:auto}
        .pb-root .pb-more:hover{background:#f1f5f9;color:#334155}
        .pb-root .gjs-block:has(.pb-more)::after{display:none}
        .pb-root .gjs-block:has(.pb-more){padding-right:36px}
        .pb-menu{position:fixed;z-index:10000;min-width:150px;padding:4px;border:1px solid #e2e8f0;border-radius:10px;background:#fff;box-shadow:0 12px 28px -8px rgba(15,23,42,.25)}
        .pb-menu button{display:flex;align-items:center;gap:8px;width:100%;padding:7px 10px;border-radius:7px;font-size:12px;font-weight:600;color:#334155;text-align:left}
        .pb-menu button:hover{background:#f1f5f9}
        .pb-menu .pb-menu-danger{color:#dc2626}.pb-menu .pb-menu-danger:hover{background:#fef2f2}
        .pb-code{position:fixed;inset:0;z-index:10050;background:rgba(15,23,42,.55);backdrop-filter:blur(2px);display:flex;align-items:center;justify-content:center;padding:24px}
        .pb-code-box{width:min(1280px,100%);height:min(780px,100%);background:#fff;border-radius:16px;box-shadow:0 30px 80px -20px rgba(0,0,0,.5);display:flex;flex-direction:column;overflow:hidden;font-family:system-ui,sans-serif;color:#0f172a}
        .pb-code-head{display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-bottom:1px solid #e2e8f0}
        .pb-code-head strong{font-size:16px}.pb-code-head span{margin-left:10px;padding:2px 10px;border-radius:999px;background:#eff6ff;color:#1d4ed8;font-size:12px;font-weight:600}
        .pb-code-x{width:32px;height:32px;border:0;border-radius:8px;background:transparent;font-size:24px;line-height:1;color:#64748b;cursor:pointer}.pb-code-x:hover{background:#f1f5f9}
        .pb-code-tabs{display:none;gap:6px;padding:10px 20px 0}
        .pb-code-tabs button{border:0;background:#f1f5f9;padding:6px 14px;border-radius:8px;font-weight:600;font-size:13px;cursor:pointer}.pb-code-tabs button.on{background:#1d4ed8;color:#fff}
        .pb-code-panes{flex:1;min-height:0;display:grid;grid-template-columns:1.4fr 1fr;gap:1px;background:#1e293b}
        .pb-code-panes section{display:flex;flex-direction:column;min-width:0;min-height:0;background:#2b2b2b}
        .pb-code-panes h4{margin:0;padding:8px 16px;background:#1e293b;color:#94a3b8;font:600 11px/1 system-ui;letter-spacing:.08em;text-transform:uppercase}
        .pb-code-ed{flex:1;min-height:0;position:relative}
        .pb-code-ed .CodeMirror{position:absolute;inset:0;height:100%;font:13px/1.6 ui-monospace,SFMono-Regular,Menlo,Consolas,monospace}
        .pb-code-foot{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:12px 20px;border-top:1px solid #e2e8f0;background:#f8fafc}
        .pb-code-foot p{margin:0;font-size:12.5px;color:#64748b}.pb-code-foot code{background:#e2e8f0;padding:1px 5px;border-radius:4px}
        .pb-code-foot>div{display:flex;gap:10px;flex:none}
        .pb-code-btn{border:1px solid #cbd5e1;background:#fff;padding:9px 18px;border-radius:9px;font-weight:600;font-size:14px;cursor:pointer}.pb-code-btn:hover{background:#f1f5f9}
        .pb-code-primary{background:#1d4ed8;border-color:#1d4ed8;color:#fff}.pb-code-primary:hover{background:#1e40af}
        .pb-code-primary kbd{margin-left:6px;opacity:.7;font:11px system-ui}
        @media(max-width:900px){.pb-code{padding:0}.pb-code-box{border-radius:0}.pb-code-tabs{display:flex}.pb-code-panes{display:block;position:relative}.pb-code-panes section{display:none;height:100%}.pb-code-panes section.on{display:flex}.pb-code-foot p{display:none}}

        /* ── Right panel: style manager + settings ── */
        .pb-root .gjs-sm-sector{border-bottom:1px solid #e2e8f0}
        .pb-root .gjs-sm-sector-title{display:flex;align-items:center;gap:6px;background:#fff!important;border:0!important;color:#0f172a!important;font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;padding:14px 16px;cursor:pointer}
        .pb-root .gjs-sm-sector-title:hover{background:#f8fafc!important}
        .pb-root .gjs-sm-sector-label{font-weight:700}
        .pb-root .gjs-sm-properties{padding:4px 12px 14px}
        .pb-root .gjs-sm-label,.pb-root .gjs-trt-trait .gjs-label{font-size:12px;font-weight:600;color:#64748b!important}
        .pb-root .gjs-sm-property--full .gjs-sm-label{color:#2563eb!important}
        .pb-root .gjs-sm-property--parent .gjs-sm-label{color:#d97706!important}
        .pb-root .gjs-sm-composite.gjs-sm-property > .gjs-sm-properties,.pb-root .gjs-sm-property.gjs-sm-composite .gjs-sm-properties{background:#f8fafc!important;border:1px solid #e2e8f0;border-radius:10px;padding:10px}
        .pb-root .gjs-field{background:#fff!important;border:1px solid #e2e8f0;border-radius:8px;color:#0f172a;box-shadow:none}
        .pb-root .gjs-field:focus-within{border-color:#60a5fa;box-shadow:0 0 0 3px rgba(96,165,250,.2)}
        .pb-root .gjs-field input,.pb-root .gjs-field select,.pb-root .gjs-field textarea{color:#0f172a!important;font-size:13px}
        .pb-root .gjs-field input::placeholder{color:#94a3b8}
        .pb-root .gjs-field-units select,.pb-root .gjs-field-arrows{color:#64748b}
        .pb-root .gjs-radio-items{border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;background:#fff}
        .pb-root .gjs-radio-item-label{color:#475569;font-size:12px;font-weight:600;border-color:#e2e8f0}
        .pb-root .gjs-radio-item input:checked + .gjs-radio-item-label{background:#2563eb!important;color:#fff!important}
        .pb-root .gjs-clm-tags{padding:6px 4px}
        .pb-root .gjs-trt-traits{padding:6px 4px}
        .pb-root .gjs-trt-trait{display:flex;align-items:center;gap:12px;padding:8px 14px;font-size:13px;color:#334155}
        .pb-root .gjs-trt-trait .gjs-label-wrp{flex:0 0 34%}
        .pb-root .gjs-trt-trait .gjs-field-wrp{flex:1;min-width:0}

        /* ── Left panel: outline ── */
        .pb-root .gjs-layer{color:#334155}
        .pb-root .gjs-layer-item.gjs-one-bg{background:#fff!important;border:0!important;border-radius:8px;margin:1px 8px;padding:2px 8px;min-height:34px;transition:background .12s}
        .pb-root .gjs-layer.gjs-hovered > .gjs-layer-item.gjs-one-bg,.pb-root .gjs-layer-item.gjs-one-bg:hover{background:#f1f5f9!important}
        .pb-root .gjs-layer.gjs-selected > .gjs-layer-item.gjs-one-bg{background:#dbeafe!important}
        .pb-root .gjs-layer.gjs-selected > .gjs-layer-item .gjs-layer-name{color:#1d4ed8}
        .pb-root .gjs-layer-title{background:transparent!important;border:0!important;padding:6px 4px;font-size:13px;font-weight:600;letter-spacing:0;text-transform:none;color:#0f172a}
        .pb-root .gjs-layer-count{font-size:11px;color:#94a3b8}
        .pb-root .gjs-layer-vis,.pb-root .gjs-layer-move,.pb-root .gjs-layer-caret{color:#94a3b8}
        .pb-root .gjs-layer-vis:hover,.pb-root .gjs-layer-move:hover{color:#2563eb}

        /* The page sits on the grey stage as a rounded card. */
        .pb-root .gjs-editor-cont{background:#f1f5f9}
        .pb-root .gjs-cv-canvas{background:transparent;width:calc(100% - 48px);height:calc(100% - 24px);top:12px;left:24px}
        .pb-root .gjs-frame-wrapper{background:#fff;border-radius:12px;box-shadow:0 10px 30px -12px rgba(15,23,42,.25);overflow:hidden}
        .pb-root .gjs-toolbar{background-color:#2563eb;border-radius:8px}
        .pb-root .gjs-badge{background-color:#2563eb;color:#fff;border-radius:4px}

        /* ── Polish: motion, focus, scrollbars, panel skin ─────────────────── */
        .pb-root{position:relative;--pb-ring:0 0 0 3px rgba(37,99,235,.28)}
        .pb-root button,.pb-root select,.pb-root a{transition:background-color .15s,color .15s,border-color .15s,box-shadow .15s,transform .1s,opacity .15s}
        .pb-root button:not(:disabled):active{transform:scale(.97)}
        .pb-root button:focus-visible,.pb-root select:focus-visible,.pb-root a:focus-visible,.pb-root input:focus-visible{outline:none;box-shadow:var(--pb-ring)}
        .pb-save:hover:not(:disabled){box-shadow:0 8px 20px -6px rgba(29,78,216,.65);transform:translateY(-1px)}
        .pb-subtabs button{position:relative}
        .pb-subtabs button:hover{color:#334155}
        .pb-subtabs button.pb-head-on{color:#0f172a}
        .pb-root .gjs-frame-wrapper{transition:width .28s cubic-bezier(.4,0,.2,1)}
        .pb-root .gjs-cv-canvas{background:#eef2f7}
        .pb-root .gjs-block{cursor:grab}.pb-root .gjs-block:hover{transform:translateY(-1px)}.pb-root .gjs-block:active{cursor:grabbing}

        /* thin scrollbars on every panel */
        .pb-root *{scrollbar-width:thin;scrollbar-color:#cbd5e1 transparent}
        .pb-root ::-webkit-scrollbar{width:8px;height:8px}
        .pb-root ::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:8px;border:2px solid transparent;background-clip:content-box}
        .pb-root ::-webkit-scrollbar-thumb:hover{background:#94a3b8;background-clip:content-box}

        /* Style / Settings panels */
        .pb-root .gjs-sm-sector,.pb-root .gjs-trt-traits{border:0}
        .pb-root .gjs-sm-sector-title,.pb-root .gjs-sm-sector__title{padding:14px 20px;border-bottom:1px solid #eef2f7;background:#fff;font-size:11px;font-weight:700;letter-spacing:.08em;color:#334155;cursor:pointer;transition:background-color .15s}
        .pb-root .gjs-sm-sector-title:hover,.pb-root .gjs-sm-sector__title:hover{background:#f8fafc}
        .pb-root .gjs-sm-properties{padding:14px 20px 8px;background:#fcfdff;border-bottom:1px solid #eef2f7}
        .pb-root .gjs-sm-label,.pb-root .gjs-label{color:#475569;font-size:12px;font-weight:600}
        .pb-root .gjs-field{background:#f1f5f9;border:1px solid transparent;border-radius:8px;color:#0f172a;transition:border-color .15s,background-color .15s,box-shadow .15s}
        .pb-root .gjs-field:hover{background:#e9eef5}
        .pb-root .gjs-field:focus-within{background:#fff;border-color:#2563eb;box-shadow:var(--pb-ring)}
        .pb-root .gjs-field input,.pb-root .gjs-field select,.pb-root .gjs-field textarea{color:#0f172a;font-size:13px}
        .pb-root .gjs-trt-trait{padding:10px 20px}
        .pb-root .gjs-layer-title{transition:background-color .12s}
        .pb-root .gjs-layer:not(.gjs-selected) .gjs-layer-title:hover{background:#f1f5f9}

        /* Loading overlay */
        .pb-boot{position:absolute;inset:0;z-index:60;background:#fff;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:14px;color:#64748b;font:600 14px system-ui}
        .pb-spin{width:34px;height:34px;border-radius:50%;border:3px solid #dbeafe;border-top-color:#2563eb;animation:pb-spin .8s linear infinite}
        @keyframes pb-spin{to{transform:rotate(360deg)}}
        .pb-code{animation:pb-fade .15s ease-out}.pb-code-box{animation:pb-pop .2s cubic-bezier(.2,.8,.2,1)}
        @keyframes pb-fade{from{opacity:0}}
        @keyframes pb-pop{from{opacity:0;transform:translateY(10px) scale(.985)}}
        @media (prefers-reduced-motion:reduce){.pb-root *,.pb-code,.pb-code-box{animation:none!important;transition:none!important}}
            /* Right panel sectors: the same card accordion as the Components list on the left. */
        .pb-root .gjs-sm-sectors{padding:12px 16px 16px}
        .pb-root .gjs-sm-sector{margin:0 0 8px;border:1px solid #e2e8f0!important;border-radius:12px;background:#fff;overflow:hidden;transition:border-color .12s,box-shadow .12s}
        .pb-root .gjs-sm-sector:hover{border-color:#cbd5e1!important}
        .pb-root .gjs-sm-sector.gjs-sm-open{border-color:#bfdbfe!important;box-shadow:0 4px 14px -8px rgba(37,99,235,.35)}
        .pb-root .gjs-sm-sector-title,.pb-root .gjs-sm-sector__title{display:flex;align-items:center;gap:10px;padding:13px 14px;border:0!important;background:#fff!important;color:#334155!important;font-size:12px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;transition:background .12s,color .12s}
        .pb-root .gjs-sm-sector-title:hover{background:#f8fafc!important;color:#0f172a!important}
        .pb-root .gjs-sm-sector.gjs-sm-open .gjs-sm-sector-title{background:#eff6ff!important;color:#1d4ed8!important;border-bottom:1px solid #dbeafe!important}
        .pb-root .gjs-sm-sector-caret{display:none}
        .pb-root .gjs-sm-sector-title::before{content:"";flex:none;width:6px;height:6px;border-radius:50%;background:#cbd5e1}
        .pb-root .gjs-sm-sector.gjs-sm-open .gjs-sm-sector-title::before{background:#2563eb}
        .pb-root .gjs-sm-sector-label{margin:0;font-weight:700}
        .pb-root .gjs-sm-sector-title::after{content:"";flex:none;margin-left:auto;width:7px;height:7px;border-right:2px solid #94a3b8;border-bottom:2px solid #94a3b8;transform:rotate(-45deg);transition:transform .15s}
        .pb-root .gjs-sm-sector.gjs-sm-open .gjs-sm-sector-title::after{transform:rotate(45deg);border-color:#2563eb}
        .pb-root .gjs-sm-properties{padding:12px 12px 6px;background:#fff;border-bottom:0}
        /* One compact control size (32px) across both panels and the canvas toolbar. */
        .pb-side-head button{padding:10px 16px;font-size:11px}
        .pb-subtabs button{padding:8px 0}
        .pb-search-wrap{height:32px;border-radius:8px;padding:0 10px}
        .pb-search-wrap input{padding:0;font-size:12px}
        .pb-devbar{padding:8px 12px;gap:10px}
        .pb-devbar-in{height:32px;border-radius:8px;padding:0 6px;gap:2px}
        .pb-devbar-in button{min-width:28px;height:26px;border-radius:6px}
        .pb-devbar-in svg{width:15px;height:15px}
        .pb-devbar .pb-icon{flex:none}
        .pb-sep{height:16px;margin:0 4px}
        .pb-root .gjs-block-categories{padding:2px 12px 12px}
        .pb-root .gjs-sm-sectors{padding:10px 12px 12px}
        .pb-root .gjs-block-category,.pb-root .gjs-sm-sector{margin:0 0 6px;border-radius:8px}
        .pb-root .gjs-block-category .gjs-title,.pb-root .gjs-sm-sector-title,.pb-root .gjs-sm-sector__title{height:32px;padding:0 12px;font-size:11px;box-sizing:border-box}
        .pb-root .gjs-block-category .gjs-title::before,.pb-root .gjs-sm-sector-title::before{width:5px;height:5px}
        .pb-root .gjs-block-category .gjs-title::after,.pb-root .gjs-sm-sector-title::after{width:6px;height:6px}
        .pb-root .gjs-blocks-c{padding:8px 8px 0}
        .pb-root .gjs-block{margin:0 0 8px;padding:7px 28px 7px 10px;gap:10px;border-radius:8px}
        .pb-root .gjs-block__media{flex-basis:32px;width:32px;height:32px;border-radius:8px}
        .pb-root .gjs-block__media svg{width:18px;height:18px}
        .pb-root .gjs-block-label{font-size:12px}
        .pb-root .gjs-block-label small{font-size:11px}
        /* Every strip is the same height: 32px controls, 40px rows. */
        .pb-pill{height:32px;box-sizing:border-box;padding:2px;border-radius:8px}
        .pb-pill button{height:26px;padding:0 10px;border-radius:6px;font-size:12px}
        .pb-seg-on{box-shadow:0 1px 2px rgba(15,23,42,.1)}
        .pb-side-head,.pb-subtabs{height:40px;box-sizing:border-box;align-items:stretch}
        .pb-side-head button,.pb-subtabs button{display:flex;align-items:center;justify-content:center;height:100%;padding:0 16px;font-size:11px}
        .pb-side-head > span{display:flex;align-items:center;padding:0 16px!important;font-size:11px}
        .pb-subtabs button{padding:0}
        .pb-devbar{height:48px;box-sizing:border-box;padding:0 12px}
        .pb-devbar-in{height:32px;box-sizing:border-box}
        .pb-search-wrap{height:32px;box-sizing:border-box}
        .pb-top{height:48px}
        /* Canvas toolbar: a little roomier than the rest. */
        .pb-devbar{height:60px}
        .pb-devbar-in{height:42px;border-radius:10px;padding:0 10px;gap:4px}
        .pb-devbar-in button{min-width:34px;height:32px;border-radius:8px}
        .pb-devbar-in svg{width:18px;height:18px}
        .pb-devbar .pb-icon{width:38px;height:38px;border-radius:10px}
        .pb-zoom{font-size:13px;padding:0 10px}
        .pb-sep{height:20px;margin:0 8px}
</style>
</div>
    <livewire:admin.media-library.picker-modal key="builder-picker-modal" />
</div>
