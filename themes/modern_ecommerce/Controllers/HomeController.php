<?php

namespace Themes\ModernEcommerce\Controllers;

use App\Http\Controllers\Themes\Concerns\RendersHomePage;
use App\Http\Controllers\Themes\ThemeController;

/**
 * The "modern_ecommerce" theme's homepage. The data comes from the shared
 * RendersHomePage concern; the look is themes/modern_ecommerce/views/index.blade.php.
 */
class HomeController extends ThemeController
{
    use RendersHomePage;
}
