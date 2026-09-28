@extends('website.layout')
@php $ws = app(\App\Services\WebsiteService::class); @endphp

@section('content')

{{-- Hero: 3 portions — main slider + 2 CMS side cards --}}
@php
    $heroSideCards = ($heroSideCards ?? collect())->take(2)->map(function ($banner) {
        return (object) [
            'title' => $banner->title,
            'sub' => $banner->subtitle ?: ($banner->badge_text ?: ''),
            'url' => $banner->button_url ?: route('website.shop'),
            'cta' => $banner->button_text ?: 'Shop',
            'badge' => $banner->discount_badge ?: $banner->badge_text,
            'image' => $banner->image_path ? public_storage_url($banner->image_path) : null,
            'tone' => $banner->theme === 'light' ? 'light' : 'dark',
        ];
    });

    // Keep 3-portion layout visible until CMS cards are added
    if ($heroSideCards->isEmpty()) {
        $heroSideCards = collect([
            (object) [
                'title' => 'New Arrivals',
                'sub' => 'Fresh tech, just landed',
                'url' => route('website.shop', ['filter' => 'new']),
                'cta' => 'See new',
                'badge' => 'New',
                'image' => 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=640&q=80',
                'tone' => 'dark',
            ],
            (object) [
                'title' => 'Best Sellers',
                'sub' => 'Most loved gadgets',
                'url' => route('website.shop'),
                'cta' => 'Shop',
                'badge' => 'Hot',
                'image' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=640&q=80',
                'tone' => 'light',
            ],
        ]);
    }
@endphp
<section class="mg-hero3">
    <div class="tn-container">
        <div class="mg-hero3-grid">
            <div
                class="mg-hero3-slider tn-hero"
    @if($heroSlides->count() > 1)
                    x-data="{
                        slide: 0,
                        total: {{ $heroSlides->count() }},
                        timer: null,
                        paused: false,
                        go(i) { this.slide = ((i % this.total) + this.total) % this.total; this.restart(); },
                        next() { this.go(this.slide + 1); },
                        prev() { this.go(this.slide - 1); },
                        restart() {
                            clearInterval(this.timer);
                            if (this.paused || this.total < 2) return;
                            this.timer = setInterval(() => { if (!this.paused) this.slide = (this.slide + 1) % this.total; }, 5500);
                        },
                        init() { this.restart(); }
                    }"
                    @mouseenter="paused = true; clearInterval(timer)"
                    @mouseleave="paused = false; restart()"
                    @focusin="paused = true; clearInterval(timer)"
                    @focusout="paused = false; restart()"
                @else
                    x-data="{ slide: 0, total: 1 }"
    @endif
            >
                <div class="tn-hero-track">
    @forelse($heroSlides as $i => $slide)
        @php
            $posterUrl = $slide->image_path ? public_storage_url($slide->image_path) : null;
            if ($posterUrl && $slide->image_path) {
                $full = public_storage_path($slide->image_path);
                $posterUrl .= '?v='.(is_file($full) ? filemtime($full) : time());
            }
            $link = $slide->button_url ?: route('website.shop');
        @endphp
                        <div
                            class="tn-hero-slide{{ $i === 0 ? ' is-active' : '' }}"
                            :class="{ 'is-active': slide === {{ $i }} }"
                            x-bind:aria-hidden="slide !== {{ $i }}"
                        >
            @if($posterUrl)
                                <a href="{{ $link }}" class="tn-hero-poster" aria-label="{{ $slide->title }}" x-bind:tabindex="slide === {{ $i }} ? 0 : -1">
                    <img
                        src="{{ $posterUrl }}"
                        alt="{{ $slide->title }}"
                        class="tn-hero-img"
                        width="1920"
                        height="640"
                        decoding="async"
                                        @if($i === 0) fetchpriority="high" @else loading="lazy" @endif
                    >
                </a>
            @else
                <div class="tn-hero-fallback">
                                    <div class="tn-hero-fallback-inner">
                        <p class="tn-hero-kicker">{{ data_get($settings, 'special_offer_text') ?: 'Premium Electronics' }}</p>
                        <h1 class="tn-hero-title">Upgrade Your Digital Life</h1>
                        <p class="tn-hero-sub">Discover the latest gadgets, unbeatable deals, and premium tech at {{ $settings->store_name ?? 'our store' }}.</p>
                        <div class="tn-hero-actions">
                            <a href="{{ route('website.shop') }}" class="tn-btn tn-btn-primary">Shop Now</a>
                            <a href="{{ route('website.shop', ['filter' => 'new']) }}" class="tn-btn tn-btn-outline">Explore Collection</a>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @empty
                        <div class="tn-hero-slide is-active">
            <div class="tn-hero-fallback">
                                <div class="tn-hero-fallback-inner">
                    <p class="tn-hero-kicker">{{ data_get($settings, 'special_offer_text') ?: 'Premium Electronics' }}</p>
                    <h1 class="tn-hero-title">Upgrade Your Digital Life</h1>
                                    <p class="tn-hero-sub">Discover the latest gadgets, unbeatable deals, and premium tech at {{ $settings->store_name ?? config('app.name', 'Maks Gadget') }}.</p>
                    <div class="tn-hero-actions">
                        <a href="{{ route('website.shop') }}" class="tn-btn tn-btn-primary">Shop Now</a>
                        <a href="{{ route('website.shop', ['filter' => 'new']) }}" class="tn-btn tn-btn-outline">Explore Collection</a>
                    </div>
                </div>
            </div>
        </div>
    @endforelse
                </div>
    @if($heroSlides->count() > 1)
                    <div class="tn-hero-dots" role="tablist" aria-label="Hero slides">
            @foreach($heroSlides as $di => $ds)
                            <button
                                type="button"
                                class="tn-hero-dot"
                                :class="{ 'is-active': slide === {{ $di }} }"
                                @click="go({{ $di }})"
                                role="tab"
                                :aria-selected="slide === {{ $di }}"
                                aria-label="Slide {{ $di + 1 }}"
                            >
                                <template x-if="slide === {{ $di }}">
                                    <span class="tn-hero-dot-fill"></span>
                                </template>
                            </button>
            @endforeach
        </div>
    @endif
            </div>

            <aside class="mg-hero3-side" aria-label="Featured offers">
                @foreach($heroSideCards->take(2) as $side)
                    <a href="{{ $side->url }}" class="mg-hero3-card is-{{ $side->tone }}">
                        @if($side->image)
                            <img src="{{ $side->image }}" alt="" class="mg-hero3-card-img" loading="lazy" decoding="async">
                        @endif
                        <span class="mg-hero3-card-veil" aria-hidden="true"></span>
                        <span class="mg-hero3-card-body">
                            @if($side->badge)
                                <span class="mg-hero3-card-badge">{{ $side->badge }}</span>
                            @endif
                            <span class="mg-hero3-card-title">{{ $side->title }}</span>
                            @if(($side->sub ?? '') !== '')
                                <span class="mg-hero3-card-sub">{{ $side->sub }}</span>
                            @endif
                            <span class="mg-hero3-card-cta">{{ $side->cta }} <i aria-hidden="true">→</i></span>
                        </span>
                    </a>
                @endforeach
            </aside>
        </div>
    </div>
</section>

{{-- Service features — premium strip under hero --}}
@if($features->isNotEmpty())
@php $homeCopy = data_get($settings, 'home_copy') ?: []; @endphp
<section class="tn-features">
    <div class="tn-container">
        <div class="tn-features-panel">
            <div class="tn-features-grid tn-features-grid--{{ min(max($features->count(), 1), 4) }}">
                @foreach($features as $feature)
                    <div class="tn-feature">
                        <div class="tn-feature-icon">@include('website.partials.feature-icon', ['icon' => $feature->icon])</div>
                        <div class="tn-feature-copy">
                            <p class="tn-feature-title">{{ $feature->title }}</p>
                            @if($feature->subtitle)<p class="tn-feature-sub">{{ $feature->subtitle }}</p>@endif
                        </div>
                    </div>
                @endforeach
            </div>
            @if(!empty($settings->trusted_by_text))
                <p class="tn-features-trusted">{{ $settings->trusted_by_text }}</p>
            @endif
        </div>
    </div>
</section>
@endif

{{-- Shop by Category — 3D coverflow with scroll / swipe / click --}}
@if($categories->isNotEmpty())
@php
    $homeCopy = data_get($settings, 'home_copy') ?: [];
    $catTaglines = [
        'smartphones' => 'Power in your pocket.',
        'phones' => 'Power in your pocket.',
        'laptops' => 'Create. Work. Win.',
        'tablets' => 'Light. Fast. Ready.',
        'headphones' => 'Immersive sound.',
        'earbuds' => 'Pure sound. Zero limits.',
        'earphones' => 'Pure sound. Zero limits.',
        'smartwatches' => 'Smarter. Healthier. You.',
        'watches' => 'Smarter. Healthier. You.',
        'cameras' => 'Capture every moment.',
        'gaming' => 'Play without limits.',
        'speakers' => 'Fill the room.',
        'chargers-cables' => 'Power that lasts.',
        'accessories' => 'Finish the look.',
        'monitors' => 'See every detail.',
        'drones' => 'Sky is the limit.',
    ];
    $catFallbacks = [
        'phone' => 'https://images.unsplash.com/photo-1592890288564-766794220d53?w=900&q=85',
        'laptop' => 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=900&q=85',
        'tablet' => 'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?w=900&q=85',
        'headphones' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=900&q=85',
        'earbuds' => 'https://images.unsplash.com/photo-1606220588913-b3aacb4d2f46?w=900&q=85',
        'watch' => 'https://images.unsplash.com/photo-1434493789847-2f02dc6ca35d?w=900&q=85',
        'camera' => 'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?w=900&q=85',
        'game' => 'https://images.unsplash.com/photo-1606144042614-b2417e99c4e3?w=900&q=85',
        'speaker' => 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=900&q=85',
        'drone' => 'https://images.unsplash.com/photo-1473968512647-3e447244af8f?w=900&q=85',
        'monitor' => 'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?w=900&q=85',
        'mouse' => 'https://images.unsplash.com/photo-1527814050087-3793815479db?w=900&q=85',
        'plug' => 'https://images.unsplash.com/photo-1583863788434-e58a36330cf0?w=900&q=85',
    ];
    $catThemes = ['is-ink', 'is-graphite', 'is-midnight', 'is-steel', 'is-ink', 'is-graphite', 'is-midnight', 'is-steel'];
    $coverCats = $categories->values();
    $coverTotal = $coverCats->count();
    $coverStart = $coverTotal > 1 ? min(1, $coverTotal - 1) : 0;
@endphp
<section
    class="mg-cover"
    :class="{ 'is-mobile-cover': narrow }"
    x-data="{
        active: {{ $coverStart }},
        total: {{ $coverTotal }},
        timer: null,
        dragging: false,
        dragMoved: false,
        dragStartX: 0,
        dragDelta: 0,
        narrow: typeof window !== 'undefined' && window.matchMedia('(max-width: 900px)').matches,
        syncNarrow() {
            this.narrow = window.matchMedia('(max-width: 900px)').matches;
        },
        go(i) {
            if (this.total < 1) return;
            this.active = ((i % this.total) + this.total) % this.total;
            this.arm();
        },
        next() { this.go(this.active + 1); },
        prev() { this.go(this.active - 1); },
        delta(i) {
            const n = this.total;
            if (n < 2) return 0;
            let d = ((i - this.active) % n + n) % n;
            if (d > n / 2) d -= n;
            return d;
        },
        styleFor(i) {
            const d = this.delta(i);
            const abs = Math.abs(d);
            const mobile = this.narrow;
            const dragNudge = this.dragging ? (this.dragDelta * (mobile ? 0.18 : 0.12)) : 0;
            if (mobile) {
                /* Flat peek coverflow — center focus, neighbors peek (matches previous style) */
                if (abs > 2) {
                    return 'opacity:0; visibility:hidden; pointer-events:none; transform: translate(-50%, -50%) scale(0.62);';
                }
                const x = d * 72;
                const scale = abs === 0 ? 1 : (abs === 1 ? 0.84 : 0.72);
                const opacity = abs === 0 ? 1 : (abs === 1 ? 0.9 : 0.55);
                const z = 40 - abs;
                return `transform: translate(-50%, -50%) translateX(calc(${x}% + ${dragNudge}px)) scale(${scale}); z-index:${z}; opacity:${opacity};`;
            }
            if (abs > 3) {
                return 'opacity:0; visibility:hidden; pointer-events:none; transform: translate(-50%, -50%) scale(0.55);';
            }
            const x = d * 54;
            const y = abs * 2.5;
            const rot = d * -22;
            const scale = Math.max(0.72, 1 - abs * 0.1);
            const z = 50 - abs;
            const opacity = abs === 0 ? 1 : (abs === 1 ? 0.9 : (abs === 2 ? 0.62 : 0.4));
            return `transform: translate(-50%, -50%) translateX(calc(${x}% + ${dragNudge}px)) translateY(${y}%) rotateY(${rot}deg) scale(${scale}); z-index:${z}; opacity:${opacity};`;
        },
        arm() {
            clearInterval(this.timer);
            if (this.total < 2 || this.dragging) return;
            this.timer = setInterval(() => { this.next(); }, 4200);
        },
        onPointerDown(e) {
            if (this.total < 2) return;
            if (e.pointerType === 'mouse' && e.button !== 0) return;
            this.dragging = true;
            this.dragMoved = false;
            this.dragStartX = e.clientX;
            this.dragDelta = 0;
            clearInterval(this.timer);
            try { e.currentTarget.setPointerCapture(e.pointerId); } catch (_) {}
        },
        onPointerMove(e) {
            if (!this.dragging) return;
            this.dragDelta = e.clientX - this.dragStartX;
            if (Math.abs(this.dragDelta) > 8) this.dragMoved = true;
        },
        onPointerUp(e) {
            if (!this.dragging) return;
            this.dragging = false;
            const threshold = Math.min(64, Math.max(36, window.innerWidth * 0.1));
            if (this.dragDelta <= -threshold) this.next();
            else if (this.dragDelta >= threshold) this.prev();
            else this.arm();
            this.dragDelta = 0;
            try { e.currentTarget.releasePointerCapture(e.pointerId); } catch (_) {}
            if (this.dragMoved) {
                setTimeout(() => { this.dragMoved = false; }, 80);
            }
        },
        openCategory(url) {
            if (this.dragMoved || this.dragging) return;
            window.location.href = url;
        },
        onWheel(e) {
            if (this.total < 2) return;
            const dominant = Math.abs(e.deltaX) > Math.abs(e.deltaY) ? e.deltaX : (e.shiftKey ? e.deltaY : 0);
            if (!dominant) return;
            e.preventDefault();
            if (this._wheelLock) return;
            this._wheelLock = true;
            if (dominant > 0) this.next();
            else this.prev();
            setTimeout(() => { this._wheelLock = false; }, 420);
        }
    }"
    x-init="
        syncNarrow();
        arm();
        window.addEventListener('resize', () => {
            syncNarrow();
            arm();
        });
    "
>
    <div class="tn-container">
        <div class="mg-cover-head">
            <div class="mg-cover-top">
                <div class="mg-cover-copy">
                    <p class="mg-cover-eyebrow">{{ $homeCopy['categories_eyebrow'] ?? 'Curated collections' }}</p>
                    <h2 class="mg-cover-title">{{ $homeCopy['categories_title'] ?? 'Shop by' }} <span>{{ $homeCopy['categories_title_accent'] ?? 'Category' }}</span></h2>
                    <p class="mg-cover-sub">{{ $homeCopy['categories_subtitle'] ?? 'Premium gadgets, sorted for how you live — browse the collection.' }}</p>
                </div>
                <a href="{{ route('website.shop') }}" class="mg-cover-all">View all <span aria-hidden="true">→</span></a>
            </div>

            <div class="mg-cover-marquee"
                 aria-label="Categories preview"
                 @pointerdown="$el.classList.add('is-paused')"
                 @pointerup="$el.classList.remove('is-paused')"
                 @pointerleave="$el.classList.remove('is-paused')"
                 @touchstart.passive="$el.classList.add('is-paused')"
                 @touchend.passive="$el.classList.remove('is-paused')">
                <div class="mg-cover-marquee-fade mg-cover-marquee-fade--left" aria-hidden="true"></div>
                <div class="mg-cover-marquee-fade mg-cover-marquee-fade--right" aria-hidden="true"></div>
                <div class="mg-cover-marquee-track">
                    @foreach([0, 1] as $loopPass)
                        @foreach($coverCats as $i => $category)
                            @php
                                $iconMeta = $category->iconMeta();
                                $count = (int) ($category->products_count ?? 0);
                                $countLabel = $count > 0 ? ($count >= 100 ? '100+' : $count.'+') : 'New';
                            @endphp
                            <button
                                type="button"
                                class="mg-cover-marquee-item{{ $loopPass === 1 ? ' mg-cover-marquee-item--dup' : '' }}"
                                :class="{ 'is-active': active === {{ $i }} }"
                                @click="go({{ $i }})"
                                tabindex="{{ $loopPass === 0 ? 0 : -1 }}"
                                aria-hidden="{{ $loopPass === 0 ? 'false' : 'true' }}"
                            >
                                <span class="mg-cover-marquee-ico">
                                    @include('website.partials.category-icon-svg', ['icon' => $iconMeta['key'], 'class' => 'mg-cover-marquee-svg'])
                                </span>
                                <span class="mg-cover-marquee-text">
                                    <span class="mg-cover-marquee-name">{{ $category->name }}</span>
                                    <span class="mg-cover-marquee-count">{{ $countLabel }}</span>
                                </span>
                            </button>
                        @endforeach
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div
        class="mg-cover-stage"
        x-ref="coverStage"
        :class="{ 'is-dragging': dragging }"
        @pointerdown="onPointerDown($event)"
        @pointermove="onPointerMove($event)"
        @pointerup="onPointerUp($event)"
        @pointercancel="onPointerUp($event)"
        @wheel="onWheel($event)"
        role="region"
        aria-label="Swipe categories"
    >
        <div class="mg-cover-track" aria-live="polite">
            @foreach($coverCats as $i => $category)
                @php
                    $iconMeta = $category->iconMeta();
                    $slug = $category->slug ?? \Illuminate\Support\Str::slug($category->name);
                    $img = $ws->categoryImageUrl($category) ?: ($catFallbacks[$iconMeta['key']] ?? $catFallbacks['phone']);
                    $tagline = trim((string) ($category->description ?? ''))
                        ?: ($catTaglines[$slug] ?? ($catTaglines[$iconMeta['key']] ?? 'Explore the collection.'));
                    $count = (int) ($category->products_count ?? 0);
                    $countLabel = $category->product_count_label
                        ?: ($count > 0 ? ($count >= 100 ? '100+ products' : $count.' products') : 'Shop now');
                    $theme = $catThemes[$i % count($catThemes)];
                    $url = route('website.category', $category->slug ?? $category->id);
                @endphp
                <article
                    class="mg-cover-card {{ $theme }}"
                    data-cover-i="{{ $i }}"
                    :class="{ 'is-active': active === {{ $i }}, 'is-dragging': dragging }"
                    :style="styleFor({{ $i }})"
                    @click="active === {{ $i }} ? openCategory(@js($url)) : go({{ $i }})"
                    role="link"
                    tabindex="0"
                    @keydown.enter.prevent="openCategory(@js($url))"
                    aria-label="{{ $category->name }}"
                >
                    <div class="mg-cover-media">
                        <img src="{{ $img }}" alt="{{ $category->name }}" class="mg-cover-img" loading="lazy" decoding="async" draggable="false">
                    </div>
                    <div class="mg-cover-veil" aria-hidden="true"></div>
                    <div class="mg-cover-shine" aria-hidden="true"></div>
                    <div class="mg-cover-chip">
                        <span class="mg-cover-chip-icon" style="--cat-color: {{ $iconMeta['color'] }};">
                            @include('website.partials.category-icon-svg', ['icon' => $iconMeta['key'], 'class' => 'mg-cover-chip-svg'])
                        </span>
                        <span>{{ $countLabel }}</span>
                    </div>
                    <div class="mg-cover-foot">
                        <div class="mg-cover-foot-text">
                            <h3 class="mg-cover-name">{{ $category->name }}</h3>
                            <p class="mg-cover-tagline">{{ $tagline }}</p>
                        </div>
                    </div>
                    <div class="mg-cover-pager" x-show="active === {{ $i }}" x-cloak>
                        <span x-text="String(active + 1).padStart(2,'0')"></span>
                        <i aria-hidden="true"></i>
                        <span x-text="String(total).padStart(2,'0')"></span>
                    </div>
                </article>
            @endforeach
        </div>
        <p class="mg-cover-hint" aria-hidden="true">Drag or swipe</p>
    </div>
</section>
@endif

{{-- Flash Sale --}}
@if($flashSaleProducts->isNotEmpty())
<section class="tn-flash">
    <div class="tn-container">
        <div class="tn-flash-head">
            @php $homeCopy = data_get($settings, 'home_copy') ?: []; @endphp
            <div class="tn-flash-copy">
                <p class="tn-flash-eyebrow">{{ $homeCopy['flash_eyebrow'] ?? 'Limited time' }}</p>
                <div class="tn-flash-title-row">
                    <h2 class="tn-flash-title">{{ $homeCopy['flash_title'] ?? 'Flash' }} <span>{{ $homeCopy['flash_title_accent'] ?? 'Sale' }}</span></h2>
                    <span class="tn-flash-live">Live</span>
                </div>
                <p class="tn-flash-sub">{{ $homeCopy['flash_subtitle'] ?? 'Today’s best prices on selected gadgets — ends when the timer hits zero.' }}</p>
                <div class="tn-countdown tn-countdown--flash" x-data="{
                    h:0,m:0,s:0,
                    end: {{ ($flashSaleEndsAt ?? null) ? ((int) $flashSaleEndsAt->timestamp * 1000) : 'null' }},
                    tick(){
                        const target = this.end ?? new Date().setHours(23,59,59,999);
                        const d = Math.max(0, target - Date.now());
                        this.h = Math.floor(d/3600000);
                        this.m = Math.floor((d%3600000)/60000);
                        this.s = Math.floor((d%60000)/1000);
                    }
                }" x-init="tick(); setInterval(()=>tick(),1000)">
                    <span class="tn-countdown-label">Ends in</span>
                    <span class="tn-countdown-box"><strong x-text="String(h).padStart(2,'0')">00</strong><small>Hrs</small></span>
                    <span class="tn-countdown-sep">:</span>
                    <span class="tn-countdown-box"><strong x-text="String(m).padStart(2,'0')">00</strong><small>Min</small></span>
                    <span class="tn-countdown-sep">:</span>
                    <span class="tn-countdown-box"><strong x-text="String(s).padStart(2,'0')">00</strong><small>Sec</small></span>
                </div>
            </div>
            <a href="{{ route('website.shop', ['filter' => 'deals']) }}" class="tn-flash-link">View all deals <span aria-hidden="true">→</span></a>
        </div>

        <div class="tn-flash-grid">
            @foreach($flashSaleProducts as $product)
                @include('website.partials.tn-product-card', ['product' => $product, 'flash' => true])
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Mid promo marquee (CMS → Landing Page → Mid promo banner) --}}
@if(($midPromoBanners ?? collect())->isNotEmpty())
@php
    $midPromoList = $midPromoBanners->values();
@endphp
<section class="mg-midpromo" aria-label="Featured promotions">
    <div class="tn-container">
        <div class="mg-midpromo-marquee" @mouseenter="$el.classList.add('is-paused')" @mouseleave="$el.classList.remove('is-paused')">
            <div class="mg-midpromo-track">
                @foreach([0, 1] as $loopCopy)
                    @foreach($midPromoList as $idx => $banner)
                        @php
                            $isLight = ($banner->theme ?? 'dark') === 'light';
                            $url = $banner->button_url ?: route('website.shop');
                            $sub = trim((string) ($banner->subtitle ?? ''));
                            $img = $banner->image_path ? public_storage_url($banner->image_path) : null;
                        @endphp
                        <article class="mg-midpromo-card {{ $isLight ? 'is-light' : 'is-dark' }}" @if($loopCopy === 1) aria-hidden="true" @endif>
                            <a href="{{ $url }}" class="mg-midpromo-link" @if($loopCopy === 1) tabindex="-1" @endif aria-label="{{ $banner->title }}">
                                <div class="mg-midpromo-media" aria-hidden="true">
                                    @if($img)
                                        <img src="{{ $img }}" alt="" class="mg-midpromo-img" loading="{{ $loopCopy === 0 && $idx < 3 ? 'eager' : 'lazy' }}">
                                    @else
                                        <div class="mg-midpromo-fallback"></div>
                                    @endif
                                </div>
                                <div class="mg-midpromo-panel">
                                    @if($banner->badge_text || $banner->discount_badge)
                                        <div class="mg-midpromo-meta">
                                            @if($banner->badge_text)
                                                <span class="mg-midpromo-badge">{{ $banner->badge_text }}</span>
                                            @endif
                                            @if($banner->discount_badge)
                                                <span class="mg-midpromo-offer">{{ $banner->discount_badge }}</span>
                                            @endif
                                        </div>
                                    @endif
                                    <h3 class="mg-midpromo-title">{{ $banner->title }}</h3>
                                    @if($sub !== '')
                                        @php
                                            $subHtml = e($sub);
                                            if (!empty($banner->highlight_text)) {
                                                $hi = e($banner->highlight_text);
                                                $subHtml = str_ireplace($hi, '<em>'.$hi.'</em>', $subHtml);
                                            }
                                        @endphp
                                        <p class="mg-midpromo-sub">{!! $subHtml !!}</p>
                                    @endif
                                    @if(!empty($banner->highlight_text) && $sub === '')
                                        <p class="mg-midpromo-sub"><em>{{ $banner->highlight_text }}</em></p>
                                    @endif
                                    <div class="mg-midpromo-foot">
                                        <span class="mg-midpromo-cta">
                                            {{ $banner->button_text ?: 'Shop now' }}
                                        </span>
                                        @if($banner->price_from)
                                            <span class="mg-midpromo-price">From {{ $ws->formatPrice($banner->price_from, $settings) }}</span>
                                        @endif
                                    </div>
                                </div>
                            </a>
                        </article>
                    @endforeach
                @endforeach
            </div>
        </div>
    </div>
</section>
@endif

{{-- New Arrivals --}}
@if(($newArrivals ?? collect())->isNotEmpty())
<section class="tn-section tn-section-new">
    <div class="tn-container">
        <div class="tn-section-head tn-section-head--branded">
            @php $homeCopy = data_get($settings, 'home_copy') ?: []; @endphp
            <div class="tn-section-copy">
                <p class="tn-section-eyebrow">{{ $homeCopy['new_eyebrow'] ?? 'Just landed' }}</p>
                <h2 class="tn-section-title">{{ $homeCopy['new_title'] ?? 'New' }} <span>{{ $homeCopy['new_title_accent'] ?? 'Arrivals' }}</span></h2>
                <p class="tn-section-sub">{{ $homeCopy['new_subtitle'] ?? 'Fresh gadgets added to the store — explore what’s new this week.' }}</p>
            </div>
            <a href="{{ route('website.shop', ['filter' => 'new']) }}" class="tn-section-link">View all new arrivals <span aria-hidden="true">→</span></a>
        </div>
        <div class="tn-flash-grid">
            @foreach($newArrivals as $product)
                @include('website.partials.tn-product-card', ['product' => $product])
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- What's Trending Now --}}
@if($trendingProducts->isNotEmpty())
<section class="tn-trending" aria-labelledby="tn-trending-heading">
    <div class="tn-container">
        <div class="tn-trending-head">
            @php $homeCopy = data_get($settings, 'home_copy') ?: []; @endphp
            <div class="tn-trending-copy">
                <p class="tn-trending-kicker">{{ $homeCopy['trending_eyebrow'] ?? 'Most loved' }}</p>
                <h2 id="tn-trending-heading" class="tn-trending-title">{{ $homeCopy['trending_title'] ?? "What's" }} <span>{{ $homeCopy['trending_title_accent'] ?? 'Trending' }}</span></h2>
                <p class="tn-trending-sub">{{ $homeCopy['trending_subtitle'] ?? 'Customer favorites — grab them before they sell out.' }}</p>
            </div>
            <a href="{{ route('website.shop', ['filter' => 'bestsellers']) }}" class="tn-trending-all">
                View all <span aria-hidden="true">→</span>
            </a>
        </div>

        <div class="tn-trending-grid">
            @foreach($trendingProducts as $product)
                @include('website.partials.tn-trending-card', [
                    'product' => $product,
                    'trendingRank' => $loop->iteration,
                ])
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Brands We Carry — continuous slow marquee --}}
@if($brands->isNotEmpty())
@php
    $brandTaglines = [
        'apple' => 'iPhone · iPad · Mac · Watch',
        'samsung' => 'Galaxy · Tablets · Watches',
        'xiaomi' => 'Smartphones · IoT · Accessories',
        'oneplus' => 'Phones · Buds · Accessories',
        'realme' => 'Smartphones · AIoT · Accessories',
        'jbl' => 'Audio · Headphones · Speakers',
        'anker' => 'Chargers · Power Banks · Cables',
        'logitech' => 'Accessories · Keyboards · Mice',
        'baseus' => 'Chargers · Cables · Car Accessories',
        'boat' => 'Audio · Wearables · Accessories',
        'sony' => 'Audio · Cameras · Gaming',
        'bose' => 'Headphones · Speakers · Audio',
        'dell' => 'Laptops · Monitors · PCs',
        'hp' => 'Laptops · Printers · PCs',
        'asus' => 'Laptops · Gaming · Components',
        'lenovo' => 'Laptops · Tablets · PCs',
        'acer' => 'Laptops · Monitors · PCs',
        'canon' => 'Cameras · Lenses · Printers',
        'gopro' => 'Action Cams · Mounts · Accessories',
        'google' => 'Pixel · Nest · Accessories',
        'razer' => 'Gaming · Keyboards · Mice',
        'nothing' => 'Phones · Audio · Accessories',
        'microsoft' => 'Surface · Accessories · Software',
    ];
    $partnerBrands = $brands->values();
@endphp
<section class="tn-brands" aria-labelledby="tn-brands-heading">
    <div class="tn-container">
            <div class="tn-brands-head">
            @php $homeCopy = data_get($settings, 'home_copy') ?: []; @endphp
            <div class="tn-brands-copy">
                <p class="tn-brands-eyebrow">{{ $homeCopy['brands_eyebrow'] ?? 'Partners' }}</p>
                <h2 id="tn-brands-heading" class="tn-brands-title">{{ $homeCopy['brands_title'] ?? 'Brands We' }} <span>{{ $homeCopy['brands_title_accent'] ?? 'Carry' }}</span></h2>
                <p class="tn-brands-sub">{{ $homeCopy['brands_subtitle'] ?? 'Trusted names in gadgets — quality, performance, and innovation.' }}</p>
            </div>
        </div>
            </div>

    <div class="tn-brands-marquee" aria-label="Brand partners">
        <div class="tn-brands-track">
            @foreach([0, 1] as $loopPass)
                @foreach($partnerBrands as $brand)
                    @php
                        $brandSlug = \Illuminate\Support\Str::slug($brand->name);
                        $logoUrl = $brand->logo_url
                            ?: ($brand->logo_path ? public_storage_url($brand->logo_path) : null);
                        $tagline = trim((string) ($brand->tagline ?? ''))
                            ?: ($brandTaglines[$brandSlug] ?? 'Gadgets · Accessories');
                    @endphp
                    <a href="{{ route('website.brand', $brandSlug) }}"
                       class="tn-brand-card"
                       title="Shop {{ $brand->name }}"
                       tabindex="{{ $loopPass === 0 ? 0 : -1 }}"
                       aria-hidden="{{ $loopPass === 0 ? 'false' : 'true' }}">
                        <span class="tn-brand-logo-frame">
                            @if($logoUrl)
                                <img src="{{ $logoUrl }}"
                                     alt="{{ $brand->name }}"
                                     class="tn-brand-logo"
                                     loading="lazy"
                                     decoding="async"
                                     onerror="this.classList.add('is-broken'); this.nextElementSibling?.classList.add('is-visible');">
                                <span class="tn-brand-fallback">{{ $brand->name }}</span>
                            @else
                                <span class="tn-brand-fallback is-visible">{{ $brand->name }}</span>
                            @endif
                        </span>
                        <span class="tn-brand-name">{{ $brand->name }}</span>
                        <span class="tn-brand-cats">{{ $tagline }}</span>
                    </a>
                @endforeach
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Deals You'll Love (CMS → Landing Page promo banners) --}}
@if($promoBanners->isNotEmpty())
@php
    $dealsKicker = data_get($settings, 'deals_kicker') ?: 'This week';
    $dealsTitle = data_get($settings, 'deals_title') ?: 'Featured';
    $dealsAccent = data_get($settings, 'deals_title_accent') ?: 'Deals';
    $dealsSub = data_get($settings, 'deals_subtitle') ?: 'Premium gadgets at carefully chosen prices.';
@endphp
<section class="tn-deals" aria-labelledby="tn-deals-heading">
    <div class="tn-deals-glow" aria-hidden="true"></div>
    <div class="tn-container">
        <header class="tn-deals-head">
            <p class="tn-deals-kicker">
                <span class="tn-deals-kicker-dot" aria-hidden="true"></span>
                {{ $dealsKicker }}
            </p>
            <h2 id="tn-deals-heading" class="tn-deals-title">
                {{ $dealsTitle }} <span>{{ $dealsAccent }}</span>
            </h2>
            <p class="tn-deals-sub">{{ $dealsSub }}</p>
        </header>

        <div class="tn-deals-grid">
            @foreach($promoBanners->take(2) as $banner)
                @php
                    $isLight = $banner->theme === 'light';
                    $url = $banner->button_url ?: route('website.shop');
                    $sub = trim((string) ($banner->subtitle ?? ''));
                @endphp
                <article class="tn-deal {{ $isLight ? 'is-light' : 'is-dark' }}">
                    <a href="{{ $url }}" class="tn-deal-link" aria-label="{{ $banner->title }}">
                    @if($banner->discount_badge)
                        <span class="tn-deal-disc">{{ $banner->discount_badge }}</span>
                    @endif

                    <div class="tn-deal-body">
                        @if($banner->badge_text)
                                <span class="tn-deal-badge">{{ $banner->badge_text }}</span>
                        @endif

                        <div class="tn-deal-copy">
                                <h3 class="tn-deal-name">{{ $banner->title }}</h3>
                                @if($sub !== '')
                                    @php
                                        $subHtml = e($sub);
                                        if (!empty($banner->highlight_text)) {
                                            $hi = e($banner->highlight_text);
                                            $subHtml = str_ireplace($hi, '<em>'.$hi.'</em>', $subHtml);
                                        }
                                    @endphp
                                    <p class="tn-deal-offer">{!! $subHtml !!}</p>
                                @elseif(!empty($banner->highlight_text))
                                    <p class="tn-deal-offer"><em>{{ $banner->highlight_text }}</em></p>
                                @endif
                            @if($banner->price_from)
                                    <p class="tn-deal-price">
                                    <span>From</span>
                                    <strong>{{ $ws->formatPrice($banner->price_from, $settings) }}</strong>
                                    </p>
                            @endif
                        </div>

                            <span class="tn-deal-cta">
                                {{ $banner->button_text ?: 'Shop now' }}
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6"/></svg>
                            </span>
                    </div>

                        <div class="tn-deal-media" aria-hidden="true">
                            <span class="tn-deal-orb"></span>
                            <span class="tn-deal-ring"></span>
                        @if($banner->image_path)
                                <img src="{{ public_storage_url($banner->image_path) }}" alt="" class="tn-deal-img" loading="lazy">
                        @endif
                    </div>
                    </a>
                </article>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Testimonials (CMS → Reviews) --}}
@if(($featuredReviews ?? collect())->isNotEmpty())
<section class="tn-section">
    <div class="tn-container">
        @php $homeCopy = data_get($settings, 'home_copy') ?: []; @endphp
        <div class="tn-section-head tn-section-head-center">
            <h2 class="tn-section-title">{{ $homeCopy['reviews_title'] ?? 'What Our Customers Say' }}</h2>
            <p class="tn-section-desc">{{ $homeCopy['reviews_subtitle'] ?? 'Real feedback from shoppers who love our products and service.' }}</p>
        </div>
        <div class="tn-review-grid">
            @foreach($featuredReviews->take(3) as $review)
                <div class="tn-review-card">
                    <div class="tn-review-stars">
                        @for($i = 1; $i <= 5; $i++)
                            <span class="{{ $i <= (int) $review->rating ? 'filled' : '' }}">&#9733;</span>
                        @endfor
                    </div>
                    <p class="tn-review-body">&ldquo;{{ $review->body }}&rdquo;</p>
                    <div class="tn-review-author">
                        @if($review->avatar_path)
                            <img src="{{ public_storage_url($review->avatar_path) }}" alt="" class="tn-review-avatar">
                        @else
                            <div class="tn-review-avatar tn-review-avatar--letter">{{ strtoupper(mb_substr($review->customer_name, 0, 1)) }}</div>
                        @endif
                        <div>
                            <p class="tn-review-name">{{ $review->customer_name }}</p>
                            @if($review->customer_title)
                                <p class="tn-review-role">{{ $review->customer_title }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Latest from the Blog --}}
@if(($latestBlogs ?? collect())->isNotEmpty())
<section class="tn-blog" aria-labelledby="tn-blog-heading">
    <div class="tn-container">
        @php $homeCopy = data_get($settings, 'home_copy') ?: []; @endphp
        <div class="tn-blog-head">
            <p class="tn-blog-kicker">
                <span class="tn-blog-kicker-line" aria-hidden="true"></span>
                {{ $homeCopy['blog_eyebrow'] ?? 'Latest from the Blog' }}
                <span class="tn-blog-kicker-line" aria-hidden="true"></span>
            </p>
            <div class="tn-blog-head-main">
                <div class="tn-blog-head-copy">
                    <h2 id="tn-blog-heading" class="tn-blog-heading">{{ $homeCopy['blog_title'] ?? 'Latest from the' }} <em>{{ $homeCopy['blog_title_accent'] ?? 'Blog' }}</em></h2>
                    <p class="tn-blog-sub">{{ $homeCopy['blog_subtitle'] ?? 'Stay ahead with gadget tips, reviews, guides and tech insights.' }}</p>
                </div>
                <a href="{{ route('website.blogs') }}" class="tn-blog-all">
                    View All Articles
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6l6 6-6 6"/></svg>
                </a>
            </div>
        </div>

        <div class="tn-blog-grid">
            @foreach($latestBlogs as $post)
                @php
                    $excerpt = trim((string) ($post->excerpt ?: \Illuminate\Support\Str::limit(strip_tags((string) $post->body), 120)));
                @endphp
                <article class="tn-blog-card">
                    <a href="{{ route('website.blog', $post->slug) }}" class="tn-blog-media" tabindex="-1" aria-hidden="true">
                        <img src="{{ $post->coverUrl() }}" alt="" class="tn-blog-img" loading="lazy" decoding="async">
                    </a>
                    <div class="tn-blog-body">
                        @if($post->category)
                            <span class="tn-blog-tag">{{ $post->category->name }}</span>
                        @endif
                        <h3 class="tn-blog-title">
                            <a href="{{ route('website.blog', $post->slug) }}">{{ $post->title }}</a>
                        </h3>
                        <p class="tn-blog-meta">
                            <span>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path stroke-linecap="round" d="M8 3v4M16 3v4M3 10h18"/></svg>
                                {{ optional($post->published_at)->format('M j, Y') ?: 'Recently' }}
                            </span>
                            <span>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 7v5l3 2"/></svg>
                                {{ $post->readingTimeLabel() }}
                            </span>
                        </p>
                        @if($excerpt !== '')
                            <p class="tn-blog-excerpt">{{ $excerpt }}</p>
                        @else
                            <p class="tn-blog-excerpt tn-blog-excerpt--empty" aria-hidden="true">&nbsp;</p>
                        @endif
                        <a href="{{ route('website.blog', $post->slug) }}" class="tn-blog-read">
                            Read Article
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6l6 6-6 6"/></svg>
                        </a>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
@endif

@endsection
