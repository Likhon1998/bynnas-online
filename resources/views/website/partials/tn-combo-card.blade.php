@php
    $ws = app(\App\Services\WebsiteService::class);
    $discount = $product->discountPercent();
    $currentPrice = $product->currentPrice();
    $compareAt = $product->compareAtPrice();
    $img = $ws->productImageUrl($product);
    $displayName = $product->storefrontDisplayName();
    $brandLabel = trim((string) ($product->brand_name ?? $product->brand?->name ?? ''));
    $comboItems = $product->comboItemList();
    $comboCount = count($comboItems);
    $comboBlurb = trim((string) ($product->short_description ?? ''));
    $saveAmount = ($compareAt && $compareAt > $currentPrice) ? round($compareAt - $currentPrice, 2) : 0;
    $inStock = ((int) $product->availableStock()) > 0;
    $variantChips = $product->variantOptions()
        ->map(fn ($opt) => trim((string) ($opt['value'] ?? '')))
        ->filter()
        ->unique()
        ->take(2)
        ->values()
        ->all();

    $listItem = [
        'id' => $product->id,
        'name' => $displayName,
        'price' => $currentPrice,
        'image' => $img,
        'url' => route('website.product', $product),
        'category' => $brandLabel,
        'rating' => (float) ($product->rating ?? 0),
    ];

    $cartItem = [
        'id' => $product->id,
        'name' => $displayName,
        'price' => $currentPrice,
        'image' => $img,
        'stock' => max(0, (int) $product->availableStock()),
    ];
@endphp

<article class="tn-combo-card">
    <div class="tn-combo-card__media">
        @if($discount > 0)
            <span class="tn-combo-card__ribbon">-{{ $discount }}% off</span>
        @elseif($product->is_best_seller)
            <span class="tn-combo-card__ribbon tn-combo-card__ribbon--best">Best seller</span>
        @else
            <span class="tn-combo-card__ribbon tn-combo-card__ribbon--pack">Combo pack</span>
        @endif

        <button type="button"
                class="tn-combo-card__wish"
                title="Wishlist"
                :class="inWishlist({{ $product->id }}) && 'is-active'"
                @click.prevent="toggleWishlist(@js($listItem))"
                :aria-pressed="inWishlist({{ $product->id }}) ? 'true' : 'false'"
                aria-label="Toggle wishlist">
            <svg :fill="inWishlist({{ $product->id }}) ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
            </svg>
        </button>

        <a href="{{ route('website.product', $product) }}" class="tn-combo-card__img" aria-label="{{ $displayName }}">
            <img src="{{ $img }}" alt="{{ $displayName }}" loading="lazy" decoding="async">
        </a>

        <div class="tn-combo-card__media-meta">
            <span class="tn-combo-card__count">
                <strong>{{ $comboCount > 0 ? $comboCount : '—' }}</strong>
                {{ $comboCount > 0 ? \Illuminate\Support\Str::plural('item', $comboCount) : 'pack' }}
            </span>
            @if(! empty($variantChips))
                <span class="tn-combo-card__size">{{ implode(' · ', $variantChips) }}</span>
            @endif
        </div>
    </div>

    <div class="tn-combo-card__body">
        <div class="tn-combo-card__topline">
            @if($brandLabel !== '')
                <p class="tn-combo-card__brand">{{ $brandLabel }}</p>
            @endif
            <span class="tn-combo-card__stock {{ $inStock ? 'is-in' : 'is-out' }}">
                {{ $inStock ? 'In stock' : 'Out of stock' }}
            </span>
        </div>

        <a href="{{ route('website.product', $product) }}" class="tn-combo-card__title">{{ $displayName }}</a>

        @if($comboCount > 0)
            <div class="tn-combo-card__includes">
                <div class="tn-combo-card__includes-head">
                    <p class="tn-combo-card__includes-label">Includes</p>
                    <span class="tn-combo-card__includes-count">{{ $comboCount }}</span>
                </div>
                <ol class="tn-combo-card__list">
                    @foreach(array_slice($comboItems, 0, 2) as $item)
                        <li>
                            <span class="tn-combo-card__num" aria-hidden="true">{{ $loop->iteration }}</span>
                            <span class="tn-combo-card__item">{{ $item }}</span>
                        </li>
                    @endforeach
                </ol>
                @if($comboCount > 2)
                    <p class="tn-combo-card__more">+{{ $comboCount - 2 }} more</p>
                @endif
            </div>
        @elseif($comboBlurb !== '')
            <p class="tn-combo-card__blurb">{{ $comboBlurb }}</p>
        @endif

        <div class="tn-combo-card__buy">
            <div class="tn-combo-card__prices">
                <div class="tn-combo-card__price-row">
                    <span class="tn-combo-card__price">{{ $ws->formatPrice($currentPrice, $settings) }}</span>
                    @if($compareAt && $compareAt > $currentPrice)
                        <span class="tn-combo-card__old">{{ $ws->formatPrice($compareAt, $settings) }}</span>
                    @endif
                </div>
                @if($saveAmount > 0)
                    <span class="tn-combo-card__save">Save {{ $ws->formatPrice($saveAmount, $settings) }}</span>
                @endif
            </div>

            <button type="button"
                    class="tn-combo-card__add"
                    data-add-to-cart='@json($cartItem)'
                    data-qty="1"
                    data-open-cart="1"
                    @disabled(! $inStock)
                    aria-label="Add {{ $displayName }} to cart">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l3-8H6.4M7 13L5.4 5M7 13l-2 6h12m-8 0a1 1 0 100 2 1 1 0 000-2zm8 0a1 1 0 100 2 1 1 0 000-2z"/>
                </svg>
                <span>Add</span>
            </button>
        </div>
    </div>
</article>
