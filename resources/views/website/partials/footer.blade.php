@php
    $storeName = $settings->store_name ?? config('app.name', 'Maks Gadget');
    $storeLogo = !empty($settings->logo_path) ? public_storage_url($settings->logo_path) : null;
    $tagline = $settings->footer_tagline
        ?? 'Your one-stop shop for the latest tech gadgets and accessories.';

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

    $accessoriesCategory = collect($allCategories ?? $categories ?? [])->first(
        fn ($c) => str_contains(strtolower($c->name ?? ''), 'accessor')
    );
@endphp
<footer class="tn-footer">
    <div class="tn-container tn-footer-shell">
        <div class="tn-footer-main">
            <div class="tn-footer-brand">
                <a href="{{ route('home') }}" class="tn-footer-logo">
                    @php
                        $footerIconPath = $settings->favicon_path ?: $settings->logo_path;
                        $footerIcon = $footerIconPath ? public_storage_url($footerIconPath) : null;
                        $footerIconVer = $footerIconPath
                            ? (@filemtime(public_storage_path($footerIconPath)) ?: time())
                            : time();
                    @endphp
                    @if($footerIcon)
                        <img src="{{ $footerIcon }}?v={{ $footerIconVer }}"
                             alt="{{ $storeName }}"
                             class="tn-footer-logo-img"
                             width="52"
                             height="52"
                             style="width:52px;height:52px;object-fit:contain;background:transparent;border:0;border-radius:0;">
                        <span class="tn-footer-logo-text">{{ $storeName }}</span>
                    @else
                        <span class="tn-footer-logo-mark" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                            </svg>
                        </span>
                        <span class="tn-footer-logo-text">{{ $storeName }}</span>
                    @endif
                </a>
                <p class="tn-footer-tagline">{{ $tagline }}</p>

                @php
                    $socialLinks = collect($socialMap)->map(function ($meta, $key) use ($socialRaw) {
                        $url = trim((string) ($socialRaw[$key] ?? ''));

                        return $url !== '' ? ['url' => $url, 'label' => $meta['label'], 'path' => $meta['path']] : null;
                    })->filter()->values();
                @endphp
                @if($socialLinks->isNotEmpty())
                <div class="tn-footer-social">
                    @foreach($socialLinks as $social)
                        <a href="{{ $social['url'] }}" target="_blank" rel="noopener" class="tn-footer-social-btn" aria-label="{{ $social['label'] }}">
                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="{{ $social['path'] }}"/></svg>
                        </a>
                    @endforeach
                </div>
                @endif
            </div>

            <div class="tn-footer-col">
                <h4 class="tn-footer-heading">Shop</h4>
                <ul class="tn-footer-links">
                    <li>
                        <a href="{{ route('website.shop') }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h6v6H4V6zm10 0h6v6h-6V6zM4 16h6v6H4v-6zm10 0h6v6h-6v-6z"/></svg>
                            All Products
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('website.shop', ['filter' => 'deals']) }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"/><circle cx="7" cy="7" r="1.25" fill="currentColor" stroke="none"/></svg>
                            Deals
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('website.shop', ['filter' => 'new']) }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M5 3l1.5 4.5L11 9 6.5 10.5 5 15l-1.5-4.5L0 9l4.5-1.5L5 3zm14 4l1 3 3 1-3 1-1 3-1-3-3-1 3-1 1-3zm-7 6l1.2 3.6L20 18l-3.8 1.4L15 23l-1.2-3.6L10 18l3.8-1.4L15 13z"/></svg>
                            New Arrivals
                            <span class="tn-footer-badge">New</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('website.shop', ['filter' => 'bestsellers']) }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3l2.4 4.9 5.4.8-3.9 3.8.9 5.4L12 15.9 7.2 18l.9-5.4L4.2 8.7l5.4-.8L12 3z"/></svg>
                            Best Sellers
                        </a>
                    </li>
                    <li>
                        <a href="{{ $accessoriesCategory ? route('website.shop', ['category' => $accessoriesCategory->slug ?? $accessoriesCategory->id]) : route('website.shop') }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                            Accessories
                        </a>
                    </li>
                </ul>
            </div>

            <div class="tn-footer-col">
                <h4 class="tn-footer-heading">Help</h4>
                <ul class="tn-footer-links">
                    <li>
                        <a href="{{ route('website.contact') }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M22 16.92v3a2 2 0 01-2.18 2 19.8 19.8 0 01-8.63-3.07 19.5 19.5 0 01-6-6A19.8 19.8 0 012.12 4.18 2 2 0 014.11 2h3a2 2 0 012 1.72c.13.96.36 1.9.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0122 16.92z"/></svg>
                            Contact Us
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('website.faqs') }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M9.1 9a3 3 0 015.8 1c0 2-3 2.5-3 4.5M12 17h.01"/></svg>
                            FAQ
                        </a>
                    </li>
                    <li>
                        <a href="{{ $shippingPage ? route('website.page', $shippingPage->slug) : route('website.faqs') }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M1 7h13v10H1V7zm13 3h5l3 3v4h-8V10z"/><circle cx="5.5" cy="18.5" r="1.75"/><circle cx="16.5" cy="18.5" r="1.75"/></svg>
                            Shipping Info
                        </a>
                    </li>
                    <li>
                        <a href="{{ $returnsPage ? route('website.page', $returnsPage->slug) : route('website.faqs') }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h11a4 4 0 110 8H9"/><path stroke-linecap="round" stroke-linejoin="round" d="M7 6L3 10l4 4"/></svg>
                            Returns
                        </a>
                    </li>
                </ul>
            </div>

            <div class="tn-footer-col">
                <h4 class="tn-footer-heading">Company</h4>
                <ul class="tn-footer-links">
                    <li>
                        <a href="{{ $aboutPage ? route('website.page', $aboutPage->slug) : route('website.contact') }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M17 21v-2a4 4 0 00-4-4H7a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path stroke-linecap="round" stroke-linejoin="round" d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
                            About Us
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('website.blogs') }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4h12a2 2 0 012 2v14l-4-2-4 2-4-2-4 2V6a2 2 0 012-2z"/><path stroke-linecap="round" d="M8 9h8M8 13h6"/></svg>
                            Blog
                        </a>
                    </li>
                    <li>
                        <a href="{{ $privacyPage ? route('website.page', $privacyPage->slug) : url('/page/privacy-policy') }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3l8 3v5c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6l8-3z"/></svg>
                            Privacy Policy
                        </a>
                    </li>
                    <li>
                        <a href="{{ $termsPage ? route('website.page', $termsPage->slug) : url('/page/terms-and-conditions') }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 3h7l5 5v11a2 2 0 01-2 2H8a2 2 0 01-2-2V5a2 2 0 012-2z"/><path stroke-linecap="round" d="M15 3v5h5M9 13h6M9 17h4"/></svg>
                            Terms &amp; Conditions
                        </a>
                    </li>
                    @foreach($extraFooterPages ?? [] as $extraPage)
                        <li>
                            <a href="{{ route('website.page', $extraPage->slug) }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 3h7l5 5v11a2 2 0 01-2 2H8a2 2 0 01-2-2V5a2 2 0 012-2z"/><path stroke-linecap="round" d="M15 3v5h5"/></svg>
                                {{ $extraPage->title }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
    <div class="tn-footer-credit">
        <div class="tn-container tn-footer-credit-inner">
            <p class="tn-footer-copy">&copy; {{ date('Y') }} {{ $storeName }}</p>
            <span class="tn-footer-credit-sep" aria-hidden="true"></span>
            @include('partials.powered-by', ['variant' => 'footer'])
        </div>
    </div>
</footer>
