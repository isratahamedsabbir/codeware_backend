<?php

use App\Livewire\Admin\ThemeSettings\Edit as ThemeSettingsEdit;
use App\Models\Page;
use App\Models\Setting;
use App\Models\User;
use App\Support\ThemeSettings;
use App\Support\Themes;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

/*
 * Modern Ecommerce's home banners — the hero slider and the promo tiles — are edited
 * under Theme Settings → Page Settings → Home and stored in the theme's theme.json.
 */

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->admin()->create());
    Setting::set('site_theme', 'modern_ecommerce');
    Themes::forget();

    $this->home = Page::factory()->published()->create(['title' => ['en' => 'Home', 'bn' => ''], 'slug' => 'home', 'type' => 'page']);
});

it('offers the hero slider and promo tiles under Page Settings → Home', function () {
    Livewire::test(ThemeSettingsEdit::class, ['slug' => 'modern_ecommerce'])
        ->assertSee('Hero slider')
        ->assertSee('Promo tiles')
        ->assertSee('theme_modern_ecommerce_hero_slides', false)
        ->assertSee('theme_modern_ecommerce_promos', false);
});

it('saves the hero slides and promo tiles into the theme\'s theme.json', function () {
    Livewire::test(ThemeSettingsEdit::class, ['slug' => 'modern_ecommerce'])
        ->set('repeaters.theme_modern_ecommerce_hero_slides', [
            ['title' => 'WINTER EDIT', 'subtitle' => 'COATS', 'badge' => 'NEW', 'button' => 'BROWSE', 'link' => '/shop?sort=newest', 'image' => '/storage/hero-1.jpg'],
            ['title' => '', 'subtitle' => '', 'badge' => '', 'button' => '', 'link' => '', 'image' => ''], // a blank row is dropped
        ])
        ->set('repeaters.theme_modern_ecommerce_promos', [
            ['alt' => 'Free delivery', 'link' => '/shop', 'image' => '/storage/promo-1.jpg'],
        ])
        ->call('save');

    $slides = ThemeSettings::rows('modern_ecommerce', 'theme_modern_ecommerce_hero_slides', 'title');
    $promos = ThemeSettings::rows('modern_ecommerce', 'theme_modern_ecommerce_promos', 'alt');

    expect($slides)->toHaveCount(1)
        ->and($slides[0]['title'])->toBe('WINTER EDIT')
        ->and($slides[0]['link'])->toBe('/shop?sort=newest')
        ->and($promos)->toHaveCount(1)
        ->and($promos[0]['alt'])->toBe('Free delivery');
});

it('renders the saved slides and tiles on the home page', function () {
    ThemeSettings::merge('modern_ecommerce', [
        'theme_modern_ecommerce_hero_slides' => [['title' => 'WINTER EDIT', 'subtitle' => 'COATS', 'badge' => 'NEW DROP', 'button' => 'BROWSE', 'link' => '/shop?sort=newest', 'image' => '/storage/hero-1.jpg']],
        'theme_modern_ecommerce_promos' => [['alt' => 'Free delivery over 50', 'link' => '/shop', 'image' => '/storage/promo-1.jpg']],
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('WINTER EDIT')
        ->assertSee('NEW DROP')
        ->assertSee('BROWSE')
        ->assertSee('/storage/hero-1.jpg', false)
        ->assertSee('Free delivery over 50')
        ->assertSee('/storage/promo-1.jpg', false)
        // The built-in sample content is gone once real slides exist.
        ->assertDontSee('LEATHER BACKPACK');
});

it('keeps the sample slides until a slide is added, and falls back to the old promo banner settings', function () {
    Setting::set('home_promo_banner_1', '/storage/legacy-promo.jpg');

    $this->get('/')
        ->assertOk()
        ->assertSee('LEATHER BACKPACK')
        ->assertSee('/storage/legacy-promo.jpg', false);
});
