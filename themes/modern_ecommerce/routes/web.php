<?php

/*
 * The storefront routes of the "modern_ecommerce" theme — the whole shop.
 *
 * Every route name here means the same method + URI as in the classic theme's
 * route file (see App\Support\Themes::ROUTE_TEMPLATES). The 'theme' guard 404s
 * a page this theme ships no template for, so only routes with a matching
 * template in views/ answer while this theme is active.
 */

use Illuminate\Support\Facades\Route;
use Themes\ModernEcommerce\Controllers\AccountController;
use Themes\ModernEcommerce\Controllers\BlogController;
use Themes\ModernEcommerce\Controllers\CareerController;
use Themes\ModernEcommerce\Controllers\CartController;
use Themes\ModernEcommerce\Controllers\FlashDealController;
use Themes\ModernEcommerce\Controllers\HomeController;
use Themes\ModernEcommerce\Controllers\PageController;
use Themes\ModernEcommerce\Controllers\ProductController;
use Themes\ModernEcommerce\Controllers\ShopController;

Route::get('/', [HomeController::class, 'home'])->name('home');

// /home is just an alias for / — same controller method and view.
Route::get('/home', [HomeController::class, 'home']);

// Standalone pages (About, Contact, FAQ, ...) — explicitly whitelisted so this
// can never shadow auth/system routes regardless of registration order.
Route::get('/{slug}', [PageController::class, 'page'])
    ->where('slug', 'about|contact|faq')
    ->name('page');

Route::middleware('feature:products')->group(function () {
    Route::get('/shop', [ShopController::class, 'shop'])->name('shop');
    Route::get('/products/{slug}', [ProductController::class, 'product'])->name('products.show');
    Route::get('/category/{slug}', [ShopController::class, 'category'])->name('shop.category');
    Route::get('/brand/{slug}', [ShopController::class, 'brand'])->name('shop.brand');
    Route::get('/tag/{slug}', [ShopController::class, 'tag'])->name('shop.tag');
});

Route::middleware('feature:advertisements')->group(function () {
    Route::get('/ad/{code}', [ProductController::class, 'adClick'])->name('advertisements.click');
});

Route::middleware('feature:products')->group(function () {
    Route::get('/favorites', [ProductController::class, 'favorites'])->name('favorites');
});

Route::middleware('feature:flash-deals')->group(function () {
    Route::get('/flash-deals', [FlashDealController::class, 'flashDeals'])->name('flash-deals');
});

Route::middleware('feature:blog')->group(function () {
    Route::get('/blog', [BlogController::class, 'blog'])->name('blog');
    Route::get('/blog/{slug}', [BlogController::class, 'post'])->name('blog.post');
});

Route::middleware('feature:careers')->group(function () {
    Route::get('/careers', [CareerController::class, 'careers'])->name('careers');
    Route::get('/careers/{slug}', [CareerController::class, 'career'])->name('careers.show');
});

Route::middleware('feature:orders')->group(function () {
    Route::get('/cart', [CartController::class, 'cart'])->name('cart');
    Route::get('/checkout', [CartController::class, 'checkout'])->name('checkout');
    Route::get('/order-confirmation/{orderNumber}', [CartController::class, 'orderConfirmation'])->name('checkout.confirmation');
});

Route::get('/account/delete', [AccountController::class, 'deleteAccount'])->name('account.delete');

Route::middleware(['auth'])->group(function () {
    Route::get('/account', [AccountController::class, 'dashboard'])->name('account.dashboard');
    Route::get('/account/orders', [AccountController::class, 'orders'])->name('account.orders');
    Route::get('/account/orders/{orderNumber}', [AccountController::class, 'orderShow'])->name('account.orders.show');
    Route::get('/account/profile', [AccountController::class, 'profile'])->name('account.profile');
});
