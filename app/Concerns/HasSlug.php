<?php

namespace App\Concerns;

use App\Models\Builders\SlugAwareBuilder;
use App\Models\SlugRecord;
use App\Support\Locale;
use App\Support\Slug;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Str;

/**
 * Gives a model its URL slug through the shared `slugs` table (App\Models\SlugRecord)
 * instead of a `slug` column of its own.
 *
 * - `$model->slug` reads it; `$model->slug = 'x'` / `fill(['slug' => 'x'])` queues a
 *   new one, written when the model is saved. The `slugRecord` relation is loaded
 *   with every query, so reading it costs no extra query per row.
 * - A model saved with no slug gets one made from its title/name; if that is
 *   taken by anything in the slugs table it gets `-2`, `-3`, ... Only a slug
 *   typed by hand can collide, and that is rejected by the table's unique index
 *   (the admin forms validate against it first — see Slug::uniqueRules()).
 * - An existing slug is never regenerated just because the title changed.
 * - `where('slug', ...)` keeps working on the model's queries (SlugAwareBuilder).
 *
 * The slug stays in the table when a soft-deleted record is trashed (so a restore
 * gets its address back) and goes with the record when it is force-deleted.
 *
 * Models that don't own a slug but hang off a Page (Product, Post, the categories)
 * keep proxying to `$this->page->slug` — the Page is what the storefront routes on.
 */
trait HasSlug
{
    /** A slug assigned in memory, waiting for the model to be saved. */
    protected ?string $pendingSlug = null;

    public static function bootHasSlug(): void
    {
        static::saved(fn ($model) => $model->persistSlug());

        static::deleted(function ($model) {
            if (! method_exists($model, 'isForceDeleting') || $model->isForceDeleting()) {
                $model->slugRecord()->delete();
            }
        });
    }

    public function initializeHasSlug(): void
    {
        $this->with = array_values(array_unique([...$this->with, 'slugRecord']));
        $this->append('slug');
    }

    public function newEloquentBuilder($query): SlugAwareBuilder
    {
        return new SlugAwareBuilder($query);
    }

    public function slugRecord(): MorphOne
    {
        return $this->morphOne(SlugRecord::class, 'sluggable');
    }

    public function getSlugAttribute(): ?string
    {
        return $this->pendingSlug ?? $this->slugRecord?->slug;
    }

    public function setSlugAttribute(mixed $value): void
    {
        $this->pendingSlug = blank($value) ? null : Slug::lower(trim((string) $value));
    }

    /** Writes the queued slug, or makes one if the record has none yet. */
    protected function persistSlug(): void
    {
        $slug = $this->pendingSlug;
        $this->pendingSlug = null;

        $current = $this->slugRecord()->first();

        if ($slug === null) {
            if ($current) {
                return;
            }

            $slug = Slug::unique(Slug::make($this->slugSource()) ?: $this->slugFallback(), $this);
        } elseif ($current?->slug === $slug) {
            return;
        }

        $this->slugRecord()->updateOrCreate([], ['slug' => $slug]);
        $this->unsetRelation('slugRecord');
    }

    /** The text a new slug is made from: the primary-locale title or name. */
    protected function slugSource(): string
    {
        foreach (['title', 'name'] as $field) {
            if (! array_key_exists($field, $this->attributes)) {
                continue;
            }

            $value = in_array($field, $this->translatable ?? [], true)
                ? ($this->getTranslation($field, Locale::primary(), false) ?: $this->getTranslation($field, 'en', false))
                : $this->attributes[$field];

            return (string) (is_array($value) ? reset($value) : $value);
        }

        return '';
    }

    /** What a record whose title has no Latin letters (so slugifies to nothing) is called. */
    protected function slugFallback(): string
    {
        return Str::kebab(class_basename($this));
    }
}
