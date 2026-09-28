@php
    $presets = [
        ['key' => 'today', 'label' => 'Today', 'start' => now()->toDateString(), 'end' => now()->toDateString()],
        ['key' => 'week', 'label' => 'This week', 'start' => now()->copy()->startOfWeek()->toDateString(), 'end' => now()->toDateString()],
        ['key' => 'month', 'label' => 'This month', 'start' => now()->copy()->startOfMonth()->toDateString(), 'end' => now()->toDateString()],
        ['key' => '30d', 'label' => 'Last 30 days', 'start' => now()->copy()->subDays(29)->toDateString(), 'end' => now()->toDateString()],
    ];
    $activePreset = request('all_time') ? 'all' : null;
    if (! $activePreset) {
        foreach ($presets as $p) {
            if ($startDate->toDateString() === $p['start'] && $endDate->toDateString() === $p['end']) {
                $activePreset = $p['key'];
                break;
            }
        }
    }
@endphp

<x-app-layout>
    <div class="pb-10 min-w-0 space-y-5"
         x-data="{
            tab: @js($view ?: 'person'),
            startDate: @js($startDate->format('Y-m-d')),
            endDate: @js($endDate->format('Y-m-d')),
            allTime: @js((bool) request('all_time')),
            staffId: @js($selectedStaffId ? (string) $selectedStaffId : ''),
            preset: @js($activePreset),
            baseUrl: @js(route('reports.staff_performance')),
            busy: false,
            setTab(name) {
                this.tab = name;
                this.syncUrl();
            },
            buildUrl(extra = {}) {
                const url = new URL(this.baseUrl, window.location.origin);
                url.searchParams.set('view', this.tab);
                if (extra.allTime ?? this.allTime) {
                    url.searchParams.set('all_time', '1');
                } else {
                    url.searchParams.set('start_date', extra.startDate ?? this.startDate);
                    url.searchParams.set('end_date', extra.endDate ?? this.endDate);
                }
                const staff = extra.staffId !== undefined ? extra.staffId : this.staffId;
                if (staff) url.searchParams.set('staff_id', staff);
                if (extra.page) url.searchParams.set('page', extra.page);
                return url;
            },
            syncUrl(url) {
                try {
                    window.history.replaceState({}, '', url || this.buildUrl());
                } catch (e) {}
            },
            applyFetched(html) {
                const doc = new DOMParser().parseFromString(html, 'text/html');
                [['summary', 'lb-summary'], ['person', 'lb-person'], ['counter', 'lb-counter'], ['log', 'lb-log']].forEach(([ref, id]) => {
                    const next = doc.getElementById(id);
                    const cur = this.$refs[ref];
                    if (next && cur) cur.innerHTML = next.outerHTML;
                });
            },
            async refresh(extra = {}) {
                if (this.busy) return;
                this.busy = true;
                const url = this.buildUrl(extra);
                try {
                    const res = await fetch(url.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html',
                        },
                    });
                    this.applyFetched(await res.text());
                    this.syncUrl(url);
                } catch (e) {
                    window.location = url.toString();
                } finally {
                    this.busy = false;
                }
            },
            applyPreset(key, start, end, allTime = false) {
                this.preset = key;
                this.allTime = !!allTime;
                if (!allTime) {
                    this.startDate = start;
                    this.endDate = end;
                }
                this.refresh({ startDate: start, endDate: end, allTime });
            },
            applyDates() {
                this.allTime = false;
                this.preset = null;
                this.refresh();
            },
            resetDates() {
                this.applyPreset('month', @js(now()->copy()->startOfMonth()->toDateString()), @js(now()->toDateString()), false);
            },
            onStaffChange(e) {
                if (!e.target.matches('[data-staff-filter]')) return;
                this.staffId = e.target.value || '';
                this.refresh({ staffId: this.staffId });
            },
            onLogClick(e) {
                const a = e.target.closest('a');
                if (!a || !a.closest('[data-log-pagination]')) return;
                e.preventDefault();
                try {
                    const page = new URL(a.href).searchParams.get('page');
                    this.refresh({ page });
                } catch (err) {}
            },
         }">
        {{-- Header --}}
        <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-4">
            <div class="min-w-0">
                <p class="text-[11px] font-bold uppercase tracking-wider text-blue-600 mb-1">Reports · Recognition</p>
                <h1 class="text-2xl sm:text-[26px] font-extrabold tracking-tight text-slate-900">Sales Leaderboard</h1>
                <p class="mt-1 text-sm text-slate-500">Track who and which counter sold the most — praise your top performers.</p>
            </div>
            <div class="flex flex-wrap gap-2 text-xs">
                <a href="{{ route('reports.daily') }}" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-2 font-bold text-slate-600 hover:bg-slate-50">Daily Sales</a>
                <a href="{{ route('analytics.overview') }}" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-3 py-2 font-bold text-slate-600 hover:bg-slate-50">Analytics</a>
            </div>
        </div>

        {{-- Filters --}}
        <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-sm space-y-4">
            <div class="flex flex-wrap gap-2">
                @foreach($presets as $p)
                    <button type="button"
                            @click="applyPreset(@js($p['key']), @js($p['start']), @js($p['end']), false)"
                            :class="preset === @js($p['key']) ? 'bg-blue-600 text-white border-blue-600' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'"
                            class="inline-flex rounded-full px-3 py-1.5 text-[11px] font-bold border transition-colors">
                        {{ $p['label'] }}
                    </button>
                @endforeach
                <button type="button"
                        @click="applyPreset('all', null, null, true)"
                        :class="preset === 'all' ? 'bg-blue-600 text-white border-blue-600' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'"
                        class="inline-flex rounded-full px-3 py-1.5 text-[11px] font-bold border transition-colors">
                    All time
                </button>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">From</label>
                    <input type="date" x-model="startDate" class="w-full rounded-xl border-slate-200 text-sm">
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1">To</label>
                    <input type="date" x-model="endDate" class="w-full rounded-xl border-slate-200 text-sm">
                </div>
                <div class="sm:col-span-2 flex items-end gap-2">
                    <button type="button" @click="applyDates()"
                            class="inline-flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold px-5 py-2.5 rounded-xl">
                        Apply
                    </button>
                    <button type="button" @click="resetDates()"
                            class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-bold text-slate-600 hover:bg-slate-50">
                        Reset
                    </button>
                </div>
            </div>
        </div>

        <div class="space-y-5 transition-opacity duration-150" :class="busy ? 'opacity-50 pointer-events-none' : ''">
            <div x-ref="summary">
                @include('reports.partials.staff-performance-summary')
            </div>

            {{-- Instant tabs --}}
            <div class="flex flex-wrap gap-2 border-b border-slate-200 pb-px">
                <button type="button" @click="setTab('person')"
                        :class="tab === 'person' ? 'bg-white text-blue-700 border-slate-200' : 'text-slate-500 border-transparent hover:text-slate-800'"
                        class="px-4 py-2.5 text-sm font-bold rounded-t-xl border border-b-0 transition-colors">
                    Person-wise
                </button>
                <button type="button" @click="setTab('counter')"
                        :class="tab === 'counter' ? 'bg-white text-blue-700 border-slate-200' : 'text-slate-500 border-transparent hover:text-slate-800'"
                        class="px-4 py-2.5 text-sm font-bold rounded-t-xl border border-b-0 transition-colors">
                    Counter-wise
                </button>
                <button type="button" @click="setTab('log')"
                        :class="tab === 'log' ? 'bg-white text-blue-700 border-slate-200' : 'text-slate-500 border-transparent hover:text-slate-800'"
                        class="px-4 py-2.5 text-sm font-bold rounded-t-xl border border-b-0 transition-colors">
                    Daily activity log
                </button>
            </div>

            <div x-show="tab === 'person'" x-cloak>
                <div x-ref="person">
                    @include('reports.partials.staff-performance-person')
                </div>
            </div>
            <div x-show="tab === 'counter'" x-cloak>
                <div x-ref="counter">
                    @include('reports.partials.staff-performance-counter')
                </div>
            </div>
            <div x-show="tab === 'log'" x-cloak
                 @change="onStaffChange($event)"
                 @click="onLogClick($event)">
                <div x-ref="log">
                    @include('reports.partials.staff-performance-log')
                </div>
            </div>
        </div>
    </div>

    {{-- Details modal --}}
    <div id="detailsModal" class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 opacity-0 transition-opacity">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] flex flex-col overflow-hidden scale-95 transition-transform" id="modalContent">
            <div class="p-5 border-b border-slate-100 flex justify-between items-start bg-slate-900 text-white">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-wider text-blue-300 mb-1">Order details</p>
                    <h3 class="text-lg font-extrabold" id="modalStaffName">Loading…</h3>
                    <p class="text-xs text-slate-400 mt-1"><span id="modalDate"></span> · <span id="modalCounter"></span></p>
                </div>
                <button type="button" onclick="closeDetailsModal()" class="h-9 w-9 rounded-full bg-white/10 hover:bg-rose-500 flex items-center justify-center">×</button>
            </div>
            <div class="grid grid-cols-2 gap-3 p-4 bg-slate-50 border-b border-slate-100">
                <div class="bg-white rounded-xl border border-slate-100 p-3 text-center">
                    <p class="text-[10px] font-bold uppercase text-slate-400">Orders</p>
                    <p class="text-xl font-black" id="modalTotalOrders">-</p>
                </div>
                <div class="bg-white rounded-xl border border-slate-100 p-3 text-center">
                    <p class="text-[10px] font-bold uppercase text-emerald-500">Revenue</p>
                    <p class="text-xl font-black text-emerald-600" id="modalTotalRevenue">-</p>
                </div>
            </div>
            <div class="overflow-y-auto flex-1 p-4">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-[10px] uppercase tracking-wider text-slate-400 border-b">
                            <th class="pb-2 text-left font-bold">Time</th>
                            <th class="pb-2 text-left font-bold">Invoice</th>
                            <th class="pb-2 text-left font-bold">Customer</th>
                            <th class="pb-2 text-right font-bold">Amount</th>
                        </tr>
                    </thead>
                    <tbody id="modalTableBody" class="divide-y divide-slate-50"></tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        const modal = document.getElementById('detailsModal');
        const modalContent = document.getElementById('modalContent');
        const tbody = document.getElementById('modalTableBody');

        function openDetailsModal(staffId, date, counterId) {
            modal.classList.remove('hidden');
            setTimeout(() => { modal.classList.remove('opacity-0'); modalContent.classList.remove('scale-95'); }, 10);
            tbody.innerHTML = '<tr><td colspan="4" class="py-10 text-center text-slate-400 font-bold">Loading…</td></tr>';
            fetch(`{{ route('reports.staff_daily_details') }}?staff_id=${staffId}&date=${date}&counter_id=${counterId}`)
                .then(r => r.json())
                .then(data => {
                    document.getElementById('modalStaffName').textContent = data.staff_name;
                    document.getElementById('modalDate').textContent = data.date_formatted;
                    document.getElementById('modalCounter').textContent = data.counter_name;
                    document.getElementById('modalTotalOrders').textContent = data.total_orders;
                    document.getElementById('modalTotalRevenue').textContent = '৳' + data.total_revenue;
                    tbody.innerHTML = '';
                    if (!data.orders.length) {
                        tbody.innerHTML = '<tr><td colspan="4" class="py-10 text-center text-slate-400">No transactions.</td></tr>';
                        return;
                    }
                    data.orders.forEach(order => {
                        tbody.innerHTML += `<tr>
                            <td class="py-3 font-bold text-slate-800">${order.time}</td>
                            <td class="py-3 font-bold text-blue-600">${order.invoice || ('#' + order.id)}</td>
                            <td class="py-3 text-slate-600">${order.customer}</td>
                            <td class="py-3 text-right font-extrabold text-emerald-600">৳${order.amount}</td>
                        </tr>`;
                    });
                })
                .catch(() => { tbody.innerHTML = '<tr><td colspan="4" class="py-10 text-center text-rose-500">Failed to load.</td></tr>'; });
        }

        function closeDetailsModal() {
            modal.classList.add('opacity-0');
            modalContent.classList.add('scale-95');
            setTimeout(() => modal.classList.add('hidden'), 250);
        }
        window.onclick = (e) => { if (e.target === modal) closeDetailsModal(); };
    </script>
</x-app-layout>
