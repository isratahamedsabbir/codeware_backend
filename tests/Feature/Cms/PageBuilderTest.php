<?php

use App\Http\Controllers\Admin\BuilderAssetController;
use App\Livewire\Admin\Pages\Builder;
use App\Models\Language;
use App\Models\MediaLibrary;
use App\Models\Page;
use App\Models\PageBlock;
use App\Models\PageBuild;
use App\Models\User;
use App\Support\Locale;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->admin()->create());
});

it('renders the builder component with its Alpine hook', function () {
    $page = Page::factory()->create(['type' => 'page']);

    Livewire::test(Builder::class, ['id' => $page->id])->assertSee('pageBuilder', false);
});

it('saves the builder output into the page', function () {
    $page = Page::factory()->create(['type' => 'page']);

    $original = ['content' => $page->getTranslations('content'), 'puck_data' => $page->puck_data];

    Livewire::test(Builder::class, ['id' => $page->id])
        ->call('save', ['pages' => [['frames' => []]]], '<h1>Hi</h1>', 'h1{color:red}')
        ->assertHasNoErrors();

    $page->refresh();
    $locale = Livewire::test(Builder::class, ['id' => $page->id])->get('locale');

    $build = $page->build($locale);
    expect($build->html)->toBe('<h1>Hi</h1>')
        ->and($build->css)->toBe('h1{color:red}')
        ->and($build->project)->toHaveKey('pages')
        ->and($page->hasBuilderContent($locale))->toBeTrue()
        ->and($page->builderHtml($locale))->toBe('<style>h1{color:red}</style><h1>Hi</h1>');

    // Puck's columns are never touched.
    expect($page->getTranslations('content'))->toEqual($original['content'])
        ->and($page->puck_data)->toEqual($original['puck_data']);

    // Saving again updates the row rather than adding one; re-opening still renders.
    Livewire::test(Builder::class, ['id' => $page->id])->call('save', ['pages' => []], '<p>2</p>', '')->assertOk();
    expect(PageBuild::where('page_id', $page->id)->count())->toBe(1);
});

it('shows the primary design on every locale until that locale has its own', function () {
    $page = Page::factory()->create(['type' => 'page']);
    $primary = Locale::primary();
    PageBuild::create(['page_id' => $page->id, 'locale' => $primary, 'html' => '<p>main</p>', 'css' => '']);
    $page->load('builds');

    expect($page->hasBuilderContent('xx'))->toBeTrue()
        ->and($page->builderHtml('xx'))->toContain('<p>main</p>');

    PageBuild::create(['page_id' => $page->id, 'locale' => 'xx', 'html' => '<p>own</p>', 'css' => '']);

    expect($page->load('builds')->builderHtml('xx'))->toContain('<p>own</p>');
});

it('keeps separate builder designs for each active language', function () {
    Language::create(['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_active' => true, 'is_default' => true]);
    Language::create(['code' => 'bn', 'name' => 'Bengali', 'native_name' => 'বাংলা', 'is_active' => true]);

    $page = Page::factory()->create([
        'type' => 'page',
        'title' => ['en' => 'Home', 'bn' => 'হোম'],
    ]);

    $builder = Livewire::test(Builder::class, ['id' => $page->id])
        ->assertSee('English')
        ->assertSee('বাংলা')
        ->call('save', ['pages' => [['frames' => []]]], '<h1>English</h1>', '');

    $switch = $builder->call('switchLocale', 'bn')->assertSet('locale', 'bn');
    $result = $switch->effects['returns'][0];

    expect($result['project'])->toHaveKey('pages')
        ->and($result['title'])->toBe('হোম');

    $builder->call('save', ['pages' => [['frames' => []]]], '<h1>বাংলা</h1>', '');

    expect(PageBuild::where('page_id', $page->id)->where('locale', 'en')->value('html'))->toBe('<h1>English</h1>')
        ->and(PageBuild::where('page_id', $page->id)->where('locale', 'bn')->value('html'))->toBe('<h1>বাংলা</h1>');
});

it('saves builder SEO into the page own SEO without changing its borrowed source', function () {
    Language::create(['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_active' => true, 'is_default' => true]);
    Language::create(['code' => 'bn', 'name' => 'Bengali', 'native_name' => 'বাংলা', 'is_active' => true]);

    $source = Page::factory()->create(['title' => ['en' => 'Source']]);
    $source->update(['seo_title' => ['en' => 'Source SEO']]);

    $page = Page::factory()->create([
        'type' => 'page',
        'title' => ['en' => 'Home', 'bn' => 'হোম'],
        'seo_page_id' => $source->id,
    ]);
    $page->update(['seo_title' => ['en' => 'Own English SEO', 'bn' => 'নিজের বাংলা SEO']]);

    Livewire::test(Builder::class, ['id' => $page->id])
        ->assertSee('Search Engine (SEO)')
        ->call('saveSeo', [
            'seo_title' => 'Updated English SEO',
            'seo_description' => 'Search description',
            'og_title' => 'Social title',
            'og_description' => 'Social description',
            'og_image' => '/media/og.png',
            'twitter_title' => 'Twitter title',
            'twitter_description' => 'Twitter description',
            'twitter_image' => '/media/twitter.png',
            'no_index' => true,
            'no_follow' => false,
            'canonical_base' => 'https://example.com',
            'canonical_slug' => 'home',
        ])
        ->assertHasNoErrors();

    $page = Page::findOrFail($page->id);

    expect($page->ownSeoTranslations('seo_title'))->toMatchArray([
        'en' => 'Updated English SEO',
        'bn' => 'নিজের বাংলা SEO',
    ])
        ->and($page->ownSeoAttribute('no_index'))->toBeTrue()
        ->and($page->seo_title)->toBe('Source SEO')
        ->and($source->ownSeoTranslations('seo_title')['en'])->toBe('Source SEO');
});

it('saves a component as a reusable block and offers it on any page', function () {
    $pageA = Page::factory()->create(['type' => 'page']);
    $pageB = Page::factory()->create(['type' => 'page']);

    $id = Livewire::test(Builder::class, ['id' => $pageA->id])
        ->call('saveBlock', 'Hero', '<section>Hero</section><style>section{color:red}</style>')
        ->assertHasNoErrors()
        ->effects['returns'][0] ?? null;

    expect(PageBlock::where('name', 'Hero')->exists())->toBeTrue();

    Livewire::test(Builder::class, ['id' => $pageB->id])
        ->assertSee('Hero')
        ->call('deleteBlock', PageBlock::first()->id)
        ->assertDispatched('block-deleted');

    expect(PageBlock::count())->toBe(0);
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
