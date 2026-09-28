<x-app-layout>
    <div class="w-full min-w-0 py-3 sm:py-4 space-y-3">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Cash Sessions</h2>
                <p class="text-sm text-slate-500 mt-0.5">
                    @if($isAdmin ?? false)
                        Open · sell on POS · close with counted cash
                    @else
                        Your till — opening cash, sales, transfers
                    @endif
                </p>
            </div>
            @if($isAdmin ?? false)
                <a href="{{ route('counters.index') }}" class="text-xs font-bold text-indigo-600 hover:underline">Manage counters</a>
            @endif
        </div>

        @if(session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-800">{{ session('error') }}</div>
        @endif

        <div class="grid lg:grid-cols-12 gap-3">
            <div class="lg:col-span-4 rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="px-3.5 py-2.5 border-b border-slate-100 bg-slate-50">
                    <h3 class="text-xs font-bold uppercase tracking-wide text-slate-500">Open counter</h3>
                </div>
                <div class="p-3.5">
                    @php $availableCounters = $counters->filter(fn ($c) => ! isset($openSessions[$c->id])); @endphp

                    @if($availableCounters->isEmpty())
                        <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2.5 text-xs text-amber-900 font-medium">
                            All listed counters already have an open session. Close one first.
                        </div>
                    @else
                        <form method="POST" action="{{ route('counters.sessions.open') }}" class="space-y-2.5">
                            @csrf
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 mb-1">Counter</label>
                                <select name="counter_id" class="w-full text-sm rounded-lg border-slate-200 py-1.5" required>
                                    <option value="">Select…</option>
                                    @foreach($availableCounters as $counter)
                                        <option value="{{ $counter->id }}" @selected(old('counter_id') == $counter->id)>{{ $counter->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 mb-1">Starting cash (৳)</label>
                                <input type="number" step="0.01" min="0" name="opening_cash" value="{{ old('opening_cash', '0') }}" class="w-full text-sm rounded-lg border-slate-200 py-1.5 font-bold" required>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-slate-500 mb-1">Notes</label>
                                <input type="text" name="notes" value="{{ old('notes') }}" placeholder="Optional" class="w-full text-sm rounded-lg border-slate-200 py-1.5">
                            </div>
                            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold py-2 rounded-lg">Open session</button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="lg:col-span-8 space-y-2">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wide text-slate-500">Currently open</h3>
                    <span class="text-[11px] font-semibold text-slate-400">{{ $openSessions->count() }} open</span>
                </div>
                @forelse($openSessions as $session)
                    @php
                        $stats = $live[$session->counter_id]['stats'] ?? ['order_count'=>0,'total_sales'=>0,'cash_sales'=>0,'transfers_in'=>0,'transfers_out'=>0,'cash_purchases'=>0];
                        $expected = $live[$session->counter_id]['expected'] ?? $session->opening_cash;
                    @endphp
                    <div class="rounded-xl border border-slate-200 bg-white px-3.5 py-3 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="font-bold text-slate-900">{{ $session->counter->name }}</p>
                            <p class="text-[11px] text-slate-500 mt-0.5">
                                {{ $session->opened_at->format('M j, g:i A') }} · {{ $session->opener->name ?? '—' }} · Start {{ format_taka($session->opening_cash) }}
                            </p>
                            <div class="mt-1.5 flex flex-wrap gap-x-3 gap-y-1 text-[11px]">
                                <span class="font-semibold text-slate-700">Sales {{ format_taka($stats['total_sales']) }}</span>
                                <span class="text-slate-500">{{ $stats['order_count'] }} orders</span>
                                <span class="text-emerald-700 font-semibold">Expected {{ format_taka($expected) }}</span>
                            </div>
                        </div>
                        <a href="{{ route('counters.sessions.close-form', $session) }}"
                           class="inline-flex justify-center bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold px-3.5 py-2 rounded-lg whitespace-nowrap">
                            Close &amp; count
                        </a>
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm text-slate-400">
                        No open sessions.
                    </div>
                @endforelse
            </div>
        </div>

        @if($canTransfer ?? false)
        <div class="rounded-xl border border-indigo-100 bg-white shadow-sm overflow-hidden">
            <div class="px-3.5 py-2.5 border-b border-indigo-50 bg-indigo-50/60">
                <h3 class="text-xs font-bold uppercase tracking-wide text-indigo-800">Transfer between counters</h3>
            </div>
            <form method="POST" action="{{ route('counters.sessions.transfer') }}" class="p-3.5 grid sm:grid-cols-2 lg:grid-cols-4 gap-2.5 items-end">
                @csrf
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 mb-1">From</label>
                    <select name="from_counter_id" required class="w-full text-sm rounded-lg border-slate-200 py-1.5">
                        @foreach($openSessions as $session)
                            <option value="{{ $session->counter_id }}" @selected(old('from_counter_id') == $session->counter_id)>
                                {{ $session->counter->name }} (≈ {{ format_taka($live[$session->counter_id]['expected'] ?? $session->opening_cash) }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 mb-1">To</label>
                    <select name="to_counter_id" required class="w-full text-sm rounded-lg border-slate-200 py-1.5">
                        <option value="">Select…</option>
                        @foreach($transferTargets as $target)
                            <option value="{{ $target->id }}" @selected(old('to_counter_id') == $target->id)>{{ $target->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 mb-1">Amount (৳)</label>
                    <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" required
                           class="w-full text-sm rounded-lg border-slate-200 py-1.5 font-bold" placeholder="0.00">
                </div>
                <div>
                    <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold py-2 rounded-lg">Transfer</button>
                </div>
                <div class="sm:col-span-2 lg:col-span-4">
                    <label class="block text-[11px] font-bold text-slate-500 mb-1">Reason *</label>
                    <input type="text" name="reason" value="{{ old('reason') }}" required maxlength="500"
                           class="w-full text-sm rounded-lg border-slate-200 py-1.5"
                           placeholder="Why is cash moving?">
                </div>
            </form>
        </div>
        @endif

        <div class="rounded-xl border border-slate-200 bg-white overflow-hidden shadow-sm">
            <div class="px-3.5 py-2.5 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
                <h3 class="text-xs font-bold uppercase tracking-wide text-slate-500">Session history</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[700px] text-sm">
                    <thead>
                        <tr class="text-[10px] uppercase tracking-wider text-slate-400 border-b border-slate-100">
                            <th class="text-left font-bold px-3 py-2">Counter</th>
                            <th class="text-left font-bold px-2 py-2">Opened</th>
                            <th class="text-left font-bold px-2 py-2">Status</th>
                            <th class="text-right font-bold px-2 py-2">Sales</th>
                            <th class="text-right font-bold px-2 py-2">Open → Close</th>
                            <th class="text-right font-bold px-2 py-2">Variance</th>
                            <th class="px-3 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($recent as $session)
                            <tr class="hover:bg-slate-50/80">
                                <td class="px-3 py-2 font-semibold text-slate-900">{{ $session->counter->name ?? '—' }}</td>
                                <td class="px-2 py-2 text-xs text-slate-500">{{ $session->opened_at->format('M j, H:i') }}</td>
                                <td class="px-2 py-2">
                                    <span class="text-[10px] font-bold uppercase px-1.5 py-0.5 rounded {{ $session->status === 'open' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                        {{ $session->status }}
                                    </span>
                                </td>
                                <td class="px-2 py-2 text-right font-semibold">
                                    @if($session->status === 'closed')
                                        {{ format_taka($session->total_sales) }}
                                        <span class="block text-[10px] text-slate-400 font-medium">{{ $session->order_count }} orders</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-2 py-2 text-right text-xs text-slate-600">
                                    {{ format_taka($session->opening_cash) }}
                                    @if($session->status === 'closed')
                                        → {{ format_taka($session->closing_cash) }}
                                    @endif
                                </td>
                                <td class="px-2 py-2 text-right font-semibold {{ ($session->variance ?? 0) < 0 ? 'text-red-600' : (($session->variance ?? 0) > 0 ? 'text-emerald-600' : 'text-slate-500') }}">
                                    @if($session->status === 'closed')
                                        {{ format_taka($session->variance) }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <a href="{{ route('counters.sessions.show', $session) }}" class="text-indigo-600 font-bold text-xs">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="p-8 text-center text-slate-400 text-sm">No sessions yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($recent->hasPages())
                <div class="px-3 py-2 border-t border-slate-100">{{ $recent->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
