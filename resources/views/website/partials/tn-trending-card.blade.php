@php
    $ws = app(\App\Services\WebsiteService::class);
    $currentPrice = $product->currentPrice();
    $compareAt = $product->compareAtPrice();
    $discount = $product->discountPercent();
    $img = $ws->productImageUrl($product);
    $displayName = $product->storefrontDisplayName();
    $catLabel = strtoupper($product->category?->name ?? $product->brand_name ?? 'Gadgets');
    $rank = (int) ($trendingRank ?? 1);
    $rating = (float) ($product->rating ?? 0);
    $reviews = (int) ($product->review_count ?? 0);
    $reviewLabel = $reviews >= 1000
        ? rtrim(rtrim(number_format($reviews / 1000, 1), '0'), '.').'k'
        : number_format($reviews);
    $availableQty = max(0, (int) $product->availableStock());
    $isTop = $rank <= 3;
    $isFirst = $rank === 1;

    $cartItem = [
        'id' => $product->id,
        'name' => $displayName,
        'price' => $currentPrice,
        'image' => $img,
        'stock' => $availableQty,
    ];
@endphp

<article class="tn-trending-card{{ $isFirst ? ' is-featured' : '' }}{{ $isTop ? ' is-top' : '' }}">
    <a href="{{ route('website.product', $product) }}" class="tn-trending-media" aria-label="{{ $displayName }}">
        <span class="tn-trending-badge" data-rank="{{ $rank }}">
            @if($isFirst)
                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2l2.4 7.2H22l-6 4.8 2.3 7L12 16.8 5.7 21l2.3-7-6-4.8h7.6L12 2z"/></svg>
            @endif
            #{{ $rank }}
        </span>
        @if($discount > 0)
            <span class="tn-trending-sale">-{{ $discount }}%</span>
        @elseif($isTop)
            <span class="tn-trending-hot">Hot</span>
        @endif
        <span class="tn-trending-shine" aria-hidden="true"></span>
        <img src="{{ $img }}" alt="{{ $displayName }}" class="tn-trending-img" loading="lazy" decoding="async">
    </a>

    <div class="tn-trending-body">
        <p class="tn-trending-cat">{{ $catLabel }}</p>
        <a href="{{ route('website.product', $product) }}" class="tn-trending-name">{{ $displayName }}</a>

        <div class="tn-trending-rating" aria-label="Rating {{ number_format($rating, 1) }}">
            @for($i = 1; $i <= 5; $i++)
                <span class="tn-trending-star {{ $i > round($rating) ? 'is-empty' : '' }}">★</span>
            @endfor
            @if($rating > 0)
                <strong>{{ number_format($rating, 1) }}</strong>
            @endif
            @if($reviews > 0)
                <span>({{ $reviewLabel }})</span>
            @endif
        </div>

        <div class="tn-trending-foot">
            <div class="tn-trending-prices">
                <span class="tn-trending-price">{{ $ws->formatPrice($currentPrice, $settings) }}</span>
                @if($compareAt && $compareAt > $currentPrice)
                    <span class="tn-trending-old">{{ $ws->formatPrice($compareAt, $settings) }}</span>
                @endif
            </div>
            <button type="button"
                    class="tn-trending-cart"
                    title="Add to cart"
                    aria-label="Add {{ $displayName }} to cart"
                    data-add-to-cart='@json($cartItem)'
                    data-qty="1"
                    data-open-cart="1"
                    @if($availableQty < 1) disabled @endif>
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l3-8H6.4M7 13L5.4 5M7 13l-2 6h12m-8 0a1 1 0 100 2 1 1 0 000-2zm8 0a1 1 0 100 2 1 1 0 000-2z"/>
                </svg>
                <span>Add</span>
            </button>
        </div>
    </div>
</article>
