@php
    $storeName = $settings->store_name ?? config('app.name', 'Bynnas Social');
    $tagline = trim((string) ($settings->footer_tagline ?? ''));
    $footerCopy = data_get($settings, 'home_copy') ?: [];
    $footerLogoTagline = trim((string) ($footerCopy['logo_tagline'] ?? ''));
    $footerLogoTagline = $footerLogoTagline === '-' ? '' : $footerLogoTagline;

    $socialLinks = collect(\App\Models\SiteSetting::socialProfiles(data_get($settings, 'social_links') ?: []));

    $pages = collect($footerPages ?? []);
    $publishedPages = \App\Models\CmsPage::where('shop_id', app(\App\Services\WebsiteService::class)->shopId())
        ->where('is_published', true)
        ->orderBy('sort_order')
        ->get(['id', 'slug', 'title']);
    $findPage = function (array $needles) use ($publishedPages) {
        return $publishedPages->first(function ($p) use ($needles) {
            $hay = strtolower(($p->slug ?? '').' '.($p->title ?? ''));
            foreach ($needles as $n) {
                if (str_contains($hay, $n)) {
                    return true;
                }
            }

            return false;
        });
    };
    $aboutPage = $findPage(['about']);
    $privacyPage = $findPage(['privacy']);
    $termsPage = $findPage(['terms', 'condition']);
    $shippingPage = $findPage(['shipping', 'delivery']);
    $returnsPage = $findPage(['return', 'refund']);
    // Any other CMS pages marked “show in footer” that weren’t matched above
    $extraFooterPages = $pages->filter(function ($p) use ($aboutPage, $privacyPage, $termsPage, $shippingPage, $returnsPage) {
        foreach ([$aboutPage, $privacyPage, $termsPage, $shippingPage, $returnsPage] as $known) {
            if ($known && (int) $known->id === (int) $p->id) {
                return false;
            }
        }

        return true;
    });

    $privacyUrl = $privacyPage ? route('website.page', $privacyPage->slug) : null;
    $termsUrl = $termsPage ? route('website.page', $termsPage->slug) : null;
    $returnsUrl = $returnsPage ? route('website.page', $returnsPage->slug) : null;

    $footerCategories = collect($allCategories ?? $categories ?? [])->take(6);

    $contactPhone = trim((string) data_get($settings, 'contact_phone', ''));
    $contactEmail = trim((string) data_get($settings, 'contact_email', ''));
    $contactAddress = trim((string) data_get($settings, 'contact_address', ''));

    $footerIconPath = $settings->favicon_path ?: $settings->logo_path;
    $footerIcon = $footerIconPath ? public_storage_url($footerIconPath) : null;
    $footerIconVer = $footerIconPath
        ? (@filemtime(public_storage_path($footerIconPath)) ?: time())
        : time();
@endphp
<footer class="tn-footer bb-footer">
    <div class="tn-container tn-footer-shell">
        <div class="bb-footer-main">
            <div class="bb-footer-brand">
                <a href="{{ route('home') }}" class="bb-footer-logo">
                    @if($footerIcon)
                        <img src="{{ $footerIcon }}?v={{ $footerIconVer }}" alt="{{ $storeName }}" class="bb-footer-logo-img" width="44" height="44">
                    @else
                        <span class="bb-footer-logo-mark" aria-hidden="true">@include('website.partials.lottie', ['name' => 'bear'])</span>
                    @endif
                    <span class="bb-footer-logo-stack">
                        <span class="bb-footer-logo-text">{{ $storeName }}</span>
                        @if($footerLogoTagline !== '')
                            <span class="bb-footer-logo-tagline">{{ $footerLogoTagline }}</span>
                        @endif
                    </span>
                </a>
                @if($tagline !== '')
                    <p class="bb-footer-tagline">{{ $tagline }}</p>
                @endif
                @if($socialLinks->isNotEmpty())
                    <div class="bb-footer-social">
                        @foreach($socialLinks as $social)
                            <a href="{{ $social['url'] }}" target="_blank" rel="noopener" aria-label="{{ $social['label'] }}">
                                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="{{ $social['path'] }}"/></svg>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="bb-footer-col">
                <h4 class="bb-footer-heading">Shop</h4>
                <ul class="bb-footer-links">
                    <li><a href="{{ route('website.shop') }}">All Products</a></li>
                    <li><a href="{{ route('website.shop', ['filter' => 'new']) }}">New Arrivals</a></li>
                    <li><a href="{{ route('website.shop', ['filter' => 'bestsellers']) }}">Best Sellers</a></li>
                    <li><a href="{{ route('website.shop', ['filter' => 'deals']) }}">Sale</a></li>
                    <li><a href="{{ route('website.shop', ['filter' => 'combo']) }}">Combo Deals</a></li>
                    <li><a href="{{ route('website.wishlist') }}">Wishlist</a></li>
                </ul>
            </div>

            @if($footerCategories->isNotEmpty())
                <div class="bb-footer-col">
                    <h4 class="bb-footer-heading">Categories</h4>
                    <ul class="bb-footer-links">
                        @foreach($footerCategories as $cat)
                            <li><a href="{{ route('website.category', $cat->slug ?? $cat->id) }}">{{ $cat->name }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bb-footer-col">
                <h4 class="bb-footer-heading">Customer Care</h4>
                <ul class="bb-footer-links">
                    @if($shippingPage)
                        <li><a href="{{ route('website.page', $shippingPage->slug) }}">Shipping &amp; Delivery</a></li>
                    @endif
                    @if($returnsUrl)
                        <li><a href="{{ $returnsUrl }}">Returns &amp; Exchanges</a></li>
                    @endif
                    <li><a href="{{ route('website.faqs') }}">FAQ</a></li>
                    <li><a href="{{ route('website.contact') }}">Contact Us</a></li>
                </ul>
            </div>

            <div class="bb-footer-col">
                <h4 class="bb-footer-heading">About Us</h4>
                <ul class="bb-footer-links">
                    @if($aboutPage)
                        <li><a href="{{ route('website.page', $aboutPage->slug) }}">Our Story</a></li>
                    @endif
                    <li><a href="{{ route('website.blogs') }}">Blog</a></li>
                    @foreach($extraFooterPages ?? [] as $extraPage)
                        <li><a href="{{ route('website.page', $extraPage->slug) }}">{{ $extraPage->title }}</a></li>
                    @endforeach
                </ul>
            </div>

            @if($contactPhone !== '' || $contactEmail !== '' || $contactAddress !== '')
                <div class="bb-footer-col bb-footer-contact">
                    <h4 class="bb-footer-heading">Contact Us</h4>
                    <ul class="bb-footer-links">
                        @if($contactPhone !== '')
                            <li><a href="tel:{{ preg_replace('/[^0-9+]/', '', $contactPhone) }}">@include('website.partials.bb-icon', ['name' => 'phone']) {{ $contactPhone }}</a></li>
                        @endif
                        @if($contactEmail !== '')
                            <li><a href="mailto:{{ $contactEmail }}">@include('website.partials.bb-icon', ['name' => 'mail']) {{ $contactEmail }}</a></li>
                        @endif
                        @if($contactAddress !== '')
                            <li><span>@include('website.partials.bb-icon', ['name' => 'pin']) {{ $contactAddress }}</span></li>
                        @endif
                    </ul>
                </div>
            @endif
        </div>
    </div>
    <div class="tn-footer-credit bb-footer-credit">
        <div class="tn-container bb-footer-credit-inner">
            <p class="tn-footer-copy">&copy; {{ date('Y') }} {{ $storeName }}. All rights reserved.</p>
            <nav class="bb-footer-legal" aria-label="Legal">
                @if($privacyUrl)<a href="{{ $privacyUrl }}">Privacy Policy</a>@endif
                @if($termsUrl)<a href="{{ $termsUrl }}">Terms of Service</a>@endif
                @if($returnsUrl)<a href="{{ $returnsUrl }}">Refund Policy</a>@endif
            </nav>
            @include('partials.powered-by', ['variant' => 'footer'])
        </div>
    </div>
</footer>
