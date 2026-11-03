<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Where a record's public page lives: `/{prefix}/{slug}`, or just `/{slug}` when
 * the prefix is empty. Set per kind of record at Settings → General → URL
 * structure (rows in the `urls` settings group, `url_prefix_{type}`).
 *
 * This decides the address the site *links to*. It does not move any route: the
 * themes' own `/products/{slug}` and `/blog/{slug}` keep answering, and
 * Themes\SlugController serves whatever is configured here, so an address that
 * was linked to before the setting changed still works.
 */
class UrlStructure
{
    /** type => [label, default prefix, route name its page renders through] */
    public const TYPES = [
        'product' => ['Product', 'products', 'products.show'],
        'post' => ['Post', 'blog', 'blog.post'],
        'service' => ['Service', 'services', null],
    ];

    public static function defaultPrefix(string $type): string
    {
        return self::TYPES[$type][1] ?? '';
    }

    /** The configured prefix, without slashes; '' means the bare /{slug}. */
    public static function prefix(string $type): string
    {
        $value = Setting::get('url_prefix_'.$type);

        return static::normalize($value === null ? static::defaultPrefix($type) : (string) $value);
    }

    public static function normalize(string $prefix): string
    {
        return trim(Slug::lower(trim($prefix)), '/ ');
    }

    /** The path (no leading slash) a record of this type is reached at. */
    public static function path(string $type, string $slug): string
    {
        $prefix = static::prefix($type);

        return ($prefix === '' ? '' : $prefix.'/').$slug;
    }

    public static function url(string $type, string $slug): string
    {
        return url(static::path($type, $slug));
    }

    /** The type whose configured prefix is this one, for an incoming /{prefix}/{slug}. */
    public static function typeForPrefix(string $prefix): ?string
    {
        foreach (array_keys(self::TYPES) as $type) {
            if (static::prefix($type) !== '' && static::prefix($type) === $prefix) {
                return $type;
            }
        }

        return null;
    }
}
