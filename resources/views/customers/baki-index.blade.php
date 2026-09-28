<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Customer Baki</h2>
                <p class="mt-0.5 text-sm text-slate-500">Collect any amount toward outstanding credit — partial payments allowed.</p>
            </div>
            <a href="{{ route('customers.index') }}"
               class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold uppercase tracking-wide text-slate-700 shadow-sm transition hover:bg-slate-50">
                All customers
            </a>
        </div>
    </x-slot>

    <div class="py-6" x-data="bakiCollect({
        initialSlipUrl: @js(session('open_slip_url')),
        autoPrint: @js((bool) session('print_slip')),
    })">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
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

            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 shadow-sm flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-amber-700">Total outstanding</p>
                    <p class="mt-1 text-3xl font-black text-amber-900">{{ format_taka($totalOutstanding) }}</p>
                    <p class="mt-1 text-xs text-amber-700/80">{{ $customers->count() }} customer(s) with baki</p>
                </div>
                <p class="text-xs font-semibold text-amber-800/90 max-w-sm">
                    Example: balance ৳10,000 — customer can pay ৳200 now. Remaining stays as baki.
                </p>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Customer</th>
                            <th class="px-4 py-3">Mobile</th>
                            <th class="px-4 py-3 text-right">Baki balance</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($customers as $customer)
                            <tr class="hover:bg-slate-50/80">
                                <td class="px-4 py-3 font-semibold text-slate-900">{{ $customer->name }}</td>
                                <td class="px-4 py-3 text-slate-600">{{ $customer->phone ?: '—' }}</td>
                                <td class="px-4 py-3 text-right font-bold text-amber-700">{{ format_taka((float) $customer->baki_balance) }}</td>
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <button type="button"
                                                @click="openPay({
                                                    id: {{ (int) $customer->id }},
                                                    name: @js($customer->name),
                                                    phone: @js($customer->phone ?: ''),
                                                    balance: {{ (float) $customer->baki_balance }},
                                                    action: @js(route('customers.baki.pay', $customer)),
                                                })"
                                                class="inline-flex rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-emerald-700">
                                            Collect
                                        </button>
                                        <a href="{{ route('customers.baki.show', $customer) }}"
                                           class="inline-flex rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-50">
                                            History
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-10 text-center text-slate-500">No customers with outstanding baki.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Collect partial payment modal --}}
        <div x-show="open" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             @keydown.escape.window="close()">
            <div class="absolute inset-0 bg-slate-900/50" @click="close()"></div>
            <div class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 shadow-2xl"
                 @click.stop>
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Collect baki payment</h3>
                        <p class="text-sm text-slate-500" x-text="customer?.name"></p>
                        <p class="text-xs text-slate-400" x-show="customer?.phone" x-text="customer?.phone"></p>
                    </div>
                    <button type="button" @click="close()" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-amber-700">Current baki</p>
                    <p class="text-2xl font-black text-amber-900">৳<span x-text="fmt(customer?.balance || 0)"></span></p>
                </div>

                <form method="POST" :action="customer?.action" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Pay now (৳) — any amount up to balance</label>
                        <input type="number" name="amount" step="0.01" min="0.01"
                               x-model.number="amount"
                               :max="customer?.balance || 0"
                               class="w-full rounded-xl border-slate-200 text-sm font-semibold focus:border-emerald-500 focus:ring-emerald-500"
                               placeholder="e.g. 200" required>
                        <p class="mt-1 text-[11px] text-slate-500">
                            Partial OK. Remaining after pay:
                            <strong class="text-amber-700">৳<span x-text="fmt(remaining())"></span></strong>
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button type="button" @click="setAmount(200)" class="rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-bold text-slate-700 hover:bg-slate-50">৳200</button>
                        <button type="button" @click="setAmount(500)" class="rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-bold text-slate-700 hover:bg-slate-50">৳500</button>
                        <button type="button" @click="setAmount(1000)" class="rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-bold text-slate-700 hover:bg-slate-50">৳1,000</button>
                        <button type="button" @click="setAmount(customer?.balance || 0)" class="rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-800 hover:bg-emerald-100">Pay full</button>
                    </div>

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
                        <input type="text" name="note" maxlength="255"
                               class="w-full rounded-xl border-slate-200 text-sm" placeholder="e.g. Partial payment ৳200">
                    </div>
                    <button type="submit"
                            class="w-full rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-emerald-700 disabled:opacity-50"
                            :disabled="!amount || amount <= 0 || amount > (customer?.balance || 0) + 0.009">
                        Collect ৳<span x-text="fmt(amount || 0)"></span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function bakiCollect(opts = {}) {
            return {
                open: false,
                customer: null,
                amount: null,
                slipOpen: !!opts.initialSlipUrl,
                slipUrl: opts.initialSlipUrl || '',
                autoPrintSlip: !!opts.autoPrint,
                openPay(c) {
                    this.customer = c;
                    this.amount = null;
                    this.open = true;
                },
                close() {
                    this.open = false;
                    this.customer = null;
                    this.amount = null;
                },
                setAmount(v) {
                    const bal = Number(this.customer?.balance || 0);
                    const n = Math.min(bal, Math.max(0, Number(v) || 0));
                    this.amount = Math.round(n * 100) / 100;
                },
                remaining() {
                    const bal = Number(this.customer?.balance || 0);
                    const pay = Math.min(bal, Math.max(0, Number(this.amount) || 0));
                    return Math.round((bal - pay) * 100) / 100;
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
                    try {
                        this.$refs.slipFrame?.contentWindow?.focus();
                        this.$refs.slipFrame?.contentWindow?.print();
                    } catch (e) {}
                },
                onSlipLoaded() {
                    if (this.autoPrintSlip) {
                        this.autoPrintSlip = false;
                        setTimeout(() => this.printSlip(), 350);
                    }
                },
            };
        }
    </script>
</x-app-layout>
