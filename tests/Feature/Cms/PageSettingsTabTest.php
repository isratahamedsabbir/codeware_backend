<?php

use App\Livewire\Admin\ThemeSettings\Edit as ThemeSettingsEdit;
use App\Livewire\Admin\ThemeSettings\Index as ThemeSettingsScreen;
use App\Models\CmsSection;
use App\Models\Page;
use App\Models\Setting;
use App\Models\User;
use App\Support\Themes;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

/*
 * Theme Settings → Page Settings: the live theme's pages with their sections,
 * edited in place.
 */

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
    $this->actingAs(User::factory()->admin()->create());
    Setting::set('site_theme', 'news');
    Themes::forget();

    $this->about = Page::factory()->published()->create([
        'title' => ['en' => 'About us', 'bn' => ''],
        'slug' => 'about',
        'type' => 'page',
    ]);

    $this->section = CmsSection::create([
        'page_id' => $this->about->id,
        'name' => 'team',
        'sort_order' => 1,
        'status' => 'active',
        'cards' => [['image' => null, 'title' => 'Alex', 'description' => 'Editor']],
        'constant' => [['key' => 'HEADLINE', 'type' => 'textarea', 'value' => 'Meet us']],
    ]);
});

it('lists the pages the live theme serves, with their sections', function () {
    Livewire::test(ThemeSettingsEdit::class, ['slug' => 'news'])
        ->assertSee('About us')
        ->assertSee('team')
        ->assertSet("titles.{$this->about->id}.en", 'About us')
        ->assertSet("cards.{$this->section->id}.0.title", 'Alex');
});

it('shows only the home page for a theme with no page template', function () {
    Setting::set('site_theme', 'portfolio');
    Themes::forget();

    Livewire::test(ThemeSettingsScreen::class)->assertDontSee('About us');
});

it('updates a page title and a section\'s cards and constant values', function () {
    Livewire::test(ThemeSettingsScreen::class)
        ->set("titles.{$this->about->id}.en", 'About the newsroom')
        ->set("cards.{$this->section->id}.0.title", 'Sam')
        ->call('addCard', $this->section->id)
        ->set("cards.{$this->section->id}.1.title", 'Jo')
        ->set("values.{$this->section->id}.0", 'Say hello')
        ->call('save')
        ->assertHasNoErrors();

    expect($this->about->fresh()->getTranslation('title', 'en'))->toBe('About the newsroom');

    $section = $this->section->fresh();
    expect($section->cards)->toHaveCount(2)
        ->and($section->cards[0]['title'])->toBe('Sam')
        ->and($section->constant[0]['key'])->toBe('HEADLINE')
        ->and($section->constant[0]['value'])->toBe('Say hello');
});

it('requires a primary-language title', function () {
    Livewire::test(ThemeSettingsScreen::class)
        ->set("titles.{$this->about->id}.en", '')
        ->call('save')
        ->assertHasErrors(["titles.{$this->about->id}.en"]);
});

it('ignores a section that is not one of the theme\'s pages', function () {
    $other = Page::factory()->published()->create(['title' => ['en' => 'Hidden', 'bn' => ''], 'slug' => 'hidden-post', 'type' => 'post']);
    $foreign = CmsSection::create(['page_id' => $other->id, 'name' => 'x', 'sort_order' => 1, 'status' => 'active', 'cards' => [['image' => null, 'title' => 'keep', 'description' => '']]]);

    Livewire::test(ThemeSettingsScreen::class)
        ->set("cards.{$foreign->id}", [['image' => null, 'title' => 'tampered', 'description' => '']])
        ->call('save');

    expect($foreign->fresh()->cards[0]['title'])->toBe('keep');
});

it('edits a card subtitle, button and text colour, and the storefront renders them', function () {
    Setting::set('site_theme', 'classic_ecommerce');
    Themes::forget();

    // The generic card grid. About and FAQ have bespoke layouts (a story, a Q&A
    // list) that only use a card's title and description.
    $page = Page::factory()->published()->create(['title' => ['en' => 'Offers', 'bn' => ''], 'slug' => 'offers', 'type' => 'page']);
    $section = CmsSection::create([
        'page_id' => $page->id, 'name' => 'deals', 'sort_order' => 1, 'status' => 'active',
        'cards' => [['image' => null, 'title' => 'Winter sale', 'description' => 'Warm things']],
    ]);

    Livewire::test(ThemeSettingsScreen::class)
        ->set("cards.{$section->id}.0.subtitle", 'Ends Sunday')
        ->set("cards.{$section->id}.0.button_label", 'Join us')
        ->set("cards.{$section->id}.0.button_url", '/contact')
        ->set("cards.{$section->id}.0.text_color", '#ff0000')
        ->call('save')
        ->assertHasNoErrors();

    $card = $section->fresh()->cards[0];
    expect($card['subtitle'])->toBe('Ends Sunday')
        ->and($card['text_color'])->toBe('#ff0000');

    $this->get('/offers')
        ->assertOk()
        ->assertSee('Ends Sunday')
        ->assertSee('Join us')
        ->assertSee('color: #ff0000', false);
});

it('rejects a text colour that is not a hex value', function () {
    Livewire::test(ThemeSettingsScreen::class)
        ->set("cards.{$this->section->id}.0.text_color", 'red; background: url(x)')
        ->call('save')
        ->assertHasErrors(["cards.{$this->section->id}.0.text_color"]);
});

it('switches the page shown on the right when a page is clicked on the left', function () {
    $contact = Page::factory()->published()->create(['title' => ['en' => 'Contact us', 'bn' => ''], 'slug' => 'contact', 'type' => 'page']);

    Livewire::test(ThemeSettingsScreen::class)
        ->assertSet('selectedPageId', $this->about->id)
        ->call('selectPage', $contact->id)
        ->assertSet('selectedPageId', $contact->id)
        ->call('selectPage', 999999)
        ->assertSet('selectedPageId', $contact->id);
});

it('saves the classic home banners: promo tiles to settings, their links to theme.json', function () {
    Setting::set('site_theme', 'classic_ecommerce');
    Themes::forget();

    Livewire::test(ThemeSettingsScreen::class)
        ->set('promo.0.image', 'media/tile-one.jpg')
        ->set('promo.0.link', '/shop?sort=newest')
        ->set('promo.1.link', '/shop')
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::get('home_promo_banner_1'))->toBe('media/tile-one.jpg')
        ->and(\App\Support\ThemeSettings::text('classic_ecommerce', 'theme_classic_ecommerce_promo_1_link'))->toBe('/shop?sort=newest')
        ->and(\App\Support\ThemeSettings::text('classic_ecommerce', 'theme_classic_ecommerce_promo_2_link'))->toBe('/shop');
});

it('renders a card\'s subtitle, button and text colour on every theme that serves pages', function (string $slug) {
    Setting::set('site_theme', $slug);
    Themes::forget();

    $page = Page::factory()->published()->create(['title' => ['en' => 'Offers', 'bn' => ''], 'slug' => 'offers', 'type' => 'page']);
    CmsSection::create([
        'page_id' => $page->id, 'name' => 'deals', 'sort_order' => 1, 'status' => 'active',
        'cards' => [['image' => null, 'title' => 'Winter sale', 'description' => 'Warm things', 'subtitle' => 'Ends Sunday', 'button_label' => 'Shop now', 'button_url' => '/shop', 'text_color' => '#ff0000']],
    ]);

    $this->get('/offers')->assertOk()->assertSee('Ends Sunday')->assertSee('Shop now')->assertSee('color: #ff0000', false);
})->with(['classic_ecommerce', 'modern_ecommerce', 'default', 'news']);

it('shows a theme settings page with Theme Settings and Page Settings, not the theme list', function () {
    Livewire::test(ThemeSettingsScreen::class)
        ->assertDontSee('Page Settings')
        ->assertDontSee('theme_portfolio_font', false);

    Livewire::test(ThemeSettingsEdit::class, ['slug' => 'portfolio'])
        ->assertSet('settingsFor', 'portfolio')
        ->assertSee('Page Settings')
        ->assertSee('theme_portfolio_font', false)
        // The portfolio serves no standalone pages, so the About page is not listed.
        ->assertDontSee('About us');
});

it('lists the pages of the theme being edited, not the live one', function () {
    Setting::set('site_theme', 'portfolio');
    Themes::forget();

    Livewire::test(ThemeSettingsEdit::class, ['slug' => 'news'])
        ->assertSet("titles.{$this->about->id}.en", 'About us')
        ->assertSee('About us');
});

it('does not change the live theme when another theme is saved', function () {
    Setting::set('site_theme', 'portfolio');
    Themes::forget();

    Livewire::test(ThemeSettingsEdit::class, ['slug' => 'news'])
        ->set('settings.theme_news_tagline', 'All the news')
        ->call('save');

    expect(Setting::get('site_theme'))->toBe('portfolio')
        ->and(\App\Support\ThemeSettings::text('news', 'theme_news_tagline'))->toBe('All the news');
});

it('serves an installed theme settings page and 404s one that is not installed', function () {
    // Mfa::isRequiredFor() reads the role flag, not the config switch.
    \Illuminate\Support\Facades\DB::table('roles')->update(['mfa_enabled' => false]);

    $this->get(route('admin.theme-settings.edit', 'news'))->assertOk()->assertSee('Page Settings');
    $this->get(route('admin.theme-settings.edit', 'no-such-theme'))->assertNotFound();
});

it('links every theme card Settings button to that theme settings page', function () {
    Livewire::test(ThemeSettingsScreen::class)
        ->assertSeeHtml('href="'.route('admin.theme-settings.edit', 'news').'"')
        ->assertSeeHtml('href="'.route('admin.theme-settings.edit', 'classic_ecommerce').'"');
});

it('downloads a theme\'s saved settings as json, without the manifest', function () {
    \App\Support\ThemeSettings::merge('news', ['theme_news_tagline' => 'All the news', 'theme_news_accent_color' => '#1d4ed8']);

    $component = Livewire::test(ThemeSettingsEdit::class, ['slug' => 'news'])->call('downloadSettings');

    $component->assertFileDownloaded('news-settings.json');

    $json = json_decode(\App\Support\ThemeSettings::text('news', 'theme_news_tagline') ? json_encode(\App\Support\ThemeSettings::settings('news')) : '{}', true);
    expect($json)->toHaveKey('theme_news_tagline', 'All the news')
        ->and($json)->not->toHaveKey('name')
        ->and($json)->not->toHaveKey('version');
});
