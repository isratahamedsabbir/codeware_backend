<div class="max-w-[1600px] w-full mx-auto flex-1">

    @push('page-header-actions')
        <flux:button variant="ghost" size="sm" class="admin-back-btn" icon="arrow-left" href="{{ route('admin.groups') }}" wire:navigate>
            Back
        </flux:button>
    @endpush

    <x-admin-section-card variant="postbox" title="Group Details" :collapsible="false" class="w-full max-w-2xl">

        <x-admin-locale-tabs>
            @foreach (\App\Support\Locale::translatable() as $language)
                <x-admin-locale-panel :code="$language->code">
                    <flux:field>
                        <flux:label :badge="$language->code">
                            Name
                            @if ($language->code === $this->primaryLocale)<span class="text-red-500 ml-0.5">*</span>@endif
                        </flux:label>
                        <flux:input wire:model="name.{{ $language->code }}" placeholder="Group name" />
                        @if ($language->code === $this->primaryLocale)<flux:error name="name.{{ $language->code }}" />@endif
                    </flux:field>
                </x-admin-locale-panel>
            @endforeach
        </x-admin-locale-tabs>

        <div class="mt-4" wire:key="group-type-panel">
            <flux:field>
                <flux:label>Type<span class="text-red-500 ml-0.5">*</span><x-field-hint text="Groups under the Gallery type are the ones the Media Library offers for gallery images. Manage the list of types under Accessories → Types" /></flux:label>
                <flux:select wire:model="typeId" placeholder="Select a type">
                    @foreach ($types as $typeOption)
                        <flux:select.option value="{{ $typeOption->id }}">{{ $typeOption->getTranslation('name', $this->primaryLocale, false) ?: $typeOption->getTranslation('name', 'en', false) }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="typeId" />
            </flux:field>
        </div>

        <div class="-mx-3 -mb-3 mt-4 flex items-center gap-3 flex-wrap rounded-b-[3px] border-t border-zinc-200 bg-zinc-50 px-3 py-3 dark:border-zinc-700 dark:bg-zinc-800/40">
            <x-admin-save-button :label="$groupId ? 'Update Group' : 'Create Group'" />
        </div>

    </x-admin-section-card>
</div>
