<x-supply-layout
    title="Stock Adjustment"
    subtitle="Manual stock in/out plus full movement history. Sales and transfers change stock automatically — shown below with clear + / − signs."
>
    {{-- Manual adjustment form --}}
    <form method="POST" action="{{ route('supply.adjustments.store') }}" class="bg-white rounded-2xl border border-gray-100 p-5 sm:p-6 mb-6 grid md:grid-cols-4 gap-4 max-w-5xl shadow-sm">
        @csrf
        <div class="md:col-span-2">
            <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wide">Product</label>
            <select name="product_id" class="w-full rounded-xl border-gray-200 mt-1 text-sm" required>
                @foreach($products as $p)
                    <option value="{{ $p->id }}">{{ $p->name }} (stock: {{ $p->stock_quantity }})</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wide">Direction</label>
            <select name="type" class="w-full rounded-xl border-gray-200 mt-1 text-sm" required>
                <option value="in">Stock In (+)</option>
                <option value="out">Stock Out (−)</option>
            </select>
        </div>
        <div>
            <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wide">Quantity</label>
            <input type="number" name="quantity" min="1" class="w-full rounded-xl border-gray-200 mt-1 text-sm" required>
        </div>
        <div class="md:col-span-4">
            <label class="text-[11px] font-bold text-gray-500 uppercase tracking-wide">Reason / Reference</label>
            <input name="reference" class="w-full rounded-xl border-gray-200 mt-1 text-sm" placeholder="e.g. Physical count correction" required>
        </div>
        <div class="md:col-span-4 flex flex-wrap items-center gap-3">
            <button class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-xl text-sm font-bold">Save Adjustment</button>
            <p class="text-[11px] text-gray-500">
                <span class="font-bold text-emerald-600">+ green</span> = stock increased ·
                <span class="font-bold text-rose-600">− red</span> = stock decreased (sale / transfer out / damage)
            </p>
        </div>
    </form>

    {{-- Live filters (no full page reload) --}}
    <div class="mb-4 space-y-3"
         x-data="{
            search: @js(request('search', '')),
            direction: @js(request('direction', '')),
            source: @js(request('source', '')),
            startDate: @js(request('start_date', '')),
            endDate: @js(request('end_date', '')),
            baseUrl: @js(route('supply.adjustments.index')),
            busy: false,
            timer: null,
            hasFilters() {
                return !!(this.search || this.direction || this.source || this.startDate || this.endDate);
            },
            buildUrl(page) {
                const url = new URL(this.baseUrl, window.location.origin);
                if (this.search) url.searchParams.set('search', this.search);
                if (this.direction) url.searchParams.set('direction', this.direction);
                if (this.source) url.searchParams.set('source', this.source);
                if (this.startDate) url.searchParams.set('start_date', this.startDate);
                if (this.endDate) url.searchParams.set('end_date', this.endDate);
                if (page) url.searchParams.set('page', page);
                return url;
            },
            async refresh(page) {
                if (this.busy) return;
                this.busy = true;
                const url = this.buildUrl(page);
                try {
                    const res = await fetch(url.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html',
                        },
                    });
                    const html = await res.text();
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const next = doc.getElementById('sa-results');
                    const cur = document.getElementById('sa-results');
                    if (next && cur) cur.outerHTML = next.outerHTML;
                    history.replaceState({}, '', url);
                } catch (e) {
                    window.location = url.toString();
                } finally {
                    this.busy = false;
                }
            },
            onSearch() {
                clearTimeout(this.timer);
                this.timer = setTimeout(() => this.refresh(), 350);
            },
            clearAll() {
                this.search = '';
                this.direction = '';
                this.source = '';
                this.startDate = '';
                this.endDate = '';
                this.refresh();
            },
            onPagerClick(e) {
                const a = e.target.closest('a');
                if (!a || !a.closest('[data-sa-pagination]')) return;
                e.preventDefault();
                try {
                    const page = new URL(a.href).searchParams.get('page');
                    this.refresh(page);
                } catch (err) {}
            },
         }"
         @click="onPagerClick($event)">
        <div class="flex flex-wrap gap-2 items-center">
            <input type="search"
                   x-model="search"
                   @input="onSearch()"
                   placeholder="Search product, reason, reference…"
                   class="rounded-xl border-gray-200 text-sm min-w-[200px] flex-1">
            <label class="inline-flex items-center gap-1.5 text-[11px] font-bold text-slate-500">
                From
                <input type="date"
                       x-model="startDate"
                       @change="refresh()"
                       class="rounded-xl border-gray-200 text-sm font-semibold text-slate-700">
            </label>
            <label class="inline-flex items-center gap-1.5 text-[11px] font-bold text-slate-500">
                To
                <input type="date"
                       x-model="endDate"
                       @change="refresh()"
                       class="rounded-xl border-gray-200 text-sm font-semibold text-slate-700">
            </label>
            <select x-model="direction"
                    @change="refresh()"
                    class="rounded-xl border-gray-200 text-sm">
                <option value="">All directions</option>
                <option value="in">In only (+)</option>
                <option value="out">Out only (−)</option>
            </select>
            <select x-model="source"
                    @change="refresh()"
                    class="rounded-xl border-gray-200 text-sm">
                <option value="">All sources</option>
                <option value="sale">Sales</option>
                <option value="transfer">Transfers</option>
                <option value="adjustment">Manual adjustments</option>
                <option value="purchase">Purchase receive / return</option>
                <option value="damage">Damage</option>
                <option value="opening">Opening stock</option>
            </select>
            <button type="button"
                    x-show="hasFilters()"
                    x-cloak
                    @click="clearAll()"
                    class="text-sm font-semibold text-gray-500 px-3 py-2 hover:text-slate-800">
                Clear
            </button>
            <span x-show="busy" x-cloak class="text-[11px] font-bold text-slate-400">Updating…</span>
        </div>

        <div :class="busy ? 'opacity-50 pointer-events-none' : ''">
            @include('supply.adjustments.partials.results')
        </div>
    </div>
</x-supply-layout>
