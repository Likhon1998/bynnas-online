<x-app-layout>
    @php
        $locationPayload = $locations->map(fn ($l) => [
            'id' => (string) $l->id,
            'name' => $l->name,
            'type' => $l->type,
        ])->values();

        $warehouseMap = [];
        foreach ($warehouseQty as $locId => $byProduct) {
            $warehouseMap[(string) $locId] = collect($byProduct)->mapWithKeys(
                fn ($qty, $pid) => [(string) $pid => (int) $qty]
            )->all();
        }

        $productPayload = $products->map(function ($p) {
            $img = $p->image ?: ($p->imagePaths()[0] ?? null);
            return [
                'id' => (string) $p->id,
                'name' => $p->name,
                'sku' => $p->sku ?: '—',
                'barcode' => $p->barcode ?: '—',
                'image' => $img ? asset('storage/' . ltrim($img, '/')) : null,
                'stock' => (int) $p->stock_quantity,
            ];
        })->values();

        $defaultWarehouse = $locations->firstWhere('type', 'warehouse');
        $defaultStore = $locations->firstWhere('type', 'store');
    @endphp

    <div class="st-page max-w-[1200px] mx-auto pt-1 pb-14 px-0 sm:px-0 min-w-0"
         x-data="stockTransferForm({
            locations: @js($locationPayload),
            products: @js($productPayload),
            warehouseQty: @js($warehouseMap),
            fromId: '{{ old('from_location_id', $defaultWarehouse?->id ?? $locations->first()?->id) }}',
            toId: '{{ old('to_location_id', $defaultStore?->id ?? $locations->skip(1)->first()?->id) }}',
            notes: @js(old('notes', '')),
         })">
        <style>
            .st-page { --st-blue: #2563eb; --st-ink: #0f172a; --st-muted: #64748b; }
            .st-card {
                background: #fff;
                border: 1px solid #e8edf5;
                border-radius: 16px;
                box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
            }
            .st-btn-primary {
                display: inline-flex; align-items: center; justify-content: center; gap: 8px;
                background: var(--st-blue); color: #fff;
                font-size: 13px; font-weight: 700;
                padding: 11px 18px; border-radius: 10px; border: 0;
            }
            .st-btn-primary:hover { background: #1d4ed8; }
            .st-btn-outline {
                display: inline-flex; align-items: center; justify-content: center; gap: 6px;
                background: #fff; color: var(--st-blue);
                font-size: 13px; font-weight: 700;
                padding: 10px 16px; border-radius: 10px;
                border: 1.5px solid var(--st-blue); text-decoration: none;
            }
            .st-btn-outline:hover { background: #eff6ff; }
            .st-btn-ghost {
                display: inline-flex; align-items: center; justify-content: center;
                background: #fff; color: #475569;
                font-size: 13px; font-weight: 700;
                padding: 11px 16px; border-radius: 10px;
                border: 1px solid #e2e8f0; text-decoration: none;
            }
            .st-btn-ghost:hover { background: #f8fafc; }
            .st-input {
                width: 100%; border-radius: 10px; border: 1px solid #e2e8f0;
                padding: 10px 12px; font-size: 13px; color: #0f172a; background: #fff;
            }
            .st-input:focus { outline: none; border-color: #93c5fd; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12); }
            .st-label { display: block; font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 6px; }
            .st-type {
                display: flex; align-items: center; gap: 8px;
                padding: 10px 14px; border-radius: 10px; border: 1.5px solid #e2e8f0;
                background: #fff; cursor: pointer; font-size: 13px; font-weight: 700; color: #334155;
            }
            .st-type.is-active { border-color: var(--st-blue); background: #eff6ff; color: #1d4ed8; }
            .st-table th {
                font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .04em;
                color: #64748b; text-align: left; padding: 10px 12px; border-bottom: 1px solid #eef2f7;
                background: #f8fafc;
            }
            .st-table td { padding: 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; font-size: 13px; }
        </style>

        {{-- Header --}}
        <div class="mb-5 flex flex-col sm:flex-row sm:items-start justify-between gap-3">
            <div>
                <h1 class="text-[22px] sm:text-[26px] font-extrabold tracking-tight text-slate-900">New Stock Transfer</h1>
                <p class="mt-1 text-sm text-slate-500 max-w-2xl">
                    Warehouse ↔ Store updates sellable stock. Warehouse ↔ Warehouse is warehouse-only (POS/web unchanged until transferred to a store).
                </p>
            </div>
            <a href="{{ route('supply.stock-transfers.index') }}" class="st-btn-outline self-start shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to Stock Transfer List
            </a>
        </div>

        @include('supply.partials.alerts')

        <form method="POST" action="{{ route('supply.stock-transfers.store') }}" @submit="prepareSubmit">
            @csrf
            <input type="hidden" name="from_location_id" :value="fromId">
            <input type="hidden" name="to_location_id" :value="toId">
            <input type="hidden" name="notes" :value="combinedNotes()">

            <div class="grid lg:grid-cols-12 gap-5">
                {{-- Left column --}}
                <div class="lg:col-span-8 space-y-5">
                    {{-- Transfer Information --}}
                    <div class="st-card overflow-hidden">
                        <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-2.5">
                            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                            </span>
                            <h2 class="text-[15px] font-extrabold text-slate-900">Transfer Information</h2>
                        </div>
                        <div class="p-5 space-y-4">
                            <div>
                                <label class="st-label">Transfer Type</label>
                                <div class="flex flex-wrap gap-2.5">
                                    <button type="button" class="st-type" :class="transferType === 'ws' && 'is-active'" @click="setType('ws')">
                                        <span class="h-3.5 w-3.5 rounded-full border-2" :class="transferType === 'ws' ? 'border-blue-600 bg-blue-600' : 'border-slate-300'"></span>
                                        Warehouse ↔ Store
                                    </button>
                                    <button type="button" class="st-type" :class="transferType === 'ww' && 'is-active'" @click="setType('ww')">
                                        <span class="h-3.5 w-3.5 rounded-full border-2" :class="transferType === 'ww' ? 'border-blue-600 bg-blue-600' : 'border-slate-300'"></span>
                                        Warehouse ↔ Warehouse
                                    </button>
                                </div>
                            </div>

                            <div class="grid sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="st-label">From</label>
                                    <select class="st-input" x-model="fromId" @change="onFromChange()">
                                        <template x-for="loc in fromOptions" :key="loc.id">
                                            <option :value="loc.id" x-text="loc.name"></option>
                                        </template>
                                    </select>
                                    <p class="mt-1 text-[11px] text-slate-400" x-text="fromLoc ? (fromLoc.type === 'warehouse' ? 'Source warehouse' : 'Source store') : 'Source location'"></p>
                                </div>
                                <div>
                                    <label class="st-label">To</label>
                                    <select class="st-input" x-model="toId">
                                        <template x-for="loc in toOptions" :key="loc.id">
                                            <option :value="loc.id" x-text="loc.name"></option>
                                        </template>
                                    </select>
                                    <p class="mt-1 text-[11px] text-slate-400" x-text="toLoc ? (toLoc.type === 'warehouse' ? 'Destination warehouse' : 'Destination store') : 'Destination location'"></p>
                                </div>
                            </div>

                            <div class="grid sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="st-label">Transfer Date</label>
                                    <div class="relative">
                                        <input type="date" class="st-input pr-10" x-model="transferDate">
                                        <svg class="w-4 h-4 text-slate-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                </div>
                                <div>
                                    <label class="st-label">Reference / Note (Optional)</label>
                                    <input type="text" class="st-input" x-model="reference" placeholder="e.g. Regular stock transfer to downtown store">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Products --}}
                    <div class="st-card overflow-hidden">
                        <div class="px-5 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex items-center gap-2.5">
                                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                </span>
                                <h2 class="text-[15px] font-extrabold text-slate-900">Products</h2>
                            </div>
                            <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
                                <div class="relative flex-1 sm:w-72">
                                    <input type="search" class="st-input pl-9" placeholder="Search products by name, SKU or barcode..." x-model="query" @keydown.enter.prevent="addFromSearch()">
                                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                </div>
                                <button type="button" class="st-btn-outline whitespace-nowrap" @click="addFromSearch()">+ Add Product</button>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="st-table w-full min-w-[720px]">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>SKU / Barcode</th>
                                        <th>Available (From)</th>
                                        <th>Transfer Qty</th>
                                        <th>Unit</th>
                                        <th class="text-right!">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(row, index) in rows" :key="row.key">
                                        <tr>
                                            <td>
                                                <div class="flex items-center gap-3">
                                                    <div class="h-11 w-11 rounded-lg bg-slate-100 border border-slate-200 overflow-hidden flex items-center justify-center shrink-0">
                                                        <template x-if="row.product?.image">
                                                            <img :src="row.product.image" alt="" class="h-full w-full object-cover">
                                                        </template>
                                                        <template x-if="!row.product?.image">
                                                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                        </template>
                                                    </div>
                                                    <div>
                                                        <p class="font-bold text-slate-900" x-text="row.product?.name || 'Select product'"></p>
                                                        <p class="text-[11px] text-slate-400" x-show="row.product">
                                                            <span x-text="'SKU: ' + (row.product?.sku || '—')"></span>
                                                        </p>
                                                    </div>
                                                    <input type="hidden" :name="'items['+index+'][product_id]'" :value="row.product_id">
                                                </div>
                                            </td>
                                            <td>
                                                <div class="text-xs text-slate-600 leading-relaxed">
                                                    <div x-text="row.product?.sku || '—'"></div>
                                                    <div class="text-slate-400" x-text="row.product?.barcode || '—'"></div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="font-bold text-emerald-600" x-text="available(row)"></div>
                                                <div class="text-[11px] text-slate-400" x-text="fromLoc?.name || ''"></div>
                                            </td>
                                            <td>
                                                <input type="number"
                                                       class="st-input w-24"
                                                       min="1"
                                                       :max="available(row) || undefined"
                                                       :name="'items['+index+'][quantity]'"
                                                       x-model.number="row.quantity"
                                                       required>
                                            </td>
                                            <td class="text-slate-500 font-medium">pcs</td>
                                            <td class="text-right">
                                                <button type="button" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-red-500 hover:bg-red-50" @click="removeRow(index)" title="Remove">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                    <tr x-show="rows.length === 0">
                                        <td colspan="6" class="py-10 text-center text-slate-400">
                                            Search and add products to transfer.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="p-5 border-t border-slate-100">
                            <label class="st-label">Notes (Optional)</label>
                            <textarea class="st-input" rows="3" x-model="extraNotes" placeholder="Add any additional notes about this transfer..."></textarea>
                        </div>
                    </div>
                </div>

                {{-- Right column --}}
                <div class="lg:col-span-4 space-y-5">
                    <div class="st-card p-5">
                        <h3 class="text-[14px] font-extrabold text-slate-900 mb-4">How it works</h3>
                        <div class="space-y-3">
                            <div class="rounded-xl border border-emerald-100 bg-emerald-50/70 p-3.5 flex gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                </span>
                                <div>
                                    <p class="text-xs font-extrabold text-emerald-900">Warehouse ↔ Store</p>
                                    <p class="mt-0.5 text-[12px] text-emerald-800/80 leading-snug">Updates sellable stock for POS and the online store.</p>
                                </div>
                            </div>
                            <div class="rounded-xl border border-violet-100 bg-violet-50/70 p-3.5 flex gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-violet-100 text-violet-700">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                                </span>
                                <div>
                                    <p class="text-xs font-extrabold text-violet-900">Warehouse ↔ Warehouse</p>
                                    <p class="mt-0.5 text-[12px] text-violet-800/80 leading-snug">Moves held stock only — POS/web sellable qty stays the same.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="st-card p-5">
                        <h3 class="text-[14px] font-extrabold text-slate-900 mb-4">Transfer Summary</h3>
                        <dl class="space-y-3 text-sm">
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-slate-500">Total Products</dt>
                                <dd class="font-extrabold text-slate-900" x-text="rows.length"></dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-slate-500">Total Quantity</dt>
                                <dd class="font-extrabold text-slate-900" x-text="totalQty()"></dd>
                            </div>
                            <div class="border-t border-slate-100 pt-3 flex items-center justify-between gap-3">
                                <dt class="text-slate-500">From</dt>
                                <dd class="font-bold text-slate-800 text-right" x-text="fromLoc?.name || '—'"></dd>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <dt class="text-slate-500">To</dt>
                                <dd class="font-bold text-slate-800 text-right" x-text="toLoc?.name || '—'"></dd>
                            </div>
                            <div class="border-t border-slate-100 pt-3">
                                <dt class="text-slate-500 mb-2">Estimated Impact</dt>
                                <dd>
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-bold"
                                          :class="impactClass()"
                                          x-text="impactLabel()"></span>
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            {{-- Footer actions --}}
            <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
                <a href="{{ route('supply.stock-transfers.index') }}" class="st-btn-ghost">Cancel</a>
                <button type="submit" class="st-btn-primary" :disabled="rows.length === 0" :class="rows.length === 0 && 'opacity-50 cursor-not-allowed'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Create Stock Transfer
                </button>
            </div>
        </form>
    </div>

    <script>
        function stockTransferForm(cfg) {
            const today = new Date().toISOString().slice(0, 10);
            return {
                locations: cfg.locations || [],
                products: cfg.products || [],
                warehouseQty: cfg.warehouseQty || {},
                transferType: 'ws',
                fromId: String(cfg.fromId || ''),
                toId: String(cfg.toId || ''),
                transferDate: today,
                reference: '',
                extraNotes: cfg.notes || '',
                query: '',
                rows: [],
                init() {
                    this.inferType();
                    this.syncTo();
                },
                get fromLoc() { return this.locations.find(l => String(l.id) === String(this.fromId)); },
                get toLoc() { return this.locations.find(l => String(l.id) === String(this.toId)); },
                get warehouses() { return this.locations.filter(l => l.type === 'warehouse'); },
                get stores() { return this.locations.filter(l => l.type === 'store'); },
                get fromOptions() {
                    if (this.transferType === 'ww') return this.warehouses;
                    return this.locations;
                },
                get toOptions() {
                    const from = this.fromLoc;
                    if (!from) return [];
                    if (this.transferType === 'ww') {
                        return this.warehouses.filter(l => String(l.id) !== String(from.id));
                    }
                    // Warehouse ↔ Store: opposite type only
                    if (from.type === 'warehouse') return this.stores;
                    return this.warehouses;
                },
                inferType() {
                    const from = this.fromLoc;
                    const to = this.toLoc;
                    if (from && to && from.type === 'warehouse' && to.type === 'warehouse') {
                        this.transferType = 'ww';
                    } else {
                        this.transferType = 'ws';
                    }
                },
                setType(type) {
                    this.transferType = type;
                    if (type === 'ww') {
                        const wh = this.warehouses;
                        this.fromId = wh[0] ? String(wh[0].id) : '';
                        this.toId = wh[1] ? String(wh[1].id) : (wh[0] ? String(wh[0].id) : '');
                        if (String(this.fromId) === String(this.toId) && wh.length < 2) {
                            this.toId = '';
                        }
                    } else {
                        const wh = this.warehouses[0];
                        const st = this.stores[0];
                        this.fromId = wh ? String(wh.id) : '';
                        this.toId = st ? String(st.id) : '';
                    }
                    this.syncTo();
                },
                onFromChange() { this.syncTo(); },
                syncTo() {
                    if (!this.toOptions.some(l => String(l.id) === String(this.toId))) {
                        this.toId = this.toOptions[0] ? String(this.toOptions[0].id) : '';
                    }
                },
                available(row) {
                    const p = row.product;
                    if (!p) return 0;
                    const from = this.fromLoc;
                    if (!from) return 0;
                    if (from.type === 'warehouse') {
                        return Number((this.warehouseQty[from.id] || {})[p.id] || 0);
                    }
                    return Number(p.stock || 0);
                },
                searchMatches() {
                    const q = (this.query || '').trim().toLowerCase();
                    if (!q) return [];
                    return this.products.filter(p =>
                        (p.name || '').toLowerCase().includes(q)
                        || (p.sku || '').toLowerCase().includes(q)
                        || (p.barcode || '').toLowerCase().includes(q)
                    ).slice(0, 8);
                },
                addFromSearch() {
                    const matches = this.searchMatches();
                    const pick = matches[0] || null;
                    if (!pick) {
                        // if empty query, open first unused product
                        const unused = this.products.find(p => !this.rows.some(r => String(r.product_id) === String(p.id)));
                        if (!unused) return;
                        this.addProduct(unused);
                        return;
                    }
                    this.addProduct(pick);
                    this.query = '';
                },
                addProduct(product) {
                    if (!product) return;
                    const existing = this.rows.find(r => String(r.product_id) === String(product.id));
                    if (existing) {
                        existing.quantity = Number(existing.quantity || 0) + 1;
                        return;
                    }
                    this.rows.push({
                        key: Date.now() + Math.random(),
                        product_id: String(product.id),
                        product,
                        quantity: 1,
                    });
                },
                removeRow(index) { this.rows.splice(index, 1); },
                totalQty() {
                    return this.rows.reduce((s, r) => s + (Number(r.quantity) || 0), 0);
                },
                impactLabel() {
                    const from = this.fromLoc, to = this.toLoc;
                    if (!from || !to) return 'Select locations';
                    if (from.type === 'warehouse' && to.type === 'store') return 'Increase sellable stock';
                    if (from.type === 'store' && to.type === 'warehouse') return 'Decrease sellable stock';
                    return 'Warehouse move only';
                },
                impactClass() {
                    const from = this.fromLoc, to = this.toLoc;
                    if (from && to && from.type === 'warehouse' && to.type === 'store') {
                        return 'bg-emerald-50 text-emerald-700 border border-emerald-100';
                    }
                    if (from && to && from.type === 'store' && to.type === 'warehouse') {
                        return 'bg-amber-50 text-amber-800 border border-amber-100';
                    }
                    return 'bg-violet-50 text-violet-700 border border-violet-100';
                },
                combinedNotes() {
                    return [this.reference, this.extraNotes].map(s => (s || '').trim()).filter(Boolean).join('\n');
                },
                prepareSubmit(e) {
                    if (!this.rows.length) {
                        e.preventDefault();
                        alert('Add at least one product to transfer.');
                        return;
                    }
                    for (const row of this.rows) {
                        const avail = this.available(row);
                        if ((Number(row.quantity) || 0) > avail) {
                            e.preventDefault();
                            alert(`${row.product?.name || 'Product'}: only ${avail} available at source.`);
                            return;
                        }
                    }
                },
            };
        }
    </script>
</x-app-layout>
