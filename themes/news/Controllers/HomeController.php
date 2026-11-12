<?php

namespace Themes\News\Controllers;

use App\Http\Controllers\Themes\Concerns\RendersHomePage;
use App\Http\Controllers\Themes\ThemeController;

/**
 * The front page's stories come from Themes\News\Support\NewsFeed, called by
 * views/index.blade.php itself — see the note there.
 */
class HomeController extends ThemeController
{
    use RendersHomePage;
}
