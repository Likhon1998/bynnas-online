<div class="space-y-4">
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-sm">
            <p class="text-[10px] font-bold text-gray-400 uppercase">Total Discounts</p>
            <p class="text-2xl font-black text-amber-600 mt-2">{{ format_taka($discountTotal) }}</p>
        </div>
        <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-sm">
            <p class="text-[10px] font-bold text-gray-400 uppercase">Discounted Orders</p>
            <p class="text-2xl font-black text-gray-900 mt-2">{{ number_format($discounts->count()) }}</p>
        </div>
        <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-sm">
            <p class="text-[10px] font-bold text-gray-400 uppercase">vs Last Period</p>
            <p class="text-2xl font-black {{ $kpis['change']['discounts'] >= 0 ? 'text-rose-600' : 'text-emerald-600' }} mt-2">
                {{ $kpis['change']['discounts'] >= 0 ? '+' : '' }}{{ $kpis['change']['discounts'] }}%
            </p>
        </div>
    </div>

    <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="font-bold text-gray-900">Discount Report</h3>
            <p class="text-xs text-gray-500 mt-0.5">Orders with discounts applied</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-[11px] uppercase font-bold text-gray-500">
                    <tr>
                        <th class="px-5 py-3 text-left">Invoice</th>
                        <th class="px-5 py-3 text-left">Date</th>
                        <th class="px-5 py-3 text-left">Customer</th>
                        <th class="px-5 py-3 text-right">Gross</th>
                        <th class="px-5 py-3 text-right">Discount</th>
                        <th class="px-5 py-3 text-right">Net</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($discounts as $order)
                        <tr class="hover:bg-gray-50/80">
                            <td class="px-5 py-3 font-semibold text-gray-900">{{ $order->invoice_no }}</td>
                            <td class="px-5 py-3 text-gray-500 whitespace-nowrap">{{ $order->created_at->format('d M Y') }}</td>
                            <td class="px-5 py-3">{{ $order->customer?->name ?? 'Walk-in' }}</td>
                            <td class="px-5 py-3 text-right font-medium">{{ format_taka($order->total_amount) }}</td>
                            <td class="px-5 py-3 text-right font-bold text-amber-600">{{ format_taka($order->discount_amount) }}</td>
                            <td class="px-5 py-3 text-right font-bold text-gray-900">{{ format_taka($order->netPayable()) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-gray-400">No discounts in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
