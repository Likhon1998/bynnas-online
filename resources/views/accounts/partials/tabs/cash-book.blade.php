<div class="space-y-4">
    <x-accounts.panel title="Cash Book" subtitle="Cash movements per counter — POS cash, petty cash, and payment wallets.">
        <div class="px-6 py-4 border-b border-gray-100 bg-slate-50/60">
            <form action="{{ route('accounts.cash-book') }}" method="GET" class="flex flex-wrap gap-3 items-end">
                <input type="hidden" name="start_date" value="{{ request('start_date', $start->format('Y-m-d')) }}">
                <input type="hidden" name="end_date" value="{{ request('end_date', $end->format('Y-m-d')) }}">
                @if(request('all_time'))
                    <input type="hidden" name="all_time" value="1">
                @endif
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Counter</label>
                    <select name="counter_id" onchange="this.form.submit()" class="border-gray-200 rounded-lg text-sm px-3 py-2">
                        <option value="">All / Shop-level</option>
                        @foreach($counters as $c)
                            <option value="{{ $c->id }}" @selected(request('counter_id') == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="min-w-[220px]">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Cash Account</label>
                    <select name="account_id" onchange="this.form.submit()" class="w-full border-gray-200 rounded-lg text-sm px-3 py-2">
                        @foreach($cashAccounts as $a)
                            <option value="{{ $a->id }}" @selected($cashBookAccount?->id === $a->id)>{{ $a->name }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>

        @if($cashBookAccount)
            <div class="px-6 py-5 bg-indigo-50/70 border-b border-indigo-100 flex flex-wrap justify-between items-center gap-4">
                <div>
                    <p class="text-[10px] font-bold text-indigo-500 uppercase">Current Balance</p>
                    <p class="text-lg font-black text-indigo-800">{{ $cashBookAccount->name }}</p>
                </div>
                <p class="text-2xl font-black text-indigo-600">{{ format_taka($cashBookBalance) }}</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-[11px] uppercase font-bold text-gray-500 tracking-wider">
                        <tr>
                            <th class="px-6 py-3 text-left">Date</th>
                            <th class="px-6 py-3 text-left">Type</th>
                            <th class="px-6 py-3 text-left">Description</th>
                            <th class="px-6 py-3 text-left">Counter</th>
                            <th class="px-6 py-3 text-right">In</th>
                            <th class="px-6 py-3 text-right">Out</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($cashBookEntries as $entry)
                            <tr class="hover:bg-gray-50/80">
                                <td class="px-6 py-4 whitespace-nowrap text-gray-500">{{ $entry->transaction->transaction_date->format('d M Y') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap capitalize text-gray-700">{{ str_replace('_', ' ', $entry->transaction->type) }}</td>
                                <td class="px-6 py-4 text-gray-900">{{ $entry->transaction->description }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-500">{{ $entry->counter?->name ?? '—' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-emerald-600 font-semibold">
                                    {{ $entry->entry_type === 'debit' ? format_taka($entry->amount) : '—' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-rose-600 font-semibold">
                                    {{ $entry->entry_type === 'credit' ? format_taka($entry->amount) : '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-400">No cash movements in this period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </x-accounts.panel>
</div>
