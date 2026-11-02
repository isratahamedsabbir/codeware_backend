<?php

use App\Livewire\Frontend\CartDrawer;
use App\Models\Setting;
use App\Support\Themes;
use Livewire\Livewire;

it('renders an empty cart drawer', function () {
    Livewire::test(CartDrawer::class)
        ->assertOk()
        ->assertSee('0 items')
        ->assertSee('Your cart is empty');
});

it('puts the cart drawer on ecommerce pages but not on the cart page', function () {
    Setting::set('site_theme', 'ecommerce');
    Themes::forget();

    $this->get('/')->assertOk()->assertSee('x-on:open-cart-drawer.window', false);
    $this->get('/cart')->assertOk()->assertDontSee('x-on:open-cart-drawer.window', false);
});
