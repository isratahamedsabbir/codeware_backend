<?php

namespace App\View\Components\Builder;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * A dynamic block of the page builder, written as an ordinary Blade component.
 *
 * To add one, drop a class in `app/View/Components/Builder/` that extends this and a
 * view in `resources/views/builders/blocks/` — nothing else. It shows up in the
 * editor's "Dynamic" list on its own, with a settings panel built from `fields()`,
 * and it also works by hand anywhere in Blade: `<x-builder.products :options="['limit' => 4]" />`.
 *
 *   class Faq extends Block {
 *       public static function key(): string  { return 'faq'; }
 *       public static function name(): string { return 'FAQ list'; }
 *       public static function fields(): array { return [...static::headingFields(), static::limitField()]; }
 *       protected function viewData(): array  { return ['items' => Faq::limit($this->int('limit'))->get()]; }
 *   }
 *
 * Field types: text, textarea, number, select, checkbox. A field flagged
 * `translatable` gets one input per language in the editor (stored as
 * `data-{name}` for the primary language and `data-{name}-{code}` for the rest);
 * the page prints the visitor's language and falls back to the primary text.
 */
abstract class Block extends Component
{
    /** Hard ceiling, so a typo in a placeholder can't print the whole catalogue. */
    protected const MAX_ITEMS = 24;

    /** @param array<string, string> $options the placeholder's data-* values, already resolved to one language */
    public function __construct(public array $options = [], public bool $withStyle = true) {}

    /** The `data-cw` value of the placeholder. */
    abstract public static function key(): string;

    abstract public static function name(): string;

    /** @return array<int, array<string, mixed>> the settings of the block — see the class comment */
    abstract public static function fields(): array;

    /** @return array<string, mixed> what the block's view needs, built from live data */
    abstract protected function viewData(): array;

    public static function hint(): string
    {
        return '';
    }

    /** A key of the editor's icon set (grid, list, star, logos…). */
    public static function icon(): string
    {
        return 'grid';
    }

    /** What the editor draws while designing: label of the sample card and its shape (card, square, logo). */
    public static function preview(): array
    {
        return ['sample' => 'Item', 'shape' => 'card', 'columns' => true];
    }

    /** Blocks that return the same key share one <style> per page. */
    public static function styleKey(): string
    {
        return static::key();
    }

    /** The view this block prints. */
    public static function viewName(): string
    {
        return 'builders.blocks.'.static::key();
    }

    /** Heading fields every block gets, so any block can carry translated copy. */
    public static function headingFields(): array
    {
        return [
            ['name' => 'heading', 'type' => 'text', 'label' => 'Heading', 'translatable' => true],
            ['name' => 'subheading', 'type' => 'text', 'label' => 'Subheading', 'translatable' => true],
        ];
    }

    public static function limitField(int $default = 6): array
    {
        return ['name' => 'limit', 'type' => 'number', 'label' => 'How many', 'min' => 1, 'max' => static::MAX_ITEMS, 'default' => $default];
    }

    public static function columnsField(int $default = 3, int $max = 6): array
    {
        return ['name' => 'columns', 'type' => 'select', 'label' => 'Columns', 'default' => $default,
            'options' => collect(range(1, $max))->mapWithKeys(fn ($n) => [(string) $n => (string) $n])->all()];
    }

    public static function featuredField(): array
    {
        return ['name' => 'featured', 'type' => 'checkbox', 'label' => 'Featured only', 'default' => false];
    }

    /** The editor's definition of this block: everything the JS needs, nothing it has to hard-code. */
    public static function definition(): array
    {
        return [
            'key' => static::key(),
            'name' => static::name(),
            'hint' => static::hint(),
            'icon' => static::icon(),
            'preview' => static::preview(),
            'fields' => array_values(static::fields()),
            'defaults' => static::defaults(),
        ];
    }

    /** @return array<string, string> option => value for a freshly dropped block */
    public static function defaults(): array
    {
        $out = [];

        foreach (static::fields() as $field) {
            $default = $field['default'] ?? ($field['type'] === 'checkbox' ? false : '');
            $out[$field['name']] = is_bool($default) ? ($default ? 'true' : 'false') : (string) $default;
        }

        return $out;
    }

    public function render(): View
    {
        return view(static::viewName(), $this->viewData() + [
            'withStyle' => $this->withStyle,
            'options' => $this->options,
            'heading' => $this->text('heading'),
            'subheading' => $this->text('subheading'),
        ]);
    }

    protected function int(string $name, int $default = 6, int $min = 1, ?int $max = null): int
    {
        return max($min, min((int) ($this->options[$name] ?? $default), $max ?? static::MAX_ITEMS));
    }

    protected function flag(string $name): bool
    {
        return ($this->options[$name] ?? '') === 'true';
    }

    protected function text(string $name): string
    {
        return trim((string) ($this->options[$name] ?? ''));
    }
}
