<?php

namespace Themes\ModernEcommerce\Controllers;

use App\Http\Controllers\Themes\Concerns\RendersStandalonePage;
use App\Http\Controllers\Themes\ThemeController;

/**
 * The "modern_ecommerce" theme's standalone pages (About, Contact, FAQ, ...).
 */
class PageController extends ThemeController
{
    use RendersStandalonePage;
}
