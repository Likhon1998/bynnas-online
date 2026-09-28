<div id="sa-results" class="bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm transition-opacity duration-150">
    <div class="overflow-x-auto">
            <table class="w-full text-sm min-w-[960px]">
                <thead class="bg-slate-900 text-white text-[11px] uppercase tracking-widest">
                    <tr>
                        <th class="p-3.5 text-left">Date</th>
                        <th class="p-3.5 text-left">Product</th>
                        <th class="p-3.5 text-left">Type</th>
                        <th class="p-3.5 text-left">Source</th>
                        <th class="p-3.5 text-right">Qty change</th>
                        <th class="p-3.5 text-left">Location</th>
                        <th class="p-3.5 text-left">Sellable balance</th>
                        <th class="p-3.5 text-left min-w-[220px]">Reference</th>
                    </tr>
                </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($movements as $m)
                    @php
                        $isIn = $m->isStockIn();
                        $signed = $m->signedQuantity();
                    @endphp
                    <tr class="hover:bg-slate-50/80">
                        <td class="p-3.5 whitespace-nowrap text-slate-600">{{ $m->created_at->format('M d, Y H:i') }}</td>
                        <td class="p-3.5 font-semibold text-slate-900">{{ $m->product->name ?? '—' }}</td>
                        <td class="p-3.5">
                            @if($isIn)
                                <span class="inline-flex items-center rounded-lg border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wide text-emerald-700">
                                    IN (+)
                                </span>
                            @elseif($m->type === 'sale')
                                <span class="inline-flex items-center rounded-lg border border-rose-200 bg-rose-50 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wide text-rose-700">
                                    SALE (−)
                                </span>
                            @else
                                <span class="inline-flex items-center rounded-lg border border-rose-200 bg-rose-50 px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wide text-rose-700">
                                    OUT (−)
                                </span>
                            @endif
                        </td>
                        <td class="p-3.5 text-slate-600">{{ $m->reasonLabel() }}</td>
                        <td class="p-3.5 text-right">
                            <span class="inline-flex min-w-[3.25rem] justify-end rounded-lg px-2 py-1 text-sm font-black tabular-nums {{ $isIn ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                {{ $isIn ? '+' : '−' }}{{ abs($signed) }}
                            </span>
                        </td>
                        <td class="p-3.5 text-slate-600">{{ $m->location?->name ?? '—' }}</td>
                        <td class="p-3.5">
                            <span class="text-slate-500 tabular-nums">{{ $m->previous_stock }}</span>
                            <span class="text-slate-300 mx-1">→</span>
                            <span class="font-bold tabular-nums {{ $isIn ? 'text-emerald-700' : 'text-rose-700' }}">{{ $m->current_stock }}</span>
                        </td>
                        <td class="p-3.5 text-slate-600 text-[12.5px] leading-snug break-all">{{ $m->reference ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="p-10 text-center text-gray-400">No stock movements found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($movements->hasPages())
        <div class="p-4 border-t border-gray-50" data-sa-pagination>{{ $movements->links() }}</div>
    @endif
</div>
