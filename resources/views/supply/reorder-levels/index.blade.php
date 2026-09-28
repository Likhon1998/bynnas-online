<x-supply-layout title="Reorder Level" subtitle="Alert = when to warn. Reorder qty = how many to order when stock is low.">
    <div class="mb-5 grid sm:grid-cols-2 gap-3 text-sm">
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="text-[11px] font-bold uppercase tracking-wide text-gray-400 mb-1">Alert level</p>
            <p class="text-gray-700 text-sm">When stock reaches this number, the product is marked <span class="font-semibold text-amber-700">low stock</span> (dashboard, product list, analytics).</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="text-[11px] font-bold uppercase tracking-wide text-gray-400 mb-1">Reorder qty</p>
            <p class="text-gray-700 text-sm">Suggested quantity to buy on the next Purchase Order. Used when you click <span class="font-semibold">Create PO for low stock</span>.</p>
        </div>
    </div>

    @if($lowStock->count())
        <div class="mb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
            <p class="text-sm font-semibold text-amber-800">
                {{ $lowStock->count() }} product(s) at or below alert level.
            </p>
            <a href="{{ route('supply.purchase-orders.create', ['from_reorder' => 1]) }}"
               class="inline-flex items-center justify-center text-xs font-bold bg-amber-600 hover:bg-amber-700 text-white px-3 py-2 rounded-lg">
                Create PO for low stock
            </a>
            <a href="{{ route('reports.low_stock') }}"
               class="inline-flex items-center justify-center text-xs font-bold bg-white border border-amber-300 text-amber-800 hover:bg-amber-100 px-3 py-2 rounded-lg">
                Low stock report
            </a>
        </div>
    @endif

    <form method="POST" action="{{ route('supply.reorder-levels.update') }}" class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        @csrf @method('PUT')
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-900 text-white text-[10px] uppercase tracking-wider">
                        <th class="text-left font-semibold p-3">Product</th>
                        <th class="font-semibold p-3 w-24">Stock</th>
                        <th class="font-semibold p-3 w-28">Alert level</th>
                        <th class="font-semibold p-3 w-28">Reorder qty</th>
                        <th class="font-semibold p-3 w-24">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($products as $i => $product)
                        @php $isLow = $product->stock_quantity <= $product->alert_quantity; @endphp
                        <tr class="{{ $isLow ? 'bg-amber-50/70' : '' }}">
                            <td class="p-3 font-semibold text-gray-900">
                                {{ $product->name }}
                                <input type="hidden" name="products[{{ $i }}][id]" value="{{ $product->id }}">
                            </td>
                            <td class="p-3 text-center font-bold">{{ $product->stock_quantity }}</td>
                            <td class="p-3">
                                <input type="number" name="products[{{ $i }}][alert_quantity]"
                                       value="{{ $product->alert_quantity }}" min="0"
                                       class="w-20 text-sm text-center rounded-lg border-gray-200 py-1 mx-auto block">
                            </td>
                            <td class="p-3">
                                <input type="number" name="products[{{ $i }}][reorder_quantity]"
                                       value="{{ $product->reorder_quantity ?? 0 }}" min="0"
                                       class="w-20 text-sm text-center rounded-lg border-gray-200 py-1 mx-auto block">
                            </td>
                            <td class="p-3 text-center">
                                @if($isLow)
                                    <span class="text-[10px] font-bold uppercase tracking-wide text-amber-700 bg-amber-100 px-2 py-1 rounded-md">Reorder</span>
                                @else
                                    <span class="text-[10px] font-bold uppercase tracking-wide text-emerald-700 bg-emerald-50 px-2 py-1 rounded-md">OK</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-8 text-center text-gray-400">
                                No products yet. Add products under Inventory first.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($products->count())
            <div class="p-3 border-t border-gray-100">
                <button class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-4 py-2 rounded-lg font-bold">
                    Save reorder levels
                </button>
            </div>
        @endif
    </form>
</x-supply-layout>
