import grapesjs from 'grapesjs';
import blocksBasic from 'grapesjs-blocks-basic';
import forms from 'grapesjs-plugin-forms';
import navbar from 'grapesjs-navbar';
import tabs from 'grapesjs-tabs';
import customCode from 'grapesjs-custom-code';
import tooltip from 'grapesjs-tooltip';
import styleBg from 'grapesjs-style-bg';
import parserPostcss from 'grapesjs-parser-postcss';
import 'grapesjs/dist/css/grapes.min.css';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import { registerBlocks, blockLabel } from './page-builder-blocks';
// The media-library picker modal (a Livewire component) is built on this Alpine component.
import './chunk-upload';

// Some GrapesJS plugins ship CommonJS, so the import can arrive as
// {default: fn} (sometimes nested) instead of the function itself.
const plugin = (m) => {
    while (m && typeof m !== 'function' && m.default) m = m.default;
    return m;
};

// SweetAlert2 stands in for the browser's confirm()/prompt()/status text.
const toastBox = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
});
const toast = (icon, title) => toastBox.fire({ icon, title });

const confirmBox = async ({ title, text = '', confirmText = 'Yes', icon = 'warning', danger = true }) => {
    const r = await Swal.fire({
        title,
        text,
        icon,
        showCancelButton: true,
        confirmButtonText: confirmText,
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        focusCancel: true,
        confirmButtonColor: danger ? '#dc2626' : '#1d4ed8',
    });

    return r.isConfirmed;
};

const askName = async (title) => {
    const r = await Swal.fire({
        title,
        input: 'text',
        inputPlaceholder: 'e.g. Pricing section',
        showCancelButton: true,
        confirmButtonText: 'Save',
        confirmButtonColor: '#1d4ed8',
        inputValidator: (v) => (v && v.trim() ? undefined : 'Please enter a name'),
    });

    return r.isConfirmed ? r.value.trim() : null;
};

/*
 * Code editor modal for the selected element: HTML and CSS side by side, CodeMirror (bundled with
 * GrapesJS) for highlighting and line numbers. Resolves {html, css} on Apply, null on Cancel.
 */
function openCodeEditor(editor, { title, html, css }) {
    return new Promise((resolve) => {
        const root = document.createElement('div');
        root.className = 'pb-code';
        root.innerHTML = `
            <div class="pb-code-box" role="dialog" aria-modal="true" aria-label="Edit code">
                <header class="pb-code-head">
                    <div><strong>Edit code</strong><span></span></div>
                    <button type="button" class="pb-code-x" data-act="cancel" title="Close (Esc)">&times;</button>
                </header>
                <div class="pb-code-tabs">
                    <button type="button" data-tab="html" class="on">HTML</button>
                    <button type="button" data-tab="css">CSS</button>
                </div>
                <div class="pb-code-panes">
                    <section data-pane="html" class="on"><h4>HTML</h4><div class="pb-code-ed"></div></section>
                    <section data-pane="css"><h4>CSS</h4><div class="pb-code-ed"></div></section>
                </div>
                <footer class="pb-code-foot">
                    <p>Scripts and inline handlers (<code>onclick</code>…) are removed when you apply.</p>
                    <div><button type="button" class="pb-code-btn" data-act="cancel">Cancel</button><button type="button" class="pb-code-btn pb-code-primary" data-act="apply">Apply <kbd>Ctrl ↵</kbd></button></div>
                </footer>
            </div>`;
        root.querySelector('.pb-code-head span').textContent = title;
        document.body.appendChild(root);

        const make = (name, value, el) => {
            const v = editor.CodeManager.createViewer({
                codeName: name, theme: 'hopscotch', readOnly: false, lineNumbers: true, lineWrapping: false,
                autoBeautify: true, autoCloseTags: true, autoCloseBrackets: true, indentUnit: 2, tabSize: 2,
            });
            el.appendChild(v.getElement());
            v.setContent(value);

            return v;
        };
        const [htmlEl, cssEl] = root.querySelectorAll('.pb-code-ed');
        const htmlV = make('htmlmixed', html, htmlEl);
        const cssV = make('css', css, cssEl);
        const refresh = () => requestAnimationFrame(() => { htmlV.refresh?.(); cssV.refresh?.(); });
        refresh();

        // Narrow screens show one pane at a time.
        root.querySelectorAll('[data-tab]').forEach((t) => t.addEventListener('click', () => {
            root.querySelectorAll('[data-tab]').forEach((x) => x.classList.toggle('on', x === t));
            root.querySelectorAll('[data-pane]').forEach((p) => p.classList.toggle('on', p.dataset.pane === t.dataset.tab));
            refresh();
        }));

        const close = (value) => {
            document.removeEventListener('keydown', onKey, true);
            root.remove();
            resolve(value);
        };
        const apply = () => close({ html: htmlV.getContent(), css: cssV.getContent() });
        const onKey = (e) => {
            if (e.key === 'Escape') { e.stopPropagation(); close(null); }
            if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) { e.preventDefault(); apply(); }
        };
        document.addEventListener('keydown', onKey, true);
        root.addEventListener('mousedown', (e) => { if (e.target === root) close(null); });
        root.querySelectorAll('[data-act]').forEach((b) => b.addEventListener('click', () => (b.dataset.act === 'apply' ? apply() : close(null))));
    });
}

/** Inert parse, so nothing in the pasted code runs while it is cleaned; returns '' when nothing is left. */
function cleanHtml(code) {
    const doc = new DOMParser().parseFromString(`<body>${code}</body>`, 'text/html');
    const css = [...doc.querySelectorAll('style')].map((s) => s.textContent).join('\n');
    doc.querySelectorAll('style, script, iframe, object, embed, link, meta, base').forEach((n) => n.remove());
    doc.body.querySelectorAll('*').forEach((n) => {
        [...n.attributes].forEach((a) => {
            const bad = a.name.startsWith('on') || (['href', 'src', 'action', 'formaction', 'xlink:href'].includes(a.name) && /^\s*(javascript|data:text\/html)/i.test(a.value));
            if (bad) n.removeAttribute(a.name);
        });
    });
    // A <style> typed into the HTML pane still counts: keep it as a style block next to the markup.
    const body = doc.body.innerHTML.trim();

    return body ? (css.trim() ? `${body}<style>${css}</style>` : body) : '';
}

/** No script-ish values in pasted CSS (legacy expression()/javascript: urls, @import). */
const stripScriptsFromCss = (css) => String(css).replace(/expression\s*\(|javascript\s*:|@import[^;]*;?/gi, '');

const DEVICES ={ desktop: 'Desktop', tablet: 'Tablet', mobile: 'Mobile portrait' };

const USER_ICON = '<svg viewBox="0 0 24 24" width="38" height="38" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 2 3 7v10l9 5 9-5V7l-9-5Z"/><path d="m3 7 9 5 9-5M12 12v10"/></svg>';

/*
 * Alpine component behind admin/builders/editor.blade.php. Registered on
 * alpine:init like the other admin components, so it exists before Alpine
 * evaluates the x-data that uses it.
 *
 * The layout is ours rather than GrapesJS's own panels — components on the
 * left, canvas in the middle, style and settings on the right, like the Puck
 * editor — so the managers are mounted into the elements the Blade view
 * provides ($refs).
 */
document.addEventListener('alpine:init', () => {
    window.Alpine.data('pageBuilder', ({ project, blocks = [], assets = [], categories = [], locale, pageTitle, uploadUrl, csrf }) => ({
        editor: null,
        locale,
        pageTitle,
        device: 'desktop',
        left: 'blocks',
        right: 'style',
        search: '',
        hasSelection: false,
        selectedName: '',
        previewing: false,
        showLeft: true,
        showRight: true,
        zoom: 100,
        saving: false,
        ready: false,
        status: '',
        history: { undo: false, redo: false },

        init() {
            const editor = (this.editor = grapesjs.init({
                container: this.$refs.gjs,
                height: '100%',
                fromElement: false,
                storageManager: false,
                undoManager: { trackSelection: false },
                panels: { defaults: [] },
                blockManager: { appendTo: this.$refs.blocks },
                // Keep the page body in Outline so users can expand it and select the whole page.
                layerManager: { appendTo: this.$refs.layers, showWrapper: true },
                // componentFirst: styling a block changes that one element, not every element sharing its class.
                selectorManager: { componentFirst: true },
                styleManager: { appendTo: this.$refs.styles },
                traitManager: { appendTo: this.$refs.traits },
                assetManager: {
                    assets: assets.map((src) => ({ type: 'image', src })),
                    upload: uploadUrl,
                    uploadName: 'files',
                    multiUpload: true,
                    autoAdd: true,
                    headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                },
                canvas: { styles: [] },
                protectedCss: '* { box-sizing: border-box; } body { margin: 0; font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; color: #0f172a; }',
                plugins: [
                    (e) => plugin(blocksBasic)(e, { flexGrid: true, category: 'Basic' }),
                    ...[forms, navbar, tabs, customCode, tooltip, styleBg, parserPostcss].map(plugin),
                ],
                projectData: project || undefined,
            }));

            // GrapesJS lets the page body ('wrapper') carry only background styles; allow the usual page-level ones too.
            const sides = ['top', 'right', 'bottom', 'left'];
            editor.getWrapper().set('stylable', [
                'background', 'background-color', 'background-image', 'background-repeat', 'background-attachment', 'background-position', 'background-size',
                'padding', 'margin', ...sides.map((x) => `padding-${x}`), ...sides.map((x) => `margin-${x}`),
                'color', 'font-family', 'font-size', 'font-weight', 'line-height', 'letter-spacing', 'text-align', 'min-height',
            ]);

            // Keep Alpine's reactivity away from the GrapesJS editor (and so every model it returns):
            // proxying that whole object graph is slow and breaks identity checks inside GrapesJS.
            Object.defineProperty(editor, '__v_skip', { value: true, configurable: true });

            registerBlocks(editor, { categories });
            const tabsBlock = editor.Blocks.get('tabs');
            if (tabsBlock) {
                const definition = tabsBlock.toJSON();
                editor.Blocks.remove('tabs');
                editor.Blocks.add('tabs', { ...definition, category: 'Content' });
            }
            blocks.forEach((b) => this.addBlock(b));
            this.tidyCategories();
            this.enableSaveAsBlock();

            // Canvas polish, injected into the page iframe: a calmer hover/selection outline and smooth scrolling.
            editor.on('canvas:frame:load', ({ el }) => {
                const doc = el?.contentDocument;
                if (!doc || doc.getElementById('pb-frame-polish')) return;

                const style = doc.createElement('style');
                style.id = 'pb-frame-polish';
                style.textContent = `
                    html{scroll-behavior:smooth;-webkit-font-smoothing:antialiased}
                    .gjs-dashed *[data-gjs-highlightable]{outline:1px dashed rgba(100,116,139,.35)!important;outline-offset:-1px}
                    .gjs-hovered{outline:2px solid rgba(37,99,235,.45)!important;outline-offset:-2px;transition:outline-color .15s}
                    .gjs-selected{outline:2px solid #2563eb!important;outline-offset:-2px}
                    .gjs-placeholder{border-color:#2563eb!important;background:rgba(37,99,235,.15)!important}
                    ::-webkit-scrollbar{width:10px;height:10px}::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:8px;border:2px solid transparent;background-clip:content-box}`;
                doc.head.appendChild(style);
            });

            // The skeleton overlay lifts once the canvas has been laid out.
            this.$nextTick(() => setTimeout(() => { this.ready = true; }, 150));

            // The delete button on a saved block's card. GrapesJS starts a drag on mousedown anywhere on
            // a block and swallows the click that follows, so the button is caught first, in the capture phase.
            const delBtn = (target) => target instanceof Element ? target.closest('[data-del-block]') : null;
            const stopBlockDrag = (e) => {
                if (!delBtn(e.target)) return;

                e.stopPropagation();
            };
            this.$refs.blocks.addEventListener('pointerdown', stopBlockDrag, true);
            this.$refs.blocks.addEventListener('mousedown', stopBlockDrag, true);
            this.$refs.blocks.addEventListener('click', (e) => {
                const btn = delBtn(e.target);
                if (!btn) return;

                e.preventDefault();
                e.stopPropagation();
                this.deleteBlock(Number(btn.dataset.delBlock));
            }, true);

            const sync = () => {
                const sel = editor.getSelected();
                this.hasSelection = !!sel;
                this.selectedName = sel ? sel.getName() : '';
            };
            editor.on('component:selected', sync);
            editor.on('component:deselected', sync);
            editor.on('update', () => {
                this.history.undo = editor.UndoManager.hasUndo();
                this.history.redo = editor.UndoManager.hasRedo();
            });

            this.$watch('search', (q) => this.filterBlocks(q));

            // Ctrl/Cmd+S saves, anywhere on the page (and inside the canvas).
            const onKey = (e) => {
                if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
                    e.preventDefault();
                    this.save();
                }
            };
            document.addEventListener('keydown', onKey);
            editor.on('load', () => editor.Canvas.getDocument().addEventListener('keydown', onKey));

            window.addEventListener('beforeunload', (e) => {
                if (editor.getDirtyCount() > 0) {
                    e.preventDefault();
                    e.returnValue = '';
                }
            });
        },

        // ── components list ───────────────────────────────────────

        tidyCategories() {
            const order = ['Sections', 'Dynamic', 'Content', 'Layout', 'My blocks'];

            this.editor.Blocks.getCategories().forEach((c) => c.set('open', false));

            // GrapesJS lists categories in the order their first block arrived, and the
            // plugins' own (Basic, Forms, Extra) arrive first — put ours on top, theirs after.
            this.$nextTick(() => {
                const root = this.$refs.blocks.querySelector('.gjs-block-categories');
                if (!root) return;

                const rank = (el) => {
                    const i = order.indexOf(el.querySelector('.gjs-title')?.textContent.trim());
                    return i === -1 ? order.length : i;
                };

                [...root.children].sort((a, b) => rank(a) - rank(b)).forEach((el) => root.appendChild(el));
            });
        },

        // A saved block, offered in the list under "My blocks" (with a delete button on its card).
        addBlock(b) {
            this.editor.Blocks.add(`user-${b.id}`, {
                label: `${blockLabel(b.name, 'Saved block')}<button type="button" class="pb-del" data-del-block="${b.id}" title="Delete this block">&times;</button>`,
                category: 'My blocks',
                content: b.content,
                media: USER_ICON,
            });
        },

        removeBlock(id) {
            this.editor.Blocks.remove(`user-${id}`);
        },

        async deleteBlock(id) {
            if (!await confirmBox({ title: 'Delete this block?', text: 'Pages already using it keep their copy.', confirmText: 'Delete' })) return;

            await this.$wire.deleteBlock(id);
            this.removeBlock(id);
            toast('success', 'Block deleted');
        },

        async switchLocale(nextLocale, select) {
            if (nextLocale === this.locale) return;

            if (this.editor.getDirtyCount() > 0) {
                if (!await confirmBox({ title: 'Save changes first?', text: 'Save the page before switching languages.', confirmText: 'Save & switch', icon: 'question', danger: false })) {
                    select.value = this.locale;

                    return;
                }

                if (!await this.save()) {
                    select.value = this.locale;

                    return;
                }
            }

            this.status = 'Loading language…';

            try {
                const result = await this.$wire.switchLocale(nextLocale);

                this.editor.loadProjectData(result.project || { pages: [] });
                this.locale = nextLocale;
                this.pageTitle = result.title;
                this.status = `Editing ${select.options[select.selectedIndex].text}`;
            } catch (error) {
                console.error('Could not switch builder language', error);
                select.value = this.locale;
                this.status = 'Could not switch language';
                toast('error', 'Could not switch language');
            } finally {
                setTimeout(() => (this.status = ''), 2500);
            }
        },

        async deleteSelected() {
            const selected = this.editor.getSelected();
            if (!selected || !await confirmBox({ title: 'Delete this element?', text: 'You can undo this action.', confirmText: 'Delete' })) return;

            selected.remove();
        },

        filterBlocks(q) {
            q = q.trim().toLowerCase();
            const root = this.$refs.blocks;

            root.querySelectorAll('.gjs-block').forEach((el) => {
                const label = (el.textContent || '').toLowerCase();
                el.style.display = !q || label.includes(q) ? '' : 'none';
            });

            root.querySelectorAll('.gjs-block-category').forEach((cat) => {
                const any = [...cat.querySelectorAll('.gjs-block')].some((b) => b.style.display !== 'none');
                cat.style.display = any ? '' : 'none';
                if (q && any) cat.classList.add('gjs-open');
            });
        },

        // A save icon in the selected element's toolbar: stores it (with its CSS) as a reusable block.
        enableSaveAsBlock() {
            const editor = this.editor;

            editor.Commands.add('save-as-block', {
                run: async (ed) => {
                    const comp = ed.getSelected();
                    if (!comp) return;

                    const name = await askName('Name this block');
                    if (!name) return;

                    const css = ed.CodeManager.getCode(comp, 'css', { cssc: ed.Css });
                    const content = comp.toHTML() + (css ? `<style>${css}</style>` : '');
                    const id = await this.$wire.saveBlock(name, content);

                    this.addBlock({ id, name, content });
                    this.tidyCategories();
                    toast('success', 'Saved as a reusable block');
                },
            });

            // </> icon in the toolbar: edit the selected element's HTML + CSS by hand.
            editor.Commands.add('edit-code', {
                run: async (ed) => {
                    const comp = ed.getSelected();
                    if (!comp) return;

                    const result = await openCodeEditor(ed, {
                        title: comp.getName() || 'Element',
                        html: comp.toHTML(),
                        css: ed.CodeManager.getCode(comp, 'css', { cssc: ed.Css }) || '',
                    });
                    if (!result) return;

                    const html = cleanHtml(result.html);
                    if (!html) return toast('error', 'Nothing left after cleaning the code');

                    const [fresh] = [].concat(comp.replaceWith(html));
                    const css = stripScriptsFromCss(result.css);
                    if (css.trim()) ed.Css.addRules(css);
                    if (fresh) ed.select(fresh);
                    toast('success', 'Code applied');
                },
            });

            editor.on('component:selected', (comp) => {
                const toolbar = comp.get('toolbar') || [];
                if (!comp.get('removable') || toolbar.some((t) => t.command === 'save-as-block')) return;

                comp.set('toolbar', [
                    ...toolbar,
                    {
                        label: '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="m8 7-5 5 5 5M16 7l5 5-5 5"/></svg>',
                        attributes: { title: 'Edit code' },
                        command: 'edit-code',
                    },
                    {
                        label: '<svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M17 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7l-4-4Zm-5 16a3 3 0 1 1 0-6 3 3 0 0 1 0 6Zm3-10H5V5h10v4Z"/></svg>',
                        attributes: { title: 'Save as reusable block' },
                        command: 'save-as-block',
                    },
                ]);
            });
        },

        // ── toolbar ───────────────────────────────────────────────

        setDevice(key) {
            this.device = key;
            this.editor.setDevice(DEVICES[key]);
        },

        setZoom(z) {
            this.zoom = Math.min(200, Math.max(30, z));
            this.editor.Canvas.setZoom(this.zoom);
        },

        undo() { this.editor.UndoManager.undo(); },
        redo() { this.editor.UndoManager.redo(); },

        // Edit ⇄ Preview: preview hides both side panels and the editing chrome.
        setPreview(on) {
            if (this.previewing === on) return;

            this.previewing = on;
            on ? this.editor.runCommand('preview') : this.editor.stopCommand('preview');
            this.$nextTick(() => this.editor.refresh());
        },

        togglePanel(side) {
            side === 'left' ? (this.showLeft = !this.showLeft) : (this.showRight = !this.showRight);
            this.$nextTick(() => this.editor.refresh());
        },

        showCode() {
            this.editor.runCommand('export-template');
        },

        toggleOutline() {
            const c = this.editor.Commands;
            c.isActive('sw-visibility') ? c.stop('sw-visibility') : c.run('sw-visibility');
        },

        async clearAll() {
            if (await confirmBox({ title: 'Clear the whole page?', text: 'Everything on this page will be removed. You can undo this action.', confirmText: 'Clear page' })) {
                this.editor.Components.clear();
                this.editor.Css.clear();
            }
        },

        async save() {
            if (this.saving) return false;

            this.saving = true;
            this.status = 'Saving…';

            try {
                const body = this.editor.getHtml().replace(/^\s*<body[^>]*>|<\/body>\s*$/g, '');
                await this.$wire.save(this.editor.getProjectData(), body, this.editor.getCss());

                this.editor.getModel().set('changesCount', 0);
                this.status = 'Saved';
                toast('success', 'Page saved');
                return true;
            } catch (e) {
                this.status = 'Could not save';
                toast('error', 'Could not save the page');
                return false;
            } finally {
                this.saving = false;
                setTimeout(() => (this.status = ''), 2500);
            }
        },

        destroy() {
            this.editor?.destroy();
        },
    }));
});
