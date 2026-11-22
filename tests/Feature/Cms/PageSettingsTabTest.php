<?php

use App\Livewire\Admin\ThemeSettings\Edit as ThemeSettingsEdit;
use App\Livewire\Admin\ThemeSettings\Index as ThemeSettingsScreen;
use App\Models\CmsSection;
use App\Models\Page;
use App\Models\Seo;
use App\Models\Setting;
use App\Models\User;
use App\Support\Frontend;
use App\Support\Themes;
use App\Support\ThemeSettings;
use Database\Seeders\PageSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\DB;
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
        ->assertSet("cards.{$this->section->id}.0.title", 'Alex');
});

it('shows only the home page for a theme with no page template', function () {
    Setting::set('site_theme', 'portfolio');
    Themes::forget();

    Livewire::test(ThemeSettingsScreen::class)->assertDontSee('About us');
});

it('updates a page title and a section\'s cards and constant values', function () {
    Livewire::test(ThemeSettingsScreen::class)
        ->set("cards.{$this->section->id}.0.title", 'Sam')
        ->call('addCard', $this->section->id)
        ->set("cards.{$this->section->id}.1.title", 'Jo')
        ->set("values.{$this->section->id}.0", 'Say hello')
        ->call('save')
        ->assertHasNoErrors();

    expect($this->about->fresh()->getTranslation('title', 'en'))->toBe('About us');

    $section = $this->section->fresh();
    expect($section->cards)->toHaveCount(2)
        ->and($section->cards[0]['title'])->toBe('Sam')
        ->and($section->constant[0]['key'])->toBe('HEADLINE')
        ->and($section->constant[0]['value'])->toBe('Say hello');
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
    $page = Page::factory()->published()->create(['title' => ['en' => 'Contact us', 'bn' => ''], 'slug' => 'contact', 'type' => 'page']);
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

    $this->get('/contact')
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
        ->and(ThemeSettings::text('classic_ecommerce', 'theme_classic_ecommerce_promo_1_link'))->toBe('/shop?sort=newest')
        ->and(ThemeSettings::text('classic_ecommerce', 'theme_classic_ecommerce_promo_2_link'))->toBe('/shop');
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
})->with(['classic_ecommerce', 'modern_ecommerce', 'news']);

it('shows a theme settings page with Theme Settings and Page Settings, not the theme list', function () {
    Livewire::test(ThemeSettingsScreen::class)
        ->assertDontSee('Page Settings')
        ->assertDontSee('theme_portfolio_font', false);

    Livewire::test(ThemeSettingsEdit::class, ['slug' => 'portfolio'])
        ->assertSet('settingsFor', 'portfolio')
        ->assertSee('Page Settings')
        ->assertSee('theme_portfolio_font', false)
        // The portfolio's pages are one settings group, Home — not rows of the pages table.
        ->assertViewHas('psGroups', fn ($groups) => $groups->pluck('key')->all() === ['home']);
});

it('lists the pages of the theme being edited, not the live one', function () {
    Setting::set('site_theme', 'portfolio');
    Themes::forget();

    Livewire::test(ThemeSettingsEdit::class, ['slug' => 'news'])
        ->assertSee('About us');
});

it('does not change the live theme when another theme is saved', function () {
    Setting::set('site_theme', 'portfolio');
    Themes::forget();

    Livewire::test(ThemeSettingsEdit::class, ['slug' => 'news'])
        ->set('settings.theme_news_tagline', 'All the news')
        ->call('save');

    expect(Setting::get('site_theme'))->toBe('portfolio')
        ->and(ThemeSettings::text('news', 'theme_news_tagline'))->toBe('All the news');
});

it('serves an installed theme settings page and 404s one that is not installed', function () {
    // Mfa::isRequiredFor() reads the role flag, not the config switch.
    DB::table('roles')->update(['mfa_enabled' => false]);

    $this->get(route('admin.theme-settings.edit', 'news'))->assertOk()->assertSee('Page Settings');
    $this->get(route('admin.theme-settings.edit', 'no-such-theme'))->assertNotFound();
});

it('links every theme card Settings button to that theme settings page', function () {
    Livewire::test(ThemeSettingsScreen::class)
        ->assertSeeHtml('href="'.route('admin.theme-settings.edit', 'news').'"')
        ->assertSeeHtml('href="'.route('admin.theme-settings.edit', 'classic_ecommerce').'"');
});

it('downloads a theme\'s saved settings as json, without the manifest', function () {
    ThemeSettings::merge('news', ['theme_news_tagline' => 'All the news', 'theme_news_accent_color' => '#1d4ed8']);

    $component = Livewire::test(ThemeSettingsEdit::class, ['slug' => 'news'])->call('downloadSettings');

    $component->assertFileDownloaded('news-settings.json');

    $json = json_decode(ThemeSettings::text('news', 'theme_news_tagline') ? json_encode(ThemeSettings::settings('news')) : '{}', true);
    expect($json)->toHaveKey('theme_news_tagline', 'All the news')
        ->and($json)->not->toHaveKey('name')
        ->and($json)->not->toHaveKey('version');
});

it('lists every page as an SEO option and saves the one chosen for a page', function () {
    // A page the news theme has (its theme.json lists home, about, contact, faq).
    $source = Page::factory()->published()->create(['title' => ['en' => 'Shared SEO page', 'bn' => ''], 'slug' => 'contact', 'type' => 'page', 'seo_title' => ['en' => 'Shared meta']]);

    Livewire::test(ThemeSettingsEdit::class, ['slug' => 'news'])
        ->assertSet("seoSources.{$this->about->id}", '')
        ->assertSee('Shared SEO page')
        ->set("seoSources.{$this->about->id}", $source->id)
        ->call('save')
        ->assertHasNoErrors();

    $about = $this->about->fresh();

    expect($about->seo_page_id)->toBe($source->id)
        ->and($about->seo_title)->toBe('Shared meta');

    // Empty goes back to the page's own SEO.
    Livewire::test(ThemeSettingsEdit::class, ['slug' => 'news'])
        ->assertSet("seoSources.{$this->about->id}", $source->id)
        ->set("seoSources.{$this->about->id}", '')
        ->call('save');

    expect($this->about->fresh()->seo_page_id)->toBeNull()
        ->and($this->about->fresh()->seo_title)->toBeNull();
});

it('selects the Default page as the SEO source of a page that never picked one', function () {
    $default = Page::factory()->create(['title' => ['en' => 'Default', 'bn' => ''], 'slug' => 'default', 'type' => 'page', 'status' => 'inactive']);

    Livewire::test(ThemeSettingsEdit::class, ['slug' => 'news'])
        ->assertSet("seoSources.{$this->about->id}", $default->id);
});

it('keeps a page that picked its own SEO on its own SEO, and shows it after the save', function () {
    $default = Page::factory()->create(['title' => ['en' => 'Default', 'bn' => ''], 'slug' => 'default', 'type' => 'page', 'status' => 'inactive']);

    Livewire::test(ThemeSettingsEdit::class, ['slug' => 'news'])
        ->assertSet("seoSources.{$this->about->id}", $default->id)
        ->set("seoSources.{$this->about->id}", $this->about->id)
        ->call('save')
        ->assertHasNoErrors();

    // Stored as itself, which the page reads as "my own row" …
    expect($this->about->fresh()->seo_page_id)->toBe($this->about->id)
        ->and($this->about->fresh()->seoSourcePage()->is($this->about))->toBeTrue();

    // … and the form shows that choice, not Default, when it is opened again.
    Livewire::test(ThemeSettingsEdit::class, ['slug' => 'news'])
        ->assertSet("seoSources.{$this->about->id}", $this->about->id);
});

it('lists only pages as SEO options, not posts, products or categories', function () {
    Page::factory()->published()->create(['title' => ['en' => 'A blog post page', 'bn' => ''], 'slug' => 'a-blog-post', 'type' => 'post']);
    Page::factory()->published()->create(['title' => ['en' => 'A product page', 'bn' => ''], 'slug' => 'a-product', 'type' => 'product']);

    Livewire::test(ThemeSettingsEdit::class, ['slug' => 'news'])
        ->assertSee('About us')
        ->assertDontSee('A blog post page')
        ->assertDontSee('A product page');
});

it('rejects an SEO source that is not a page', function () {
    Livewire::test(ThemeSettingsEdit::class, ['slug' => 'news'])
        ->set("seoSources.{$this->about->id}", 999999)
        ->call('save')
        ->assertHasErrors(["seoSources.{$this->about->id}"]);
});

it('keeps every block of a page — SEO, banners and each section — collapsed until opened', function () {
    Setting::set('site_theme', 'classic_ecommerce');
    Themes::forget();

    $html = Livewire::test(ThemeSettingsEdit::class, ['slug' => 'classic_ecommerce'])
        ->call('selectPage', Page::where('slug', 'home')->value('id') ?? $this->about->id)
        ->html();

    // SEO (+ banners on the home page) + the section: all closed.
    expect(substr_count($html, 'x-data="{ open: false }"'))->toBeGreaterThanOrEqual(2)
        ->and($html)->not->toContain('x-data="{ open: true }"');
});

it('opens the block that holds a validation error so the message is not hidden', function () {
    Livewire::test(ThemeSettingsEdit::class, ['slug' => 'news'])
        ->set("seoSources.{$this->about->id}", 999999)
        ->call('save')
        ->assertHasErrors(["seoSources.{$this->about->id}"])
        ->assertSeeHtml('x-data="{ open: true }"');
});

it('applies a card\'s text colour in the FAQ and About layouts of the storefront themes', function (string $theme) {
    Setting::set('site_theme', $theme);
    Themes::forget();

    $faq = Page::factory()->published()->create(['title' => ['en' => 'FAQ', 'bn' => ''], 'slug' => 'faq', 'type' => 'page']);
    CmsSection::create(['page_id' => $faq->id, 'name' => 'questions', 'sort_order' => 1, 'status' => 'active',
        'cards' => [['image' => null, 'title' => 'How do returns work?', 'description' => '30 days.', 'text_color' => '#123456']]]);

    $metrics = Page::where('slug', 'about')->first();
    CmsSection::create(['page_id' => $metrics->id, 'name' => 'impact', 'sort_order' => 2, 'status' => 'active',
        'cards' => [['image' => null, 'title' => '12,000+', 'description' => 'Customers', 'text_color' => '#654321']]]);

    $this->get('/faq')->assertOk()->assertSee('color: #123456', false);
    $this->get('/about')->assertOk()->assertSee('color: #654321', false);
})->with(['classic_ecommerce', 'modern_ecommerce']);

// ---------------------------------------------------------------- Which pages a theme has

it('lists only the pages a theme names in its theme.json', function (string $theme, array $slugs) {
    foreach (['home', 'contact', 'faq', 'default'] as $slug) {
        Page::factory()->published()->create(['title' => ['en' => ucfirst($slug).' page', 'bn' => ''], 'slug' => $slug, 'type' => 'page']);
    }

    $component = Livewire::test(ThemeSettingsEdit::class, ['slug' => $theme]);
    $listed = $component->viewData('psPages')->pluck('slug')->sort()->values()->all();

    expect($listed)->toBe(collect($slugs)->sort()->values()->all());
})->with([
    'news' => ['news', ['home', 'about', 'contact', 'faq']],
    'classic' => ['classic_ecommerce', ['home', 'about', 'contact', 'faq']],
]);

it('lists the pages in the order the theme names them', function () {
    Page::factory()->published()->create(['title' => ['en' => 'Faq page', 'bn' => ''], 'slug' => 'faq', 'type' => 'page', 'sort_order' => 0]);
    Page::factory()->published()->create(['title' => ['en' => 'Home page', 'bn' => ''], 'slug' => 'home', 'type' => 'page', 'sort_order' => 9]);

    $ids = Livewire::test(ThemeSettingsEdit::class, ['slug' => 'news'])->viewData('psPages')->pluck('slug')->all();

    // theme.json says home, about, contact, faq — not the sort_order of the rows.
    expect(array_search('home', $ids))->toBeLessThan(array_search('faq', $ids));
});

it('offers a theme\'s own pages and the Default page as SEO options, never another theme\'s', function () {
    Page::factory()->published()->create(['title' => ['en' => 'Home', 'bn' => ''], 'slug' => 'home', 'type' => 'page']);
    Page::factory()->create(['title' => ['en' => 'Default', 'bn' => ''], 'slug' => 'default', 'type' => 'page', 'status' => 'inactive']);

    // A theme that keeps plain page slugs offers its own pages plus Default.
    Livewire::test(ThemeSettingsEdit::class, ['slug' => 'news'])
        ->assertViewHas('psSeoOptions', fn ($options) => $options->contains('About us') && $options->contains('Default') && $options->contains('Home'));

    // A theme with settings groups offers every page, Default first.
    Livewire::test(ThemeSettingsEdit::class, ['slug' => 'portfolio'])
        ->assertViewHas('psSeoOptions', fn ($options) => $options->contains('Default') && $options->contains('Home') && $options->first() === 'Default');
});

/**
 * Runs $test with the default theme's theme.json stripped of any saved group SEO
 * pick (a real site keeps page ids there that the test database does not have),
 * and puts the file back afterwards.
 */
function withCleanDefaultThemeFile(Closure $test): void
{
    $file = base_path('themes/default/theme.json');
    $original = file_get_contents($file);
    $data = json_decode($original, true);
    unset($data['pages']['home']['seo']);
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    ThemeSettings::forget('default');

    try {
        $test();
    } finally {
        file_put_contents($file, $original);
        ThemeSettings::forget('default');
    }
}

it('lists the default theme\'s settings groups by name, without touching the pages table', function () {
    withCleanDefaultThemeFile(function () {
        $before = Page::count();

        Livewire::test(ThemeSettingsEdit::class, ['slug' => 'default'])
            ->assertViewHas('psGroups', fn ($groups) => $groups->pluck('key')->all() === ['home'])
            ->assertSee('Intro Heading')
            ->call('save')
            ->assertHasNoErrors();

        expect(Page::count())->toBe($before);
    });
});

it('lists the site pages beside a group theme groups, without SEO or Builder pickers', function () {
    Page::factory()->published()->create(['title' => ['en' => 'Our Story', 'bn' => ''], 'slug' => 'our-story', 'type' => 'page']);
    Page::factory()->create(['title' => ['en' => 'Default', 'bn' => ''], 'slug' => 'default', 'type' => 'page', 'status' => 'inactive']);

    Livewire::test(ThemeSettingsEdit::class, ['slug' => 'default'])
        ->assertSee('Intro Heading')
        ->assertDontSee('Which builder should this page show?')
        ->assertDontSee('Which SEO should this page use?')
        ->assertViewHas('psPages', fn ($pages) => $pages->pluck('slug')->all() === ['our-story'])
        ->call('selectPage', Page::where('type', 'page')->get()->firstWhere('slug', 'our-story')->id)
        ->assertSee('Our Story')
        ->assertDontSee('Which SEO should this page use?');
});

it('seeds the Default page: inactive, with an SEO row, out of the nav', function () {
    $this->seed(PageSeeder::class);

    $page = Page::where('type', 'page')->where('slug', 'default')->first();

    expect($page)->not->toBeNull()
        ->and($page->status)->toBe('inactive')
        ->and(Seo::where('page_id', $page->id)->exists())->toBeTrue()
        ->and(Frontend::navPages()->pluck('slug')->all())->not->toContain('default');
});

it('serves the picked page\'s own SEO even when that page borrows another page\'s', function () {
    $borrowed = Page::factory()->create(['title' => ['en' => 'Default', 'bn' => ''], 'slug' => 'default', 'type' => 'page', 'status' => 'inactive']);
    $source = Page::factory()->published()->create([
        'title' => ['en' => 'About us', 'bn' => ''], 'slug' => 'about-borrower', 'type' => 'page',
        'seo_title' => ['en' => 'About own title'],
    ]);
    $source->update(['seo_page_id' => $borrowed->id]);

    $file = base_path('themes/default/theme.json');
    $original = file_get_contents($file);

    try {
        $data = json_decode($original, true);
        $data['pages']['home']['seo'] = $source->id;
        file_put_contents($file, json_encode($data));
        ThemeSettings::forget('default');

        expect(ThemeSettings::groupSeoPage('default', 'home')->seo_title)->toBe('About own title');
    } finally {
        file_put_contents($file, $original);
        ThemeSettings::forget('default');
    }
});

it('reorders a section\'s items with the up and down buttons', function () {
    $component = Livewire::test(ThemeSettingsScreen::class)
        ->call('addCard', $this->section->id)
        ->set("cards.{$this->section->id}.1.title", 'Second')
        ->call('moveCard', $this->section->id, 1, 'up');

    expect($component->get("cards.{$this->section->id}.0.title"))->toBe('Second')
        ->and($component->get("cards.{$this->section->id}.1.title"))->toBe('Alex');

    // The first item cannot go further up, and the last cannot go further down.
    $component->call('moveCard', $this->section->id, 0, 'up')->call('moveCard', $this->section->id, 1, 'down');

    expect($component->get("cards.{$this->section->id}.0.title"))->toBe('Second');
});

it('does not move every page onto Default just because Save was pressed', function () {
    Page::factory()->create(['title' => ['en' => 'Default', 'bn' => ''], 'slug' => 'default', 'type' => 'page', 'status' => 'inactive']);
    $this->about->update(['seo_title' => ['en' => 'About own title']]);

    // The form shows Default for a page that never picked a source …
    Livewire::test(ThemeSettingsEdit::class, ['slug' => 'news'])->call('save')->assertHasNoErrors();

    // … but saving without touching it must leave the page on its own SEO.
    expect($this->about->fresh()->seo_page_id)->toBeNull()
        ->and($this->about->fresh()->seo_title)->toBe('About own title');
});

it('falls back to Default in the form when a page\'s saved SEO source was deleted', function () {
    $default = Page::factory()->create(['title' => ['en' => 'Default', 'bn' => ''], 'slug' => 'default', 'type' => 'page', 'status' => 'inactive']);
    $gone = Page::factory()->published()->create(['title' => ['en' => 'Gone', 'bn' => ''], 'slug' => 'gone', 'type' => 'page']);
    $this->about->update(['seo_page_id' => $gone->id]);
    $gone->delete();

    Livewire::test(ThemeSettingsEdit::class, ['slug' => 'news'])
        ->assertSet("seoSources.{$this->about->id}", $default->id)
        ->call('save')
        ->assertHasNoErrors();
});

it('shows a success toast right away when a theme\'s own settings page is saved, without reloading', function () {
    Livewire::test(ThemeSettingsEdit::class, ['slug' => 'news'])
        ->call('save')
        ->assertDispatched('notify', message: 'Theme settings saved.', type: 'success');
});

it('shows an error toast, and asks folded cards to open, when a save is refused', function () {
    Livewire::test(ThemeSettingsEdit::class, ['slug' => 'news'])
        ->set("seoSources.{$this->about->id}", 987654)
        ->call('save')
        ->assertHasErrors()
        ->assertDispatched('notify', type: 'error')
        ->assertDispatched('validation-failed');
});

it('keeps the theme list\'s save on the flash message and reload', function () {
    Livewire::test(ThemeSettingsScreen::class)
        ->call('save')
        ->assertNotDispatched('notify');
});
