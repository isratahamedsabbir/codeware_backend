<?php

namespace App\Support\Seo;

use App\Models\Page;
use App\Models\Seo;

/**
 * The SEO keys the admin API accepts on anything that owns a page — pages,
 * posts, products, categories — so every one of them takes the same keys the
 * same way.
 *
 * Every key is optional and all-or-nothing per key: a key that is in the request
 * is written to the page's `seos` row (null clears it), a key that is not in the
 * request leaves the stored value alone. A request with no SEO keys at all never
 * touches the row.
 *
 *     seo_title, seo_description, og_title, og_description,
 *     twitter_title, twitter_description      a string (the current language) or
 *                                             {"en": "...", "bn": "..."}
 *     og_image, twitter_image                 a URL / media path
 *     no_index, no_follow                     boolean
 *     canonical_base, canonical_slug          string
 */
final class SeoInput
{
    /** Every key, in the order the API documents them. */
    public static function keys(): array
    {
        return Seo::FIELDS;
    }

    /**
     * Validation rules for the SEO keys, ready to spread into a request's rules.
     *
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        $rules = [];

        foreach (Seo::TRANSLATABLE as $field) {
            $max = in_array($field, ['seo_description', 'og_description', 'twitter_description'], true) ? null : 255;

            $rules[$field] = ['sometimes', 'nullable', function (string $attribute, mixed $value, \Closure $fail) use ($max) {
                if (is_string($value)) {
                    if ($max !== null && mb_strlen($value) > $max) {
                        $fail("The {$attribute} may not be greater than {$max} characters.");
                    }

                    return;
                }

                if (! is_array($value)) {
                    $fail("The {$attribute} must be a string, or an object of translations such as {\"en\": \"...\"}.");
                }
            }];

            // Only checked when a translations object was sent.
            $rules[$field.'.*'] = array_filter(['nullable', 'string', $max ? "max:{$max}" : null]);
        }

        return $rules + [
            'og_image' => 'sometimes|nullable|string|max:2048',
            'twitter_image' => 'sometimes|nullable|string|max:2048',
            'no_index' => 'sometimes|boolean',
            'no_follow' => 'sometimes|boolean',
            'canonical_base' => 'sometimes|nullable|string|max:255',
            'canonical_slug' => 'sometimes|nullable|string|max:255',
        ];
    }

    /**
     * The SEO keys the request actually sent, and nothing else.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public static function sent(array $validated): array
    {
        return array_intersect_key($validated, array_flip(self::keys()));
    }

    /**
     * $validated without its SEO keys — for the model the request is really about
     * (a post, a product, a category), which has no SEO columns of its own.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public static function without(array $validated): array
    {
        return array_diff_key($validated, array_flip(self::keys()));
    }

    /**
     * A page's SEO as the admin API returns it: the translatable copy as a map
     * per language (null when never set), the rest as stored. Every key is always
     * present, so a client can show the form without guessing.
     *
     * @return array<string, mixed>
     */
    public static function present(?Page $page): array
    {
        $out = [];

        foreach (Seo::TRANSLATABLE as $field) {
            $out[$field] = $page?->getTranslations($field) ?: null;
        }

        foreach (['og_image', 'twitter_image', 'canonical_base', 'canonical_slug'] as $field) {
            $out[$field] = $page?->{$field};
        }

        $out['no_index'] = (bool) $page?->no_index;
        $out['no_follow'] = (bool) $page?->no_follow;

        return $out;
    }
}
