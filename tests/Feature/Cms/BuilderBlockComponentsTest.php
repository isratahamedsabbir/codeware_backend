<?php

use App\Models\Post;
use App\Support\Builder\DynamicBlocks;
use App\View\Components\Builder\Block;
use Illuminate\Support\Facades\Blade;

it('discovers every dynamic block from the Blade component folder', function () {
    $registry = DynamicBlocks::registry();

    expect(array_keys($registry))->toBe(['brands', 'language-switcher', 'posts', 'products', 'services']);

    foreach ($registry as $class) {
        expect(is_subclass_of($class, Block::class))->toBeTrue();
    }
});

it('describes each block to the editor, with one input per language for translatable fields', function () {
    $languages = [['code' => 'en', 'label' => 'English'], ['code' => 'bn', 'label' => 'বাংলা']];
    $products = collect(DynamicBlocks::definitions($languages))->firstWhere('key', 'products');
    $names = array_column($products['fields'], 'name');

    expect($names)->toContain('heading', 'heading-bn', 'category', 'limit', 'columns', 'order', 'featured')
        ->and($products['defaults'])->toMatchArray(['limit' => '8', 'columns' => '4', 'featured' => 'false', 'heading-bn' => ''])
        ->and(collect($products['fields'])->firstWhere('name', 'heading-bn')['label'])->toBe('Heading (বাংলা)');

    // A single-language site gets no extra inputs.
    $single = collect(DynamicBlocks::definitions([['code' => 'en', 'label' => 'English']]))->firstWhere('key', 'posts');
    expect(array_column($single['fields'], 'name'))->toBe(['heading', 'subheading', 'limit', 'columns']);
});

it('prints the visitor language for a translated option and falls back to the primary text', function () {
    Post::factory()->published()->create();
    $options = ['heading' => 'Latest news', 'heading-bn' => 'সর্বশেষ খবর', 'subheading' => 'Read on', 'subheading-bn' => ''];

    $bn = DynamicBlocks::resolveLocale($options, 'bn');
    expect($bn)->toBe(['heading' => 'সর্বশেষ খবর', 'subheading' => 'Read on'])
        ->and(DynamicBlocks::block('posts', $bn))->toContain('সর্বশেষ খবর')->toContain('Read on');

    expect(DynamicBlocks::resolveLocale($options, 'en'))->toBe(['heading' => 'Latest news', 'subheading' => 'Read on']);
});

it('resolves the language before handing the placeholder to the lazy component', function () {
    $page = DynamicBlocks::render('<div data-cw="posts" data-heading="Hello" data-heading-bn="হ্যালো"></div>', 'bn');

    expect($page)->toContain('wire:id')->toContain('\u09b9\u09cd')->not->toContain('heading-bn');
});

it('works as a plain Blade component too', function () {
    Post::factory()->published()->create(['title' => ['en' => 'Hello Post']]);

    expect(Blade::render('<x-builder.posts :options="[\'limit\' => \'2\', \'heading\' => \'News\']" />'))
        ->toContain('Hello Post')->toContain('News');
});

it('prints a language switcher that links to the other language', function () {
    \App\Models\Language::create(['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'is_active' => true, 'is_default' => true, 'sort_order' => 1]);
    \App\Models\Language::create(['code' => 'bn', 'name' => 'Bengali', 'native_name' => 'বাংলা', 'is_active' => true, 'sort_order' => 2]);
    \Illuminate\Support\Facades\Cache::forget('languages:active');
    app()->setLocale('en');

    $html = DynamicBlocks::block('language-switcher', ['email' => 'info@example.com', 'phone' => '+880 1700-000000', 'background' => 'red;x']);

    expect($html)->toContain('href="?lang=bn"')->toContain('বাংলা')
        ->toContain('mailto:info@example.com')->toContain('tel:+8801700000000')
        ->toContain('background:#043c26')->not->toContain('red;x');
});
