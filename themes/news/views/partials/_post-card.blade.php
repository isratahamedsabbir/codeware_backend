{{--
    One story, in one of four shapes. Expects $post and $variant:
      lead   — big image over a large serif headline (the front page's top story)
      side   — image over a medium headline
      row    — headline and excerpt beside a small thumbnail (lists)
      card   — image, headline and excerpt stacked (grids)
    The headline link is stretched over the whole card (after:absolute), so there
    is one tab stop per story and the category link is lifted above it with z-10.
--}}
@php
    $variant = $variant ?? 'card';
    $url = \App\Support\UrlStructure::url('post', $post->slug);
    $category = ($post->category?->page && $post->category->status === 'active') ? $post->category : null;

    $body = is_array($post->description) ? ($post->description[app()->getLocale()] ?? reset($post->description)) : $post->description;
    $excerpt = \Illuminate\Support\Str::limit(trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags((string) $body)))), $variant === 'lead' ? 220 : 140);
    $ratio = $variant === 'lead' ? 'aspect-[16/9]' : 'aspect-[16/10]';
@endphp

<article class="group relative {{ $variant === 'row' ? 'flex gap-4' : 'flex flex-col' }}">
    @if (filled($post->featured_image))
        <div class="overflow-hidden bg-zinc-100 {{ $variant === 'row' ? 'h-20 w-28 shrink-0 sm:h-24 sm:w-36' : $ratio }}">
            <img src="{{ $post->featured_image }}" alt="" loading="{{ $variant === 'lead' ? 'eager' : 'lazy' }}" decoding="async"
                class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
        </div>
    @elseif ($variant !== 'row')
        <div class="nw-serif flex items-center justify-center bg-zinc-100 text-3xl font-bold text-zinc-300 {{ $ratio }}">
            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($post->title, 0, 1)) }}
        </div>
    @endif

    <div class="{{ $variant === 'row' ? 'min-w-0 flex-1' : 'mt-3' }}">
        @if ($category)
            <a href="{{ route('blog', ['category' => $category->page->slug]) }}"
                class="relative z-10 text-[11px] font-bold uppercase tracking-widest hover:underline" style="color: var(--nw-accent)">
                {{ $category->name }}
            </a>
        @endif

        <h3 class="nw-serif mt-1 font-bold leading-tight text-zinc-900 {{ $variant === 'lead' ? 'text-3xl sm:text-4xl' : ($variant === 'side' ? 'text-xl' : 'text-lg') }}">
            <a href="{{ $url }}" class="decoration-1 underline-offset-4 after:absolute after:inset-0 after:content-[''] group-hover:underline">{{ $post->title }}</a>
        </h3>

        @if ($excerpt !== '' && in_array($variant, ['lead', 'card', 'row'], true))
            <p class="mt-2 text-sm leading-relaxed text-zinc-600 {{ $variant === 'row' ? 'line-clamp-2' : '' }}">{{ $excerpt }}</p>
        @endif

        <p class="mt-2 flex flex-wrap items-center gap-x-2 text-xs text-zinc-500">
            @if ($post->user?->name)
                <span class="font-medium text-zinc-700">{{ $post->user->name }}</span>
            @endif
            @if ($post->published_at)
                @if ($post->user?->name)<span aria-hidden="true">&middot;</span>@endif
                <time datetime="{{ $post->published_at->toDateString() }}">{{ $post->published_at->toDisplay() }}</time>
            @endif
            @if ($post->reading_time)
                <span aria-hidden="true">&middot;</span>
                <span>{{ __(':min min read', ['min' => $post->reading_time]) }}</span>
            @endif
        </p>
    </div>
</article>
