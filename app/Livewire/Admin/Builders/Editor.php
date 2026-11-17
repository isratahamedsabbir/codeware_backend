<?php

namespace App\Livewire\Admin\Pages;

use App\Concerns\HasSeoFields;
use App\Models\MediaLibrary;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\PageBuild;
use App\Support\Locale;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

/**
 * GrapesJS visual builder for a page.
 *
 * Saves a separate `page_builds` row per page and locale: `project` is what
 * GrapesJS reloads, html+css is what the themes print. pages.content and
 * pages.puck_data belong to the Puck editor and are never touched.
 */
class Builder extends Component
{
    use HasSeoFields;

    public int $pageId;

    public string $slug = '';

    /** The locale whose design is open in the editor. */
    public string $locale = '';

    public function mount(int $id): void
    {
        $page = Page::where('type', 'page')->findOrFail($id);
        $this->pageId = $page->id;
        $this->slug = $page->slug;

        $this->locale = Locale::primary();
        $this->hydrateSeoFieldsFromPage($page);
    }

    public function save(array $project, string $html, string $css): void
    {
        $this->assertLocaleSupported($this->locale);

        $page = Page::findOrFail($this->pageId);

        PageBuild::updateOrCreate(
            ['page_id' => $page->id, 'locale' => $this->locale],
            ['project' => $project, 'html' => $html, 'css' => $css],
        );

        $this->dispatch('builder-saved');
    }

    /** Switches the editor locale, using the primary design as a starting point for a new translation. */
    public function switchLocale(string $locale): array
    {
        $this->assertLocaleSupported($locale);

        $this->locale = $locale;
        $primary = Locale::primary();
        $page = Page::with('builds')->findOrFail($this->pageId);
        $this->slug = $page->slug;
        $this->hydrateSeoFieldsFromPage($page);

        return [
            'project' => $page->build($locale)?->project ?? $page->build($primary)?->project,
            'title' => $page->getTranslation('title', $locale, false)
                ?: $page->getTranslation('title', $primary, false)
                ?: 'Untitled page',
            'seo' => $this->seoForLocale($page, $locale),
        ];
    }

    public function saveSeo(array $seo): void
    {
        $this->assertLocaleSupported($this->locale);

        $validated = Validator::make($seo, self::seoTranslatableFields() + [
            'og_image' => 'nullable|string|max:255',
            'twitter_image' => 'nullable|string|max:255',
            'no_index' => 'boolean',
            'no_follow' => 'boolean',
            'canonical_base' => 'nullable|string|max:255',
            'canonical_slug' => 'nullable|string|max:255',
        ])->validate();

        $page = Page::findOrFail($this->pageId);
        $this->slug = $page->slug;
        $this->hydrateSeoFieldsFromPage($page);

        foreach (array_keys(self::seoTranslatableFields()) as $field) {
            if (array_key_exists($field, $validated)) {
                $this->{$field}[$this->locale] = $validated[$field];
            }
        }

        foreach (['og_image', 'twitter_image', 'no_index', 'no_follow', 'canonical_base', 'canonical_slug'] as $field) {
            if (array_key_exists($field, $validated)) {
                $this->{$field} = $validated[$field];
            }
        }

        $page->update($this->seoPagePayload());

        $this->dispatch('seo-saved');
    }

    /** Stores the selected component as a reusable block; returns its id so the editor can offer it at once. */
    public function saveBlock(string $name, string $content): int
    {
        $name = trim($name);
        abort_if($name === '' || mb_strlen($name) > 255 || trim($content) === '', 422);

        return PageBlock::create(['name' => $name, 'content' => $content, 'user_id' => auth()->id()])->id;
    }

    public function deleteBlock(int $id): void
    {
        PageBlock::whereKey($id)->delete();

        $this->dispatch('block-deleted', id: $id);
    }

    public function render()
    {
        $page = Page::findOrFail($this->pageId);

        return view('livewire.admin.pages.builder', [
            'page' => $page,
            'assets' => MediaLibrary::where('file_type', 'image')->latest()->limit(80)->get()->map->url->filter()->values(),
            'blocks' => PageBlock::orderBy('name')->get(['id', 'name', 'content']),
            'project' => $page->build($this->locale)?->project,
            'locales' => $this->availableLocales(),
            'seo' => $this->seoForLocale($page, $this->locale),
            'canonicalBases' => $this->canonicalBaseOptions(),
        ])->layout('layouts.builder', ['title' => 'Page Builder']);
    }

    private function seoForLocale(Page $page, string $locale): array
    {
        $seo = [];

        foreach (array_keys(self::seoTranslatableFields()) as $field) {
            $seo[$field] = $page->ownSeoTranslations($field)[$locale] ?? '';
        }

        return $seo + [
            'og_image' => $page->ownSeoAttribute('og_image') ?? '',
            'twitter_image' => $page->ownSeoAttribute('twitter_image') ?? '',
            'no_index' => (bool) $page->ownSeoAttribute('no_index'),
            'no_follow' => (bool) $page->ownSeoAttribute('no_follow'),
            'canonical_base' => $page->ownSeoAttribute('canonical_base') ?? '',
            'canonical_slug' => $page->ownSeoAttribute('canonical_slug') ?? $page->slug,
        ];
    }

    private function assertLocaleSupported(string $locale): void
    {
        abort_unless(in_array($locale, array_column($this->availableLocales(), 'code'), true), 422);
    }

    /** @return array<int, array{code: string, label: string}> */
    private function availableLocales(): array
    {
        $locales = Locale::active()
            ->map(fn ($language) => [
                'code' => $language->code,
                'label' => $language->native_name ?: $language->name,
            ])
            ->values()
            ->all();

        return $locales ?: [[
            'code' => Locale::primary(),
            'label' => Locale::label(Locale::primary()),
        ]];
    }
}
