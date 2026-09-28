<x-app-layout>
    <div class="max-w-2xl mx-auto pt-0 pb-12 px-4 sm:px-6 lg:px-8">
        <div class="mb-6 mt-4">
            <a href="{{ route('counters.sessions.index') }}" class="text-sm font-semibold text-indigo-600 hover:underline">← Back</a>
            <h2 class="text-2xl font-black text-gray-950 tracking-tight mt-2">Close {{ $session->counter->name }}</h2>
            <p class="text-sm text-gray-500 mt-1">Count the cash in the drawer and close the day’s session.</p>
        </div>

        @if(session('error'))
            <div class="mb-4 rounded-xl border border-red-100 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">{{ session('error') }}</div>
        @endif

        <div class="bg-white rounded-2xl border border-gray-100 p-5 mb-5 grid grid-cols-2 gap-3 text-sm">
            <div>
                <p class="text-[11px] uppercase font-bold text-gray-400">Opened</p>
                <p class="font-semibold">{{ $session->opened_at->format('M d, Y H:i') }}</p>
            </div>
            <div>
                <p class="text-[11px] uppercase font-bold text-gray-400">Starting cash</p>
                <p class="font-semibold">{{ format_taka($session->opening_cash) }}</p>
            </div>
            <div>
                <p class="text-[11px] uppercase font-bold text-gray-400">Orders</p>
                <p class="font-semibold">{{ $stats['order_count'] }}</p>
            </div>
            <div>
                <p class="text-[11px] uppercase font-bold text-gray-400">Total sales</p>
                <p class="font-semibold">{{ format_taka($stats['total_sales']) }}</p>
            </div>
            <div>
                <p class="text-[11px] uppercase font-bold text-gray-400">Cash sales</p>
                <p class="font-semibold">{{ format_taka($stats['cash_sales']) }}</p>
            </div>
            <div>
                <p class="text-[11px] uppercase font-bold text-gray-400">Card / Mobile</p>
                <p class="font-semibold">{{ format_taka($stats['card_sales'] + $stats['mobile_sales']) }}</p>
            </div>
            <div>
                <p class="text-[11px] uppercase font-bold text-gray-400">Cash refunds</p>
                <p class="font-semibold text-red-600">{{ format_taka($stats['cash_refunds']) }}</p>
            </div>
            <div>
                <p class="text-[11px] uppercase font-bold text-gray-400">Transfers in</p>
                <p class="font-semibold text-emerald-700">{{ format_taka($stats['transfers_in'] ?? 0) }}</p>
            </div>
            <div>
                <p class="text-[11px] uppercase font-bold text-gray-400">Transfers out</p>
                <p class="font-semibold text-amber-700">{{ format_taka($stats['transfers_out'] ?? 0) }}</p>
            </div>
            <div>
                <p class="text-[11px] uppercase font-bold text-gray-400">Cash purchases</p>
                <p class="font-semibold text-red-600">{{ format_taka($stats['cash_purchases'] ?? 0) }}</p>
            </div>
            <div>
                <p class="text-[11px] uppercase font-bold text-gray-400">Expected in drawer</p>
                <p class="font-black text-emerald-700 text-lg">{{ format_taka($expected) }}</p>
                <p class="text-[10px] text-gray-400 mt-0.5">Start + cash sales + transfers in − refunds − transfers out − purchases</p>
            </div>
        </div>

        @include('counters.partials.transfer-log', ['transferLog' => $transferLog ?? [], 'class' => 'mb-5'])

        <form method="POST" action="{{ route('counters.sessions.close', $session) }}" class="bg-white rounded-2xl border border-gray-100 p-5 space-y-4"
              data-confirm="Confirm you physically counted the drawer. Expected is only a guide — enter the real counted cash."
              data-confirm-title="Confirm count?"
              data-confirm-ok="Confirm"
              data-confirm-tone="warning">
            @csrf
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-900">
                <strong>Count the cash yourself.</strong> Expected {{ format_taka($expected) }} is a guide only — do not copy it blindly.
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-gray-500 mb-1">Counted closing cash (৳)</label>
                <input type="number" step="0.01" min="0" name="closing_cash" value="{{ old('closing_cash') }}"
                       placeholder="Enter counted amount" autocomplete="off"
                       class="w-full text-sm rounded-lg border-gray-200 py-2 font-bold" required>
                <p class="mt-1 text-[11px] text-gray-400">Reference expected: {{ format_taka($expected) }}</p>
            </div>
            <div>
                <label class="block text-[11px] font-semibold text-gray-500 mb-1">Closing notes</label>
                <input type="text" name="notes" placeholder="Required if variance — shortage / overage reason"
                       class="w-full text-sm rounded-lg border-gray-200 py-1.5 placeholder:text-gray-400">
            </div>
            <label class="flex items-start gap-2 text-xs text-slate-600">
                <input type="checkbox" name="counted_confirm" value="1" required class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <span>I physically counted the drawer and the amount above is what is in the till.</span>
            </label>
            <button class="w-full bg-slate-900 hover:bg-slate-800 text-white text-sm font-bold py-2.5 rounded-lg">
                Close counter session
            </button>
        </form>
    </div>
</x-app-layout>
