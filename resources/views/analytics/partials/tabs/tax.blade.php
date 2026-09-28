<div class="space-y-4">
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-sm">
            <p class="text-[10px] font-bold text-gray-400 uppercase">Taxable Sales</p>
            <p class="text-2xl font-black text-gray-900 mt-2">{{ format_taka($kpis['revenue']) }}</p>
        </div>
        <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-sm">
            <p class="text-[10px] font-bold text-gray-400 uppercase">Tax Collected</p>
            <p class="text-2xl font-black text-indigo-600 mt-2">{{ format_taka($taxTotal) }}</p>
        </div>
        <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-sm">
            <p class="text-[10px] font-bold text-gray-400 uppercase">Tax Rate</p>
            <p class="text-2xl font-black text-gray-500 mt-2">—</p>
        </div>
    </div>

    <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-8 text-center">
        <div class="mx-auto h-12 w-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center mb-4">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <h3 class="font-bold text-gray-900">Tax / VAT not tracked yet</h3>
        <p class="text-sm text-gray-500 mt-2 max-w-md mx-auto">
            Orders in this system do not store tax separately. You can still export this report — it includes taxable sales for the period with tax collected as ৳0.00.
        </p>
        <button
            type="button"
            @click="$dispatch('open-report-preview', { tab: 'tax' })"
            class="inline-flex items-center gap-2 mt-5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold px-4 py-2.5 rounded-xl"
        >
            Preview Tax Report
        </button>
    </div>
</div>
