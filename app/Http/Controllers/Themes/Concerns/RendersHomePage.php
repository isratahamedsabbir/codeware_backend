<?php

namespace App\Http\Controllers\Themes\Concerns;

use App\Models\Advertisement;
use App\Models\CmsSection;
use App\Support\Features;
use App\Support\Frontend;
use App\Support\ThemeSettings;
use App\Support\Themes;

/**
 * The homepage: the public site root, populated with the "home" page's CMS
 * sections.
 *
 * Split out of the old application-wide FrontendController and shared by every
 * theme that ships a home template, so the *data* a homepage needs is stated
 * once. What a theme's homepage looks like was always the template's own job
 * (themes/{slug}/index.blade.php) and still is — this
 * only decides what it is handed.
 */
trait RendersHomePage
{
    public function home()
    {
        $homePage = Frontend::homePage();

        $sections = $homePage
            ? CmsSection::cachedForPage($homePage->id)
            : collect();

        // A theme that keeps its settings in groups (theme.json `pages` as
        // {key, name}) has a "home" group whose SEO pick — Page Settings → Home →
        // SEO — is the page the template hands its layout to speak for "/".
        // Routes are keyed by method + URI, so the last theme's controller
        // answers "/" whichever theme is live: this has to read the *active*
        // theme, not the class's own.
        $theme = Themes::active();
        $seoPage = Themes::manifest($theme)['groups'] !== null ? ThemeSettings::groupSeoPage($theme, 'home') : null;

        return $this->view('index', [
            'seoPage' => $seoPage,
            'page' => $homePage,
            'sections' => $sections,
            // The page name only. The site root's <title> is the Global SEO title
            // (or the home page's own seo_title), which SeoResolver picks and
            // deliberately does not run through the "%s | Site Name" template.
            'title' => $homePage?->title,
            'currentSlug' => 'home',
            'advertisement' => Features::enabled('advertisements') ? Advertisement::displayAd() : null,
        ]);
    }
}
