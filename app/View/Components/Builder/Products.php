<?php

namespace App\View\Components\Builder;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Support\UrlStructure;

/** Live products from the shop. */
class Products extends Block
{
    public static function key(): string
    {
        return 'products';
    }

    public static function name(): string
    {
        return 'Products';
    }

    public static function hint(): string
    {
        return 'Live products from your shop';
    }

    public static function preview(): array
    {
        return ['sample' => 'Product', 'shape' => 'square', 'columns' => true];
    }

    public static function fields(): array
    {
        return [
            ...static::headingFields(),
            ['name' => 'category', 'type' => 'select', 'label' => 'Category', 'default' => '0',
                'options' => ['0' => 'All products'] + ProductCategory::orderBy('id')->get()
                    ->mapWithKeys(fn ($c) => [(string) $c->id => (string) $c->name])->all()],
            static::limitField(8),
            static::columnsField(4),
            ['name' => 'order', 'type' => 'select', 'label' => 'Order', 'default' => 'latest', 'options' => [
                'latest' => 'Newest first', 'oldest' => 'Oldest first',
                'price-asc' => 'Price: low to high', 'price-desc' => 'Price: high to low', 'manual' => 'Manual order',
            ]],
            static::featuredField(),
        ];
    }

    protected function viewData(): array
    {
        $query = Product::query()->active()->with('page.slugRecord');

        if (($category = (int) ($this->options['category'] ?? 0)) > 0) {
            $query->whereHas('categories', fn ($q) => $q->where('categories.id', $category));
        }

        if ($this->flag('featured')) {
            $query->featured();
        }

        match ($this->options['order'] ?? 'latest') {
            'oldest' => $query->oldest('id'),
            'price-asc' => $query->orderBy('price'),
            'price-desc' => $query->orderByDesc('price'),
            'manual' => $query->orderBy('sort_order')->orderByDesc('id'),
            default => $query->latest('id'),
        };

        return [
            'products' => $query->limit($this->int('limit', 8))->get(),
            'columns' => $this->int('columns', 4, 1, 6),
            'url' => fn (Product $p) => $p->slug ? UrlStructure::url('product', $p->slug) : '#',
        ];
    }
}
