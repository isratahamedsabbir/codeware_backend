<?php

use App\Livewire\Admin\Groups\Form;
use App\Livewire\Admin\Groups\Index;
use App\Livewire\Admin\MediaLibrary\Index as MediaIndex;
use App\Models\Group;
use App\Models\MediaLibrary;
use App\Models\Type;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TypeSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(TypeSeeder::class);
    $this->actingAs(User::factory()->admin()->create());
});

function galleryImage(array $attributes = []): MediaLibrary
{
    return MediaLibrary::create(array_merge([
        'filename' => 'a.jpg',
        'original_filename' => 'a.jpg',
        'path' => 'media/a.jpg',
        'mime_type' => 'image/jpeg',
        'file_type' => 'image',
        'is_gallery' => true,
    ], $attributes));
}

it('seeds a Gallery type', function () {
    expect(Type::idFor(Type::GALLERY))->not->toBeNull();
});

it('renders the groups list and create form', function () {
    Group::create(['name' => ['en' => 'Events'], 'type_id' => Type::idFor(Type::GALLERY)]);

    Livewire::test(Index::class)->assertOk()->assertSee('Events')->assertSee('Gallery');
    Livewire::test(Form::class)->assertOk()->assertSee('Group Details');
});

it('creates a group under a type', function () {
    Livewire::test(Form::class)
        ->set('name.en', 'Events')
        ->set('typeId', Type::idFor(Type::GALLERY))
        ->call('save')
        ->assertHasNoErrors();

    $group = Group::first();

    expect($group->getTranslation('name', 'en'))->toBe('Events')
        ->and($group->type_id)->toBe(Type::idFor(Type::GALLERY))
        ->and($group->status)->toBe('active');
});

it('requires a name and a type', function () {
    Livewire::test(Form::class)
        ->call('save')
        ->assertHasErrors(['typeId', 'name.en']);
});

it('toggles status and deletes a group, detaching its images', function () {
    $group = Group::create(['name' => ['en' => 'Events'], 'type_id' => Type::idFor(Type::GALLERY)]);
    $image = galleryImage(['gallery_group_id' => $group->id]);

    Livewire::test(Index::class)
        ->call('toggleStatus', $group->id)
        ->call('confirmDelete', $group->id)
        ->call('delete');

    expect(Group::find($group->id))->toBeNull()
        ->and($image->fresh()->gallery_group_id)->toBeNull();
});

it('refuses to delete a type that still has groups', function () {
    $type = Type::create(['name' => ['en' => 'Extra'], 'status' => 'active']);
    Group::create(['name' => ['en' => 'G'], 'type_id' => $type->id]);

    Livewire::test(\App\Livewire\Admin\Types\Index::class)
        ->call('confirmDelete', $type->id)
        ->call('delete');

    expect(Type::find($type->id))->not->toBeNull();
});

it('adds a non-gallery image to the gallery when a group is picked', function () {
    $group = Group::create(['name' => ['en' => 'Events'], 'type_id' => Type::idFor(Type::GALLERY)]);
    $image = galleryImage(['is_gallery' => false]);

    Livewire::test(MediaIndex::class)
        ->call('viewDetails', $image->id)
        ->assertSee('Gallery Group')
        ->set('editGalleryGroupId', $group->id)
        ->call('saveMediaDetails')
        ->assertHasNoErrors();

    expect($image->fresh()->is_gallery)->toBeTrue()
        ->and($image->fresh()->gallery_group_id)->toBe($group->id);
});

it('files a gallery image into a Gallery-type group from Edit Details', function () {
    $group = Group::create(['name' => ['en' => 'Events'], 'type_id' => Type::idFor(Type::GALLERY)]);
    $image = galleryImage();

    Livewire::test(MediaIndex::class)
        ->call('viewDetails', $image->id)
        ->assertSee('Gallery Group')
        ->set('editGalleryGroupId', $group->id)
        ->call('saveMediaDetails')
        ->assertHasNoErrors();

    expect($image->fresh()->gallery_group_id)->toBe($group->id);
});

it('rejects a group that is not under the Gallery type', function () {
    $group = Group::create(['name' => ['en' => 'Shoes'], 'type_id' => Type::idFor(Type::PRODUCT)]);
    $image = galleryImage();

    Livewire::test(MediaIndex::class)
        ->call('viewDetails', $image->id)
        ->set('editGalleryGroupId', $group->id)
        ->call('saveMediaDetails')
        ->assertHasErrors('editGalleryGroupId');
});

it('does not offer the Gallery type in the category form', function () {
    $options = Livewire::test(\App\Livewire\Admin\Categories\Form::class)->get('typeOptions');

    expect(collect($options)->pluck('slug'))->not->toContain(Type::GALLERY);
});

it('files an image into a group from the picker modal too', function () {
    $group = Group::create(['name' => ['en' => 'Events'], 'type_id' => Type::idFor(Type::GALLERY)]);
    $image = galleryImage(['is_gallery' => false]);

    Livewire::test(\App\Livewire\Admin\MediaLibrary\PickerModal::class)
        ->call('openPicker', 'test-picker')
        ->call('selectMedia', $image->id)
        ->assertSee('Gallery Group')
        ->assertSet('editGalleryGroupId', null)
        ->set('editGalleryGroupId', $group->id)
        ->call('saveMediaDetails')
        ->assertHasNoErrors();

    expect($image->fresh()->gallery_group_id)->toBe($group->id)
        ->and($image->fresh()->is_gallery)->toBeTrue();
});
