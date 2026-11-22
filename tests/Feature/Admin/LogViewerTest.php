<?php

use App\Livewire\Admin\Logs\Index;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->logPath = storage_path('logs/zz-test-viewer.log');
    file_put_contents($this->logPath, "[2026-11-22 10:00:00] testing.ERROR: Boom happened {\"a\":1}\n#0 stack line one\n#1 stack line two\n[2026-11-22 10:01:00] testing.INFO: All fine\n");
});

afterEach(function () {
    @unlink($this->logPath);
});

it('lets an admin read parsed log entries and filter by level', function () {
    Livewire::actingAs(User::factory()->admin()->create())
        ->test(Index::class)
        ->set('file', 'zz-test-viewer.log')
        ->assertSee('Boom happened')
        ->assertSee('All fine')
        ->set('level', 'error')
        ->assertSee('Boom happened')
        ->assertDontSee('All fine');
});

it('keeps staff out', function () {
    $this->actingAs(User::factory()->staff()->create())
        ->get(route('admin.logs'))
        ->assertForbidden();
});

it('only touches files that are really in the logs folder', function () {
    Livewire::actingAs(User::factory()->admin()->create())
        ->test(Index::class)
        ->set('file', '../../.env')
        ->call('delete');

    expect(file_exists(base_path('.env')))->toBeTrue();
});
