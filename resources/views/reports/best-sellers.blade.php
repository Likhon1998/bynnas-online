<x-app-layout>
    <div class="max-w-7xl mx-auto pt-8 pb-12 px-4 sm:px-6 lg:px-8">
        
        <div class="mb-8 bg-slate-900 rounded-[32px] p-8 shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-6 relative overflow-hidden">
            <div class="absolute right-0 top-0 w-64 h-64 bg-white opacity-5 rounded-full blur-3xl -mr-10 -mt-10 pointer-events-none"></div>

            <div class="flex items-center gap-5 relative z-10">
                <div class="w-16 h-16 bg-white/10 border border-white/20 text-white rounded-2xl flex items-center justify-center shadow-inner">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                </div>
                <div>
                    <span class="bg-indigo-500 text-white text-[10px] font-black px-2.5 py-1 rounded-md uppercase tracking-widest mb-2 inline-block shadow-sm">Inventory Analytics</span>
                    <h2 class="text-3xl font-black text-white tracking-tight">Best Selling Items</h2>
                    <p class="text-sm text-slate-300 font-medium mt-1">Track your highest-performing products.</p>
                </div>
            </div>
            
            <div class="relative z-10 flex flex-col md:flex-row items-end gap-3">
                <form action="{{ route('reports.best_sellers') }}" method="GET" class="flex flex-wrap items-end gap-2">
                    <div>
                        <label for="start_date" class="block text-slate-400 text-[10px] font-bold uppercase tracking-wider mb-1">From</label>
                        <input type="date" name="start_date" id="start_date" value="{{ $startDate->format('Y-m-d') }}" class="bg-white/10 border border-white/20 text-white text-sm rounded-xl focus:ring-indigo-500 focus:border-indigo-500 block p-2.5 shadow-sm font-medium [color-scheme:dark]">
                    </div>
                    <div>
                        <label for="end_date" class="block text-slate-400 text-[10px] font-bold uppercase tracking-wider mb-1">To</label>
                        <input type="date" name="end_date" id="end_date" value="{{ $endDate->format('Y-m-d') }}" class="bg-white/10 border border-white/20 text-white text-sm rounded-xl focus:ring-indigo-500 focus:border-indigo-500 block p-2.5 shadow-sm font-medium [color-scheme:dark]">
                    </div>
                    <button type="submit" class="bg-indigo-500 text-white px-4 py-2.5 rounded-xl font-bold text-sm hover:bg-indigo-400 transition shadow-lg active:scale-95 flex items-center gap-2">
                        Filter
                    </button>

                    @if(request('start_date') || request('end_date'))
                        <a href="{{ route('reports.best_sellers') }}" class="bg-slate-700 text-white px-4 py-2.5 rounded-xl font-bold text-sm hover:bg-slate-600 transition shadow-lg active:scale-95" title="Reset Filters">
                            Clear
                        </a>
                    @endif
                </form>
            </div>
        </div>

        <h3 class="text-xl font-black text-gray-900 tracking-tight mb-4 px-2">
            Top Items for: <span class="text-indigo-600">{{ $startDate->format('M j, Y') }} - {{ $endDate->format('M j, Y') }}</span>
        </h3>

        <div class="bg-white rounded-[32px] shadow-sm border border-gray-100 overflow-hidden ring-1 ring-gray-50">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse whitespace-nowrap">
                    <thead>
                        <tr class="bg-gray-50/50 border-b border-gray-100">
                            <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-[0.15em] pl-8 w-16">Rank</th>
                            <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-[0.15em]">Product Details</th>
                            <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-[0.15em] text-center">Total Quantity Sold</th>
                            <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-[0.15em] text-right pr-8">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($bestSellers as $index => $item)
                            <tr class="hover:bg-indigo-50/30 transition-colors group">
                                <td class="p-5 pl-8">
                                    <div class="w-8 h-8 rounded-full {{ $index < 3 && $bestSellers->currentPage() == 1 ? 'bg-amber-100 text-amber-600' : 'bg-gray-100 text-gray-500' }} flex items-center justify-center font-black text-sm shadow-inner">
                                        #{{ ($bestSellers->currentPage() - 1) * $bestSellers->perPage() + $index + 1 }}
                                    </div>
                                </td>
                                <td class="p-5">
                                    <div class="text-sm font-extrabold text-gray-900">
                                        {{ $item->product->name ?? 'Unknown Product' }}
                                    </div>
                                    <div class="text-[11px] font-medium text-gray-500 font-mono mt-0.5">
                                        SKU: {{ $item->product->sku ?? 'N/A' }}
                                    </div>
                                </td>
                                <td class="p-5 text-center">
                                    <span class="bg-indigo-50 text-indigo-700 font-black text-sm px-4 py-1.5 rounded-lg border border-indigo-100">
                                        {{ number_format($item->total_sold) }} Units
                                    </span>
                                </td>
                                <td class="p-5 text-right pr-8">
                                    @if($index == 0)
                                        <span class="text-xs font-bold text-emerald-600 bg-emerald-50 px-3 py-1 rounded-md border border-emerald-100">
                                            Top Seller 🔥
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="p-16 text-center">
                                    <p class="text-gray-900 font-bold text-lg mb-1">No items sold in this period.</p>
                                    <p class="text-gray-500 font-medium text-sm">Try adjusting your date range filter.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($bestSellers->hasPages())
                <div class="p-4 border-t border-gray-100 bg-gray-50/50">
                    {{ $bestSellers->links() }}
                </div>
            @endif
        </div>

    </div>
</x-app-layout>