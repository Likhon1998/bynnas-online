<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    EMI — {{ $plan->customer->name ?? 'Customer' }}
                </h2>
                <p class="mt-0.5 text-sm text-slate-500">
                    {{ $plan->customer->phone ?: 'No mobile' }}
                    @if($plan->order)
                        · Invoice <a href="{{ route('pos.receipt', $plan->order) }}" class="font-semibold text-indigo-600 hover:underline" target="_blank">{{ $plan->order->invoice_no }}</a>
                    @endif
                    · {{ $plan->months }} months
                </p>
            </div>
            <a href="{{ route('customers.emi.index') }}"
               class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold uppercase tracking-wide text-slate-700 shadow-sm transition hover:bg-slate-50">
                Back to EMI list
            </a>
        </div>
    </x-slot>

    @php $remaining = round((float) $plan->remaining_amount, 2); @endphp

    <div class="py-6" x-data="{
        remaining: {{ $remaining }},
        amount: null,
        slipOpen: {{ session('open_slip_url') ? 'true' : 'false' }},
        slipUrl: @js(session('open_slip_url')),
        autoPrintSlip: {{ session('print_slip') ? 'true' : 'false' }},
        setAmount(v) {
            const n = Math.min(this.remaining, Math.max(0, Number(v) || 0));
            this.amount = Math.round(n * 100) / 100;
        },
        left() {
            const pay = Math.min(this.remaining, Math.max(0, Number(this.amount) || 0));
            return Math.round((this.remaining - pay) * 100) / 100;
        },
        fmt(n) {
            return Number(n || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        openSlip(url) {
            this.slipUrl = url;
            this.autoPrintSlip = false;
            this.slipOpen = true;
        },
        closeSlip() {
            this.slipOpen = false;
            this.slipUrl = '';
            this.autoPrintSlip = false;
        },
        printSlip() {
            try { this.$refs.slipFrame?.contentWindow?.focus(); this.$refs.slipFrame?.contentWindow?.print(); } catch (e) {}
        },
        onSlipLoaded() {
            if (this.autoPrintSlip) {
                this.autoPrintSlip = false;
                setTimeout(() => this.printSlip(), 350);
            }
        }
    }">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @include('customers.partials.slip-modal')
            @if (session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800">{{ session('error') }}</div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Financed</p>
                    <p class="mt-1 text-2xl font-black text-slate-900">{{ format_taka((float) $plan->principal) }}</p>
                    <p class="mt-1 text-xs text-slate-500">Down paid {{ format_taka((float) $plan->down_payment) }}</p>
                </div>
                <div class="rounded-2xl border border-indigo-200 bg-indigo-50 px-5 py-4 shadow-sm">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-indigo-700">Remaining</p>
                    <p class="mt-1 text-2xl font-black text-indigo-950">{{ format_taka($remaining) }}</p>
                    <p class="mt-1 text-xs text-indigo-700/80">Status: {{ strtoupper($plan->status) }}</p>
                </div>
                <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Monthly</p>
                    <p class="mt-1 text-2xl font-black text-slate-900">{{ format_taka((float) $plan->installment_amount) }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $plan->months }} installments</p>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-3">
                    <h3 class="text-sm font-bold text-slate-800">Products on this EMI</h3>
                    <p class="mt-0.5 text-xs text-slate-500">Items from invoice {{ $plan->order->invoice_no ?? '—' }}</p>
                </div>
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Product</th>
                            <th class="px-4 py-3">SKU / Barcode</th>
                            <th class="px-4 py-3 text-right">Qty</th>
                            <th class="px-4 py-3 text-right">Unit</th>
                            <th class="px-4 py-3 text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse(($plan->order?->items ?? collect()) as $item)
                            @php
                                $product = $item->product;
                                $imgPath = $product ? ($product->image ?: ($product->imagePaths()[0] ?? null)) : null;
                                $imgUrl = public_storage_url($imgPath);
                            @endphp
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="h-14 w-14 shrink-0 overflow-hidden rounded-xl border border-slate-200 bg-slate-100">
                                            @if($imgUrl)
                                                <img src="{{ $imgUrl }}" alt="{{ $product->name ?? 'Product' }}" class="h-full w-full object-cover" loading="lazy">
                                            @else
                                                <div class="flex h-full w-full items-center justify-center text-slate-300">
                                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <div class="font-semibold text-slate-900 truncate">{{ $product->name ?? 'Product' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-500 font-mono">
                                    {{ $product->sku ?: ($product->barcode ?? '—') }}
                                </td>
                                <td class="px-4 py-3 text-right font-semibold">{{ (int) $item->quantity }}</td>
                                <td class="px-4 py-3 text-right">{{ format_taka((float) $item->unit_price) }}</td>
                                <td class="px-4 py-3 text-right font-bold text-slate-800">{{ format_taka((float) $item->subtotal) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-slate-500">No product lines found for this EMI order.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($remaining > 0 && $plan->status === 'active')
            <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-3">Collect EMI payment (partial OK)</p>
                <form method="POST" action="{{ route('customers.emi.pay', $plan) }}" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @csrf
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Amount (৳)</label>
                            <input type="number" name="amount" step="0.01" min="0.01" max="{{ $remaining }}"
                                   x-model.number="amount" required
                                   class="w-full rounded-xl border-slate-200 text-sm font-semibold"
                                   placeholder="e.g. next installment or any partial">
                            <p class="mt-1 text-[11px] text-slate-500">
                                Remaining after pay: <strong class="text-indigo-700">৳<span x-text="fmt(left())"></span></strong>
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" @click="setAmount({{ (float) $plan->installment_amount }})" class="rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-bold text-slate-700 hover:bg-slate-50">1 month</button>
                            <button type="button" @click="setAmount({{ round(min($remaining, (float) $plan->installment_amount * 2), 2) }})" class="rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-bold text-slate-700 hover:bg-slate-50">2 months</button>
                            <button type="button" @click="setAmount(500)" class="rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-bold text-slate-700 hover:bg-slate-50">৳500</button>
                            <button type="button" @click="setAmount(1000)" class="rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-bold text-slate-700 hover:bg-slate-50">৳1,000</button>
                            <button type="button" @click="setAmount(remaining)" class="rounded-lg border border-indigo-200 bg-indigo-50 px-2.5 py-1 text-xs font-bold text-indigo-800 hover:bg-indigo-100">Pay all remaining</button>
                        </div>
                        <p class="text-[11px] text-slate-500 leading-relaxed">
                            Any amount is allowed — early, partial, 2+ months, or full close. Money is applied to the oldest unpaid installment first, then the next, and so on. Due dates do not block early payment.
                        </p>
                    </div>
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Method</label>
                            <select name="method" class="w-full rounded-xl border-slate-200 text-sm" required>
                                <option value="cash">Cash</option>
                                <option value="card">Card</option>
                                <option value="bkash">bKash</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Note (optional)</label>
                            <input type="text" name="note" maxlength="255" class="w-full rounded-xl border-slate-200 text-sm" placeholder="e.g. Month 2 installment">
                        </div>
                        <button type="submit"
                                class="w-full rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-emerald-700 disabled:opacity-50"
                                :disabled="!amount || amount <= 0 || amount > remaining + 0.009">
                            Collect ৳<span x-text="fmt(amount || 0)"></span>
                        </button>
                    </div>
                </form>
            </div>
            @endif

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-3">
                    <h3 class="text-sm font-bold text-slate-800">Installment schedule</h3>
                </div>
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-4 py-3">#</th>
                            <th class="px-4 py-3">Due date</th>
                            <th class="px-4 py-3 text-right">Amount</th>
                            <th class="px-4 py-3 text-right">Paid</th>
                            <th class="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($plan->installments as $inst)
                            @php
                                $tone = match($inst->status) {
                                    'paid' => 'bg-emerald-100 text-emerald-800',
                                    'partial' => 'bg-amber-100 text-amber-800',
                                    'overdue' => 'bg-rose-100 text-rose-800',
                                    default => 'bg-slate-100 text-slate-700',
                                };
                            @endphp
                            <tr>
                                <td class="px-4 py-3 font-semibold">{{ $inst->sequence }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $inst->due_date?->format('d M Y') }}</td>
                                <td class="px-4 py-3 text-right font-semibold">{{ format_taka((float) $inst->amount) }}</td>
                                <td class="px-4 py-3 text-right text-emerald-700">{{ format_taka((float) $inst->paid_amount) }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide {{ $tone }}">{{ $inst->status }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-3">
                    <h3 class="text-sm font-bold text-slate-800">Payment history</h3>
                </div>
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-4 py-3">When</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">Note</th>
                            <th class="px-4 py-3">By</th>
                            <th class="px-4 py-3 text-right">Amount</th>
                            <th class="px-4 py-3 text-right">Slip</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($plan->entries as $entry)
                            @php $amt = (float) $entry->amount; @endphp
                            <tr>
                                <td class="px-4 py-3 text-slate-600 whitespace-nowrap">
                                    {{ $entry->created_at?->timezone(config('app.display_timezone', 'Asia/Dhaka'))->format('d M Y, h:i A') }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide {{ $entry->type === 'payment' ? 'bg-emerald-100 text-emerald-800' : 'bg-indigo-100 text-indigo-800' }}">
                                        {{ $entry->type }}
                                    </span>
                                    @if($entry->method)
                                        <span class="ml-1 text-[10px] text-slate-400">{{ $entry->method }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-500">{{ $entry->note }}</td>
                                <td class="px-4 py-3 text-slate-500">{{ $entry->user->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-right font-bold {{ $amt > 0 ? 'text-indigo-700' : 'text-emerald-700' }}">
                                    {{ $amt > 0 ? '+' : '' }}{{ format_taka($amt) }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if($entry->type === 'payment')
                                        <button type="button"
                                                @click="openSlip(@js(route('customers.emi.slip', ['entry' => $entry, 'embed' => 1])))"
                                                class="inline-flex rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-emerald-800 hover:bg-emerald-100">
                                            Slip
                                        </button>
                                    @else
                                        <span class="text-slate-300">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">No entries yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
