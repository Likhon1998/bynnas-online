<x-supply-layout
    title="Damage Product"
    subtitle="Write off damaged or expired stock so POS and the web store stay accurate."
>
    @php
        $productOptions = $products->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'sku' => $p->barcode ?? $p->sku ?? null,
            'stock' => (int) $p->stock_quantity,
        ])->values();
    @endphp

    <div
        class="space-y-6"
        x-data="damageWriteOff(@js($productOptions), {{ old('product_id') ? (int) old('product_id') : 'null' }}, {{ old('quantity', 1) }})"
    >
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-2xl border border-rose-100 bg-rose-50/70 px-4 py-3">
                <div class="text-[11px] font-bold uppercase tracking-wide text-rose-600">This page</div>
                <div class="mt-1 text-2xl font-black text-rose-900">{{ $damages->total() }}</div>
                <div class="text-xs font-medium text-rose-700/80">damage records</div>
            </div>
            <div class="rounded-2xl border border-amber-100 bg-amber-50/70 px-4 py-3">
                <div class="text-[11px] font-bold uppercase tracking-wide text-amber-700">Written off today</div>
                <div class="mt-1 text-2xl font-black text-amber-950">{{ $todayDamagedQty ?? 0 }}</div>
                <div class="text-xs font-medium text-amber-800/80">units across all records</div>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                <div class="text-[11px] font-bold uppercase tracking-wide text-slate-500">Products available</div>
                <div class="mt-1 text-2xl font-black text-slate-900">{{ $products->count() }}</div>
                <div class="text-xs font-medium text-slate-500">can be selected to write off</div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-5">
            <form
                method="POST"
                action="{{ route('supply.damage-products.store') }}"
                class="lg:col-span-3 rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden"
                @submit="if (!canSubmit()) { $event.preventDefault(); }"
            >
                @csrf
                <div class="border-b border-slate-100 bg-gradient-to-r from-rose-50 to-white px-5 py-4">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-700">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900">Write off stock</h3>
                            <p class="mt-0.5 text-sm text-slate-500">Removes qty from sellable inventory and logs a damage movement.</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-5 p-5 sm:p-6">
                    @if ($errors->any())
                        <div class="rounded-xl border border-red-100 bg-red-50 px-4 py-3 text-sm text-red-700">
                            <ul class="list-disc pl-4 space-y-0.5">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div>
                        <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Product</label>
                        <div class="relative">
                            <input
                                type="search"
                                x-model="query"
                                @focus="open = true"
                                @click.outside="open = false"
                                placeholder="Search by name or barcode…"
                                class="w-full rounded-xl border-slate-200 bg-slate-50/80 py-2.5 pl-10 pr-3 text-sm focus:border-rose-400 focus:ring-rose-200"
                                autocomplete="off"
                            >
                            <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input type="hidden" name="product_id" :value="selectedId" required>
                        <div
                            x-show="open"
                            x-cloak
                            class="mt-2 max-h-56 overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-lg"
                        >
                            <template x-for="p in filtered()" :key="p.id">
                                <button
                                    type="button"
                                    @click="selectProduct(p)"
                                    class="flex w-full items-center justify-between gap-3 border-b border-slate-50 px-3.5 py-2.5 text-left last:border-0 hover:bg-rose-50"
                                    :class="String(selectedId) === String(p.id) && 'bg-rose-50'"
                                >
                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-semibold text-slate-900" x-text="p.name"></div>
                                        <div class="truncate text-xs text-slate-400" x-text="p.sku || 'No SKU'"></div>
                                    </div>
                                    <span
                                        class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-bold"
                                        :class="p.stock < 1 ? 'bg-red-50 text-red-600' : p.stock < 5 ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700'"
                                        x-text="p.stock + ' on hand'"
                                    ></span>
                                </button>
                            </template>
                            <div x-show="filtered().length === 0" class="px-4 py-6 text-center text-sm text-slate-400">
                                No products match that search.
                            </div>
                        </div>
                        @error('product_id')
                            <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Damaged qty</label>
                            <input
                                type="number"
                                name="quantity"
                                x-model.number="qty"
                                min="1"
                                :max="maxQty || null"
                                class="w-full rounded-xl border-slate-200 bg-slate-50/80 py-2.5 text-sm focus:border-rose-400 focus:ring-rose-200"
                                required
                            >
                            <p class="mt-1 text-xs text-slate-400" x-show="selected">
                                Max: <span class="font-semibold text-slate-600" x-text="maxQty"></span>
                            </p>
                            @error('quantity')
                                <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-3">
                            <div class="text-[11px] font-bold uppercase tracking-wide text-slate-400">After write-off</div>
                            <div class="mt-1 text-lg font-black text-slate-900" x-text="selected ? Math.max(0, maxQty - (qty || 0)) + ' left' : '—'"></div>
                            <div class="text-xs text-slate-500">Remaining sellable stock</div>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-500">Damage reason</label>
                        <div class="mb-2 flex flex-wrap gap-2">
                            <template x-for="chip in reasonChips" :key="chip">
                                <button
                                    type="button"
                                    @click="reference = chip"
                                    class="rounded-full border px-3 py-1 text-xs font-semibold transition"
                                    :class="reference === chip
                                        ? 'border-rose-300 bg-rose-50 text-rose-700'
                                        : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300'"
                                    x-text="chip"
                                ></button>
                            </template>
                        </div>
                        <input
                            type="text"
                            name="reference"
                            x-model="reference"
                            value="{{ old('reference') }}"
                            class="w-full rounded-xl border-slate-200 bg-slate-50/80 py-2.5 text-sm focus:border-rose-400 focus:ring-rose-200"
                            placeholder="e.g. Broken packaging, water damage, expired"
                            required
                        >
                        @error('reference')
                            <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-100 bg-slate-50/80 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs text-slate-500">This cannot be undone from this screen. Use Stock Adjustment if you need a correction later.</p>
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-rose-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="!canSubmit()"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m-7 0V5a1 1 0 011-1h4a1 1 0 011 1v2"/>
                        </svg>
                        Write Off Stock
                    </button>
                </div>
            </form>

            <div class="lg:col-span-2 space-y-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="text-[11px] font-bold uppercase tracking-wide text-slate-400">Selected product</div>
                    <template x-if="selected">
                        <div class="mt-3">
                            <div class="text-lg font-black text-slate-900" x-text="selected.name"></div>
                            <div class="mt-1 text-sm text-slate-500" x-text="selected.sku || 'No barcode / SKU'"></div>
                            <div class="mt-4 grid grid-cols-2 gap-3">
                                <div class="rounded-xl bg-emerald-50 px-3 py-3">
                                    <div class="text-[10px] font-bold uppercase text-emerald-700">On hand</div>
                                    <div class="mt-0.5 text-xl font-black text-emerald-900" x-text="selected.stock"></div>
                                </div>
                                <div class="rounded-xl bg-rose-50 px-3 py-3">
                                    <div class="text-[10px] font-bold uppercase text-rose-700">Writing off</div>
                                    <div class="mt-0.5 text-xl font-black text-rose-900" x-text="qty || 0"></div>
                                </div>
                            </div>
                            <p class="mt-4 text-xs leading-relaxed text-slate-500">
                                Confirm the reason before submitting. Damaged qty is removed from sellable stock immediately.
                            </p>
                        </div>
                    </template>
                    <template x-if="!selected">
                        <div class="mt-4 rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center">
                            <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-white text-slate-300 shadow-sm">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10"/>
                                </svg>
                            </div>
                            <p class="text-sm font-semibold text-slate-600">Pick a product to begin</p>
                            <p class="mt-1 text-xs text-slate-400">Search by name or barcode on the left.</p>
                        </div>
                    </template>
                </div>

                <div class="rounded-2xl border border-amber-100 bg-amber-50/60 px-4 py-4 text-sm text-amber-950">
                    <div class="font-bold">Quick tips</div>
                    <ul class="mt-2 list-disc space-y-1 pl-4 text-xs text-amber-900/80">
                        <li>Use a clear reason so audits stay readable.</li>
                        <li>Write off only units that cannot be sold.</li>
                        <li>Out-of-stock products still appear, but cannot be written off.</li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-1 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Damage history</h3>
                    <p class="text-sm text-slate-500">Recent write-offs for this shop.</p>
                </div>
                <div class="text-xs font-semibold text-slate-400">
                    Showing {{ $damages->firstItem() ?? 0 }}–{{ $damages->lastItem() ?? 0 }} of {{ $damages->total() }}
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-sm">
                    <thead>
                        <tr class="bg-slate-900 text-left text-[11px] font-bold uppercase tracking-wider text-white/90">
                            <th class="px-5 py-3.5">Date</th>
                            <th class="px-5 py-3.5">Product</th>
                            <th class="px-5 py-3.5 text-center">Qty</th>
                            <th class="px-5 py-3.5">Reason</th>
                            <th class="px-5 py-3.5">By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($damages as $d)
                            <tr class="hover:bg-slate-50/80">
                                <td class="px-5 py-3.5 whitespace-nowrap text-slate-600">
                                    <div class="font-semibold text-slate-800">{{ $d->created_at->format('M d, Y') }}</div>
                                    <div class="text-xs text-slate-400">{{ $d->created_at->format('h:i A') }}</div>
                                </td>
                                <td class="px-5 py-3.5 font-semibold text-slate-900">{{ $d->product->name ?? '—' }}</td>
                                <td class="px-5 py-3.5 text-center">
                                    <span class="inline-flex min-w-[2.25rem] items-center justify-center rounded-full bg-rose-50 px-2.5 py-1 text-xs font-bold text-rose-700">
                                        −{{ $d->quantity }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-slate-600">{{ $d->reference ?: '—' }}</td>
                                <td class="px-5 py-3.5 text-slate-500">{{ $d->user->name ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-14 text-center">
                                    <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-slate-50 text-slate-300">
                                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </div>
                                    <p class="font-semibold text-slate-600">No damage records yet</p>
                                    <p class="mt-1 text-sm text-slate-400">Write-offs you submit will show up here.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($damages->hasPages())
                <div class="border-t border-slate-100 px-5 py-4">
                    {{ $damages->links() }}
                </div>
            @endif
        </div>
    </div>

    <style>[x-cloak]{display:none!important}</style>
    <script>
        function damageWriteOff(products, initialId, initialQty) {
            return {
                products,
                query: '',
                open: false,
                selectedId: initialId,
                qty: initialQty || 1,
                reference: @js(old('reference', '')),
                reasonChips: ['Broken packaging', 'Water damage', 'Expired', 'Defective', 'Transit damage'],
                get selected() {
                    return this.products.find(p => String(p.id) === String(this.selectedId)) || null;
                },
                get maxQty() {
                    return this.selected ? this.selected.stock : 0;
                },
                filtered() {
                    const q = this.query.trim().toLowerCase();
                    if (!q) return this.products.slice(0, 40);
                    return this.products.filter(p =>
                        String(p.name).toLowerCase().includes(q) ||
                        String(p.sku || '').toLowerCase().includes(q)
                    ).slice(0, 40);
                },
                selectProduct(p) {
                    this.selectedId = p.id;
                    this.query = p.name;
                    this.open = false;
                    if (!this.qty || this.qty > p.stock) this.qty = Math.min(1, p.stock) || 1;
                },
                canSubmit() {
                    return !!this.selectedId && this.maxQty > 0 && this.qty > 0 && this.qty <= this.maxQty && String(this.reference || '').trim().length > 0;
                },
                init() {
                    if (this.selected) this.query = this.selected.name;
                }
            }
        }
    </script>
</x-supply-layout>
