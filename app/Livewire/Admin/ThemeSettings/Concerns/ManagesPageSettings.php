<?php

namespace App\Livewire\Admin\ThemeSettings\Concerns;

use App\Models\CmsSection;
use App\Models\Page;
use App\Models\Setting;
use App\Support\AdminActivity;
use App\Support\Features;
use App\Support\HeroSlides;
use App\Support\Locale;
use App\Support\ThemeSettings;
use App\Support\Themes;
use Illuminate\Support\Collection;

/**
 * Theme Settings → the "Page Settings" card (mixed into the screen's component, so
 * it shares its state, uploads and single Save bar): the pages the live theme serves on
 * the left, and — for the page you pick — its title and every section's content
 * on the right, editable in place.
 *
 * Theme Settings (the other card) is the global look: colours, typography. This
 * is content: a page's title, and per section each card's image, title,
 * subtitle, description, button and text colour, plus the values of the
 * section's constants.
 *
 * Which pages: the home page when the theme ships an index template, and every
 * standalone page (About, Contact, ...) when it ships a `page` template — the
 * same rule the storefront uses to answer or 404 a URL.
 *
 * A theme can add its own built-in home sections (a hero slider, promo tiles)
 * by shipping page-settings.blade.php at its root; that fragment binds to
 * $heroSlides and $promo below and is shown at the top of the Home page.
 *
 * Structure — new sections, renamed sections, constant keys, slug, SEO, the
 * page builder — stays in Pages / CMS, which the page panel links to.
 */
trait ManagesPageSettings
{
    /** Card fields editable here, in display order. */
    private const CARD_FIELDS = ['image', 'title', 'subtitle', 'description', 'button_label', 'button_url', 'text_color'];

    public ?int $selectedPageId = null;

    /** @var array<int, array<string, string>> page id => locale => title */
    public array $titles = [];

    /** @var array<int, int|string> page id => id of the page whose SEO it uses ('' = its own) */
    public array $seoSources = [];

    /** @var array<int, array<int, array<string, ?string>>> section id => cards */
    public array $cards = [];

    /** @var array<int, array<int, string>> section id => constant index => value */
    public array $values = [];

    /** @var array<int, array<string, mixed>> */
    public array $heroSlides = [];

    /** @var array<int, array{image: string, link: string}> */
    public array $promo = [];

    protected function loadPageSettings(): void
    {
        $this->load();

        if (! $this->pages()->contains('id', $this->selectedPageId)) {
            $this->selectedPageId = $this->pages()->first()?->id;
        }
    }

    public function selectPage(int $pageId): void
    {
        if ($this->pages()->contains('id', $pageId)) {
            $this->selectedPageId = $pageId;
        }
    }

    /**
     * The theme whose pages are being edited: the one a card's Settings button
     * opened, or the live theme while no settings dialog is open.
     */
    private function pageTheme(): string
    {
        return $this->settingsFor && Themes::isInstalled($this->settingsFor) ? $this->settingsFor : Themes::active();
    }

    /** Whether the theme being edited ships its own home-page banner editor. */
    private function hasBanners(): bool
    {
        return is_file(Themes::path().'/'.$this->pageTheme().'/page-settings.blade.php');
    }

    /** @return Collection<int, Page> */
    private function pages(): Collection
    {
        if (! Features::enabled('pages')) {
            return collect();
        }

        $theme = $this->pageTheme();
        $pages = collect();

        if (Themes::hasTemplateFor($theme, 'index')) {
            $home = Page::ofType('page')->where('slug', 'home')->first();

            if ($home) {
                $pages->push($home);
            }
        }

        if (Themes::hasTemplateFor($theme, 'page')) {
            $pages = $pages->merge(
                Page::ofType('page')->where('slug', '!=', 'home')->orderBy('sort_order')->orderBy('id')->get()
            );
        }

        return $pages->unique('id')->values();
    }

    /**
     * Every page a page may borrow its SEO from, as id => title, for the SEO
     * dropdown. Pages only: posts, products and categories are not listed.
     *
     * @return Collection<int, string>
     */
    private function seoOptions(): Collection
    {
        $primary = Locale::primary();

        return Page::query()
            ->ofType('page')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->mapWithKeys(fn (Page $page) => [
                $page->id => $page->getTranslation('title', $primary, true) ?: 'Untitled',
            ]);
    }

    /** @return Collection<int, CmsSection> */
    private function sectionsFor(Collection $pages): Collection
    {
        return CmsSection::whereIn('page_id', $pages->pluck('id'))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    private function load(): void
    {
        $pages = $this->pages();

        $this->titles = $pages->mapWithKeys(fn (Page $page) => [$page->id => $page->getTranslations('title')])->all();
        $this->seoSources = $pages->mapWithKeys(fn (Page $page) => [$page->id => $page->seo_page_id ?? ''])->all();

        $sections = $this->sectionsFor($pages);

        $this->cards = $sections->mapWithKeys(fn (CmsSection $section) => [
            $section->id => collect($section->cards ?? [])
                ->map(fn ($card) => collect(self::CARD_FIELDS)->mapWithKeys(fn ($f) => [$f => $card[$f] ?? ($f === 'image' ? null : '')])->all())
                ->values()
                ->all(),
        ])->all();

        $this->values = $sections->mapWithKeys(fn (CmsSection $section) => [
            $section->id => collect($section->constant ?? [])->map(fn ($pair) => (string) ($pair['value'] ?? ''))->values()->all(),
        ])->all();

        if ($this->hasBanners()) {
            $this->heroSlides = HeroSlides::stored() ?: [HeroSlides::blank()];
            $theme = $this->pageTheme();
            $this->promo = [
                ['image' => (string) Setting::get('home_promo_banner_1', ''), 'link' => ThemeSettings::text($theme, 'theme_'.$theme.'_promo_1_link')],
                ['image' => (string) Setting::get('home_promo_banner_2', ''), 'link' => ThemeSettings::text($theme, 'theme_'.$theme.'_promo_2_link')],
            ];
        }
    }

    public function addHeroSlide(): void
    {
        if (count($this->heroSlides) < self::MAX_HERO_SLIDES) {
            $this->heroSlides[] = HeroSlides::blank();
        }
    }

    public function removeHeroSlide(int $index): void
    {
        unset($this->heroSlides[$index]);

        $this->heroSlides = array_values($this->heroSlides) ?: [HeroSlides::blank()];
    }

    public function addCard(int $sectionId): void
    {
        if (! array_key_exists($sectionId, $this->cards)) {
            return;
        }

        $this->cards[$sectionId][] = collect(self::CARD_FIELDS)->mapWithKeys(fn ($f) => [$f => $f === 'image' ? null : ''])->all();
    }

    public function removeCard(int $sectionId, int $index): void
    {
        if (! isset($this->cards[$sectionId][$index])) {
            return;
        }

        unset($this->cards[$sectionId][$index]);
        $this->cards[$sectionId] = array_values($this->cards[$sectionId]);
    }

    /** @return array{0: array<string, mixed>, 1: array<string, string>} */
    protected function pageSettingsRules(): array
    {
        $primary = Locale::primary();

        $rules = [
            'titles.*' => ['array'],
            'seoSources.*' => ['nullable', 'integer', \Illuminate\Validation\Rule::exists('pages', 'id')->where('type', 'page')],
            "titles.*.{$primary}" => ['required', 'string', 'max:255'],
            'cards.*.*.image' => ['nullable', 'string'],
            'cards.*.*.title' => ['nullable', 'string', 'max:255'],
            'cards.*.*.subtitle' => ['nullable', 'string', 'max:255'],
            'cards.*.*.description' => ['nullable', 'string'],
            'cards.*.*.button_label' => ['nullable', 'string', 'max:80'],
            'cards.*.*.button_url' => ['nullable', 'string', 'max:500'],
            'cards.*.*.text_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{3,8}$/'],
            'values.*.*' => ['nullable', 'string', 'max:1000'],
            'promo.*.link' => ['nullable', 'string', 'max:500'],
            'promo.*.image' => ['nullable', 'string'],
        ];

        foreach (Locale::codes() as $code) {
            if ($code !== $primary) {
                $rules["titles.*.{$code}"] = ['nullable', 'string', 'max:255'];
            }
        }

        return [$rules, ['cards.*.*.text_color.regex' => 'Use a hex colour like #1e7bc4.']];
    }

    protected function savePageSettings(): void
    {
        $pages = $this->pages();
        $sections = $this->sectionsFor($pages);
        $primary = Locale::primary();

        // Only rows this card loaded may be written: ids arrive from the browser,
        // so a page or section that is not one of the theme's own is ignored
        // rather than trusted.
        foreach ($pages as $page) {
            if (! isset($this->titles[$page->id])) {
                continue;
            }

            $title = array_filter(array_map(fn ($v) => is_string($v) ? trim($v) : '', $this->titles[$page->id]));

            if ($title !== [] && $title !== $page->getTranslations('title')) {
                $page->update(['title' => $title]);
                AdminActivity::log('updated', "Page #{$page->id}: {$page->getTranslation('title', $primary, false)} (Page Settings)");
            }
        }

        // The SEO each page uses: another page's, or (empty) its own and then the
        // site-wide defaults. A page cannot name itself — that is the same as empty.
        foreach ($pages as $page) {
            if (! array_key_exists($page->id, $this->seoSources)) {
                continue;
            }

            $chosen = filled($this->seoSources[$page->id]) ? (int) $this->seoSources[$page->id] : null;
            $chosen = $chosen === $page->id ? null : $chosen;

            if ($chosen !== ($page->seo_page_id ? (int) $page->seo_page_id : null)) {
                $page->update(['seo_page_id' => $chosen]);
                AdminActivity::log('updated', "Page #{$page->id}: SEO source ".($chosen ? "set to page #{$chosen}" : 'cleared').' (Page Settings)');
            }
        }

        foreach ($sections as $section) {
            if (! isset($this->cards[$section->id])) {
                continue;
            }

            $cards = collect($this->cards[$section->id])
                ->map(function ($card) {
                    $row = [];

                    foreach (self::CARD_FIELDS as $field) {
                        $row[$field] = $field === 'image'
                            ? (filled($card[$field] ?? null) ? $card[$field] : null)
                            : (string) ($card[$field] ?? '');
                    }

                    return $row;
                })
                ->values()
                ->all();

            // Keys and types stay as stored; only the value is edited here.
            $constant = collect($section->constant ?? [])
                ->values()
                ->map(fn ($pair, $i) => [...$pair, 'value' => (string) ($this->values[$section->id][$i] ?? ($pair['value'] ?? ''))])
                ->all();

            if ($cards !== ($section->cards ?? []) || $constant !== ($section->constant ?? [])) {
                $section->update(['cards' => $cards, 'constant' => $constant]);
                AdminActivity::log('updated', "CMS section: {$section->name} (Page Settings)");
            }
        }

        if ($this->hasBanners()) {
            $this->saveBanners();
        }

    }

    private function saveBanners(): void
    {
        // A slide without an image isn't shown, so it isn't kept either.
        $slides = array_values(array_filter(
            array_map(HeroSlides::normalize(...), $this->heroSlides),
            fn (array $slide) => $slide['image'] !== '',
        ));

        Setting::set('home_hero_slides', json_encode($slides));
        Setting::set('home_hero_image', $slides[0]['image'] ?? '');

        $theme = $this->pageTheme();

        Setting::set('home_promo_banner_1', (string) ($this->promo[0]['image'] ?? ''));
        Setting::set('home_promo_banner_2', (string) ($this->promo[1]['image'] ?? ''));

        if (ThemeSettings::exists($theme)) {
            ThemeSettings::merge($theme, [
                "theme_{$theme}_promo_1_link" => (string) ($this->promo[0]['link'] ?? ''),
                "theme_{$theme}_promo_2_link" => (string) ($this->promo[1]['link'] ?? ''),
            ]);
        }

        AdminActivity::log('updated', 'Home banners updated (Page Settings)');
    }

    /**
     * What the Page Settings card's partial reads, prefixed `ps` so it can never
     * collide with a variable a theme's own settings partial sets in the same scope.
     *
     * @return array<string, mixed>
     */
    protected function pageSettingsData(): array
    {
        $pages = $this->pages();
        $selected = $pages->firstWhere('id', $this->selectedPageId) ?? $pages->first();

        return [
            'psPages' => $pages,
            'psSelected' => $selected,
            'psSections' => $selected ? $this->sectionsFor(collect([$selected])) : collect(),
            'psLanguages' => Locale::translatable(),
            'psPrimary' => Locale::primary(),
            'psSeoOptions' => $this->seoOptions(),
            'psTheme' => $this->pageTheme(),
            'psFeatureOn' => Features::enabled('pages'),
            'psBanners' => $this->hasBanners() ? Themes::viewNamespace($this->pageTheme()).'::page-settings' : null,
        ];
    }
}
