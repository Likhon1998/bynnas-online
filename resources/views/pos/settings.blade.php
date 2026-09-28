<x-app-layout>
    <div class="max-w-2xl mx-auto space-y-5">
        <div>
            <h1 class="text-[1.35rem] font-bold text-slate-900 tracking-tight">POS Modes</h1>
            <p class="mt-0.5 text-sm text-slate-500">Turn EMI, Baki, and product sale discounts on or off for the POS terminal. Off switches hide the feature and block it on checkout.</p>
        </div>

        @if(session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('pos.settings.update') }}" class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            @csrf
            @method('PUT')

            <div class="divide-y divide-slate-100">
                <label class="flex items-start gap-4 p-5 cursor-pointer hover:bg-slate-50/80">
                    <input type="checkbox" name="pos_emi_enabled" value="1" class="mt-1 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                           @checked(old('pos_emi_enabled', $shop->pos_emi_enabled))>
                    <span class="min-w-0">
                        <span class="block text-sm font-bold text-slate-900">EMI checkout</span>
                        <span class="mt-0.5 block text-xs text-slate-500">Cashiers can sell on installment plans (down payment + months). Collection pages stay available either way.</span>
                    </span>
                </label>

                <label class="flex items-start gap-4 p-5 cursor-pointer hover:bg-slate-50/80">
                    <input type="checkbox" name="pos_baki_enabled" value="1" class="mt-1 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                           @checked(old('pos_baki_enabled', $shop->pos_baki_enabled))>
                    <span class="min-w-0">
                        <span class="block text-sm font-bold text-slate-900">Baki (credit) checkout</span>
                        <span class="mt-0.5 block text-xs text-slate-500">Cashiers can leave unpaid balance as customer credit (baki).</span>
                    </span>
                </label>

                <label class="flex items-start gap-4 p-5 cursor-pointer hover:bg-slate-50/80">
                    <input type="checkbox" name="pos_sale_enabled" value="1" class="mt-1 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                           @checked(old('pos_sale_enabled', $shop->pos_sale_enabled))>
                    <span class="min-w-0">
                        <span class="block text-sm font-bold text-slate-900">Product discounts / sales</span>
                        <span class="mt-0.5 block text-xs text-slate-500">Apply timed sales and permanent product discounts at POS. When off, POS always charges the list selling price.</span>
                    </span>
                </label>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-slate-100 bg-slate-50/80 px-5 py-4">
                <a href="{{ route('dashboard') }}" class="rounded-xl px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">Cancel</a>
                <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-indigo-700">Save modes</button>
            </div>
        </form>
    </div>
</x-app-layout>
