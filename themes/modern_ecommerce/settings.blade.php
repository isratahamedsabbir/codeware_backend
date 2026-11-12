{{--
    Modern Ecommerce theme settings. The wire:key is load-bearing: theme settings
    partials are swapped into one slot, and an unkeyed root keeps the first x-data.
--}}
<div wire:key="theme-settings-{{ $themeSlug }}" class="grid grid-cols-1 gap-5">
    <flux:field>
        <flux:label>Accent color<x-field-hint text="Buttons, prices, badges and links. Leave empty for the default red." /></flux:label>
        <flux:input type="color" wire:model="settings.theme_modern_ecommerce_accent_color" />
    </flux:field>

    <x-admin-font-picker
        model="settings.theme_modern_ecommerce_font"
        :value="$settings['theme_modern_ecommerce_font'] ?? ''"
        own-face="plus-jakarta"
        own-label="Plus Jakarta Sans"
        hint="Applies to this theme's public pages only."
        description="Theme default keeps Plus Jakarta Sans. Anything else replaces it for this theme alone." />
</div>

@fluxScripts
