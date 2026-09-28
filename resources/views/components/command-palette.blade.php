{{-- Spotlight / command palette for admin header search --}}
@php
    $user = auth()->user();
    $retail = retail_enabled();
    $quickActions = collect([
        $user->can('view dashboard') ? ['id' => 'act-dashboard', 'title' => 'Dashboard', 'subtitle' => 'Business summary', 'url' => route('dashboard'), 'icon' => 'dashboard', 'keywords' => 'home overview'] : null,
        $retail && $user->can('process pos sales') ? ['id' => 'act-pos', 'title' => 'Launch POS Terminal', 'subtitle' => 'Open full-size register window', 'url' => route('pos.index'), 'icon' => 'pos', 'keywords' => 'pos sell checkout register', 'target' => 'pos'] : null,

        // Inventory
        $user->can('manage inventory') ? ['id' => 'act-products', 'title' => 'Products', 'subtitle' => 'Inventory list', 'url' => route('products.index'), 'icon' => 'product', 'keywords' => 'inventory stock sku'] : null,
        $user->can('manage inventory') ? ['id' => 'act-add-product', 'title' => 'Add Product', 'subtitle' => 'Create a new SKU', 'url' => route('products.create'), 'icon' => 'plus', 'keywords' => 'new create product'] : null,
        $user->can('manage inventory') ? ['id' => 'act-categories', 'title' => 'Categories', 'subtitle' => 'Product categories & icons', 'url' => route('categories.index'), 'icon' => 'category', 'keywords' => 'category icon new arrival'] : null,
        $user->can('manage inventory') ? ['id' => 'act-brands', 'title' => 'Brands', 'subtitle' => 'Brand directory', 'url' => route('brands.index'), 'icon' => 'brand', 'keywords' => 'brand logo'] : null,
        $user->can('manage inventory') ? ['id' => 'act-import', 'title' => 'Import CSV Products', 'subtitle' => 'Bulk upload catalog', 'url' => route('products.import'), 'icon' => 'plus', 'keywords' => 'csv import upload'] : null,
        $user->can('manage inventory') ? ['id' => 'act-barcodes', 'title' => 'Print Barcodes', 'subtitle' => 'Label printing', 'url' => route('products.barcodes'), 'icon' => 'product', 'keywords' => 'barcode label print'] : null,
        $user->can('manage inventory') ? ['id' => 'act-low-stock', 'title' => 'Low Stock', 'subtitle' => 'Items at reorder level', 'url' => route('reports.low_stock'), 'icon' => 'alert', 'keywords' => 'reorder alert inventory'] : null,
        $user->can('manage inventory') ? ['id' => 'act-suppliers', 'title' => 'Suppliers', 'subtitle' => 'Vendor directory', 'url' => route('supply.suppliers.index'), 'icon' => 'supplier', 'keywords' => 'vendor supplier'] : null,
        $user->can('manage inventory') ? ['id' => 'act-po', 'title' => 'Purchase Orders', 'subtitle' => 'Buy stock from suppliers', 'url' => route('supply.purchase-orders.index'), 'icon' => 'order', 'keywords' => 'purchase po supply'] : null,
        $user->can('manage inventory') ? ['id' => 'act-opening-stock', 'title' => 'Opening Inventory', 'subtitle' => 'Set opening stock', 'url' => route('supply.opening-inventory.index'), 'icon' => 'product', 'keywords' => 'opening stock'] : null,
        $user->can('manage inventory') ? ['id' => 'act-adjust', 'title' => 'Stock Adjustment', 'subtitle' => 'Correct stock counts', 'url' => route('supply.adjustments.index'), 'icon' => 'alert', 'keywords' => 'adjust stock'] : null,

        // Sales
        $retail && $user->can('view sales ledger') ? ['id' => 'act-sales', 'title' => 'Sales Ledger', 'subtitle' => 'POS & online sales', 'url' => route('sales.index'), 'icon' => 'order', 'keywords' => 'orders invoices'] : null,
        $user->can('view sales ledger') && $user->isAdminUser() ? ['id' => 'act-online', 'title' => 'Online Orders', 'subtitle' => 'Web checkout queue', 'url' => route('online-orders.index'), 'icon' => 'globe', 'keywords' => 'web pending new arrival online'] : null,
        $user->can('view sales ledger') || $user->can('process pos sales') ? ['id' => 'act-customers', 'title' => 'Customers', 'subtitle' => 'Customer directory', 'url' => route('customers.index'), 'icon' => 'customer', 'keywords' => 'people phone'] : null,

        // Sessions / finance / reports
        $retail ? ['id' => 'act-sessions', 'title' => 'Cash Sessions', 'subtitle' => 'Open / close tills', 'url' => route('counters.sessions.index'), 'icon' => 'cash', 'keywords' => 'till float open close'] : null,
        $user->isAdminUser() ? ['id' => 'act-accounts', 'title' => 'Accounts', 'subtitle' => 'Chart, ledger, petty cash', 'url' => route('accounts.opening-balance'), 'icon' => 'accounts', 'keywords' => 'ledger bookkeeping'] : null,
        $user->isAdminUser() ? ['id' => 'act-reports', 'title' => 'Reports', 'subtitle' => 'Sales analytics', 'url' => route('analytics.overview'), 'icon' => 'chart', 'keywords' => 'analytics report'] : null,
        $retail && $user->isAdminUser() ? ['id' => 'act-top-sellers', 'title' => 'Top Sellers', 'subtitle' => 'Staff performance', 'url' => route('reports.staff_performance'), 'icon' => 'chart', 'keywords' => 'staff performance sellers'] : null,

        // CMS
        $user->can('manage website') ? ['id' => 'act-cms-landing', 'title' => 'Landing Page', 'subtitle' => 'Store branding & features', 'url' => route('cms.landing.edit'), 'icon' => 'page', 'keywords' => 'cms website landing'] : null,
        $user->can('manage website') ? ['id' => 'act-cms-slides', 'title' => 'Home Slides', 'subtitle' => 'Hero banners', 'url' => route('cms.slides.index'), 'icon' => 'page', 'keywords' => 'cms slides banner'] : null,
        $user->can('manage website') ? ['id' => 'act-cms-pages', 'title' => 'CMS Pages', 'subtitle' => 'About, policies…', 'url' => route('cms.pages.index'), 'icon' => 'page', 'keywords' => 'cms pages'] : null,
        $user->can('manage website') ? ['id' => 'act-cms-blogs', 'title' => 'Blogs', 'subtitle' => 'Blog posts', 'url' => route('cms.blogs.index'), 'icon' => 'blog', 'keywords' => 'cms blog'] : null,
        $user->can('manage website') ? ['id' => 'act-cms-faqs', 'title' => 'FAQs', 'subtitle' => 'Help content', 'url' => route('cms.faqs.index'), 'icon' => 'page', 'keywords' => 'cms faq help'] : null,
        $user->can('manage website') ? ['id' => 'act-cms-contact', 'title' => 'Contact Messages', 'subtitle' => 'Inbox from storefront', 'url' => route('cms.contact.index'), 'icon' => 'customer', 'keywords' => 'cms contact message'] : null,
        $user->can('manage website') ? ['id' => 'act-cms-reviews', 'title' => 'Reviews', 'subtitle' => 'Customer reviews', 'url' => route('cms.reviews.index'), 'icon' => 'blog', 'keywords' => 'cms review'] : null,

        // Access control
        $user->can('manage roles') ? ['id' => 'act-roles', 'title' => 'Roles', 'subtitle' => 'Permissions', 'url' => route('roles.index'), 'icon' => 'staff', 'keywords' => 'role permission access'] : null,
        $user->can('manage staff') ? ['id' => 'act-staff', 'title' => 'Staff', 'subtitle' => 'Team members', 'url' => route('staff.index'), 'icon' => 'staff', 'keywords' => 'employee user'] : null,
        $retail && $user->can('manage counters') ? ['id' => 'act-counters', 'title' => 'Counters', 'subtitle' => 'POS counters', 'url' => route('counters.index'), 'icon' => 'cash', 'keywords' => 'counter till'] : null,

        ['id' => 'act-store', 'title' => 'View Store', 'subtitle' => 'Open storefront', 'url' => route('home'), 'icon' => 'globe', 'keywords' => 'website shop new arrivals', 'target' => '_blank'],
        ['id' => 'act-profile', 'title' => 'Profile', 'subtitle' => 'Your account settings', 'url' => route('profile.edit'), 'icon' => 'staff', 'keywords' => 'profile password'],
    ])->filter()->values();
@endphp

<div x-data="commandPalette({
        searchUrl: @js(route('global-search')),
        actions: @js($quickActions),
    })"
     @keydown.window="onGlobalKey($event)"
     @open-command-palette.window="open()"
     class="contents">

    <div class="hidden md:flex flex-1 max-w-xl mx-auto">
        <button type="button"
                @click="open()"
                class="group relative flex w-full items-center gap-3 rounded-xl border border-slate-200/90 bg-slate-50/90 px-3.5 py-2.5 text-left shadow-sm transition hover:border-blue-300 hover:bg-white hover:shadow-md hover:shadow-blue-100/50 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-400">
            <svg class="h-4 w-4 shrink-0 text-slate-400 group-hover:text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <span class="flex-1 truncate text-[13px] text-slate-400 group-hover:text-slate-500">Search products, orders, customers, categories…</span>
            <span class="hidden sm:inline-flex items-center gap-1">
                <kbd class="rounded-md border border-slate-200 bg-white px-1.5 py-0.5 text-[10px] font-semibold text-slate-400 shadow-sm" x-text="modLabel"></kbd>
                <kbd class="rounded-md border border-slate-200 bg-white px-1.5 py-0.5 text-[10px] font-semibold text-slate-400 shadow-sm">K</kbd>
            </span>
        </button>
    </div>

    <button type="button"
            class="md:hidden inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500"
            @click="open()"
            title="Search">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
    </button>

    <template x-teleport="body">
        <div x-show="isOpen"
             x-cloak
             class="fixed inset-0 z-[100] flex items-start justify-center px-4 pt-[12vh] sm:pt-[14vh]"
             role="dialog"
             aria-modal="true"
             aria-label="Command palette">
            <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-[2px]"
                 x-show="isOpen"
                 x-transition:enter="ease-out duration-150"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-100"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="close()"></div>

            <div class="relative w-full max-w-xl overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-2xl shadow-slate-900/25 ring-1 ring-black/5"
                 x-show="isOpen"
                 x-transition:enter="ease-out duration-150"
                 x-transition:enter-start="opacity-0 translate-y-2 scale-[0.98]"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="ease-in duration-100"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 translate-y-1 scale-[0.98]"
                 @click.stop>
                <div class="flex items-center gap-3 border-b border-slate-100 px-4 py-3">
                    <svg class="h-5 w-5 shrink-0 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="text"
                           x-ref="query"
                           x-model="query"
                           @input.debounce.200ms="search()"
                           @keydown.down.prevent="move(1)"
                           @keydown.up.prevent="move(-1)"
                           @keydown.enter.prevent="go()"
                           @keydown.escape.prevent="close()"
                           placeholder="Search anything you can access…"
                           class="w-full border-0 bg-transparent p-0 text-[15px] font-medium text-slate-800 placeholder:text-slate-400 focus:ring-0"
                           autocomplete="off"
                           spellcheck="false">
                    <kbd class="hidden sm:inline-flex rounded-md border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-[10px] font-semibold text-slate-400">esc</kbd>
                </div>

                <div class="max-h-[min(60vh,420px)] overflow-y-auto overscroll-contain py-2" @mouseleave="activeIndex = -1">
                    <template x-if="loading">
                        <p class="px-4 py-8 text-center text-[12px] text-slate-400">Searching…</p>
                    </template>

                    <template x-if="!loading && flatItems.length === 0">
                        <div class="px-4 py-10 text-center">
                            <p class="text-[13px] font-semibold text-slate-700" x-text="query.trim() ? 'No matches' : 'Start typing'"></p>
                            <p class="mt-1 text-[12px] text-slate-400" x-text="query.trim() ? 'Try a product, invoice, category, customer, or page name' : 'Jump to any page you have access to'"></p>
                        </div>
                    </template>

                    <template x-for="(group, gi) in visibleGroups" :key="group.key">
                        <div class="mb-1">
                            <p class="px-4 py-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400" x-text="group.label"></p>
                            <template x-for="(item, ii) in group.items" :key="item.id">
                                <button type="button"
                                        @click="go(item)"
                                        @mouseenter="activeIndex = flatIndex(gi, ii)"
                                        class="flex w-full items-center gap-3 px-3 py-2 text-left transition"
                                        :class="activeIndex === flatIndex(gi, ii) ? 'bg-blue-50' : 'hover:bg-slate-50'">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-slate-100 bg-slate-50 text-slate-500"
                                         :class="activeIndex === flatIndex(gi, ii) ? 'border-blue-200 bg-white text-blue-600' : ''">
                                        <template x-if="item.image">
                                            <img :src="item.image" alt="" class="h-full w-full object-cover">
                                        </template>
                                        <template x-if="!item.image">
                                            <span x-html="iconSvg(item.icon)" class="flex h-4 w-4 items-center justify-center"></span>
                                        </template>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-[13px] font-semibold text-slate-800" x-text="item.title"></p>
                                        <p class="truncate text-[11px] text-slate-400" x-text="item.subtitle"></p>
                                    </div>
                                    <span class="shrink-0 text-[11px] font-bold text-slate-500" x-text="item.meta || ''"></span>
                                    <span class="shrink-0 text-[10px] font-semibold text-slate-300" x-show="activeIndex === flatIndex(gi, ii)">↵</span>
                                </button>
                            </template>
                        </div>
                    </template>
                </div>

                <div class="flex flex-wrap items-center gap-x-3 gap-y-1 border-t border-slate-100 bg-slate-50/80 px-4 py-2 text-[10px] font-semibold text-slate-400">
                    <span><kbd class="rounded border border-slate-200 bg-white px-1 py-0.5">↑↓</kbd> navigate</span>
                    <span><kbd class="rounded border border-slate-200 bg-white px-1 py-0.5">↵</kbd> open</span>
                    <span><kbd class="rounded border border-slate-200 bg-white px-1 py-0.5">esc</kbd> close</span>
                    <span class="ml-auto hidden sm:inline">Also <span x-text="modLabel"></span> + /</span>
                </div>
            </div>
        </div>
    </template>
</div>

<script>
function commandPalette({ searchUrl, actions }) {
    return {
        isOpen: false,
        query: '',
        loading: false,
        groups: [],
        activeIndex: 0,
        abort: null,
        modLabel: /Mac|iPhone|iPad/.test(navigator.platform || navigator.userAgent) ? '⌘' : 'Ctrl',
        actions: actions || [],

        get visibleGroups() {
            if (!this.query.trim()) {
                return [{ key: 'actions', label: 'Quick actions', items: this.actions }];
            }

            const q = this.query.trim().toLowerCase();
            const matchedActions = this.actions.filter((a) => {
                const hay = `${a.title} ${a.subtitle || ''} ${a.keywords || ''}`.toLowerCase();
                return hay.includes(q);
            });

            const out = [];
            if (matchedActions.length) {
                out.push({ key: 'actions', label: 'Quick actions', items: matchedActions });
            }
            (this.groups || []).forEach((g) => out.push(g));
            return out;
        },

        get flatItems() {
            return this.visibleGroups.flatMap((g) => g.items);
        },

        flatIndex(gi, ii) {
            let n = 0;
            for (let i = 0; i < gi; i++) n += this.visibleGroups[i].items.length;
            return n + ii;
        },

        open() {
            this.isOpen = true;
            this.query = '';
            this.groups = [];
            this.activeIndex = 0;
            this.$nextTick(() => this.$refs.query?.focus());
        },

        close() {
            this.isOpen = false;
            this.loading = false;
            if (this.abort) {
                this.abort.abort();
                this.abort = null;
            }
        },

        onGlobalKey(e) {
            const key = (e.key || '').toLowerCase();
            const mod = e.metaKey || e.ctrlKey;
            if (mod && (key === 'k' || key === '/')) {
                e.preventDefault();
                if (this.isOpen) this.close();
                else this.open();
                return;
            }
            if (key === 'escape' && this.isOpen) {
                e.preventDefault();
                this.close();
            }
        },

        move(delta) {
            const len = this.flatItems.length;
            if (!len) return;
            this.activeIndex = (this.activeIndex + delta + len) % len;
        },

        go(item) {
            const target = item || this.flatItems[this.activeIndex];
            if (!target?.url) return;
            this.close();
            if (target.target === 'pos' && typeof window.launchPosTerminal === 'function') {
                window.launchPosTerminal(target.url);
            } else if (target.target === '_blank') {
                window.open(target.url, '_blank', 'noopener');
            } else {
                window.location.href = target.url;
            }
        },

        async search() {
            const q = this.query.trim();
            if (!q) {
                this.groups = [];
                this.loading = false;
                this.activeIndex = 0;
                return;
            }

            if (this.abort) this.abort.abort();
            this.abort = new AbortController();
            this.loading = true;

            try {
                const res = await fetch(`${searchUrl}?q=${encodeURIComponent(q)}`, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    signal: this.abort.signal,
                });
                if (!res.ok) throw new Error('search failed');
                const data = await res.json();
                this.groups = data.groups || [];
                this.activeIndex = 0;
            } catch (err) {
                if (err.name !== 'AbortError') this.groups = [];
            } finally {
                this.loading = false;
            }
        },

        iconSvg(name) {
            const paths = {
                dashboard: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
                pos: 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z',
                product: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
                plus: 'M12 4v16m8-8H4',
                customer: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
                order: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                globe: 'M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9',
                cash: 'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z',
                accounts: 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z',
                chart: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                alert: 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
                category: 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z',
                brand: 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z',
                supplier: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
                page: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                blog: 'M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z',
                staff: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
            };
            const d = paths[name] || paths.product;
            return `<svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${d}"/></svg>`;
        },
    };
}
</script>
