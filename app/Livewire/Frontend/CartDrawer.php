<?php

namespace App\Livewire\Frontend;

/**
 * The storefront's floating cart button and the slide-in cart panel it opens.
 *
 * Same state and actions as the /cart page (quantity steppers, remove), so it
 * extends CartPage instead of repeating them — only the markup differs. It
 * listens for `cart-updated` like the page does, so adding a product anywhere
 * on the site refreshes the button's count and total.
 *
 * The panel is opened with the `open-cart-drawer` browser event (the floating
 * button and the header cart icon both fire it).
 */
class CartDrawer extends CartPage
{
    public function render()
    {
        return view('livewire.frontend.cart-drawer');
    }
}
