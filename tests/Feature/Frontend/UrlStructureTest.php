<?php

use App\Models\Setting;
use App\Support\UrlStructure;

it('builds a record address from the configured prefix', function () {
    Setting::set('url_prefix_product', 'shop-item');
    Setting::set('url_prefix_post', '');

    expect(UrlStructure::path('product', 'x'))->toBe('shop-item/x')
        ->and(UrlStructure::path('post', 'y'))->toBe('y')
        ->and(UrlStructure::typeForPrefix('shop-item'))->toBe('product')
        ->and(UrlStructure::typeForPrefix('nope'))->toBeNull();
});

it('defaults to the classic prefixes before anything is saved', function () {
    expect(UrlStructure::prefix('product'))->toBe('products')
        ->and(UrlStructure::prefix('post'))->toBe('blog');
});
