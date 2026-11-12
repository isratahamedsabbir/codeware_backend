<?php

namespace Themes\Ecommerce\Controllers;

use App\Http\Controllers\Themes\Concerns\RendersCatalog;
use App\Http\Controllers\Themes\ThemeController;
use App\Models\ProductCategory;
use Illuminate\Http\RedirectResponse;

/**
 * The "ecommerce" theme's catalog: the shop listing and the category, brand and
 * tag pages that filter it.
 *
 * The only theme with a shop — the portfolio is a one-pager and the default
 * theme a small site, neither of which has a product listing, so neither has
 * this file. The sidebar facets, the filter behaviour and the sort options are
 * this theme's shop.blade.php and its partials; what is decided here is which
 * products a request resolves to.
 */
class ShopController extends ThemeController
{
    use RendersCatalog;

    /**
     * Category URLs used to render their own landing page. They now open the
     * shop with that category selected, so the old address is kept as a
     * permanent redirect for bookmarks and admin-built menu links.
     */
    public function category(string $slug): RedirectResponse
    {
        $exists = ProductCategory::active()
            ->whereHas('page', fn ($query) => $query->where('slug', $slug))
            ->exists();

        abort_unless($exists, 404, 'Unknown category.');

        return redirect()->route('shop', ['category' => $slug], 301);
    }

    /**
     * Brand URLs follow the same rule as categories: the filter lives on the
     * shop now, and the old landing-page address redirects there.
     */
    public function brand(string $slug): RedirectResponse
    {
        abort_unless($this->resolveBrand($slug), 404, 'Unknown brand.');

        return redirect()->route('shop', ['brand' => $slug], 301);
    }
}
