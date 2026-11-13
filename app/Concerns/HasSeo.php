<?php

namespace App\Concerns;

use App\Models\Seo;
use App\Models\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A model that owns one `seos` row: the page's search and social metadata.
 *
 * The twelve SEO fields stay readable and writable on the page itself —
 * `$page->seo_title`, `$page->no_index`, `$page->fill(['og_image' => ...])`,
 * `$page->update([...])` — exactly as when they were columns, so no caller had to
 * change. Reads come from the related row; writes are held until the page is
 * saved, then land on its row (created on first write). A page that was never
 * given SEO copy has a blank row (created with the page).
 *
 * The model using this must call seoAttribute() from getAttribute(), queueSeo()
 * from setAttribute() and persistSeo() from its `saved` event (see Page): the
 * Eloquent and spatie/laravel-translatable overrides it has to sit next to cannot
 * be resolved inside a trait.
 *
 * A page may also borrow another page's SEO: `pages.seo_page_id` names the page
 * whose SEO this one uses. Reads (`$page->seo_title`, the public site, the API)
 * then come from that page's row; with nothing chosen they come from the page's
 * own row, and a field blank there falls through to the site-wide defaults in
 * Settings (see SeoResolver). The page forms and the admin API edit the page's
 * *own* row either way — ownSeoAttribute() / ownSeoTranslations() — so choosing
 * a source never copies anything into it, and un-choosing brings it straight back.
 *
 * Reads lazy-load the relation once per page, so a list that prints SEO values
 * must eager-load it: `Page::with('seo', 'seoSource.seo')`.
 */
trait HasSeo
{
    /** @var array<string, mixed> SEO values written since the last save. */
    protected array $pendingSeo = [];

    public function seo(): HasOne
    {
        return $this->hasOne(Seo::class);
    }

    /** The page whose SEO this page borrows (`seo_page_id`), if one was chosen. */
    public function seoSource(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'seo_page_id');
    }

    /**
     * The page whose SEO row the public site reads for this one: the chosen
     * source, or the page itself when none is chosen (or the chosen one is gone).
     */
    public function seoSourcePage(): Page
    {
        if (! $this->getAttribute('seo_page_id') || $this->getAttribute('seo_page_id') === $this->getKey()) {
            return $this;
        }

        return $this->getRelationValue('seoSource') ?? $this;
    }

    public static function isSeoField(string $key): bool
    {
        return in_array($key, Seo::FIELDS, true);
    }

    public static function isTranslatableSeoField(string $key): bool
    {
        return in_array($key, Seo::TRANSLATABLE, true);
    }

    /** The page's row, or null when it has none. */
    public function seoRecord(): ?Seo
    {
        return $this->exists ? $this->getRelationValue('seo') : null;
    }

    /** A SEO field as the page presents it: its source page's value, else its own. */
    public function seoAttribute(string $key): mixed
    {
        return $this->seoSourcePage()->ownSeoAttribute($key);
    }

    /** @return array<string, string|null> a translatable SEO field, by locale — as presented */
    public function seoTranslations(string $key): array
    {
        return $this->seoSourcePage()->ownSeoTranslations($key);
    }

    /** A SEO field of this page's own row (pending write first) — what its forms edit. */
    public function ownSeoAttribute(string $key): mixed
    {
        if (array_key_exists($key, $this->pendingSeo)) {
            $pending = $this->pendingSeo[$key];

            if (is_array($pending) && static::isTranslatableSeoField($key)) {
                $locale = app()->getLocale();

                return $pending[$locale] ?? $pending[config('app.fallback_locale')] ?? (reset($pending) ?: null);
            }

            return $pending;
        }

        $record = $this->seoRecord();

        if (! $record) {
            return in_array($key, ['no_index', 'no_follow'], true) ? false : null;
        }

        // A row just created in this request has not read its column defaults back.
        return in_array($key, ['no_index', 'no_follow'], true) ? (bool) $record->{$key} : $record->{$key};
    }

    /** @return array<string, string|null> a translatable field of this page's own row, by locale */
    public function ownSeoTranslations(string $key): array
    {
        if (array_key_exists($key, $this->pendingSeo) && is_array($this->pendingSeo[$key])) {
            return $this->pendingSeo[$key];
        }

        return $this->seoRecord()?->getTranslations($key) ?? [];
    }

    public function queueSeo(string $key, mixed $value): void
    {
        $this->pendingSeo[$key] = $value;
    }

    /**
     * Gives a newly created page its `seos` row (blank until someone fills it in)
     * and writes any queued SEO values onto it. Called from the saved event.
     *
     * Every page owns a row from the moment it exists — whatever made it: the
     * page form, a post, a product, a category, an import, a seeder — so the SEO
     * screen lists every page and nothing has to guess whether one is missing.
     */
    public function persistSeo(): void
    {
        if ($this->pendingSeo === [] && ! $this->wasRecentlyCreated) {
            return;
        }

        $pending = $this->pendingSeo;
        $this->pendingSeo = [];

        $record = $this->seoRecord() ?? new Seo(['page_id' => $this->getKey()]);

        foreach ($pending as $key => $value) {
            if (static::isTranslatableSeoField($key) && is_array($value)) {
                $record->setTranslations($key, $value);
            } else {
                $record->{$key} = $value;
            }
        }

        $record->save();
        $this->setRelation('seo', $record);
    }

    /**
     * Pages that may be indexed, by the SEO they actually present: the chosen
     * source page's row when one is chosen, the page's own row otherwise — and a
     * row that does not say noindex.
     */
    public function scopeIndexable(Builder $query): Builder
    {
        $noIndex = fn (Builder $q) => $q->where('no_index', true);

        return $query->where(function (Builder $q) use ($noIndex) {
            $q->where(fn (Builder $own) => $own->whereNull('seo_page_id')->whereDoesntHave('seo', $noIndex))
                ->orWhere(fn (Builder $borrowed) => $borrowed->whereNotNull('seo_page_id')->whereDoesntHave('seoSource.seo', $noIndex));
        });
    }
}
