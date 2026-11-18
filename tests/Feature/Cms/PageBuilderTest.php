<?php

use App\Http\Controllers\Admin\BuilderAssetController;
use App\Livewire\Admin\Builders\Editor;
use App\Livewire\Admin\Builders\Index;
use App\Livewire\Admin\ThemeSettings\Edit;
use App\Models\BuilderBlock;
use App\Models\BuilderDesign;
use App\Models\Language;
use App\Models\MediaLibrary;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Setting;
use App\Models\User;
use App\Support\Builder\DynamicBlocks;
use App\Support\ThemeSettings;
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

it('saves the design per language and keeps the others untouched', function () {
    Language::create(['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_active' => true, 'is_default' => true]);
    Language::create(['code' => 'bn', 'name' => 'Bengali', 'native_name' => 'বাংলা', 'is_active' => true]);
    $design = BuilderDesign::create(['name' => 'Home']);

    $editor = Livewire::test(Editor::class, ['id' => $design->id])
        ->call('save', ['pages' => [['frames' => []]]], '<h1>English</h1>', 'h1{color:red}');

    $result = $editor->call('switchLocale', 'bn')->assertSet('locale', 'bn')->effects['returns'][0];
    expect($result['project'])->toHaveKey('pages');

    $editor->call('save', ['pages' => []], '<h1>বাংলা</h1>', '');

    $design->refresh();
    expect($design->html)->toEqual(['en' => '<h1>English</h1>', 'bn' => '<h1>বাংলা</h1>'])
        ->and($design->renderedHtml('en'))->toBe('<style>h1{color:red}</style><h1>English</h1>')
        ->and($design->localeFor('xx'))->toBe('en')
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

it('shows the builder picked in Page Settings → Home as the home page', function () {
    Setting::set('site_theme', 'default');
    $design = BuilderDesign::create(['name' => 'Landing']);
    $design->saveLocale('en', ['pages' => []], '<h1>From builder</h1>', '');

    ThemeSettings::merge('default', ['theme_default_home_builder' => (string) $design->id]);

    expect(ThemeSettings::groupBuilder('default', 'home')?->id)->toBe($design->id);

    $this->get('/')->assertOk()->assertSee('<h1>From builder</h1>', false);

    // Back to None: the theme's own home page again.
    ThemeSettings::merge('default', ['theme_default_home_builder' => '']);
    $this->get('/')->assertOk()->assertDontSee('<h1>From builder</h1>', false);
});

it('lets Page Settings pick a builder for a group', function () {
    Setting::set('site_theme', 'default');
    $design = BuilderDesign::create(['name' => 'Landing']);

    Livewire::test(Edit::class, ['slug' => 'default'])
        ->assertSee('Which builder should this page show?')
        ->set('groupBuilder.home', $design->id)
        ->call('save')
        ->assertHasNoErrors();

    expect(ThemeSettings::groupBuilder('default', 'home')?->id)->toBe($design->id);
});

it('replaces a products placeholder with live products and keeps plain HTML untouched', function () {
    $product = Product::factory()->create(['name' => ['en' => 'Blue Mug'], 'price' => 120, 'status' => 'active']);
    $draft = Product::factory()->create(['name' => ['en' => 'Hidden Mug'], 'status' => 'inactive']);

    $html = DynamicBlocks::render('<h1>Hi</h1><div data-cw="products" data-limit="4" data-columns="2"></div>');

    expect($html)->toContain('<h1>Hi</h1>')
        ->toContain('Blue Mug')
        ->not->toContain('Hidden Mug')
        ->not->toContain('data-cw="products"')
        ->and(DynamicBlocks::render('<p>x</p>'))->toBe('<p>x</p>');
});

it('filters dynamic products by category, clamps the limit, escapes output and prints the style once', function () {
    $cat = ProductCategory::factory()->create();
    $in = Product::factory()->published()->create(['name' => ['en' => '<b>In</b> Mug']]);
    $out = Product::factory()->published()->create(['name' => ['en' => 'Other Cup']]);
    $in->categories()->attach($cat->id);

    $html = DynamicBlocks::render(
        '<div data-cw="products" data-category="'.$cat->id.'" data-limit="9999"></div><div data-cw="products" data-category="abc" data-columns="99"></div>'
    );

    expect($html)->toContain('&lt;b&gt;In&lt;/b&gt; Mug')
        ->not->toContain('<b>In</b>')
        ->and(substr_count($html, '<style>'))->toBe(1)
        ->and(substr_count($html, 'Other Cup'))->toBe(1) // only the unfiltered second block
        ->and($html)->toContain('--cw-cols:6');
});

it('renders the posts, services and brands dynamic blocks', function () {
    $post = \App\Models\Post::factory()->published()->create(['title' => ['en' => 'Hello Post']]);
    $draft = \App\Models\Post::factory()->draft()->create(['title' => ['en' => 'Secret Draft']]);
    $service = \App\Models\Service::factory()->create(['name' => ['en' => 'Web Design'], 'status' => 'active']);
    $brand = \App\Models\ProductBrand::factory()->create(['name' => ['en' => 'Acme'], 'status' => 'active', 'logo' => 'https://x.test/acme.png']);

    $html = \App\Support\Builder\DynamicBlocks::render(
        '<div data-cw="posts" data-limit="3"></div><div data-cw="services"></div><div data-cw="brands"></div>'
    );

    expect($html)->toContain('Hello Post')->not->toContain('Secret Draft')
        ->toContain('Web Design')
        ->toContain('https://x.test/acme.png')
        ->and(substr_count($html, '<style>'))->toBe(2); // one shared card stylesheet + one for brands
});
