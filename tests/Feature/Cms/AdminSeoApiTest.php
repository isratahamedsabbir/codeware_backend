<?php

use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Seo;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Sanctum\Sanctum;

/*
 * The admin API takes the same SEO keys on everything that owns a page — pages,
 * posts, products, product categories. Every key is optional: a key that is sent
 * is written to the page's `seos` row (null clears it), a key that is not sent
 * leaves the stored value alone, and a request with no SEO keys never touches it.
 */

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->admin = User::factory()->admin()->create();
    Sanctum::actingAs($this->admin);
});

function seoRowFor(Page $page): Seo
{
    return Seo::where('page_id', $page->id)->firstOrFail();
}

// ---------------------------------------------------------------- Pages

it('creates a page with SEO keys and puts them on its seos row', function () {
    $id = $this->postJson('/api/v1/admin/pages', [
        'title' => ['en' => 'Careers', 'bn' => ''],
        'seo_title' => ['en' => 'Work with us', 'bn' => 'আমাদের সাথে কাজ করুন'],
        'seo_description' => 'Open roles at Codeware',
        'og_image' => '/og/careers.png',
        'no_index' => true,
        'canonical_slug' => 'jobs',
    ])->assertCreated()->json('data.id');

    $row = seoRowFor(Page::findOrFail($id));

    expect($row->getTranslation('seo_title', 'en'))->toBe('Work with us')
        ->and($row->getTranslation('seo_title', 'bn'))->toBe('আমাদের সাথে কাজ করুন')
        ->and($row->getTranslation('seo_description', 'en'))->toBe('Open roles at Codeware')
        ->and($row->og_image)->toBe('/og/careers.png')
        ->and($row->no_index)->toBeTrue()
        ->and($row->canonical_slug)->toBe('jobs');
});

it('creates a page without SEO keys and still gives it a blank seos row', function () {
    $id = $this->postJson('/api/v1/admin/pages', ['title' => ['en' => 'Plain page']])->assertCreated()->json('data.id');

    $row = seoRowFor(Page::findOrFail($id));

    expect($row->seo_title)->toBeNull()
        ->and($row->no_index)->toBeFalse();
});

it('updates only the SEO keys that were sent and leaves the others alone', function () {
    $page = Page::factory()->published()->create([
        'seo_title' => ['en' => 'Keep this title'],
        'og_title' => ['en' => 'Keep this OG title'],
        'no_follow' => true,
    ]);

    $this->putJson("/api/v1/admin/pages/{$page->id}", ['seo_description' => 'A new description'])->assertOk();

    $row = seoRowFor($page);

    expect($row->getTranslation('seo_description', 'en'))->toBe('A new description')
        ->and($row->getTranslation('seo_title', 'en'))->toBe('Keep this title')
        ->and($row->getTranslation('og_title', 'en'))->toBe('Keep this OG title')
        ->and($row->no_follow)->toBeTrue();
});

it('does not touch SEO at all when the request has no SEO keys', function () {
    $page = Page::factory()->published()->create([
        'seo_title' => ['en' => 'Untouched'],
        'no_index' => true,
        'og_image' => '/og/keep.png',
    ]);

    $this->putJson("/api/v1/admin/pages/{$page->id}", ['status' => 'inactive'])->assertOk();

    $row = seoRowFor($page);

    expect($row->getTranslation('seo_title', 'en'))->toBe('Untouched')
        ->and($row->no_index)->toBeTrue()
        ->and($row->og_image)->toBe('/og/keep.png');
});

it('clears a SEO key that is sent as null and turns a flag off with false', function () {
    $page = Page::factory()->published()->create([
        'seo_title' => ['en' => 'Remove me'],
        'og_image' => '/og/remove.png',
        'no_index' => true,
    ]);

    $this->putJson("/api/v1/admin/pages/{$page->id}", [
        'seo_title' => null,
        'og_image' => null,
        'no_index' => false,
    ])->assertOk();

    $row = seoRowFor($page);

    expect($row->seo_title)->toBeNull()
        ->and($row->og_image)->toBeNull()
        ->and($row->no_index)->toBeFalse()
        ->and(Seo::where('page_id', $page->id)->count())->toBe(1);
});

it('shows the full SEO of a page under a seo key, every field present', function () {
    $page = Page::factory()->published()->create([
        'seo_title' => ['en' => 'Shown title', 'bn' => 'শিরোনাম'],
        'twitter_image' => '/tw.png',
    ]);

    $this->getJson("/api/v1/admin/pages/{$page->id}")
        ->assertOk()
        ->assertJsonPath('data.seo.seo_title.en', 'Shown title')
        ->assertJsonPath('data.seo.seo_title.bn', 'শিরোনাম')
        ->assertJsonPath('data.seo.twitter_image', '/tw.png')
        ->assertJsonPath('data.seo.seo_description', null)
        ->assertJsonPath('data.seo.no_index', false)
        ->assertJsonStructure(['data' => ['seo' => [
            'seo_title', 'seo_description', 'og_title', 'og_description', 'twitter_title', 'twitter_description',
            'og_image', 'twitter_image', 'no_index', 'no_follow', 'canonical_base', 'canonical_slug',
        ]]]);
});

it('rejects an SEO key of the wrong shape', function () {
    $page = Page::factory()->published()->create();

    $this->putJson("/api/v1/admin/pages/{$page->id}", [
        'seo_title' => str_repeat('x', 300),
        'no_index' => 'maybe',
        'og_title' => 123,
    ])->assertUnprocessable()->assertJsonValidationErrors(['seo_title', 'no_index', 'og_title']);

    $this->putJson("/api/v1/admin/pages/{$page->id}", ['seo_title' => ['en' => str_repeat('x', 300)]])
        ->assertUnprocessable()->assertJsonValidationErrors(['seo_title.en']);
});

// ---------------------------------------------------------------- Posts

it('creates a post with SEO keys on its paired page row, and updates only what is sent', function () {
    $id = $this->postJson('/api/v1/admin/posts', [
        'title' => ['en' => 'Launch day', 'bn' => ''],
        'seo_title' => 'Launch day — what shipped',
        'og_description' => ['en' => 'Shared on social'],
        'no_index' => true,
    ])->assertCreated()->json('data.id');

    $page = Post::findOrFail($id)->page;
    $row = seoRowFor($page);

    expect($row->getTranslation('seo_title', 'en'))->toBe('Launch day — what shipped')
        ->and($row->getTranslation('og_description', 'en'))->toBe('Shared on social')
        ->and($row->no_index)->toBeTrue();

    $this->putJson("/api/v1/admin/posts/{$id}", ['twitter_title' => ['en' => 'Tweet title']])->assertOk();

    $row->refresh();
    expect($row->getTranslation('twitter_title', 'en'))->toBe('Tweet title')
        ->and($row->getTranslation('seo_title', 'en'))->toBe('Launch day — what shipped')
        ->and($row->no_index)->toBeTrue();
});

it('leaves a post\'s SEO alone when the update has no SEO keys, and can turn noindex off', function () {
    $id = $this->postJson('/api/v1/admin/posts', [
        'title' => ['en' => 'Steady post'],
        'seo_title' => 'Steady',
        'no_index' => true,
    ])->assertCreated()->json('data.id');

    $this->putJson("/api/v1/admin/posts/{$id}", ['status' => 'inactive'])->assertOk();

    $row = seoRowFor(Post::findOrFail($id)->page);
    expect($row->getTranslation('seo_title', 'en'))->toBe('Steady')->and($row->no_index)->toBeTrue();

    $this->putJson("/api/v1/admin/posts/{$id}", ['no_index' => false])->assertOk();

    expect(seoRowFor(Post::findOrFail($id)->page)->no_index)->toBeFalse();
});

it('shows a post\'s full SEO under a seo key', function () {
    $post = Post::factory()->active()->create();
    Page::factory()->published()->create(['type' => 'post', 'post_id' => $post->id, 'og_title' => ['en' => 'OG shown']]);

    $this->getJson("/api/v1/admin/posts/{$post->id}")
        ->assertOk()
        ->assertJsonPath('data.seo.og_title.en', 'OG shown')
        ->assertJsonStructure(['data' => ['seo' => ['seo_title', 'og_image', 'no_follow', 'canonical_base']]]);
});

// ---------------------------------------------------------------- Products

it('creates and updates a product\'s SEO through its paired page row', function () {
    $id = $this->postJson('/api/v1/admin/products', [
        'name' => ['en' => 'Green tea', 'bn' => ''],
        'status' => 'inactive',
        'seo_title' => 'Buy green tea',
        'twitter_description' => ['en' => 'Fresh leaves'],
    ])->assertCreated()->json('data.id');

    $page = Product::findOrFail($id)->page;

    expect(seoRowFor($page)->getTranslation('seo_title', 'en'))->toBe('Buy green tea');

    $this->putJson("/api/v1/admin/products/{$id}", ['no_follow' => true, 'og_image' => '/og/tea.png'])->assertOk();

    $row = seoRowFor($page);
    expect($row->no_follow)->toBeTrue()
        ->and($row->og_image)->toBe('/og/tea.png')
        ->and($row->getTranslation('seo_title', 'en'))->toBe('Buy green tea')
        ->and($row->getTranslation('twitter_description', 'en'))->toBe('Fresh leaves');

    // No SEO keys: nothing about the row changes.
    $this->putJson("/api/v1/admin/products/{$id}", ['status' => 'active'])->assertOk();

    expect(seoRowFor($page)->no_follow)->toBeTrue();
});

// ---------------------------------------------------------------- Categories

it('creates and updates a product category\'s SEO, and lists it', function () {
    $id = $this->postJson('/api/v1/admin/product-categories', [
        'name' => ['en' => 'Teas', 'bn' => ''],
        'seo_title' => 'All our teas',
        'canonical_base' => 'https://shop.example.com',
    ])->assertCreated()->json('data.id');

    $page = ProductCategory::findOrFail($id)->page;

    expect(seoRowFor($page)->getTranslation('seo_title', 'en'))->toBe('All our teas')
        ->and(seoRowFor($page)->canonical_base)->toBe('https://shop.example.com');

    $this->putJson("/api/v1/admin/product-categories/{$id}", ['seo_description' => ['en' => 'Loose leaf and bagged']])->assertOk();

    $row = seoRowFor($page);
    expect($row->getTranslation('seo_description', 'en'))->toBe('Loose leaf and bagged')
        ->and($row->getTranslation('seo_title', 'en'))->toBe('All our teas');

    $this->getJson('/api/v1/admin/product-categories')
        ->assertOk()
        ->assertJsonFragment(['canonical_base' => 'https://shop.example.com']);
});

// ---------------------------------------------------------------- Public

it('serves the SEO that was written through the admin API on the public API', function () {
    $id = $this->postJson('/api/v1/admin/pages', [
        'title' => ['en' => 'Press', 'bn' => ''],
        'status' => 'active',
        'seo_title' => 'Press kit',
        'no_index' => true,
    ])->assertCreated()->json('data.id');

    $slug = Page::findOrFail($id)->slug;

    $this->getJson("/api/v1/pages/{$slug}")
        ->assertOk()
        ->assertJsonPath('data.meta_data.seo_title', 'Press kit')
        ->assertJsonPath('data.meta_data.no_index', true);
});

// ---------------------------------------------------------------- Borrowed SEO

it('sets and clears the page whose SEO a page uses through seo_page_id', function () {
    $source = Page::factory()->published()->create(['seo_title' => ['en' => 'Source SEO']]);
    $page = Page::factory()->published()->create(['seo_title' => ['en' => 'Own SEO']]);

    $this->putJson("/api/v1/admin/pages/{$page->id}", ['seo_page_id' => $source->id])->assertOk();

    expect($page->fresh()->seo_page_id)->toBe($source->id)
        ->and($page->fresh()->seo_title)->toBe('Source SEO');

    // The admin show returns the page's own SEO plus which page it borrows from.
    $this->getJson("/api/v1/admin/pages/{$page->id}")
        ->assertOk()
        ->assertJsonPath('data.seo.seo_page_id', $source->id)
        ->assertJsonPath('data.seo.seo_title.en', 'Own SEO');

    // The public API presents the borrowed SEO.
    $page->update(['status' => 'active']);
    $this->getJson("/api/v1/pages/{$page->slug}")->assertOk()->assertJsonPath('data.meta_data.seo_title', 'Source SEO');

    $this->putJson("/api/v1/admin/pages/{$page->id}", ['seo_page_id' => null])->assertOk();

    expect($page->fresh()->seo_page_id)->toBeNull()
        ->and($page->fresh()->seo_title)->toBe('Own SEO');
});

it('rejects an seo_page_id that is not a page', function () {
    $page = Page::factory()->published()->create();

    $this->putJson("/api/v1/admin/pages/{$page->id}", ['seo_page_id' => 999999])
        ->assertUnprocessable()->assertJsonValidationErrors(['seo_page_id']);
});
