<?php

namespace Themes\ModernEcommerce\Controllers;

use App\Http\Controllers\Themes\Concerns\RendersCart;
use App\Http\Controllers\Themes\ThemeController;

/**
 * The "modern_ecommerce" theme's cart, checkout and order confirmation pages.
 */
class CartController extends ThemeController
{
    use RendersCart;
}
