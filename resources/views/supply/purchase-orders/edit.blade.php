<x-supply-layout :title="'Edit ' . $order->po_number" subtitle="Only allowed before any stock is received. Lines come from Inventory → Product List.">
    @php
        $productOptions = $products->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name,
            'cost' => (float) $p->cost_price,
            'stock' => (int) $p->stock_quantity,
            'barcode' => $p->barcode,
        ])->values();

        $initialRows = $order->items->map(fn ($item) => [
            'key' => 'i'.$item->id,
            'product_id' => (string) $item->product_id,
            'quantity' => (int) $item->quantity,
            'unit_cost' => (float) $item->unit_cost,
        ])->values();
    @endphp

    <form method="POST"
          action="{{ route('supply.purchase-orders.update', $order) }}"
          class="space-y-5"
          x-data="poForm(@js($productOptions), @js($initialRows))">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-xl border border-gray-200 p-4 grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div>
                <label class="block text-[11px] font-semibold text-gray-500 mb-1">Supplier</label>
                <select name="supplier_id" class="w-full text-sm rounded-lg border-gray-200 py-1.5" required>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}" @selected($order->supplier_id == $s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-gray-500 mb-1">Order date</label>
                <input type="date" name="order_date" value="{{ old('order_date', $order->order_date?->format('Y-m-d')) }}" class="w-full text-sm rounded-lg border-gray-200 py-1.5" required>
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-gray-500 mb-1">Expected date</label>
                <input type="date" name="expected_date" value="{{ old('expected_date', $order->expected_date?->format('Y-m-d')) }}" class="w-full text-sm rounded-lg border-gray-200 py-1.5">
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-gray-500 mb-1">Notes</label>
                <input type="text" name="notes" value="{{ old('notes', $order->notes) }}" placeholder="Optional" class="w-full text-sm rounded-lg border-gray-200 py-1.5 placeholder:text-gray-400">
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100 bg-slate-50">
                <h3 class="text-sm font-bold text-gray-900">Line items</h3>
                <button type="button" @click="addRow()" class="text-xs font-bold text-indigo-600 bg-indigo-50 hover:bg-indigo-100 px-2.5 py-1.5 rounded-lg">+ Add line</button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-[10px] uppercase tracking-wider text-gray-400 border-b border-gray-100">
                            <th class="text-left font-semibold px-4 py-2">Product</th>
                            <th class="text-left font-semibold px-2 py-2 w-20">In stock</th>
                            <th class="text-left font-semibold px-2 py-2 w-24">Qty</th>
                            <th class="text-left font-semibold px-2 py-2 w-28">Unit cost</th>
                            <th class="text-right font-semibold px-2 py-2 w-28">Line total</th>
                            <th class="px-3 py-2 w-10"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(row, index) in rows" :key="row.key">
                            <tr class="border-b border-gray-50">
                                <td class="px-4 py-2">
                                    <select :name="'items['+index+'][product_id]'" x-model="row.product_id" @change="onProductChange(row)" class="w-full text-sm rounded-lg border-gray-200 py-1.5" required>
                                        <option value="">Select product…</option>
                                        <template x-for="p in products" :key="p.id">
                                            <option :value="p.id" x-text="p.name"></option>
                                        </template>
                                    </select>
                                </td>
                                <td class="px-2 py-2 text-xs text-gray-500" x-text="stockLabel(row)"></td>
                                <td class="px-2 py-2"><input type="number" :name="'items['+index+'][quantity]'" x-model.number="row.quantity" min="1" class="w-full text-sm rounded-lg border-gray-200 py-1.5" required></td>
                                <td class="px-2 py-2"><input type="number" step="0.01" :name="'items['+index+'][unit_cost]'" x-model.number="row.unit_cost" min="0" class="w-full text-sm rounded-lg border-gray-200 py-1.5" required></td>
                                <td class="px-2 py-2 text-right font-semibold" x-text="formatMoney(lineTotal(row))"></td>
                                <td class="px-3 py-2 text-center"><button type="button" @click="removeRow(index)" class="text-gray-400 hover:text-red-500 text-lg leading-none">&times;</button></td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot>
                        <tr class="bg-slate-50">
                            <td colspan="4" class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Order total</td>
                            <td class="px-2 py-3 text-right text-sm font-bold" x-text="formatMoney(orderTotal())"></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="flex gap-3">
            <button class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-5 py-2 rounded-lg font-bold">Save changes</button>
            <a href="{{ route('supply.purchase-orders.show', $order) }}" class="text-sm font-semibold text-gray-500 hover:text-gray-800 self-center">Cancel</a>
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
