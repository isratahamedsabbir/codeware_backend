{{-- Custom Code tab of Developer Tools (moved from Settings). --}}
            <div class="max-w-[1600px] space-y-5">
                <x-admin-section-card header-border="border-zinc-100" icon="code-bracket" title="Head Code (Global)"
                    description="Injected into <head>, before it closes, on every page — meta tags, verification tags, analytics.">
                    <flux:field>
                        <flux:textarea wire:model="settings.custom_head_code" class="h-40 w-full font-mono text-xs" placeholder="<script>...</script>" />
                    </flux:field>
                </x-admin-section-card>

                <x-admin-section-card header-border="border-zinc-100" icon="code-bracket" title="Body Code (Global)"
                    description="Injected just before </body> closes, on every page — chat widgets, tracking pixels, deferred scripts.">
                    <flux:field>
                        <flux:textarea wire:model="settings.custom_body_code" class="h-40 w-full font-mono text-xs" placeholder="<script>...</script>" />
                    </flux:field>
                </x-admin-section-card>

                {{-- Page-specific code: each row picks the kind of page it applies to
                     and is output after the global code on those pages only. --}}
                <x-admin-section-card header-border="border-zinc-100" icon="document-duplicate" title="Custom Code Snippets"
                    description="Named head/body snippets. Pick one from the “Custom code” dropdown in a page, product, post or category's SEO section to load it on that page only.">
                    <x-slot:actions>
                        <flux:button size="sm" variant="outline" icon="plus" wire:click="addCodeRule" square tooltip="Add code" aria-label="Add code" />
                    </x-slot:actions>

                    {{-- Same collapsible-row look as the Constant fields. Open state is
                         kept server-side ($openCodeRules, by snippet id) so typing in a
                         box re-renders without collapsing the row. --}}
                    <div class="space-y-3">
                        @forelse ($codeRules as $i => $rule)
                            @php $codeOpen = in_array($rule['id'], $openCodeRules, true); @endphp
                            <div wire:key="code-rule-{{ $rule['id'] }}"
                                class="group overflow-hidden rounded-lg border bg-white transition-all dark:bg-zinc-800/40 {{ $codeOpen ? 'border-zinc-300 shadow-sm dark:border-zinc-600' : 'border-zinc-200 hover:border-zinc-300 dark:border-zinc-700' }}">
                                <div wire:click="toggleCodeRule('{{ $rule['id'] }}')" role="button" tabindex="0" aria-expanded="{{ $codeOpen ? 'true' : 'false' }}"
                                    class="flex cursor-pointer select-none items-center justify-between gap-4 px-4 py-3">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <div class="flex size-8 shrink-0 items-center justify-center rounded-lg transition-colors {{ $codeOpen ? 'bg-primary/10 text-primary' : 'bg-zinc-100 text-zinc-400 dark:bg-zinc-800 dark:text-zinc-500' }}">
                                            <flux:icon.code-bracket class="size-4" />
                                        </div>
                                        <p class="truncate font-mono text-sm font-semibold text-zinc-800 dark:text-zinc-100">
                                            {{ filled($rule['name'] ?? '') ? $rule['name'] : 'Snippet '.($i + 1) }}
                                        </p>
                                    </div>

                                    <div class="flex shrink-0 items-center gap-1.5">
                                        <button type="button" wire:click.stop="removeCodeRule({{ $i }})"
                                            class="flex size-7 items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-rose-50 hover:text-rose-500 cursor-pointer" aria-label="Remove snippet">
                                            <flux:icon.x-mark class="size-4" />
                                        </button>
                                    </div>
                                </div>

                                <div class="space-y-4 border-t border-zinc-100 p-4 {{ $codeOpen ? '' : 'hidden' }} dark:border-zinc-700">
                                    <div>
                                        <flux:label>Name</flux:label>
                                        <flux:input wire:model.live="codeRules.{{ $i }}.name" placeholder="e.g. Global, Product, Category" />
                                    </div>
                                    <div>
                                        <flux:label>Head code</flux:label>
                                        <flux:textarea wire:model="codeRules.{{ $i }}.head" class="h-[6.5rem] resize-none font-mono text-xs" placeholder="<script>...</script>" />
                                    </div>
                                    <div>
                                        <flux:label>Body code</flux:label>
                                        <flux:textarea wire:model="codeRules.{{ $i }}.body" class="h-[6.5rem] resize-none font-mono text-xs" placeholder="<script>...</script>" />
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-lg border border-dashed border-zinc-200 py-12 text-center dark:border-zinc-700">
                                <div class="mx-auto mb-3 flex size-10 items-center justify-center rounded-full bg-zinc-50 text-zinc-300 dark:bg-zinc-800 dark:text-zinc-600">
                                    <flux:icon.code-bracket class="size-5" />
                                </div>
                                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-300">No snippets yet</p>
                                <p class="mt-1 text-xs text-zinc-400 dark:text-zinc-500">Click “Add code” to create one.</p>
                            </div>
                        @endforelse
                    </div>
                </x-admin-section-card>
            </div>
