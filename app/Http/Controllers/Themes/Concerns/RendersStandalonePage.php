<?php

namespace App\Http\Controllers\Themes\Concerns;

use App\Models\CmsSection;
use App\Models\Page;

/**
 * A standalone page (About, Contact, FAQ, ...) — same rendering as the
 * homepage, just scoped to the requested page's own CMS sections.
 *
 * Each theme supplies its own page.blade.php, and a theme that ships none
 * simply has no standalone pages: /about 404s rather than being rendered in
 * some other theme's design (see Themes::view()).
 */
trait RendersStandalonePage
{
    public function page(string $slug)
    {
        $page = Page::where('slug', $slug)->where('type', 'page')->where('status', 'active')->firstOrFail();

        // Pages → Edit → Builder: a design picked there is the page.
        if ($page->builder_id && ($design = \App\Models\BuilderDesign::find($page->builder_id))) {
            return view('builders.show', ['design' => $design]);
        }

        return $this->view('page', [
            'page' => $page,
            'sections' => CmsSection::cachedForPage($page->id),
            'title' => $page->seo_title ?: $page->getTranslation('title', 'en', false),
            'currentSlug' => $slug,
        ]);
    }
}
