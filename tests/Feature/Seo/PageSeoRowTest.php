<?php

use App\Models\Page;
use App\Models\Seo;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;

/*
 * Every page's search/social metadata lives in its own `seos` row (one per page,
 * created with the page), read and written through the page. The site-wide
 * defaults stay in Settings.
 */

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->admin()->create());
});

function seoPage(string $title, string $slug, array $seo = [], string $type = 'page'): Page
{
    return Page::factory()->published()->create([
        'title' => ['en' => $title, 'bn' => ''],
        'slug' => $slug,
        'type' => $type,
        ...$seo,
    ]);
}

it('keeps a page\'s SEO in a seos row of its own, not on the page', function () {
    $page = seoPage('About us', 'about-us', [
        'seo_title' => ['en' => 'About Codeware', 'bn' => 'কোডওয়্যার'],
        'seo_description' => ['en' => 'Who we are'],
        'og_image' => 'media/og.jpg',
        'no_index' => true,
    ]);

    $row = Seo::where('page_id', $page->id)->first();

    expect($row)->not->toBeNull()
        ->and($row->getTranslations('seo_title'))->toEqual(['en' => 'About Codeware', 'bn' => 'কোডওয়্যার'])
        ->and($row->og_image)->toBe('media/og.jpg')
        ->and($row->no_index)->toBeTrue()
        ->and(\Illuminate\Support\Facades\Schema::hasColumn('pages', 'seo_title'))->toBeFalse()
        // Still readable on the page, exactly as when these were columns.
        ->and($page->fresh()->seo_title)->toBe('About Codeware')
        ->and($page->fresh()->no_index)->toBeTrue()
        ->and($page->fresh()->getTranslations('seo_title'))->toEqual(['en' => 'About Codeware', 'bn' => 'কোডওয়্যার']);
});

it('creates a blank seos row for every page the moment it is created', function () {
    $page = seoPage('Plain', 'plain');

    $row = Seo::where('page_id', $page->id)->first();

    expect($row)->not->toBeNull()
        ->and($row->seo_title)->toBeNull()
        ->and($row->no_index)->toBeFalse()
        ->and($page->seo_title)->toBeNull()
        ->and($page->no_index)->toBeFalse();
});

it('creates a seos row for every kind of page: page, post, product and categories', function () {
    foreach (['page', 'post', 'product', 'post_category', 'product_category'] as $type) {
        $page = seoPage(ucfirst($type), 'slug-'.$type, [], $type);

        expect(Seo::where('page_id', $page->id)->count())->toBe(1);
    }
});

it('gives a page created with SEO copy that copy on its one row', function () {
    $page = seoPage('Landing', 'landing', ['seo_title' => ['en' => 'Landing page']]);

    expect(Seo::where('page_id', $page->id)->count())->toBe(1)
        ->and(Seo::where('page_id', $page->id)->first()->getTranslation('seo_title', 'en'))->toBe('Landing page');
});

it('backfills a row for a page written around the model', function () {
    $page = seoPage('Raw', 'raw');
    Seo::where('page_id', $page->id)->delete();

    expect(Seo::ensureForAllPages())->toBeGreaterThanOrEqual(1)
        ->and(Seo::where('page_id', $page->id)->count())->toBe(1);
});

it('has at most one seos row per page and updates it in place', function () {
    $page = seoPage('FAQ', 'faq', ['seo_title' => ['en' => 'First']]);

    $page->update(['seo_title' => ['en' => 'Second']]);
    $page->update(['og_title' => ['en' => 'Shared title']]);

    expect(Seo::where('page_id', $page->id)->count())->toBe(1)
        ->and($page->fresh()->seo_title)->toBe('Second')
        ->and($page->fresh()->og_title)->toBe('Shared title');
});

it('deletes a page\'s SEO row with the page', function () {
    $page = seoPage('Gone', 'gone', ['seo_title' => ['en' => 'x']]);

    $page->forceDelete();

    expect(Seo::where('page_id', $page->id)->exists())->toBeFalse();
});

it('leaves noindex pages out of the indexable scope', function () {
    $open = seoPage('Open', 'open');
    $hidden = seoPage('Hidden', 'hidden', ['no_index' => true]);

    $ids = Page::indexable()->pluck('id');

    expect($ids)->toContain($open->id)->not->toContain($hidden->id);
});
