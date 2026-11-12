{{--
    Classic Ecommerce — the home page's banners, shown under Page Settings → Home.

    Included by the Theme Settings screen's Page Settings card when a theme ships a
    page-settings.blade.php at its root. Binds to the screen's component state:
    $heroSlides (heroSlides.{i}.image|title|link|description) and
    $promo (promo.{0|1}.image|link). Saved by its Save Page Settings button.
--}}
<div class="space-y-3" wire:key="classic-home-banners">
    @php
        // What the storefront draws at full desktop width (816 × 440 hero, 400 × 212
        // tiles), doubled so the image stays sharp on high-density screens. Wider
        // or taller images are cropped to fit, so keep the subject centred.
        $heroSize = '1632 × 880';
        $promoSize = '800 × 424';

        $promoTiles = [
            [0, 'New arrivals', 'Top tile', '/shop?sort=newest'],
            [1, 'Best deals', 'Bottom tile', '/shop'],
        ];
    @endphp

    {{-- Banners — laid out exactly like the storefront's homepage header: the
         hero slider on the left, the two promo tiles stacked beside it. Every
         slide's picker and fields stay mounted (x-show) so each keeps its
         Livewire binding while you switch slides. `isolate` keeps the
         z-indexed cards on the images from rising above the sticky page
         header and save bar. --}}
    <section x-data="{ activeSlide: 0 }"
        class="isolate overflow-hidden rounded-xl bg-white dark:bg-zinc-900">
        {{-- ── Canvas: hero (2/3) + promo tiles (1/3) ── --}}
        <div class="bg-zinc-50/70 p-5 dark:bg-zinc-800/40">
            <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
                {{-- Hero slider --}}
                <div class="min-w-0 space-y-3 xl:col-span-2">
                    {{-- Each slide's text and link sit on the image itself, where
                         the storefront shows them. The fields are a sibling of
                         the picker (not inside its button), so typing never
                         opens the media library. --}}
                    @foreach ($heroSlides as $i => $slide)
                        <div wire:key="hero-slide-{{ $i }}" x-show="activeSlide === {{ $i }}" @if ($i > 0) x-cloak @endif class="relative">
                            <x-media-picker model="heroSlides.{{ $i }}.image" label="Slide {{ $i + 1 }}" :size-hint="$heroSize" preview dropzone drop-height="h-72 2xl:h-80"
                                only-images mimes="jpg,jpeg,png,gif,webp,avif" :max-size-mb="4" placeholder="Choose from the library" />
                            @if (filled($slide['image']))
                                <span class="pointer-events-none absolute left-3 top-3 z-10 rounded-md bg-zinc-900/75 px-2 py-1 text-[11px] font-semibold text-white backdrop-blur">
                                    Slide {{ $i + 1 }} · {{ $heroSize }}
                                </span>
                            @endif

                            <div class="absolute inset-x-4 bottom-4 z-20 space-y-2">
                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-[minmax(0,1fr)_14rem]">
                                    <flux:input size="sm" wire:model="heroSlides.{{ $i }}.title" placeholder="Title — e.g. Fresh organic tea" maxlength="120" aria-label="Slide {{ $i + 1 }} title" />
                                    <flux:input size="sm" wire:model="heroSlides.{{ $i }}.link" icon="link" placeholder="/shop" aria-label="Slide {{ $i + 1 }} link"
                                        title="A page on this site (e.g. /shop?category=tea) or a full https:// address. Blank opens the Shop page." />
                                </div>
                                <flux:textarea wire:model="heroSlides.{{ $i }}.description" rows="2" class="resize-none text-sm"
                                    placeholder="Description — e.g. Hand-picked leaves, delivered fresh to your door." maxlength="300" aria-label="Slide {{ $i + 1 }} description" />
                            </div>
                        </div>
                    @endforeach

                    <div class="flex items-center gap-2 overflow-x-auto pb-1">
                        @foreach ($heroSlides as $i => $slide)
                            <div wire:key="hero-tab-{{ $i }}" class="group/tab relative shrink-0 pt-1.5 pr-1.5">
                                <button type="button" x-on:click="activeSlide = {{ $i }}" aria-label="Slide {{ $i + 1 }}"
                                    x-bind:class="activeSlide === {{ $i }}
                                        ? 'border-primary ring-2 ring-primary/20'
                                        : 'border-zinc-200 opacity-75 hover:opacity-100 hover:border-zinc-300 dark:border-zinc-700'"
                                    class="relative flex h-12 w-20 items-center justify-center overflow-hidden rounded-lg! border-2 bg-zinc-100 text-xs font-semibold text-zinc-500 transition dark:bg-zinc-800">
                                    @if (filled($slide['image']))
                                        <img src="{{ $slide['image'] }}" alt="" class="h-full w-full object-cover">
                                    @else
                                        <flux:icon.photo variant="mini" class="size-4 text-zinc-400" />
                                    @endif
                                    <span class="absolute bottom-0.5 left-0.5 rounded bg-zinc-900/70 px-1 text-[10px] font-bold leading-4 text-white">{{ $i + 1 }}</span>
                                </button>
                                @if (count($heroSlides) > 1)
                                    <button type="button" title="Remove slide {{ $i + 1 }}"
                                        wire:click="removeHeroSlide({{ $i }})"
                                        x-on:click="activeSlide = Math.max(0, Math.min(activeSlide, $wire.heroSlides.length - 2))"
                                        class="absolute right-0 top-0 hidden size-5 items-center justify-center rounded-full! bg-white text-red-500 shadow ring-1 ring-zinc-200 group-hover/tab:flex dark:bg-zinc-800 dark:ring-zinc-700">
                                        <flux:icon.x-mark variant="micro" class="size-3" />
                                    </button>
                                @endif
                            </div>
                        @endforeach

                        @if (count($heroSlides) < \App\Livewire\Admin\ThemeSettings\Index::MAX_HERO_SLIDES)
                            {{-- Switches to the new slide only once the server has added it —
                                 switching first leaves no slide to show until the response
                                 lands, and the hero collapses then grows back. The click
                                 expressions here and on the remove buttons read the count
                                 from $wire rather than baking it in: an x-on:click whose text
                                 changes between renders gets rebound by the morph, and the
                                 button stops working after the first click. --}}
                            <div wire:key="hero-tab-add" class="shrink-0 pt-1.5 pr-1.5">
                                <button type="button" title="Add slide" aria-label="Add slide"
                                    x-on:click="$wire.addHeroSlide().then(() => activeSlide = $wire.heroSlides.length - 1)"
                                    class="flex h-12 w-20 flex-col items-center justify-center gap-0.5 rounded-lg! border-2 border-dashed border-zinc-300 text-zinc-400 transition hover:border-primary hover:bg-primary/5 hover:text-primary dark:border-zinc-600">
                                    <flux:icon.plus variant="mini" class="size-4" />
                                    <span class="text-[10px] font-semibold leading-none">Add</span>
                                </button>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Promo tiles — stacked beside the hero on desktop, side by
                     side under it on smaller screens, as on the storefront. --}}
                <div class="min-w-0 space-y-3">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-1">
                        @foreach ($promoTiles as [$n, $promoLabel, $promoPosition, $example])
                            <div class="relative min-w-0">
                                <x-media-picker model="promo.{{ $n }}.image" label="{{ $promoLabel }}" :size-hint="$promoSize" preview dropzone drop-height="h-32 2xl:h-36"
                                    only-images mimes="jpg,jpeg,png,gif,webp,avif" :max-size-mb="4" placeholder="Choose from the library" />
                                @if (filled($promo[$n]['image'] ?? null))
                                    <span class="pointer-events-none absolute left-3 top-3 z-10 rounded-md bg-zinc-900/75 px-2 py-1 text-[11px] font-semibold text-white backdrop-blur">
                                        {{ $promoLabel }} · {{ $promoPosition }} · {{ $promoSize }}
                                    </span>
                                @endif

                                {{-- The tile's link, on the tile (see the hero fields above). --}}
                                <div class="absolute inset-x-3 bottom-3 z-20">
                                    <flux:input size="sm" wire:model="promo.{{ $n }}.link" icon="link" placeholder="{{ $example }}" aria-label="{{ $promoLabel }} link"
                                        title="A page on this site or a full https:// address. Blank opens the Shop page." />
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
