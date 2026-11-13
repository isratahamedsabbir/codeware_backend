<?php

namespace App\Livewire\Admin\Seo;

use App\Concerns\HasSeoFields;
use App\Concerns\HasTranslatableFields;
use App\Models\Page;
use App\Models\Setting;
use App\Support\AdminActivity;
use App\Support\Locale;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Admin → SEO: the SEO of every page in one place, and the site-wide defaults
 * every page without SEO copy of its own inherits.
 *
 * Each page (a product's, post's or category's included — they all own a page
 * row) has at most one `seos` row; this screen lists them all with what is
 * missing, and edits one in a dialog with the same fields the page forms use.
 * The defaults below the list live in Settings, as before.
 */
class Index extends Component
{
    use HasSeoFields, HasTranslatableFields;
    use WithPagination;

    /** Page types the manager lists, with their label. */
    public const TYPES = [
        'page' => 'Page',
        'post' => 'Post',
        'product' => 'Product',
        'post_category' => 'Blog category',
        'product_category' => 'Product category',
    ];

    public string $search = '';

    public string $typeFilter = '';

    /** '' | missing_title | missing_description | noindex */
    public string $statusFilter = '';

    public ?int $editingPageId = null;

    /** The edited page's slug: the canonical path follows it (see HasSeoFields). */
    public string $slug = '';

    public string $editingTitle = '';

    public string $editingType = '';

    public array $settings = [];

    /** @var array<int, string> */
    public array $canonicalUrls = [];

    public function mount(): void
    {
        foreach (Setting::where('group', 'seo')->get() as $setting) {
            $this->settings[$setting->key] = Setting::isTranslatable($setting->key)
                ? array_replace(
                    array_fill_keys(Locale::translatable()->pluck('code')->all(), ''),
                    Setting::translations($setting->value)
                )
                : ($setting->value ?? '');
        }

        $this->canonicalUrls = json_decode(Setting::get('seo_canonical_urls', '[]') ?: '[]', true) ?: [];

        if (empty($this->canonicalUrls)) {
            $this->canonicalUrls = [''];
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    /** @return Builder<Page> */
    private function pagesQuery(): Builder
    {
        $query = Page::query()->with(['seo', 'slugRecord'])->whereIn('type', array_keys(self::TYPES));

        if ($this->typeFilter !== '' && array_key_exists($this->typeFilter, self::TYPES)) {
            $query->where('type', $this->typeFilter);
        }

        if (trim($this->search) !== '') {
            $term = '%'.addcslashes(trim($this->search), '%_\\').'%';

            $query->where(function (Builder $q) use ($term) {
                $q->where('title', 'like', $term)
                    ->orWhereHas('slugRecord', fn (Builder $s) => $s->where('slug', 'like', $term))
                    ->orWhereHas('seo', fn (Builder $s) => $s->where('seo_title', 'like', $term));
            });
        }

        match ($this->statusFilter) {
            'missing_title' => $query->whereDoesntHave('seo', fn (Builder $q) => $q->whereNotNull('seo_title')),
            'missing_description' => $query->whereDoesntHave('seo', fn (Builder $q) => $q->whereNotNull('seo_description')),
            'noindex' => $query->whereHas('seo', fn (Builder $q) => $q->where('no_index', true)),
            default => null,
        };

        return $query->orderBy('type')->orderBy('sort_order')->orderBy('id');
    }

    /** How many pages lack each thing, for the summary strip. */
    private function counts(): array
    {
        $base = fn () => Page::query()->whereIn('type', array_keys(self::TYPES));

        return [
            'total' => $base()->count(),
            'missing_title' => $base()->whereDoesntHave('seo', fn (Builder $q) => $q->whereNotNull('seo_title'))->count(),
            'missing_description' => $base()->whereDoesntHave('seo', fn (Builder $q) => $q->whereNotNull('seo_description'))->count(),
            'noindex' => $base()->whereHas('seo', fn (Builder $q) => $q->where('no_index', true))->count(),
        ];
    }

    public function editPage(int $pageId): void
    {
        $page = Page::with('seo')->whereIn('type', array_keys(self::TYPES))->findOrFail($pageId);

        $this->resetValidation();
        $this->editingPageId = $page->id;
        $this->editingTitle = (string) $page->getTranslation('title', Locale::primary(), true);
        $this->editingType = (string) $page->type;
        $this->slug = (string) $page->slug;
        $this->hydrateSeoFieldsFromPage($page);
    }

    public function closeEdit(): void
    {
        $this->editingPageId = null;
        $this->resetValidation();
    }

    public function savePageSeo(): void
    {
        $page = Page::whereIn('type', array_keys(self::TYPES))->findOrFail($this->editingPageId);

        $this->validate(array_merge(
            $this->getRules(),
            $this->translatableRules(self::seoTranslatableFields()),
        ));

        $page->update($this->seoPagePayload());

        AdminActivity::log('updated', "SEO: {$this->editingTitle} (page #{$page->id})");
        $this->dispatch('notify', message: 'SEO saved.');
        $this->closeEdit();
    }

    public function addCanonicalUrl(): void
    {
        $this->canonicalUrls[] = '';
    }

    public function removeCanonicalUrl(int $index): void
    {
        unset($this->canonicalUrls[$index]);
        $this->canonicalUrls = array_values($this->canonicalUrls);

        if (empty($this->canonicalUrls)) {
            $this->canonicalUrls = [''];
        }
    }

    public function save(): void
    {
        foreach ($this->settings as $key => $value) {
            Setting::set(
                $key,
                Setting::isTranslatable($key) ? json_encode((object) $value) : $value
            );
        }

        $urls = array_values(array_filter($this->canonicalUrls, fn ($url) => trim($url) !== ''));
        Setting::set('seo_canonical_urls', json_encode($urls));

        AdminActivity::log('updated', 'SEO settings updated');

        $this->dispatch('notify', message: 'SEO settings saved.');
    }

    public function render()
    {
        return view('livewire.admin.seo.index', [
            'pages' => $this->pagesQuery()->paginate(15),
            'counts' => $this->counts(),
            'types' => self::TYPES,
            'languages' => Locale::translatable(),
        ])->layout('layouts.admin', ['title' => 'SEO']);
    }
}
