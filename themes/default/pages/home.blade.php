{{--
    Default theme — the "Home" settings group, shown under Page Settings → Home.

    One file per group listed in theme.json `pages` ({"key": "home"}): the
    Page Settings card includes pages/{key}.blade.php. Binds to the screen's settings bag;
    Every section of the group is one <x-admin-collapse-section> card.
    Persists to this theme's theme.json and is read by views/index.blade.php with
        \App\Support\ThemeSettings::text('default', 'theme_default_intro_heading')
--}}
<x-admin-collapse-section wire:key="default-home-intro" icon="document-text" title="Intro" description="The heading and text shown on the home page.">
<div class="grid grid-cols-1 gap-5">
    <flux:field>
        <flux:label>Intro Heading<x-field-hint text="The headline rendered on the public homepage." /></flux:label>
        <flux:input wire:model="settings.theme_default_intro_heading" placeholder="Welcome to {{ config('app.name') }}" />
    </flux:field>

    <flux:field>
        <flux:label>Intro Text<x-field-hint text="The supporting line under the heading." /></flux:label>
        <flux:textarea wire:model="settings.theme_default_intro_text" class="h-24" placeholder="A short description of the site." />
    </flux:field>
</div>
</x-admin-collapse-section>
