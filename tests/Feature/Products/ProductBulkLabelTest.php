<?php

use App\Livewire\Admin\Products\Index as ProductsIndex;
use App\Livewire\Admin\Products\Form as ProductForm;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

it('applies an image label while keeping modal products separate from table selection', function () {
    $first = Product::factory()->create();
    $second = Product::factory()->create();

    Livewire::test(ProductsIndex::class)
        ->call('openBulkLabel')
        ->set('bulkProductIds', [(string) $first->id, (string) $second->id])
        ->set('bulkLabel', 'Best seller')
        ->call('bulkUpdateLabel')
        ->assertSet('bulkProductIds', [$first->id, $second->id])
        ->assertSet('selectedIds', [])
        ->assertDispatched('close-modal', name: 'product-bulk-label');

    expect($first->refresh()->label)->toBe('Best seller')
        ->and($second->refresh()->label)->toBe('Best seller');
});

it('rejects bulk image labels longer than 40 characters', function () {
    $product = Product::factory()->create();

    Livewire::test(ProductsIndex::class)
        ->set('bulkProductIds', [$product->id])
        ->set('bulkLabel', str_repeat('a', 41))
        ->call('bulkUpdateLabel')
        ->assertHasErrors(['bulkLabel' => 'max']);

    expect($product->refresh()->label)->toBeNull();
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

it('keeps previous selections when the label modal is reopened', function () {
    $product = Product::factory()->create();

    Livewire::test(ProductsIndex::class)
        ->set('bulkProductIds', [$product->id])
        ->call('openBulkLabel')
        ->assertSet('bulkProductIds', [$product->id])
        ->assertSet('selectedIds', [])
        ->assertSee('1 selected');
});

it('folds products selected in the table into the modal selection', function () {
    $product = Product::factory()->create();

    Livewire::test(ProductsIndex::class)
        ->call('toggleSelect', $product->id)
        ->call('openBulkLabel')
        ->assertSet('bulkProductIds', [$product->id]);
});

it('keeps modal checkbox selection separate from the table selection', function () {
    $product = Product::factory()->create();

    Livewire::test(ProductsIndex::class)
        ->set('bulkProductIds', [(string) $product->id])
        ->assertSet('selectedIds', []);
});

it('shows the current image label beside each product in the label modal', function () {
    Product::factory()->create([
        'name' => ['en' => 'Labeled product', 'bn' => ''],
        'label' => 'New arrival',
    ]);

    Livewire::test(ProductsIndex::class)
        ->call('openBulkLabel')
        ->assertSee('Labeled product')
        ->assertSee('New arrival')
        ->assertSee('Delete');
});

it('removes only the image label when deleting it from the label modal', function () {
    $product = Product::factory()->create(['label' => 'Clearance']);

    Livewire::test(ProductsIndex::class)
        ->set('bulkProductIds', [$product->id])
        ->call('removeProductLabel', $product->id)
        ->assertSet('bulkProductIds', [$product->id])
        ->assertSet('selectedIds', [])
        ->assertDispatched('notify', message: 'Product image label removed.');

    expect($product->refresh()->label)->toBeNull()
        ->and($product->exists)->toBeTrue();
});
