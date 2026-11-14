<?php

use App\Models\Language;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Support\Seo\SeoResolver;

/**
 * The schema.org JSON-LD the public API ships on a single page, product or post.
 *
 * It has to be the same document the storefront prints in its <head>: the API
 * resolves it against the record's public URL, not against api/v1/..., so the
 * canonical, the @ids and the robots decision agree with the website.
 */
beforeEach(function () {
    Language::create(['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_active' => true, 'is_default' => true]);
    Language::create(['code' => 'bn', 'name' => 'Bengali', 'native_name' => 'বাংলা', 'is_active' => true]);

    Setting::set('site_theme', 'classic_ecommerce');
    Setting::set('site_name', 'Codeware');
    Setting::set('seo_site_url', 'https://codeware.test');

    SeoResolver::flush();
});

afterEach(fn () => SeoResolver::flush());

function apiNode(?array $schema, string $type): array
{
    expect($schema)->toBeArray()->and($schema['@context'])->toBe('https://schema.org');

    $matches = array_values(array_filter($schema['@graph'], fn ($n) => ($n['@type'] ?? null) === $type));
    expect($matches)->toHaveCount(1, "expected exactly one {$type} node");

    return $matches[0];
}

it('serves a product with its schema.org graph', function () {
    $product = Product::factory()->published()->create();
    $page = Page::create(['type' => 'product', 'product_id' => $product->id, 'user_id' => User::factory()->create()->id, 'title' => ['en' => 'Widget'], 'status' => 'active']);

    $schema = $this->getJson('/api/v1/products/'.$page->slug)->assertOk()->json('data.schema');

    $node = apiNode($schema, 'Product');
    expect($node['url'])->toBe('https://codeware.test/products/'.$page->slug)
        ->and($node['offers']['priceCurrency'])->toBeString();
    // The graph is the storefront's, not the API's own address.
    expect(json_encode($schema))->not->toContain('api/v1');
});

it('serves a post as a BlogPosting', function () {
    $post = Post::factory()->published()->create();
    $page = Page::create(['type' => 'post', 'post_id' => $post->id, 'user_id' => User::factory()->create()->id, 'title' => ['en' => 'Hello'], 'status' => 'active']);

    $schema = $this->getJson('/api/v1/posts/'.$page->slug)->assertOk()->json('data.schema');

    expect(apiNode($schema, 'BlogPosting')['url'])->toBe('https://codeware.test/blog/'.$page->slug);
});

it('serves a plain page and the home page', function () {
    $user = User::factory()->create()->id;
    $about = Page::create(['type' => 'page', 'user_id' => $user, 'title' => ['en' => 'About'], 'status' => 'active']);

    $schema = $this->getJson('/api/v1/pages/'.$about->slug)->assertOk()->json('data.schema');
    expect(apiNode($schema, 'WebPage')['url'])->toBe('https://codeware.test/'.$about->slug);

    $home = Page::where('slug', 'home')->first()
        ?? Page::create(['type' => 'page', 'user_id' => $user, 'title' => ['en' => 'Home'], 'slug' => 'home', 'status' => 'active']);

    $schema = $this->getJson('/api/v1/pages/'.$home->slug)->assertOk()->json('data.schema');
    expect(apiNode($schema, 'WebPage')['url'])->toBe('https://codeware.test/');
});

it('follows ?locale into the graph and puts the app locale back', function () {
    $product = Product::factory()->published()->create(['name' => ['en' => 'Chair', 'bn' => 'চেয়ার']]);
    $page = Page::create(['type' => 'product', 'product_id' => $product->id, 'user_id' => User::factory()->create()->id, 'title' => ['en' => 'Chair', 'bn' => 'চেয়ার'], 'status' => 'active']);

    $before = app()->getLocale();
    $schema = $this->getJson('/api/v1/products/'.$page->slug.'?locale=bn')->assertOk()->json('data.schema');

    expect(apiNode($schema, 'Product')['name'])->toBe('চেয়ার')
        ->and(apiNode($schema, 'WebSite')['inLanguage'])->toBe('bn')
        ->and(app()->getLocale())->toBe($before);
});

it('sends schema null for a noindex page', function () {
    $page = Page::create(['type' => 'page', 'user_id' => User::factory()->create()->id, 'title' => ['en' => 'Secret'], 'status' => 'active', 'no_index' => true]);

    $this->getJson('/api/v1/pages/'.$page->slug)->assertOk()->assertJsonPath('data.schema', null);
});
