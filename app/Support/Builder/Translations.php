<?php

namespace App\Support\Builder;

use App\Support\Locale;

/**
 * Section-by-section translations inside ONE builder design.
 *
 * A design is built once, in the primary language: whatever a text element
 * holds is its primary-language text. Another language is stored on the element
 * itself, as an attribute per language —
 *
 *     <h1 data-t-bn="আমাদের সম্পর্কে">About us</h1>
 *
 * — and the public page swaps the element's content for the visitor's language
 * (falling back to the primary text when that language was never filled in).
 * The attributes never reach the visitor.
 */
class Translations
{
    /** Language codes that have at least one translated element in this HTML. */
    public static function locales(string $html): array
    {
        preg_match_all('/\sdata-t-([A-Za-z_-]+)\s*=/', $html, $m);

        return array_values(array_unique(array_map('strtolower', $m[1])));
    }

    public static function has(string $html, string $locale): bool
    {
        return in_array(strtolower($locale), static::locales($html), true);
    }

    /** Swaps each element's content for its $locale translation, then drops every data-t-* attribute. */
    public static function apply(string $html, ?string $locale = null): string
    {
        $locale = strtolower($locale ?? Locale::current());
        $attr = 'data-t-'.preg_quote($locale, '/');

        // Walk the open tags that carry this language's text, innermost last-first so
        // replacing one never moves the offset of one still to do.
        if ($locale !== strtolower(Locale::primary())
            && preg_match_all('/<([a-zA-Z][a-zA-Z0-9]*)\b[^>]*?\s'.$attr.'\s*=\s*("([^"]*)"|\'([^\']*)\')[^>]*>/s', $html, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
            foreach (array_reverse($matches) as $match) {
                [$open, $start] = $match[0];
                $tag = $match[1][0];
                $text = html_entity_decode($match[3][0] !== '' ? $match[3][0] : ($match[4][0] ?? ''), ENT_QUOTES | ENT_HTML5);

                if (trim($text) === '' || str_ends_with($open, '/>')) {
                    continue;
                }

                $from = $start + strlen($open);
                $end = static::closingTag($html, $tag, $from);

                if ($end === null) {
                    continue;
                }

                $html = substr($html, 0, $from).$text.substr($html, $end);
            }
        }

        return preg_replace('/\sdata-t-[A-Za-z_-]+\s*=\s*("[^"]*"|\'[^\']*\')/', '', $html) ?? $html;
    }

    /** Offset of the `</tag>` that closes the element whose content starts at $from, counting nested same-name tags. */
    private static function closingTag(string $html, string $tag, int $from): ?int
    {
        $depth = 1;
        $pattern = '/<(\/?)'.preg_quote($tag, '/').'\b[^>]*>/i';
        $offset = $from;

        while (preg_match($pattern, $html, $m, PREG_OFFSET_CAPTURE, $offset)) {
            [$found, $pos] = $m[0];

            if ($m[1][0] === '/') {
                if (--$depth === 0) {
                    return $pos;
                }
            } elseif (! str_ends_with($found, '/>')) {
                $depth++;
            }

            $offset = $pos + strlen($found);
        }

        return null;
    }
}
