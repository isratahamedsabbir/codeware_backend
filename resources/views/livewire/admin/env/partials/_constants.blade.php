{{-- Constant tab of Developer Tools (moved from Settings). --}}
            <div class="max-w-[1600px] space-y-5">
                <x-admin-section-card header-border="border-zinc-100" icon="variable" title="Constant"
                    description="Freeform key/value pairs, available site-wide — not tied to any page or CMS section."
                    collapsible :collapsed="true">
                    <x-slot:titleActions>
                        {{-- Points into the Developer Guide rather than opening a copy of
                             it. Everything this button used to explain - what a constant
                             is, how to read one, where they surface - is answered once
                             in the guide, so the modal was a second copy to keep true,
                             and the first one to go stale.

                             It was also already wrong when written: it claimed a file
                             constant "resolves to" its public URL, when the picker
                             stores the URL as the value outright and nothing resolves
                             it. And it never mentioned the key rules that decide whether
                             a save succeeds at all.

                             A plain anchor rather than wire:navigate: the guide is
                             another page entirely, and a full load is what actually
                             honours the fragment and lands on the heading. --}}
                        @if (AppSupporteatures::enabled('developer-guide'))
                        <a href="{{ route('admin.developer-guide') }}#constants"
                            title="how constants work — open the developer guide"
                            aria-label="how constants work — open the developer guide"
                            class="flex size-5 items-center justify-center text-zinc-400 transition-colors hover:text-primary cursor-pointer">
                            <flux:icon.information-circle class="size-4" />
                        </a>
                        @endif
                    </x-slot:titleActions>
                    <x-slot:actions>
                        <flux:button size="sm" variant="outline" icon="plus" wire:click="addConstant" x-on:click="open = true">Add field</flux:button>
                    </x-slot:actions>

                    <flux:error name="constants" />

                    <x-admin-constant-fields :items="$constants" :open-constants="$openConstants" model="constants" empty-hint="Add reusable key/value pairs for phone numbers, links, footer text and more." />
                </x-admin-section-card>
            </div>
