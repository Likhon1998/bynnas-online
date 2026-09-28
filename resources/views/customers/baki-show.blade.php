<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $customer->name }} — Baki</h2>
                <p class="mt-0.5 text-sm text-slate-500">{{ $customer->phone ?: 'No mobile' }} · Collect any partial amount toward this balance</p>
            </div>
            <a href="{{ route('customers.baki.index') }}"
               class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold uppercase tracking-wide text-slate-700 shadow-sm transition hover:bg-slate-50">
                Back to list
            </a>
        </div>
    </x-slot>

    @php $balance = round((float) $customer->baki_balance, 2); @endphp

    <div class="py-6" x-data="{
        balance: {{ $balance }},
        amount: {{ old('amount') !== null ? (float) old('amount') : 'null' }},
        slipOpen: {{ session('open_slip_url') ? 'true' : 'false' }},
        slipUrl: @js(session('open_slip_url')),
        autoPrintSlip: {{ session('print_slip') ? 'true' : 'false' }},
        remaining() {
            const pay = Math.min(this.balance, Math.max(0, Number(this.amount) || 0));
            return Math.round((this.balance - pay) * 100) / 100;
        },
        setAmount(v) {
            const n = Math.min(this.balance, Math.max(0, Number(v) || 0));
            this.amount = Math.round(n * 100) / 100;
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
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800">
                    {{ session('error') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                    <ul class="list-disc pl-4">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 shadow-sm">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-amber-700">Current baki</p>
                    <p class="mt-1 text-3xl font-black text-amber-900">{{ format_taka($balance) }}</p>
                    <p class="mt-2 text-xs font-semibold text-amber-800/80">
                        Customer can pay any amount (e.g. ৳200). Rest stays as baki.
                    </p>
                </div>

                @if($balance > 0)
                <div class="rounded-2xl border border-slate-200 bg-white px-5 py-4 shadow-sm">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-3">Collect payment (partial OK)</p>
                    <form method="POST" action="{{ route('customers.baki.pay', $customer) }}" class="space-y-3">
                        @csrf
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Amount to collect (৳)</label>
                            <input type="number" name="amount" step="0.01" min="0.01"
                                   max="{{ $balance }}"
                                   x-model.number="amount"
                                   class="w-full rounded-xl border-slate-200 text-sm font-semibold focus:border-emerald-500 focus:ring-emerald-500"
                                   placeholder="e.g. 200" required>
                            <p class="mt-1 text-[11px] text-slate-500">
                                Max {{ format_taka($balance) }} · Remaining after this payment:
                                <strong class="text-amber-700">৳<span x-text="fmt(remaining())"></span></strong>
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button type="button" @click="setAmount(200)" class="rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-bold text-slate-700 hover:bg-slate-50">৳200</button>
                            <button type="button" @click="setAmount(500)" class="rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-bold text-slate-700 hover:bg-slate-50">৳500</button>
                            <button type="button" @click="setAmount(1000)" class="rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-bold text-slate-700 hover:bg-slate-50">৳1,000</button>
                            <button type="button" @click="setAmount(balance)" class="rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-800 hover:bg-emerald-100">Pay full</button>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Method</label>
                            <select name="method" class="w-full rounded-xl border-slate-200 text-sm" required>
                                <option value="cash" @selected(old('method', 'cash') === 'cash')>Cash</option>
                                <option value="card" @selected(old('method') === 'card')>Card</option>
                                <option value="bkash" @selected(old('method') === 'bkash')>bKash</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Note (optional)</label>
                            <input type="text" name="note" value="{{ old('note') }}" maxlength="255"
                                   class="w-full rounded-xl border-slate-200 text-sm" placeholder="e.g. Partial payment ৳200">
                        </div>
                        <button type="submit"
                                class="w-full rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-emerald-700 disabled:opacity-50"
                                :disabled="!amount || amount <= 0 || amount > balance + 0.009">
                            Collect ৳<span x-text="fmt(amount || 0)"></span>
                        </button>
                    </form>
                </div>
                @else
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 shadow-sm flex items-center">
                    <p class="text-sm font-semibold text-emerald-800">No outstanding baki for this customer.</p>
                </div>
                @endif
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 px-4 py-3">
                    <h3 class="text-sm font-bold text-slate-800">Payment & credit history</h3>
                </div>
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-4 py-3">When</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3">Invoice / note</th>
                            <th class="px-4 py-3">By</th>
                            <th class="px-4 py-3 text-right">Amount</th>
                            <th class="px-4 py-3 text-right">Slip</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($entries as $entry)
                            @php
                                $amt = (float) $entry->amount;
                                $isCredit = $amt > 0;
                            @endphp
                            <tr>
                                <td class="px-4 py-3 text-slate-600 whitespace-nowrap">
                                    {{ $entry->created_at?->timezone(config('app.display_timezone', 'Asia/Dhaka'))->format('d M Y, h:i A') }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide
                                        {{ $entry->type === 'sale' ? 'bg-amber-100 text-amber-800' : ($entry->type === 'payment' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700') }}">
                                        {{ $entry->type }}
                                    </span>
                                    @if($entry->method)
                                        <span class="ml-1 text-[10px] text-slate-400">{{ $entry->method }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    @if($entry->order)
                                        <a href="{{ route('pos.receipt', $entry->order) }}" class="font-semibold text-indigo-600 hover:underline" target="_blank">
                                            {{ $entry->order->invoice_no }}
                                        </a>
                                    @endif
                                    @if($entry->note)
                                        <div class="text-xs text-slate-500">{{ $entry->note }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-500">{{ $entry->user->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-right font-bold {{ $isCredit ? 'text-amber-700' : 'text-emerald-700' }}">
                                    {{ $isCredit ? '+' : '' }}{{ format_taka($amt) }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if($entry->type === 'payment')
                                        <button type="button"
                                                @click="openSlip(@js(route('customers.baki.slip', ['entry' => $entry, 'embed' => 1])))"
                                                class="inline-flex rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-emerald-800 hover:bg-emerald-100">
                                            Slip
                                        </button>
                                    @else
                                        <span class="text-slate-300">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-10 text-center text-slate-500">No baki entries yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                @if($entries->hasPages())
                    <div class="border-t border-slate-100 px-4 py-3">
                        {{ $entries->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
