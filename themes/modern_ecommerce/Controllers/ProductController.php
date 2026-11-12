<?php

namespace Themes\ModernEcommerce\Controllers;

use App\Http\Controllers\Themes\Concerns\RendersProduct;
use App\Http\Controllers\Themes\ThemeController;

/**
 * The "modern_ecommerce" theme's product detail page and saved favorites.
 */
class ProductController extends ThemeController
{
    use RendersProduct;
}
