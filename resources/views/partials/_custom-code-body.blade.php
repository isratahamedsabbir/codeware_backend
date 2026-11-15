{{-- Settings → Custom Code → Body Code: pasted verbatim just before </body>
     on every public theme page (never the admin/auth screens), followed by the
     snippet picked in the page's SEO section. --}}
@if (filled($gtmId = config('services.google_tag_manager.id')))
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtmId }}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
@endif
@if (filled($customBodyCode = \App\Support\CustomCode::render('body', $page ?? null)))
{!! $customBodyCode !!}
@endif
