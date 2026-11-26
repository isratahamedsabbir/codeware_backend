<?php

namespace App\Support;

use App\Models\Feature;

/**
 * This admin panel is reused as a starting point across multiple projects, and not
 * every project needs every module (Chat, Blog, File Manager, ...). Each feature
 * below can be turned off per-deployment from Settings → Features, which hides it
 * from the sidebar and blocks its routes — without touching code. Anything not
 * listed here (Dashboard, Settings itself) is core and always on.
 */
class Features
{
    public const ALL = [
        'env' => 'Developer Tools (App, Maintenance & API Settings)',
        'blog' => 'Blog (Posts)',
        'types' => 'Types (the product/post split behind Categories, Tags & Brands)',
        'groups' => 'Groups (named groups under a Type, used by the Media Library gallery)',
        'categories' => 'Categories (shared by Blog and Products)',
        'tags' => 'Tags (shared by Blog and Products)',
        'access-control' => 'Access Control (Roles, Permissions, Users)',
        'audit-log' => 'Audit Log',
        'products' => 'Products',
        'brands' => 'Brands',
        'services' => 'Services',
        'advertisements' => 'Advertisements',
        'orders' => 'Orders & Reports',
        'discounts' => 'Discounts (Products)',
        'flash-deals' => 'Flash Deals (time-limited sales, storefront page & API)',
        'vouchers' => 'Gift Vouchers',
        'pages' => 'Pages',
        'cms' => 'CMS',
        'media-library' => 'Media Library',
        'file-manager' => 'File Manager',
        'chat' => 'Chat',
        'contacts' => 'Contacts',
        'comments' => 'Comments (Posts, Products & Services)',
        'reviews' => 'Reviews (Posts, Products & Services)',
        'careers' => 'Careers (job posts, applications & storefront page)',
        'newsletter' => 'Newsletter (Subscribers)',
        'menu' => 'Menu Manager',
        'email-templates' => 'Email Templates',
        'localization' => 'Localization (Languages & Translations)',
        'location' => 'Location (Countries, Divisions, Districts, Upazilas)',
        'advance' => 'Advance (Sitemap & Robots.txt)',
        'plugins' => 'Plugins (install & manage plugin modules)',
        'crud' => 'CRUD (builder & generated record screens)',
        'about' => 'About (company info page)',
        'theme-settings' => 'Theme Settings',
        'developer-guide' => 'Developer Guide (in-panel documentation & its help links)',
    ];

    /**
     * Features that are on in a fresh install; every other feature starts off and
     * is switched on from Settings → Features. Core screens that are not in ALL
     * (Dashboard, Settings, Theme Settings, SEO, Social Links, Mobile Apps) are
     * always on. Only FeatureSeeder reads this — it seeds the initial rows and
     * never overwrites a row an admin already has.
     */
    public const DEFAULT_ENABLED = [
        'blog',
        'pages',
        'media-library',
        'contacts',
        'access-control',
        'plugins',
        'localization',
        'theme-settings',
        'developer-guide',
    ];

    /**
     * Settings that only configure something the panel still has: a toggle for a
     * module that has been turned off is a dead control, so it drops off the
     * Settings screen along with the header widget it enables. Keyed by setting
     * key, valued by the feature it rides on — the same rule
     * MenuItem::isVisibleToCurrentUser() applies to the sidebar, and resolved
     * through the one helper below so the toggle and its widget can't drift.
     */
    public const SETTING_FEATURES = [
        'shop_toggle_enabled' => 'products',
        'additional_data_products_enabled' => 'products',
        'additional_data_posts_enabled' => 'blog',
        'language_switcher_enabled' => 'localization',
    ];

    /**
     * child feature => the feature it hangs off. A child is only as available as
     * its parent: turning the parent off takes every descendant with it (sidebar
     * links, routes, storefront pages, API), whatever the child's own toggle says.
     * The child's own row is never rewritten, so switching the parent back on
     * restores the child exactly as it was left.
     */
    public const PARENTS = [
        'categories' => 'types',
        'tags' => 'types',
        'groups' => 'types',
        'brands' => 'types',
        'discounts' => 'products',
        'flash-deals' => 'products',
        'cms' => 'pages',
    ];

    public static function enabled(string $key): bool
    {
        if (! array_key_exists($key, self::ALL)) {
            return true;
        }

        if (! (bool) Feature::enabledMapCached()->get($key, true)) {
            return false;
        }

        $parent = self::PARENTS[$key] ?? null;

        return $parent === null || self::enabled($parent);
    }

    /**
     * Whether a setting is still worth showing — false only when the feature it
     * belongs to is off. An unmapped key is always available, mirroring the
     * fail-open behaviour of enabled() above.
     */
    public static function settingAvailable(string $settingKey): bool
    {
        $feature = self::SETTING_FEATURES[$settingKey] ?? null;

        return $feature === null || self::enabled($feature);
    }

    public static function settingKey(string $key): string
    {
        return "feature_{$key}";
    }
}
