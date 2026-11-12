<?php

namespace Themes\ModernEcommerce\Controllers;

use App\Http\Controllers\Themes\Concerns\RendersBlog;
use App\Http\Controllers\Themes\ThemeController;

/**
 * The "modern_ecommerce" theme's blog feed and single post.
 */
class BlogController extends ThemeController
{
    use RendersBlog;
}
