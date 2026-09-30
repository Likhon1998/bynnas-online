@php
    $activeTab = $activeTab ?? 'opening-balance';

    $chartRows = ($chartAccounts ?? collect())->map(function ($row) {
        $account = $row['account'];

        return [
            'id' => $account->id,
            'code' => $account->code,
            'name' => $account->name,
            'type' => $account->type,
            'type_label' => \App\Support\AccountUi::typeLabel($account->type),
            'group' => \App\Support\AccountUi::groupLabel($account),
            'balance' => (float) $row['balance'],
            'active' => (bool) $account->is_active,
            'is_system' => (bool) $account->is_system,
        ];
    })->values();
@endphp

<x-app-layout>
    <div
        class="mx-auto pt-0 pb-8 sm:pb-12 px-0 space-y-4 sm:space-y-6 min-w-0"
        x-data="{
            tab: @js($activeTab),
            showAddModal: false,
            setTab(name, url) {
                this.tab = name;
                if (url && window.history.replaceState) {
                    window.history.replaceState({}, '', url);
                }
            },
            openAddModal() {
                this.showAddModal = true;
                this.tab = 'chart';
            }
        }"
    >
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl font-black text-gray-900 tracking-tight">Accounts</h2>
                <p class="text-sm text-gray-500 mt-1">{{ retail_enabled() ? 'Integrated bookkeeping across all POS counters and online store.' : 'Integrated bookkeeping for online sales, COD, purchases and expenses.' }}</p>
            </div>
            <button
                type="button"
                @click="openAddModal()"
                class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold px-4 py-2.5 rounded-xl shadow-sm transition-all"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                Add New Account
            </button>
        </div>

        @include('accounts.partials.toolbar')

        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm font-medium">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 rounded-xl text-sm font-medium">{{ session('error') }}</div>
        @endif

        <div x-show="tab === 'opening-balance'" x-cloak>
            @include('accounts.partials.tabs.opening-balance')
        </div>
        <div x-show="tab === 'chart'" x-cloak>
            @include('accounts.partials.tabs.chart', ['chartRows' => $chartRows])
        </div>
        <div x-show="tab === 'ledger'" x-cloak>
            @include('accounts.partials.tabs.ledger')
        </div>
        <div x-show="tab === 'cash-book'" x-cloak>
            @include('accounts.partials.tabs.cash-book')
        </div>
        @if(retail_enabled())
            <div x-show="tab === 'daily-summary'" x-cloak>
                @include('accounts.partials.tabs.daily-summary')
            </div>
        @endif
        <div x-show="tab === 'petty-cash'" x-cloak>
            @include('accounts.partials.tabs.petty-cash')
        </div>
        <div x-show="tab === 'transfer'" x-cloak>
            @include('accounts.partials.tabs.transfer')
        </div>

        @include('accounts.partials.add-account-modal')
    </div>
</x-app-layout>
