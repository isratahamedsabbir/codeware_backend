<?php

namespace App\View\Components\Builder;

use App\Models\Faq;

/** Active FAQs, as an accordion. */
class Faqs extends Block
{
    public static function key(): string
    {
        return 'faqs';
    }

    public static function name(): string
    {
        return 'FAQ';
    }

    public static function hint(): string
    {
        return 'Questions and answers';
    }

    public static function icon(): string
    {
        return 'list';
    }

    public static function preview(): array
    {
        return ['sample' => 'Question', 'shape' => 'card', 'columns' => false];
    }

    public static function fields(): array
    {
        return [...static::headingFields(), static::limitField(8)];
    }

    protected function viewData(): array
    {
        return ['faqs' => Faq::query()->where('is_active', true)
            ->orderBy('sort_order')->orderBy('id')
            ->limit($this->int('limit', 8))->get()];
    }
}
