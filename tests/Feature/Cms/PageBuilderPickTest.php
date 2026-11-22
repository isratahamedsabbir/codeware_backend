<?php

use App\Livewire\Admin\Pages\Form;
use App\Models\BuilderDesign;
use App\Models\Page;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

it('picks a builder design on the page form and saves it', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->admin()->create());
    $design = BuilderDesign::create(['name' => 'Landing', 'user_id' => auth()->id()]);
    $page = Page::factory()->published()->create(['title' => ['en' => 'About', 'bn' => ''], 'slug' => 'about']);

    Livewire\Livewire::test(Form::class, ['id' => $page->id])
        ->assertSee('Landing')
        ->set('builderId', (string) $design->id)
        ->call('save')
        ->assertHasNoErrors();

    expect($page->fresh()->builder_id)->toBe($design->id);
});
