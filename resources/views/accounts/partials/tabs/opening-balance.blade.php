@php
    use App\Support\AccountUi;
@endphp

<x-accounts.panel title="Opening Balances" subtitle="Set starting balances per account. Each counter has its own cash account.">
    <form action="{{ route('accounts.opening-balance.update') }}" method="POST">
        @csrf
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-[11px] uppercase font-bold text-gray-500 tracking-wider">
                    <tr>
                        <th class="px-6 py-3 text-left">Account Code</th>
                        <th class="px-6 py-3 text-left">Account Name</th>
                        <th class="px-6 py-3 text-left">Account Type</th>
                        <th class="px-6 py-3 text-left">Counter</th>
                        <th class="px-6 py-3 text-right">Opening Balance</th>
                        <th class="px-6 py-3 text-right">Current Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @foreach($accounts as $row)
                        @php $account = $row['account']; @endphp
                        <tr class="hover:bg-gray-50/80 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-600">{{ $account->code }}</td>
                            <td class="px-6 py-4 whitespace-nowrap font-semibold text-gray-900">{{ $account->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @include('accounts.partials.type-badge', ['type' => $account->type])
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-500">{{ $account->counter?->name ?? '—' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    name="balances[{{ $account->id }}]"
                                    value="{{ number_format($account->opening_balance, 2, '.', '') }}"
                                    class="w-32 text-right border-gray-200 rounded-lg text-sm px-2 py-1.5 focus:border-indigo-500 focus:ring-indigo-500"
                                >
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right font-bold {{ AccountUi::balanceTone($account->type, (float) $row['balance']) }}">
                                {{ format_taka($row['balance']) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-gray-100 bg-slate-50/60 flex justify-end">
            <button type="submit" class="bg-indigo-600 text-white text-sm font-bold px-5 py-2.5 rounded-xl hover:bg-indigo-700 shadow-sm">Save Opening Balances</button>
        </div>
    </form>
</x-accounts.panel>
