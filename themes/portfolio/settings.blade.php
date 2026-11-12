{{--
    Portfolio theme settings — bound to the Theme Settings screen (Admin →

    Theme Settings) via wire:model="settings.theme_{slug}_*".

    The global look (Typography) is edited here. The one-pager's content — profile,
    projects, experience, skills, testimonials, education, certifications — is page
    content and lives in page-settings.blade.php, edited under Page Settings → Home.
    Every value of either file
    lives in this theme's own theme.json beside this file rather than in the
    database — so the folder can be zipped up and handed to someone else with its
    content still in it. Projects, Experience, Skills and Testimonials used to
    have their own tables and admin CRUD screens; they are ordinary lists here
    now, so the whole portfolio is one place with one save button rather than four
    places an owner has to find.

    Scalar fields persist through the component's generic $settings bag. List
    fields (stats, projects, experience, skills, testimonials, education,
    certifications) bind to the separate $repeaters bag instead, each stored as
    one array in this theme's theme.json. The theme settings component
    discovers them by parsing this file for `setting-key="theme_*"` (which is also
    where it reads each one's `:max` from, so the add button and the save-time cap
    agree) and grows the add/remove/reorder UI for each. See
    App\Livewire\Admin\ThemeSettings\Index.

    Read back by the one-pager through Themes\Portfolio\Support\PortfolioProfile, e.g.
        \Themes\Portfolio\Support\PortfolioProfile::hero()['role']
        \Themes\Portfolio\Support\PortfolioProfile::services()
        \Themes\Portfolio\Support\PortfolioProfile::projects()

    A section with no rows is not rendered on the storefront at all, so leaving
    these empty is a valid state: a smaller finished portfolio beats a
    half-filled one that advertises the gaps.

    Each panel holds one kind of list. Education and Certifications used to share
    a single "Credentials" panel side by side, which left the column a row sat in
    as the only thing saying what it was for — so they are two panels now, and a
    stored browser that still remembers the old tab name is sent to the first of
    the two rather than left looking at nothing.
--}}
{{-- The wire:key is load-bearing, not decoration. This partial and
     ecommerce/settings.blade.php are swapped into the same slot on the Theme
     Settings screen by a plain @include, and both root elements are an
     unkeyed <div x-data="{ tab, open }"> with the same tag. Without a key,
     Livewire morphs one into the other in place, and Alpine keeps the x-data
     object it parsed first — so after switching to ecommerce, this scope is
     still driving the page: `tab` is 'profile', which matches neither of
     ecommerce's 'banners' / 'colors' panels, so Theme Settings looks empty, and
     clicking Colors then reveals only Colors because the stale open() happens to
     set the same property. Keying on the slug makes the node unique, so
     Livewire replaces it and Alpine re-initialises with the right scope. --}}
<div wire:key="theme-settings-{{ $themeSlug }}" class="grid grid-cols-1 gap-5">
    {{-- The face this theme's pages render in. This theme ships Instrument Sans
         and is designed around it, so "Theme default" is the option that keeps
         it looking the way it was built; the rest are there for a deliberate
         change, or for a portfolio that should read in the same face as the rest
         of a multi-theme site. See App\Support\ThemeFont. --}}
    <section class="rounded-xl bg-white p-5 dark:bg-zinc-900">
        <x-admin-font-picker
            model="settings.theme_portfolio_font"
            :value="$settings['theme_portfolio_font'] ?? ''"
            own-face="instrument-sans"
            own-label="Instrument Sans"
            hint="Applies to this theme's public pages only. Every theme keeps its own typeface until you say otherwise."
            description="Theme default keeps Instrument Sans, the font this theme ships with. Anything else replaces it for this theme alone — the other themes are untouched." />
    </section>
</div>

@fluxScripts
