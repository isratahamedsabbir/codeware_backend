{{--
    Default theme — the home page's intro block, shown under Page Settings → Home.

    Included by the Theme Settings screen's Page Settings card when a theme ships
    a page-settings.blade.php at its root. Binds to the screen's settings bag;
    persists to this theme's theme.json and is read by views/index.blade.php with
        \App\Support\ThemeSettings::text('default', 'theme_default_intro_heading')
--}}
<div wire:key="default-home-intro" class="grid grid-cols-1 gap-5">
    <flux:field>
        <flux:label>Intro Heading<x-field-hint text="The headline rendered on the public homepage." /></flux:label>
        <flux:input wire:model="settings.theme_default_intro_heading" placeholder="Welcome to {{ config('app.name') }}" />
    </flux:field>

    <flux:field>
        <flux:label>Intro Text<x-field-hint text="The supporting line under the heading." /></flux:label>
        <flux:textarea wire:model="settings.theme_default_intro_text" class="h-24" placeholder="A short description of the site." />
    </flux:field>
</div>
