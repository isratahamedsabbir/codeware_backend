<?php

use App\Livewire\Admin\Seo\Index as SeoScreen;
use App\Models\Page;
use App\Models\Seo;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

/*
 * Admin → SEO: every page's search/social metadata lives in its own `seos` row
 * (one per page), read and written through the page, and managed from one screen.
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

it('creates no seos row for a page that was never given SEO copy', function () {
    $page = seoPage('Plain', 'plain');

    expect(Seo::where('page_id', $page->id)->exists())->toBeFalse()
        ->and($page->seo_title)->toBeNull()
        ->and($page->no_index)->toBeFalse();
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

it('lists every page with what its SEO is missing', function () {
    seoPage('Has meta', 'has-meta', ['seo_title' => ['en' => 'A proper meta title'], 'seo_description' => ['en' => 'A proper description']]);
    seoPage('Bare page', 'bare-page');

    Livewire::test(SeoScreen::class)
        ->assertSee('Has meta')
        ->assertSee('Bare page')
        ->assertSee('A proper meta title')
        ->assertSee('Missing');
});

it('filters to pages without a meta title, without a description and to noindex ones', function () {
    seoPage('Titled', 'titled', ['seo_title' => ['en' => 'Present']]);
    seoPage('Untitled one', 'untitled-one');
    seoPage('Hidden one', 'hidden-one', ['no_index' => true, 'seo_title' => ['en' => 'Hidden meta']]);

    Livewire::test(SeoScreen::class)
        ->set('statusFilter', 'missing_title')
        ->assertSee('Untitled one')
        ->assertDontSee('Titled')
        ->set('statusFilter', 'missing_description')
        ->assertSee('Titled')
        ->set('statusFilter', 'noindex')
        ->assertSee('Hidden one')
        ->assertDontSee('Untitled one');
});

it('searches by title, address and meta title, and filters by type', function () {
    seoPage('Contact us', 'reach-out', ['seo_title' => ['en' => 'Get in touch']]);
    seoPage('Blog entry', 'blog-entry', [], 'post');

    Livewire::test(SeoScreen::class)
        ->set('search', 'reach-out')->assertSee('Contact us')->assertDontSee('Blog entry')
        ->set('search', 'Get in touch')->assertSee('Contact us')
        ->set('search', '')
        ->set('typeFilter', 'post')->assertSee('Blog entry')->assertDontSee('Contact us');
});

it('edits a page\'s SEO from the manager and saves it to its seos row', function () {
    $page = seoPage('Pricing', 'pricing', ['seo_title' => ['en' => 'Old title']]);

    Livewire::test(SeoScreen::class)
        ->call('editPage', $page->id)
        ->assertSet('editingPageId', $page->id)
        ->assertSet('seo_title.en', 'Old title')
        ->set('seo_title.en', 'Pricing — plans for every team')
        ->set('seo_description.en', 'Compare plans.')
        ->set('no_follow', true)
        ->call('savePageSeo')
        ->assertHasNoErrors()
        ->assertSet('editingPageId', null);

    $row = Seo::where('page_id', $page->id)->first();

    expect($row->getTranslation('seo_title', 'en'))->toBe('Pricing — plans for every team')
        ->and($row->getTranslation('seo_description', 'en'))->toBe('Compare plans.')
        ->and($row->no_follow)->toBeTrue()
        ->and(Seo::where('page_id', $page->id)->count())->toBe(1);
});

it('refuses to edit the SEO of a page type the manager does not list', function () {
    $other = Page::factory()->create(['type' => 'landing', 'title' => ['en' => 'x']]);

    Livewire::test(SeoScreen::class)
        ->call('editPage', $other->id)
        ->assertSet('editingPageId', null);
});
