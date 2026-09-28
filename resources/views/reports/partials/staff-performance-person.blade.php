<div id="lb-person" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100">
        <h2 class="text-base font-extrabold text-slate-900">Employee ranking</h2>
        <p class="text-xs text-slate-500">Ranked by net sales revenue in the selected period.</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[640px]">
            <thead>
                <tr class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500 border-b border-slate-100">
                    <th class="text-left font-bold px-5 py-3 w-16">Rank</th>
                    <th class="text-left font-bold px-3 py-3">Employee</th>
                    <th class="text-center font-bold px-3 py-3">Orders</th>
                    <th class="text-right font-bold px-3 py-3">Avg ticket</th>
                    <th class="text-right font-bold px-3 py-3">Revenue</th>
                    <th class="text-right font-bold px-5 py-3">Share</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse($personLeaderboard as $row)
                    @php
                        $share = $periodTotal > 0 ? ($row->total_revenue / $periodTotal) * 100 : 0;
                        $medal = match((int) $row->rank) {
                            1 => 'bg-amber-100 text-amber-800 border-amber-200',
                            2 => 'bg-slate-200 text-slate-700 border-slate-300',
                            3 => 'bg-orange-100 text-orange-800 border-orange-200',
                            default => 'bg-slate-50 text-slate-500 border-slate-200',
                        };
                    @endphp
                    <tr class="{{ $row->rank === 1 ? 'bg-amber-50/40' : 'hover:bg-slate-50/80' }}">
                        <td class="px-5 py-3.5">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full border text-xs font-black {{ $medal }}">
                                {{ $row->rank === 1 ? '🥇' : ($row->rank === 2 ? '🥈' : ($row->rank === 3 ? '🥉' : '#'.$row->rank)) }}
                            </span>
                        </td>
                        <td class="px-3 py-3.5">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="h-9 w-9 rounded-full bg-blue-50 text-blue-700 flex items-center justify-center text-xs font-black shrink-0">
                                    {{ strtoupper(substr($row->user->name ?? '?', 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="font-extrabold text-slate-900 truncate">{{ $row->user->name ?? 'Deleted staff' }}</p>
                                    @if($row->rank === 1)
                                        <p class="text-[11px] font-bold text-amber-700">Best seller this period — praise them!</p>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-3 py-3.5 text-center font-bold text-slate-700">{{ $row->total_orders }}</td>
                        <td class="px-3 py-3.5 text-right text-slate-600">{{ format_taka($row->avg_ticket) }}</td>
                        <td class="px-3 py-3.5 text-right font-extrabold text-emerald-600">{{ format_taka($row->total_revenue) }}</td>
                        <td class="px-5 py-3.5 text-right">
                            <div class="inline-flex flex-col items-end gap-1 min-w-[88px]">
                                <span class="text-xs font-bold text-slate-600">{{ number_format($share, 1) }}%</span>
                                <span class="block h-1.5 w-20 rounded-full bg-slate-100 overflow-hidden">
                                    <span class="block h-full rounded-full bg-blue-500" style="width: {{ min(100, $share) }}%"></span>
                                </span>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-12 text-center text-slate-400">No employee sales in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
