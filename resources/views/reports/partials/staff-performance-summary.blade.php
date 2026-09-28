<div id="lb-summary" class="grid sm:grid-cols-2 xl:grid-cols-4 gap-3">
    <div class="bg-slate-900 rounded-2xl p-4 text-white">
        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Period revenue</p>
        <p class="mt-2 text-2xl font-black tracking-tight">{{ format_taka($periodTotal) }}</p>
        <p class="mt-1 text-xs text-slate-400">{{ $periodOrders }} orders · {{ $startDate->format('M j') }} – {{ $endDate->format('M j, Y') }}</p>
    </div>
    <div class="bg-white rounded-2xl border border-amber-200 p-4 shadow-sm">
        <p class="text-[10px] font-bold uppercase tracking-wider text-amber-600">🏆 Top employee</p>
        @if($topPerson)
            <p class="mt-2 text-lg font-extrabold text-slate-900 truncate">{{ $topPerson->user->name ?? 'Unknown' }}</p>
            <p class="text-sm font-bold text-emerald-600">{{ format_taka($topPerson->total_revenue) }}</p>
            <p class="text-xs text-slate-400">{{ $topPerson->total_orders }} orders</p>
        @else
            <p class="mt-2 text-sm text-slate-400">No sales in this period.</p>
        @endif
    </div>
    <div class="bg-white rounded-2xl border border-indigo-200 p-4 shadow-sm">
        <p class="text-[10px] font-bold uppercase tracking-wider text-indigo-600">🏪 Top counter</p>
        @if($topCounter)
            <p class="mt-2 text-lg font-extrabold text-slate-900 truncate">{{ $topCounter->counter->name ?? 'Unassigned' }}</p>
            <p class="text-sm font-bold text-emerald-600">{{ format_taka($topCounter->total_revenue) }}</p>
            <p class="text-xs text-slate-400">{{ $topCounter->total_orders }} orders · {{ $topCounter->staff_count }} staff</p>
        @else
            <p class="mt-2 text-sm text-slate-400">No sales in this period.</p>
        @endif
    </div>
    <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Active sellers</p>
        <p class="mt-2 text-2xl font-black text-slate-900">{{ $personLeaderboard->count() }}</p>
        <p class="mt-1 text-xs text-slate-400">{{ $counterLeaderboard->count() }} counters with sales</p>
    </div>
</div>
