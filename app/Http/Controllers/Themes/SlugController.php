<?php

namespace App\Http\Controllers\Themes;

use App\Models\Page;
use App\Models\ProductBrand;
use App\Models\JobPost;
use App\Models\SlugRecord;
use App\Models\Tag;
use App\Support\Features;
use App\Support\Slug;
use App\Support\Themes;
use App\Support\UrlStructure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/**
 * The bare `/{slug}` address. Every slug lives in the one `slugs` table and is
 * unique across all of it, so the slug alone says what the visitor wants: this
 * looks the row up and hands the request to the active theme's own controller
 * for that kind of record — the same one the prefixed address (/products/{slug},
 * /blog/{slug}, ...) runs, so both addresses render identically.
 *
 * Registered after every theme's route file (see routes/web.php) so a real
 * route — /login, /cart, /shop — always wins over a record that happens to share
 * its name. The prefixed addresses keep working.
 */
class SlugController
{
    /** Page.type => [route name whose controller renders it, feature flag gating it]. */
    private const PAGE_TYPES = [
        'page' => ['page', null, null],
        'product' => ['products.show', 'products', 'product'],
        'product_category' => ['shop.category', 'products', null],
        'post' => ['blog.post', 'blog', 'post'],
    ];

    /** Non-page slug owners => [route name, feature flag]. */
    private const OWNERS = [
        ProductBrand::class => ['shop.brand', 'products', null],
        Tag::class => ['shop.tag', 'products', null],
        JobPost::class => ['careers.show', 'careers', null],
    ];

    public function __invoke(Request $request)
    {
        // `/slug`, or `/prefix/slug` where the prefix is one Settings → URL structure
        // gives a kind of record. Anything else, the admin and vendor hosts and
        // api/* included, is a plain 404.
        abort_unless(Themes::isStorefrontRequest($request) && preg_match('#^[A-Za-z0-9_-]+(/[A-Za-z0-9_-]+)?$#', $request->path()), 404);

        $segments = explode('/', $request->path());
        $slug = array_pop($segments);
        $prefixType = $segments ? (UrlStructure::typeForPrefix(Slug::lower($segments[0])) ?? abort(404)) : null;

        $record = SlugRecord::where('slug', Slug::lower($slug))->first();

        abort_unless($record, 404);

        [$name, $feature, $urlType] = $this->target($record) ?? abort(404);

        // /products/slug must be a product, /blog/slug a post.
        abort_if($prefixType !== null && $prefixType !== $urlType, 404);

        abort_if($feature !== null && ! Features::enabled($feature), 404);
        abort_unless(Themes::activeThemeCanRender($name), 404);

        [$class, $method] = $this->activeThemeAction($name) ?? abort(404);

        return app()->call([app($class), $method], ['slug' => $record->slug]);
    }

    /** @return array{0: string, 1: ?string, 2: ?string}|null [route name, feature flag, UrlStructure type] */
    private function target(SlugRecord $record): ?array
    {
        if ($record->sluggable_type === Page::class || $record->sluggable_type === (new Page)->getMorphClass()) {
            $type = Page::whereKey($record->sluggable_id)->value('type');

            return self::PAGE_TYPES[$type] ?? null;
        }

        foreach (self::OWNERS as $model => $target) {
            if ($record->sluggable_type === $model || $record->sluggable_type === (new $model)->getMorphClass()) {
                return $target;
            }
        }

        return null;
    }

    /**
     * The active theme's controller class and method for a route name. Themes
     * register the same URIs under the same names and Laravel keeps only the last
     * registration of a URI, so the route table can't be asked "which one is the
     * active theme's". A shared name means the same method everywhere, and each
     * theme names its controllers alike, so the route supplies the method and
     * class basename and the active theme's namespace the rest.
     *
     * @return array{0: class-string, 1: string}|null
     */
    private function activeThemeAction(string $name): ?array
    {
        $route = Route::getRoutes()->getByName($name);

        if (! $route || ! $route->getControllerClass()) {
            return null;
        }

        $class = 'Themes\\'.Str::studly(Themes::active()).'\\Controllers\\'.class_basename($route->getControllerClass());

        return class_exists($class) ? [$class, $route->getActionMethod()] : null;
    }
}
