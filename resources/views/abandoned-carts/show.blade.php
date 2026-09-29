<x-app-layout>
@php
    $items = collect($cart->items ?? []);
    $phone = $cart->phone ?: $cart->customer?->phone;
    $closed = in_array($cart->status, \App\Models\AbandonedCart::CLOSED_STATUSES, true);
    $waDigits = $phone ? preg_replace('/\D+/', '', \App\Models\Customer::normalizePhone($phone)) : null;
    if ($waDigits && str_starts_with($waDigits, '01')) { $waDigits = '88'.$waDigits; }
@endphp

<div class="mx-auto max-w-4xl space-y-4 text-[12px] text-slate-700">
    <div>
        <a href="{{ route('abandoned-carts.index') }}" class="text-[12px] font-semibold text-indigo-600 hover:text-indigo-700">← Back to abandoned carts</a>
        <div class="mt-1.5 flex flex-wrap items-center gap-2.5">
            <h1 class="text-xl font-extrabold tracking-tight text-slate-900">{{ $cart->name ?: ($cart->customer?->name ?: 'Anonymous shopper') }}</h1>
            <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-0.5 text-[11px] font-bold text-slate-700">{{ $cart->displayStatus() }}</span>
        </div>
        <p class="mt-0.5 text-[12px] text-slate-500">Started {{ $cart->created_at->format('d M Y, h:i A') }} · last activity {{ $cart->last_activity_at?->diffForHumans() }}</p>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-[13px] font-medium text-emerald-700">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-[13px] font-medium text-rose-700">{{ session('error') }}</div>
    @endif

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="space-y-4 md:col-span-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="mb-3 text-[14px] font-bold text-slate-900">Cart</h2>
                <table class="min-w-full">
                    <tbody class="divide-y divide-slate-100">
                        @foreach($items as $item)
                            <tr>
                                <td class="py-2">{{ $item['name'] ?? 'Item' }}</td>
                                <td class="py-2 text-center text-slate-500">× {{ $item['qty'] ?? 1 }}</td>
                                <td class="py-2 text-right font-semibold">Tk {{ format_taka_number((float) ($item['price'] ?? 0) * (int) ($item['qty'] ?? 1)) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t border-slate-200">
                            <td class="pt-2 font-bold text-slate-900" colspan="2">Subtotal (at last visit)</td>
                            <td class="pt-2 text-right font-bold text-slate-900">Tk {{ format_taka_number((float) $cart->subtotal) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if($cart->notes)
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <h2 class="mb-2 text-[14px] font-bold text-slate-900">Follow-up notes</h2>
                    <p class="whitespace-pre-line text-[12px] text-slate-600">{{ $cart->notes }}</p>
                </div>
            @endif
        </div>

        <div class="space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="mb-2 text-[14px] font-bold text-slate-900">Shopper</h2>
                <p class="text-[13px] font-semibold">{{ $phone ?: 'No phone captured' }}</p>
                @if($cart->user?->email)<p class="text-[11px] text-slate-500">{{ $cart->user->email }}</p>@endif
                @if($cart->customer)<p class="mt-1 text-[11px] text-slate-500">Customer record: {{ $cart->customer->name }}</p>@endif
                <p class="mt-2 text-[11px] text-slate-500">Source: {{ $cart->campaign?->name ?? ($cart->utm_source ? ucfirst($cart->utm_source).($cart->utm_medium ? ' / '.$cart->utm_medium : '') : 'Direct') }}</p>
                @if($cart->contacted_at)
                    <p class="mt-1 text-[11px] text-slate-500">Contacted {{ $cart->contacted_at->format('d M, h:i A') }}{{ $cart->contactedBy ? ' by '.$cart->contactedBy->name : '' }}</p>
                @endif
                @if($cart->order)
                    <a href="{{ route('online-orders.show', $cart->order) }}" class="mt-2 inline-block font-semibold text-emerald-700 hover:underline">Order {{ $cart->order->invoice_no }}</a>
                @endif
                @if($phone)
                    <div class="mt-3 flex gap-2">
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="flex-1 rounded-lg border border-slate-200 px-2 py-1.5 text-center text-[12px] font-bold text-slate-700 hover:bg-slate-50">Call</a>
                        @if($waDigits)
                            <a href="https://wa.me/{{ $waDigits }}" target="_blank" rel="noopener noreferrer" class="flex-1 rounded-lg border border-emerald-200 bg-emerald-50 px-2 py-1.5 text-center text-[12px] font-bold text-emerald-700 hover:bg-emerald-100">WhatsApp</a>
                        @endif
                    </div>
                @endif
            </div>

            @unless($closed)
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <h2 class="mb-2 text-[14px] font-bold text-slate-900">Recovery</h2>
                    <form method="POST" action="{{ route('abandoned-carts.status', $cart) }}" class="space-y-2">
                        @csrf
                        <textarea name="notes" rows="2" maxlength="2000" class="block w-full rounded-lg border-slate-200 text-[12px]" placeholder="What happened? (optional)"></textarea>
                        <div class="grid grid-cols-2 gap-2">
                            <button name="status" value="contacted" class="rounded-lg bg-indigo-600 px-2 py-2 text-[12px] font-bold text-white hover:bg-indigo-700">Mark contacted</button>
                            <button name="status" value="lost" class="rounded-lg border border-slate-200 bg-white px-2 py-2 text-[12px] font-bold text-slate-600 hover:bg-slate-50">Mark lost</button>
                        </div>
                    </form>
                    @if($phone)
                        <form method="POST" action="{{ route('abandoned-carts.lead', $cart) }}" class="mt-2">
                            @csrf
                            <button class="w-full rounded-lg border border-violet-200 bg-violet-50 px-2 py-2 text-[12px] font-bold text-violet-700 hover:bg-violet-100">
                                {{ $openLead ? 'Open existing lead' : 'Create lead for follow-up' }}
                            </button>
                        </form>
                    @endif
                    <p class="mt-2 text-[10px] text-slate-400">The cart is marked recovered automatically when this shopper places an order.</p>
                </div>
            @endunless
        </div>
    </div>
</div>
</x-app-layout>
