<x-app-layout>
    <div class="max-w-3xl mx-auto pt-0 pb-12 px-4 sm:px-6 lg:px-8">
        <div class="mb-6 mt-4">
            <a href="{{ route('counters.sessions.index') }}" class="text-sm font-semibold text-indigo-600 hover:underline">← Back to sessions</a>
            <h2 class="text-2xl font-black text-gray-950 tracking-tight mt-2">
                {{ $session->counter->name ?? 'Counter' }} session
            </h2>
            <p class="text-sm text-gray-500 mt-1">
                {{ $session->opened_at->format('M d, Y H:i') }}
                @if($session->closed_at)
                    → {{ $session->closed_at->format('H:i') }}
                @endif
                · <span class="uppercase font-bold text-xs">{{ $session->status }}</span>
            </p>
        </div>

        @if(session('success'))
            <div class="mb-4 rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
        @endif

        <div class="grid sm:grid-cols-2 gap-3 mb-6">
            <div class="bg-white rounded-xl border p-4">
                <p class="text-[11px] font-bold uppercase text-gray-400">Starting cash</p>
                <p class="text-xl font-black">{{ format_taka($session->opening_cash) }}</p>
                <p class="text-xs text-gray-500 mt-1">Opened by {{ $session->opener->name ?? '—' }}</p>
            </div>
            <div class="bg-white rounded-xl border p-4">
                <p class="text-[11px] font-bold uppercase text-gray-400">Closing cash</p>
                <p class="text-xl font-black">{{ $session->closing_cash !== null ? format_taka($session->closing_cash) : '—' }}</p>
                <p class="text-xs text-gray-500 mt-1">Closed by {{ $session->closer->name ?? '—' }}</p>
            </div>
            <div class="bg-white rounded-xl border p-4">
                <p class="text-[11px] font-bold uppercase text-gray-400">Total sales</p>
                <p class="text-xl font-black">{{ format_taka($session->total_sales) }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ $session->order_count }} completed orders</p>
            </div>
            <div class="bg-white rounded-xl border p-4">
                <p class="text-[11px] font-bold uppercase text-gray-400">Variance</p>
                <p class="text-xl font-black {{ ($session->variance ?? 0) < 0 ? 'text-red-600' : (($session->variance ?? 0) > 0 ? 'text-emerald-600' : '') }}">
                    {{ $session->variance !== null ? format_taka($session->variance) : '—' }}
                </p>
                <p class="text-xs text-gray-500 mt-1">Counted − expected ({{ format_taka($session->expected_cash ?? 0) }})</p>
            </div>
        </div>

        <div class="bg-white rounded-xl border p-4 text-sm space-y-2">
            <div class="flex justify-between"><span class="text-gray-500">Cash sales</span><span class="font-semibold">{{ format_taka($stats['cash_sales'] ?? $session->cash_sales) }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Card sales</span><span class="font-semibold">{{ format_taka($stats['card_sales'] ?? $session->card_sales) }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Mobile / bKash</span><span class="font-semibold">{{ format_taka($stats['mobile_sales'] ?? $session->mobile_sales) }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Transfers in</span><span class="font-semibold text-emerald-700">{{ format_taka($stats['transfers_in'] ?? 0) }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Transfers out</span><span class="font-semibold text-amber-700">{{ format_taka($stats['transfers_out'] ?? 0) }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Cash purchases</span><span class="font-semibold text-red-600">{{ format_taka($stats['cash_purchases'] ?? 0) }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">Cash refunds</span><span class="font-semibold text-red-600">{{ format_taka($stats['cash_refunds'] ?? $session->cash_refunds) }}</span></div>
            @if($session->notes)
                <div class="pt-3 border-t text-xs text-gray-600 whitespace-pre-line">{{ $session->notes }}</div>
            @endif
        </div>

        @include('counters.partials.transfer-log', ['transferLog' => $transferLog ?? []])

        @if($session->status === 'open')
            <div class="mt-5 flex flex-wrap gap-2">
                <a href="{{ route('counters.sessions.close-form', $session) }}" class="inline-flex bg-slate-900 text-white text-sm font-bold px-4 py-2 rounded-lg">Close session</a>
                <a href="{{ route('counters.sessions.index') }}" class="inline-flex border border-indigo-200 text-indigo-700 text-sm font-bold px-4 py-2 rounded-lg hover:bg-indigo-50">Transfer cash</a>
            </div>
        @endif
    </div>
</x-app-layout>
