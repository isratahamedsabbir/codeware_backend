<?php

namespace App\View\Components\Builder;

use App\Models\Service;
use App\Support\UrlStructure;
use Illuminate\Support\Collection;

/** Active services. */
class Services extends CardList
{
    public static function key(): string
    {
        return 'services';
    }

    public static function name(): string
    {
        return 'Services';
    }

    public static function hint(): string
    {
        return 'Your active services';
    }

    public static function icon(): string
    {
        return 'star';
    }

    public static function preview(): array
    {
        return ['sample' => 'Service', 'shape' => 'card', 'columns' => true];
    }

    public static function fields(): array
    {
        return [...static::headingFields(), static::limitField(6), static::columnsField(3, 4)];
    }

    protected function items(): Collection
    {
        return Service::query()->active()->with('slugRecord')
            ->orderBy('sort_order')->orderByDesc('id')
            ->limit($this->int('limit', 6))->get()
            ->map(fn (Service $s) => [
                'title' => (string) $s->name,
                'text' => (string) $s->description,
                'image' => $s->featured_image,
                'url' => $s->slug ? UrlStructure::url('service', $s->slug) : '#',
                'meta' => null,
            ]);
    }
}
