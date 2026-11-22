{{-- Optional translated heading of a dynamic block (fields "heading" / "subheading"). Inline styles: it must not add a <style> of its own. --}}
@if (($heading ?? '') !== '' || ($subheading ?? '') !== '')
    <div style="max-width:1140px;margin:0 auto;padding:32px 20px 0;text-align:center">
        @if (($heading ?? '') !== '')<h2 style="margin:0 0 8px;font-size:2rem;line-height:1.2;font-weight:700">{{ $heading }}</h2>@endif
        @if (($subheading ?? '') !== '')<p style="margin:0;color:#64748b;line-height:1.6">{{ $subheading }}</p>@endif
    </div>
@endif
