@extends('website.layout')

@php
    $storeName = data_get($settings ?? null, 'store_name') ?: config('app.name');
    $seoTitle = $product->seoTitle().' | '.$storeName;
    $seoDescription = $product->seoDescription();
    $ogImage = $product->og_image
        ? public_storage_url($product->og_image)
        : app(\App\Services\WebsiteService::class)->productImageUrl($product);
    $canonical = route('website.product', $product);
@endphp

@section('title', $seoTitle)

@section('meta')
    @if($seoDescription !== '')
        <meta name="description" content="{{ $seoDescription }}">
    @endif
    <link rel="canonical" href="{{ $canonical }}">
    <meta property="og:type" content="product">
    <meta property="og:site_name" content="{{ $storeName }}">
    <meta property="og:title" content="{{ $product->og_title ?: $product->seoTitle() }}">
    <meta property="og:description" content="{{ $product->og_description ?: $seoDescription }}">
    <meta property="og:url" content="{{ $canonical }}">
    @if($ogImage)
        <meta property="og:image" content="{{ $ogImage }}">
    @endif
    <meta property="product:price:amount" content="{{ number_format($product->currentPrice(), 2, '.', '') }}">
    <meta property="product:price:currency" content="{{ data_get($settings ?? null, 'currency_code', 'BDT') }}">
    <meta name="twitter:card" content="summary_large_image">
@endsection

@section('content')
<script>
window.productLive = function () {
    return {
        loading: false,
        _req: 0,

        async loadVariant(url, { push = true } = {}) {
            if (!url) return;
            const req = ++this._req;
            this.loading = true;

            try {
                const fetchUrl = url.includes('?') ? `${url}&ajax=1` : `${url}?ajax=1`;
                const res = await fetch(fetchUrl, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                if (!res.ok) throw new Error('Variant load failed');
                const data = await res.json();
                if (req !== this._req) return;

                const box = this.$root.querySelector('[data-product-live]');
                if (box && data.html) {
                    box.innerHTML = data.html;
                    if (window.Alpine && typeof Alpine.initTree === 'function') {
                        Alpine.initTree(box);
                    }
                }

                if (data.title) {
                    document.title = data.title;
                }

                if (push && data.url) {
                    history.pushState({ productLive: true }, '', data.url);
                }
            } catch (e) {
                if (req !== this._req) return;
                window.location.href = url;
            } finally {
                if (req === this._req) this.loading = false;
            }
        },

        onPopState() {
            if (!/\/product\//.test(window.location.pathname)) return;
            this.loadVariant(window.location.href, { push: false });
        },
    };
};

document.addEventListener('click', function (event) {
    const link = event.target.closest('a[data-product-variant]');
    if (!link) return;

    const root = link.closest('[data-product-live-root]');
    if (!root || !root._x_dataStack) return;

    event.preventDefault();
    event.stopPropagation();
    root._x_dataStack[0].loadVariant(link.href);
}, true);
</script>

<div class="max-w-7xl mx-auto px-4 py-3 sm:py-4"
     data-product-live-root
     x-data="productLive()"
     :class="{ 'pd-live-loading': loading }"
     @popstate.window="onPopState()">
    <div data-product-live>
        @include('website.partials.product-live')
    </div>
</div>
@endsection
