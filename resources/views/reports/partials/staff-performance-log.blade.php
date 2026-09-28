<div id="lb-log" class="space-y-4">
    <div class="bg-white rounded-2xl border border-slate-200 p-4 flex flex-wrap gap-3 items-end">
        <div class="min-w-[200px] flex-1">
            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">Filter employee</label>
            <select name="staff_id" data-staff-filter class="w-full rounded-xl border-slate-200 text-sm">
                <option value="">All staff</option>
                @foreach($staffList as $staff)
                    <option value="{{ $staff->id }}" @selected((string) $selectedStaffId === (string) $staff->id)>{{ $staff->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[720px]">
                <thead>
                    <tr class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500 border-b border-slate-100">
                        <th class="text-left font-bold px-5 py-3">Date</th>
                        <th class="text-left font-bold px-3 py-3">Employee</th>
                        <th class="text-left font-bold px-3 py-3">Counter</th>
                        <th class="text-center font-bold px-3 py-3">Orders</th>
                        <th class="text-right font-bold px-3 py-3">Revenue</th>
                        <th class="text-right font-bold px-5 py-3">Receipts</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($activityLog as $row)
                        <tr class="hover:bg-slate-50/80">
                            <td class="px-5 py-3.5">
                                <p class="font-bold text-slate-900">{{ \Carbon\Carbon::parse($row->sale_date)->format('d M Y') }}</p>
                                <p class="text-xs text-slate-400">{{ \Carbon\Carbon::parse($row->sale_date)->format('l') }}</p>
                            </td>
                            <td class="px-3 py-3.5 font-bold text-slate-800">{{ $row->user->name ?? 'Deleted staff' }}</td>
                            <td class="px-3 py-3.5">
                                <span class="inline-flex rounded-lg bg-indigo-50 text-indigo-700 px-2.5 py-1 text-[10px] font-bold uppercase">{{ $row->counter->name ?? 'Unassigned' }}</span>
                            </td>
                            <td class="px-3 py-3.5 text-center font-bold">{{ $row->total_orders }}</td>
                            <td class="px-3 py-3.5 text-right font-extrabold text-emerald-600">{{ format_taka($row->total_revenue) }}</td>
                            <td class="px-5 py-3.5 text-right">
                                <button type="button"
                                        onclick="openDetailsModal('{{ $row->user_id }}', '{{ $row->sale_date }}', '{{ $row->counter_id }}')"
                                        class="inline-flex items-center gap-1 rounded-xl bg-slate-900 text-white text-xs font-bold px-3 py-2 hover:bg-blue-600">
                                    View
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-12 text-center text-slate-400">No activity for this filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($activityLog->hasPages())
            <div class="p-4 border-t border-slate-100" data-log-pagination>{{ $activityLog->links() }}</div>
        @endif
    </div>
</div>
