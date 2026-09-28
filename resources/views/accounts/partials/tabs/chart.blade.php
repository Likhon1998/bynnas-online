@php
    use App\Support\AccountUi;
@endphp

<script>
    function accountsChartData(initialRows) {
        return {
            rows: initialRows,
            search: '',
            typeFilter: '',
            groupFilter: '',
            statusFilter: '',
            page: 1,
            perPage: 8,
            get groups() {
                return [...new Set(this.rows.map(r => r.group))].sort();
            },
            get filtered() {
                return this.rows.filter(row => {
                    const q = this.search.trim().toLowerCase();
                    const matchesSearch = !q
                        || row.code.toLowerCase().includes(q)
                        || row.name.toLowerCase().includes(q)
                        || row.group.toLowerCase().includes(q);
                    const matchesType = !this.typeFilter || row.type === this.typeFilter;
                    const matchesGroup = !this.groupFilter || row.group === this.groupFilter;
                    const matchesStatus = !this.statusFilter
                        || (this.statusFilter === 'active' && row.active)
                        || (this.statusFilter === 'inactive' && !row.active);
                    return matchesSearch && matchesType && matchesGroup && matchesStatus;
                });
            },
            get totalPages() {
                return Math.max(1, Math.ceil(this.filtered.length / this.perPage));
            },
            get paginated() {
                const start = (this.page - 1) * this.perPage;
                return this.filtered.slice(start, start + this.perPage);
            },
            get rangeStart() {
                return this.filtered.length ? (this.page - 1) * this.perPage + 1 : 0;
            },
            get rangeEnd() {
                return Math.min(this.page * this.perPage, this.filtered.length);
            },
            balanceClass(type, balance) {
                if (balance === 0) return 'text-gray-900';
                return ['asset', 'equity', 'income'].includes(type) ? 'text-emerald-600' : 'text-rose-600';
            },
            formatBalance(value) {
                return '৳' + Number(value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            },
            goToPage(page) {
                this.page = Math.min(Math.max(1, page), this.totalPages);
            },
            exportCsv() {
                const escape = (value) => '"' + String(value).replace(/"/g, '""') + '"';
                const headers = ['Account Code', 'Account Name', 'Account Type', 'Group', 'Balance', 'Status'];
                const lines = this.filtered.map(row => [
                    row.code,
                    row.name,
                    row.type_label,
                    row.group,
                    Math.round(Number(row.balance) || 0).toLocaleString(),
                    row.active ? 'Active' : 'Inactive',
                ]);
                const csv = [headers, ...lines].map(cols => cols.map(escape).join(',')).join('\n');
                const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = 'chart-of-accounts.csv';
                link.click();
                URL.revokeObjectURL(link.href);
            },
            resetFilters() {
                this.search = '';
                this.typeFilter = '';
                this.groupFilter = '';
                this.statusFilter = '';
                this.page = 1;
            },
            init() {
                this.$watch('search', () => { this.page = 1; });
                this.$watch('typeFilter', () => { this.page = 1; });
                this.$watch('groupFilter', () => { this.page = 1; });
                this.$watch('statusFilter', () => { this.page = 1; });
                this.$watch('filtered', () => {
                    if (this.page > this.totalPages) {
                        this.page = this.totalPages;
                    }
                });
            },
        };
    }
</script>

<div x-data="accountsChartData(@js($chartRows))">
    <x-accounts.panel title="Chart of Accounts" subtitle="All your accounts in one place.">
        <x-slot:actions>
            <button
                type="button"
                @click="exportCsv()"
                class="inline-flex items-center gap-2 border border-gray-200 bg-white text-gray-700 text-xs font-bold px-3 py-2 rounded-lg hover:bg-gray-50 transition"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Export
            </button>
        </x-slot:actions>

        <div class="px-6 py-4 border-b border-gray-100 bg-slate-50/60">
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative flex-1 min-w-[200px]">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input
                        type="search"
                        x-model="search"
                        placeholder="Search accounts..."
                        class="w-full pl-9 pr-3 py-2 border-gray-200 rounded-lg text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                </div>
                <select x-model="typeFilter" class="border-gray-200 rounded-lg text-sm px-3 py-2 min-w-[150px]">
                    <option value="">All Account Types</option>
                    <option value="asset">Asset</option>
                    <option value="liability">Liability</option>
                    <option value="equity">Equity</option>
                    <option value="income">Revenue</option>
                    <option value="expense">Expense</option>
                </select>
                <select x-model="groupFilter" class="border-gray-200 rounded-lg text-sm px-3 py-2 min-w-[150px]">
                    <option value="">All Groups</option>
                    <template x-for="group in groups" :key="group">
                        <option :value="group" x-text="group"></option>
                    </template>
                </select>
                <select x-model="statusFilter" class="border-gray-200 rounded-lg text-sm px-3 py-2 min-w-[130px]">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-[11px] uppercase font-bold text-gray-500 tracking-wider">
                    <tr>
                        <th class="px-6 py-3 text-left">Account Code</th>
                        <th class="px-6 py-3 text-left">Account Name</th>
                        <th class="px-6 py-3 text-left">Account Type</th>
                        <th class="px-6 py-3 text-left">Group</th>
                        <th class="px-6 py-3 text-right">Balance</th>
                        <th class="px-6 py-3 text-center">Status</th>
                        <th class="px-6 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    <template x-if="paginated.length === 0">
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-400">
                                <p class="font-medium text-gray-500">No accounts match your filters.</p>
                                <button type="button" @click="resetFilters()" class="mt-2 text-xs font-bold text-indigo-600 hover:text-indigo-800">Clear filters</button>
                            </td>
                        </tr>
                    </template>
                    <template x-for="row in paginated" :key="row.id">
                        <tr class="hover:bg-gray-50/80 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-700" x-text="row.code"></td>
                            <td class="px-6 py-4 whitespace-nowrap font-semibold text-gray-900" x-text="row.name"></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-bold ring-1 ring-inset"
                                    :class="{
                                        'bg-blue-50 text-blue-700 ring-blue-100': row.type === 'asset',
                                        'bg-violet-50 text-violet-700 ring-violet-100': row.type === 'liability',
                                        'bg-emerald-50 text-emerald-700 ring-emerald-100': row.type === 'equity',
                                        'bg-amber-50 text-amber-700 ring-amber-100': row.type === 'income',
                                        'bg-rose-50 text-rose-700 ring-rose-100': row.type === 'expense',
                                    }"
                                    x-text="row.type_label"
                                ></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-gray-600" x-text="row.group"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-right font-bold" :class="balanceClass(row.type, row.balance)" x-text="formatBalance(row.balance)"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-bold ring-1 ring-inset"
                                    :class="row.active ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : 'bg-gray-50 text-gray-600 ring-gray-100'"
                                    x-text="row.active ? 'Active' : 'Inactive'"
                                ></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <div class="inline-flex items-center gap-1">
                                    <button
                                        type="button"
                                        class="p-1.5 rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 transition"
                                        :class="row.is_system ? 'opacity-40 cursor-not-allowed' : ''"
                                        :disabled="row.is_system"
                                        title="Edit account"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    <button type="button" class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition" title="More options">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10 6a2 2 0 110-4 2 2 0 010 4zm0 4a2 2 0 110-4 2 2 0 010 4zm0 4a2 2 0 110-4 2 2 0 010 4z"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-gray-100 flex flex-wrap items-center justify-between gap-3 bg-white">
            <p class="text-xs text-gray-500">
                Showing <span class="font-bold text-gray-700" x-text="rangeStart"></span>
                to <span class="font-bold text-gray-700" x-text="rangeEnd"></span>
                of <span class="font-bold text-gray-700" x-text="filtered.length"></span> entries
            </p>
            <div class="inline-flex items-center gap-1">
                <button type="button" @click="goToPage(1)" :disabled="page === 1" class="p-2 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
                </button>
                <button type="button" @click="goToPage(page - 1)" :disabled="page === 1" class="p-2 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <template x-for="p in totalPages" :key="p">
                    <button
                        type="button"
                        @click="goToPage(p)"
                        class="min-w-[2rem] h-8 px-2 rounded-lg text-xs font-bold transition"
                        :class="page === p ? 'bg-indigo-600 text-white' : 'border border-gray-200 text-gray-600 hover:bg-gray-50'"
                        x-text="p"
                        x-show="totalPages <= 7 || p === 1 || p === totalPages || Math.abs(p - page) <= 1"
                    ></button>
                </template>
                <button type="button" @click="goToPage(page + 1)" :disabled="page === totalPages" class="p-2 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
                <button type="button" @click="goToPage(totalPages)" :disabled="page === totalPages" class="p-2 rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"/></svg>
                </button>
            </div>
        </div>
    </x-accounts.panel>
</div>
