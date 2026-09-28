<x-supply-layout
    title="Opening Inventory"
    subtitle="One-time setup for new products. All stock lives in your single store — synced to POS and your online shop."
    :action-url="route('products.create', ['from' => 'opening-inventory'])"
    action-label="+ Add Product"
>
    @if($products->isNotEmpty())
        <form method="POST" action="{{ route('supply.opening-inventory.store') }}" class="bg-white rounded-2xl border overflow-hidden mb-8">
            @csrf
            <div class="px-6 py-4 border-b bg-gray-50">
                <h2 class="text-sm font-bold text-gray-900">Set opening stock</h2>
                <p class="text-xs text-gray-500 mt-1">Products below have no opening inventory yet. Enter quantity once — they will move to the recorded list below.</p>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-slate-900 text-white text-[11px] uppercase tracking-widest">
                    <tr><th class="p-4 text-left">Product</th><th class="p-4">Current Stock</th><th class="p-4">Opening Qty</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($products as $i => $product)
                        <tr>
                            <td class="p-4 font-semibold">
                                {{ $product->name }}
                                <input type="hidden" name="items[{{ $i }}][product_id]" value="{{ $product->id }}">
                                @if($product->sku || $product->barcode)
                                    <div class="text-[11px] font-normal text-gray-400 mt-0.5 font-mono">{{ $product->sku ?: $product->barcode }}</div>
                                @endif
                            </td>
                            <td class="p-4 text-center">{{ $product->stock_quantity }}</td>
                            <td class="p-4">
                                <input type="number"
                                       name="items[{{ $i }}][quantity]"
                                       value="{{ old('items.'.$i.'.quantity') }}"
                                       min="0"
                                       step="1"
                                       placeholder="0"
                                       class="w-28 rounded-lg border-gray-200 mx-auto block text-center">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="p-4 border-t flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <p class="text-xs text-gray-500">Enter qty only for products you want to stock now (leave blank to skip). Each product can be opened once — later changes use Stock Adjustment.</p>
                <button class="bg-indigo-600 text-white px-6 py-2.5 rounded-xl font-bold shrink-0">Save Opening Inventory</button>
            </div>
        </form>
    @endif

    @if($openedRecords->isNotEmpty())
        <div class="bg-white rounded-2xl border overflow-hidden">
            <div class="px-6 py-4 border-b bg-gray-50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h2 class="text-sm font-bold text-gray-900">Recorded opening inventory</h2>
                    <p class="text-xs text-gray-500 mt-1">Read-only. To add or remove stock later, use Stock Adjustment.</p>
                </div>
                <a href="{{ route('supply.adjustments.index') }}" class="inline-flex items-center justify-center gap-2 bg-white border border-gray-200 text-gray-700 px-4 py-2 rounded-xl text-xs font-bold hover:bg-gray-50 transition-all shrink-0">
                    Stock Adjustment
                </a>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-slate-900 text-white text-[11px] uppercase tracking-widest">
                    <tr>
                        <th class="p-4 text-left">Date</th>
                        <th class="p-4 text-left">Product</th>
                        <th class="p-4">Opening Qty</th>
                        <th class="p-4">Current Stock</th>
                        <th class="p-4 text-left">Reference</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($openedRecords as $record)
                        <tr>
                            <td class="p-4 text-gray-600">{{ $record->created_at->format('M d, Y H:i') }}</td>
                            <td class="p-4 font-semibold">{{ $record->product->name ?? '—' }}</td>
                            <td class="p-4 text-center font-bold text-indigo-600">{{ $record->quantity }}</td>
                            <td class="p-4 text-center">{{ $record->product->stock_quantity ?? '—' }}</td>
                            <td class="p-4 text-gray-500">{{ $record->reference }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @elseif($products->isEmpty())
        <div class="bg-white rounded-2xl border p-12 text-center">
            <p class="text-lg font-bold text-gray-900">No products waiting for opening stock</p>
            <p class="mt-2 text-sm text-gray-500">Add a new product first — it will appear in the form above once created with zero stock.</p>
            <div class="mt-6 flex flex-wrap justify-center gap-3">
                <a href="{{ route('products.create', ['from' => 'opening-inventory']) }}" class="inline-flex items-center gap-2 bg-indigo-600 text-white px-5 py-2.5 rounded-xl text-sm font-bold hover:bg-indigo-700 transition-all">
                    Add Product
                </a>
                <a href="{{ route('supply.adjustments.index') }}" class="inline-flex items-center gap-2 bg-white border border-gray-200 text-gray-700 px-5 py-2.5 rounded-xl text-sm font-bold hover:bg-gray-50 transition-all">
                    Stock Adjustment
                </a>
            </div>
        </div>
    @endif
</x-supply-layout>
