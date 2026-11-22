<?php

namespace App\View\Components\Builder;

use App\Models\ProductBrand;

/** A row of brand logos (no links — a brand has no page of its own to open). */
class Brands extends Block
{
    public static function key(): string
    {
        return 'brands';
    }

    public static function name(): string
    {
        return 'Brands';
    }

    public static function hint(): string
    {
        return 'Row of brand logos';
    }

    public static function icon(): string
    {
        return 'logos';
    }

    public static function preview(): array
    {
        return ['sample' => 'Brand', 'shape' => 'logo', 'columns' => false];
    }

    public static function fields(): array
    {
        return [...static::headingFields(), static::limitField(12), static::featuredField()];
    }

    protected function viewData(): array
    {
        return ['brands' => ProductBrand::query()->active()
            ->when($this->flag('featured'), fn ($q) => $q->where('featured', true))
            ->orderBy('sort_order')->orderBy('id')
            ->limit($this->int('limit', 12))->get()];
    }
}
