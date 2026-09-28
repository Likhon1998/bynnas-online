<x-app-layout>
    <div class="max-w-7xl mx-auto pb-6 px-3 sm:px-4 lg:px-6"
         x-data="barcodePrinter(@js($products->getCollection()->map(fn ($p) => [
             'id' => $p->id,
             'name' => $p->name,
             'barcode' => $p->barcode,
             'price' => (float) $p->selling_price,
             'image' => $p->image ? public_storage_url($p->image) : null,
             'category' => $p->category?->name,
         ])->values()), @js(route('products.barcodes.print')), @js(route('products.barcodes')), @js(request('q', '')), @js(request('category_id', '')))">

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            {{-- Compact toolbar --}}
            <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 bg-white px-3 py-2 sm:px-3.5">
                <h2 class="mr-auto text-[15px] font-semibold text-slate-900">Print Barcodes</h2>

                <div class="flex min-w-0 flex-1 flex-wrap items-center gap-1.5 sm:max-w-xl">
                    <input type="search" x-model="q" x-ref="qInput" placeholder="Search name, barcode, SKU…"
                           @input="onSearchInput()"
                           @keydown.enter.prevent="applyFilters()"
                           class="min-w-[160px] flex-1 rounded-lg border-slate-200 bg-slate-50 px-2.5 py-1.5 text-[13px] focus:border-blue-400 focus:bg-white focus:ring-blue-100">
                    <select x-model="categoryId" @change="onCategoryChange()"
                            class="rounded-lg border-slate-200 bg-slate-50 px-2 py-1.5 text-[13px] focus:border-blue-400 focus:bg-white focus:ring-blue-100">
                        <option value="">All categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    <template x-if="q || categoryId">
                        <a href="{{ route('products.barcodes') }}" class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-[12px] font-medium text-slate-600 hover:bg-slate-50">Clear</a>
                    </template>
                </div>

                <label class="inline-flex items-center gap-1.5 text-[12px] text-slate-600">
                    <span class="hidden sm:inline">Copies</span>
                    <input type="number" x-model.number="copies" min="1" max="20"
                           class="w-14 rounded-lg border-slate-200 px-2 py-1.5 text-[13px] focus:border-blue-400 focus:ring-blue-100">
                </label>

                <label class="inline-flex items-center gap-1.5 text-[12px] font-medium text-slate-700">
                    <input type="checkbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                           :checked="allSelected" @change="toggleAll($event.target.checked)">
                    <span class="hidden sm:inline">Select all</span>
                </label>

                <a href="{{ route('products.index') }}" class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-[12px] font-medium text-slate-600 hover:bg-slate-50">
                    Products
                </a>
                <button type="button" @click="printSelected()" :disabled="selected.length === 0"
                        class="inline-flex items-center gap-1 rounded-lg bg-blue-600 px-2.5 py-1.5 text-[12px] font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Print (<span x-text="selected.length"></span>)
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50/80 text-[10px] font-semibold uppercase tracking-wide text-slate-500">
                            <th class="w-10 px-3 py-2"></th>
                            <th class="px-3 py-2">Product</th>
                            <th class="px-3 py-2">Barcode</th>
                            <th class="px-3 py-2">Preview</th>
                            <th class="px-3 py-2 text-right">Price</th>
                            <th class="px-3 py-2 text-right">Print</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($products as $product)
                            <tr class="hover:bg-slate-50/80" :class="isSelected({{ $product->id }}) ? 'bg-blue-50/40' : ''">
                                <td class="px-3 py-2">
                                    <input type="checkbox" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                           :checked="isSelected({{ $product->id }})"
                                           @change="toggle({{ $product->id }})">
                                </td>
                                <td class="px-3 py-2">
                                    <div class="flex items-center gap-2.5">
                                        <div class="flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-md border border-slate-100 bg-slate-50">
                                            @if($product->image)
                                                <img src="{{ public_storage_url($product->image) }}" alt="" class="h-full w-full object-cover">
                                            @else
                                                <svg class="h-3.5 w-3.5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <p class="truncate text-[13px] font-semibold text-slate-900">{{ $product->name }}</p>
                                            <p class="truncate text-[11px] text-slate-500">{{ $product->category?->name ?? 'Uncategorized' }}@if($product->brand?->name || $product->brand_name) · {{ $product->brand?->name ?? $product->brand_name }}@endif</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-2">
                                    <code class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[11px] text-slate-700">{{ $product->barcode }}</code>
                                </td>
                                <td class="px-3 py-2">
                                    <div class="barcode-preview w-[150px] overflow-hidden rounded-md border border-slate-200 bg-white px-1.5 py-1 text-center">
                                        <svg class="js-barcode mx-auto max-w-full" data-value="{{ $product->barcode }}"></svg>
                                        <p class="mt-0.5 truncate font-mono text-[9px] font-semibold text-slate-700">{{ $product->barcode }}</p>
                                    </div>
                                </td>
                                <td class="px-3 py-2 text-right text-[13px] font-semibold text-slate-800">
                                    {{ format_taka($product->selling_price, 'Tk ') }}
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <button type="button" @click="printOne({{ $product->id }})"
                                            class="inline-flex items-center gap-1 rounded-md border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-semibold text-slate-700 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700">
                                        Print
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-3 py-10 text-center text-sm text-slate-400">
                                    No products found. Add products first, then print barcodes here.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($products->hasPages())
                <div class="border-t border-slate-100 px-3 py-2">{{ $products->links() }}</div>
            @endif
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
    <script>
        function barcodePrinter(pageProducts, printUrl, listUrl, initialQ, initialCategoryId) {
            return {
                pageProducts,
                printUrl,
                listUrl,
                q: initialQ || '',
                categoryId: initialCategoryId ? String(initialCategoryId) : '',
                selected: [],
                copies: 1,
                filterTimer: null,
                get allSelected() {
                    return this.pageProducts.length > 0 && this.pageProducts.every((p) => this.selected.includes(p.id));
                },
                isSelected(id) {
                    return this.selected.includes(id);
                },
                toggle(id) {
                    if (this.isSelected(id)) {
                        this.selected = this.selected.filter((x) => x !== id);
                    } else {
                        this.selected.push(id);
                    }
                },
                toggleAll(checked) {
                    const ids = this.pageProducts.map((p) => p.id);
                    if (checked) {
                        this.selected = Array.from(new Set([...this.selected, ...ids]));
                    } else {
                        this.selected = this.selected.filter((id) => !ids.includes(id));
                    }
                },
                applyFilters() {
                    clearTimeout(this.filterTimer);
                    const params = new URLSearchParams();
                    const q = String(this.q || '').trim();
                    const categoryId = String(this.categoryId || '').trim();
                    if (q) params.set('q', q);
                    if (categoryId) params.set('category_id', categoryId);
                    const qs = params.toString();
                    window.location.assign(qs ? `${this.listUrl}?${qs}` : this.listUrl);
                },
                onSearchInput() {
                    clearTimeout(this.filterTimer);
                    this.filterTimer = setTimeout(() => this.applyFilters(), 350);
                },
                onCategoryChange() {
                    clearTimeout(this.filterTimer);
                    this.applyFilters();
                },
                openPrint(ids) {
                    if (!ids.length) return;
                    const params = new URLSearchParams({
                        product_ids: ids.join(','),
                        copies: String(Math.max(1, Math.min(20, this.copies || 1))),
                    });
                    window.location.href = `${this.printUrl}?${params.toString()}`;
                },
                printOne(id) {
                    this.openPrint([id]);
                },
                printSelected() {
                    this.openPrint(this.selected);
                },
                init() {
                    this.$nextTick(() => {
                        this.renderBarcodes();
                        if (this.$refs.qInput && this.q) {
                            const el = this.$refs.qInput;
                            el.focus();
                            try { el.setSelectionRange(el.value.length, el.value.length); } catch (e) {}
                        }
                    });
                },
                renderBarcodes() {
                    document.querySelectorAll('svg.js-barcode').forEach((el) => {
                        const value = el.getAttribute('data-value');
                        if (!value) return;
                        try {
                            const avail = Math.max(100, (el.parentElement?.clientWidth || 150) - 8);
                            const modules = (String(value).length * 11) + 35;
                            const barWidth = Math.max(0.8, Math.min(1.6, avail / modules));
                            JsBarcode(el, value, {
                                format: 'CODE128',
                                width: barWidth,
                                height: 28,
                                displayValue: false,
                                margin: 0,
                                background: '#ffffff',
                            });
                            el.style.maxWidth = '100%';
                            el.style.width = '100%';
                            el.style.height = 'auto';
                        } catch (e) {}
                    });
                },
            };
        }
    </script>
</x-app-layout>
