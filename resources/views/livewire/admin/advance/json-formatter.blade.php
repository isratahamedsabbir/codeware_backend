<div class="max-w-4xl space-y-6">
    <x-admin-section-card icon="code-bracket" title="JSON Formatter" icon-color="bg-sky-500/10 text-sky-600"
        description="Paste JSON to validate, pretty-print or minify it. Nothing is sent to the server.">

        <div wire:key="json-formatter" x-data="{
            input: '',
            error: '',
            copied: false,
            run(minify = false) {
                this.error = '';
                if (!this.input.trim()) return;
                try {
                    const parsed = JSON.parse(this.input);
                    this.input = minify ? JSON.stringify(parsed) : JSON.stringify(parsed, null, 2);
                } catch (e) {
                    this.error = e.message;
                }
            },
            async copy() {
                await navigator.clipboard.writeText(this.input);
                this.copied = true;
                setTimeout(() => this.copied = false, 1500);
            },
            clear() { this.input = ''; this.error = ''; }
        }" class="space-y-3">

            <textarea x-model="input" rows="18" spellcheck="false" placeholder='{"name": "Codeware", "tags": ["a", "b"]}'
                class="w-full rounded border border-zinc-200 bg-zinc-50 p-3 font-mono text-sm text-zinc-800 dark:bg-zinc-900 dark:border-zinc-700 dark:text-zinc-200"
                :class="error && 'border-rose-400'"></textarea>

            <p x-show="error" x-cloak x-text="error" class="text-xs text-rose-600"></p>

            <div class="flex flex-wrap items-center gap-2">
                <flux:button type="button" size="sm" variant="primary" icon="code-bracket" x-on:click="run(false)">Format</flux:button>
                <flux:button type="button" size="sm" variant="ghost" icon="arrows-pointing-in" x-on:click="run(true)">Minify</flux:button>
                <flux:button type="button" size="sm" variant="ghost" icon="clipboard-document" x-on:click="copy()">
                    <span x-show="!copied">Copy</span>
                    <span x-show="copied" x-cloak>Copied!</span>
                </flux:button>
                <flux:button type="button" size="sm" variant="ghost" icon="trash" x-on:click="clear()">Clear</flux:button>
            </div>
        </div>
    </x-admin-section-card>
</div>
