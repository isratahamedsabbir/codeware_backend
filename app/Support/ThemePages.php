<?php

namespace App\Support;

use App\Models\CmsSection;
use App\Models\Page;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * The pages a theme needs, as rows of the `pages` table.
 *
 * theme.json lists page slugs (`"pages": ["home", "about", "pricing"]`), and a
 * theme's route/controller serves them from the `pages` table — so a slug that
 * is listed but has no row is a 404. sync() closes that gap: it creates the
 * missing rows, and is called when a theme is installed, when it is activated,
 * and by `php artisan themes:sync-pages`.
 *
 * Where a page's content comes from, first match wins:
 *   1. themes/{slug}/data/pages/{page}.json — the theme ships its own (same
 *      shape as the project's data/pages/*.json: title, description, content,
 *      cms[]). Every file there is a page of the theme even if theme.json does
 *      not list it, so the folder alone is enough.
 *   2. data/pages/{page}.json — the shared starter content (home/about/…).
 *   3. a blank, active page titled after the slug.
 *
 * Existing pages are never touched — the owner edits them in the admin, and a
 * re-sync must not overwrite that. Themes whose `pages` is a map of groups
 * (default, portfolio) have no page rows by design; only their data/pages
 * files, if any, are created.
 */
class ThemePages
{
    /**
     * Every page slug the theme needs, in order.
     *
     * @return array<int, string>
     */
    public static function slugs(string $theme): array
    {
        $slugs = Themes::manifest($theme)['pages'] ?? [];

        foreach (glob(Themes::path()."/{$theme}/data/pages/*.json") ?: [] as $file) {
            $slugs[] = basename($file, '.json');
        }

        return array_values(array_unique(array_filter($slugs, fn ($s) => is_string($s) && preg_match('/^[a-z0-9][a-z0-9_-]*$/', $s))));
    }

    /**
     * Create the theme's missing pages.
     *
     * @return array{created: array<int, string>, existing: array<int, string>, blocked: array<int, string>}
     */
    public static function sync(string $theme): array
    {
        $result = ['created' => [], 'existing' => [], 'blocked' => []];

        if (! Themes::isInstalled($theme)) {
            return $result;
        }

        $owner = auth()->id()
            ?? User::whereHas('roles', fn ($q) => $q->where('name', 'admin'))->value('id')
            ?? User::query()->orderBy('id')->value('id');
        $order = (int) Page::where('type', 'page')->max('sort_order');

        // pages.user_id is required; with no user at all there is no one to own a page.
        if ($owner === null) {
            return $result;
        }

        foreach (static::slugs($theme) as $slug) {
            if (Page::where('slug', $slug)->exists()) {
                $result['existing'][] = $slug;

                continue;
            }

            // The slug belongs to something that is not a page (a service, a brand…).
            if (Slug::taken($slug)) {
                $result['blocked'][] = $slug;

                continue;
            }

            $json = static::definition($theme, $slug);

            $page = Page::create([
                'user_id' => $owner,
                'slug' => $slug,
                'title' => ['en' => $json['title'] ?? Str::headline($slug), 'bn' => ''],
                'seo_description' => $json['description'] ?? '',
                'puck_data' => [
                    'root' => $json['root'] ?? ['props' => []],
                    'content' => $json['content'] ?? [],
                ],
                'template' => 'puck',
                'status' => 'active',
                'sort_order' => ++$order,
            ]);

            foreach ($json['cms'] ?? [] as $i => $section) {
                CmsSection::create([
                    'page_id' => $page->id,
                    'name' => $section['name'],
                    'cards' => $section['cards'] ?? [],
                    'constant' => $section['constant'] ?? [],
                    'status' => $section['status'] ?? 'active',
                    'sort_order' => $i,
                ]);
            }

            $result['created'][] = $slug;
        }

        return $result;
    }

    /** @return array<string, mixed> */
    private static function definition(string $theme, string $slug): array
    {
        foreach ([Themes::path()."/{$theme}/data/pages/{$slug}.json", base_path("data/pages/{$slug}.json")] as $file) {
            if (is_file($file) && is_array($json = json_decode((string) file_get_contents($file), true))) {
                return $json;
            }
        }

        return [];
    }
}
