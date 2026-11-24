<?php

use App\Livewire\Admin\Posts\Index as PostsIndex;
use App\Livewire\Admin\Products\Index as ProductsIndex;
use App\Livewire\Admin\Services\Index as ServicesIndex;
use App\Models\User;
use App\Support\UrlStructure;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->admin()->create());
});

it('saves each kind of record\'s URL prefix from its own list screen', function () {
    Livewire::test(ProductsIndex::class)->assertSet('urlPrefix', 'products')
        ->set('urlPrefix', 'shop-item')->call('saveUrlPrefix')->assertHasNoErrors();
    Livewire::test(PostsIndex::class)->set('urlPrefix', '')->call('saveUrlPrefix')->assertHasNoErrors();
    Livewire::test(ServicesIndex::class)->set('urlPrefix', 'what-we-do')->call('saveUrlPrefix')->assertHasNoErrors();

    expect(UrlStructure::prefix('product'))->toBe('shop-item')
        ->and(UrlStructure::prefix('post'))->toBe('')
        ->and(UrlStructure::prefix('service'))->toBe('what-we-do');
});

it('rejects a reserved prefix or one another kind of page already uses', function () {
    Livewire::test(ProductsIndex::class)->set('urlPrefix', 'admin')->call('saveUrlPrefix')->assertHasErrors('urlPrefix');

    // 'blog' belongs to posts by default.
    Livewire::test(ProductsIndex::class)->set('urlPrefix', 'blog')->call('saveUrlPrefix')->assertHasErrors('urlPrefix');
});

it('keeps the URL prefixes off the Settings screen', function () {
    Livewire::test(App\Livewire\Admin\Settings\Index::class)->assertDontSee('URL structure');
});
