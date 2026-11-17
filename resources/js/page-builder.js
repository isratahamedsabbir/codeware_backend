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

const SEO_PICKER = 'builder-seo-';

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

const DEVICES = { desktop: 'Desktop', tablet: 'Tablet', mobile: 'Mobile portrait' };

const USER_ICON = '<svg viewBox="0 0 24 24" width="38" height="38" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 2 3 7v10l9 5 9-5V7l-9-5Z"/><path d="m3 7 9 5 9-5M12 12v10"/></svg>';

/*
 * Alpine component behind admin/pages/builder.blade.php. Registered on
 * alpine:init like the other admin components, so it exists before Alpine
 * evaluates the x-data that uses it.
 *
 * The layout is ours rather than GrapesJS's own panels — components on the
 * left, canvas in the middle, style and settings on the right, like the Puck
 * editor — so the managers are mounted into the elements the Blade view
 * provides ($refs).
 */
document.addEventListener('alpine:init', () => {
    window.Alpine.data('pageBuilder', ({ project, blocks = [], assets = [], locale, pageTitle, seo = {}, canonicalBases = [], uploadUrl, csrf }) => ({
        editor: null,
        locale,
        pageTitle,
        seo,
        canonicalBases,
        seoSnapshot: '',
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
        seoSaving: false,
        status: '',
        seoStatus: '',
        history: { undo: false, redo: false },

        init() {
            this.seoSnapshot = JSON.stringify(this.seo);

            // The media library picker answers with 'mediaPickerSelected' — keep only our own SEO picks.
            window.addEventListener('mediaPickerSelected', (ev) => {
                const d = Array.isArray(ev.detail) ? ev.detail[0] : ev.detail;
                if (!d || !String(d.pickerId || '').startsWith(SEO_PICKER)) return;

                this.seo[d.pickerId.slice(SEO_PICKER.length)] = d.url || '';
            });

            const editor = (this.editor = grapesjs.init({
                container: this.$refs.gjs,
                height: '100%',
                fromElement: false,
                storageManager: false,
                undoManager: { trackSelection: false },
                panels: { defaults: [] },
                blockManager: { appendTo: this.$refs.blocks },
                // showWrapper: false lists the page's own blocks at the top level instead of one collapsed "Body" row.
                layerManager: { appendTo: this.$refs.layers, showWrapper: false },
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

            registerBlocks(editor);
            const tabsBlock = editor.Blocks.get('tabs');
            if (tabsBlock) {
                const definition = tabsBlock.toJSON();
                editor.Blocks.remove('tabs');
                editor.Blocks.add('tabs', { ...definition, category: 'Content' });
            }
            blocks.forEach((b) => this.addBlock(b));
            this.tidyCategories();
            this.enableSaveAsBlock();

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
            const order = ['Sections', 'Content', 'Layout', 'My blocks'];

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

            if (this.editor.getDirtyCount() > 0 || this.hasUnsavedSeo()) {
                if (!await confirmBox({ title: 'Save changes first?', text: 'Save the page and SEO changes before switching languages.', confirmText: 'Save & switch', icon: 'question', danger: false })) {
                    select.value = this.locale;

                    return;
                }

                if (!await this.saveAll()) {
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
                this.seo = result.seo;
                this.seoSnapshot = JSON.stringify(this.seo);
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

            editor.on('component:selected', (comp) => {
                const toolbar = comp.get('toolbar') || [];
                if (!comp.get('removable') || toolbar.some((t) => t.command === 'save-as-block')) return;

                comp.set('toolbar', [
                    ...toolbar,
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

        // Opens the media library to choose the OG / Twitter image (field: og_image | twitter_image).
        pickSeoImage(field) {
            window.dispatchEvent(new CustomEvent('open-media-picker', {
                detail: { pickerId: SEO_PICKER + field, onlyImages: true, mimes: 'jpg,jpeg,png,gif,webp,avif', maxSizeKb: 2048 },
            }));
        },

        hasUnsavedSeo() {
            return JSON.stringify(this.seo) !== this.seoSnapshot;
        },

        async saveSeo() {
            if (this.seoSaving) return false;

            this.seoSaving = true;
            this.seoStatus = 'Saving SEO…';

            try {
                await this.$wire.saveSeo(JSON.parse(JSON.stringify(this.seo)));
                this.seoSnapshot = JSON.stringify(this.seo);
                this.seoStatus = 'SEO saved';
                toast('success', 'SEO saved');
                return true;
            } catch (error) {
                console.error('Could not save page SEO', error);
                this.seoStatus = 'Could not save SEO';
                toast('error', 'Could not save SEO');
                return false;
            } finally {
                this.seoSaving = false;
                setTimeout(() => (this.seoStatus = ''), 2500);
            }
        },

        async saveAll() {
            const builderDirty = this.editor.getDirtyCount() > 0;
            const seoDirty = this.hasUnsavedSeo();

            if (builderDirty || !seoDirty) {
                if (!await this.save()) return false;
            }

            if (seoDirty && !await this.saveSeo()) return false;

            return true;
        },

        destroy() {
            this.editor?.destroy();
        },
    }));
});
