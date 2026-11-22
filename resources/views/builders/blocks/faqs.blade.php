{{-- Output of the builder's "FAQ" block: an accordion of questions. --}}
@if ($withStyle)
<style>
.cw-faq{max-width:820px;margin:0 auto;padding:32px 20px}
.cw-faq details{border-bottom:1px solid #e2e8f0;padding:16px 0}
.cw-faq summary{cursor:pointer;font-weight:600;list-style:none;display:flex;justify-content:space-between;gap:16px}
.cw-faq summary::-webkit-details-marker{display:none}
.cw-faq summary::after{content:"+";font-size:1.25rem;line-height:1;color:#64748b}
.cw-faq details[open] summary::after{content:"\2212"}
.cw-faq .cw-faq-a{margin-top:10px;color:#475569;line-height:1.7}
</style>
@endif
@include('builders.blocks._heading')
@if ($faqs->isNotEmpty())
    <div class="cw-faq">
        @foreach ($faqs as $faq)
            <details>
                <summary>{{ $faq->question }}</summary>
                <div class="cw-faq-a">{!! nl2br(e($faq->answer)) !!}</div>
            </details>
        @endforeach
    </div>
@endif
