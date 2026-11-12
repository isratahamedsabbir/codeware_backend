<?php

namespace Themes\ClassicEcommerce\Controllers;

use App\Http\Controllers\Themes\Concerns\RendersFlashDeals;
use App\Http\Controllers\Themes\ThemeController;

/**
 * The "classic_ecommerce" theme's flash-deals page: the sales running right now, each
 * with a countdown to its end. Which deals count as live and what their products
 * cost is decided by App\Models\FlashDeal, not here.
 */
class FlashDealController extends ThemeController
{
    use RendersFlashDeals;
}
