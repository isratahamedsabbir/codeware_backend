<?php

namespace App\Support\Builder;

use App\Models\Post;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\Service;
use App\Support\UrlStructure;

/**
 * Dynamic blocks of the page builder.
 *
 * A design is stored as plain HTML, so a block that must show live data is saved
 * as an empty placeholder — `<div data-cw="products" data-limit="8" …></div>` —
 * and swapped for real markup here, each time the page is printed. No PHP or
 * Blade is ever stored in a design: the editor only chooses *options* (category,
 * count, columns), and the markup comes from this class.
 */
class DynamicBlocks
{
    /** Hard ceiling, so a typo in a placeholder can't print the whole catalogue. */
    private const MAX_ITEMS = 24;

    public static function render(string $html): string
    {
        if (! str_contains($html, 'data-cw=')) {
            return $html;
        }

        $styled = []; // each block type prints its <style> once per page, however many times it is dropped

        return preg_replace_callback(
            '/<div\b([^>]*\bdata-cw="([a-z-]+)"[^>]*)>\s*<\/div>/i',
            function (array $m) use (&$styled) {
                $options = self::options($m[1]);
                $styleKey = in_array($m[2], ['posts', 'services'], true) ? 'cards' : $m[2]; // these two share one stylesheet
                $first = ! isset($styled[$styleKey]);
                $styled[$styleKey] = true;

                return match ($m[2]) {
                    'products' => self::products($options, $first),
                    'posts' => self::posts($options, $first),
                    'services' => self::services($options, $first),
                    'brands' => self::brands($options, $first),
                    default => $m[0],
                };
            },
            $html,
        ) ?? $html;
    }

    /** @return array<string, string> the placeholder's data-* attributes, without the "data-" */
    private static function options(string $attributes): array
    {
        preg_match_all('/\bdata-([a-z-]+)="([^"]*)"/i', $attributes, $found, PREG_SET_ORDER);

        return collect($found)->mapWithKeys(fn ($f) => [$f[1] => html_entity_decode($f[2])])->all();
    }

    private static function limit(array $o, int $default = 6): int
    {
        return max(1, min((int) ($o['limit'] ?? $default), self::MAX_ITEMS));
    }

    private static function columns(array $o, int $default = 3): int
    {
        return max(1, min((int) ($o['columns'] ?? $default), 6));
    }

    /** Latest blog posts. */
    private static function posts(array $o, bool $withStyle): string
    {
        $posts = Post::query()->published()->with('page.slugRecord')
            ->orderByDesc('published_at')->orderByDesc('id')
            ->limit(self::limit($o))->get();

        return self::cards($posts->map(fn (Post $p) => [
            'title' => (string) $p->title,
            'text' => (string) $p->short_description,
            'image' => $p->featured_image,
            'url' => $p->slug ? UrlStructure::url('post', $p->slug) : '#',
            'meta' => $p->published_at?->format('M j, Y'),
        ]), $o, $withStyle);
    }

    /** Active services. */
    private static function services(array $o, bool $withStyle): string
    {
        $services = Service::query()->active()->with('slugRecord')
            ->orderBy('sort_order')->orderByDesc('id')
            ->limit(self::limit($o))->get();

        return self::cards($services->map(fn (Service $s) => [
            'title' => (string) $s->name,
            'text' => (string) $s->description,
            'image' => $s->featured_image,
            'url' => $s->slug ? UrlStructure::url('service', $s->slug) : '#',
            'meta' => null,
        ]), $o, $withStyle);
    }

    /** @param \Illuminate\Support\Collection<int, array<string, mixed>> $items */
    private static function cards($items, array $o, bool $withStyle): string
    {
        return view('builders.blocks.cards', [
            'items' => $items,
            'columns' => self::columns($o),
            'withStyle' => $withStyle,
        ])->render();
    }

    /** A row of brand logos (no links — a brand has no page of its own to open). */
    private static function brands(array $o, bool $withStyle): string
    {
        $brands = ProductBrand::query()->active()
            ->when(($o['featured'] ?? '') === 'true', fn ($q) => $q->where('featured', true))
            ->orderBy('sort_order')->orderBy('id')
            ->limit(self::limit($o, 12))->get();

        return view('builders.blocks.brands', ['brands' => $brands, 'withStyle' => $withStyle])->render();
    }

    private static function products(array $o, bool $withStyle = true): string
    {
        $limit = max(1, min((int) ($o['limit'] ?? 8), self::MAX_ITEMS));
        $columns = max(1, min((int) ($o['columns'] ?? 4), 6));

        $query = Product::query()->active()->with('page.slugRecord');

        if (($category = (int) ($o['category'] ?? 0)) > 0) {
            $query->whereHas('categories', fn ($q) => $q->where('categories.id', $category));
        }

        if (($o['featured'] ?? '') === 'true') {
            $query->featured();
        }

        match ($o['order'] ?? 'latest') {
            'oldest' => $query->oldest('id'),
            'price-asc' => $query->orderBy('price'),
            'price-desc' => $query->orderByDesc('price'),
            'manual' => $query->orderBy('sort_order')->orderByDesc('id'),
            default => $query->latest('id'),
        };

        $products = $query->limit($limit)->get();

        return view('builders.blocks.products', [
            'products' => $products,
            'columns' => $columns,
            'withStyle' => $withStyle,
            'url' => fn (Product $p) => $p->slug ? UrlStructure::url('product', $p->slug) : '#',
        ])->render();
    }
}
