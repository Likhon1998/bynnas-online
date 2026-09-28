@extends('website.layout')

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
