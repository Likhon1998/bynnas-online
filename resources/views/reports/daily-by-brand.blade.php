<x-app-layout>
    <div class="pt-0 pb-5">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

            {{-- ═══ HERO HEADER ═══ --}}
            <div class="relative bg-slate-900 rounded-2xl overflow-hidden">
                <div class="absolute inset-0 opacity-[0.035]" style="background-image:linear-gradient(rgba(255,255,255,1) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,1) 1px,transparent 1px);background-size:32px 32px;"></div>
                <div class="absolute top-0 right-0 h-full w-64 bg-gradient-to-l from-cyan-600/10 to-transparent pointer-events-none"></div>

                <div class="relative flex flex-col lg:flex-row items-start lg:items-center justify-between px-6 py-5 gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-xl bg-cyan-500/20 border border-cyan-500/30 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                            </svg>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-cyan-400 uppercase tracking-[0.15em]">Brand performance</span>
                            <h2 class="text-lg font-bold text-white leading-tight">Sales by Brand</h2>
                            <p class="text-xs text-slate-300">Units &amp; revenue per brand — POS + website completed orders.</p>
                        </div>
                    </div>

                    <form action="{{ route('reports.daily_by_brand') }}" method="GET" class="flex flex-wrap items-end gap-2">
                        <div>
                            <label for="start_date" class="block text-[10px] font-bold text-slate-300 uppercase tracking-widest mb-1">From</label>
                            <input type="date" name="start_date" id="start_date"
                                   value="{{ request('start_date', $startDate->format('Y-m-d')) }}"
                                   class="bg-white/10 border border-white/20 text-white text-xs rounded-xl px-3 py-2 font-medium [color-scheme:dark] focus:outline-none focus:ring-1 focus:ring-cyan-500">
                        </div>
                        <div>
                            <label for="end_date" class="block text-[10px] font-bold text-slate-300 uppercase tracking-widest mb-1">To</label>
                            <input type="date" name="end_date" id="end_date"
                                   value="{{ request('end_date', $endDate->format('Y-m-d')) }}"
                                   class="bg-white/10 border border-white/20 text-white text-xs rounded-xl px-3 py-2 font-medium [color-scheme:dark] focus:outline-none focus:ring-1 focus:ring-cyan-500">
                        </div>
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 bg-cyan-500 hover:bg-cyan-400 text-white text-xs font-bold px-4 py-2 rounded-xl transition-colors">
                            Apply Range
                        </button>
                        <a href="{{ route('reports.daily_by_brand', ['today' => 1]) }}"
                           class="inline-flex items-center gap-1.5 bg-emerald-500/90 hover:bg-emerald-400 text-white text-xs font-bold px-4 py-2 rounded-xl transition-colors">
                            Today
                        </a>
                        <a href="{{ route('reports.daily_by_brand', ['all_time' => true]) }}"
                           class="inline-flex items-center gap-1.5 bg-white/10 hover:bg-white/15 border border-white/20 text-white text-xs font-bold px-4 py-2 rounded-xl transition-colors">
                            All Time
                        </a>
                        @if(request('start_date') || request('all_time') || request('today'))
                            <a href="{{ route('reports.daily_by_brand') }}"
                               class="inline-flex items-center gap-1.5 bg-white/10 hover:bg-white/15 border border-white/20 text-white text-xs font-bold px-4 py-2 rounded-xl transition-colors">
                                Clear
                            </a>
                        @endif
                    </form>
                </div>
            </div>

            {{-- Period label --}}
            <div class="flex items-center gap-2 px-1">
                <div class="w-1 h-4 bg-cyan-500 rounded-full"></div>
                <p class="text-sm font-bold text-slate-700">
                    Reporting Period:
                    <span class="text-cyan-700 font-black">
                        @if(request('all_time'))
                            All Time (Lifetime)
                        @elseif(request('today') || ($startDate->isToday() && $endDate->isToday()))
                            Today ({{ $startDate->format('M j, Y') }})
                        @else
                            {{ $startDate->format('M j, Y') }} — {{ $endDate->format('M j, Y') }}
                        @endif
                    </span>
                </p>
            </div>

            {{-- Summary cards --}}
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
                <div class="bg-slate-900 rounded-2xl p-5 relative overflow-hidden">
                    <div class="relative">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Selling Amount</p>
                        <p class="text-[22px] font-black text-white tracking-tight leading-none">{{ format_taka($summary->total_revenue) }}</p>
                        <p class="text-xs text-slate-300 mt-2">{{ $summary->total_units }} units · {{ $summary->brand_count }} brands</p>
                    </div>
                </div>
                <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-3">Purchase Cost</p>
                    <p class="text-[22px] font-black text-gray-900 tracking-tight leading-none">{{ format_taka($summary->total_cost) }}</p>
                    <p class="text-xs text-slate-500 mt-2">Product cost × units sold</p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-3">Gross Profit</p>
                    <p class="text-[22px] font-black {{ $summary->total_profit >= 0 ? 'text-emerald-600' : 'text-rose-600' }} tracking-tight leading-none">{{ format_taka($summary->total_profit) }}</p>
                    <p class="text-xs text-slate-500 mt-2">Selling − purchase cost</p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-3">POS Sales</p>
                    <p class="text-[22px] font-black text-gray-900 tracking-tight leading-none">{{ format_taka($summary->pos_revenue) }}</p>
                    <p class="text-xs text-slate-500 mt-2">{{ $summary->pos_units }} units from counters</p>
                </div>
                <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-3">Website Sales</p>
                    <p class="text-[22px] font-black text-gray-900 tracking-tight leading-none">{{ format_taka($summary->web_revenue) }}</p>
                    <p class="text-xs text-slate-500 mt-2">{{ $summary->web_units }} units from online orders</p>
                </div>
            </div>

            {{-- Brand ranking --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="flex items-center justify-between gap-2 px-5 py-4 border-b border-gray-200">
                    <div class="flex items-center gap-2">
                        <div class="w-1 h-4 bg-cyan-500 rounded-full"></div>
                        <h3 class="text-sm font-bold text-slate-800 tracking-tight">Brand Ranking</h3>
                    </div>
                    <p class="text-[11px] text-slate-500 font-medium">Merchandise only (delivery fees excluded)</p>
                </div>
                <div class="overflow-x-auto max-h-[420px] overflow-y-auto">
                    <table class="w-full text-left border-collapse whitespace-nowrap">
                        <thead class="sticky top-0 bg-gray-50 z-10 border-b border-gray-200">
                            <tr>
                                <th class="px-5 py-3 text-[10px] font-black text-slate-600 uppercase tracking-[0.12em]">#</th>
                                <th class="px-5 py-3 text-[10px] font-black text-slate-600 uppercase tracking-[0.12em]">Brand</th>
                                <th class="px-5 py-3 text-[10px] font-black text-slate-600 uppercase tracking-[0.12em] text-right">Units</th>
                                <th class="px-5 py-3 text-[10px] font-black text-amber-700 uppercase tracking-[0.12em] text-right">Purchase Cost</th>
                                <th class="px-5 py-3 text-[10px] font-black text-cyan-800 uppercase tracking-[0.12em] text-right">Selling Amount</th>
                                <th class="px-5 py-3 text-[10px] font-black text-emerald-700 uppercase tracking-[0.12em] text-right">Profit</th>
                                <th class="px-5 py-3 text-[10px] font-black text-emerald-700 uppercase tracking-[0.12em] text-right">POS</th>
                                <th class="px-5 py-3 text-[10px] font-black text-sky-700 uppercase tracking-[0.12em] text-right">Website</th>
                                <th class="px-5 py-3 text-[10px] font-black text-slate-600 uppercase tracking-[0.12em] text-right">Share</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @php $revTotal = max(0.01, (float) $summary->total_revenue); @endphp
                            @forelse($brandSales as $index => $row)
                                <tr class="hover:bg-slate-50/60 transition-colors">
                                    <td class="px-5 py-3 text-sm text-gray-400 font-bold">{{ $index + 1 }}</td>
                                    <td class="px-5 py-3 text-sm font-bold text-gray-900">{{ $row->brand }}</td>
                                    <td class="px-5 py-3 text-sm text-gray-600 font-medium text-right">{{ number_format($row->sold) }}</td>
                                    <td class="px-5 py-3 text-sm font-semibold text-amber-700 text-right">{{ format_taka($row->cost) }}</td>
                                    <td class="px-5 py-3 text-sm font-black text-cyan-700 text-right">{{ format_taka($row->revenue) }}</td>
                                    <td class="px-5 py-3 text-sm font-bold {{ $row->profit >= 0 ? 'text-emerald-600' : 'text-rose-600' }} text-right">{{ format_taka($row->profit) }}</td>
                                    <td class="px-5 py-3 text-sm text-slate-500 text-right">
                                        <span class="font-medium text-slate-700">{{ format_taka($row->pos_revenue) }}</span>
                                        <span class="text-slate-500 text-xs"> · {{ $row->pos_sold }}u</span>
                                    </td>
                                    <td class="px-5 py-3 text-sm text-slate-500 text-right">
                                        <span class="font-medium text-slate-700">{{ format_taka($row->web_revenue) }}</span>
                                        <span class="text-slate-500 text-xs"> · {{ $row->web_sold }}u</span>
                                    </td>
                                    <td class="px-5 py-3 text-sm font-bold text-slate-600 text-right">{{ number_format(($row->revenue / $revTotal) * 100, 1) }}%</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-5 py-10 text-center text-sm text-gray-400">No brand sales for this period. Complete POS checkouts or deliver website orders to see data here.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Daily breakdown --}}
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                <div class="flex items-center gap-2 px-5 py-4 border-b border-gray-200">
                    <div class="w-1 h-4 bg-amber-400 rounded-full"></div>
                    <h3 class="text-sm font-bold text-slate-800 tracking-tight">Daily Breakdown by Brand</h3>
                </div>
                <div class="overflow-x-auto max-h-[560px] overflow-y-auto">
                    <table class="w-full text-left border-collapse whitespace-nowrap">
                        <thead class="sticky top-0 bg-gray-50 z-10 border-b border-gray-200">
                            <tr>
                                <th class="px-5 py-3 text-[10px] font-black text-slate-600 uppercase tracking-[0.12em]">Date</th>
                                <th class="px-5 py-3 text-[10px] font-black text-slate-600 uppercase tracking-[0.12em]">Brand</th>
                                <th class="px-5 py-3 text-[10px] font-black text-slate-600 uppercase tracking-[0.12em] text-right">Units</th>
                                <th class="px-5 py-3 text-[10px] font-black text-amber-700 uppercase tracking-[0.12em] text-right">Purchase Cost</th>
                                <th class="px-5 py-3 text-[10px] font-black text-cyan-800 uppercase tracking-[0.12em] text-right">Selling Amount</th>
                                <th class="px-5 py-3 text-[10px] font-black text-emerald-700 uppercase tracking-[0.12em] text-right">Profit</th>
                                <th class="px-5 py-3 text-[10px] font-black text-emerald-700 uppercase tracking-[0.12em] text-right">POS</th>
                                <th class="px-5 py-3 text-[10px] font-black text-sky-700 uppercase tracking-[0.12em] text-right">Website</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($dailyGrouped as $date => $rows)
                                @foreach($rows as $i => $row)
                                    <tr class="hover:bg-slate-50/60 transition-colors">
                                        <td class="px-5 py-3 text-sm font-bold text-gray-900">
                                            @if($i === 0)
                                                {{ \Carbon\Carbon::parse($date)->format('d M, Y') }}
                                            @endif
                                        </td>
                                        <td class="px-5 py-3 text-sm font-medium text-gray-800">{{ $row->brand }}</td>
                                        <td class="px-5 py-3 text-sm text-slate-600 text-right">{{ number_format($row->sold) }}</td>
                                        <td class="px-5 py-3 text-sm font-semibold text-amber-700 text-right">{{ format_taka($row->cost) }}</td>
                                        <td class="px-5 py-3 text-sm font-black text-cyan-700 text-right">{{ format_taka($row->revenue) }}</td>
                                        <td class="px-5 py-3 text-sm font-bold {{ $row->profit >= 0 ? 'text-emerald-600' : 'text-rose-600' }} text-right">{{ format_taka($row->profit) }}</td>
                                        <td class="px-5 py-3 text-sm text-slate-700 text-right">{{ format_taka($row->pos_revenue) }} <span class="text-xs text-slate-500">({{ $row->pos_sold }})</span></td>
                                        <td class="px-5 py-3 text-sm text-slate-700 text-right">{{ format_taka($row->web_revenue) }} <span class="text-xs text-slate-500">({{ $row->web_sold }})</span></td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="8" class="px-5 py-10 text-center text-sm text-slate-500">No daily brand sales in this period.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
