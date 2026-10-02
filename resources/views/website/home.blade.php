@extends('website.layout')
@php
    $ws = app(\App\Services\WebsiteService::class);
    $storeName = $settings->store_name ?? config('app.name', 'Bynnas Social');
    $homeCopy = collect(data_get($settings, 'home_copy') ?: [])
        ->map(fn ($v) => is_string($v) ? str_replace('{store}', $storeName, $v) : $v)
        ->all();
@endphp

@section('content')

{{-- Hero: headline + CTAs + trust badges, CMS slide photos rotate in the rounded media panel --}}
@php
    $heroTitle = $homeCopy['hero_title'] ?? '';
    $heroSubtitle = $homeCopy['hero_subtitle'] ?? '';
    $heroBadge = $homeCopy['hero_badge'] ?? '';
    $heroTrust = [
        ['lottie' => 'sparkles', 'title' => $homeCopy['hero_trust_1_title'] ?? '', 'sub' => $homeCopy['hero_trust_1_sub'] ?? ''],
        ['lottie' => 'check', 'title' => $homeCopy['hero_trust_2_title'] ?? '', 'sub' => $homeCopy['hero_trust_2_sub'] ?? ''],
        ['lottie' => 'heart', 'title' => $homeCopy['hero_trust_3_title'] ?? '', 'sub' => $homeCopy['hero_trust_3_sub'] ?? ''],
    ];
    $heroTrust = array_values(array_filter($heroTrust, fn ($t) => filled($t['title'])));
    $heroImages = $heroSlides->filter(fn ($s) => filled($s->image_path))->map(function ($slide) {
        $src = public_storage_url($slide->image_path);
        $full = public_storage_path($slide->image_path);

        return (object) [
            'src' => $src.'?v='.(is_file($full) ? filemtime($full) : time()),
            'title' => $slide->title,
            'url' => $slide->button_url ?: route('website.shop'),
            'cta' => $slide->button_text ?: 'Shop now',
        ];
    })->values();
@endphp
<section class="bb-hero">
        <div class="bb-hero-panel"
             x-data="{
                slide: 0,
                total: {{ $heroImages->count() }},
                timer: null,
                go(i) { this.slide = ((i % this.total) + this.total) % this.total; this.arm(); },
                arm() {
                    clearInterval(this.timer);
                    if (this.total > 1) this.timer = setInterval(() => { this.slide = (this.slide + 1) % this.total; }, 5500);
                }
             }"
             x-init="arm()">
            <div class="bb-hero-media">
                @foreach($heroImages as $i => $image)
                    <a href="{{ $image->url }}"
                       class="bb-hero-slide{{ $i === 0 ? ' is-active' : '' }}"
                       :class="{ 'is-active': slide === {{ $i }} }"
                       aria-label="{{ $image->title }}"
                       x-bind:tabindex="slide === {{ $i }} ? 0 : -1">
                        <img src="{{ $image->src }}" alt="{{ $image->title }}" class="bb-fill" decoding="async" @if($i === 0) fetchpriority="high" @else loading="lazy" @endif>
                    </a>
                @endforeach
            </div>

            <div class="tn-container bb-hero-inner">
                <div class="bb-hero-copy">
                    <h1 class="bb-hero-title">{{ $heroTitle }}</h1>
                    <p class="bb-hero-sub">{{ $heroSubtitle }}</p>
                    <div class="bb-hero-actions">
                        <a href="{{ route('website.shop') }}" class="bb-btn bb-btn--coral">
                            Shop Now
                            <span class="bb-btn-arrow">@include('website.partials.bb-icon', ['name' => 'arrow-right'])</span>
                        </a>
                        <a href="{{ route('website.shop', ['filter' => 'new']) }}" class="bb-btn bb-btn--ghost">Explore Collection</a>
                    </div>
                    @if($heroTrust)
                    <ul class="bb-hero-trust">
                        @foreach($heroTrust as $trust)
                            <li>
                                <span class="bb-hero-trust-ico">@include('website.partials.lottie', ['name' => $trust['lottie']])</span>
                                <span>
                                    <strong>{{ $trust['title'] }}</strong>
                                    <small>{{ $trust['sub'] }}</small>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                    @endif
                </div>

                @if(filled($heroBadge))
                <span class="bb-hero-badge" aria-hidden="true">
                    <span class="bb-hero-badge-peek">@include('website.partials.lottie', ['name' => 'chick'])</span>
                    <span>{{ $heroBadge }}</span>
                </span>
                @endif
            </div>

            @if($heroImages->count() > 1)
                <div class="bb-hero-dots" role="tablist" aria-label="Hero slides">
                    @foreach($heroImages as $i => $image)
                        <button type="button" class="bb-hero-dot" :class="{ 'is-active': slide === {{ $i }} }" @click="go({{ $i }})" aria-label="Show slide {{ $i + 1 }}"></button>
                    @endforeach
                </div>
            @endif

            <span class="bb-hero-deco bb-hero-deco--balloon">@include('website.partials.lottie', ['name' => 'balloon'])</span>
            <span class="bb-hero-deco bb-hero-deco--spark">@include('website.partials.lottie', ['name' => 'sparkles'])</span>
            <span class="bb-hero-deco bb-hero-deco--star">@include('website.partials.lottie', ['name' => 'star'])</span>
            <span class="bb-hero-wave" aria-hidden="true"></span>
        </div>
</section>

{{-- Hero side cards (CMS → Landing Page → Hero side cards) --}}
@if(($heroSideCards ?? collect())->isNotEmpty())
<section class="bb-section bb-hero-cards">
    <div class="tn-container">
        <div class="bb-promo-pair">
            @foreach($heroSideCards as $banner)
                @include('website.partials.promo-card', ['banner' => $banner, 'variant' => 'is-wide'])
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Shop by Categories --}}
@if($categories->isNotEmpty())
@php
    $catFallback = $ws->placeholderImageUrl();
    $catTints = ['is-peach', 'is-lavender', 'is-sage', 'is-butter', 'is-rose', 'is-sky'];
@endphp
<section class="bb-section bb-cats">
    <div class="tn-container">
        <header class="bb-head">
            <span class="bb-head-mark">@include('website.partials.lottie', ['name' => 'rainbow'])</span>
            <h2 class="bb-head-title">{{ $homeCopy['categories_title'] ?? '' }} {{ $homeCopy['categories_title_accent'] ?? '' }}</h2>
            <p class="bb-head-sub">{{ $homeCopy['categories_subtitle'] ?? '' }}</p>
        </header>
        <div class="bb-cat-grid">
            @foreach($categories->take(6) as $i => $category)
                @php
                    $img = $ws->categoryImageUrl($category) ?: $catFallback;
                    $count = (int) ($category->products_count ?? 0);
                    $countLabel = $category->product_count_label
                        ?: ($count > 0 ? $count.'+ products' : 'Shop now');
                @endphp
                <a href="{{ route('website.category', $category->slug ?? $category->id) }}" class="bb-cat-card {{ $catTints[$i % count($catTints)] }}">
                    <span class="bb-cat-media"><img src="{{ $img }}" alt="{{ $category->name }}" class="bb-fill" loading="lazy" decoding="async"></span>
                    <span class="bb-cat-name">{{ $category->name }}</span>
                    <span class="bb-cat-foot">
                        <span class="bb-cat-count">{{ $countLabel }}</span>
                        <span class="bb-cat-go">@include('website.partials.bb-icon', ['name' => 'arrow-right'])</span>
                    </span>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Flash Sale: biggest live discounts + countdown to the earliest sale end --}}
@if(($flashSaleProducts ?? collect())->isNotEmpty())
<section class="bb-section bb-flash-wrap">
    <div class="tn-container">
        <div class="bb-flash">
            <div class="bb-flash-top">
                <div class="bb-flash-title">
                    <span class="bb-flash-fire">@include('website.partials.lottie', ['name' => 'fire'])</span>
                    <div>
                        <h2 class="bb-flash-heading">{{ $homeCopy['flash_title'] ?? '' }} {{ $homeCopy['flash_title_accent'] ?? '' }}</h2>
                        <p class="bb-flash-sub">{{ $homeCopy['flash_subtitle'] ?? '' }}</p>
                    </div>
                </div>
                @if($flashSaleEndsAt)
                    <div class="bb-countdown"
                         x-data="{
                            end: {{ \Illuminate\Support\Carbon::parse($flashSaleEndsAt)->getTimestamp() * 1000 }},
                            d: 0, h: 0, m: 0, s: 0,
                            tick() {
                                const t = Math.max(0, this.end - Date.now());
                                this.d = Math.floor(t / 864e5);
                                this.h = Math.floor(t / 36e5) % 24;
                                this.m = Math.floor(t / 6e4) % 60;
                                this.s = Math.floor(t / 1e3) % 60;
                            },
                            pad(n) { return String(n).padStart(2, '0'); }
                         }"
                         x-init="tick(); setInterval(() => tick(), 1000)"
                         role="timer" aria-label="Flash sale ends {{ \Illuminate\Support\Carbon::parse($flashSaleEndsAt)->toDayDateTimeString() }}">
                        <span class="bb-countdown-clock">@include('website.partials.lottie', ['name' => 'alarm-clock'])</span>
                        <span class="bb-countdown-label">Ends in</span>
                        <span class="bb-countdown-box" x-show="d > 0"><b x-text="pad(d)">00</b><small>Days</small></span>
                        <span class="bb-countdown-box"><b x-text="pad(h)">00</b><small>Hrs</small></span>
                        <span class="bb-countdown-box"><b x-text="pad(m)">00</b><small>Min</small></span>
                        <span class="bb-countdown-box"><b x-text="pad(s)">00</b><small>Sec</small></span>
                    </div>
                @endif
            </div>
            <div class="bb-rail-wrap" x-data="{
                over: false,
                check() { const r = this.$refs.rail; this.over = r.scrollWidth > r.clientWidth + 4; },
                scroll(d) { const r = this.$refs.rail; r.scrollBy({ left: d * r.clientWidth * 0.8, behavior: 'smooth' }); }
             }"
             x-init="$nextTick(() => check()); window.addEventListener('resize', () => check())">
                <button type="button" x-show="over" x-cloak class="bb-rail-arrow is-prev" @click="scroll(-1)" aria-label="Previous deals">@include('website.partials.bb-icon', ['name' => 'chevron-left'])</button>
                <div class="bb-rail" x-ref="rail">
                    @foreach($flashSaleProducts as $product)
                        <div class="bb-rail-item">@include('website.partials.tn-product-card', ['product' => $product, 'flash' => true])</div>
                    @endforeach
                </div>
                <button type="button" x-show="over" x-cloak class="bb-rail-arrow is-next" @click="scroll(1)" aria-label="Next deals">@include('website.partials.bb-icon', ['name' => 'chevron-right'])</button>
            </div>
            <div class="bb-section-cta">
                <a href="{{ route('website.shop', ['filter' => 'deals']) }}" class="bb-btn bb-btn--coral">
                    Shop all deals
                    <span class="bb-btn-arrow">@include('website.partials.bb-icon', ['name' => 'arrow-right'])</span>
                </a>
            </div>
        </div>
    </div>
</section>
@endif

{{-- Deal banners (CMS → Landing Page → Promo banners + deals heading) --}}
@if(($promoBanners ?? collect())->isNotEmpty())
<section class="bb-section bb-deals">
    <div class="tn-container">
        <header class="bb-head">
            @if(filled($settings->deals_kicker ?? null))
                <span class="bb-head-kicker">{{ $settings->deals_kicker }}</span>
            @endif
            <h2 class="bb-head-title">{{ $settings->deals_title }} <span class="bb-head-accent">{{ $settings->deals_title_accent }}</span></h2>
            @if(filled($settings->deals_subtitle ?? null))
                <p class="bb-head-sub">{{ $settings->deals_subtitle }}</p>
            @endif
        </header>
        <div class="bb-promo-grid" style="--bb-promo-count: {{ min(4, $promoBanners->count()) }}">
            @foreach($promoBanners as $banner)
                @include('website.partials.promo-card', ['banner' => $banner])
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Featured Products (admin "Featured on homepage"; best sellers until any are ticked) --}}
@if(($featuredProducts ?? collect())->isNotEmpty())
<section class="bb-section bb-featured">
    <div class="tn-container">
        <header class="bb-head">
            <span class="bb-head-mark">@include('website.partials.lottie', ['name' => 'star'])</span>
            <h2 class="bb-head-title">{{ $homeCopy['featured_title'] ?? '' }}</h2>
            <p class="bb-head-sub">{{ $homeCopy['featured_subtitle'] ?? '' }}</p>
        </header>
        <div class="bb-rail-wrap" x-data="{
                over: false,
                check() { const r = this.$refs.rail; this.over = r.scrollWidth > r.clientWidth + 4; },
                scroll(d) { const r = this.$refs.rail; r.scrollBy({ left: d * r.clientWidth * 0.8, behavior: 'smooth' }); }
             }"
             x-init="$nextTick(() => check()); window.addEventListener('resize', () => check())">
            <button type="button" x-show="over" x-cloak class="bb-rail-arrow is-prev" @click="scroll(-1)" aria-label="Previous products">@include('website.partials.bb-icon', ['name' => 'chevron-left'])</button>
            <div class="bb-rail" x-ref="rail">
                @foreach($featuredProducts as $product)
                    <div class="bb-rail-item">@include('website.partials.tn-product-card', ['product' => $product])</div>
                @endforeach
            </div>
            <button type="button" x-show="over" x-cloak class="bb-rail-arrow is-next" @click="scroll(1)" aria-label="Next products">@include('website.partials.bb-icon', ['name' => 'chevron-right'])</button>
        </div>
    </div>
</section>
@endif

{{-- Combo Deals: products flagged as combo packs in admin --}}
@if(($comboProducts ?? collect())->isNotEmpty())
<section class="bb-section bb-combos">
    <div class="tn-container">
        <div class="bb-combo-panel">
            <header class="bb-head">
                <span class="bb-head-mark">@include('website.partials.lottie', ['name' => 'gift'])</span>
                <h2 class="bb-head-title">{{ $homeCopy['combo_title'] ?? '' }}</h2>
                <p class="bb-head-sub">{{ $homeCopy['combo_subtitle'] ?? '' }}</p>
            </header>
            <div class="bb-product-grid bb-product-grid--combo" style="--bb-grid-count: {{ $comboProducts->count() }}">
                @foreach($comboProducts as $product)
                    @include('website.partials.tn-product-card', ['product' => $product])
                @endforeach
            </div>
            <div class="bb-section-cta">
                <a href="{{ route('website.shop', ['filter' => 'combo']) }}" class="bb-btn bb-btn--ghost">See all combos</a>
            </div>
            <span class="bb-combo-deco bb-combo-deco--balloon">@include('website.partials.lottie', ['name' => 'balloon'])</span>
            <span class="bb-combo-deco bb-combo-deco--party">@include('website.partials.lottie', ['name' => 'party'])</span>
        </div>
    </div>
</section>
@endif

{{-- Mid promo strip (CMS → Landing Page → Mid promo banner) --}}
@if(($midPromoBanners ?? collect())->isNotEmpty())
<section class="bb-section bb-mid-promo">
    <div class="tn-container">
        <div class="bb-rail-wrap" x-data="{
                over: false,
                check() { const r = this.$refs.rail; this.over = r.scrollWidth > r.clientWidth + 4; },
                scroll(d) { const r = this.$refs.rail; r.scrollBy({ left: d * r.clientWidth * 0.8, behavior: 'smooth' }); }
             }"
             x-init="$nextTick(() => check()); window.addEventListener('resize', () => check())">
            <button type="button" x-show="over" x-cloak class="bb-rail-arrow is-prev" @click="scroll(-1)" aria-label="Previous offers">@include('website.partials.bb-icon', ['name' => 'chevron-left'])</button>
            <div class="bb-promo-rail" x-ref="rail">
                @foreach($midPromoBanners as $banner)
                    @include('website.partials.promo-card', ['banner' => $banner, 'variant' => 'is-wide'])
                @endforeach
            </div>
            <button type="button" x-show="over" x-cloak class="bb-rail-arrow is-next" @click="scroll(1)" aria-label="Next offers">@include('website.partials.bb-icon', ['name' => 'chevron-right'])</button>
        </div>
    </div>
</section>
@endif

{{-- Why choose us (CMS → Landing Page features) --}}
@if($features->isNotEmpty())
@php
    $whyImage = $categories->map(fn ($c) => $ws->categoryImageUrl($c))->filter()->first();
    $whyLottie = [
        'truck' => 'rocket', 'shipping' => 'rocket', 'return' => 'package', 'lock' => 'lock',
        'shield' => 'check', 'warranty' => 'check', 'payment' => 'money', 'support' => 'hug', 'chat' => 'hug',
    ];
@endphp
<section class="bb-section bb-why-wrap">
    <div class="tn-container">
        <div class="bb-why">
            <div class="bb-why-copy">
                <span class="bb-head-mark bb-why-mark">@include('website.partials.lottie', ['name' => 'hug'])</span>
                <h2 class="bb-head-title">{{ $homeCopy['why_title'] ?? '' }}</h2>
                <p class="bb-head-sub">{{ $homeCopy['why_subtitle'] ?? '' }}</p>
                <div class="bb-why-grid">
                    @foreach($features as $feature)
                        <div class="bb-why-item">
                            <span class="bb-why-ico">@include('website.partials.lottie', ['name' => $whyLottie[$feature->icon] ?? 'sparkles'])</span>
                            <div>
                                <p class="bb-why-title">{{ $feature->title }}</p>
                                @if($feature->subtitle)<p class="bb-why-sub">{{ $feature->subtitle }}</p>@endif
                            </div>
                        </div>
                    @endforeach
                </div>
                @if(filled($settings->trusted_by_text ?? null))
                    <p class="bb-why-trusted">@include('website.partials.bb-icon', ['name' => 'shield']) {{ $settings->trusted_by_text }}</p>
                @endif
            </div>
            @if($whyImage)
            <div class="bb-why-media">
                <img src="{{ $whyImage }}" alt="" class="bb-fill" loading="lazy" decoding="async">
                @if(filled($homeCopy['why_badge'] ?? null))
                <span class="bb-why-badge" aria-hidden="true">
                    <span class="bb-why-badge-peek">@include('website.partials.lottie', ['name' => 'purple-heart'])</span>
                    <span>{{ $homeCopy['why_badge'] }}</span>
                </span>
                @endif
                <span class="bb-why-kite">@include('website.partials.lottie', ['name' => 'kite'])</span>
            </div>
            @endif
        </div>
    </div>
</section>
@endif

{{-- New Arrivals --}}
@if(($newArrivals ?? collect())->isNotEmpty())
<section class="bb-section bb-new">
    <div class="tn-container">
        <header class="bb-head">
            <span class="bb-head-mark">@include('website.partials.lottie', ['name' => 'hatching-chick'])</span>
            <h2 class="bb-head-title">{{ $homeCopy['new_title'] ?? '' }} {{ $homeCopy['new_title_accent'] ?? '' }}</h2>
            <p class="bb-head-sub">{{ $homeCopy['new_subtitle'] ?? '' }}</p>
        </header>
        <div class="bb-product-grid">
            @foreach($newArrivals as $product)
                @include('website.partials.tn-product-card', ['product' => $product])
            @endforeach
        </div>
        <div class="bb-section-cta">
            <a href="{{ route('website.shop', ['filter' => 'new']) }}" class="bb-btn bb-btn--ghost">See all new arrivals</a>
        </div>
    </div>
</section>
@endif

{{-- Brands (Products → Brands; target of the "Brands" menu link) --}}
@php $homeBrands = ($brands ?? collect())->filter(fn ($b) => (int) ($b->products_count ?? 0) > 0)->take(12)->values(); @endphp
@if($homeBrands->isNotEmpty())
<section class="bb-section bb-brands" id="brands">
    <div class="tn-container">
        <header class="bb-head">
            <span class="bb-head-mark">@include('website.partials.lottie', ['name' => 'sparkles'])</span>
            <h2 class="bb-head-title">{{ $homeCopy['brands_title'] ?? '' }} {{ $homeCopy['brands_title_accent'] ?? '' }}</h2>
            <p class="bb-head-sub">{{ $homeCopy['brands_subtitle'] ?? '' }}</p>
        </header>
        <div class="bb-brand-grid">
            @foreach($homeBrands as $brand)
                <a href="{{ route('website.brand', \Illuminate\Support\Str::slug($brand->name)) }}" class="bb-brand-chip">
                    @if($brand->logo_path)
                        <img src="{{ public_storage_url($brand->logo_path) }}" alt="{{ $brand->name }}" loading="lazy" decoding="async">
                    @else
                        <span class="bb-brand-letter" aria-hidden="true">{{ strtoupper(mb_substr($brand->name, 0, 1)) }}</span>
                    @endif
                    <span class="bb-brand-name">{{ $brand->name }}</span>
                    <span class="bb-brand-count">{{ $brand->products_count }} {{ \Illuminate\Support\Str::plural('item', (int) $brand->products_count) }}</span>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Testimonials (CMS → Reviews) --}}
@if(($featuredReviews ?? collect())->isNotEmpty())
@php $reviewPages = $featuredReviews->values()->chunk(3)->values(); @endphp
<section class="bb-section bb-reviews"
         x-data="{
            page: 0,
            pages: {{ $reviewPages->count() }},
            timer: null,
            go(i) { this.page = ((i % this.pages) + this.pages) % this.pages; this.arm(); },
            arm() { clearInterval(this.timer); if (this.pages > 1) this.timer = setInterval(() => { this.page = (this.page + 1) % this.pages; }, 7000); }
         }"
         x-init="arm()">
    <span class="bb-reviews-cloud bb-reviews-cloud--left" aria-hidden="true">@include('website.partials.bb-icon', ['name' => 'cloud'])</span>
    <span class="bb-reviews-cloud bb-reviews-cloud--right" aria-hidden="true">@include('website.partials.bb-icon', ['name' => 'cloud'])</span>
    <div class="tn-container">
        <header class="bb-head">
            <span class="bb-head-mark">@include('website.partials.lottie', ['name' => 'hearts-face'])</span>
            <h2 class="bb-head-title">{{ $homeCopy['reviews_title'] ?? '' }}</h2>
            <p class="bb-head-sub">{{ $homeCopy['reviews_subtitle'] ?? '' }}</p>
        </header>
        <div class="bb-review-viewport">
            <div class="bb-review-track" :style="'transform: translateX(-' + (page * 100) + '%)'">
                @foreach($reviewPages as $group)
                    <div class="bb-review-page">
                        @foreach($group as $review)
                            <article class="bb-review-card">
                                <span class="bb-review-quote">@include('website.partials.bb-icon', ['name' => 'quote'])</span>
                                <p class="bb-review-body">{{ $review->body }}</p>
                                <div class="bb-review-stars" aria-label="Rated {{ (int) $review->rating }} out of 5">
                                    @for($i = 1; $i <= 5; $i++)
                                        <span class="{{ $i <= (int) $review->rating ? 'is-on' : '' }}">★</span>
                                    @endfor
                                </div>
                                <div class="bb-review-author">
                                    @if($review->avatar_path)
                                        <img src="{{ public_storage_url($review->avatar_path) }}" alt="" class="bb-review-avatar">
                                    @else
                                        <span class="bb-review-avatar bb-review-avatar--letter">{{ strtoupper(mb_substr($review->customer_name, 0, 1)) }}</span>
                                    @endif
                                    <div>
                                        <p class="bb-review-name">{{ $review->customer_name }}</p>
                                        @if($review->customer_title)
                                            <p class="bb-review-role">{{ $review->customer_title }}</p>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
        @if($reviewPages->count() > 1)
            <div class="bb-dots" role="tablist" aria-label="Testimonials">
                @foreach($reviewPages as $i => $group)
                    <button type="button" class="bb-dot" :class="{ 'is-active': page === {{ $i }} }" @click="go({{ $i }})" aria-label="Show reviews {{ $i + 1 }}"></button>
                @endforeach
            </div>
        @endif
    </div>
</section>
@endif

{{-- Latest blog posts (CMS → Blogs) --}}
@if(($latestBlogs ?? collect())->isNotEmpty())
<section class="bb-section bb-blog">
    <div class="tn-container">
        <header class="bb-head">
            <span class="bb-head-mark">@include('website.partials.lottie', ['name' => 'rainbow'])</span>
            <h2 class="bb-head-title">{{ $homeCopy['blog_title'] ?? '' }} {{ $homeCopy['blog_title_accent'] ?? '' }}</h2>
            <p class="bb-head-sub">{{ $homeCopy['blog_subtitle'] ?? '' }}</p>
        </header>
        <div class="bb-blog-grid">
            @foreach($latestBlogs as $post)
                <a href="{{ route('website.blog', $post->slug) }}" class="bb-blog-card">
                    <span class="bb-blog-media">
                        <img src="{{ $post->coverUrl() }}" alt="{{ $post->title }}" class="bb-blog-img" loading="lazy" decoding="async">
                        @if($post->category)
                            <span class="bb-blog-chip">{{ $post->category->name }}</span>
                        @endif
                    </span>
                    <span class="bb-blog-body">
                        @if($post->published_at)
                            <time class="bb-blog-meta" datetime="{{ $post->published_at->toDateString() }}">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                {{ $post->published_at->format('M d, Y') }}
                            </time>
                        @endif
                        <span class="bb-blog-title">{{ $post->title }}</span>
                        @if($post->excerpt)
                            <span class="bb-blog-excerpt">{{ strip_tags($post->excerpt) }}</span>
                        @endif
                        <span class="bb-blog-more">
                            Read article
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M5 12h14m-6-6 6 6-6 6"/></svg>
                        </span>
                    </span>
                </a>
            @endforeach
        </div>
        <div class="bb-section-cta">
            <a href="{{ route('website.blogs') }}" class="bb-btn bb-btn--ghost">Read the blog</a>
        </div>
    </div>
</section>
@endif

@endsection
