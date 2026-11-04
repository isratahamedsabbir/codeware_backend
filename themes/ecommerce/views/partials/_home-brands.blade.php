@if ($homeBrands->isNotEmpty())
    <section class="mx-auto max-w-7xl px-4 pt-8 sm:px-6">
        <style>
            .home-brand-marquee {
                will-change: transform;
            }
            .home-brand-viewport {
                -webkit-mask-image: linear-gradient(90deg, transparent, #000 4%, #000 96%, transparent);
                mask-image: linear-gradient(90deg, transparent, #000 4%, #000 96%, transparent);
                cursor: grab;
                touch-action: pan-y;
                user-select: none;
            }
            .home-brand-viewport.is-dragging {
                cursor: grabbing;
            }
            @media (prefers-reduced-motion: reduce) {
                .home-brand-marquee { will-change: auto; }
                .home-brand-viewport {
                    -webkit-mask-image: none;
                    mask-image: none;
                }
            }
        </style>
        <div class="rounded-xl p-0.5 sm:p-1">
          <div
            x-data="{
                offset: 0,
                startX: 0,
                startOffset: 0,
                pointerId: null,
                dragging: false,
                moved: false,
                autoMove: !window.matchMedia('(prefers-reduced-motion: reduce)').matches,
                lastTime: 0,
                frame: null,
                initMarquee() {
                    if (this.autoMove) this.frame = requestAnimationFrame((time) => this.animateBrands(time));
                },
                animateBrands(time) {
                    if (this.lastTime && this.autoMove && !this.dragging) {
                        this.offset += (time - this.lastTime) * 0.024;
                        this.normalizeOffset();
                        this.renderBrands();
                    }
                    this.lastTime = time;
                    this.frame = requestAnimationFrame((nextTime) => this.animateBrands(nextTime));
                },
                normalizeOffset() {
                    const loopWidth = this.$refs.track.scrollWidth / 2;
                    if (loopWidth > 0) this.offset = ((this.offset % loopWidth) + loopWidth) % loopWidth;
                },
                renderBrands() {
                    this.$refs.track.style.transform = `translate3d(-${this.offset}px, 0, 0)`;
                },
                startBrandDrag(event) {
                    if (event.button !== undefined && event.button !== 0) return;
                    this.dragging = true;
                    this.moved = false;
                    this.pointerId = event.pointerId;
                    this.startX = event.clientX;
                    this.startOffset = this.offset;
                    event.currentTarget.classList.add('is-dragging');
                },
                moveBrandDrag(event) {
                    if (!this.dragging || event.pointerId !== this.pointerId) return;
                    const distance = this.startX - event.clientX;
                    if (Math.abs(distance) > 4) this.moved = true;
                    if (!this.moved) return;
                    this.offset = this.startOffset + distance;
                    this.normalizeOffset();
                    this.renderBrands();
                },
                stopBrandDrag(event) {
                    if (!this.dragging || (event && event.pointerId !== this.pointerId)) return;
                    this.dragging = false;
                    this.pointerId = null;
                    this.lastTime = 0;
                    this.$refs.viewport.classList.remove('is-dragging');
                    if (this.moved) window.setTimeout(() => { this.moved = false; }, 0);
                },
                preventBrandClick(event) {
                    if (!this.moved) return;
                    event.preventDefault();
                    event.stopPropagation();
                    this.moved = false;
                },
                destroy() {
                    if (this.frame) cancelAnimationFrame(this.frame);
                },
            }"
            x-init="initMarquee()"
            @pointerdown="startBrandDrag($event)"
            @pointermove.window="moveBrandDrag($event)"
            @pointerup.window="stopBrandDrag($event)"
            @pointercancel.window="stopBrandDrag($event)"
            @click.capture="preventBrandClick($event)"
            x-ref="viewport"
            class="home-brand-viewport no-scrollbar overflow-x-auto overscroll-x-contain rounded-xl">
            <div x-ref="track" class="home-brand-marquee flex w-max">
                @foreach ([false, true] as $duplicate)
                    <div @if ($duplicate) aria-hidden="true" inert @endif class="flex shrink-0 gap-3 py-1 pr-3 sm:gap-4 sm:py-1.5 sm:pr-4">
                        @foreach ($homeBrands as $brand)
                            <a href="{{ route('shop', ['brand' => $brand->slug]) }}"
                                @if ($duplicate) tabindex="-1" @endif
                                draggable="false"
                                class="group flex min-h-[56px] w-[140px] shrink-0 items-center justify-center rounded-xl border border-zinc-200/80 bg-white px-3 py-2 shadow-[0_3px_10px_-6px_rgba(15,23,42,0.28)] transition duration-300 hover:-translate-y-1 hover:border-brand/30 hover:shadow-[0_12px_24px_-12px_rgba(4,91,48,0.35)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand sm:min-h-[64px] sm:w-[170px]">
                                @if ($brand->logo)
                                    <img src="{{ $brand->logo }}" alt="{{ $brand->name }}" loading="lazy" decoding="async" draggable="false" width="160" height="48" class="max-h-10 w-auto object-contain grayscale transition duration-300 group-hover:scale-105 group-hover:grayscale-0">
                                @else
                                    <span class="text-xs font-bold uppercase tracking-[0.12em] text-zinc-600 transition-colors group-hover:text-brand sm:text-sm">{{ $brand->name }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                @endforeach
            </div>
          </div>
        </div>
    </section>
@endif
