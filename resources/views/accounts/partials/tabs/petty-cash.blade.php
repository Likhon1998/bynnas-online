<div class="max-w-2xl">
    <x-accounts.panel title="Petty Cash" subtitle="Record small expenses from the petty cash account.">
        <div class="px-6 py-5 border-b border-gray-100 bg-indigo-50/70">
            <p class="text-[10px] font-bold text-indigo-500 uppercase">Available Balance</p>
            <p class="text-3xl font-black text-indigo-600 mt-1">{{ format_taka($pettyBalance) }}</p>
        </div>

        <form action="{{ route('accounts.petty-cash.store') }}" method="POST" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Amount (৳)</label>
                <input type="number" step="0.01" min="0.01" name="amount" required class="w-full border-gray-200 rounded-xl text-sm px-3 py-2.5 focus:border-indigo-500 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Description</label>
                <textarea name="description" rows="3" required class="w-full border-gray-200 rounded-xl text-sm px-3 py-2.5 focus:border-indigo-500 focus:ring-indigo-500" placeholder="e.g. Stationery, courier, tea..."></textarea>
            </div>
            <button type="submit" class="w-full bg-indigo-600 text-white text-sm font-bold py-3 rounded-xl hover:bg-indigo-700 shadow-sm">Record Expense</button>
        </form>
    </x-accounts.panel>
</div>
