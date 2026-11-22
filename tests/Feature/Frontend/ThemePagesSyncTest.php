<?php

use App\Models\Page;
use App\Models\Setting;
use App\Models\User;
use App\Support\ThemePages;
use App\Support\ThemeSettings;
use App\Support\Themes;
use Illuminate\Support\Facades\File;

afterEach(function () {
    File::deleteDirectory(Themes::path().'/bare');
    Themes::forget();
});

it('creates the pages a theme lists or ships, never overwrites, and serves them', function () {
    $dir = Themes::path().'/bare';
    File::ensureDirectoryExists($dir.'/views');
    File::ensureDirectoryExists($dir.'/data/pages');
    File::put($dir.'/views/index.blade.php', '<p>home</p>');
    File::put($dir.'/views/page.blade.php', '<p>PAGE:{{ $title }}</p>');
    File::put($dir.'/theme.json', json_encode(['name' => 'Bare', 'pages' => ['about', 'team']]));
    File::put($dir.'/data/pages/pricing.json', json_encode(['title' => 'Our Pricing']));
    Themes::forget();

    User::factory()->create();
    Page::factory()->published()->create(['title' => ['en' => 'My About', 'bn' => ''], 'slug' => 'about']);

    $r = ThemePages::sync('bare');

    expect($r['created'])->toEqualCanonicalizing(['team', 'pricing'])
        ->and($r['existing'])->toBe(['about'])
        ->and(Page::where('slug', 'about')->first()->getTranslation('title', 'en'))->toBe('My About')
        ->and(Page::where('slug', 'pricing')->first()->getTranslation('title', 'en'))->toBe('Our Pricing');

    // Re-running is a no-op.
    expect(ThemePages::sync('bare')['created'])->toBe([]);

});

it('serves an extra page a real theme ships in data/pages at /slug', function () {
    $dir = Themes::path().'/classic_ecommerce/data/pages';
    File::ensureDirectoryExists($dir);
    File::put($dir.'/pricing.json', json_encode(['title' => 'Our Pricing']));
    User::factory()->create();

    try {
        Setting::set('site_theme', 'classic_ecommerce');
        expect(ThemePages::sync('classic_ecommerce')['created'])->toContain('pricing');

        $this->get('/pricing')->assertOk()->assertSee('Our Pricing');
    } finally {
        File::deleteDirectory(Themes::path().'/classic_ecommerce/data');
    }
});

it('treats the keys of a slug-keyed pages map as pages and keeps their settings readable', function () {
    $dir = Themes::path().'/bare';
    File::ensureDirectoryExists($dir.'/views');
    File::put($dir.'/views/index.blade.php', '<p>home</p>');
    File::put($dir.'/theme.json', json_encode([
        'name' => 'Bare',
        'pages' => ['pricing' => ['label' => 'Our Plans', 'hero' => ['title' => 'Pick one']]],
    ]));
    Themes::forget();
    User::factory()->create();

    expect(ThemePages::sync('bare')['created'])->toBe(['pricing'])
        ->and(Page::where('slug', 'pricing')->first()->getTranslation('title', 'en'))->toBe('Our Plans')
        ->and(ThemeSettings::text('bare', 'hero_title'))->toBe('Pick one');
});
