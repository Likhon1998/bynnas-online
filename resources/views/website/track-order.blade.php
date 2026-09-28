@extends('website.layout')

@section('title', 'Track Order — '.($settings->store_name ?? config('app.name', 'Maks Gadget')))

@section('content')
<div class="max-w-3xl mx-auto px-4 py-12">
    <div class="text-center mb-8">
        <p class="text-xs font-bold tracking-[0.2em] uppercase text-blue-600">Order tracking</p>
        <h1 class="mt-2 text-3xl font-extrabold text-slate-900">Track your order</h1>
        <p class="mt-2 text-sm text-slate-500">Enter your Order ID (WEB-…) and the phone number used at checkout.</p>
    </div>

    @if(session('error'))
        <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('website.track.lookup') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
        @csrf
        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Order ID</label>
            <input name="invoice_no" value="{{ old('invoice_no', $invoiceNo ?? '') }}" required
                   class="w-full rounded-xl border-slate-200 text-sm" placeholder="WEB-1-2026-00001">
            @error('invoice_no') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-xs font-semibold text-slate-600 mb-1">Phone number</label>
            <input name="phone" value="{{ old('phone', $phone ?? '') }}" required
                   class="w-full rounded-xl border-slate-200 text-sm" placeholder="01XXXXXXXXX">
            @error('phone') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <button type="submit" class="w-full gaget-btn-primary text-center">Track order</button>
    </form>

    @if(!empty($tracking))
        <div class="mt-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Order</p>
                    <p class="text-lg font-bold text-slate-900">{{ $tracking['invoice'] }}</p>
                    <p class="text-sm text-slate-500">{{ $tracking['status_label'] }} · {{ $tracking['date'] }}</p>
                </div>
                <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">{{ $tracking['status_label'] }}</span>
            </div>
            <p class="text-sm text-slate-600">{{ $tracking['where_is_product'] ?? '' }}</p>
            @if(!empty($tracking['courier']) || !empty($tracking['tracking_number']))
                <p class="text-sm text-slate-500">
                    Courier: {{ $tracking['courier'] ?: '—' }}
                    @if(!empty($tracking['tracking_number']))
                        · Tracking #: {{ $tracking['tracking_number'] }}
                    @endif
                </p>
            @endif

            @if(!empty($tracking['timeline']))
                <ol class="space-y-3 border-t border-slate-100 pt-4">
                    @foreach($tracking['timeline'] as $step)
                        <li class="flex gap-3">
                            <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full {{ !empty($step['done']) ? 'bg-blue-600' : 'bg-slate-300' }}"></span>
                            <div>
                                <p class="text-sm font-semibold text-slate-800">{{ $step['label'] ?? $step['status'] ?? 'Update' }}</p>
                                @if(!empty($step['note']))
                                    <p class="text-xs text-slate-500">{{ $step['note'] }}</p>
                                @endif
                                @if(!empty($step['at']))
                                    <p class="text-[11px] text-slate-400">{{ $step['at'] }}</p>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ol>
            @endif
        </div>
    @endif

    <p class="mt-6 text-center text-sm text-slate-500">
        Have an account?
        <a href="{{ route('website.account') }}" class="font-semibold text-blue-600 hover:text-blue-700">View all orders</a>
    </p>
</div>
@endsection
