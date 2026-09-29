@extends('website.layout')
@php
    $ws = app(\App\Services\WebsiteService::class);
    $homeCopy = data_get($settings, 'home_copy') ?: [];
    $storeName = $settings->store_name ?? config('app.name', 'Bynnas Social');
@endphp

@section('content')

{{-- Hero: headline + CTAs + trust badges, CMS slide photos rotate in the rounded media panel --}}
@php
    $heroTitle = $homeCopy['hero_title'] ?? 'Little Things, Big Joys';
    $heroSubtitle = $homeCopy['hero_subtitle'] ?? 'Thoughtfully chosen essentials for every precious moment.';
    $heroBadge = $homeCopy['hero_badge'] ?? 'For Every Little Adventure';
    $heroTrust = [
        ['lottie' => 'sparkles', 'title' => $homeCopy['hero_trust_1_title'] ?? 'Gentle Picks', 'sub' => $homeCopy['hero_trust_1_sub'] ?? 'Chosen for little ones'],
        ['lottie' => 'check', 'title' => $homeCopy['hero_trust_2_title'] ?? 'Genuine Products', 'sub' => $homeCopy['hero_trust_2_sub'] ?? 'From trusted brands'],
        ['lottie' => 'heart', 'title' => $homeCopy['hero_trust_3_title'] ?? 'Made with Love', 'sub' => $homeCopy['hero_trust_3_sub'] ?? 'For happy families'],
    ];
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
    if ($heroImages->isEmpty()) {
        $heroImages = collect([(object) [
            'src' => 'https://images.unsplash.com/photo-1515488042361-ee00e0ddd4e4?w=1400&q=80',
            'title' => $heroTitle,
            'url' => route('website.shop'),
            'cta' => 'Shop now',
        ]]);
    }
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
                </div>

                <span class="bb-hero-badge" aria-hidden="true">
                    <span class="bb-hero-badge-peek">@include('website.partials.lottie', ['name' => 'chick'])</span>
                    <span>{{ $heroBadge }}</span>
                </span>
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

{{-- Shop by Categories --}}
@if($categories->isNotEmpty())
@php
    $catFallback = 'https://images.unsplash.com/photo-1515488042361-ee00e0ddd4e4?w=600&q=80';
    $catTints = ['is-peach', 'is-lavender', 'is-sage', 'is-butter', 'is-rose', 'is-sky'];
@endphp
<section class="bb-section bb-cats">
    <div class="tn-container">
        <header class="bb-head">
            <span class="bb-head-mark">@include('website.partials.lottie', ['name' => 'rainbow'])</span>
            <h2 class="bb-head-title">{{ $homeCopy['categories_title'] ?? 'Shop by' }} {{ $homeCopy['categories_title_accent'] ?? 'Categories' }}</h2>
            <p class="bb-head-sub">{{ $homeCopy['categories_subtitle'] ?? 'Everything your little one needs, all in one place.' }}</p>
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
                        <h2 class="bb-flash-heading">{{ $homeCopy['flash_title'] ?? 'Flash' }} {{ $homeCopy['flash_title_accent'] ?? 'Sale' }}</h2>
                        <p class="bb-flash-sub">{{ $homeCopy['flash_subtitle'] ?? 'Hurry! Sweet prices on little favourites.' }}</p>
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

{{-- Featured Products (best sellers) --}}
@if(($bestSellers ?? collect())->isNotEmpty())
<section class="bb-section bb-featured">
    <div class="tn-container">
        <header class="bb-head">
            <span class="bb-head-mark">@include('website.partials.lottie', ['name' => 'star'])</span>
            <h2 class="bb-head-title">{{ $homeCopy['featured_title'] ?? 'Featured Products' }}</h2>
            <p class="bb-head-sub">{{ $homeCopy['featured_subtitle'] ?? 'Our most loved picks, chosen by families like yours.' }}</p>
        </header>
        <div class="bb-rail-wrap" x-data="{
                over: false,
                check() { const r = this.$refs.rail; this.over = r.scrollWidth > r.clientWidth + 4; },
                scroll(d) { const r = this.$refs.rail; r.scrollBy({ left: d * r.clientWidth * 0.8, behavior: 'smooth' }); }
             }"
             x-init="$nextTick(() => check()); window.addEventListener('resize', () => check())">
            <button type="button" x-show="over" x-cloak class="bb-rail-arrow is-prev" @click="scroll(-1)" aria-label="Previous products">@include('website.partials.bb-icon', ['name' => 'chevron-left'])</button>
            <div class="bb-rail" x-ref="rail">
                @foreach($bestSellers as $product)
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
                <h2 class="bb-head-title">{{ $homeCopy['combo_title'] ?? 'Combo Deals' }}</h2>
                <p class="bb-head-sub">{{ $homeCopy['combo_subtitle'] ?? 'Handy bundles that save you more — perfect for gifting too!' }}</p>
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

{{-- Why choose us (CMS → Landing Page features) --}}
@if($features->isNotEmpty())
@php
    $babyCategory = $categories->first(fn ($c) => str_contains(strtolower(($c->slug ?? '').' '.$c->name), 'baby'));
    $whyImage = ($babyCategory ? $ws->categoryImageUrl($babyCategory) : null)
        ?: 'https://images.unsplash.com/photo-1515488042361-ee00e0ddd4e4?w=1000&q=80';
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
                <h2 class="bb-head-title">{{ $homeCopy['why_title'] ?? 'Why Parents Choose '.$storeName }}</h2>
                <p class="bb-head-sub">{{ $homeCopy['why_subtitle'] ?? 'Because your little one deserves the very best.' }}</p>
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
            </div>
            <div class="bb-why-media">
                <img src="{{ $whyImage }}" alt="" class="bb-fill" loading="lazy" decoding="async">
                <span class="bb-why-badge" aria-hidden="true">
                    <span class="bb-why-badge-peek">@include('website.partials.lottie', ['name' => 'purple-heart'])</span>
                    <span>{{ $homeCopy['why_badge'] ?? 'Quality You Can Trust' }}</span>
                </span>
                <span class="bb-why-kite">@include('website.partials.lottie', ['name' => 'kite'])</span>
            </div>
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
            <h2 class="bb-head-title">{{ $homeCopy['new_title'] ?? 'New' }} {{ $homeCopy['new_title_accent'] ?? 'Arrivals' }}</h2>
            <p class="bb-head-sub">{{ $homeCopy['new_subtitle'] ?? 'Freshly hatched goodies, just landed in our nest.' }}</p>
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
            <h2 class="bb-head-title">{{ $homeCopy['reviews_title'] ?? 'Loved by Parents' }}</h2>
            <p class="bb-head-sub">{{ $homeCopy['reviews_subtitle'] ?? 'Real stories from our happy '.$storeName.' family.' }}</p>
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

{{-- Newsletter --}}
<section class="bb-newsletter-wrap" id="newsletter">
    <div class="tn-container">
        <div class="bb-newsletter">
            <span class="bb-newsletter-ico">@include('website.partials.lottie', ['name' => 'love-letter'])</span>
            <div class="bb-newsletter-copy">
                <h3>{{ $homeCopy['newsletter_title'] ?? 'Join Our '.$storeName.' Family' }}</h3>
                <p>{{ $homeCopy['newsletter_text'] ?? 'Get special offers and new arrivals straight to your inbox.' }}</p>
            </div>
            <form method="POST" action="{{ route('website.newsletter') }}" class="bb-newsletter-form">
                @csrf
                <label for="bb-newsletter-email" class="sr-only">Email address</label>
                <input id="bb-newsletter-email" type="email" name="email" required maxlength="255" placeholder="Enter your email address" autocomplete="email">
                <button type="submit">Subscribe</button>
            </form>
            @if(session('newsletter_success'))
                <p class="bb-newsletter-ok" role="status">{{ session('newsletter_success') }}</p>
            @endif
            @error('email')
                <p class="bb-newsletter-err" role="alert">{{ $message }}</p>
            @enderror
            <span class="bb-newsletter-sun">@include('website.partials.lottie', ['name' => 'sun'])</span>
            <span class="bb-newsletter-cloud">@include('website.partials.lottie', ['name' => 'cloud'])</span>
        </div>
    </div>
</section>

@endsection
