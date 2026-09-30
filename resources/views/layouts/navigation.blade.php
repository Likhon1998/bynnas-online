<div class="admin-sidebar-backdrop"
     :class="sidebarOpen ? 'is-visible' : ''"
     @click="sidebarOpen = false"></div>

<aside :class="{
           'is-mobile-open': sidebarOpen,
           'is-collapsed': sidebarCollapsed
       }"
       class="admin-sidebar">

    <div class="sidebar-head flex items-center gap-2 shrink-0">
        @php
            $navSettings = \App\Models\SiteSetting::current();
            $navName = $navSettings->store_name
                ?: (Auth::user()->shop->name ?? config('app.name', 'Bynnas Social'));
            $navIcon = $navSettings->favicon_path
                ? public_storage_url($navSettings->favicon_path)
                : ($navSettings->logo_path ? public_storage_url($navSettings->logo_path) : null);
        @endphp
        <a href="{{ route('dashboard') }}" @click="sidebarOpen = false" class="sidebar-brand-link flex items-center gap-2.5 min-w-0 flex-1 overflow-hidden" :title='sidebarCollapsed ? @json($navName) : null'>
            @if($navIcon)
                <img src="{{ $navIcon }}?v={{ @filemtime(public_storage_path($navSettings->favicon_path ?: $navSettings->logo_path)) ?: time() }}" alt="" class="sidebar-brand-mark h-8 w-8 object-contain shrink-0 rounded-lg" width="32" height="32">
                <span class="sidebar-brand-text truncate" x-show="!sidebarCollapsed">{{ $navName }}</span>
            @else
                <span class="sidebar-brand-mark sidebar-brand-lottie flex h-8 w-8 shrink-0 items-center justify-center rounded-full">
                    @include('website.partials.lottie', ['name' => 'bear'])
                </span>
                <span class="sidebar-brand-text truncate" x-show="!sidebarCollapsed">{{ $navName }}</span>
            @endif
        </a>

        <button type="button"
                @click="toggleSidebarCollapse()"
                class="sidebar-collapse-btn shrink-0"
                :title="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                :aria-label="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                :aria-expanded="(!sidebarCollapsed).toString()">
            <svg class="w-3.5 h-3.5 transition-transform duration-300" :class="sidebarCollapsed ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.25" d="M15 19l-7-7 7-7"/>
            </svg>
        </button>

        <button type="button" @click="sidebarOpen = false" class="admin-sidebar-close h-7 w-7 items-center justify-center rounded-full text-slate-400 hover:bg-blue-50 hover:text-blue-600" aria-label="Close menu">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    @php
        $isCatalog = request()->routeIs('brands.*', 'categories.*')
            || request()->routeIs('products.create', 'products.import*', 'products.barcodes*', 'products.variants', 'attributes.*');
        $isInventory = request()->routeIs('supply.*', 'stock.*');
        $isSalesTools = request()->routeIs('pos.settings.*', 'counters.sessions.*');
        $isCredit = request()->routeIs('customers.baki.*', 'customers.emi.*');
        $isInsights = request()->routeIs('analytics.*', 'reports.daily', 'reports.daily_by_brand', 'reports.staff_performance', 'reports.staff_daily_details', 'pos.settings.*');
        $isWebsite = request()->routeIs('cms.*');
        $isTeam = request()->routeIs('roles.*', 'staff.*') || request()->routeIs('counters.index', 'counters.store', 'counters.update');
        $retailEnabled = retail_enabled();
        $pendingWebOrders = 0;
        if (Auth::user()->can('manage orders')) {
            $pendingWebOrders = \App\Models\Order::where('shop_id', Auth::user()->shop_id)
                ->onlineOrders()
                ->whereIn('status', \App\Support\OrderStatus::newValues())
                ->count();
        }
    @endphp

    <nav class="admin-scroll-hide admin-sidebar-nav flex-1 overflow-y-auto"
         @click="if ($event.target.closest('a')) sidebarOpen = false"
         x-init="
            catalogOpen = {{ $isCatalog ? 'true' : 'false' }};
            inventoryOpen = {{ $isInventory ? 'true' : 'false' }};
            salesOpen = {{ $isSalesTools ? 'true' : 'false' }};
            customersOpen = {{ $isCredit ? 'true' : 'false' }};
            insightsOpen = {{ $isInsights ? 'true' : 'false' }};
            websiteOpen = {{ $isWebsite ? 'true' : 'false' }};
            teamOpen = {{ $isTeam ? 'true' : 'false' }};
         ">

        {{-- Daily essentials --}}
        @can('view dashboard')
        <a :title="sidebarCollapsed ? 'Dashboard' : null" href="{{ route('dashboard') }}"
           class="nav-link nav-tone-dash {{ request()->routeIs('dashboard') ? 'is-active' : '' }}">
            <span class="nav-ico-wrap"><svg class="nav-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg></span>
            <span class="nav-label">Dashboard</span>
        </a>
        @endcan

        @if($retailEnabled && Auth::user()->can('view sales ledger'))
        <a :title="sidebarCollapsed ? 'Sales' : null" href="{{ route('sales.index') }}"
           class="nav-link nav-tone-sales {{ request()->routeIs('sales.*') ? 'is-active' : '' }}">
            <span class="nav-ico-wrap"><svg class="nav-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></span>
            <span class="nav-label">Sales</span>
        </a>
        @endif
        @can('manage orders')
        <a :title="sidebarCollapsed ? 'Online Orders' : null" href="{{ route('online-orders.index') }}"
           class="nav-link nav-link--badge nav-tone-orders {{ request()->routeIs('online-orders.*') ? 'is-active' : '' }}">
            <span class="nav-ico-wrap"><svg class="nav-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg></span>
            <span class="nav-label">Online Orders</span>
            @if($pendingWebOrders > 0)
                <span class="nav-badge">{{ $pendingWebOrders }}</span>
            @endif
        </a>
        @endcan

        @can('manage inventory')
        <a :title="sidebarCollapsed ? 'Products' : null" href="{{ route('products.index') }}"
           class="nav-link nav-tone-products {{ request()->routeIs('products.index', 'products.edit', 'products.show') ? 'is-active' : '' }}">
            <span class="nav-ico-wrap"><svg class="nav-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg></span>
            <span class="nav-label">Products</span>
        </a>
        @endcan

        @can('manage customers')
        <a :title="sidebarCollapsed ? 'Customers' : null" href="{{ route('customers.index') }}"
           class="nav-link nav-tone-customers {{ request()->routeIs('customers.index', 'customers.create', 'customers.edit', 'customers.show') ? 'is-active' : '' }}">
            <span class="nav-ico-wrap"><svg class="nav-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg></span>
            <span class="nav-label">Customers</span>
        </a>
        @endcan

        @can('manage leads')
        @php
            $leadsNeedingAction = \App\Models\Lead::forShop((int) Auth::user()->shop_id)
                ->where(fn ($q) => $q->where('status', 'new')->orWhere(fn ($d) => $d->followUpDue()))
                ->count();
        @endphp
        <a :title="sidebarCollapsed ? 'Leads' : null" href="{{ route('leads.index') }}"
           class="nav-link nav-link--badge nav-tone-customers {{ request()->routeIs('leads.*') ? 'is-active' : '' }}">
            <span class="nav-ico-wrap"><svg class="nav-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg></span>
            <span class="nav-label">Leads</span>
            @if($leadsNeedingAction > 0)
                <span class="nav-badge">{{ $leadsNeedingAction }}</span>
            @endif
        </a>
        <a :title="sidebarCollapsed ? 'Abandoned carts' : null" href="{{ route('abandoned-carts.index') }}"
           class="nav-link nav-tone-customers {{ request()->routeIs('abandoned-carts.*') ? 'is-active' : '' }}">
            <span class="nav-ico-wrap"><svg class="nav-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0zM13 9l-3 3m0-3l3 3"/></svg></span>
            <span class="nav-label">Abandoned carts</span>
        </a>
        @endcan

        @can('manage campaigns')
        <a :title="sidebarCollapsed ? 'Campaigns' : null" href="{{ route('campaigns.index') }}"
           class="nav-link nav-tone-customers {{ request()->routeIs('campaigns.*') ? 'is-active' : '' }}">
            <span class="nav-ico-wrap"><svg class="nav-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg></span>
            <span class="nav-label">Campaigns</span>
        </a>
        <a :title="sidebarCollapsed ? 'Landing pages' : null" href="{{ route('landing-pages.index') }}"
           class="nav-link nav-tone-customers {{ request()->routeIs('landing-pages.*') ? 'is-active' : '' }}">
            <span class="nav-ico-wrap"><svg class="nav-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v14a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm0 4h16M9 13h6m-6 3h4"/></svg></span>
            <span class="nav-label">Landing pages</span>
        </a>
        @endcan

        @can('manage accounts')
        <a :title="sidebarCollapsed ? 'Accounts' : null" href="{{ route('accounts.opening-balance') }}"
           class="nav-link nav-tone-accounts {{ request()->routeIs('accounts.*') ? 'is-active' : '' }}">
            <span class="nav-ico-wrap"><svg class="nav-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg></span>
            <span class="nav-label">Accounts</span>
        </a>
        @endcan

        @can('manage inventory')
        <a :title="sidebarCollapsed ? 'Low Stock' : null" href="{{ route('reports.low_stock') }}"
           class="nav-link nav-tone-alert {{ request()->routeIs('reports.low_stock') ? 'is-active' : '' }}">
            <span class="nav-ico-wrap"><svg class="nav-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg></span>
            <span class="nav-label">Low Stock</span>
        </a>
        @endcan

        @if($retailEnabled && (Auth::user()->can('manage counters') || Auth::user()->can('process pos sales')))
        <a :title="sidebarCollapsed ? 'Cash Sessions' : null" href="{{ route('counters.sessions.index') }}"
           class="nav-link nav-tone-cash {{ request()->routeIs('counters.sessions.*') ? 'is-active' : '' }}">
            <span class="nav-ico-wrap"><svg class="nav-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg></span>
            <span class="nav-label">Cash Sessions</span>
        </a>
        @endif

        <div class="nav-divider" aria-hidden="true"></div>

        {{-- Secondary / setup --}}
        @can('manage inventory')
        <button type="button" :title="sidebarCollapsed ? 'Catalog' : null" @click="expandThen('catalogOpen')"
                class="nav-group nav-tone-catalog {{ $isCatalog ? 'is-open' : '' }}">
            <span class="nav-group-main">
                <span class="nav-ico-wrap"><svg class="nav-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg></span>
                <span class="nav-label">Catalog</span>
            </span>
            <svg class="nav-chevron" :class="catalogOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-show="catalogOpen && !sidebarCollapsed" x-cloak class="nav-submenu">
            <a href="{{ route('brands.index') }}" class="nav-sub {{ request()->routeIs('brands.*') ? 'is-active' : '' }}"><svg class="nav-sub-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/></svg><span>Brands</span></a>
            <a href="{{ route('categories.index') }}" class="nav-sub {{ request()->routeIs('categories.*') ? 'is-active' : '' }}"><svg class="nav-sub-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg><span>Categories</span></a>
            <a href="{{ route('products.create') }}" class="nav-sub {{ request()->routeIs('products.create') ? 'is-active' : '' }}"><svg class="nav-sub-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 4v16m8-8H4"/></svg><span>Add Product</span></a>
            <a href="{{ route('products.variants') }}" class="nav-sub {{ request()->routeIs('products.variants') ? 'is-active' : '' }}"><svg class="nav-sub-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 6h16M4 10h16M4 14h10M4 18h10"/></svg><span>Variants</span></a>
            <a href="{{ route('attributes.index') }}" class="nav-sub {{ request()->routeIs('attributes.*') ? 'is-active' : '' }}"><svg class="nav-sub-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg><span>Attributes</span></a>
            <a href="{{ route('products.import') }}" class="nav-sub {{ request()->routeIs('products.import*') ? 'is-active' : '' }}"><svg class="nav-sub-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg><span>Import CSV</span></a>
            <a href="{{ route('products.barcodes') }}" class="nav-sub {{ request()->routeIs('products.barcodes*') ? 'is-active' : '' }}"><svg class="nav-sub-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 7v10M8 4v16M12 7v10M16 5v14M20 8v8"/></svg><span>Barcodes</span></a>
        </div>

        <button type="button" :title="sidebarCollapsed ? 'Inventory' : null" @click="expandThen('inventoryOpen')"
                class="nav-group nav-tone-inventory {{ $isInventory ? 'is-open' : '' }}">
            <span class="nav-group-main">
                <span class="nav-ico-wrap"><svg class="nav-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg></span>
                <span class="nav-label">Inventory</span>
            </span>
            <svg class="nav-chevron" :class="inventoryOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-show="inventoryOpen && !sidebarCollapsed" x-cloak class="nav-submenu">
            <a href="{{ route('supply.purchase-orders.index') }}" class="nav-sub {{ request()->routeIs('supply.purchase-orders.*') ? 'is-active' : '' }}"><svg class="nav-sub-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10m10 0h6m-6 0H9m10 0v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0016 8h-3"/></svg><span>Purchase Orders</span></a>
            <a href="{{ route('supply.opening-inventory.index') }}" class="nav-sub {{ request()->routeIs('supply.opening-inventory.*') ? 'is-active' : '' }}"><svg class="nav-sub-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg><span>Opening Stock</span></a>
            <a href="{{ route('supply.reorder-levels.index') }}" class="nav-sub {{ request()->routeIs('supply.reorder-levels.*') ? 'is-active' : '' }}"><svg class="nav-sub-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg><span>Reorder Levels</span></a>
            <a href="{{ route('supply.purchase-returns.index') }}" class="nav-sub {{ request()->routeIs('supply.purchase-returns.*') ? 'is-active' : '' }}"><svg class="nav-sub-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg><span>Purchase Returns</span></a>
            <a href="{{ route('supply.adjustments.index') }}" class="nav-sub {{ request()->routeIs('supply.adjustments.*', 'stock.*') ? 'is-active' : '' }}"><svg class="nav-sub-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg><span>Adjustments</span></a>
            <a href="{{ route('supply.stock-transfers.index') }}" class="nav-sub {{ request()->routeIs('supply.stock-transfers.*') ? 'is-active' : '' }}"><svg class="nav-sub-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg><span>Transfers</span></a>
            <a href="{{ route('supply.damage-products.index') }}" class="nav-sub {{ request()->routeIs('supply.damage-products.*') ? 'is-active' : '' }}"><svg class="nav-sub-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg><span>Damaged Goods</span></a>
            <a href="{{ route('supply.suppliers.index') }}" class="nav-sub {{ request()->routeIs('supply.suppliers.*') ? 'is-active' : '' }}"><svg class="nav-sub-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg><span>Suppliers</span></a>
            <a href="{{ route('supply.warehouses.index') }}" class="nav-sub {{ request()->routeIs('supply.warehouses.*') ? 'is-active' : '' }}"><svg class="nav-sub-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg><span>Warehouses</span></a>
            <a href="{{ route('supply.stores.index') }}" class="nav-sub {{ request()->routeIs('supply.stores.*') ? 'is-active' : '' }}"><svg class="nav-sub-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg><span>Store Setup</span></a>
        </div>
        @endcan

        @if($retailEnabled && Auth::user()->can('view sales ledger'))
        <button type="button" :title="sidebarCollapsed ? 'Credit' : null" @click="expandThen('customersOpen')"
                class="nav-group nav-tone-credit {{ $isCredit ? 'is-open' : '' }}">
            <span class="nav-group-main">
                <span class="nav-ico-wrap"><svg class="nav-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span>
                <span class="nav-label">Credit &amp; EMI</span>
            </span>
            <svg class="nav-chevron" :class="customersOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-show="customersOpen && !sidebarCollapsed" x-cloak class="nav-submenu">
            <a href="{{ route('customers.baki.index') }}" class="nav-sub {{ request()->routeIs('customers.baki.*') ? 'is-active' : '' }}"><svg class="nav-sub-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg><span>Credit (Baki)</span></a>
            <a href="{{ route('customers.emi.index') }}" class="nav-sub {{ request()->routeIs('customers.emi.*') ? 'is-active' : '' }}"><svg class="nav-sub-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg><span>EMI Plans</span></a>
                </div>
        @endif

        @can('view reports')
        <button type="button" :title="sidebarCollapsed ? 'Reports' : null" @click="expandThen('insightsOpen')"
                class="nav-group nav-tone-reports {{ $isInsights ? 'is-open' : '' }}">
            <span class="nav-group-main">
                <span class="nav-ico-wrap"><svg class="nav-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg></span>
                <span class="nav-label">Reports</span>
            </span>
            <svg class="nav-chevron" :class="insightsOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-show="insightsOpen && !sidebarCollapsed" x-cloak class="nav-submenu">
            <a href="{{ route('analytics.overview') }}" class="nav-sub {{ request()->routeIs('analytics.*') ? 'is-active' : '' }}"><svg class="nav-sub-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg><span>Analytics</span></a>
            <a href="{{ route('reports.daily') }}" class="nav-sub {{ request()->routeIs('reports.daily') ? 'is-active' : '' }}"><svg class="nav-sub-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg><span>Daily Collections</span></a>
            <a href="{{ route('reports.daily_by_brand') }}" class="nav-sub {{ request()->routeIs('reports.daily_by_brand') ? 'is-active' : '' }}"><svg class="nav-sub-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/></svg><span>Sales by Brand</span></a>
            @if($retailEnabled)
            <a href="{{ route('reports.staff_performance') }}" class="nav-sub {{ request()->routeIs('reports.staff_performance', 'reports.staff_daily_details') ? 'is-active' : '' }}"><svg class="nav-sub-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg><span>Staff Performance</span></a>
            <a href="{{ route('pos.settings.edit') }}" class="nav-sub {{ request()->routeIs('pos.settings.*') ? 'is-active' : '' }}"><svg class="nav-sub-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg><span>POS Settings</span></a>
            @endif
            </div>
        @endcan

        @can('manage website')
        <button type="button" :title="sidebarCollapsed ? 'Website' : null" @click="expandThen('websiteOpen')"
                class="nav-group nav-tone-web {{ $isWebsite ? 'is-open' : '' }}">
            <span class="nav-group-main">
                <span class="nav-ico-wrap"><svg class="nav-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm0 8a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zm12 0a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg></span>
                <span class="nav-label">Website</span>
            </span>
            <svg class="nav-chevron" :class="websiteOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-show="websiteOpen && !sidebarCollapsed" x-cloak class="nav-submenu">
            <a href="{{ route('cms.landing.edit') }}" class="nav-sub {{ request()->routeIs('cms.landing.*') ? 'is-active' : '' }}"><svg class="nav-sub-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm0 8a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zm12 0a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg><span>Landing Page</span></a>
            <a href="{{ route('cms.slides.index') }}" class="nav-sub {{ request()->routeIs('cms.slides.*') ? 'is-active' : '' }}"><svg class="nav-sub-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg><span>Home Slides</span></a>
            <a href="{{ route('cms.navigation.index') }}" class="nav-sub {{ request()->routeIs('cms.navigation.*') ? 'is-active' : '' }}"><svg class="nav-sub-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 6h16M4 12h16M4 18h16"/></svg><span>Navigation</span></a>
            <a href="{{ route('cms.pages.index') }}" class="nav-sub {{ request()->routeIs('cms.pages.*') ? 'is-active' : '' }}"><svg class="nav-sub-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg><span>Pages</span></a>
            <a href="{{ route('cms.blogs.index') }}" class="nav-sub {{ request()->routeIs('cms.blogs.*') ? 'is-active' : '' }}"><svg class="nav-sub-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg><span>Blog Posts</span></a>
            <a href="{{ route('cms.blog-categories.index') }}" class="nav-sub {{ request()->routeIs('cms.blog-categories.*') ? 'is-active' : '' }}"><svg class="nav-sub-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg><span>Blog Categories</span></a>
            <a href="{{ route('cms.faqs.index') }}" class="nav-sub {{ request()->routeIs('cms.faqs.*') ? 'is-active' : '' }}"><svg class="nav-sub-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg><span>FAQ</span></a>
            <a href="{{ route('cms.faq-categories.index') }}" class="nav-sub {{ request()->routeIs('cms.faq-categories.*') ? 'is-active' : '' }}"><svg class="nav-sub-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg><span>FAQ Categories</span></a>
            <a href="{{ route('cms.reviews.index') }}" class="nav-sub {{ request()->routeIs('cms.reviews.*') ? 'is-active' : '' }}"><svg class="nav-sub-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg><span>Reviews</span></a>
            <a href="{{ route('cms.contact.index') }}" class="nav-sub {{ request()->routeIs('cms.contact.*') ? 'is-active' : '' }}"><svg class="nav-sub-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg><span>Contact</span></a>
            <a href="{{ route('cms.delivery.edit') }}" class="nav-sub {{ request()->routeIs('cms.delivery.*') ? 'is-active' : '' }}"><svg class="nav-sub-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10m10 0h6m-6 0H9m10 0v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0016 8h-3"/></svg><span>Delivery</span></a>
            <a href="{{ route('cms.couriers.index') }}" class="nav-sub {{ request()->routeIs('cms.couriers.*') ? 'is-active' : '' }}"><svg class="nav-sub-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10m10 0h6m-6 0H9m10 0v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0016 8h-3"/></svg><span>Couriers</span></a>
        </div>
        @endcan

        @if(Auth::user()->can('manage roles') || Auth::user()->can('manage staff') || ($retailEnabled && Auth::user()->can('manage counters')))
        <button type="button" :title="sidebarCollapsed ? 'Team' : null" @click="expandThen('teamOpen')"
                class="nav-group nav-tone-team {{ $isTeam ? 'is-open' : '' }}">
            <span class="nav-group-main">
                <span class="nav-ico-wrap"><svg class="nav-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg></span>
                <span class="nav-label">Team</span>
            </span>
            <svg class="nav-chevron" :class="teamOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-show="teamOpen && !sidebarCollapsed" x-cloak class="nav-submenu">
            @can('manage staff')
            <a href="{{ route('staff.index') }}" class="nav-sub {{ request()->routeIs('staff.*') ? 'is-active' : '' }}"><svg class="nav-sub-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg><span>Staff</span></a>
            @endcan
            @can('manage roles')
            <a href="{{ route('roles.index') }}" class="nav-sub {{ request()->routeIs('roles.*') ? 'is-active' : '' }}"><svg class="nav-sub-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg><span>Roles</span></a>
            @endcan
            @if($retailEnabled && Auth::user()->can('manage counters'))
            <a href="{{ route('counters.index') }}" class="nav-sub {{ request()->routeIs('counters.index') || request()->routeIs('counters.store') || request()->routeIs('counters.update') ? 'is-active' : '' }}"><svg class="nav-sub-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg><span>Counters</span></a>
            @endif
        </div>
        @endif
    </nav>

    <div class="sidebar-foot">
        <a href="{{ route('home') }}" target="_blank" rel="noopener" class="sidebar-store-card bb-lottie-host" :title="sidebarCollapsed ? 'View store' : null">
            @include('website.partials.lottie', ['name' => 'rocket', 'hover' => true])
            <span class="sidebar-store-copy min-w-0">
                <strong class="truncate">View your store</strong>
                <small>See what shoppers see</small>
            </span>
        </a>
    </div>
</aside>

<header class="admin-topbar"
        :class="sidebarCollapsed ? 'is-rail' : ''">
    <button type="button" @click="sidebarOpen = true" class="admin-mobile-menu-btn text-slate-500 hover:text-slate-800 focus:outline-none" aria-label="Open menu">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>

    @include('components.command-palette')

    <div class="flex items-center gap-1.5 sm:gap-2 ml-auto shrink-0">
        @if($retailEnabled && Auth::user()->can('process pos sales'))
            <a href="{{ route('pos.index') }}"
               onclick="return window.launchPosTerminal(this.href)"
               class="topbar-pill topbar-pill--pos">
                <span class="sm:hidden">POS</span>
                <span class="hidden sm:inline">POS Terminal</span>
            </a>
        @endif

        @if(Auth::check())
            <a href="{{ route('home') }}" target="_blank"
               class="topbar-pill topbar-pill--store">
                <span class="sm:hidden">Store</span>
                <span class="hidden sm:inline">View Store</span>
            </a>
        @endif

        @can('manage orders')
            <div class="relative"
                 x-data="onlineOrderBell(@js(route('online-orders.notifications')), @js(route('online-orders.notifications.seen')))"
                 @keydown.escape.window="if (panelOpen) closePanel()"
                 @click.outside="if (panelOpen) closePanel()">
                <button type="button"
                        @click="togglePanel($event)"
                        class="topbar-icon-btn relative inline-flex h-9 w-9 items-center justify-center border border-slate-200 bg-white text-slate-500 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-600"
                        :class="panelOpen ? 'border-blue-300 bg-blue-50 text-blue-700' : ''"
                        title="Online order notifications"
                        aria-haspopup="true"
                        :aria-expanded="panelOpen">
                    <svg class="h-4 w-4 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V4a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    <span x-show="unread > 0" x-cloak x-text="unread > 99 ? '99+' : unread"
                          class="pointer-events-none absolute -right-0.5 -top-0.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-rose-500 px-1 text-[9px] font-bold text-white"></span>
                </button>

                <div x-show="panelOpen"
                     x-cloak
                     x-transition.opacity.duration.150ms
                     class="absolute right-0 top-[calc(100%+8px)] z-50 admin-fluid-panel overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-900/10"
                     style="display: none;">
                    <div class="flex items-center justify-between border-b border-slate-100 px-3.5 py-2.5">
                        <p class="text-[13px] font-bold text-slate-900">Online orders</p>
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-500" x-text="loading ? 'Loading…' : (items.length + ' recent')"></span>
                    </div>

                    <div class="max-h-[360px] overflow-y-auto">
                        <template x-if="error && !loading">
                            <p class="px-4 py-6 text-center text-[12px] text-rose-500" x-text="error"></p>
                        </template>

                        <template x-if="!loading && !error && items.length === 0">
                            <div class="px-4 py-7 text-center">
                                @include('website.partials.lottie', ['name' => 'package', 'class' => 'h-12 w-12'])
                                <p class="mt-1 text-[12px] font-semibold text-slate-400">No online orders yet.</p>
                            </div>
                        </template>

                        <template x-for="item in items" :key="item.id">
                            <a :href="item.url"
                               @click="openItem(item, $event)"
                               class="block border-b border-slate-50 px-3.5 py-2.5 transition last:border-0"
                               :class="item.is_new ? 'bg-blue-50/80 hover:bg-blue-50' : 'bg-white hover:bg-slate-50'">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5">
                                            <p class="truncate text-[12px] font-bold" :class="item.is_new ? 'text-blue-900' : 'text-slate-700'" x-text="item.invoice"></p>
                                            <span x-show="item.is_new" class="rounded-full bg-blue-700 px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide text-white">New</span>
                                        </div>
                                        <p class="mt-0.5 truncate text-[11px]" :class="item.is_new ? 'text-blue-700' : 'text-slate-500'" x-text="item.customer + (item.phone ? ' · ' + item.phone : '')"></p>
                                        <p class="mt-0.5 text-[10px]" :class="item.is_new ? 'text-blue-500' : 'text-slate-400'" x-text="item.status_label + ' · ' + item.at"></p>
                                    </div>
                                    <p class="shrink-0 text-[11px] font-bold" :class="item.is_new ? 'text-blue-800' : 'text-slate-500'" x-text="'Tk ' + item.total"></p>
                                </div>
                            </a>
                        </template>
                    </div>

                    <a href="{{ route('online-orders.index') }}"
                       class="block border-t border-slate-100 bg-slate-50 px-3.5 py-2.5 text-center text-[12px] font-bold text-blue-700 hover:bg-slate-100">
                        View all online orders
                    </a>
                </div>
            </div>
        @endcan

        @if(Auth::check())
            <div class="relative"
                 x-data="staffAlertBell(@js(route('notifications.feed')), @js(route('notifications.read-all')))"
                 @keydown.escape.window="panelOpen = false"
                 @click.outside="panelOpen = false">
                <button type="button"
                        @click="toggle()"
                        class="topbar-icon-btn relative inline-flex h-9 w-9 items-center justify-center border border-slate-200 bg-white text-slate-500 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-600"
                        :class="panelOpen ? 'border-indigo-300 bg-indigo-50 text-indigo-700' : ''"
                        title="Alerts: leads, low stock, abandoned carts, delivery issues"
                        aria-haspopup="true"
                        :aria-expanded="panelOpen">
                    <svg class="h-4 w-4 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span x-show="unread > 0" x-cloak x-text="unread > 99 ? '99+' : unread"
                          class="pointer-events-none absolute -right-0.5 -top-0.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-indigo-600 px-1 text-[9px] font-bold text-white"></span>
                </button>

                <div x-show="panelOpen" x-cloak x-transition.opacity.duration.150ms
                     class="absolute right-0 top-[calc(100%+8px)] z-50 admin-fluid-panel overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-900/10"
                     style="display: none;">
                    <div class="flex items-center justify-between border-b border-slate-100 px-3.5 py-2.5">
                        <p class="text-[13px] font-bold text-slate-900">Alerts</p>
                        <button type="button" x-show="unread > 0" @click="readAll()" class="text-[11px] font-semibold text-indigo-600 hover:underline">Mark all read</button>
                    </div>
                    <div class="max-h-[360px] overflow-y-auto">
                        <template x-if="!loading && items.length === 0">
                            <div class="px-4 py-7 text-center">
                                @include('website.partials.lottie', ['name' => 'check', 'class' => 'h-12 w-12'])
                                <p class="mt-1 text-[12px] font-semibold text-slate-400">All caught up, no alerts.</p>
                            </div>
                        </template>
                        <template x-for="item in items" :key="item.id">
                            <a :href="item.url || '#'" @click="open(item, $event)"
                               class="block border-b border-slate-50 px-3.5 py-2.5 transition last:border-0"
                               :class="item.is_new ? 'bg-indigo-50/70 hover:bg-indigo-50' : 'bg-white hover:bg-slate-50'">
                                <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400" x-text="item.kind_label + ' · ' + item.at"></p>
                                <p class="mt-0.5 text-[12px] font-bold text-slate-800" x-text="item.title"></p>
                                <p class="mt-0.5 text-[11px] text-slate-500" x-text="item.body"></p>
                            </a>
                        </template>
                    </div>
                    <a href="{{ route('notifications.index') }}"
                       class="block border-t border-slate-100 bg-slate-50 px-3.5 py-2.5 text-center text-[12px] font-bold text-indigo-700 hover:bg-slate-100">
                        All notifications
                    </a>
                </div>
            </div>
        @endif

        <div class="flex items-center pl-0.5 sm:pl-1">
            <x-dropdown align="right" width="48">
                <x-slot name="trigger">
                    <button class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white py-1 pl-1 pr-2.5 text-[13px] font-semibold text-slate-700 hover:bg-slate-50">
                        <span class="flex h-7 w-7 items-center justify-center rounded-full bg-blue-100 text-blue-700 text-xs font-extrabold">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </span>
                        <span class="hidden lg:inline max-w-[100px] truncate">{{ Auth::user()->name }}</span>
                        <svg class="h-4 w-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                    </button>
                </x-slot>
                <x-slot name="content">
                    <x-dropdown-link :href="route('profile.edit')">{{ __('Profile') }}</x-dropdown-link>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();" class="text-rose-600">
                            {{ __('Log Out') }}
                        </x-dropdown-link>
                    </form>
                </x-slot>
            </x-dropdown>
        </div>
    </div>
</header>
