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

            @php
                $zoneRows = old('zones', $zones->map(fn ($z) => [
                    'id' => $z->id, 'name' => $z->name, 'code' => $z->code, 'fee' => (float) $z->fee,
                    'note' => $z->note, 'is_active' => $z->is_active,
                ])->values()->all());
                $defaultZone = old('default_zone', optional($zones->firstWhere('is_default', true))->code ?? optional($zones->first())->code);
            @endphp
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden"
                 x-data="{ zones: @js(array_values($zoneRows)), defaultZone: @js($defaultZone), add() { this.zones.push({ id: null, name: '', code: '', fee: 0, note: '', is_active: true }); } }">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 px-5 py-4">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Delivery zones</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Customers pick one of the active zones at checkout and on landing pages.</p>
                    </div>
                    <button type="button" @click="add()" class="rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-700 hover:bg-indigo-100">+ Add zone</button>
                </div>
                <x-input-error class="px-5 pt-3" :messages="$errors->get('zones')" />
                <div class="divide-y divide-slate-100">
                    <template x-for="(zone, i) in zones" :key="i">
                        <div class="grid gap-3 p-4 sm:grid-cols-12 sm:items-end" :class="zone.delete ? 'opacity-40' : ''">
                            <input type="hidden" :name="`zones[${i}][id]`" :value="zone.id ?? ''">
                            <input type="hidden" :name="`zones[${i}][delete]`" :value="zone.delete ? 1 : 0">
                            <div class="sm:col-span-3">
                                <label class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Name</label>
                                <input type="text" :name="`zones[${i}][name]`" x-model="zone.name" maxlength="80" required placeholder="e.g. Chattogram city"
                                       class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Code</label>
                                <input type="text" :name="`zones[${i}][code]`" x-model="zone.code" maxlength="40" placeholder="auto"
                                       class="mt-1 w-full rounded-xl border-slate-200 font-mono text-xs" :readonly="!!zone.id">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Fee (৳)</label>
                                <input type="number" step="0.01" min="0" :name="`zones[${i}][fee]`" x-model="zone.fee" required
                                       class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                            </div>
                            <div class="sm:col-span-3">
                                <label class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Note (shown to customer)</label>
                                <input type="text" :name="`zones[${i}][note]`" x-model="zone.note" maxlength="160" placeholder="e.g. 1–2 days"
                                       class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                            </div>
                            <div class="flex flex-wrap items-center gap-3 sm:col-span-2 sm:justify-end">
                                <label class="flex items-center gap-1.5 text-xs font-semibold text-slate-600">
                                    <input type="checkbox" :name="`zones[${i}][is_active]`" value="1" x-model="zone.is_active" class="rounded border-slate-300 text-indigo-600">
                                    Active
                                </label>
                                <label class="flex items-center gap-1.5 text-xs font-semibold text-slate-600" title="Pre-selected at checkout">
                                    <input type="radio" name="default_zone" :value="zone.code" x-model="defaultZone" :disabled="!zone.code" class="border-slate-300 text-indigo-600">
                                    Default
                                </label>
                                <button type="button" @click="zone.id ? (zone.delete = !zone.delete) : zones.splice(i, 1)"
                                        class="text-xs font-bold text-rose-600 hover:underline" x-text="zone.delete ? 'Undo' : 'Remove'"></button>
                            </div>
                        </div>
                    </template>
                </div>
                <p class="border-t border-slate-100 px-5 py-3 text-[11px] text-slate-500">Existing orders keep the zone they were placed with. Codes are fixed once saved.</p>
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

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 class="text-sm font-bold text-slate-900">Online payment gateways</h2>
                    <p class="mt-0.5 text-xs text-slate-500">bKash, Nagad and card payments stay off until credentials are added to the server <code>.env</code> and a payment driver is installed.</p>
                </div>
                <div class="divide-y divide-slate-100">
                    @foreach(collect($paymentMethods)->where('type', 'gateway') as $method)
                        <div class="flex items-center justify-between gap-3 px-5 py-3">
                            <div>
                                <p class="text-sm font-bold text-slate-900">{{ $method['label'] }}</p>
                                <p class="text-[11px] text-slate-500">{{ $method['available'] ? 'Configured' : $method['reason'] }}</p>
                            </div>
                            <span class="rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $method['available'] ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                {{ $method['available'] ? 'Ready' : 'Not active' }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('dashboard') }}" class="rounded-xl px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100">Cancel</a>
                <button type="submit" class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-indigo-700">Save delivery settings</button>
            </div>
        </form>
    </div>
</x-app-layout>
