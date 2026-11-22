<?php

use App\Models\Setting;
use App\Support\Themes;
use Illuminate\Support\Facades\Route;

it('renders a non-404 storefront error in the active theme design', function () {
    Route::get('/boom-403', fn () => abort(403))->middleware('web');

    $expected = [
        'default' => 'rounded-lg bg-primary',
        'classic_ecommerce' => 'rounded-card bg-sf-button',
        'portfolio' => 'pf-btn-solid',
    ];

    foreach ($expected as $slug => $needle) {
        Setting::set('site_theme', $slug);
        Themes::forget();

        expect(Themes::errorView(403))->toBe("theme-{$slug}::errors.error");

        $this->get('/boom-403')
            ->assertForbidden()
            ->assertSee('403')
            ->assertSee(__('Access denied'))
            ->assertSee($needle, escape: false)
            ->assertSee('noindex, nofollow', escape: false);
    }
});
