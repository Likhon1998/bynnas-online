<div id="lb-counter" class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100">
        <h2 class="text-base font-extrabold text-slate-900">Counter ranking</h2>
        <p class="text-xs text-slate-500">Which till brought in the most sales.</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm min-w-[640px]">
            <thead>
                <tr class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500 border-b border-slate-100">
                    <th class="text-left font-bold px-5 py-3 w-16">Rank</th>
                    <th class="text-left font-bold px-3 py-3">Counter</th>
                    <th class="text-center font-bold px-3 py-3">Staff</th>
                    <th class="text-center font-bold px-3 py-3">Orders</th>
                    <th class="text-right font-bold px-3 py-3">Avg ticket</th>
                    <th class="text-right font-bold px-5 py-3">Revenue</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse($counterLeaderboard as $row)
                    @php
                        $medal = match((int) $row->rank) {
                            1 => 'bg-indigo-100 text-indigo-800 border-indigo-200',
                            2 => 'bg-slate-200 text-slate-700 border-slate-300',
                            3 => 'bg-violet-100 text-violet-800 border-violet-200',
                            default => 'bg-slate-50 text-slate-500 border-slate-200',
                        };
                    @endphp
                    <tr class="{{ $row->rank === 1 ? 'bg-indigo-50/50' : 'hover:bg-slate-50/80' }}">
                        <td class="px-5 py-3.5">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full border text-xs font-black {{ $medal }}">#{{ $row->rank }}</span>
                        </td>
                        <td class="px-3 py-3.5">
                            <p class="font-extrabold text-slate-900">{{ $row->counter->name ?? 'Unassigned / Admin till' }}</p>
                            @if($row->rank === 1)
                                <p class="text-[11px] font-bold text-indigo-700">Highest counter this period</p>
                            @endif
                        </td>
                        <td class="px-3 py-3.5 text-center font-bold text-slate-600">{{ $row->staff_count }}</td>
                        <td class="px-3 py-3.5 text-center font-bold text-slate-700">{{ $row->total_orders }}</td>
                        <td class="px-3 py-3.5 text-right text-slate-600">{{ format_taka($row->avg_ticket) }}</td>
                        <td class="px-5 py-3.5 text-right font-extrabold text-emerald-600">{{ format_taka($row->total_revenue) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-12 text-center text-slate-400">No counter sales in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
