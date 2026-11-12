<?php

namespace Themes\News\Controllers;

use App\Http\Controllers\Themes\Concerns\RendersBlog;
use App\Http\Controllers\Themes\ThemeController;

class BlogController extends ThemeController
{
    use RendersBlog;
}
