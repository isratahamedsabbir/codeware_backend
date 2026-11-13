<?php

namespace App\Concerns;

use App\Models\Seo;
use Illuminate\Database\Eloquent\Builder;
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
 * Reads lazy-load the relation once per page, so a list that prints SEO values
 * must eager-load it: `Page::with('seo')`.
 */
trait HasSeo
{
    /** @var array<string, mixed> SEO values written since the last save. */
    protected array $pendingSeo = [];

    public function seo(): HasOne
    {
        return $this->hasOne(Seo::class);
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

    /** A SEO field as the page presents it: pending write first, then the row. */
    public function seoAttribute(string $key): mixed
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

    /** @return array<string, string|null> a translatable SEO field, by locale */
    public function seoTranslations(string $key): array
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

    /** Pages that may be indexed: no row, or a row that does not say noindex. */
    public function scopeIndexable(Builder $query): Builder
    {
        return $query->whereDoesntHave('seo', fn (Builder $q) => $q->where('no_index', true));
    }
}
