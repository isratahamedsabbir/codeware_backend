<?php

namespace Themes\ModernEcommerce\Controllers;

use App\Http\Controllers\Themes\Concerns\RendersCatalog;
use App\Http\Controllers\Themes\ThemeController;

/**
 * The "modern_ecommerce" theme's catalog: the shop listing plus the category,
 * brand and tag landing pages.
 */
class ShopController extends ThemeController
{
    use RendersCatalog;
}
