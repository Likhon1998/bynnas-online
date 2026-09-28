<div class="max-w-2xl">
    <x-accounts.panel title="Account Transfer" :subtitle="retail_enabled() ? 'Move funds between counters or cash accounts. Counter-to-counter moves update both tills’ expected cash.' : 'Move funds between cash, bank and wallet accounts.'">
        <form action="{{ route('accounts.transfer.store') }}" method="POST" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">From Account</label>
                <select name="from_account_id" required class="w-full border-gray-200 rounded-xl text-sm px-3 py-2.5 focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach($transferAccounts as $a)
                        <option value="{{ $a->id }}">{{ $a->name }}@if($a->counter) ({{ $a->counter->name }})@endif</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">To Account</label>
                <select name="to_account_id" required class="w-full border-gray-200 rounded-xl text-sm px-3 py-2.5 focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach($transferAccounts as $a)
                        <option value="{{ $a->id }}">{{ $a->name }}@if($a->counter) ({{ $a->counter->name }})@endif</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Amount (৳)</label>
                <input type="number" step="0.01" min="0.01" name="amount" required class="w-full border-gray-200 rounded-xl text-sm px-3 py-2.5 focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Reason / justification *</label>
                <input type="text" name="description" required class="w-full border-gray-200 rounded-xl text-sm px-3 py-2.5 focus:border-indigo-500 focus:ring-indigo-500" placeholder="{{ retail_enabled() ? 'e.g. Counter 1 → Counter 2 float for change' : 'e.g. Courier COD settlement deposited to bank' }}">
                <p class="mt-1.5 text-[11px] text-gray-400">Required for audit.{{ retail_enabled() ? ' Both counters will see this on their cash session.' : '' }}</p>
            </div>
            <button type="submit" class="w-full bg-indigo-600 text-white text-sm font-bold py-3 rounded-xl hover:bg-indigo-700 shadow-sm">Transfer Funds</button>
        </form>
    </x-accounts.panel>
</div>
