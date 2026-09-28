@extends('website.layout')

@section('content')
@php
    $viewMode = request('view', 'grid');
@endphp

<script>
window.shopLive = function (config) {
    return {
        endpoint: config.endpoint,
        view: config.view || 'grid',
        sort: (new URLSearchParams(window.location.search)).get('sort') || 'featured',
        loading: false,
        _req: 0,

        buildParams(extra = {}) {
            const form = this.$root.querySelector('[data-shop-filters]');
            const params = new URLSearchParams();

            if (form) {
                const fd = new FormData(form);
                for (const [key, value] of fd.entries()) {
                    if (value === '' || value === null) continue;
                    params.append(key, value);
                }
            }

            const urlParams = new URLSearchParams(window.location.search);
            ['filter', 'search'].forEach((key) => {
                if (!params.has(key) && urlParams.get(key)) {
                    params.set(key, urlParams.get(key));
                }
            });

            if (this.sort && this.sort !== 'featured') {
                params.set('sort', this.sort);
            } else {
                params.delete('sort');
            }

            Object.entries(extra).forEach(([key, value]) => {
                if (value === null || value === undefined || value === '') {
                    params.delete(key);
                } else {
                    params.set(key, value);
                }
            });

            params.delete('ajax');
            if (!Object.prototype.hasOwnProperty.call(extra, 'page')) {
                params.delete('page');
            } else if (extra.page) {
                params.set('page', String(extra.page));
            } else {
                params.delete('page');
            }

            return params;
        },

        async fetchResults(params, { push = true } = {}) {
            const req = ++this._req;
            this.loading = true;
            const box = this.$root.querySelector('[data-shop-results]');
            if (box) box.setAttribute('aria-busy', 'true');

            params.set('ajax', '1');
            const qs = params.toString();
            const fetchUrl = qs ? `${this.endpoint}?${qs}` : `${this.endpoint}?ajax=1`;

            try {
                const res = await fetch(fetchUrl, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                if (!res.ok) throw new Error('Shop filter failed');
                const data = await res.json();
                if (req !== this._req) return;

                if (box && data.html) {
                    const tmp = document.createElement('div');
                    tmp.innerHTML = data.html;

                    const meta = tmp.querySelector('[data-shop-count]');
                    const label = this.$root.querySelector('[data-shop-count-label]');
                    if (label) {
                        label.textContent = data.count_text || (meta ? meta.textContent.trim() : label.textContent);
                    }
                    if (meta) meta.remove();

                    box.innerHTML = tmp.innerHTML;

                    const grid = box.querySelector('[data-shop-grid]');
                    if (grid && this.view === 'list') {
                        grid.classList.add('gs-grid--list');
                    }
                }

                const titleEl = this.$root.querySelector('[data-shop-title]');
                if (titleEl && data.title) titleEl.textContent = data.title;

                params.delete('ajax');
                const cleanQs = params.toString();
                const nextUrl = cleanQs ? `${this.endpoint}?${cleanQs}` : this.endpoint;
                if (push) {
                    history.pushState({ shopLive: true }, '', nextUrl);
                }
            } catch (e) {
                if (req !== this._req) return;
                console.error(e);
            } finally {
                if (req === this._req) {
                    this.loading = false;
                    if (box) box.setAttribute('aria-busy', 'false');
                }
            }
        },

        refresh(extra = {}, options = {}) {
            return this.fetchResults(this.buildParams(extra), options);
        },

        onSortChange() {
            this.refresh();
        },

        setCategory(category) {
            const form = this.$root.querySelector('[data-shop-filters]');
            let input = form && form.querySelector('input[name="category"]');
            if (form && category) {
                if (!input) {
                    input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'category';
                    form.appendChild(input);
                }
                input.value = category;
            } else if (input) {
                input.remove();
            }

            this.$root.querySelectorAll('.gs-cat-link').forEach((link) => {
                const active = category
                    ? String(link.dataset.category || '') === String(category)
                    : link.dataset.category === '';
                link.classList.toggle('is-active', active);
            });

            this.refresh();
        },

        onPopState() {
            const params = new URLSearchParams(window.location.search);
            this.sort = params.get('sort') || 'featured';

            const form = this.$root.querySelector('[data-shop-filters]');
            if (form) {
                form.querySelectorAll('input[type="checkbox"]').forEach((el) => {
                    const values = params.getAll(el.name);
                    el.checked = values.includes(el.value);
                });

                const cat = params.get('category') || '';
                let catInput = form.querySelector('input[name="category"]');
                if (cat) {
                    if (!catInput) {
                        catInput = document.createElement('input');
                        catInput.type = 'hidden';
                        catInput.name = 'category';
                        form.appendChild(catInput);
                    }
                    catInput.value = cat;
                } else if (catInput) {
                    catInput.remove();
                }

                this.$root.querySelectorAll('.gs-cat-link').forEach((link) => {
                    const active = cat
                        ? String(link.dataset.category || '') === String(cat)
                        : link.dataset.category === '';
                    link.classList.toggle('is-active', active);
                });
            }

            this.fetchResults(params, { push: false });
        },
    };
};

document.addEventListener('click', function (event) {
    const root = event.target.closest('.gs-shop');
    if (!root || !root._x_dataStack) return;

    const pageLink = event.target.closest('[data-shop-pagination] a.gs-page');
    if (pageLink) {
        event.preventDefault();
        try {
            const url = new URL(pageLink.href, window.location.origin);
            const page = url.searchParams.get('page') || '1';
            root._x_dataStack[0].refresh({ page });
        } catch (e) {}
        return;
    }

    const cat = event.target.closest('a.gs-cat-link');
    if (cat && root.contains(cat)) {
        event.preventDefault();
        root._x_dataStack[0].setCategory(cat.dataset.category || '');
    }
}, true);

window.bindShopProductScroll = function () {
    const main = document.querySelector('.gs-main');
    if (!main || main.dataset.scrollBound === '1') return;
    main.dataset.scrollBound = '1';

    main.addEventListener('wheel', function (e) {
        if (window.innerWidth <= 900) return;

        const canScroll = main.scrollHeight > main.clientHeight + 2;
        if (!canScroll) return;

        const delta = e.deltaY;
        const atTop = main.scrollTop <= 0;
        const atBottom = main.scrollTop + main.clientHeight >= main.scrollHeight - 2;

        // Still have product content to scroll — keep page locked
        if ((delta > 0 && !atBottom) || (delta < 0 && !atTop)) {
            e.preventDefault();
            main.scrollTop += delta;
        }
        // At end/start in that direction — allow page scroll so footer can appear
    }, { passive: false });
};

document.addEventListener('DOMContentLoaded', window.bindShopProductScroll);
requestAnimationFrame(window.bindShopProductScroll);
</script>

<div class="gs-shop"
     x-data="shopLive({
         endpoint: @js(route('website.shop')),
         view: @js($viewMode),
         title: @js($pageTitle ?? 'Shop'),
     })"
     @shop-refresh="refresh()"
     @popstate.window="onPopState()">
    <div class="gs-shop-inner">
        @if(!empty($showSidebar) && !empty($sidebarFacets))
            @include('website.partials.category-filters')
        @else
            @include('website.partials.shop-sidebar')
        @endif

        <div class="gs-main">
            <nav class="gs-breadcrumb" aria-label="Breadcrumb">
                <a href="{{ route('home') }}">Home</a>
                <span>›</span>
                <span class="gs-breadcrumb-current">Shop</span>
            </nav>

            <div class="gs-toolbar">
                <div class="gs-toolbar-left">
                    <h1 class="gs-title" data-shop-title>{{ $pageTitle ?? 'Shop' }}</h1>
                    <p class="gs-count" data-shop-count-label>
                        @php
                            $from = $products->firstItem() ?? 0;
                            $to = $products->lastItem() ?? 0;
                            $total = $products->total();
                        @endphp
                        Showing {{ $from }}–{{ $to }} of {{ number_format($total) }} products
                    </p>
                </div>

                <div class="gs-toolbar-right">
                    <div class="gs-view-toggle" role="group" aria-label="View mode">
                        <button type="button" class="gs-view-btn" :class="view === 'grid' && 'is-active'" @click="view = 'grid'" title="Grid view">
                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4 4h7v7H4V4zm9 0h7v7h-7V4zM4 13h7v7H4v-7zm9 0h7v7h-7v-7z"/></svg>
                        </button>
                        <button type="button" class="gs-view-btn" :class="view === 'list' && 'is-active'" @click="view = 'list'" title="List view">
                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4 6h16v2H4V6zm0 5h16v2H4v-2zm0 5h16v2H4v-2z"/></svg>
                        </button>
                    </div>

                    <div class="gs-sort">
                        <label for="gs-sort">Sort by:</label>
                        <select id="gs-sort" x-model="sort" @change="onSortChange()" data-no-loader>
                            <option value="featured" @selected(($sort ?? request('sort', 'featured')) === 'featured')>Featured</option>
                            <option value="latest" @selected(($sort ?? '') === 'latest')>Latest</option>
                            <option value="price_asc" @selected(($sort ?? '') === 'price_asc')>Price: Low to High</option>
                            <option value="price_desc" @selected(($sort ?? '') === 'price_desc')>Price: High to Low</option>
                            <option value="name" @selected(($sort ?? '') === 'name')>Name</option>
                            <option value="bestsellers" @selected(($sort ?? '') === 'bestsellers')>Best Sellers</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="gs-results" :class="{ 'is-loading': loading }" data-shop-results aria-busy="false">
                @include('website.partials.shop-results')
            </div>
        </div>
    </div>
</div>
@endsection
