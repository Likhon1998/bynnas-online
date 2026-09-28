<div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100">
        <h3 class="font-bold text-gray-900">Products Report</h3>
        <p class="text-xs text-gray-500 mt-0.5">Best performing products by units sold and revenue</p>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-[11px] uppercase font-bold text-gray-500">
                <tr>
                    <th class="px-5 py-3 text-left">#</th>
                    <th class="px-5 py-3 text-left">Product</th>
                    <th class="px-5 py-3 text-left">Category</th>
                    <th class="px-5 py-3 text-right">Units Sold</th>
                    <th class="px-5 py-3 text-right">Revenue</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($productRows as $i => $row)
                    <tr class="hover:bg-gray-50/80">
                        <td class="px-5 py-3 text-gray-400">{{ $i + 1 }}</td>
                        <td class="px-5 py-3 font-semibold text-gray-900">{{ $row->product?->name ?? 'Unknown' }}</td>
                        <td class="px-5 py-3 text-gray-500">{{ $row->product?->category?->name ?? '—' }}</td>
                        <td class="px-5 py-3 text-right font-medium">{{ $row->sold }}</td>
                        <td class="px-5 py-3 text-right font-bold text-indigo-600">{{ format_taka($row->revenue ?? 0) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-10 text-center text-gray-400">No product sales in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
