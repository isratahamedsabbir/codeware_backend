<?php

namespace Themes\Default\Controllers;

use App\Http\Controllers\Themes\Concerns\RendersHomePage;
use App\Http\Controllers\Themes\ThemeController;

/**
 * The "default" theme's homepage.
 *
 * This class is the whole of that theme's storefront, which is also its sign-in
 * screen: the template shows the admin/vendor/delivery logins and the owner's
 * intro, and the theme ships no other page. It was FrontendController before —
 * a class of 600 lines that also answered for the ecommerce theme's shop and
 * the portfolio theme's blog — so a theme's pages were spread across files
 * named after the application rather than the theme. The other themes'
 * equivalents are Themes/Ecommerce/ and Themes/Portfolio/.
 *
 * The method itself comes from a trait rather than being written out, because
 * the *data* a homepage needs is the same on every theme — what differs is the
 * template, and that has always been this theme's own
 * themes/default/views/index.blade.php. A theme that wanted a
 * different home overrides the method here.
 */
class HomeController extends ThemeController
{
    use RendersHomePage;
}
