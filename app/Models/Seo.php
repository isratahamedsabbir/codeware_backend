<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

/**
 * Search and social metadata for one page: meta title/description, Open Graph,
 * Twitter card, robots switches and canonical. One row per page (`seos.page_id`
 * is unique); a page with no row has no SEO copy of its own and the site-wide
 * defaults (Settings → SEO) apply.
 *
 * Read and written through the page — `$page->seo_title`, `$page->update([
 * 'seo_title' => ...])` — see App\Concerns\HasSeo, so callers never touch this
 * model directly.
 */
class Seo extends Model
{
    use HasTranslations;

    /** Fields stored as a per-locale map. */
    public const TRANSLATABLE = [
        'seo_title', 'seo_description', 'og_title', 'og_description',
        'twitter_title', 'twitter_description',
    ];

    /** Every field a page proxies to this row. */
    public const FIELDS = [
        'seo_title', 'seo_description', 'og_title', 'og_description',
        'twitter_title', 'twitter_description',
        'og_image', 'twitter_image', 'no_index', 'no_follow', 'canonical_base', 'canonical_slug',
    ];

    public array $translatable = self::TRANSLATABLE;

    protected $fillable = [
        'page_id',
        'seo_title', 'seo_description', 'og_title', 'og_description',
        'twitter_title', 'twitter_description',
        'og_image', 'twitter_image', 'no_index', 'no_follow', 'canonical_base', 'canonical_slug',
    ];

    protected $casts = [
        'no_index' => 'boolean',
        'no_follow' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(fn (Seo $seo) => $seo->collapseEmptyTranslations());
    }

    /**
     * Stores a translatable field that is empty in every locale as NULL rather
     * than `[]` or `{"en":null}`, so "was this ever set?" is one null check — for
     * a query, for the manager's Missing badge and for the page forms alike.
     */
    protected function collapseEmptyTranslations(): void
    {
        foreach (self::TRANSLATABLE as $field) {
            $raw = $this->attributes[$field] ?? null;

            if (! is_string($raw) || $raw === '') {
                continue;
            }

            $decoded = json_decode($raw, true);

            if (! is_array($decoded) || array_filter($decoded, 'filled') === []) {
                $this->attributes[$field] = null;
            }
        }
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
