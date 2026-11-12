<?php

namespace Themes\News\Support;

use App\Models\Post;
use App\Models\PostCategory;
use App\Support\Features;
use Illuminate\Support\Collection;

/**
 * What the news front page is made of: one lead story, two beside it, a column of
 * the next headlines, then a few category sections.
 *
 * Read from the template rather than handed in by a controller on purpose: every
 * theme registers its routes, and the route table keeps only the last theme's
 * controller for `/`, so the home controller that actually runs is not this
 * theme's. The template asks for its own data instead.
 */
class NewsFeed
{
    private const RELATIONS = ['page', 'category.page', 'user:id,name'];

    /**
     * @return array{lead: ?Post, secondary: Collection, latest: Collection, sections: Collection}
     */
    public static function front(): array
    {
        $recent = Features::enabled('blog')
            ? Post::published()->with(self::RELATIONS)->orderByDesc('published_at')->orderByDesc('id')->limit(12)->get()
            : collect();

        return [
            'lead' => $recent->first(),
            'secondary' => $recent->slice(1, 2)->values(),
            'latest' => $recent->slice(3, 8)->values(),
            'sections' => Features::enabled('blog') ? self::sections() : collect(),
        ];
    }

    private static function sections(): Collection
    {
        return PostCategory::where('status', 'active')
            ->with('page')
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (PostCategory $category) => $category->page !== null)
            ->map(function (PostCategory $category) {
                $category->setRelation('latestPosts', Post::published()
                    ->with(self::RELATIONS)
                    ->whereHas('category', fn ($q) => $q->whereKey($category->id))
                    ->orderByDesc('published_at')
                    ->orderByDesc('id')
                    ->limit(3)
                    ->get());

                return $category;
            })
            ->filter(fn (PostCategory $category) => $category->latestPosts->isNotEmpty())
            ->take(3)
            ->values();
    }
}
