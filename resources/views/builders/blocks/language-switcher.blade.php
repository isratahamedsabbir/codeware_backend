{{-- Output of the builder's "Language switcher" block. The link is a bare ?lang=, so it works on whatever page it sits on (SetLocale stores the pick in the session). --}}
@if ($withStyle)
<style>
.cw-ls{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;padding:10px 24px;font:500 15px system-ui,sans-serif}
.cw-ls a{color:inherit;text-decoration:none}
.cw-ls-info{display:flex;align-items:center;gap:24px;flex-wrap:wrap}
.cw-ls-info a{display:inline-flex;align-items:center;gap:8px}
.cw-ls-btn{display:inline-flex;align-items:center;gap:8px;padding:6px 16px;border:1px solid currentColor;border-radius:999px;font-weight:600;cursor:pointer;list-style:none;opacity:.95}
.cw-ls-btn::-webkit-details-marker{display:none}
.cw-ls-btn:hover{background:rgba(255,255,255,.12)}
.cw-ls details{position:relative;margin-left:auto}
.cw-ls-menu{position:absolute;right:0;top:calc(100% + 6px);z-index:50;min-width:150px;padding:4px;border-radius:10px;background:#fff;color:#0f172a;box-shadow:0 12px 28px -8px rgba(15,23,42,.3)}
.cw-ls-menu a{display:block;padding:7px 12px;border-radius:7px;font-weight:600}
.cw-ls-menu a:hover{background:#f1f5f9}
</style>
@endif
@php
    $label = fn ($l) => $l->native_name ?: $l->name;
    $globe = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/></svg>';
@endphp
<div class="cw-ls" style="background:{{ $background }};color:{{ $color }}">
    <div class="cw-ls-info">
        @if ($email)
            <a href="mailto:{{ $email }}">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>{{ $email }}
            </a>
        @endif
        @if ($phone)
            <a href="tel:{{ preg_replace('/[^\d+]/', '', $phone) }}">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2Z"/></svg>{{ $phone }}
            </a>
        @endif
    </div>

    @if ($other->count() === 1)
        <a class="cw-ls-btn" style="margin-left:auto" href="?lang={{ $other->first()->code }}" hreflang="{{ $other->first()->code }}">{!! $globe !!}{{ $label($other->first()) }}</a>
    @elseif ($other->count() > 1)
        <details>
            <summary class="cw-ls-btn">{!! $globe !!}{{ $label($languages->firstWhere('code', $current) ?? $other->first()) }}</summary>
            <div class="cw-ls-menu">
                @foreach ($languages as $language)
                    <a href="?lang={{ $language->code }}" hreflang="{{ $language->code }}"@if ($language->code === $current) style="font-weight:800" @endif>{{ $label($language) }}</a>
                @endforeach
            </div>
        </details>
    @endif
</div>
