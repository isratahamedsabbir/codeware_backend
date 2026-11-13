<?php
use App\Livewire\Admin\ThemeSettings\Edit;
use App\Livewire\Admin\ThemeSettings\Index as S;
use App\Models\Page;
use App\Models\Setting;
use App\Models\User;
use App\Support\Themes;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

it('renders Theme Settings + Page Settings for every theme', function (string $slug, array $mustSee, array $mustNotSee) {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->admin()->create());
    Page::factory()->published()->create(['title' => ['en' => 'Home', 'bn' => ''], 'slug' => 'home', 'type' => 'page']);
    Page::factory()->published()->create(['title' => ['en' => 'About us', 'bn' => ''], 'slug' => 'about', 'type' => 'page']);
    // The default theme's one page is the seeded Default page (inactive, never in the nav).
    Page::factory()->create(['title' => ['en' => 'Default', 'bn' => ''], 'slug' => 'default', 'type' => 'page', 'status' => 'inactive']);
    Setting::set('site_theme', $slug); Themes::forget();
    // Closed by default: the settings live in the dialog a card's Settings button opens.
    Livewire::test(S::class)->assertOk()->assertDontSee('Page Settings');

    $c = Livewire::test(Edit::class, ['slug' => $slug])->assertOk()->assertSee('Page Settings');
    foreach ($mustSee as $t) $c->assertSee($t, false);
    foreach ($mustNotSee as $t) $c->assertDontSee($t, false);
    $c->call('save')->assertHasNoErrors();
})->with([
    'classic' => ['classic_ecommerce', ['Add slide', 'theme_classic_ecommerce_header_bg_color'], []],
    'default' => ['default', ['settings.theme_default_intro_heading', 'theme_default_font'], []],
    // About us is still an SEO option, but the portfolio serves no standalone pages, so it has no entry in the page list.
    'portfolio' => ['portfolio', ['theme_portfolio_projects', 'theme_portfolio_font'], ['>/about<']],
    'modern' => ['modern_ecommerce', ['theme_modern_ecommerce_accent_color', 'About us'], []],
    'news' => ['news', ['theme_news_tagline', 'About us'], []],
]);
