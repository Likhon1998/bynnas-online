<div class="space-y-4">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-sm">
            <p class="text-[10px] font-bold text-gray-400 uppercase">Products</p>
            <p class="text-2xl font-black text-gray-900 mt-2">{{ number_format($inventory['total_products']) }}</p>
        </div>
        <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-sm">
            <p class="text-[10px] font-bold text-gray-400 uppercase">Units in Stock</p>
            <p class="text-2xl font-black text-indigo-600 mt-2">{{ number_format($inventory['total_units']) }}</p>
        </div>
        <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-sm">
            <p class="text-[10px] font-bold text-gray-400 uppercase">Retail Value</p>
            <p class="text-2xl font-black text-emerald-600 mt-2">{{ format_taka($inventory['retail_value']) }}</p>
        </div>
        <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-sm">
            <p class="text-[10px] font-bold text-gray-400 uppercase">Low Stock Alerts</p>
            <p class="text-2xl font-black text-rose-600 mt-2">{{ number_format($inventory['low_stock']) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="font-bold text-gray-900">Low Stock Items</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-[11px] uppercase font-bold text-gray-500">
                        <tr>
                            <th class="px-5 py-3 text-left">Product</th>
                            <th class="px-5 py-3 text-left">Category</th>
                            <th class="px-5 py-3 text-right">Stock</th>
                            <th class="px-5 py-3 text-right">Alert</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($lowStock as $product)
                            <tr class="hover:bg-gray-50/80">
                                <td class="px-5 py-3 font-semibold text-gray-900">{{ $product->name }}</td>
                                <td class="px-5 py-3 text-gray-500">{{ $product->category?->name ?? '—' }}</td>
                                <td class="px-5 py-3 text-right font-bold text-rose-600">{{ $product->stock_quantity }}</td>
                                <td class="px-5 py-3 text-right text-gray-500">{{ $product->alert_quantity }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-8 text-center text-gray-400">All stock levels look healthy.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100">
                <h3 class="font-bold text-gray-900">Stock by Category</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-[11px] uppercase font-bold text-gray-500">
                        <tr>
                            <th class="px-5 py-3 text-left">Category</th>
                            <th class="px-5 py-3 text-right">Products</th>
                            <th class="px-5 py-3 text-right">Units</th>
                            <th class="px-5 py-3 text-right">Cost Value</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($stockCategories as $row)
                            <tr class="hover:bg-gray-50/80">
                                <td class="px-5 py-3 font-semibold text-gray-900">{{ $row->name }}</td>
                                <td class="px-5 py-3 text-right">{{ $row->products }}</td>
                                <td class="px-5 py-3 text-right font-medium">{{ number_format($row->units) }}</td>
                                <td class="px-5 py-3 text-right font-bold text-indigo-600">{{ format_taka($row->cost_value) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-5 py-8 text-center text-gray-400">No categories found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
