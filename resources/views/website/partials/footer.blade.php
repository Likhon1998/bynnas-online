@php
    $storeName = $settings->store_name ?? config('app.name', 'Bynnas Social');
    $tagline = $settings->footer_tagline
        ?? 'Thoughtfully chosen products for every little adventure.';
    $footerCopy = data_get($settings, 'home_copy') ?: [];
    $footerLogoTagline = $footerCopy['logo_tagline'] ?? 'Kids & Baby Store';

    $socialRaw = data_get($settings, 'social_links') ?: [];
    $socialMap = [
        'facebook' => [
            'label' => 'Facebook',
            'path' => 'M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z',
        ],
        'instagram' => [
            'label' => 'Instagram',
            'path' => 'M7 3h10a4 4 0 014 4v10a4 4 0 01-4 4H7a4 4 0 01-4-4V7a4 4 0 014-4zm5 4.5A4.5 4.5 0 1016.5 12 4.5 4.5 0 0012 7.5zm5.25-.75a1.125 1.125 0 11-1.125-1.125A1.125 1.125 0 0117.25 6.75z',
        ],
        'twitter' => [
            'label' => 'Twitter',
            'path' => 'M18.244 3H21l-6.52 7.45L22 21h-5.98l-4.68-6.12L6.1 21H3.34l6.98-7.97L2 3h6.14l4.23 5.61L18.244 3zm-1.05 16.2h1.66L7.01 4.7H5.23l11.964 14.5z',
        ],
        'youtube' => [
            'label' => 'YouTube',
            'path' => 'M22.5 7.2a2.8 2.8 0 00-2-2C18.7 4.8 12 4.8 12 4.8s-6.7 0-8.5.4a2.8 2.8 0 00-2 2A29 29 0 001 12a29 29 0 00.5 4.8 2.8 2.8 0 002 2c1.8.4 8.5.4 8.5.4s6.7 0 8.5-.4a2.8 2.8 0 002-2A29 29 0 0023 12a29 29 0 00-.5-4.8zM9.8 15.2V8.8L15.7 12l-5.9 3.2z',
        ],
    ];
    $socialLinks = collect($socialMap)->map(function ($meta, $key) use ($socialRaw) {
        $url = trim((string) ($socialRaw[$key] ?? ''));

        return $url !== '' ? ['url' => $url, 'label' => $meta['label'], 'path' => $meta['path']] : null;
    })->filter()->values();

    $pages = collect($footerPages ?? []);
    $findPage = function (array $needles) use ($pages) {
        return $pages->first(function ($p) use ($needles) {
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

    $privacyUrl = $privacyPage ? route('website.page', $privacyPage->slug) : url('/page/privacy-policy');
    $termsUrl = $termsPage ? route('website.page', $termsPage->slug) : url('/page/terms-and-conditions');
    $returnsUrl = $returnsPage ? route('website.page', $returnsPage->slug) : route('website.faqs');

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
                <p class="bb-footer-tagline">{{ $tagline }}</p>
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
                    <li><a href="{{ $shippingPage ? route('website.page', $shippingPage->slug) : route('website.faqs') }}">Shipping &amp; Delivery</a></li>
                    <li><a href="{{ $returnsUrl }}">Returns &amp; Exchanges</a></li>
                    <li><a href="{{ route('website.faqs') }}">FAQ</a></li>
                    <li><a href="{{ route('website.track') }}">Track Your Order</a></li>
                    <li><a href="{{ route('website.contact') }}">Contact Us</a></li>
                </ul>
            </div>

            <div class="bb-footer-col">
                <h4 class="bb-footer-heading">About Us</h4>
                <ul class="bb-footer-links">
                    <li><a href="{{ $aboutPage ? route('website.page', $aboutPage->slug) : route('website.contact') }}">Our Story</a></li>
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
                <a href="{{ $privacyUrl }}">Privacy Policy</a>
                <a href="{{ $termsUrl }}">Terms of Service</a>
                <a href="{{ $returnsUrl }}">Refund Policy</a>
            </nav>
            @include('partials.powered-by', ['variant' => 'footer'])
        </div>
    </div>
</footer>
