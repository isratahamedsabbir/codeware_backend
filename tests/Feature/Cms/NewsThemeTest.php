<?php

use App\Models\Page;
use App\Models\Post;
use App\Models\Setting;
use App\Support\Themes;

/*
 * The "news" theme: every story is a post. The front page, the /blog feed and a
 * single article render through the theme's own templates, and a shop URL is
 * the theme's own 404 because it ships no template for it.
 */

beforeEach(function () {
    Setting::set('site_theme', 'news');
    Themes::forget();
});

function newsPost(string $title, string $slug): Post
{
    $post = Post::factory()->published()->create([
        'title' => ['en' => $title, 'bn' => ''],
        'description' => ['en' => '<p>Body of '.$title.'</p>', 'bn' => ''],
        'published_at' => now(),
    ]);

    Page::factory()->published()->create([
        'title' => ['en' => $title, 'bn' => ''],
        'slug' => $slug,
        'type' => 'post',
        'post_id' => $post->id,
    ]);

    return $post;
}

it('is installed with templates for the pages it serves', function () {
    expect(Themes::isInstalled('news'))->toBeTrue()
        ->and(Themes::hasTemplateFor('news', 'index'))->toBeTrue()
        ->and(Themes::hasTemplateFor('news', 'blog'))->toBeTrue()
        ->and(Themes::hasTemplateFor('news', 'post'))->toBeTrue()
        ->and(Themes::hasTemplateFor('news', 'shop'))->toBeFalse();
});

it('is deletable, since only the default theme is protected', function () {
    Setting::set('site_theme', 'default');

    expect(Themes::undeletableBecause('news'))->toBeNull()
        ->and(Themes::undeletableBecause('default'))->not->toBeNull();
});

it('shows the posts on the front page', function () {
    newsPost('Lead story headline', 'lead-story-headline');
    newsPost('Second story headline', 'second-story-headline');

    $this->get('/')
        ->assertOk()
        ->assertSee('Lead story headline')
        ->assertSee('Second story headline');
});

it('lists posts on /blog and opens a single article', function () {
    newsPost('An article worth reading', 'an-article-worth-reading');

    $this->get('/blog')->assertOk()->assertSee('An article worth reading');
    $this->get('/blog/an-article-worth-reading')->assertOk()->assertSee('Body of An article worth reading', false);
});

it('renders its own 404 for a page it has no template for', function () {
    $this->get('/shop')->assertNotFound();
});
