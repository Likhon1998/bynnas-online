@php
    $tabs = [
        'opening-balance' => ['route' => 'accounts.opening-balance', 'label' => 'Opening Balance'],
        'chart' => ['route' => 'accounts.chart', 'label' => 'Chart of Accounts'],
        'ledger' => ['route' => 'accounts.ledger', 'label' => 'Ledger'],
        'cash-book' => ['route' => 'accounts.cash-book', 'label' => 'Cash Book'],
        'daily-summary' => ['route' => 'accounts.daily-summary', 'label' => 'Daily Summary'],
        'petty-cash' => ['route' => 'accounts.petty-cash', 'label' => 'Petty Cash'],
        'transfer' => ['route' => 'accounts.transfer', 'label' => 'Account Transfer'],
    ];

    $tabParams = function (string $key): array {
        return match ($key) {
            'ledger' => request()->only(['start_date', 'end_date', 'all_time', 'account_id']),
            'cash-book' => request()->only(['start_date', 'end_date', 'all_time', 'account_id', 'counter_id']),
            'daily-summary' => request()->only(['date', 'counter_id']),
            default => [],
        };
    };

    $currentTab = $activeTab ?? 'opening-balance';
@endphp

<div class="flex flex-wrap gap-2">
    @foreach($tabs as $key => $tab)
        <button
            type="button"
            @click="setTab(@js($key), @js(route($tab['route'], $tabParams($key))))"
            :class="tab === @js($key)
                ? 'bg-indigo-600 text-white shadow-md border-indigo-600'
                : 'bg-white border border-gray-200 text-gray-600 hover:border-indigo-300 hover:text-indigo-600'"
            class="px-4 py-2 rounded-xl text-xs font-bold transition-all border"
        >
            {{ $tab['label'] }}
        </button>
    @endforeach
</div>

@if(isset($start) && isset($end))
    <div x-show="['ledger', 'cash-book'].includes(tab)" x-cloak class="mt-4">
        <form action="{{ route('accounts.ledger') }}" method="GET" class="flex flex-wrap items-end gap-2 bg-white border border-gray-100 rounded-xl p-4 shadow-sm" x-show="tab === 'ledger'" x-cloak>
            @if(request('account_id'))
                <input type="hidden" name="account_id" value="{{ request('account_id') }}">
            @endif
            <div>
                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">From</label>
                <input type="date" name="start_date" value="{{ request('start_date', $start->format('Y-m-d')) }}" class="border-gray-200 rounded-lg text-sm px-3 py-2">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">To</label>
                <input type="date" name="end_date" value="{{ request('end_date', $end->format('Y-m-d')) }}" class="border-gray-200 rounded-lg text-sm px-3 py-2">
            </div>
            <button type="submit" class="bg-slate-900 text-white text-xs font-bold px-4 py-2.5 rounded-lg hover:bg-indigo-600">Apply</button>
            <a href="{{ route('accounts.ledger', array_merge(request()->only(['account_id']), ['all_time' => 1])) }}" class="bg-gray-100 text-gray-700 text-xs font-bold px-4 py-2.5 rounded-lg hover:bg-gray-200">All Time</a>
        </form>

        <form action="{{ route('accounts.cash-book') }}" method="GET" class="flex flex-wrap items-end gap-2 bg-white border border-gray-100 rounded-xl p-4 shadow-sm" x-show="tab === 'cash-book'" x-cloak>
            @if(request('account_id'))
                <input type="hidden" name="account_id" value="{{ request('account_id') }}">
            @endif
            @if(request('counter_id'))
                <input type="hidden" name="counter_id" value="{{ request('counter_id') }}">
            @endif
            <div>
                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">From</label>
                <input type="date" name="start_date" value="{{ request('start_date', $start->format('Y-m-d')) }}" class="border-gray-200 rounded-lg text-sm px-3 py-2">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">To</label>
                <input type="date" name="end_date" value="{{ request('end_date', $end->format('Y-m-d')) }}" class="border-gray-200 rounded-lg text-sm px-3 py-2">
            </div>
            <button type="submit" class="bg-slate-900 text-white text-xs font-bold px-4 py-2.5 rounded-lg hover:bg-indigo-600">Apply</button>
            <a href="{{ route('accounts.cash-book', array_merge(request()->only(['account_id', 'counter_id']), ['all_time' => 1])) }}" class="bg-gray-100 text-gray-700 text-xs font-bold px-4 py-2.5 rounded-lg hover:bg-gray-200">All Time</a>
        </form>
    </div>
@endif
