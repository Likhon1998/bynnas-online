<x-app-layout>
    <div class="max-w-3xl mx-auto space-y-5">
        <div>
            <h1 class="text-[1.35rem] font-bold text-slate-900 tracking-tight">Delivery Settings</h1>
            <p class="mt-0.5 text-sm text-slate-500">Control website delivery fees, free-delivery threshold, COD, and confirmation (advance) charge. All checkout totals use these rules live.</p>
        </div>

        @if(session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('cms.delivery.update') }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 class="text-sm font-bold text-slate-900">Delivery zones</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Customer picks Inside / Outside Dhaka at checkout.</p>
                </div>
                <div class="grid gap-4 p-5 sm:grid-cols-2">
                    <div>
                        <label class="text-xs font-bold uppercase tracking-wide text-slate-500">Inside Dhaka (৳)</label>
                        <input type="number" step="0.01" min="0" name="delivery_inside_dhaka"
                               value="{{ old('delivery_inside_dhaka', $settings->delivery_inside_dhaka ?? 60) }}"
                               class="mt-1.5 w-full rounded-xl border-slate-200 text-sm" required>
                        <x-input-error class="mt-1" :messages="$errors->get('delivery_inside_dhaka')" />
                    </div>
                    <div>
                        <label class="text-xs font-bold uppercase tracking-wide text-slate-500">Outside Dhaka (৳)</label>
                        <input type="number" step="0.01" min="0" name="delivery_outside_dhaka"
                               value="{{ old('delivery_outside_dhaka', $settings->delivery_outside_dhaka ?? 120) }}"
                               class="mt-1.5 w-full rounded-xl border-slate-200 text-sm" required>
                        <x-input-error class="mt-1" :messages="$errors->get('delivery_outside_dhaka')" />
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 class="text-sm font-bold text-slate-900">Free delivery</h2>
                </div>
                <div class="p-5 space-y-4">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" name="delivery_free_enabled" value="1"
                               class="mt-1 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                               @checked(old('delivery_free_enabled', $settings->delivery_free_enabled ?? true))>
                        <span>
                            <span class="block text-sm font-bold text-slate-900">Enable free delivery over a cart total</span>
                            <span class="mt-0.5 block text-xs text-slate-500">When cart products (before delivery) reach the amount below, delivery becomes ৳0.</span>
                        </span>
                    </label>
                    <div>
                        <label class="text-xs font-bold uppercase tracking-wide text-slate-500">Free delivery minimum (৳)</label>
                        <input type="number" step="0.01" min="0" name="delivery_free_min_amount"
                               value="{{ old('delivery_free_min_amount', $settings->delivery_free_min_amount ?? 10000) }}"
                               class="mt-1.5 w-full rounded-xl border-slate-200 text-sm" required>
                        <x-input-error class="mt-1" :messages="$errors->get('delivery_free_min_amount')" />
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 class="text-sm font-bold text-slate-900">Payment options</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Enable COD and/or a confirmation (advance) charge. At least one should stay on.</p>
                </div>
                <div class="divide-y divide-slate-100">
                    <label class="flex items-start gap-3 p-5 cursor-pointer hover:bg-slate-50/80">
                        <input type="checkbox" name="delivery_cod_enabled" value="1"
                               class="mt-1 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                               @checked(old('delivery_cod_enabled', $settings->delivery_cod_enabled ?? true))>
                        <span>
                            <span class="block text-sm font-bold text-slate-900">Cash on delivery (COD)</span>
                            <span class="mt-0.5 block text-xs text-slate-500">Customer pays the full bill when the parcel arrives. Receipt shows COD DUE until you mark delivered.</span>
                        </span>
                    </label>

                    <div class="p-5 space-y-4">
                        <label class="flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" name="delivery_confirmation_enabled" value="1"
                                   class="mt-1 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                   @checked(old('delivery_confirmation_enabled', $settings->delivery_confirmation_enabled ?? false))>
                            <span>
                                <span class="block text-sm font-bold text-slate-900">Confirmation / advance charge</span>
                                <span class="mt-0.5 block text-xs text-slate-500">Customer pays a small amount up front to confirm the order; remaining balance is due on delivery.</span>
                            </span>
                        </label>
                        <div>
                            <label class="text-xs font-bold uppercase tracking-wide text-slate-500">Confirmation amount (৳)</label>
                            <input type="number" step="0.01" min="0" name="delivery_confirmation_amount"
                                   value="{{ old('delivery_confirmation_amount', $settings->delivery_confirmation_amount ?? 0) }}"
                                   class="mt-1.5 w-full rounded-xl border-slate-200 text-sm">
                            <x-input-error class="mt-1" :messages="$errors->get('delivery_confirmation_amount')" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('dashboard') }}" class="rounded-xl px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">Cancel</a>
                <button type="submit" class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-indigo-700">Save delivery settings</button>
            </div>
        </form>
    </div>
</x-app-layout>
