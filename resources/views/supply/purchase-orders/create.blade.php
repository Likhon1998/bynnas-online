<x-supply-layout title="New Purchase Order" subtitle="Pick products from your Inventory catalog. Receive later to add sellable stock.">
    @php
        $productOptions = $products->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'cost' => (float) $p->cost_price,
            'stock' => (int) $p->stock_quantity,
            'barcode' => $p->barcode,
        ])->values();

        $suggestedRows = $suggestedRows ?? [];
    @endphp

    @if($suppliers->isEmpty())
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            No suppliers yet.
            <a href="{{ route('supply.suppliers.create') }}" class="font-bold underline">Add a supplier</a> first.
        </div>
    @endif

    @if($products->isEmpty())
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            No products in catalog. Line items come from
            <span class="font-bold">Inventory → Product List</span>.
            <a href="{{ route('products.create') }}" class="font-bold underline">Add a product</a> first.
        </div>
    @endif

    @if(count($suggestedRows))
        <div class="mb-4 rounded-xl border border-indigo-100 bg-indigo-50 px-4 py-3 text-sm text-indigo-800">
            Prefilled <span class="font-bold">{{ count($suggestedRows) }}</span> low-stock product(s) from Reorder Level (using each product’s reorder qty).
        </div>
    @endif

    <form method="POST"
          action="{{ route('supply.purchase-orders.store') }}"
          class="space-y-5"
          x-data="poForm(@js($productOptions), @js($suggestedRows))"
          @if($suppliers->isEmpty() || $products->isEmpty()) onsubmit="return false" @endif>

        @csrf

        <div class="bg-white rounded-xl border border-gray-200 p-4 grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div>
                <label class="block text-[11px] font-semibold text-gray-500 mb-1">Supplier</label>
                <select name="supplier_id" class="w-full text-sm rounded-lg border-gray-200 py-1.5 focus:border-indigo-500 focus:ring-indigo-500" required>
                    <option value="">Select supplier</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}{{ $s->company ? ' — '.$s->company : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-gray-500 mb-1">Order date</label>
                <input type="date" name="order_date" value="{{ date('Y-m-d') }}"
                       class="w-full text-sm rounded-lg border-gray-200 py-1.5 focus:border-indigo-500 focus:ring-indigo-500" required>
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-gray-500 mb-1">Expected date</label>
                <input type="date" name="expected_date"
                       class="w-full text-sm rounded-lg border-gray-200 py-1.5 focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-gray-500 mb-1">Notes</label>
                <input type="text" name="notes" placeholder="Optional"
                       class="w-full text-sm rounded-lg border-gray-200 py-1.5 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-indigo-500">
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 bg-slate-50">
                <div>
                    <h3 class="text-sm font-bold text-gray-900">Line items</h3>
                    <p class="text-[11px] text-gray-500 mt-0.5">
                        From <a href="{{ route('products.index') }}" class="text-indigo-600 font-semibold hover:underline">Inventory → Product List</a>
                        · unit cost auto-fills from product cost
                    </p>
                </div>
                <button type="button" @click="addRow()"
                        class="inline-flex items-center gap-1 text-xs font-bold text-indigo-600 bg-indigo-50 hover:bg-indigo-100 px-2.5 py-1.5 rounded-lg">
                    + Add line
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-[10px] uppercase tracking-wider text-gray-400 border-b border-gray-100">
                            <th class="text-left font-semibold px-4 py-2 w-[42%]">Product</th>
                            <th class="text-left font-semibold px-2 py-2 w-20">In stock</th>
                            <th class="text-left font-semibold px-2 py-2 w-24">Qty</th>
                            <th class="text-left font-semibold px-2 py-2 w-28">Unit cost</th>
                            <th class="text-right font-semibold px-2 py-2 w-28">Line total</th>
                            <th class="px-3 py-2 w-10"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(row, index) in rows" :key="row.key">
                            <tr class="border-b border-gray-50 hover:bg-slate-50/60">
                                <td class="px-4 py-2">
                                    <select :name="'items['+index+'][product_id]'"
                                            x-model="row.product_id"
                                            @change="onProductChange(row)"
                                            class="w-full text-sm rounded-lg border-gray-200 py-1.5 focus:border-indigo-500 focus:ring-indigo-500"
                                            required>
                                        <option value="">Select product…</option>
                                        <template x-for="p in products" :key="p.id">
                                            <option :value="p.id" x-text="p.name + (p.barcode ? ' ('+p.barcode+')' : '')"></option>
                                        </template>
                                    </select>
                                </td>
                                <td class="px-2 py-2">
                                    <span class="text-xs text-gray-500" x-text="stockLabel(row)"></span>
                                </td>
                                <td class="px-2 py-2">
                                    <input type="number"
                                           :name="'items['+index+'][quantity]'"
                                           x-model.number="row.quantity"
                                           min="1"
                                           placeholder="1"
                                           class="w-full text-sm rounded-lg border-gray-200 py-1.5 placeholder:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                           required>
                                </td>
                                <td class="px-2 py-2">
                                    <input type="number"
                                           step="0.01"
                                           :name="'items['+index+'][unit_cost]'"
                                           x-model.number="row.unit_cost"
                                           min="0"
                                           placeholder="0.00"
                                           class="w-full text-sm rounded-lg border-gray-200 py-1.5 placeholder:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
                                           required>
                                </td>
                                <td class="px-2 py-2 text-right text-sm font-semibold text-gray-800" x-text="formatMoney(lineTotal(row))"></td>
                                <td class="px-3 py-2 text-center">
                                    <button type="button" @click="removeRow(index)" class="text-gray-400 hover:text-red-500 text-lg leading-none" title="Remove">&times;</button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-50">
                            <td colspan="4" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Order total</td>
                            <td class="px-2 py-3 text-right text-sm font-bold text-gray-900" x-text="formatMoney(orderTotal())"></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-5 py-2 rounded-lg font-bold disabled:opacity-50"
                    @disabled($suppliers->isEmpty() || $products->isEmpty())>
                Create purchase order
            </button>
            <a href="{{ route('supply.purchase-orders.index') }}" class="text-sm font-semibold text-gray-500 hover:text-gray-800">Cancel</a>
        </div>
    </form>

    <script>
        function poForm(products, initialRows) {
            return {
                products: products || [],
                rows: (initialRows && initialRows.length)
                    ? initialRows
                    : [{ key: Date.now(), product_id: '', quantity: 1, unit_cost: 0 }],
                addRow() {
                    this.rows.push({ key: Date.now() + Math.random(), product_id: '', quantity: 1, unit_cost: 0 });
                },
                removeRow(index) {
                    if (this.rows.length === 1) {
                        this.rows = [{ key: Date.now(), product_id: '', quantity: 1, unit_cost: 0 }];
                        return;
                    }
                    this.rows.splice(index, 1);
                },
                onProductChange(row) {
                    const p = this.products.find(x => String(x.id) === String(row.product_id));
                    if (p) row.unit_cost = Number(p.cost) || 0;
                },
                stockLabel(row) {
                    const p = this.products.find(x => String(x.id) === String(row.product_id));
                    return p ? p.stock : '—';
                },
                lineTotal(row) {
                    return (Number(row.quantity) || 0) * (Number(row.unit_cost) || 0);
                },
                orderTotal() {
                    return this.rows.reduce((sum, row) => sum + this.lineTotal(row), 0);
                },
                formatMoney(n) {
                    return '৳' + Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
                }
            };
        }
    </script>
</x-supply-layout>
