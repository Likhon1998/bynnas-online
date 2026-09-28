<div
    x-show="showAddModal"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
    @keydown.escape.window="showAddModal = false"
>
    <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-sm" @click="showAddModal = false"></div>

    <div
        x-show="showAddModal"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl border border-gray-100 overflow-hidden"
        @click.stop
    >
        <div class="px-6 py-5 border-b border-gray-100 flex items-start justify-between gap-4">
            <div>
                <h3 class="text-lg font-bold text-gray-900">Add New Account</h3>
                <p class="text-xs text-gray-500 mt-0.5">Custom accounts appear instantly in the chart.</p>
            </div>
            <button type="button" @click="showAddModal = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
        </div>

        <form action="{{ route('accounts.chart.store') }}" method="POST" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Account Name</label>
                <input type="text" name="name" required class="w-full border-gray-200 rounded-xl text-sm px-3 py-2.5 focus:border-indigo-500 focus:ring-indigo-500" placeholder="e.g. Office Supplies">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1.5">Account Type</label>
                <select name="type" class="w-full border-gray-200 rounded-xl text-sm px-3 py-2.5 focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="asset">Asset</option>
                    <option value="liability">Liability</option>
                    <option value="equity">Equity</option>
                    <option value="income">Revenue</option>
                    <option value="expense">Expense</option>
                </select>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="button" @click="showAddModal = false" class="flex-1 border border-gray-200 text-gray-600 text-sm font-bold py-2.5 rounded-xl hover:bg-gray-50">Cancel</button>
                <button type="submit" class="flex-1 bg-indigo-600 text-white text-sm font-bold py-2.5 rounded-xl hover:bg-indigo-700 shadow-sm">Add Account</button>
            </div>
        </form>
    </div>
</div>
