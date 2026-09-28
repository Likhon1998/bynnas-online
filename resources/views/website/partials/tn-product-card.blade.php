@php
    $ws = app(\App\Services\WebsiteService::class);
    $discount = $product->discountPercent();
    $currentPrice = $product->currentPrice();
    $compareAt = $product->compareAtPrice();
    $img = $ws->productImageUrl($product);
    $displayName = $product->storefrontDisplayName();
    $catLabel = $product->category?->name ?? $product->brand_name ?? 'Electronics';
    $flash = !empty($flash);
    $isNew = $product->showsAsNew();
    $rating = (float) ($product->rating ?? 0);
    $reviews = (int) ($product->review_count ?? 0);

    $listItem = [
        'id' => $product->id,
        'name' => $displayName,
        'price' => $currentPrice,
        'image' => $img,
        'url' => route('website.product', $product),
        'category' => $catLabel,
        'rating' => $rating,
    ];

    $cartItem = [
        'id' => $product->id,
        'name' => $displayName,
        'price' => $currentPrice,
        'image' => $img,
        'stock' => max(0, (int) $product->availableStock()),
    ];
@endphp

<article class="tn-product-card {{ $flash ? 'tn-product-card--flash' : '' }} {{ $isNew && $discount <= 0 ? 'tn-product-card--new' : '' }}">
    @if($discount > 0)
        <span class="tn-product-discount">-{{ $discount }}%</span>
    @elseif($isNew)
        <span class="tn-product-new">New</span>
    @endif

    <button type="button"
            class="tn-product-wish"
            title="Wishlist"
            :class="inWishlist({{ $product->id }}) && 'is-active'"
            @click.prevent="toggleWishlist(@js($listItem))"
            :aria-pressed="inWishlist({{ $product->id }}) ? 'true' : 'false'"
            aria-label="Toggle wishlist">
        <svg :fill="inWishlist({{ $product->id }}) ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
        </svg>
    </button>

    <a href="{{ route('website.product', $product) }}" class="tn-product-img" aria-label="{{ $displayName }}">
        <img src="{{ $img }}" alt="{{ $displayName }}" loading="lazy" decoding="async">
    </a>

    <div class="tn-product-meta">
        <p class="tn-product-cat">{{ $catLabel }}</p>
        <a href="{{ route('website.product', $product) }}" class="tn-product-name">{{ $displayName }}</a>

        <div class="tn-product-stars" aria-label="Rating {{ number_format($rating, 1) }}">
            @for($i = 1; $i <= 5; $i++)
                <span class="tn-product-star {{ $i > round($rating) ? 'empty' : '' }}">★</span>
            @endfor
            @if($reviews > 0)
                <span class="tn-product-reviews">({{ number_format($reviews) }})</span>
            @else
                <span class="tn-product-reviews tn-product-reviews--empty">&nbsp;</span>
            @endif
        </div>

        <div class="tn-product-foot">
            <div class="tn-product-price-row">
                <span class="tn-product-price">{{ $ws->formatPrice($currentPrice, $settings) }}</span>
                <span class="tn-product-old{{ $compareAt ? '' : ' is-empty' }}">
                    {{ $compareAt ? $ws->formatPrice($compareAt, $settings) : '—' }}
                </span>
            </div>
            <button type="button"
                    class="tn-product-add"
                    data-add-to-cart='@json($cartItem)'
                    data-qty="1"
                    data-open-cart="1"
                    aria-label="Add {{ $displayName }} to cart">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l3-8H6.4M7 13L5.4 5M7 13l-2 6h12m-8 0a1 1 0 100 2 1 1 0 000-2zm8 0a1 1 0 100 2 1 1 0 000-2z"/>
                </svg>
                <span class="tn-product-add-label">Add</span>
            </button>
        </div>
    </div>
</article>
