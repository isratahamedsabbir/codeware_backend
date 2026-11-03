<?php

use App\Livewire\Admin\Products\Form as ProductForm;
use App\Livewire\Admin\Products\Index as ProductsIndex;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

it('applies an image label to the products selected in the table', function () {
    $first = Product::factory()->create();
    $second = Product::factory()->create();

    Livewire::test(ProductsIndex::class)
        ->call('toggleSelect', $first->id)
        ->call('toggleSelect', $second->id)
        ->call('openBulkLabel')
        ->assertSet('bulkLabelProductIds', [$first->id, $second->id])
        ->set('bulkLabel', 'Best seller')
        ->call('bulkUpdateLabel')
        ->assertSet('selectedIds', [$first->id, $second->id])
        ->assertDispatched('close-modal', name: 'product-bulk-label')
        ->assertDispatched('notify', message: 'Image label saved for 2 products');

    expect($first->refresh()->label)->toBe('Best seller')
        ->and($second->refresh()->label)->toBe('Best seller');
});

it('saves labels to the products selected when the modal was opened', function () {
    $selectedProduct = Product::factory()->create();
    $otherProduct = Product::factory()->create();

    Livewire::test(ProductsIndex::class)
        ->set('selectedIds', [$selectedProduct->id])
        ->call('openBulkLabel')
        ->set('selectedIds', [$otherProduct->id])
        ->set('bulkLabel', 'Featured')
        ->call('bulkUpdateLabel')
        ->assertSet('bulkLabelProductIds', [$selectedProduct->id]);

    expect($selectedProduct->refresh()->label)->toBe('Featured')
        ->and($otherProduct->refresh()->label)->toBeNull();
});

it('clears selected product labels when the label field is left blank', function () {
    $first = Product::factory()->create(['label' => 'Clearance']);
    $second = Product::factory()->create(['label' => 'New arrival']);

    Livewire::test(ProductsIndex::class)
        ->set('selectedIds', [$first->id, $second->id])
        ->call('openBulkLabel')
        ->assertSet('bulkLabelProductIds', [$first->id, $second->id])
        ->set('bulkLabel', '')
        ->call('bulkUpdateLabel')
        ->assertDispatched('close-modal', name: 'product-bulk-label')
        ->assertDispatched('notify', message: 'Image label cleared from 2 products');

    expect($first->refresh()->label)->toBeNull()
        ->and($second->refresh()->label)->toBeNull();
});

it('rejects bulk image labels longer than 40 characters', function () {
    $product = Product::factory()->create(['label' => 'Existing label']);

    Livewire::test(ProductsIndex::class)
        ->set('selectedIds', [$product->id])
        ->call('openBulkLabel')
        ->set('bulkLabel', str_repeat('a', 41))
        ->call('bulkUpdateLabel')
        ->assertHasErrors(['bulkLabel' => 'max']);

    expect($product->refresh()->label)->toBe('Existing label');
});

it('opens the image label modal only when table rows are selected', function () {
    $product = Product::factory()->create();

    Livewire::test(ProductsIndex::class)
        ->assertDontSee('Set image label (1)')
        ->call('toggleSelect', $product->id)
        ->assertSee('Set image label (1)')
        ->call('openBulkLabel')
        ->assertDispatched('open-modal', name: 'product-bulk-label')
        ->assertSee('Leave blank and save to remove the existing label.');
});

it('rejects product image labels longer than 40 characters in the product form', function () {
    Livewire::test(ProductForm::class)
        ->set('name.en', 'Long Label Product')
        ->set('price', '1000')
        ->set('label', str_repeat('a', 41))
        ->call('save')
        ->assertHasErrors(['label' => 'max']);

    expect(Product::whereJsonContains('name->en', 'Long Label Product')->exists())->toBeFalse();
});
