@php
    $ws = app(\App\Services\WebsiteService::class);
    $images = $ws->productImageUrls($product);
    $img = $images[0];
    $reviews = $reviews ?? collect();
        $variantOptions = $variantOptions ?? ['colors' => [], 'combos' => [], 'storages' => [], 'rams' => []];
        $discountPct = $product->discountPercent();
        $currentPrice = $product->currentPrice();
        $compareAt = $product->compareAtPrice();
    $displayName = $product->storefrontDisplayName();
    $hasVariantPicker = count($variantOptions['colors'] ?? []) > 0
        || count($variantOptions['combos'] ?? []) > 0
        || count($variantOptions['storages'] ?? []) > 0
        || count($variantOptions['rams'] ?? []) > 0;
@endphp

{{-- Breadcrumbs --}}
<nav class="text-xs text-slate-400 mb-3 flex flex-wrap items-center gap-1">
    <a href="{{ route('home') }}">Home</a>
    <span>/</span>
    @if($product->category)
        <a href="{{ route('website.shop', ['category' => $product->category_id]) }}">{{ $product->category->name }}</a>
        <span>/</span>
    @endif
    @if($product->brand_name || $product->brand)
        <span class="text-slate-500">{{ $product->brand_name ?? $product->brand?->name }}</span>
        <span>/</span>
    @endif
    <span class="text-slate-600">{{ $displayName }}</span>
</nav>

<div class="pd-layout"
     x-data="{
        active: 0,
        images: @js($images),
        tab: 'description',
        qty: 1,
        zooming: false,
        zoomX: 50,
        zoomY: 50,
        onZoomEnter() {
            if (window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
                this.zooming = true;
            }
        },
        onZoomLeave() {
            this.zooming = false;
            this.zoomX = 50;
            this.zoomY = 50;
        },
        onZoomMove(e) {
            if (!this.zooming) return;
            const rect = e.currentTarget.getBoundingClientRect();
            const x = ((e.clientX - rect.left) / Math.max(rect.width, 1)) * 100;
            const y = ((e.clientY - rect.top) / Math.max(rect.height, 1)) * 100;
            this.zoomX = Math.min(100, Math.max(0, x));
            this.zoomY = Math.min(100, Math.max(0, y));
        },
     }">

    {{-- Gallery --}}
    <div class="pd-gallery">
        <div class="pd-zoom-stage relative bg-white rounded-xl border border-slate-100 overflow-hidden"
             @mouseenter="onZoomEnter()"
             @mouseleave="onZoomLeave()"
             @mousemove="onZoomMove($event)"
             :class="zooming && 'pd-zoom-stage--active'">
            @if($discountPct > 0)
                <span class="absolute top-2.5 left-2.5 z-20 text-[10px] font-semibold bg-red-500 text-white px-2 py-0.5 rounded pointer-events-none">-{{ $discountPct }}%</span>
            @endif
            <img :src="images[active]"
                 alt="{{ $product->name }}"
                 class="pd-zoom-img absolute inset-0 w-full h-full object-contain bg-white p-1 sm:p-2 select-none"
                 :style="zooming
                    ? `transform: scale(2.35); transform-origin: ${zoomX}% ${zoomY}%;`
                    : 'transform: scale(1); transform-origin: 50% 50%;'"
                 draggable="false">
            <span class="pd-zoom-hint absolute bottom-2.5 right-2.5 z-20 hidden sm:inline-flex items-center gap-1 rounded-md bg-slate-900/70 px-2 py-1 text-[10px] font-medium text-white pointer-events-none"
                  x-show="!zooming" x-cloak>
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"/></svg>
                Hover to zoom
            </span>
        </div>

        @if(count($images) > 1)
            <div class="pd-thumbs">
                @foreach($images as $i => $url)
                    <button type="button"
                            @click="active = {{ $i }}; onZoomLeave()"
                            :class="active === {{ $i }} ? 'border-orange-500 ring-1 ring-orange-200' : 'border-slate-200 hover:border-slate-300'"
                            class="pd-thumb">
                        <img src="{{ $url }}" alt="" class="w-full h-full object-contain p-0.5">
                    </button>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Buy box --}}
    <div class="pd-buy">
        <div class="pd-brand-row">
            @if($product->showsAsNew())
                <span class="text-[10px] font-semibold uppercase bg-emerald-500 text-white px-2 py-0.5 rounded">New</span>
            @endif
            @if($product->brand?->logo_path)
                <img src="{{ public_storage_url($product->brand->logo_path) }}"
                     alt="{{ $product->brand->name }}"
                     class="pd-brand-logo">
            @elseif($product->brand_name || $product->brand)
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wide">{{ $product->brand_name ?? $product->brand?->name }}</span>
            @endif
            @if(($product->brand_name || $product->brand) && $product->brand?->logo_path)
                <span class="text-xs font-medium text-slate-500">{{ $product->brand_name ?? $product->brand?->name }}</span>
            @endif
        </div>

        <h1 class="pd-title">{{ $displayName }}</h1>

        @if($product->rating > 0 || $reviews->isNotEmpty())
            <div class="flex flex-wrap items-center gap-2 mb-2 text-xs text-slate-500">
                @php $stars = $product->rating > 0 ? round($product->rating) : (int) round($reviews->avg('rating')); @endphp
                <span class="flex text-amber-400">
                    @for($i=1;$i<=5;$i++)<span class="{{ $i<=$stars?'':'text-slate-200' }}">★</span>@endfor
                </span>
                <span>({{ $product->review_count ?: $reviews->count() }} Reviews)</span>
            </div>
        @endif

        <div class="mb-3 pb-3 border-b border-slate-100 space-y-1.5">
            <div class="flex flex-wrap items-baseline gap-2">
                <span class="text-2xl font-bold text-slate-900">{{ $ws->formatPrice($currentPrice, $settings) }}</span>
                @if($compareAt)
                    <span class="text-sm text-slate-400 line-through">{{ $ws->formatPrice($compareAt, $settings) }}</span>
                    @if($discountPct > 0)
                        <span class="text-[10px] font-medium text-red-600 bg-red-50 border border-red-100 px-1.5 py-0.5 rounded">{{ $discountPct }}% OFF</span>
                    @endif
                @endif
            </div>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs">
                @if($product->availableStock() > 0)
                    <span class="text-slate-600">Availability:
                        <span class="font-semibold text-emerald-600">In Stock</span>
                        <span class="text-slate-400">({{ $product->availableStock() }} available)</span>
                    </span>
                @else
                    <span class="text-slate-600">Availability:
                        <span class="font-semibold text-red-600">Out of Stock</span>
                    </span>
                @endif
                @if(filled($product->barcode))
                    <span class="text-slate-600">Code:
                        <span class="font-semibold text-slate-800 font-mono">{{ $product->barcode }}</span>
                    </span>
                @endif
            </div>
        </div>

        @if($hasVariantPicker)
            @php
                $hasColors = count($variantOptions['colors'] ?? []) > 0;
                $hasCombos = count($variantOptions['combos'] ?? []) > 0;
                $hasStorages = count($variantOptions['storages'] ?? []) > 0;
                $hasRams = count($variantOptions['rams'] ?? []) > 0;
                $storageBesideColor = $hasColors && ($hasCombos || $hasStorages);
            @endphp
            <div class="pd-variants {{ $storageBesideColor ? 'pd-variants--split' : '' }}">
                @if($hasColors)
                    <div class="pd-variant-block">
                        <p class="pd-variant-label">Color</p>
                        <div class="pd-option-row">
                            @foreach($variantOptions['colors'] as $opt)
                                <a href="{{ $opt['url'] }}"
                                   data-product-variant
                                   data-no-loader
                                   title="{{ $opt['label'] }}{{ empty($opt['available']) ? ' (out of stock)' : '' }}"
                                   class="pd-option {{ !empty($opt['active']) ? 'is-active' : '' }} {{ empty($opt['available']) ? 'is-disabled' : '' }}">
                                    @if(!empty($opt['image']))
                                        <img src="{{ $opt['image'] }}" alt="" class="pd-color-thumb">
                                    @else
                                        <span class="pd-swatch" style="background-color: {{ $opt['hex'] }}"></span>
                                    @endif
                                    <span>{{ $opt['label'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @elseif($product->color)
                    <p class="pd-meta-line">Color: <strong>{{ $product->color }}</strong></p>
                @endif

                @if($hasCombos)
                    <div class="pd-variant-block">
                        <p class="pd-variant-label">Storage</p>
                        <div class="pd-option-row">
                            @foreach($variantOptions['combos'] as $opt)
                                <a href="{{ $opt['url'] }}"
                                   data-product-variant
                                   data-no-loader
                                   class="pd-option pd-option--storage {{ !empty($opt['active']) ? 'is-active' : '' }} {{ empty($opt['available']) ? 'is-disabled' : '' }}">
                                    <span class="pd-option-main">{{ $opt['label'] }}</span>
                                    @if(isset($opt['price']) && empty($opt['active']))
                                        <span class="pd-option-sub">{{ $ws->formatPrice($opt['price'], $settings) }}</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                @elseif($hasStorages || $product->storage)
                    <div class="pd-variant-block">
                        <p class="pd-variant-label">Storage</p>
                        @if($hasStorages)
                            <div class="pd-option-row">
                                @foreach($variantOptions['storages'] as $opt)
                                    <a href="{{ $opt['url'] }}"
                                       data-product-variant
                                       data-no-loader
                                       class="pd-option pd-option--storage {{ !empty($opt['active']) ? 'is-active' : '' }} {{ empty($opt['available']) ? 'is-disabled' : '' }}">
                                        {{ $opt['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <p class="pd-meta-line"><strong>{{ normalize_memory_size($product->storage) ?? $product->storage }}</strong></p>
                        @endif
                    </div>
                @endif

                @if($hasRams)
                    <div class="pd-variant-block pd-variant-block--full">
                        <p class="pd-variant-label">RAM</p>
                        <div class="pd-option-row">
                            @foreach($variantOptions['rams'] as $opt)
                                <a href="{{ $opt['url'] }}"
                                   data-product-variant
                                   data-no-loader
                                   class="pd-option pd-option--storage {{ !empty($opt['active']) ? 'is-active' : '' }} {{ empty($opt['available']) ? 'is-disabled' : '' }}">
                                    {{ $opt['label'] }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @elseif($product->ram && ! $hasCombos)
                    <div class="pd-variant-block pd-variant-block--full">
                        <p class="pd-variant-label">RAM</p>
                        <p class="pd-meta-line"><strong>{{ normalize_memory_size($product->ram) ?? $product->ram }}</strong></p>
                    </div>
                @endif
            </div>
        @else
            @if($product->color || $product->storage || $product->ram)
                <div class="pd-meta-lines">
                    @if($product->color)
                        <p class="pd-meta-line">Color: <strong>{{ $product->color }}</strong></p>
                    @endif
                    @if($product->storage)
                        <p class="pd-meta-line">Storage: <strong>{{ normalize_memory_size($product->storage) ?? $product->storage }}</strong></p>
                    @endif
                    @if($product->ram)
                        <p class="pd-meta-line">RAM: <strong>{{ normalize_memory_size($product->ram) ?? $product->ram }}</strong></p>
                    @endif
                </div>
            @endif
        @endif

        @php
            $availableQty = max(0, (int) $product->availableStock());
            $cartItem = [
                'id' => $product->id,
                'name' => $displayName,
                'price' => $currentPrice,
                'image' => $img,
                'stock' => $availableQty,
            ];
            $listItem = [
                'id' => $product->id,
                'name' => $displayName,
                'price' => $currentPrice,
                'image' => $img,
                'url' => route('website.product', $product),
                'category' => $product->category?->name ?? $product->brand_name ?? 'Electronics',
                'rating' => (float) ($product->rating ?? 0),
                'stock' => $availableQty,
            ];
        @endphp

        <div class="pd-cta">
            @if($availableQty > 0)
                <div class="pd-cta-top">
                    <div class="pd-qty">
                        <button type="button" @click="qty = Math.max(1, qty - 1)" aria-label="Decrease quantity">−</button>
                        <span x-text="qty"></span>
                        <button type="button"
                                @click="qty = Math.min({{ max(1, $availableQty) }}, qty + 1)"
                                :disabled="qty >= {{ max(1, $availableQty) }}"
                                :class="qty >= {{ max(1, $availableQty) }} && 'opacity-40 cursor-not-allowed'"
                                aria-label="Increase quantity">+</button>
                    </div>
                    <button type="button"
                            data-add-to-cart='@json($cartItem)'
                            :data-qty="qty"
                            data-open-cart="0"
                            data-checkout="1"
                            class="pd-btn-primary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        Shop Now
                    </button>
                </div>
                <div class="pd-cta-bottom">
                    <button type="button"
                            data-add-to-cart='@json($cartItem)'
                            :data-qty="qty"
                            data-open-cart="1"
                            class="pd-btn-secondary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        Add To Cart
                    </button>
                    <button type="button"
                            class="pd-btn-wish"
                            :class="inWishlist({{ $product->id }}) && 'is-active'"
                            @click="toggleWishlist(@js($listItem))"
                            :aria-label="inWishlist({{ $product->id }}) ? 'Remove from wishlist' : 'Add to wishlist'">
                        <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                             :fill="inWishlist({{ $product->id }}) ? 'currentColor' : 'none'">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                        </svg>
                    </button>
                </div>
            @else
                <p class="pd-oos">This variant is currently out of stock. Try another color or storage.</p>
                <button type="button"
                        class="pd-btn-wish pd-btn-wish--wide"
                        :class="inWishlist({{ $product->id }}) && 'is-active'"
                        @click="toggleWishlist(@js($listItem))">
                    <svg viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                         :fill="inWishlist({{ $product->id }}) ? 'currentColor' : 'none'" class="w-4 h-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                    </svg>
                    <span x-text="inWishlist({{ $product->id }}) ? 'Wishlisted' : 'Save for later'"></span>
                </button>
            @endif
        </div>

        <div class="pd-trust">
            @foreach([
                ['Fast Delivery', 'COD available'],
                ['30-Day Returns', 'Easy returns'],
                ['1 Year Warranty', 'Manufacturer'],
                ['Secure Checkout', 'Sign in · COD'],
            ] as [$title, $sub])
                <div class="pd-trust-item">
                    <svg class="w-3.5 h-3.5 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold text-slate-800 leading-tight">{{ $title }}</p>
                        <p class="text-[10px] text-slate-500 leading-tight">{{ $sub }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Tabs + related in one denser lower section --}}
@if($product->short_description || $reviews->isNotEmpty() || $related->count())
<section class="pd-lower mt-6 pt-5 border-t border-slate-100">
    @if($product->short_description || $reviews->isNotEmpty())
        <div class="pd-tabs" x-data="{ tab: 'description' }">
            <div class="flex gap-5 border-b border-slate-100 mb-4">
                @if($product->short_description)
                    <button type="button" @click="tab='description'"
                            :class="tab==='description' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500'"
                            class="pb-2 text-sm font-medium border-b-2 transition">Description</button>
                @endif
                @if($reviews->isNotEmpty())
                    <button type="button" @click="tab='reviews'"
                            :class="tab==='reviews' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500'"
                            class="pb-2 text-sm font-medium border-b-2 transition">Reviews ({{ $reviews->count() }})</button>
                @endif
            </div>

            @if($product->short_description)
                <div x-show="tab==='description'">
                    <h3 class="text-sm font-semibold text-slate-900 mb-1.5">About this item</h3>
                    <div class="text-sm text-slate-600 leading-relaxed whitespace-pre-line">{{ $product->short_description }}</div>
                </div>
            @endif

            @if($reviews->isNotEmpty())
                <div x-show="tab==='reviews'" x-cloak class="grid gap-2.5 sm:grid-cols-2">
                    @foreach($reviews as $review)
                        <div class="rounded-lg border border-slate-100 bg-white p-3">
                            <div class="mb-1 text-amber-400 text-xs">{{ str_repeat('★', (int) $review->rating) }}{{ str_repeat('☆', max(0, 5 - (int) $review->rating)) }}</div>
                            <p class="text-sm text-slate-600">“{{ $review->body }}”</p>
                            <p class="mt-1.5 text-xs font-medium text-slate-800">{{ $review->customer_name }}</p>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    @if($related->count())
        <div class="pd-related {{ ($product->short_description || $reviews->isNotEmpty()) ? 'mt-6 pt-5 border-t border-slate-100' : '' }}">
            <h2 class="text-sm font-semibold text-slate-900 mb-3">You may also like</h2>
            <div class="pd-related-grid">
                @foreach($related->take(4) as $rel)
                    @php $relImg = $ws->productImageUrl($rel); @endphp
                    <a href="{{ route('website.product', $rel) }}" class="pd-related-card group">
                        <img src="{{ $relImg }}" alt="" class="pd-related-img">
                        <div class="min-w-0">
                            <p class="text-xs font-medium text-slate-800 group-hover:text-blue-600 line-clamp-2">{{ $rel->storefrontDisplayName() }}</p>
                            <p class="text-xs font-semibold text-blue-600 mt-0.5">{{ $ws->formatPrice($rel->currentPrice(), $settings) }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</section>
@endif
