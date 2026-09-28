<x-supply-layout :title="'PO ' . $order->po_number" :subtitle="'Supplier: ' . ($order->supplier->name ?? '—') . ' · Stock syncs to POS & web on receive.'">
    <div class="grid md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-2xl border p-4">
            <p class="text-xs text-gray-400 uppercase font-bold">Supplier</p>
            <p class="font-bold">{{ $order->supplier->name }}</p>
        </div>
        <div class="bg-white rounded-2xl border p-4">
            <p class="text-xs text-gray-400 uppercase font-bold">Status</p>
            <p class="font-bold uppercase tracking-wide
                @if($order->status === 'received') text-emerald-600
                @elseif($order->status === 'partial') text-amber-600
                @elseif($order->status === 'cancelled') text-red-600
                @else text-indigo-600 @endif">{{ $order->status }}</p>
        </div>
        <div class="bg-white rounded-2xl border p-4">
            <p class="text-xs text-gray-400 uppercase font-bold">PO Total</p>
            <p class="font-bold">{{ format_taka($order->total_amount) }}</p>
        </div>
        <div class="bg-white rounded-2xl border p-4">
            <p class="text-xs text-gray-400 uppercase font-bold">AP Balance (shop)</p>
            <p class="font-bold">{{ format_taka($apBalance ?? 0) }}</p>
        </div>
    </div>

    <div class="flex flex-wrap gap-2 mb-6">
        @if($order->status !== 'cancelled' && $order->items->every(fn ($i) => $i->received_quantity === 0))
            <a href="{{ route('supply.purchase-orders.edit', $order) }}" class="inline-flex items-center px-4 py-2 rounded-xl bg-white border border-gray-200 text-sm font-bold text-gray-700 hover:bg-gray-50">Edit PO</a>
            <form method="POST" action="{{ route('supply.purchase-orders.cancel', $order) }}" data-confirm="Cancel this purchase order?" data-confirm-title="Cancel order?" data-confirm-ok="Cancel order" data-confirm-tone="warning">
                @csrf
                <button class="inline-flex items-center px-4 py-2 rounded-xl bg-red-50 border border-red-100 text-sm font-bold text-red-700 hover:bg-red-100">Cancel PO</button>
            </form>
        @endif
        @if(in_array($order->status, ['partial', 'received'], true) && $order->items->sum('received_quantity') > 0)
            <a href="{{ route('supply.purchase-returns.create', ['purchase_order_id' => $order->id]) }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-amber-500 text-white text-sm font-bold hover:bg-amber-600 shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                Return excess to supplier
            </a>
        @endif
        <a href="{{ route('supply.purchase-orders.index') }}" class="inline-flex items-center px-4 py-2 rounded-xl bg-white border border-gray-200 text-sm font-bold text-gray-700 hover:bg-gray-50">Back to list</a>
    </div>

    @if(!in_array($order->status, ['received', 'cancelled'], true))
        <form method="POST" action="{{ route('supply.purchase-orders.receive', $order) }}" class="bg-white rounded-2xl border overflow-hidden mb-8">
            @csrf
            <div class="px-5 py-4 border-b bg-slate-50 flex flex-col sm:flex-row sm:items-end justify-between gap-3">
                <div>
                    <h3 class="font-bold text-gray-900">Receive stock</h3>
                    <p class="text-xs text-gray-500 mt-1">Store = sellable on POS &amp; web. Warehouse = held until Stock Transfer.</p>
                </div>
                <div class="sm:w-64">
                    <label class="text-[10px] font-bold text-gray-500 uppercase tracking-wide">Receive into</label>
                    <select name="receive_location_id" class="w-full rounded-xl border-gray-200 mt-1 text-sm" required>
                        @foreach($receiveLocations ?? [] as $loc)
                            <option value="{{ $loc->id }}" @selected((int) old('receive_location_id', $defaultReceiveLocationId) === (int) $loc->id)>
                                {{ ucfirst($loc->type) }}: {{ $loc->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-slate-900 text-white text-[11px] uppercase tracking-widest">
                    <tr>
                        <th class="p-4 text-left">Product</th>
                        <th class="p-4">Unit cost</th>
                        <th class="p-4">Ordered</th>
                        <th class="p-4">Received</th>
                        <th class="p-4">Receive now</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($order->items as $i => $item)
                        @php $remaining = max(0, $item->quantity - $item->received_quantity); @endphp
                        <tr class="{{ $remaining < 1 ? 'bg-slate-50/80 text-gray-400' : '' }}">
                            <td class="p-4 font-semibold {{ $remaining >= 1 ? 'text-gray-900' : '' }}">
                                {{ $item->product->name }}
                                <input type="hidden" name="items[{{ $i }}][id]" value="{{ $item->id }}">
                            </td>
                            <td class="p-4 text-center">{{ format_taka($item->unit_cost) }}</td>
                            <td class="p-4 text-center">{{ $item->quantity }}</td>
                            <td class="p-4 text-center">{{ $item->received_quantity }}</td>
                            <td class="p-4">
                                @if($remaining < 1)
                                    <input type="hidden" name="items[{{ $i }}][receive_qty]" value="0">
                                    <span class="block text-center text-xs font-semibold text-emerald-600">Done</span>
                                @else
                                    <input type="number"
                                           name="items[{{ $i }}][receive_qty]"
                                           value="{{ old('items.'.$i.'.receive_qty', $remaining) }}"
                                           min="0"
                                           max="{{ $remaining }}"
                                           class="w-24 rounded-lg border-gray-200 mx-auto block text-center">
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="p-4 border-t">
                <button class="bg-emerald-600 text-white px-6 py-2.5 rounded-xl font-bold hover:bg-emerald-700">Receive Stock</button>
            </div>
        </form>
    @elseif($order->status === 'received')
        <div class="p-6 bg-emerald-50 border border-emerald-100 rounded-2xl text-emerald-800 font-semibold mb-8">
            Fully received. Stock is available on POS and the online store.
            <span class="block mt-2 text-sm font-medium text-emerald-700">
                Got extra units? Use <a href="{{ route('supply.purchase-returns.create', ['purchase_order_id' => $order->id]) }}" class="underline font-bold">Return excess to supplier</a>.
            </span>
        </div>
    @elseif($order->status === 'cancelled')
        <div class="p-6 bg-red-50 border border-red-100 rounded-2xl text-red-800 font-semibold mb-8">
            This purchase order was cancelled. No stock was received.
        </div>
    @endif

    @if(in_array($order->status, ['partial', 'received'], true) && ($cashAccounts?->count() ?? 0) > 0)
        @php $remainingDue = $order->remainingPayable(); @endphp
        @if($remainingDue > 0)
            <form method="POST" action="{{ route('supply.purchase-orders.pay', $order) }}" class="bg-white rounded-2xl border p-6 mb-8 max-w-2xl space-y-4">
                @csrf
                <div>
                    <h3 class="font-bold text-gray-900">Pay supplier</h3>
                    <p class="text-xs text-gray-500 mt-1">
                        PO total {{ format_taka($order->total_amount) }}
                        · Paid {{ format_taka($order->paid_amount) }}
                        · Remaining {{ format_taka($remainingDue) }}
                    </p>
                </div>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-bold text-gray-500 uppercase">Amount</label>
                        <input type="number" step="0.01" min="0.01" max="{{ $remainingDue }}" name="amount" value="{{ old('amount', $remainingDue) }}" class="w-full rounded-xl border-gray-200 mt-1" required>
                    </div>
                    <div>
                        <label class="text-xs font-bold text-gray-500 uppercase">Pay from</label>
                        <select name="account_id" class="w-full rounded-xl border-gray-200 mt-1" required>
                            @foreach($cashAccounts as $account)
                                <option value="{{ $account->id }}">{{ $account->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="text-xs font-bold text-gray-500 uppercase">Notes</label>
                    <input type="text" name="notes" class="w-full rounded-xl border-gray-200 mt-1" placeholder="Optional reference">
                </div>
                <button class="bg-indigo-600 text-white px-6 py-2.5 rounded-xl font-bold hover:bg-indigo-700">Record Payment</button>
            </form>
        @else
            <div class="p-5 bg-emerald-50 border border-emerald-100 rounded-2xl text-emerald-800 font-semibold mb-8">
                Fully paid ({{ format_taka($order->paid_amount) }}).
            </div>
        @endif
    @endif

    @if(($ledgerEntries?->count() ?? 0) > 0)
        <div class="bg-white rounded-2xl border overflow-hidden">
            <div class="px-5 py-4 border-b bg-slate-50">
                <h3 class="font-bold text-gray-900">Accounting entries for this PO</h3>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-slate-900 text-white text-[11px] uppercase tracking-widest">
                    <tr>
                        <th class="p-4 text-left">Txn #</th>
                        <th class="p-4 text-left">Date</th>
                        <th class="p-4 text-left">Description</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($ledgerEntries as $txn)
                        <tr>
                            <td class="p-4 font-mono text-xs">{{ $txn->transaction_no }}</td>
                            <td class="p-4">{{ \Carbon\Carbon::parse($txn->transaction_date)->format('M d, Y') }}</td>
                            <td class="p-4">{{ $txn->description }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</x-supply-layout>
