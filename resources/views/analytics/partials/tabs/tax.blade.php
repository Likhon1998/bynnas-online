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
        <div class="flex items-start gap-3 bg-amber-50/60 border border-amber-100 rounded-2xl p-5">
            <span class="h-8 w-8 shrink-0 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </span>
            <div class="min-w-0">
                <p class="text-sm font-bold text-gray-900">Tax / VAT not tracked yet</p>
                <p class="text-xs text-gray-500 mt-1">Orders don't store tax separately, so exports list taxable sales with tax collected as ৳0.00.</p>
            </div>
        </div>
    </div>
</div>
