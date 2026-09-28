<x-supply-layout title="Purchase Returns" subtitle="Return excess or unwanted stock to suppliers. Stock ↓ · AP ↓." :action-url="route('supply.purchase-returns.create')" action-label="+ New Return">
    <div class="mb-4 rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-600">
        Tip: open a <a href="{{ route('supply.purchase-orders.index') }}" class="font-semibold text-indigo-600 underline">Purchase Order</a>
        and click <strong>Return excess to supplier</strong> when the supplier sent too many items.
    </div>
    <div class="bg-white rounded-2xl border overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-900 text-white text-[11px] uppercase tracking-widest">
                <tr>
                    <th class="p-4 text-left">Return #</th>
                    <th class="p-4 text-left">Supplier</th>
                    <th class="p-4 text-left">Linked PO</th>
                    <th class="p-4">Status</th>
                    <th class="p-4">Total</th>
                    <th class="p-4">Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($returns as $return)
                    <tr>
                        <td class="p-4 font-bold">{{ $return->return_number }}</td>
                        <td class="p-4">{{ $return->supplier->name ?? '—' }}</td>
                        <td class="p-4">
                            @if($return->purchaseOrder)
                                <a href="{{ route('supply.purchase-orders.show', $return->purchaseOrder) }}" class="text-indigo-600 font-semibold hover:underline">
                                    {{ $return->purchaseOrder->po_number }}
                                </a>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="p-4 uppercase text-xs font-bold">{{ $return->status }}</td>
                        <td class="p-4">{{ format_taka($return->total_amount) }}</td>
                        <td class="p-4">{{ $return->created_at->format('M d, Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-10 text-center text-gray-400">No purchase returns yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $returns->links() }}</div>
    </div>
</x-supply-layout>
