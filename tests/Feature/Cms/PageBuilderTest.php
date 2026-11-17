<?php

use App\Http\Controllers\Admin\BuilderAssetController;
use App\Livewire\Admin\Builders\Editor;
use App\Livewire\Admin\Builders\Index;
use App\Models\BuilderBlock;
use App\Models\BuilderDesign;
use App\Models\Language;
use App\Models\MediaLibrary;
use App\Models\Page;
use App\Models\User;
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
