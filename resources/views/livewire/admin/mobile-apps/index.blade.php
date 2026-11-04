<div class="max-w-[1200px] space-y-6">
    <x-admin-section-card icon="device-phone-mobile" title="Mobile App Downloads"
        description="Manage the Android and iOS app links shown in the ecommerce storefront footer.">
        <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <flux:field>
                <flux:label>Google Play Store URL</flux:label>
                <flux:input wire:model="googlePlayUrl" type="url" placeholder="https://play.google.com/store/apps/details?id=..." />
                <flux:description>Android app download link. Leave blank to hide this badge.</flux:description>
                <flux:error name="googlePlayUrl" />
            </flux:field>

            <flux:field>
                <flux:label>Apple App Store URL</flux:label>
                <flux:input wire:model="appStoreUrl" type="url" placeholder="https://apps.apple.com/app/..." />
                <flux:description>iPhone and iPad app download link. Leave blank to hide this badge.</flux:description>
                <flux:error name="appStoreUrl" />
            </flux:field>
        </div>
    </x-admin-section-card>

    <div>
        <flux:button variant="primary" size="sm" wire:click="save" wire:loading.attr="disabled">
            Save Settings
        </flux:button>
    </div>
</div>
