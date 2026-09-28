@php
    use App\Support\AccountUi;
@endphp

<div class="space-y-4">
    <x-accounts.panel title="Account Ledger" subtitle="Running balance by account for the selected period.">
        <div class="px-6 py-4 border-b border-gray-100 bg-slate-50/60">
            <form action="{{ route('accounts.ledger') }}" method="GET" class="flex flex-wrap gap-3 items-end">
                <input type="hidden" name="start_date" value="{{ request('start_date', $start->format('Y-m-d')) }}">
                <input type="hidden" name="end_date" value="{{ request('end_date', $end->format('Y-m-d')) }}">
                @if(request('all_time'))
                    <input type="hidden" name="all_time" value="1">
                @endif
                <div class="min-w-[220px]">
                    <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">Account</label>
                    <select name="account_id" onchange="this.form.submit()" class="w-full border-gray-200 rounded-lg text-sm px-3 py-2">
                        @foreach($accountList as $a)
                            <option value="{{ $a->id }}" @selected($ledgerAccount?->id === $a->id)>{{ $a->name }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>

        @if($ledgerAccount)
            <div class="px-6 py-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="font-bold text-gray-900">{{ $ledgerAccount->name }}</p>
                    <p class="text-xs text-gray-500">{{ $ledgerAccount->code }} · {{ AccountUi::typeLabel($ledgerAccount->type) }}</p>
                </div>
                @include('accounts.partials.type-badge', ['type' => $ledgerAccount->type])
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-[11px] uppercase font-bold text-gray-500 tracking-wider">
                        <tr>
                            <th class="px-6 py-3 text-left">Date</th>
                            <th class="px-6 py-3 text-left">Description</th>
                            <th class="px-6 py-3 text-left">Counter</th>
                            <th class="px-6 py-3 text-right">Debit</th>
                            <th class="px-6 py-3 text-right">Credit</th>
                            <th class="px-6 py-3 text-right">Balance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($ledgerEntries as $row)
                            <tr class="hover:bg-gray-50/80">
                                <td class="px-6 py-4 whitespace-nowrap text-gray-500">{{ $row['entry']->transaction->transaction_date->format('d M Y') }}</td>
                                <td class="px-6 py-4 text-gray-900">{{ $row['entry']->transaction->description }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-gray-500">{{ $row['entry']->counter?->name ?? '—' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-gray-700">{{ $row['debit'] > 0 ? format_taka($row['debit']) : '—' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-gray-700">{{ $row['credit'] > 0 ? format_taka($row['credit']) : '—' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right font-bold text-gray-900">{{ format_taka($row['balance']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-400">No entries in this period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </x-accounts.panel>
</div>
