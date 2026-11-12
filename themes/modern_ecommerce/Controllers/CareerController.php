<?php

namespace Themes\ModernEcommerce\Controllers;

use App\Http\Controllers\Themes\Concerns\RendersCareers;
use App\Http\Controllers\Themes\ThemeController;

/**
 * The "modern_ecommerce" theme's careers list and single job post.
 */
class CareerController extends ThemeController
{
    use RendersCareers;
}
