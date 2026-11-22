<?php

namespace App\View\Components\Builder;

use Illuminate\Support\Collection;

/** Shared by the card-style blocks (posts, services): one stylesheet, one `cards` view. */
abstract class CardList extends Block
{
    /** @return Collection<int, array{title: string, text: string, image: ?string, url: string, meta: ?string}> */
    abstract protected function items(): Collection;

    public static function styleKey(): string
    {
        return 'cards';
    }

    public static function viewName(): string
    {
        return 'builders.blocks.cards';
    }

    public static function fields(): array
    {
        return [...static::headingFields(), static::limitField(3), static::columnsField(3, 4)];
    }

    protected function viewData(): array
    {
        return ['items' => $this->items(), 'columns' => $this->int('columns', 3, 1, 6)];
    }
}
