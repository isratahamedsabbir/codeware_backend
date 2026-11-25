<?php

use App\Livewire\Admin\Dashboard;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(fn () => $this->seed(RolePermissionSeeder::class));

it('renders the extra stat cards for an admin', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    Livewire::actingAs($admin)->test(Dashboard::class)
        ->assertOk()
        ->assertSee('Services')
        ->assertSee('Users')
        ->assertSee('Orders')
        ->assertSee('Contacts')
        ->assertSet('totalUsers', User::count());
});

it('keeps admin-only numbers away from staff', function () {
    $staff = User::factory()->create();
    $staff->assignRole('staff');
    User::factory()->count(3)->create();

    $component = Livewire::actingAs($staff)->test(Dashboard::class)
        ->assertOk()
        ->assertSet('totalUsers', 0)
        ->assertSet('totalOrders', 0)
        ->assertSet('totalContacts', 0);

    expect($component->html())->not->toContain('href="'.route('admin.users').'"');
});
