<?php

namespace App\Support\Builder;

use App\Livewire\Frontend\DynamicBlock;
use App\Support\Locale;
use App\View\Components\Builder\Block;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use ReflectionClass;

/**
 * Dynamic blocks of the page builder.
 *
 * A design is stored as plain HTML, so a block that must show live data is saved
 * as an empty placeholder — `<div data-cw="products" data-limit="8" …></div>` —
 * and swapped for real markup here, each time the page is printed. No PHP or
 * Blade is ever stored in a design: the editor only chooses *options*, and the
 * markup comes from the block's own Blade component.
 *
 * Every block is a class in `app/View/Components/Builder/` extending
 * {@see Block}; this registry finds them by folder, so adding a block is adding
 * a file (see the Block class comment).
 *
 * Translated options travel as `data-{name}-{code}` next to the primary
 * `data-{name}`; they are resolved to the visitor's language *here*, before the
 * block is handed to its lazy Livewire component, because that component later
 * re-renders in a request of its own where the visitor's language is not known.
 */
class DynamicBlocks
{
    /** @return array<string, class-string<Block>> key => component class */
    public static function registry(): array
    {
        $found = [];

        foreach (File::glob(app_path('View/Components/Builder/*.php')) as $file) {
            $class = 'App\\View\\Components\\Builder\\'.basename($file, '.php');

            if (class_exists($class) && is_subclass_of($class, Block::class) && ! (new ReflectionClass($class))->isAbstract()) {
                $found[$class::key()] = $class;
            }
        }

        ksort($found);

        return $found;
    }

    /**
     * What the editor needs to offer every block: its settings, with each translatable
     * field expanded to one input per non-primary language.
     *
     * @param  array<int, array{code: string, label: string}>  $languages
     * @return array<int, array<string, mixed>>
     */
    public static function definitions(array $languages = []): array
    {
        $primary = strtolower(Locale::primary());

        return array_values(array_map(function (string $class) use ($languages, $primary) {
            $definition = $class::definition();

            $definition['fields'] = collect($definition['fields'])->flatMap(function (array $field) use ($languages, $primary) {
                if (empty($field['translatable'])) {
                    return [$field];
                }

                $others = collect($languages)->reject(fn ($l) => strtolower($l['code']) === $primary);
                $base = $field['label'];

                if ($others->isNotEmpty()) {
                    $field['label'] = $base.' ('.strtoupper($primary).')';
                }

                return [$field, ...$others->map(fn ($l) => [
                    'name' => $field['name'].'-'.strtolower($l['code']),
                    'type' => $field['type'] === 'textarea' ? 'textarea' : 'text',
                    'label' => $base.' ('.$l['label'].')',
                    'default' => '',
                ])->all()];
            })->all();

            // the per-language inputs start empty; every other default is the block's own
            $definition['defaults'] += collect($definition['fields'])->mapWithKeys(fn ($f) => [$f['name'] => ''])->all();

            return $definition;
        }, static::registry()));
    }

    public static function render(string $html, ?string $locale = null): string
    {
        if (! str_contains($html, 'data-cw=')) {
            return $html;
        }

        $locale = strtolower($locale ?? Locale::current());
        $registry = static::registry();
        $styled = []; // each block type prints its <style> once per page, however many times it is dropped

        return preg_replace_callback(
            '/<div\b([^>]*\bdata-cw="([a-z-]+)"[^>]*)>\s*<\/div>/i',
            function (array $m) use (&$styled, $registry, $locale) {
                if (! isset($registry[$m[2]])) {
                    return $m[0];
                }

                $styleKey = $registry[$m[2]]::styleKey();
                $first = ! isset($styled[$styleKey]);
                $styled[$styleKey] = true;

                // Each block is a lazy Livewire component: the page paints at once, the data loads after.
                return Livewire::mount(DynamicBlock::class, [
                    'type' => $m[2],
                    'options' => static::resolveLocale(static::options($m[1]), $locale),
                    'withStyle' => $first,
                    'lazy' => true,
                ]);
            },
            $html,
        ) ?? $html;
    }

    /** The markup of one block, built from live data. Called by the DynamicBlock Livewire component. */
    public static function block(string $type, array $options, bool $withStyle = true): string
    {
        $class = static::registry()[$type] ?? null;

        return $class ? (new $class(static::resolveLocale($options), $withStyle))->render()->render() : '';
    }

    /**
     * Folds `name-{code}` options into `name` for $locale (a blank translation falls back to
     * the primary text), then drops every `name-{code}` key.
     *
     * @param  array<string, string>  $options
     * @return array<string, string>
     */
    public static function resolveLocale(array $options, ?string $locale = null): array
    {
        $locale = strtolower($locale ?? Locale::current());
        $known = array_map('strtolower', Locale::codes());
        $out = $options;

        foreach ($options as $key => $value) {
            // `name-{code}`: a translation of `name`. A suffix that merely looks like a language code
            // (`-bn`) is only treated as one when it is the visitor's, a known one, or sits next to its base option.
            if (! preg_match('/^(.+)-([a-z]{2,3}(?:[_-][a-z0-9]+)?)$/i', $key, $m)) {
                continue;
            }

            [, $base, $code] = $m;
            $code = strtolower($code);

            if ($code !== $locale && ! in_array($code, $known, true) && ! array_key_exists($base, $options)) {
                continue;
            }

            if ($code === $locale && trim($value) !== '') {
                $out[$base] = $value;
            }

            unset($out[$key]);
        }

        return $out;
    }

    /** @return array<string, string> the placeholder's data-* attributes, without the "data-" */
    private static function options(string $attributes): array
    {
        preg_match_all('/\bdata-([a-z-]+)="([^"]*)"/i', $attributes, $found, PREG_SET_ORDER);

        return collect($found)->mapWithKeys(fn ($f) => [$f[1] => html_entity_decode($f[2])])->all();
    }
}
