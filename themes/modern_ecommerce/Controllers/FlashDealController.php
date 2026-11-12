<?php

namespace Themes\ModernEcommerce\Controllers;

use App\Http\Controllers\Themes\Concerns\RendersFlashDeals;
use App\Http\Controllers\Themes\ThemeController;

/**
 * The "modern_ecommerce" theme's flash deals page.
 */
class FlashDealController extends ThemeController
{
    use RendersFlashDeals;
}
