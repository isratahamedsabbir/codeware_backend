{{--
    News theme settings. The wire:key is load-bearing: theme settings partials are
    swapped into one slot, and an unkeyed root keeps whichever x-data came first.
--}}
<div wire:key="theme-settings-{{ $themeSlug }}" class="grid grid-cols-1 gap-5">
    <flux:field>
        <flux:label>Masthead tagline<x-field-hint text="A short line in the top bar beside today's date, e.g. 'Independent reporting since 2020'. Leave empty to show only the date." /></flux:label>
        <flux:input wire:model="settings.theme_news_tagline" placeholder="Independent reporting you can trust" />
    </flux:field>

    <flux:field>
        <flux:label>Accent color<x-field-hint text="Category labels, links and the masthead rule. Leave empty for the default red." /></flux:label>
        <flux:input type="color" wire:model="settings.theme_news_accent_color" />
    </flux:field>

    <x-admin-font-picker
        model="settings.theme_news_font"
        :value="$settings['theme_news_font'] ?? ''"
        own-face="plus-jakarta"
        own-label="Plus Jakarta Sans"
        hint="Applies to this theme's public pages only. Headlines keep their serif face either way."
        description="Theme default keeps Plus Jakarta Sans for body text. Anything else replaces it for this theme alone." />
</div>

@fluxScripts
