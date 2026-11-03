<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * One row of the `slugs` table: the URL slug of one record, whichever table it
 * lives in (Page, Service, Voucher, JobPost, ProductBrand, Tag). `slug` is unique
 * across the whole table, which is what stops two kinds of record from ever
 * sharing an address. Records reach theirs through the HasSlug concern.
 *
 * Named SlugRecord rather than Slug so it can't be confused with
 * App\Support\Slug, the formatting helper.
 */
class SlugRecord extends Model
{
    protected $table = 'slugs';

    protected $fillable = ['slug', 'sluggable_type', 'sluggable_id'];

    public function sluggable(): MorphTo
    {
        return $this->morphTo();
    }

    /** The id of the slugs row a record owns, or null when it has none (yet). */
    public static function idFor(string $type, ?int $id): ?int
    {
        return $id === null ? null : static::where('sluggable_type', $type)->where('sluggable_id', $id)->value('id');
    }
}
