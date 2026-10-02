<x-app-layout>
    <div class="pt-0 pb-5">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

            {{-- ═══ HERO HEADER ═══ --}}
            <div class="relative bg-slate-900 rounded-2xl overflow-hidden">
                <div class="absolute inset-0 opacity-[0.035]" style="background-image:linear-gradient(rgba(255,255,255,1) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,1) 1px,transparent 1px);background-size:32px 32px;"></div>
                <div class="absolute top-0 right-0 h-full w-64 bg-gradient-to-l from-indigo-600/10 to-transparent pointer-events-none"></div>

                <div class="relative flex flex-col lg:flex-row items-start lg:items-center justify-between px-6 py-5 gap-4">

                    {{-- Left: Identity --}}
                    <div class="flex items-center gap-4">
                        <div class="w-10 h-10 rounded-xl bg-indigo-500/20 border border-indigo-500/30 flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold text-indigo-400 uppercase tracking-[0.15em]">Payment totals</span>
                            <h2 class="text-lg font-bold text-white leading-tight">Cash · Card/Bank · bKash</h2>
                            <p class="text-xs text-slate-300">See how much came in by payment method — today or any date range.</p>
                        </div>
                    </div>

                    {{-- Right: Date Filter --}}
                    <form action="{{ route('reports.daily') }}" method="GET" class="flex flex-wrap items-end gap-2">
                        <div>
                            <label for="start_date" class="block text-[10px] font-bold text-slate-300 uppercase tracking-widest mb-1">From</label>
                            <input type="date" name="start_date" id="start_date"
                                   value="{{ request('start_date', $startDate->format('Y-m-d')) }}"
                                   class="bg-white/10 border border-white/20 text-white text-xs rounded-xl px-3 py-2 font-medium [color-scheme:dark] focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label for="end_date" class="block text-[10px] font-bold text-slate-300 uppercase tracking-widest mb-1">To</label>
                            <input type="date" name="end_date" id="end_date"
                                   value="{{ request('end_date', $endDate->format('Y-m-d')) }}"
                                   class="bg-white/10 border border-white/20 text-white text-xs rounded-xl px-3 py-2 font-medium [color-scheme:dark] focus:outline-none focus:ring-1 focus:ring-indigo-500">
                        </div>
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 bg-indigo-500 hover:bg-indigo-400 text-white text-xs font-bold px-4 py-2 rounded-xl transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            Apply Range
                        </button>
                        <a href="{{ route('reports.daily', ['today' => 1]) }}"
                           class="inline-flex items-center gap-1.5 bg-emerald-500/90 hover:bg-emerald-400 text-white text-xs font-bold px-4 py-2 rounded-xl transition-colors">
                            Today
                        </a>
                        <a href="{{ route('reports.daily', ['all_time' => true]) }}"
                           class="inline-flex items-center gap-1.5 bg-white/10 hover:bg-white/15 border border-white/20 text-white text-xs font-bold px-4 py-2 rounded-xl transition-colors">
                            All Time
                        </a>
                        @if(request('start_date') || request('all_time') || request('today'))
                            <a href="{{ route('reports.daily') }}"
                               class="inline-flex items-center gap-1.5 bg-white/10 hover:bg-white/15 border border-white/20 text-white text-xs font-bold px-4 py-2 rounded-xl transition-colors">
                                Clear
                            </a>
                        @endif
                    </form>
                </div>
            </div>

            {{-- ═══ PERIOD LABEL ═══ --}}
            <div class="flex items-center gap-2 px-1">
                <div class="w-1 h-4 bg-indigo-500 rounded-full"></div>
                <p class="text-sm font-bold text-slate-700">
                    Reporting Period:
                    <span class="text-indigo-600 font-black">
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

            {{-- ═══ SUMMARY STATS ═══ --}}
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

                {{-- Total Revenue --}}
                <div class="bg-slate-900 rounded-2xl p-5 relative overflow-hidden">
                    <div class="absolute inset-0 opacity-[0.03]" style="background-image:linear-gradient(rgba(255,255,255,1) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,1) 1px,transparent 1px);background-size:24px 24px;"></div>
                    <div class="relative">
                        <div class="flex items-start justify-between mb-3">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Total Revenue</p>
                            <div class="w-7 h-7 rounded-lg bg-indigo-500/20 flex items-center justify-center flex-shrink-0">
                                <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        </div>
                        <p class="text-[22px] font-black text-white tracking-tight leading-none">{{ format_taka($summary->total_revenue ?? 0) }}</p>
                        <p class="text-xs text-slate-300 mt-2">{{ $summary->total_orders ?? 0 }} orders placed</p>
                        <div class="mt-3 h-0.5 w-6 bg-indigo-500 rounded-full"></div>
                    </div>
                </div>

                {{-- Cash --}}
                <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
                    <div class="flex items-start justify-between mb-3">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Cash Collected</p>
                        <div class="w-7 h-7 rounded-lg bg-emerald-50 flex items-center justify-center flex-shrink-0">
                            <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-[22px] font-black text-gray-900 tracking-tight leading-none">{{ format_taka($summary->cash_total ?? 0) }}</p>
                    <div class="mt-3 h-0.5 w-6 bg-emerald-400 rounded-full"></div>
                </div>

                {{-- Card / Bank --}}
                <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
                    <div class="flex items-start justify-between mb-3">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Card / Bank</p>
                        <div class="w-7 h-7 rounded-lg bg-blue-50 flex items-center justify-center flex-shrink-0">
                            <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-[22px] font-black text-gray-900 tracking-tight leading-none">{{ format_taka($summary->card_total ?? 0) }}</p>
                    <div class="mt-3 h-0.5 w-6 bg-blue-400 rounded-full"></div>
                </div>

                {{-- bKash --}}
                <div class="bg-white rounded-2xl border border-gray-100 p-5 shadow-sm">
                    <div class="flex items-start justify-between mb-3">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">bKash / Mobile</p>
                        <div class="w-7 h-7 rounded-lg bg-pink-50 flex items-center justify-center flex-shrink-0">
                            <svg class="w-3.5 h-3.5 text-pink-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    </div>
                    <p class="text-[22px] font-black text-gray-900 tracking-tight leading-none">{{ format_taka($summary->bkash_total ?? 0) }}</p>
                    <div class="mt-3 h-0.5 w-6 bg-pink-400 rounded-full"></div>
                </div>

            </div>

            {{-- ═══ BREAKDOWN TABLES ═══ --}}
            <div class="grid grid-cols-1 {{ retail_enabled() ? 'lg:grid-cols-2' : '' }} gap-4">

                {{-- Sales by Employee --}}
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="flex items-center gap-2 px-5 py-4 border-b border-gray-100">
                        <div class="w-1 h-4 bg-indigo-500 rounded-full"></div>
                        <h3 class="text-sm font-bold text-slate-800 tracking-tight">Sales by Employee</h3>
                    </div>
                    <div class="divide-y divide-gray-50 max-h-[280px] overflow-y-auto">
                        @forelse($employeeSales as $empSale)
                            <div class="flex items-center justify-between px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-black text-xs flex-shrink-0">
                                        {{ strtoupper(substr($empSale->user->name ?? '?', 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-gray-900">{{ $empSale->user->name ?? 'Unknown Employee' }}</p>
                                        <p class="text-xs text-gray-400">{{ $empSale->total_orders }} {{ Str::plural('order', $empSale->total_orders) }}</p>
                                    </div>
                                </div>
                                <span class="text-sm font-black text-gray-900">{{ format_taka($empSale->total_revenue) }}</span>
                            </div>
                        @empty
                            <div class="px-5 py-8 text-center">
                                <p class="text-sm text-gray-400">No sales recorded for this period.</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                @if(retail_enabled())
                {{-- Sales by Counter --}}
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="flex items-center gap-2 px-5 py-4 border-b border-gray-100">
                        <div class="w-1 h-4 bg-slate-400 rounded-full"></div>
                        <h3 class="text-sm font-bold text-slate-800 tracking-tight">Sales by Counter</h3>
                    </div>
                    <div class="divide-y divide-gray-50 max-h-[280px] overflow-y-auto">
                        @forelse($counterSales as $counterSale)
                            <div class="flex items-center justify-between px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-gray-900">{{ $counterSale->counter->name ?? 'Unassigned / Web' }}</p>
                                        <p class="text-xs text-gray-400">{{ $counterSale->total_orders }} {{ Str::plural('order', $counterSale->total_orders) }}</p>
                                    </div>
                                </div>
                                <span class="text-sm font-black text-gray-900">{{ format_taka($counterSale->total_revenue) }}</span>
                            </div>
                        @empty
                            <div class="px-5 py-8 text-center">
                                <p class="text-sm text-gray-400">No counter activity recorded.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
                @endif

            </div>

            {{-- ═══ HISTORICAL DAILY BREAKDOWN ═══ --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="flex items-center gap-2 px-5 py-4 border-b border-gray-100">
                    <div class="w-1 h-4 bg-amber-400 rounded-full"></div>
                    <h3 class="text-sm font-bold text-slate-800 tracking-tight">Daily Breakdown</h3>
                </div>
                <div class="overflow-x-auto max-h-[520px] overflow-y-auto">
                    <table class="w-full text-left border-collapse whitespace-nowrap">
                        <thead class="sticky top-0 bg-gray-50 z-10 border-b border-gray-100">
                            <tr>
                                <th class="px-5 py-3 text-[10px] font-black text-gray-400 uppercase tracking-[0.12em]">Date</th>
                                <th class="px-5 py-3 text-[10px] font-black text-gray-400 uppercase tracking-[0.12em]">Orders</th>
                                <th class="px-5 py-3 text-[10px] font-black text-gray-400 uppercase tracking-[0.12em]">Total Revenue</th>
                                <th class="px-5 py-3 text-[10px] font-black text-emerald-500 uppercase tracking-[0.12em]">Cash</th>
                                <th class="px-5 py-3 text-[10px] font-black text-blue-500 uppercase tracking-[0.12em]">Card / Bank</th>
                                <th class="px-5 py-3 text-[10px] font-black text-pink-500 uppercase tracking-[0.12em]">bKash</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($historicalSales as $day)
                                <tr class="hover:bg-slate-50/60 transition-colors">
                                    <td class="px-5 py-3 text-sm font-bold text-gray-900">{{ \Carbon\Carbon::parse($day->date)->format('d M, Y') }}</td>
                                    <td class="px-5 py-3 text-sm text-gray-500 font-medium">{{ $day->total_orders }}</td>
                                    <td class="px-5 py-3 text-sm font-black text-indigo-600">{{ format_taka($day->total_revenue) }}</td>
                                    <td class="px-5 py-3 text-sm text-gray-600 font-medium">{{ format_taka($day->cash_total) }}</td>
                                    <td class="px-5 py-3 text-sm text-gray-600 font-medium">{{ format_taka($day->card_total) }}</td>
                                    <td class="px-5 py-3 text-sm text-gray-600 font-medium">{{ format_taka($day->bkash_total) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-5 py-10 text-center text-sm text-gray-400">No sales data available for this period.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>