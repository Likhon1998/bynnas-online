<x-app-layout>
    @php
        $outCount = (int) ($outCount ?? 0);
        $lowCount = (int) ($lowCount ?? 0);
        $totalAlerts = $outCount + $lowCount;
        $status = request('status', '');
    @endphp

    <div class="w-full min-w-0 pb-4 text-[12px] leading-snug text-slate-700"
         x-data="{
            q: @js(request('q', '')),
            status: @js($status),
            timer: null,
            listUrl: @js(route('reports.low_stock')),
            apply() {
                clearTimeout(this.timer);
                const params = new URLSearchParams();
                const q = String(this.q || '').trim();
                if (q) params.set('q', q);
                if (this.status) params.set('status', this.status);
                const qs = params.toString();
                window.location.assign(qs ? `${this.listUrl}?${qs}` : this.listUrl);
            },
            onSearch() {
                clearTimeout(this.timer);
                this.timer = setTimeout(() => this.apply(), 320);
            },
            setStatus(value) {
                this.status = value;
                this.apply();
            }
         }">

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            {{-- Compact toolbar: title + counts + actions + search --}}
            <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 px-3 py-2">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-rose-50 text-rose-600">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </span>
                    <h1 class="text-[15px] font-semibold tracking-tight text-slate-900">Low Stock</h1>
                    <span class="rounded-md bg-slate-100 px-1.5 py-0.5 text-[11px] font-semibold tabular-nums text-slate-700">{{ number_format($totalAlerts) }}</span>
                </div>

                <div class="flex flex-wrap items-center gap-1 text-[11px]">
                    <button type="button" @click="setStatus('')"
                            class="inline-flex items-center gap-1 rounded-md px-2 py-1 transition"
                            :class="!status ? 'bg-slate-900 text-white' : 'bg-slate-50 text-slate-600 hover:bg-slate-100'">
                        All <span class="tabular-nums opacity-80">{{ number_format($totalAlerts) }}</span>
                    </button>
                    <button type="button" @click="setStatus('out')"
                            class="inline-flex items-center gap-1 rounded-md px-2 py-1 transition"
                            :class="status === 'out' ? 'bg-rose-600 text-white' : 'bg-rose-50 text-rose-700 hover:bg-rose-100'">
                        Out <span class="tabular-nums opacity-80">{{ number_format($outCount) }}</span>
                    </button>
                    <button type="button" @click="setStatus('low')"
                            class="inline-flex items-center gap-1 rounded-md px-2 py-1 transition"
                            :class="status === 'low' ? 'bg-amber-500 text-white' : 'bg-amber-50 text-amber-800 hover:bg-amber-100'">
                        Low <span class="tabular-nums opacity-80">{{ number_format($lowCount) }}</span>
                    </button>
                </div>

                <div class="relative min-w-[160px] flex-1 sm:max-w-xs">
                    <svg class="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"/></svg>
                    <input type="search" x-model="q" @input="onSearch()" @keydown.enter.prevent="apply()"
                           placeholder="Search name, SKU, barcode…"
                           class="w-full rounded-lg border-slate-200 bg-slate-50 py-1.5 pl-8 pr-2.5 text-[13px] focus:border-rose-300 focus:bg-white focus:ring-rose-100">
                </div>

                <div class="ml-auto flex flex-wrap items-center gap-1">
                    <a href="{{ route('supply.purchase-orders.create') }}"
                       class="inline-flex items-center gap-1 rounded-md bg-rose-600 px-2.5 py-1 text-[11px] font-semibold text-white hover:bg-rose-700">
                        Restock PO
                    </a>
                    <a href="{{ route('supply.adjustments.index') }}"
                       class="rounded-md border border-slate-200 px-2 py-1 text-[11px] font-medium text-slate-600 hover:bg-slate-50">
                        Adjust
                    </a>
                    <a href="{{ route('products.index') }}"
                       class="rounded-md border border-slate-200 px-2 py-1 text-[11px] font-medium text-slate-600 hover:bg-slate-50">
                        Products
                    </a>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-left">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/80 text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                            <th class="px-3 py-2">Product</th>
                            <th class="px-3 py-2">Category</th>
                            <th class="px-3 py-2">SKU / Barcode</th>
                            <th class="px-3 py-2 text-center">Stock</th>
                            <th class="px-3 py-2 text-center">Alert at</th>
                            <th class="px-3 py-2 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($lowStockItems as $item)
                            @php $isOut = $item->stock_quantity <= 0; @endphp
                            <tr class="{{ $isOut ? 'bg-rose-50/40' : 'hover:bg-amber-50/40' }}">
                                <td class="px-3 py-2">
                                    <p class="text-[13px] font-semibold text-slate-900">{{ $item->name }}</p>
                                    @if($item->brand?->name || $item->brand_name)
                                        <p class="text-[11px] text-slate-400">{{ $item->brand?->name ?? $item->brand_name }}</p>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-[12px] text-slate-600">
                                    {{ $item->category->name ?? 'Uncategorized' }}
                                </td>
                                <td class="px-3 py-2">
                                    <code class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[11px] text-slate-700">{{ $item->sku ?: $item->barcode ?: 'N/A' }}</code>
                                </td>
                                <td class="px-3 py-2 text-center">
                                    @if($isOut)
                                        <span class="inline-flex rounded-md bg-rose-600 px-2 py-0.5 text-[11px] font-bold text-white">Out (0)</span>
                                    @else
                                        <span class="inline-flex rounded-md bg-amber-100 px-2 py-0.5 text-[11px] font-bold text-amber-800">{{ $item->stock_quantity }} left</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-center text-[12px] tabular-nums text-slate-500">
                                    {{ $item->alert_quantity }}
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <a href="{{ route('products.edit', $item) }}"
                                       class="inline-flex rounded-md border border-indigo-100 bg-indigo-50 px-2 py-1 text-[11px] font-semibold text-indigo-700 hover:bg-indigo-100">
                                        Edit
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-3 py-12 text-center">
                                    <p class="text-[13px] font-semibold text-emerald-700">Stock levels look healthy</p>
                                    <p class="mt-0.5 text-[11px] text-slate-400">Nothing is at or below its alert quantity.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($lowStockItems->hasPages())
                <div class="border-t border-slate-100 px-3 py-2">{{ $lowStockItems->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
