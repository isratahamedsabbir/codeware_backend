<?php

use App\Models\Page;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->actingAs($this->admin);
});

it('exports pages as a downloadable csv with the importable fields', function () {
    Page::factory()->published()->create([
        'title' => ['en' => 'About Us', 'bn' => 'আমাদের সম্পর্কে'],
        'slug' => 'about-us',
        'template' => 'puck',
        'sort_order' => 3,
    ]);

    $response = $this->get(route('admin.pages.export'));

    $response->assertOk();
    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    $response->assertDownload();

    $csv = $response->streamedContent();

    expect($csv)->toContain('Title (EN)')
        ->and($csv)->toContain('Title (BN)')
        ->and($csv)->toContain('Template')
        ->and($csv)->toContain('Sort Order')
        ->and($csv)->toContain('About Us')
        ->and($csv)->toContain('আমাদের সম্পর্কে')
        ->and($csv)->toContain('about-us')
        ->and($csv)->toContain('puck');
});

it('exports all pages when no ids are given', function () {
    Page::factory()->create(['title' => ['en' => 'Included Page', 'bn' => '']]);
    Page::factory()->create(['title' => ['en' => 'Second Page', 'bn' => '']]);

    $csv = $this->get(route('admin.pages.export'))->streamedContent();

    expect($csv)->toContain('Included Page')
        ->and($csv)->toContain('Second Page');
});

it('exports an import template containing only the header row', function () {
    Page::factory()->create(['title' => ['en' => 'Template Must Not Appear', 'bn' => '']]);

    $csv = $this->get(route('admin.pages.export', ['template' => 1]))->streamedContent();

    expect($csv)->toContain('Title (EN)')
        ->and($csv)->toContain('Sort Order')
        ->and($csv)->not->toContain('Template Must Not Appear')
        ->and(substr_count(trim($csv), "\n"))->toBe(0);
});

it('only includes pages matching the search filter', function () {
    Page::factory()->create(['title' => ['en' => 'Matching Page', 'bn' => '']]);
    Page::factory()->create(['title' => ['en' => 'Other Page', 'bn' => '']]);

    $csv = $this->get(route('admin.pages.export', ['search' => 'Matching']))->streamedContent();

    expect($csv)->toContain('Matching Page')
        ->and($csv)->not->toContain('Other Page');
});

it('only includes pages matching the type filter', function () {
    Page::factory()->create(['type' => 'page', 'title' => ['en' => 'Standalone Page', 'bn' => '']]);
    Page::factory()->create(['type' => 'post', 'title' => ['en' => 'Companion Page', 'bn' => '']]);

    $csv = $this->get(route('admin.pages.export', ['type' => 'page']))->streamedContent();

    expect($csv)->toContain('Standalone Page')
        ->and($csv)->not->toContain('Companion Page');
});

it('exports only the requested page ids, ignoring search and type', function () {
    $included = Page::factory()->create(['title' => ['en' => 'Selected Page', 'bn' => '']]);
    Page::factory()->create(['title' => ['en' => 'Other Page', 'bn' => '']]);

    $csv = $this->get(route('admin.pages.export', [
        'ids' => [$included->id],
        'search' => 'nonexistent',
    ]))->streamedContent();

    expect($csv)->toContain('Selected Page')
        ->and($csv)->not->toContain('Other Page');
});
