@extends('website.layout')

@section('title', 'My Account — ' . ($settings->store_name ?? config('app.name', 'Maks Gadget')))

@section('content')
@php
    $currency = $settings->currency_symbol ?? '৳';
    $firstName = explode(' ', trim($customer?->name ?? auth()->user()->name ?? 'Customer'))[0] ?? 'Customer';
    $justPlacedInvoice = request('order');
    $justPlacedOid = request('oid');
    $justPlaced = request()->boolean('placed');
    $highlightOrderId = null;
    if ($justPlaced && ($recentOrders ?? collect())->isNotEmpty()) {
        $match = ($recentOrders ?? collect())->first(function ($order) use ($justPlacedInvoice, $justPlacedOid) {
            if ($justPlacedInvoice && (string) $order->invoice_no === (string) $justPlacedInvoice) {
                return true;
            }
            if ($justPlacedOid && (int) $order->id === (int) $justPlacedOid) {
                return true;
            }

            return false;
        }) ?? ($recentOrders ?? collect())->first();
        $highlightOrderId = $match?->id;
        $justPlacedInvoice = $justPlacedInvoice ?: ($match?->invoice_no);
    }
    $orderDetailsMap = ($recentOrders ?? collect())->mapWithKeys(function ($order) use ($orderTracking, $customer) {
        $track = $orderTracking[$order->id] ?? null;

        return [
            $order->id => [
                'id' => $order->id,
                'invoice' => $order->invoice_no,
                'date' => asian_date($order->created_at, 'M j, Y'),
                'datetime' => asian_datetime($order->created_at, 'M j, Y g:i A'),
                'status' => $order->status === 'pending_fulfillment' ? 'pending' : $order->status,
                'status_label' => $track['status_label'] ?? ucfirst($order->status),
                'total' => format_taka_number((float) $order->total_amount),
                'where' => $track['where_is_product'] ?? '',
                'courier' => $order->shipping_courier,
                'tracking_no' => $order->shipping_tracking_no,
                'address' => $customer?->address ?: 'No address saved yet.',
                'timeline' => $track['timeline'] ?? [],
                'items' => $order->items->map(function ($item) {
                    $product = $item->product;

                    return [
                        'name' => $product?->name ?? 'Product',
                        'qty' => (int) $item->quantity,
                        'subtotal' => format_taka_number((float) $item->subtotal),
                        'image' => $product
                            ? app(\App\Services\WebsiteService::class)->productImageUrl($product)
                            : '',
                    ];
                })->values()->all(),
            ],
        ];
    });
@endphp
<div class="max-w-[1280px] mx-auto px-4 md:px-5 py-6"
     x-data="{
        openOrder: {{ $highlightOrderId ?? $activeOrder?->id ?? 'null' }},
        detailOpen: false,
        detail: null,
        orders: @js($orderDetailsMap),
        openDetail(id) {
            this.detail = this.orders[id] || null;
            this.detailOpen = !!this.detail;
        },
        closeDetail() {
            this.detailOpen = false;
            this.detail = null;
        },
        statusClass(status) {
            if (status === 'completed') return 'bg-emerald-100 text-emerald-700';
            if (status === 'shipped') return 'bg-sky-100 text-sky-800';
            if (status === 'processing') return 'bg-amber-100 text-amber-800 ring-1 ring-amber-300';
            if (status === 'pending' || status === 'pending_fulfillment') return 'bg-orange-100 text-orange-800';
            if (['cancelled','returned','refunded'].includes(status)) return 'bg-rose-100 text-rose-700';
            return 'bg-slate-100 text-slate-700';
        },
        flowSteps: [
            { key: 'pending', label: 'Received' },
            { key: 'processing', label: 'Preparing' },
            { key: 'shipped', label: 'In transit' },
            { key: 'completed', label: 'Delivered' },
        ],
        buildTrack(order) {
            if (!order) return [];
            const rankMap = { pending: 0, processing: 1, shipped: 2, completed: 3 };
            const raw = order.status || 'pending';
            const status = raw === 'pending_fulfillment' ? 'pending' : raw;
            const rank = Object.prototype.hasOwnProperty.call(rankMap, status) ? rankMap[status] : 0;
            const byKey = {};
            (order.timeline || []).forEach((s) => { if (s && s.key) byKey[s.key] = s; });
            return this.flowSteps.map((step, i) => {
                const log = byKey[step.key] || {};
                const active = typeof log.active === 'boolean' ? log.active : (step.key === status);
                const passed = typeof log.done === 'boolean'
                    ? log.done
                    : (i < rank || status === 'completed');
                return {
                    key: step.key,
                    label: step.label,
                    active,
                    done: passed && !active,
                    at: log.at || null,
                    note: log.note || null,
                };
            });
        },
        progressPct(order) {
            if (!order) return 0;
            const rankMap = { pending: 0, processing: 1, shipped: 2, completed: 3 };
            const raw = order.status || 'pending';
            const status = raw === 'pending_fulfillment' ? 'pending' : raw;
            const rank = rankMap[status] ?? 0;
            return (rank / 3) * 100;
        },
     }"
     x-init="
        @if($highlightOrderId)
            openDetail({{ (int) $highlightOrderId }});
            setTimeout(() => {
                document.getElementById('recent-orders')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 120);
        @endif
     "
     @keydown.escape.window="closeDetail()"
     @acct-open-detail.window="openDetail($event.detail)">
    <div class="acct-page max-w-[1280px] mx-auto px-3 sm:px-4 md:px-5 py-4 sm:py-6">
    <div class="grid gap-4 xl:grid-cols-[250px_minmax(0,1fr)] min-w-0">
        <div class="space-y-4 min-w-0 order-2 xl:order-1">
            @include('website.partials.account-sidebar', ['activeMenu' => 'dashboard', 'customer' => $customer, 'activeOrder' => $activeOrder])
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm hidden sm:block">
                <p class="text-[15px] font-bold text-slate-900">Exclusive Member Benefits</p>
                <p class="mt-1 text-[11px] text-slate-500">Get special offers and faster checkout.</p>
                <a href="{{ route('website.shop') }}" class="mt-3 inline-flex text-[13px] font-semibold text-blue-600 hover:text-blue-700">Explore Benefits →</a>
            </div>
        </div>

        <section class="space-y-4 min-w-0 order-1 xl:order-2">
            @if(session('profile_success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-[12px] font-semibold text-emerald-700">
                    {{ session('profile_success') }}
                </div>
            @endif

            @if($justPlaced)
                <div class="gaget-order-placed-banner" role="status">
                    <div class="gaget-order-placed-banner__icon" aria-hidden="true">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <div>
                        <p class="gaget-order-placed-banner__title">Order placed successfully</p>
                        <p class="gaget-order-placed-banner__text">
                            @if($justPlacedInvoice)
                                Your order <strong>{{ $justPlacedInvoice }}</strong> is confirmed. Track it below — we’ll update status as it moves.
                            @else
                                Your order is confirmed. Track it below — we’ll update status as it moves.
                            @endif
                        </p>
                    </div>
                </div>
            @endif

            <div class="min-w-0">
                <h1 class="acct-welcome-title">Welcome back, {{ $firstName }}!</h1>
                <p class="mt-0.5 text-[13px] text-slate-500">Here’s what’s happening with your account today.</p>
            </div>

            <div class="gaget-account-stats">
                <div class="gaget-account-stat">
                    <p class="gaget-account-stat__label">Total Orders</p>
                    <div class="gaget-account-stat__row">
                        <div>
                            <p class="gaget-account-stat__value">{{ $totalOrders }}</p>
                            <p class="gaget-account-stat__hint">All time</p>
                        </div>
                        <div class="gaget-account-stat__icon gaget-account-stat__icon--blue">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                    </div>
                </div>
                <div class="gaget-account-stat">
                    <p class="gaget-account-stat__label">Packaging</p>
                    <div class="gaget-account-stat__row">
                        <div>
                            <p class="gaget-account-stat__value">{{ $packagingOrders }}</p>
                            <p class="gaget-account-stat__hint">Received / packing</p>
                        </div>
                        <div class="gaget-account-stat__icon gaget-account-stat__icon--amber">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </div>
                    </div>
                </div>
                <div class="gaget-account-stat">
                    <p class="gaget-account-stat__label">In Transit</p>
                    <div class="gaget-account-stat__row">
                        <div>
                            <p class="gaget-account-stat__value">{{ $inTransitOrders }}</p>
                            <p class="gaget-account-stat__hint">Out for delivery</p>
                        </div>
                        <div class="gaget-account-stat__icon gaget-account-stat__icon--green">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 17h6m-8 0H5a2 2 0 01-2-2V7a2 2 0 012-2h9a2 2 0 012 2v2m0 8h1a2 2 0 002-2v-3m-3 5a2 2 0 11-4 0m4 0a2 2 0 104 0m-4 0H9m10-8l-2-3h-3"/></svg>
                        </div>
                    </div>
                </div>
                <div class="gaget-account-stat">
                    <p class="gaget-account-stat__label">Delivered</p>
                    <div class="gaget-account-stat__row">
                        <div>
                            <p class="gaget-account-stat__value">{{ $deliveredOrders }}</p>
                            <p class="gaget-account-stat__hint">Completed</p>
                        </div>
                        <div class="gaget-account-stat__icon gaget-account-stat__icon--lime">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 13l4 4L19 7"/></svg>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 xl:grid-cols-[minmax(0,1.52fr)_minmax(0,340px)] min-w-0">
                <div class="space-y-4 min-w-0">
                    <div class="rounded-2xl border border-slate-200 bg-white p-3 sm:p-4 shadow-sm min-w-0"
                         x-data="{
                            slides: @js($activeOrderSlides ?? []),
                            index: 0,
                            touchStartX: null,
                            flowSteps: [
                                { key: 'pending', label: 'Received' },
                                { key: 'processing', label: 'Preparing' },
                                { key: 'shipped', label: 'In transit' },
                                { key: 'completed', label: 'Delivered' },
                            ],
                            get current() { return this.slides[this.index] || null; },
                            get count() { return this.slides.length; },
                            get track() { return this.buildTrack(this.current); },
                            get fillPct() { return this.progressPct(this.current); },
                            buildTrack(order) {
                                if (!order) return [];
                                const rankMap = { pending: 0, processing: 1, shipped: 2, completed: 3 };
                                const raw = order.status || 'pending';
                                const status = raw === 'pending_fulfillment' ? 'pending' : raw;
                                const rank = Object.prototype.hasOwnProperty.call(rankMap, status) ? rankMap[status] : 0;
                                const byKey = {};
                                (order.timeline || []).forEach((s) => { if (s && s.key) byKey[s.key] = s; });
                                return this.flowSteps.map((step, i) => {
                                    const log = byKey[step.key] || {};
                                    const active = typeof log.active === 'boolean' ? log.active : (step.key === status);
                                    const passed = typeof log.done === 'boolean'
                                        ? log.done
                                        : (i < rank || status === 'completed');
                                    return {
                                        key: step.key,
                                        label: step.label,
                                        active,
                                        done: passed && !active,
                                        at: log.at || null,
                                        note: log.note || null,
                                    };
                                });
                            },
                            progressPct(order) {
                                if (!order) return 0;
                                const rankMap = { pending: 0, processing: 1, shipped: 2, completed: 3 };
                                const raw = order.status || 'pending';
                                const status = raw === 'pending_fulfillment' ? 'pending' : raw;
                                const rank = rankMap[status] ?? 0;
                                return (rank / 3) * 100;
                            },
                            prev() { if (this.count < 2) return; this.index = (this.index - 1 + this.count) % this.count; },
                            next() { if (this.count < 2) return; this.index = (this.index + 1) % this.count; },
                            go(i) { this.index = i; },
                            onTouchStart(e) { this.touchStartX = e.changedTouches?.[0]?.clientX ?? null; },
                            onTouchEnd(e) {
                                if (this.touchStartX === null || this.count < 2) return;
                                const dx = (e.changedTouches?.[0]?.clientX ?? this.touchStartX) - this.touchStartX;
                                if (Math.abs(dx) < 40) return;
                                dx < 0 ? this.next() : this.prev();
                                this.touchStartX = null;
                            },
                            statusClass(status) {
                                if (status === 'completed') return 'bg-emerald-100 text-emerald-700';
                                if (status === 'shipped') return 'bg-sky-100 text-sky-800';
                                if (status === 'processing') return 'bg-amber-100 text-amber-800 ring-1 ring-amber-300';
                                if (status === 'pending' || status === 'pending_fulfillment') return 'bg-orange-100 text-orange-800';
                                if (status === 'cancelled' || status === 'returned' || status === 'refunded') return 'bg-rose-100 text-rose-700';
                                return 'bg-slate-100 text-slate-700';
                            }
                         }">
                    <div class="mb-4 flex items-start justify-between gap-2 min-w-0">
                        <div class="min-w-0">
                            <h2 class="text-[15px] font-bold text-slate-900">
                                Active Order<span x-show="count > 1" x-cloak x-text="'s (' + count + ')'"></span>
                            </h2>
                            <p class="text-[11px] text-slate-500">Live status from our store to your doorstep.</p>
                        </div>
                        <a href="{{ route('website.account') }}#recent-orders" class="shrink-0 text-[11px] font-bold text-blue-600 hover:text-blue-700 whitespace-nowrap">View all</a>
                    </div>

                    <template x-if="current">
                        <div class="relative min-w-0 max-w-full"
                             @touchstart.passive="onTouchStart($event)"
                             @touchend.passive="onTouchEnd($event)">
                            <div class="acct-active-order min-w-0 max-w-full">
                                <div class="acct-active-order__head">
                                    <div class="min-w-0">
                                        <p class="acct-active-order__invoice" x-text="current.invoice"></p>
                                        <p class="acct-active-order__meta" x-show="current.id" x-text="'Ref #' + current.id + ' · Placed ' + current.date"></p>
                                    </div>
                                    <span class="acct-active-order__badge" :data-status="current.status" x-text="current.status_label"></span>
                                </div>

                                <div class="acct-active-order__status" x-show="current.where">
                                    <p class="acct-active-order__status-label">Current status</p>
                                    <p class="acct-active-order__status-text" x-text="current.where"></p>
                                </div>

                                <ol class="acct-timeline acct-timeline--compact">
                                    <template x-for="(step, sIdx) in track" :key="current.id + '-track-' + step.key">
                                        <li class="acct-timeline__item"
                                            :class="{
                                                'is-done': step.done,
                                                'is-active': step.active,
                                                'is-waiting': !step.done && !step.active
                                            }">
                                            <div class="acct-timeline__marker" aria-hidden="true">
                                                <span class="acct-timeline__dot" x-text="step.done || (step.active && current.status === 'completed') ? '✓' : (sIdx + 1)"></span>
                                                <span class="acct-timeline__line" x-show="sIdx < 3"></span>
                                            </div>
                                            <div class="acct-timeline__content">
                                                <p class="acct-timeline__label" x-text="step.label"></p>
                                                <p class="acct-timeline__meta" x-text="step.active ? (step.at || 'In progress') : (step.done ? (step.at || 'Completed') : 'Pending')"></p>
                                            </div>
                                        </li>
                                    </template>
                                </ol>

                                <div class="acct-active-order__items">
                                    <template x-for="(item, ii) in (current.items || [])" :key="current.id + '-item-' + ii">
                                        <div class="acct-active-order__item">
                                            <div class="acct-active-order__thumb">
                                                <img x-show="item.image" :src="item.image" :alt="item.name" loading="lazy">
                                                <div x-show="!item.image" class="acct-active-order__thumb-fallback" aria-hidden="true">
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                                </div>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <p class="acct-active-order__item-name" x-text="item.name"></p>
                                                <p class="acct-active-order__item-qty" x-text="'Quantity: ' + item.qty"></p>
                                            </div>
                                        </div>
                                    </template>
                                </div>

                                <div class="acct-active-order__delivery">
                                    <div>
                                        <p class="acct-active-order__delivery-label">Courier</p>
                                        <p class="acct-active-order__delivery-value" x-text="current.courier"></p>
                                    </div>
                                    <div>
                                        <p class="acct-active-order__delivery-label">Delivery address</p>
                                        <p class="acct-active-order__delivery-value" x-text="current.address"></p>
                                    </div>
                                </div>

                                <button type="button"
                                        class="acct-active-order__cta"
                                        @click="$dispatch('acct-open-detail', current.id)">
                                    View order details
                                </button>
                            </div>

                            <template x-if="count > 1">
                                <div class="mt-4 flex items-center justify-between gap-3">
                                    <button type="button" @click="prev()"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 hover:bg-slate-50"
                                            aria-label="Previous order">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                    </button>
                                    <div class="flex items-center gap-2">
                                        <template x-for="(slide, i) in slides" :key="'dot-' + slide.id">
                                            <button type="button" @click="go(i)"
                                                    class="h-2 rounded-full transition-all"
                                                    :class="i === index ? 'w-5 bg-blue-600' : 'w-2 bg-slate-300 hover:bg-slate-400'"
                                                    :aria-label="'Show order ' + (i + 1)"></button>
                                        </template>
                                        <span class="ml-1 text-[11px] font-semibold text-slate-500" x-text="(index + 1) + ' / ' + count"></span>
                                    </div>
                                    <button type="button" @click="next()"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-600 hover:bg-slate-50"
                                            aria-label="Next order">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </template>

                    <div x-show="!current" class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-6 py-10 text-center">
                        <p class="text-[13px] font-semibold text-slate-700">No active order yet.</p>
                        <p class="mt-1 text-xs text-slate-500">Place your first order and status updates will appear here.</p>
                        <a href="{{ route('website.shop') }}" class="mt-4 inline-flex rounded-xl bg-blue-600 px-4 py-2.5 text-[13px] font-semibold text-white hover:bg-blue-700">Start Shopping</a>
                    </div>
                    </div>

                    <div id="recent-orders" class="acct-orders rounded-2xl border border-slate-200 bg-white p-3 sm:p-4 shadow-sm min-w-0">
                <div class="mb-3 flex items-center justify-between gap-3 min-w-0">
                    <div class="min-w-0">
                        <h2 class="text-[15px] font-bold text-slate-900">Recent Orders</h2>
                    </div>
                    <a href="#recent-orders" class="shrink-0 text-[11px] font-bold text-blue-600 hover:text-blue-700">View all</a>
                </div>

                @if($recentOrders->isEmpty())
                    <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center">
                        <p class="text-[13px] font-semibold text-slate-700">No orders yet.</p>
                        <p class="mt-1 text-xs text-slate-500">When you place a website order, it will appear here immediately.</p>
                    </div>
                @else
                    {{-- Mobile cards --}}
                    <div class="acct-order-cards space-y-3 md:hidden">
                        @foreach($recentOrders as $order)
                            @php $track = $orderTracking[$order->id] ?? null; @endphp
                            <div class="acct-order-card {{ $highlightOrderId && (int) $highlightOrderId === (int) $order->id ? 'gaget-order-row-highlight' : '' }}">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="text-[13px] font-bold text-slate-900 break-all">{{ $order->invoice_no }}</p>
                                        <p class="text-[10px] text-slate-400">#{{ $order->id }} · {{ asian_date($order->created_at, 'M j, Y') }}</p>
                                    </div>
                                    <span class="shrink-0 inline-flex items-center gap-1 rounded-full px-2 py-1 text-[10px] font-extrabold
                                        @if($order->status === 'completed') bg-emerald-100 text-emerald-700
                                        @elseif($order->status === 'shipped') bg-sky-100 text-sky-800
                                        @elseif($order->status === 'processing') bg-amber-100 text-amber-800
                                        @elseif($order->status === 'pending') bg-orange-100 text-orange-800
                                        @elseif(in_array($order->status, ['cancelled', 'returned', 'refunded'])) bg-rose-100 text-rose-700
                                        @else bg-slate-100 text-slate-700 @endif">
                                        {{ $track['status_label'] ?? ucfirst($order->status) }}
                                    </span>
                                </div>
                                <p class="mt-2 text-[12px] font-medium text-slate-700 break-words">
                                    {{ $order->items->first()?->product?->name ?? 'Product' }}
                                    <span class="text-slate-400">· {{ $order->items->sum('quantity') }} item(s)</span>
                                </p>
                                <div class="mt-3 flex items-center justify-between gap-2">
                                    <p class="text-[14px] font-extrabold text-slate-900">{{ format_taka($order->total_amount, $currency) }}</p>
                                    <button type="button"
                                            @click="openDetail({{ $order->id }})"
                                            class="shrink-0 inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-700">
                                        View Details
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Desktop table --}}
                    <div class="hidden md:block overflow-x-auto max-w-full">
                        <table class="w-full text-[13px]">
                            <thead class="border-b border-slate-100 text-[10px] uppercase tracking-wide text-slate-400">
                                <tr>
                                    <th class="px-2 py-2.5 text-left">Order ID</th>
                                    <th class="px-2 py-2.5 text-left">Date</th>
                                    <th class="px-2 py-2.5 text-left">Items</th>
                                    <th class="px-2 py-2.5 text-right">Total</th>
                                    <th class="px-2 py-2.5 text-left">Status</th>
                                    <th class="px-2 py-2.5 text-right">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                @foreach($recentOrders as $order)
                                    @php $track = $orderTracking[$order->id] ?? null; @endphp
                                    <tr class="{{ $highlightOrderId && (int) $highlightOrderId === (int) $order->id ? 'gaget-order-row-highlight' : '' }}">
                                        <td class="px-2 py-3 font-semibold text-slate-900">
                                            <span class="block break-all">{{ $order->invoice_no }}</span>
                                            <span class="text-[10px] font-medium text-slate-400">#{{ $order->id }}</span>
                                        </td>
                                        <td class="px-2 py-3 text-slate-600 whitespace-nowrap">{{ asian_date($order->created_at, 'M j, Y') }}</td>
                                        <td class="px-2 py-3">
                                            <div class="flex items-center gap-2.5 min-w-0">
                                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-50 text-base">📦</div>
                                                <div class="min-w-0">
                                                    <p class="truncate font-medium text-slate-800">{{ $order->items->first()?->product?->name ?? 'Product' }}</p>
                                                    <p class="text-[11px] text-slate-500">{{ $order->items->sum('quantity') }} item(s)</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-2 py-3 text-right font-bold text-slate-900 whitespace-nowrap">{{ format_taka($order->total_amount, $currency) }}</td>
                                        <td class="px-2 py-3">
                                            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-extrabold
                                                @if($order->status === 'completed') bg-emerald-100 text-emerald-700
                                                @elseif($order->status === 'shipped') bg-sky-100 text-sky-800
                                                @elseif($order->status === 'processing') bg-amber-100 text-amber-800 ring-1 ring-amber-300
                                                @elseif($order->status === 'pending') bg-orange-100 text-orange-800
                                                @elseif(in_array($order->status, ['cancelled', 'returned', 'refunded'])) bg-rose-100 text-rose-700
                                                @else bg-slate-100 text-slate-700 @endif">
                                                {{ $track['status_label'] ?? ucfirst($order->status) }}
                                            </span>
                                        </td>
                                        <td class="px-2 py-3 text-right">
                                            <button type="button"
                                                    @click="openDetail({{ $order->id }})"
                                                    class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-slate-700 hover:bg-slate-50">
                                                View Details
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
                </div>

                <div class="space-y-4 min-w-0">
                    <div class="acct-info-card rounded-2xl border border-slate-200 bg-white p-3 sm:p-4 shadow-sm min-w-0">
                        <h2 class="text-[15px] font-bold text-slate-900">Account Overview</h2>
                        <div class="mt-3 space-y-3 text-[13px]">
                            <div class="flex items-start justify-between gap-3 border-b border-slate-100 pb-3 min-w-0">
                                <div class="min-w-0">
                                    <p class="text-[10px] uppercase tracking-wide text-slate-400">Name</p>
                                    <p class="mt-1 font-semibold text-slate-900 break-words">{{ $customer?->name ?? auth()->user()->name }}</p>
                                </div>
                                <a href="{{ route('website.account.profile.edit') }}" class="shrink-0 text-slate-400 hover:text-blue-600" title="Edit">✎</a>
                            </div>
                            <div class="flex items-start justify-between gap-3 border-b border-slate-100 pb-3 min-w-0">
                                <div class="min-w-0">
                                    <p class="text-[10px] uppercase tracking-wide text-slate-400">Email</p>
                                    <p class="mt-1 font-semibold text-slate-900 break-all">{{ auth()->user()->email }}</p>
                                </div>
                                <a href="{{ route('website.account.profile.edit') }}" class="shrink-0 text-slate-400 hover:text-blue-600" title="Edit">✎</a>
                            </div>
                            <div class="flex items-start justify-between gap-3 border-b border-slate-100 pb-3 min-w-0">
                                <div class="min-w-0">
                                    <p class="text-[10px] uppercase tracking-wide text-slate-400">Phone</p>
                                    <p class="mt-1 font-semibold text-slate-900 break-all">{{ $customer?->phone ?: 'Not added yet' }}</p>
                                </div>
                                <a href="{{ route('website.account.profile.edit') }}" class="shrink-0 text-slate-400 hover:text-blue-600" title="Edit">✎</a>
                            </div>
                            <div class="min-w-0">
                                <p class="text-[10px] uppercase tracking-wide text-slate-400">Member Since</p>
                                <p class="mt-1 font-semibold text-slate-900">{{ $memberSince ?? now()->format('M j, Y') }}</p>
                            </div>
                        </div>
                        <a href="{{ route('website.account.profile.edit') }}" class="acct-info-btn mt-4">View Account Details</a>
                    </div>

                    <div class="acct-info-card rounded-2xl border border-slate-200 bg-white p-3 sm:p-4 shadow-sm min-w-0">
                        <div class="mb-3 flex items-center justify-between gap-2 min-w-0">
                            <h2 class="text-[15px] font-bold text-slate-900">Shipping Addresses</h2>
                            <span class="shrink-0 text-[11px] font-semibold text-blue-600">+ Add</span>
                        </div>
                        <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-3 min-w-0">
                            <div class="mb-2 flex items-center justify-between gap-2">
                                <p class="text-[13px] font-bold text-slate-900">Home</p>
                                <span class="shrink-0 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700">Default</span>
                            </div>
                            <p class="text-[13px] text-slate-700 break-words whitespace-normal">{{ $customer?->address ?: 'No address saved yet. It will be saved on your next checkout.' }}</p>
                            @if($customer?->phone)
                                <p class="mt-2 text-[11px] font-medium text-slate-600 break-all">{{ $customer->phone }}</p>
                            @endif
                        </div>
                        <button type="button" class="acct-info-btn mt-4">View All Addresses</button>
                    </div>

                    <div class="acct-info-card rounded-2xl border border-slate-200 bg-white p-3 sm:p-4 shadow-sm min-w-0">
                        <div class="mb-3 flex items-center justify-between gap-2 min-w-0">
                            <h2 class="text-[15px] font-bold text-slate-900">Payment Methods</h2>
                            <span class="shrink-0 text-[11px] font-semibold text-blue-600">+ Add</span>
                        </div>
                        <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-3 min-w-0">
                            <div class="mb-2 flex items-center justify-between gap-2">
                                <p class="text-[13px] font-bold text-slate-900">Cash on Delivery</p>
                                <span class="shrink-0 rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-bold text-blue-700">Active</span>
                            </div>
                            <p class="text-[11px] text-slate-500 break-words whitespace-normal">Used for current website checkout and synced with admin order updates.</p>
                        </div>
                        <button type="button" class="acct-info-btn mt-4">View All Payment Methods</button>
                    </div>
                </div>
            </div>
        </section>
    </div>

    {{-- Order details sheet --}}
    <div x-show="detailOpen" x-cloak
         class="acct-order-overlay fixed inset-0 z-[80] flex items-end sm:items-center justify-center p-0 sm:p-5"
         @keydown.escape.window="closeDetail()">
        <div class="absolute inset-0 bg-slate-950/45" @click="closeDetail()"></div>
        <div class="acct-order-sheet relative w-full sm:max-w-[440px] max-h-[92vh] flex flex-col bg-white shadow-2xl"
             @click.outside="closeDetail()"
             x-show="detailOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-6 sm:translate-y-3 sm:scale-[0.98]"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0 translate-y-4">
            <template x-if="detail">
                <div class="min-w-0 flex flex-col max-h-[92vh]">
                    <div class="acct-order-sheet__hero shrink-0">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="acct-order-sheet__kicker">Order summary</p>
                                <h3 class="acct-order-sheet__invoice" x-text="detail.invoice"></h3>
                                <p class="acct-order-sheet__meta" x-text="'Placed on ' + detail.datetime"></p>
                            </div>
                            <button type="button" @click="closeDetail()" class="acct-order-sheet__close" aria-label="Close">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div class="acct-order-sheet__hero-row">
                            <span class="acct-order-sheet__badge" :data-status="detail.status" x-text="detail.status_label"></span>
                            <div class="acct-order-sheet__total">
                                <p class="acct-order-sheet__total-label">Amount paid</p>
                                <p class="acct-order-sheet__total-value" x-text="'{{ $currency }}' + detail.total"></p>
                            </div>
                        </div>
                    </div>

                    <div class="acct-order-sheet__body min-w-0 overflow-y-auto">
                        <div class="acct-order-sheet__status" x-show="detail.where">
                            <div class="acct-order-sheet__status-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 16h-1v-4h-1m1-4h.01M12 3a9 9 0 100 18 9 9 0 000-18z"/></svg>
                            </div>
                            <div>
                                <p class="acct-order-sheet__status-label">Current status</p>
                                <p class="acct-order-sheet__status-text" x-text="detail.where"></p>
                            </div>
                        </div>

                        <section class="acct-order-sheet__section">
                            <h4 class="acct-order-sheet__section-title">Shipment progress</h4>
                            <ol class="acct-timeline">
                                <template x-for="(step, sIdx) in buildTrack(detail)" :key="'detail-' + step.key">
                                    <li class="acct-timeline__item"
                                        :class="{
                                            'is-done': step.done,
                                            'is-active': step.active,
                                            'is-waiting': !step.done && !step.active
                                        }">
                                        <div class="acct-timeline__marker" aria-hidden="true">
                                            <span class="acct-timeline__dot" x-text="step.done || (step.active && detail.status === 'completed') ? '✓' : (sIdx + 1)"></span>
                                            <span class="acct-timeline__line" x-show="sIdx < 3"></span>
                                        </div>
                                        <div class="acct-timeline__content">
                                            <p class="acct-timeline__label" x-text="step.label"></p>
                                            <p class="acct-timeline__meta" x-text="step.active ? (step.at || 'In progress') : (step.done ? (step.at || 'Completed') : 'Pending')"></p>
                                            <p class="acct-timeline__note" x-show="step.active && step.note" x-text="step.note"></p>
                                        </div>
                                    </li>
                                </template>
                            </ol>
                        </section>

                        <section class="acct-order-sheet__section">
                            <h4 class="acct-order-sheet__section-title">Order items</h4>
                            <ul class="acct-order-items">
                                <template x-for="(item, i) in detail.items" :key="'item-' + i">
                                    <li class="acct-order-item">
                                        <div class="acct-order-item__media">
                                            <img x-show="item.image" :src="item.image" :alt="item.name" loading="lazy">
                                            <div x-show="!item.image" class="acct-order-item__fallback" aria-hidden="true">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                            </div>
                                        </div>
                                        <div class="acct-order-item__meta">
                                            <p class="acct-order-item__name" x-text="item.name"></p>
                                            <p class="acct-order-item__qty" x-text="'Quantity: ' + item.qty"></p>
                                        </div>
                                        <p class="acct-order-item__price" x-text="'{{ $currency }}' + item.subtotal"></p>
                                    </li>
                                </template>
                            </ul>
                        </section>

                        <section class="acct-order-sheet__section acct-order-sheet__section--last">
                            <h4 class="acct-order-sheet__section-title">Delivery details</h4>
                            <div class="acct-order-delivery">
                                <div>
                                    <p class="acct-order-delivery__label">Courier</p>
                                    <p class="acct-order-delivery__value" x-text="detail.courier || 'Store dispatch'"></p>
                                    <p class="acct-order-delivery__track" x-show="detail.tracking_no" x-text="'Tracking: ' + detail.tracking_no"></p>
                                </div>
                                <div>
                                    <p class="acct-order-delivery__label">Delivery address</p>
                                    <p class="acct-order-delivery__value" x-text="detail.address"></p>
                                </div>
                            </div>
                        </section>
                    </div>

                    <div class="acct-order-sheet__foot shrink-0">
                        <button type="button" @click="closeDetail()" class="acct-order-sheet__done">Close summary</button>
                    </div>
                </div>
            </template>
        </div>
    </div>
    </div>
</div>
@endsection
