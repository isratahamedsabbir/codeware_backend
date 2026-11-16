{{--
    Default theme — the "Home" settings group, shown under Page Settings → Home.

    One file per group listed in theme.json `pages` ({"key": "home"}): the
    Page Settings card includes pages/{key}.blade.php. Binds to the screen's settings bag;
    every section of the group is one collapse card.
    Persists to this theme's theme.json and is read by views/index.blade.php with
        \App\Support\ThemeSettings::localized('default', 'theme_default_intro_heading')
    Text fields come in an English and a Bangla copy (the Bangla key ends in _bn).
--}}
<x-admin-collapse-section wire:key="default-home-intro" icon="document-text" title="Intro" description="The heading and text shown on the home page." :locales="['en', 'bn']">
<div class="grid grid-cols-1 gap-5">
    <x-admin-locale-panel code="en" class="grid grid-cols-1 gap-5">
        <flux:field>
            <flux:label badge="en">Intro Heading<x-field-hint text="The headline rendered on the public homepage." /></flux:label>
            <flux:input wire:model="settings.theme_default_intro_heading" placeholder="Welcome to {{ config('app.name') }}" />
        </flux:field>

        <flux:field>
            <flux:label badge="en">Intro Text<x-field-hint text="The supporting line under the heading." /></flux:label>
            <flux:textarea wire:model="settings.theme_default_intro_text" class="h-24" placeholder="A short description of the site." />
        </flux:field>
    </x-admin-locale-panel>

    <x-admin-locale-panel code="bn" class="grid grid-cols-1 gap-5">
        <flux:field>
            <flux:label badge="bn">Intro Heading<x-field-hint text="Bangla headline. Blank falls back to the English one." /></flux:label>
            <flux:input wire:model="settings.theme_default_intro_heading_bn" placeholder="Intro Heading (বাংলা)" />
        </flux:field>

        <flux:field>
            <flux:label badge="bn">Intro Text<x-field-hint text="Bangla supporting line. Blank falls back to the English one." /></flux:label>
            <flux:textarea wire:model="settings.theme_default_intro_text_bn" class="h-24" placeholder="Intro Text (বাংলা)" />
        </flux:field>
    </x-admin-locale-panel>
</div>
</x-admin-collapse-section>
