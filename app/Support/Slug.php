<?php

namespace App\Support;

use App\Models\Page;
use App\Models\SlugRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

/**
 * Single source of truth for slug formatting and uniqueness. Every slug lives in
 * the one `slugs` table (App\Models\SlugRecord, reached through the HasSlug
 * concern) and is unique across ALL of it, so a page, a service, a brand and a
 * job post can never share an address. Products, Posts, ProductCategories and
 * PostCategories have no slug of their own either; they read it via an accessor
 * that proxies to their paired Page (see each model's slug() accessor), and every
 * admin Form/REST controller writes the typed slug onto the paired Page only.
 * Uniqueness is therefore a check against `slugs`, ignoring the record's own row.
 */
class Slug
{
    /**
     * Hyphen separator, special characters stripped — e.g. "Men's Shoes!"
     * becomes "mens-shoes", "Café Menu" becomes "cafe-menu".
     */
    public static function make(string $value): string
    {
        return Str::slug($value, '-');
    }

    /**
     * Lowercases a manually-typed slug. make() already returns lowercase, but
     * an admin can type directly into the slug field with any casing — this
     * is the single place that normalizes it before it ever reaches the DB.
     */
    public static function lower(string $slug): string
    {
        return Str::lower($slug);
    }

    /**
     * Validation rules for a slug field: the slug must not exist in `slugs`,
     * except on the row the record being edited already owns.
     *
     * @param  int|null  $recordId  id of the record being edited, null when creating
     * @param  class-string<Model>  $model  which model that id belongs to (a Page by default)
     * @return array<int, Unique>
     */
    public static function uniqueRules(?int $recordId, string $model = Page::class): array
    {
        return [Rule::unique('slugs', 'slug')->ignore(SlugRecord::idFor($model, $recordId))];
    }

    /**
     * Same check as uniqueRules(), but as a plain boolean for a live
     * red/green indicator while the admin is still typing — an empty slug
     * counts as available (nothing to flag yet).
     */
    public static function isAvailable(string $slug, ?int $recordId, string $model = Page::class): bool
    {
        return $slug === '' || ! static::taken($slug, $model, $recordId);
    }

    /** Whether any record other than the given one already owns this slug. */
    public static function taken(string $slug, ?string $model = null, ?int $exceptId = null): bool
    {
        return SlugRecord::where('slug', $slug)
            ->when($model !== null && $exceptId !== null, fn ($q) => $q->whereNot(
                fn ($own) => $own->where('sluggable_type', $model)->where('sluggable_id', $exceptId)
            ))
            ->exists();
    }

    /**
     * The first of $base, $base-2, $base-3, ... that no other record owns. For
     * slugs the system makes up itself; a hand-typed one is validated instead.
     */
    public static function unique(string $base, ?Model $for = null): string
    {
        $slug = $base;

        for ($suffix = 2; static::taken($slug, $for?->getMorphClass(), $for?->getKey()); $suffix++) {
            $slug = $base.'-'.$suffix;
        }

        return $slug;
    }
}
