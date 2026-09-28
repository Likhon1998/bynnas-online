<x-app-layout>
    <div class="max-w-[1100px] mx-auto pt-1 pb-12 px-4 sm:px-6">
        <div class="mb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h1 class="text-[22px] sm:text-[24px] font-extrabold tracking-tight text-slate-900">Stock Transfer</h1>
                <p class="mt-1 text-sm text-slate-500">Move stock between warehouse and store. Sellable stock updates only on warehouse ↔ store moves.</p>
            </div>
            <a href="{{ route('supply.stock-transfers.create') }}"
               class="inline-flex items-center gap-2 self-start bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold px-4 py-2.5 rounded-xl">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                New Transfer
            </a>
        </div>

        @include('supply.partials.alerts')

        <div class="bg-white rounded-2xl border border-slate-200/80 overflow-hidden shadow-sm">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500 border-b border-slate-100">
                        <th class="p-4 text-left font-bold">Transfer #</th>
                        <th class="p-4 text-left font-bold">From</th>
                        <th class="p-4 text-left font-bold">To</th>
                        <th class="p-4 text-left font-bold">Status</th>
                        <th class="p-4 text-left font-bold">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($transfers as $transfer)
                        <tr class="hover:bg-slate-50/60">
                            <td class="p-4 font-bold text-slate-900">{{ $transfer->transfer_number }}</td>
                            <td class="p-4 text-slate-600">{{ $transfer->fromLocation->name ?? '—' }}</td>
                            <td class="p-4 text-slate-600">{{ $transfer->toLocation->name ?? '—' }}</td>
                            <td class="p-4">
                                <span class="inline-flex rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100 px-2.5 py-0.5 text-[11px] font-bold uppercase">{{ $transfer->status }}</span>
                            </td>
                            <td class="p-4 text-slate-500">{{ $transfer->created_at->format('M d, Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-12 text-center text-slate-400">
                                No transfers yet.
                                <a href="{{ route('supply.stock-transfers.create') }}" class="text-blue-600 font-semibold underline ml-1">Create one</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @if($transfers->hasPages())
                <div class="p-4 border-t border-slate-100">{{ $transfers->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
