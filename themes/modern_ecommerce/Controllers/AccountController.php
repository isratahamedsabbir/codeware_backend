<?php

namespace Themes\ModernEcommerce\Controllers;

use App\Http\Controllers\Themes\Concerns\RendersAccount;
use App\Http\Controllers\Themes\ThemeController;

/**
 * The "modern_ecommerce" theme's customer account area.
 */
class AccountController extends ThemeController
{
    use RendersAccount;
}
