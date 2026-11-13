<?php

/*
 * The storefront routes of the "default" theme — the homepage and nothing else.
 *
 * routes/web.php registers this file behind the 'theme' guard, which 404s any
 * request the active theme has no template for, so a default-theme site has no
 * /shop, no /cart, no /account and no standalone pages (About, Contact, ...):
 * the request never reaches another theme's controller, which is what keeps a
 * small site from quietly growing the ecommerce theme's pages.
 *
 * The controllers are this theme's own, in themes/default/Controllers/ — the
 * fourth per-theme half, after the route file (this one), the templates
 * (themes/default/views/) and the stylesheet
 * (themes/default/public/css/theme.css). Every line here points at a class
 * inside this theme's folder, so what this theme's site is made of is readable
 * without opening a file named after the application.
 *
 * The homepage is repeated in the other theme files on purpose: each file is a
 * complete description of that theme's site, so they can be read side by side,
 * and the 'theme' guard is keyed on the route *name* rather than on the file it
 * came from. A shared name has to mean the same page in every file that
 * registers it — the files are kept in step by hand, the same way
 * Themes::ROUTE_TEMPLATES mirrors them for the nav filter.
 */

use Illuminate\Support\Facades\Route;
use Themes\Default\Controllers\HomeController;

Route::get('/', [HomeController::class, 'home'])->name('home');

// /home is just an alias for / — same controller method and view, so it keeps
// the homepage's own hero styling instead of the generic page layout.
Route::get('/home', [HomeController::class, 'home']);
