{{-- Output of the builder's "Brands" block: a row of logos. --}}
@if ($withStyle)
<style>
.cw-br{display:flex;flex-wrap:wrap;gap:36px;align-items:center;justify-content:center;max-width:1140px;margin:0 auto;padding:32px 20px}
.cw-br img{max-height:44px;width:auto;object-fit:contain;filter:grayscale(1);opacity:.75;transition:filter .2s,opacity .2s}
.cw-br img:hover{filter:none;opacity:1}
.cw-br span{font-weight:700;color:#64748b}
</style>
@endif
@include('builders.blocks._heading')
@if ($brands->isNotEmpty())
    <div class="cw-br">
        @foreach ($brands as $brand)
            @if ($brand->logo)
                <img src="{{ $brand->logo }}" alt="{{ $brand->name }}" loading="lazy">
            @else
                <span>{{ $brand->name }}</span>
            @endif
        @endforeach
    </div>
@endif
