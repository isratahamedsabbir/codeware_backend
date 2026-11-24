<?php

use App\Livewire\Admin\Pages\Form;
use App\Livewire\Admin\Env\Index;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Setting;
use App\Models\User;
use App\Support\CustomCode;
use Database\Seeders\RolePermissionSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Setting::set('custom_head_code', '<meta name="custom-head-probe" content="1">');
    Setting::set('custom_body_code', '<script>window.customBodyProbe = 1;</script>');
});

it('injects the custom head and body code on every theme\'s public pages', function (string $theme) {
    Setting::set('site_theme', $theme);

    $html = $this->get('/')->assertOk()->getContent();

    // Head code lands inside <head>, body code just before </body>.
    expect(strpos($html, 'custom-head-probe'))->toBeLessThan(strpos($html, '</head>'))
        ->and($html)->toContain('<script>window.customBodyProbe = 1;</script>')
        ->and(strpos($html, 'customBodyProbe'))->toBeLessThan(strrpos($html, '</body>'))
        ->and(strpos($html, 'customBodyProbe'))->toBeGreaterThan(strpos($html, '</head>'));
})->with(['classic_ecommerce', 'portfolio', 'default']);

it('injects the custom code on inner ecommerce pages too', function () {
    Setting::set('site_theme', 'classic_ecommerce');

    $this->get('/shop')->assertOk()
        ->assertSee('custom-head-probe', false)
        ->assertSee('customBodyProbe', false);
});

it('injects the custom code on the storefront login page, like any other public page', function () {
    // A theme's own auth/login template, so a public page of the active theme —
    // which is where the code used to be missing, because that page used to be
    // the shared admin-styled one rather than the theme's.
    Setting::set('site_theme', 'classic_ecommerce');

    $this->get(route('login'))->assertOk()
        ->assertSee('custom-head-probe', false)
        ->assertSee('customBodyProbe', false);
});

it('keeps the custom code off the admin panel', function () {
    Setting::set('site_theme', 'classic_ecommerce');
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->admin()->create());

    $this->get(config('app.admin_url').'/developer-tools')->assertOk()
        ->assertDontSee('custom-head-probe', false)
        ->assertDontSee('customBodyProbe', false);
});

it('outputs nothing when no custom code is set', function () {
    Setting::set('site_theme', 'classic_ecommerce');
    Setting::set('custom_head_code', '');
    Setting::set('custom_body_code', '');

    $this->get('/')->assertOk()
        ->assertDontSee('custom-head-probe', false)
        ->assertDontSee('customBodyProbe', false);
});

it('prints the google tag manager container from config on public pages', function () {
    Setting::set('site_theme', 'classic_ecommerce');
    config(['services.google_tag_manager.id' => 'GTM-TEST123']);

    $html = $this->get('/')->assertOk()->getContent();

    expect($html)
        ->toContain('https://www.googletagmanager.com/gtm.js?id=GTM-TEST123')
        ->and(strpos($html, 'gtm.js?id=GTM-TEST123'))->toBeLessThan(strpos($html, '</head>'));
});

it('renders page pushes from the datalayer stack before gtm.js', function () {
    Setting::set('site_theme', 'classic_ecommerce');
    config(['services.google_tag_manager.id' => 'GTM-TEST123']);

    view()->startPush('datalayer', '<script>window.dataLayer.push({"event":"probe"});</script>');

    $html = $this->get('/')->assertOk()->getContent();

    expect(strpos($html, '"event":"probe"'))->not->toBeFalse()
        ->and(strpos($html, '"event":"probe"'))->toBeLessThan(strpos($html, 'gtm.js?id=GTM-TEST123'));
});

it('loads no google tag manager container when the id is blank', function () {
    Setting::set('site_theme', 'classic_ecommerce');
    config(['services.google_tag_manager.id' => null]);

    $this->get('/')->assertOk()
        ->assertDontSee('googletagmanager.com/gtm.js', false);
});

it('outputs a snippet only on the pages that picked it', function () {
    Setting::set('site_theme', 'classic_ecommerce');
    Setting::set('custom_code_rules', json_encode([
        ['id' => 'abc123', 'name' => 'About pixel', 'head' => '<meta name="snippet-probe" content="1">', 'body' => '<script>window.snippetProbe = 1;</script>'],
    ]));
    Page::factory()->published()->create(['title' => ['en' => 'About', 'bn' => ''], 'slug' => 'about', 'custom_code_id' => 'abc123']);
    Page::factory()->published()->create(['title' => ['en' => 'Contact', 'bn' => ''], 'slug' => 'contact']);

    $this->get('/about')->assertOk()
        ->assertSee('snippet-probe', false)
        ->assertSee('snippetProbe', false)
        ->assertSee('custom-head-probe', false);

    $this->get('/contact')->assertOk()
        ->assertDontSee('snippet-probe', false)
        ->assertDontSee('snippetProbe', false)
        ->assertSee('custom-head-probe', false);
});

it('saves named custom code snippets from Developer Tools', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->admin()->create());

    Livewire\Livewire::test(Index::class)
        ->call('addCodeRule')
        ->set('codeRules.0.name', 'Product pixel')
        ->set('codeRules.0.head', '<meta name="p" content="1">')
        ->call('saveSiteData')
        ->assertHasNoErrors();

    $rules = CustomCode::rules();

    expect($rules)->toHaveCount(1)
        ->and($rules[0])->toMatchArray(['name' => 'Product pixel', 'head' => '<meta name="p" content="1">', 'body' => ''])
        ->and($rules[0]['id'])->not->toBe('');
});

describe('page-type coverage', function () {
    beforeEach(function () {
        Setting::set('site_theme', 'classic_ecommerce');
        Setting::set('custom_head_code', '<meta name="g-head" content="1">');
        Setting::set('custom_code_rules', json_encode([
            ['id' => 'aaa', 'name' => 'A', 'head' => '<meta name="snip-a" content="1">', 'body' => '<i id="body-a"></i>'],
            ['id' => 'bbb', 'name' => 'B', 'head' => '<meta name="snip-b" content="1">', 'body' => ''],
        ]));
    });

    it('loads the picked snippet on a product page', function () {
        $p = Product::factory()->published()->create();
        Page::factory()->published()->create(['type' => 'product', 'product_id' => $p->id, 'slug' => 'prod-x', 'custom_code_id' => 'aaa']);
        $this->get('/products/prod-x')->assertOk()->assertSee('g-head', false)->assertSee('snip-a', false)->assertSee('body-a', false)->assertDontSee('snip-b', false);
    });

    it('loads the picked snippet on a blog post', function () {
        $p = Post::factory()->published()->create();
        Page::factory()->published()->create(['type' => 'post', 'post_id' => $p->id, 'slug' => 'post-x', 'custom_code_id' => 'bbb']);
        $this->get('/blog/post-x')->assertOk()->assertSee('snip-b', false)->assertDontSee('snip-a', false);
    });

    it('redirects a product category page to the filtered shop', function () {
        $c = ProductCategory::factory()->create(['status' => 'active']);
        Page::factory()->published()->create(['type' => 'product_category', 'category_id' => $c->id, 'slug' => 'cat-x', 'custom_code_id' => 'aaa']);
        $this->get('/category/cat-x')->assertRedirect('/shop?category=cat-x');
    });

    it('loads the picked snippet on the homepage', function () {
        Page::factory()->published()->create(['title' => ['en' => 'Home', 'bn' => ''], 'slug' => 'home', 'custom_code_id' => 'bbb']);
        $this->get('/')->assertOk()->assertSee('snip-b', false);
    });

    it('falls back to the global code when the picked snippet no longer exists', function () {
        Page::factory()->published()->create(['title' => ['en' => 'About', 'bn' => ''], 'slug' => 'about', 'custom_code_id' => 'gone']);
        $this->get('/about')->assertOk()->assertSee('g-head', false)->assertDontSee('snip-', false);
    });

});

it('exposes the picked snippet in the public page API meta_data', function () {
    Setting::set('custom_code_rules', json_encode([
        ['id' => 'aaa', 'name' => 'A', 'head' => '<meta name="snip-a" content="1">', 'body' => '<i></i>'],
    ]));
    Page::factory()->published()->create(['title' => ['en' => 'About', 'bn' => ''], 'slug' => 'about', 'custom_code_id' => 'aaa']);
    Page::factory()->published()->create(['title' => ['en' => 'Contact', 'bn' => ''], 'slug' => 'contact']);

    $this->getJson('/api/v1/pages/about')->assertOk()
        ->assertJsonPath('data.meta_data.custom_code.id', 'aaa')
        ->assertJsonPath('data.meta_data.custom_code.head', '<meta name="snip-a" content="1">')
        ->assertJsonPath('data.meta_data.custom_code.body', '<i></i>');

    $this->getJson('/api/v1/pages/contact')->assertOk()
        ->assertJsonPath('data.meta_data.custom_code', null);
});
