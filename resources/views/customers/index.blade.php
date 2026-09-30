<x-app-layout>
    @php
        $retail = retail_enabled();
        $segmentLabels = \App\Services\CustomerSegmentService::SEGMENTS;
        $segmentBadges = \App\Services\CustomerSegmentService::BADGES;
        $customerRows = $customers->map(function ($c) use ($segmentMap) {
            return [
                'segments' => $segmentMap[$c->id] ?? [],
                'spent_fmt' => format_taka_number((float) ($c->seg_spent ?? 0)),
                'show' => route('customers.show', $c),
                'id' => $c->id,
                'name' => $c->name,
                'email' => $c->email,
                'phone' => $c->phone,
                'address' => $c->address,
                'points' => (int) ($c->reward_points ?? 0),
                'baki' => (float) ($c->baki_balance ?? 0),
                'baki_fmt' => format_taka_number((float) ($c->baki_balance ?? 0)),
                'baki_url' => route('customers.baki.show', $c),
                'orders' => (int) ($c->seg_orders ?? 0),
                'channel' => $c->user_id ? 'online' : 'offline',
                'joined' => optional($c->created_at)->format('M j, Y'),
                'initials' => strtoupper(mb_substr($c->name ?: 'CU', 0, 2)),
                'edit' => route('customers.edit', $c),
                'destroy' => route('customers.destroy', $c),
            ];
        })->values();
    @endphp

    <div class="w-full min-w-0 pb-4 text-[12px] leading-snug text-slate-700"
         x-data="customerDirectory({
            rows: @js($customerRows),
            onlineCount: {{ (int) $onlineCount }},
            offlineCount: {{ (int) $offlineCount }},
            csrf: @js(csrf_token()),
            segmentLabels: @js($segmentLabels),
            segmentBadges: @js($segmentBadges),
         })">

        @if (session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                 class="mb-2 flex items-center justify-between rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-[12px] font-semibold text-emerald-800">
                <span>{{ session('success') }}</span>
                <button type="button" @click="show = false" class="text-emerald-600 hover:text-emerald-900">&times;</button>
            </div>
        @endif

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            {{-- Compact toolbar --}}
            <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 px-3 py-2">
                <h1 class="text-[15px] font-semibold tracking-tight text-slate-900">Customers</h1>

                <a href="{{ route('customers.create') }}"
                   class="inline-flex items-center gap-1 rounded-md bg-indigo-600 px-2.5 py-1 text-[11px] font-semibold text-white hover:bg-indigo-700">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 4v16m8-8H4"/></svg>
                    Add Customer
                </a>

                <div class="flex flex-wrap items-center gap-x-2.5 gap-y-0.5 text-[11px] text-slate-600">
                    <button type="button" @click="tab = 'all'"
                            class="inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 transition"
                            :class="tab === 'all' ? 'bg-slate-100 font-semibold text-slate-900' : 'hover:bg-slate-50'">
                        <span class="text-slate-400">All</span>
                        <span class="tabular-nums" x-text="rows.length">{{ $customers->count() }}</span>
                    </button>
                    <button type="button" @click="tab = 'online'"
                            class="inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 transition"
                            :class="tab === 'online' ? 'bg-sky-50 font-semibold text-sky-800' : 'hover:bg-slate-50'">
                        <span class="text-sky-500">With account</span>
                        <span class="tabular-nums" x-text="onlineCount">{{ $onlineCount }}</span>
                    </button>
                    <button type="button" @click="tab = 'offline'"
                            class="inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 transition"
                            :class="tab === 'offline' ? 'bg-amber-50 font-semibold text-amber-800' : 'hover:bg-slate-50'">
                        <span class="text-amber-600">{{ $retail ? 'Guest / in-store' : 'Guest' }}</span>
                        <span class="tabular-nums" x-text="offlineCount">{{ $offlineCount }}</span>
                    </button>
                </div>

                <select x-model="segment" class="rounded-lg border-slate-200 bg-slate-50 py-1 text-[12px] text-slate-700" title="Customer segment">
                    <option value="">All segments</option>
                    @foreach($segmentLabels as $key => $label)
                        <option value="{{ $key }}">{{ $label }} ({{ $segmentCounts[$key] ?? 0 }})</option>
                    @endforeach
                </select>

                <div class="relative ml-auto w-full min-w-[180px] sm:w-64 sm:max-w-xs">
                    <svg class="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="search" x-model="q" placeholder="Search name, phone, email…"
                           class="w-full rounded-lg border-slate-200 bg-slate-50 py-1.5 pl-8 pr-2.5 text-[13px] text-slate-800 placeholder:text-slate-400 focus:border-indigo-300 focus:bg-white focus:ring-indigo-200">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/80 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                            <th class="px-3 py-2">Customer</th>
                            <th class="px-3 py-2">Contact</th>
                            <th class="px-3 py-2 hidden md:table-cell">Address</th>
                            <th class="px-3 py-2 text-center">Orders</th>
                            <th class="px-3 py-2 text-center">Points</th>
                            @if($retail)
                            <th class="px-3 py-2 text-right">Baki</th>
                            @endif
                            <th class="px-3 py-2 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="c in filtered" :key="c.id">
                            <tr class="group transition hover:bg-slate-50/80">
                                <td class="px-3 py-2">
                                    <div class="flex items-center gap-2.5">
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-[10px] font-bold"
                                             :class="c.channel === 'online' ? 'bg-sky-100 text-sky-700' : 'bg-amber-100 text-amber-800'"
                                             x-text="c.initials"></div>
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-1.5">
                                                <a :href="c.show" class="truncate text-[13px] font-semibold text-slate-900 hover:text-indigo-700" x-text="c.name"></a>
                                                <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wide"
                                                      :class="c.channel === 'online' ? 'bg-sky-50 text-sky-700' : 'bg-amber-50 text-amber-700'"
                                                      x-text="c.channel === 'online' ? 'Account' : 'Guest'"></span>
                                            </div>
                                            <p class="mt-0.5 text-[10px] text-slate-400">Joined <span x-text="c.joined"></span> · spent Tk <span x-text="c.spent_fmt"></span></p>
                                            <div class="mt-0.5 flex flex-wrap gap-1" x-show="c.segments.length">
                                                <template x-for="s in c.segments" :key="s">
                                                    <span class="inline-flex rounded-full px-1.5 py-0.5 text-[9px] font-bold" :class="segmentBadges[s]" x-text="segmentLabels[s]"></span>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-2">
                                    <p class="text-[13px] font-medium text-slate-800" x-text="c.phone || '—'"></p>
                                    <p class="text-[11px] text-slate-400" x-text="c.email || 'No email'"></p>
                                </td>
                                <td class="px-3 py-2 hidden md:table-cell">
                                    <p class="max-w-[220px] truncate text-[12px] text-slate-600" :title="c.address || ''" x-text="c.address || 'Not provided'"></p>
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <span class="inline-flex min-w-[1.75rem] justify-center rounded bg-slate-100 px-1.5 py-0.5 text-[11px] font-semibold text-slate-700" x-text="c.orders"></span>
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <span class="inline-flex items-center gap-0.5 rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-800">
                                        ★ <span x-text="c.points"></span>
                                    </span>
                                </td>
                                @if($retail)
                                <td class="px-3 py-2 text-right">
                                    <template x-if="c.baki > 0">
                                        <a :href="c.baki_url" class="inline-flex rounded-md bg-orange-50 px-2 py-0.5 text-[11px] font-semibold text-orange-800 hover:bg-orange-100"
                                           x-text="'৳' + c.baki_fmt"></a>
                                    </template>
                                    <template x-if="c.baki <= 0">
                                        <span class="text-[11px] text-slate-300">—</span>
                                    </template>
                                </td>
                                @endif
                                <td class="px-3 py-2 text-right">
                                    <div class="inline-flex items-center gap-1">
                                        <a :href="c.edit"
                                           class="rounded-md border border-indigo-100 bg-indigo-50 px-2 py-1 text-[11px] font-semibold text-indigo-700 hover:bg-indigo-100">Edit</a>
                                        <button type="button"
                                                @click="remove(c)"
                                                class="rounded-md border border-rose-100 bg-rose-50 px-2 py-1 text-[11px] font-semibold text-rose-700 hover:bg-rose-100">Delete</button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div x-show="filtered.length === 0" x-cloak class="px-4 py-10 text-center">
                <p class="text-[13px] font-semibold text-slate-700" x-text="emptyTitle"></p>
                <p class="mt-0.5 text-[11px] text-slate-400" x-text="emptyHint"></p>
            </div>

            <div class="flex items-center justify-between border-t border-slate-100 bg-slate-50/60 px-3 py-1.5 text-[11px] font-medium text-slate-400">
                <span>
                    Showing <span class="text-slate-700" x-text="filtered.length"></span>
                    of <span class="text-slate-700" x-text="rows.length"></span>
                </span>
                <span x-show="tab !== 'all'" x-cloak>
                    <span class="text-slate-700" x-text="tab === 'online' ? 'With account only' : 'Guests only'"></span>
                </span>
            </div>
        </div>
    </div>

    <script>
    function customerDirectory({ rows, onlineCount, offlineCount, csrf, segmentLabels, segmentBadges }) {
        return {
            rows: rows || [],
            onlineCount,
            offlineCount,
            csrf,
            segmentLabels,
            segmentBadges,
            tab: 'all',
            segment: '',
            q: '',
            get filtered() {
                const q = this.q.trim().toLowerCase();
                return this.rows.filter((c) => {
                    if (this.tab === 'online' && c.channel !== 'online') return false;
                    if (this.tab === 'offline' && c.channel !== 'offline') return false;
                    if (this.segment && !c.segments.includes(this.segment)) return false;
                    if (!q) return true;
                    const hay = `${c.name || ''} ${c.phone || ''} ${c.email || ''} ${c.address || ''}`.toLowerCase();
                    return hay.includes(q);
                });
            },
            get emptyTitle() {
                if (this.q.trim()) return 'No matches';
                if (this.tab === 'online') return 'No customers with an account yet';
                if (this.tab === 'offline') return 'No guest customers yet';
                return 'No customers found';
            },
            get emptyHint() {
                if (this.q.trim()) return 'Try another name, phone, or email.';
                if (this.tab === 'online') return 'Customers who register on the website appear here.';
                if (this.tab === 'offline') return 'Shoppers who check out without an account (website or landing pages) appear here.';
                return 'Add a customer or wait for website orders.';
            },
            async remove(c) {
                const ok = await window.adminConfirm({
                    title: 'Delete?',
                    message: `Delete ${c.name}? This cannot be undone.`,
                    confirmText: 'Delete',
                    tone: 'danger',
                });
                if (!ok) return;
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = c.destroy;
                form.innerHTML = `<input type="hidden" name="_token" value="${this.csrf}"><input type="hidden" name="_method" value="DELETE">`;
                document.body.appendChild(form);
                form.submit();
            },
        };
    }
    </script>
</x-app-layout>
