<div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100">
        <h3 class="font-bold text-gray-900">Customers Report</h3>
        <p class="text-xs text-gray-500 mt-0.5">Top customers by revenue in the selected period</p>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-[11px] uppercase font-bold text-gray-500">
                <tr>
                    <th class="px-5 py-3 text-left">Customer</th>
                    <th class="px-5 py-3 text-left">Phone</th>
                    <th class="px-5 py-3 text-right">Orders</th>
                    <th class="px-5 py-3 text-right">Revenue</th>
                    <th class="px-5 py-3 text-right">Discounts</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($customers as $row)
                    <tr class="hover:bg-gray-50/80">
                        <td class="px-5 py-3 font-semibold text-gray-900">{{ $row->customer?->name ?? 'Unknown' }}</td>
                        <td class="px-5 py-3 text-gray-500">{{ $row->customer?->phone ?? '—' }}</td>
                        <td class="px-5 py-3 text-right font-medium">{{ $row->orders }}</td>
                        <td class="px-5 py-3 text-right font-bold text-indigo-600">{{ format_taka($row->revenue) }}</td>
                        <td class="px-5 py-3 text-right text-amber-600 font-medium">{{ format_taka($row->discounts) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-10 text-center text-gray-400">No customer sales in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
