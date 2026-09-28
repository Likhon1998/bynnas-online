<x-supply-layout title="Purchase Orders" subtitle="Order from suppliers, receive stock, update Inventory & Accounts Payable." :action-url="route('supply.purchase-orders.create')" action-label="+ New Purchase Order">
    <div class="bg-white rounded-2xl border overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-slate-900 text-white text-[11px] uppercase tracking-widest">
                <tr>
                    <th class="p-4 text-left">PO #</th>
                    <th class="p-4">Supplier</th>
                    <th class="p-4">Date</th>
                    <th class="p-4">Status</th>
                    <th class="p-4">Total</th>
                    <th class="p-4 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($orders as $order)
                    <tr>
                        <td class="p-4 font-bold">{{ $order->po_number }}</td>
                        <td class="p-4">{{ $order->supplier->name }}</td>
                        <td class="p-4">{{ $order->order_date->format('M d, Y') }}</td>
                        <td class="p-4">
                            <span class="px-2 py-1 rounded-lg text-xs font-bold uppercase
                                @if($order->status === 'received') bg-emerald-50 text-emerald-700
                                @elseif($order->status === 'partial') bg-amber-50 text-amber-700
                                @elseif($order->status === 'cancelled') bg-red-50 text-red-700
                                @else bg-indigo-50 text-indigo-700 @endif">
                                {{ $order->status }}
                            </span>
                        </td>
                        <td class="p-4">{{ format_taka($order->total_amount) }}</td>
                        <td class="p-4 text-right">
                            <a href="{{ route('supply.purchase-orders.show', $order) }}" class="text-indigo-600 font-bold">
                                {{ in_array($order->status, ['ordered', 'partial']) ? 'View / Receive' : 'View' }}
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-10 text-center text-gray-400">No purchase orders yet. Add a supplier first, then create a PO.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $orders->links() }}</div>
    </div>
</x-supply-layout>
