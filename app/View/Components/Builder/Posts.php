<?php

namespace App\View\Components\Builder;

use App\Models\Post;
use App\Support\UrlStructure;
use Illuminate\Support\Collection;

/** Latest blog posts. */
class Posts extends CardList
{
    public static function key(): string
    {
        return 'posts';
    }

    public static function name(): string
    {
        return 'Latest posts';
    }

    public static function hint(): string
    {
        return 'Newest blog posts';
    }

    public static function icon(): string
    {
        return 'list';
    }

    public static function preview(): array
    {
        return ['sample' => 'Blog post', 'shape' => 'card', 'columns' => true];
    }

    protected function items(): Collection
    {
        return Post::query()->published()->with('page.slugRecord')
            ->orderByDesc('published_at')->orderByDesc('id')
            ->limit($this->int('limit', 6))->get()
            ->map(fn (Post $p) => [
                'title' => (string) $p->title,
                'text' => (string) $p->short_description,
                'image' => $p->featured_image,
                'url' => $p->slug ? UrlStructure::url('post', $p->slug) : '#',
                'meta' => $p->published_at?->format('M j, Y'),
            ]);
    }
}
