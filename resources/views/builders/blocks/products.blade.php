{{-- Output of the builder's "Products" block. Self-contained (cw-pg- classes), like every builder block. --}}
@if ($withStyle)
<style>
.cw-pg{display:grid;grid-template-columns:repeat(var(--cw-cols,4),1fr);gap:24px;max-width:1140px;margin:0 auto;padding:32px 20px}
.cw-pg-card{display:block;border:1px solid #e2e8f0;border-radius:16px;background:#fff;overflow:hidden;color:inherit;text-decoration:none}
.cw-pg-img{aspect-ratio:1/1;background:#f1f5f9;display:block;width:100%;object-fit:cover}
.cw-pg-body{padding:16px}
.cw-pg-name{margin:0 0 8px;font-size:1rem;font-weight:600;line-height:1.35}
.cw-pg-price{font-weight:700;color:#2563eb}
.cw-pg-price s{margin-left:8px;font-weight:400;color:#94a3b8}
.cw-pg-empty{text-align:center;color:#64748b;padding:40px 20px}
@media(max-width:900px){.cw-pg{grid-template-columns:repeat(2,1fr)}}
@media(max-width:480px){.cw-pg{grid-template-columns:1fr}}
</style>
@endif
@if ($products->isEmpty())
    <div class="cw-pg-empty">No products found.</div>
@else
    <div class="cw-pg" style="--cw-cols:{{ $columns }}">
        @foreach ($products as $product)
            @php($sale = $product->discount_price !== null && (float) $product->discount_price > 0 && (float) $product->discount_price < (float) $product->price)
            <a class="cw-pg-card" href="{{ $url($product) }}">
                @if ($product->featured_image)
                    <img class="cw-pg-img" src="{{ $product->featured_image }}" alt="{{ $product->name }}" loading="lazy">
                @else
                    <span class="cw-pg-img"></span>
                @endif
                <span class="cw-pg-body">
                    <h3 class="cw-pg-name">{{ $product->name }}</h3>
                    <span class="cw-pg-price">
                        {{ format_money($sale ? $product->discount_price : $product->price) }}
                        @if ($sale)<s>{{ format_money($product->price) }}</s>@endif
                    </span>
                </span>
            </a>
        @endforeach
    </div>
@endif
