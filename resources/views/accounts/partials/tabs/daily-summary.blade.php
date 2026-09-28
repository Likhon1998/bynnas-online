<div class="space-y-4">
    <x-accounts.panel title="Daily Summary" subtitle="Opening, movements, and closing balance per POS counter.">
        <div class="px-6 py-4 border-b border-gray-100 bg-slate-50/60">
            <form action="{{ route('accounts.daily-summary') }}" method="GET" class="flex flex-wrap gap-3 items-end">
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Date</label>
                    <input type="date" name="date" value="{{ $date->format('Y-m-d') }}" onchange="this.form.submit()" class="border-gray-200 rounded-lg text-sm px-3 py-2">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Counter</label>
                    <select name="counter_id" onchange="this.form.submit()" class="border-gray-200 rounded-lg text-sm px-3 py-2">
                        <option value="">All Counters</option>
                        @foreach($counters as $c)
                            <option value="{{ $c->id }}" @selected($summaryCounterId == $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                @forelse($summaryRows as $row)
                    <div class="border border-gray-100 rounded-xl p-5 bg-slate-50/40">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <p class="text-[10px] font-bold text-gray-400 uppercase">Counter</p>
                                <h4 class="text-base font-black text-gray-900">{{ $row['counter']->name }}</h4>
                            </div>
                            <p class="text-xs text-gray-400">{{ $date->format('d M Y') }}</p>
                        </div>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between py-2 border-b border-gray-100">
                                <span class="text-gray-500">Opening Balance</span>
                                <span class="font-bold text-gray-900">{{ format_taka($row['opening']) }}</span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-100">
                                <span class="text-emerald-600">Sales (Cash In)</span>
                                <span class="font-bold text-emerald-600">+{{ format_taka($row['sales_in']) }}</span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-100">
                                <span class="text-indigo-600">Transfers In</span>
                                <span class="font-bold text-indigo-600">+{{ format_taka($row['transfers_in']) }}</span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-100">
                                <span class="text-rose-600">Transfers Out</span>
                                <span class="font-bold text-rose-600">-{{ format_taka($row['transfers_out']) }}</span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-100">
                                <span class="text-rose-600">Refunds Out</span>
                                <span class="font-bold text-rose-600">-{{ format_taka($row['refunds_out']) }}</span>
                            </div>
                            <div class="flex justify-between py-3 bg-white rounded-xl px-3 mt-2 border border-gray-100">
                                <span class="font-bold text-gray-800">Closing Balance</span>
                                <span class="font-black text-indigo-600">{{ format_taka($row['closing']) }}</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-2 py-12 text-center text-gray-400">
                        No counters found. Add counters under Settings to track multi-counter cash.
                    </div>
                @endforelse
            </div>
        </div>
    </x-accounts.panel>
</div>
