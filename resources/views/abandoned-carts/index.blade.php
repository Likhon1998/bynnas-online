<x-app-layout>
@php
    $tabs = ['abandoned' => 'Abandoned', 'contacted' => 'Contacted', 'recovered' => 'Recovered', 'converted' => 'Ordered', 'lost' => 'Lost', 'all' => 'All'];
    $badge = fn ($cart) => match (true) {
        $cart->status === 'recovered' => 'bg-emerald-100 text-emerald-800',
        $cart->status === 'converted' => 'bg-sky-100 text-sky-800',
        $cart->status === 'contacted' => 'bg-indigo-100 text-indigo-800',
        $cart->status === 'lost' => 'bg-slate-200 text-slate-600',
        $cart->isAbandoned() => 'bg-amber-100 text-amber-800',
        default => 'bg-slate-100 text-slate-600',
    };
@endphp

<div class="space-y-4 text-[12px] text-slate-700">
    <div>
        <h1 class="text-xl font-extrabold tracking-tight text-slate-900">Abandoned carts</h1>
        <p class="mt-0.5 text-[12px] text-slate-500">Website carts with no order after {{ $idleMinutes }} minutes of inactivity. Phone numbers come from the shopper's account or the checkout form.</p>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-[13px] font-medium text-emerald-700">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-[13px] font-medium text-rose-700">{{ session('error') }}</div>
    @endif

    <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-3.5 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-amber-700">Abandoned now</p>
            <p class="mt-1 text-2xl font-extrabold text-amber-800">{{ $stats['abandoned'] }}</p>
            <p class="text-[11px] text-amber-700">Not yet contacted</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-3.5 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Value at risk</p>
            <p class="mt-1 text-2xl font-extrabold text-slate-900">Tk {{ format_taka_number($stats['abandoned_value']) }}</p>
            <p class="text-[11px] text-slate-500">Abandoned + contacted carts</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-3.5 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Contacted</p>
            <p class="mt-1 text-2xl font-extrabold text-indigo-700">{{ $stats['contacted'] }}</p>
            <p class="text-[11px] text-slate-500">Waiting for the shopper</p>
        </div>
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-3.5 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-emerald-700">Recovered (30 days)</p>
            <p class="mt-1 text-2xl font-extrabold text-emerald-800">{{ $stats['recovered_30d'] }}</p>
            <p class="text-[11px] text-emerald-700">Tk {{ format_taka_number($stats['recovered_value_30d']) }} in cart value</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center gap-1.5 border-b border-slate-100 px-3 py-2">
            @foreach($tabs as $key => $label)
                <a href="{{ route('abandoned-carts.index', array_filter(array_merge($filters, ['status' => $key]))) }}"
                   class="rounded-md px-2 py-1 text-[11px] font-semibold transition {{ $status === $key ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-100' }}">{{ $label }}</a>
            @endforeach
            <form method="GET" class="ml-auto flex flex-wrap items-center gap-2">
                <input type="hidden" name="status" value="{{ $status }}">
                <select name="contact" class="rounded-lg border-slate-200 py-1 text-[12px]">
                    <option value="">Any contact</option>
                    <option value="with" @selected(($filters['contact'] ?? '') === 'with')>Has phone / customer</option>
                    <option value="without" @selected(($filters['contact'] ?? '') === 'without')>Anonymous</option>
                </select>
                <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Name or phone" class="w-40 rounded-lg border-slate-200 py-1 text-[12px]">
                <button class="rounded-lg bg-slate-800 px-3 py-1 text-[12px] font-semibold text-white">Filter</button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/80 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                        <th class="px-3 py-2">Shopper</th>
                        <th class="px-3 py-2">Items</th>
                        <th class="px-3 py-2 text-right">Value</th>
                        <th class="px-3 py-2 hidden md:table-cell">Source</th>
                        <th class="px-3 py-2">Last activity</th>
                        <th class="px-3 py-2">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($carts as $cart)
                        @php $items = collect($cart->items ?? []); @endphp
                        <tr class="hover:bg-slate-50/80">
                            <td class="px-3 py-2">
                                <a href="{{ route('abandoned-carts.show', $cart) }}" class="text-[13px] font-semibold text-slate-900 hover:text-indigo-700">{{ $cart->name ?: ($cart->customer?->name ?: 'Anonymous shopper') }}</a>
                                <p class="text-[11px] text-slate-500">{{ $cart->phone ?: ($cart->customer?->phone ?: 'No phone') }}</p>
                            </td>
                            <td class="px-3 py-2">
                                <p class="max-w-[240px] truncate" title="{{ $items->map(fn ($i) => ($i['qty'] ?? 1).' × '.($i['name'] ?? ''))->implode(', ') }}">
                                    {{ $items->first()['name'] ?? '—' }}@if($items->count() > 1) <span class="text-slate-400">+{{ $items->count() - 1 }} more</span>@endif
                                </p>
                                <p class="text-[10px] text-slate-400">{{ $cart->item_count }} unit(s)</p>
                            </td>
                            <td class="px-3 py-2 text-right font-semibold text-slate-900">Tk {{ format_taka_number((float) $cart->subtotal) }}</td>
                            <td class="px-3 py-2 hidden md:table-cell">
                                {{ $cart->campaign?->name ?? ($cart->utm_source ? ucfirst($cart->utm_source).($cart->utm_medium ? ' / '.$cart->utm_medium : '') : 'Direct') }}
                            </td>
                            <td class="px-3 py-2">{{ $cart->last_activity_at?->diffForHumans() }}</td>
                            <td class="px-3 py-2">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold {{ $badge($cart) }}">{{ $cart->displayStatus() }}</span>
                                @if($cart->order)<p class="mt-0.5 text-[10px] text-emerald-700">{{ $cart->order->invoice_no }}</p>@endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center">
                            <p class="text-[13px] font-semibold text-slate-700">No carts here</p>
                            <p class="mt-0.5 text-[11px] text-slate-400">Carts appear once shoppers add products on the website.</p>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($carts->hasPages())
            <div class="border-t border-slate-100 px-3 py-2">{{ $carts->links() }}</div>
        @endif
    </div>
</div>
</x-app-layout>
