<div class="space-y-4">
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        @foreach([
            ['Total', $orderSummary['total'], 'text-indigo-600'],
            ['POS', $orderSummary['pos'], 'text-sky-600'],
            ['Online', $orderSummary['web'], 'text-emerald-600'],
            ['Pending', $orderSummary['pending'], 'text-amber-600'],
            ['Completed', $orderSummary['completed'], 'text-violet-600'],
        ] as [$label, $value, $tone])
            <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-sm">
                <p class="text-[10px] font-bold text-gray-400 uppercase">{{ $label }}</p>
                <p class="text-2xl font-black {{ $tone }} mt-2">{{ number_format($value) }}</p>
            </div>
        @endforeach
    </div>

    <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h3 class="font-bold text-gray-900">Orders Report</h3>
                <p class="text-xs text-gray-500">Recent orders in the selected period</p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-[11px] uppercase font-bold text-gray-500">
                    <tr>
                        <th class="px-5 py-3 text-left">Invoice</th>
                        <th class="px-5 py-3 text-left">Date</th>
                        <th class="px-5 py-3 text-left">Customer</th>
                        <th class="px-5 py-3 text-left">Channel</th>
                        <th class="px-5 py-3 text-left">Payment</th>
                        <th class="px-5 py-3 text-left">Status</th>
                        <th class="px-5 py-3 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($recentOrders as $order)
                        <tr class="hover:bg-gray-50/80">
                            <td class="px-5 py-3 font-semibold text-gray-900">{{ $order->invoice_no }}</td>
                            <td class="px-5 py-3 text-gray-500 whitespace-nowrap">{{ $order->created_at->format('d M Y, h:i A') }}</td>
                            <td class="px-5 py-3">{{ $order->customer?->name ?? 'Walk-in' }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $order->counter_id ? 'bg-sky-50 text-sky-700' : 'bg-emerald-50 text-emerald-700' }}">
                                    {{ $order->counter_id ? 'POS' : 'Online' }}
                                </span>
                            </td>
                            <td class="px-5 py-3 capitalize text-gray-600">{{ $order->payment_method }}</td>
                            <td class="px-5 py-3 capitalize text-gray-600">{{ $order->status }}</td>
                            <td class="px-5 py-3 text-right font-bold text-indigo-600">{{ format_taka($order->total_amount) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-5 py-10 text-center text-gray-400">No orders in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
