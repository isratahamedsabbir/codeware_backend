<?php

/*
 * The storefront routes of the "news" theme: a front page, the posts feed, a
 * single article and the standalone About / Contact / FAQ pages. No shop, cart
 * or account routes — asking a news site for /shop gets this theme's own 404.
 *
 * Route names shared with the other themes (`home`, `page`, `blog`, `blog.post`)
 * mean the same method + URI everywhere; see themes/default/routes/web.php.
 */

use Illuminate\Support\Facades\Route;
use Themes\News\Controllers\BlogController;
use Themes\News\Controllers\HomeController;
use Themes\News\Controllers\PageController;

Route::get('/', [HomeController::class, 'home'])->name('home');
Route::get('/home', [HomeController::class, 'home']);

Route::get('/{slug}', [PageController::class, 'page'])
    ->where('slug', 'about|contact|faq')
    ->name('page');

// Posts. Gated by the same "blog" feature as the admin's Posts module.
Route::middleware('feature:blog')->group(function () {
    Route::get('/blog', [BlogController::class, 'blog'])->name('blog');
    Route::get('/blog/{slug}', [BlogController::class, 'post'])->name('blog.post');
});
