<?php

use App\Http\Controllers\Admin\BuilderAssetController;
use App\Livewire\Admin\Builders\Editor;
use App\Livewire\Admin\Builders\Index;
use App\Models\BuilderBlock;
use App\Models\BuilderDesign;
use App\Models\Language;
use App\Models\MediaLibrary;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Support\Builder\DynamicBlocks;
use App\Support\ContentCache;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->admin()->create());
});

it('has no connection to pages or SEO', function () {
    expect(Schema::hasColumn('builders', 'page_id'))->toBeFalse()
        ->and(method_exists(Page::class, 'builds'))->toBeFalse()
        ->and(method_exists(Page::class, 'builderHtml'))->toBeFalse()
        ->and(method_exists(Editor::class, 'saveSeo'))->toBeFalse();
});

it('duplicates a builder with its content under a new name and address', function () {
    $design = BuilderDesign::create(['name' => 'Landing', 'user_id' => auth()->id()]);
    $design->saveLocale('en', ['pages' => []], '<h1>Hello</h1>', '.a{color:red}');

    Livewire::test(Index::class)->call('duplicate', $design->id);

    $copy = BuilderDesign::firstWhere('name', 'Landing (copy)');
    expect($copy)->not->toBeNull()
        ->and($copy->id)->not->toBe($design->id)
        ->and($copy->slug)->not->toBe($design->slug)
        ->and($copy->html['en'])->toBe('<h1>Hello</h1>')
        ->and(BuilderDesign::count())->toBe(2);
});

it('creates a builder from the Builder menu and opens its editor', function () {
    Livewire::test(Index::class)
        ->set('name', 'Landing')
        ->call('create')
        ->assertRedirect();

    $design = BuilderDesign::firstWhere('name', 'Landing');
    expect($design)->not->toBeNull()
        ->and($design->slug)->toBe('landing')
        ->and(Page::count())->toBe(0);

    Livewire::test(Editor::class, ['id' => $design->id])->assertSee('pageBuilder', false);
});

it('keeps one design for every language and saves it under the primary one', function () {
    Language::create(['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_active' => true, 'is_default' => true]);
    Language::create(['code' => 'bn', 'name' => 'Bengali', 'native_name' => 'বাংলা', 'is_active' => true]);
    $design = BuilderDesign::create(['name' => 'Home']);

    Livewire::test(Editor::class, ['id' => $design->id])
        ->assertSet('locale', 'en')
        ->call('save', ['pages' => [['frames' => []]]], '<h1 data-t-bn="বাংলা">English</h1>', 'h1{color:red}');

    $design->refresh();
    expect($design->html)->toHaveKey('en')->and($design->html)->not->toHaveKey('bn')
        ->and($design->renderedHtml('en'))->toBe('<style>h1{color:red}</style><h1>English</h1>')
        ->and($design->renderedHtml('bn'))->toBe('<style>h1{color:red}</style><h1>বাংলা</h1>')
        ->and($design->locales())->toEqual(['en', 'bn'])
        ->and(BuilderDesign::count())->toBe(1);
});

it('serves a design at its own slug', function () {
    $design = BuilderDesign::create(['name' => 'Promo', 'slug' => 'promo']);
    $design->saveLocale('en', ['pages' => []], '<h1>Sale</h1>', '');

    $this->get('/promo')->assertOk()->assertSee('<h1>Sale</h1>', false);
});

it('saves a component as a reusable block and offers it on any design', function () {
    $a = BuilderDesign::create(['name' => 'A']);
    $b = BuilderDesign::create(['name' => 'B']);

    Livewire::test(Editor::class, ['id' => $a->id])
        ->call('saveBlock', 'Hero', '<section>Hero</section><style>section{color:red}</style>')
        ->assertHasNoErrors();

    expect(BuilderBlock::where('name', 'Hero')->exists())->toBeTrue()
        ->and(BuilderDesign::count())->toBe(2);

    Livewire::test(Editor::class, ['id' => $b->id])
        ->assertSee('Hero')
        ->call('deleteBlock', BuilderBlock::first()->id)
        ->assertDispatched('block-deleted');

    expect(BuilderBlock::count())->toBe(0);
});

it('uploads builder images into the media library', function () {
    Storage::fake('public');

    $request = Request::create('/x', 'POST', [], [], [
        'files' => [UploadedFile::fake()->image('hero.jpg', 400, 300)],
    ]);
    $request->setUserResolver(fn () => auth()->user());

    $json = app(BuilderAssetController::class)->store($request)->getData(true);

    expect($json['data'][0])->toHaveKeys(['src', 'name', 'type'])
        ->and(MediaLibrary::where('original_filename', 'hero.jpg')->exists())->toBeTrue();
});

it('shows the builder picked on the home page, else the theme page', function () {
    Setting::set('site_theme', 'default');
    $home = Page::factory()->published()->create(['title' => ['en' => 'Home', 'bn' => ''], 'slug' => 'home', 'type' => 'page']);
    $design = BuilderDesign::create(['name' => 'Landing']);
    $design->saveLocale('en', ['pages' => []], '<h1>From builder</h1>', '');

    // Builder picked: it is the home page.
    $home->update(['builder_id' => $design->id]);
    ContentCache::bust();
    $this->get('/')->assertOk()->assertSee('<h1>From builder</h1>', false);

    // None picked: the theme's own home page.
    $home->update(['builder_id' => null]);
    ContentCache::bust();
    $this->get('/')->assertOk()->assertDontSee('<h1>From builder</h1>', false);

    // No builder and the active theme ships no home template: 404.
    $index = base_path('themes/default/views/index.blade.php');
    $backup = file_get_contents($index);
    try {
        unlink($index);
        $this->get('/')->assertNotFound();
    } finally {
        file_put_contents($index, $backup);
    }
});

it('replaces a products placeholder with live products and keeps plain HTML untouched', function () {
    $product = Product::factory()->create(['name' => ['en' => 'Blue Mug'], 'price' => 120, 'status' => 'active']);
    $draft = Product::factory()->create(['name' => ['en' => 'Hidden Mug'], 'status' => 'inactive']);

    $html = DynamicBlocks::block('products', ['limit' => '4', 'columns' => '2']);

    expect($html)->toContain('Blue Mug')->not->toContain('Hidden Mug');

    // The page keeps plain HTML as is and swaps each placeholder for a lazy Livewire block.
    $page = DynamicBlocks::render('<h1>Hi</h1><div data-cw="products" data-limit="4"></div>');
    expect($page)->toContain('<h1>Hi</h1>')->toContain('wire:id')->not->toContain('data-cw="products"')
        ->and(DynamicBlocks::render('<p>x</p>'))->toBe('<p>x</p>');
});

it('filters dynamic products by category, clamps the limit, escapes output and prints the style once', function () {
    $cat = ProductCategory::factory()->create();
    $in = Product::factory()->published()->create(['name' => ['en' => '<b>In</b> Mug']]);
    $out = Product::factory()->published()->create(['name' => ['en' => 'Other Cup']]);
    $in->categories()->attach($cat->id);

    $html = DynamicBlocks::block('products', ['category' => (string) $cat->id, 'limit' => '9999'], true)
        .DynamicBlocks::block('products', ['category' => 'abc', 'columns' => '99'], false);

    expect($html)->toContain('&lt;b&gt;In&lt;/b&gt; Mug')
        ->not->toContain('<b>In</b>')
        ->and(substr_count($html, '<style>'))->toBe(1)
        ->and(substr_count($html, 'Other Cup'))->toBe(1) // only the unfiltered second block
        ->and($html)->toContain('--cw-cols:6');
});

it('renders the posts, services and brands dynamic blocks', function () {
    $post = Post::factory()->published()->create(['title' => ['en' => 'Hello Post']]);
    $draft = Post::factory()->draft()->create(['title' => ['en' => 'Secret Draft']]);
    $service = Service::factory()->create(['name' => ['en' => 'Web Design'], 'status' => 'active']);
    $brand = ProductBrand::factory()->create(['name' => ['en' => 'Acme'], 'status' => 'active', 'logo' => 'https://x.test/acme.png']);

    $html = DynamicBlocks::block('posts', ['limit' => '3'], true)
        .DynamicBlocks::block('services', [], false)
        .DynamicBlocks::block('brands', [], true);

    expect($html)->toContain('Hello Post')->not->toContain('Secret Draft')
        ->toContain('Web Design')
        ->toContain('https://x.test/acme.png')
        ->and(substr_count($html, '<style>'))->toBe(2); // one shared card stylesheet + one for brands
});
