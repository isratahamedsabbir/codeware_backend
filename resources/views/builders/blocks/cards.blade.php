{{-- Output of the builder's "Latest posts" / "Services" blocks. Self-contained (cw-cd- classes). --}}
@if ($withStyle)
<style>
.cw-cd{display:grid;grid-template-columns:repeat(var(--cw-cols,3),1fr);gap:24px;max-width:1140px;margin:0 auto;padding:32px 20px}
.cw-cd-card{display:flex;flex-direction:column;border:1px solid #e2e8f0;border-radius:16px;background:#fff;overflow:hidden;color:inherit;text-decoration:none}
.cw-cd-img{aspect-ratio:16/10;background:#f1f5f9;display:block;width:100%;object-fit:cover}
.cw-cd-body{padding:18px;display:block}
.cw-cd-meta{display:block;margin-bottom:6px;font-size:.8rem;color:#94a3b8}
.cw-cd-title{margin:0 0 8px;font-size:1.1rem;font-weight:700;line-height:1.35}
.cw-cd-text{margin:0;color:#64748b;line-height:1.6;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden}
.cw-cd-empty{text-align:center;color:#64748b;padding:40px 20px}
@media(max-width:900px){.cw-cd{grid-template-columns:repeat(2,1fr)}}
@media(max-width:480px){.cw-cd{grid-template-columns:1fr}}
</style>
@endif
@if ($items->isEmpty())
    <div class="cw-cd-empty">Nothing to show yet.</div>
@else
    <div class="cw-cd" style="--cw-cols:{{ $columns }}">
        @foreach ($items as $item)
            <a class="cw-cd-card" href="{{ $item['url'] }}">
                @if ($item['image'])
                    <img class="cw-cd-img" src="{{ $item['image'] }}" alt="{{ $item['title'] }}" loading="lazy">
                @else
                    <span class="cw-cd-img"></span>
                @endif
                <span class="cw-cd-body">
                    @if ($item['meta'])<span class="cw-cd-meta">{{ $item['meta'] }}</span>@endif
                    <h3 class="cw-cd-title">{{ $item['title'] }}</h3>
                    @if ($item['text'] !== '')<p class="cw-cd-text">{{ \Illuminate\Support\Str::limit(strip_tags($item['text']), 140) }}</p>@endif
                </span>
            </a>
        @endforeach
    </div>
@endif
