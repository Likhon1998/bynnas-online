<x-app-layout>
@php
    $user = Auth::user();
    $money = fn ($n) => format_taka($n);
    $statusClass = [
        'completed' => 'bg-emerald-50 text-emerald-700',
        'pending' => 'bg-amber-50 text-amber-700',
        'processing' => 'bg-amber-50 text-amber-700',
        'shipped' => 'bg-sky-50 text-sky-700',
        'cancelled' => 'bg-rose-50 text-rose-700',
        'returned' => 'bg-slate-100 text-slate-600',
        'refunded' => 'bg-slate-100 text-slate-600',
    ];
@endphp

@php
    $bs = $businessSummary ?? [];
    $sessionBadge = match ($bs['session_status'] ?? 'none') {
        'open' => ['Open session', 'bg-emerald-100 text-emerald-800'],
        'stale' => ['Session still open (prev. day)', 'bg-amber-100 text-amber-900'],
        'closed' => ['Closed', 'bg-slate-100 text-slate-600'],
        default => ['No session yet', 'bg-amber-50 text-amber-800'],
    };
    $openingHint = !empty($bs['has_session']) ? 'Declared opening float' : 'Till cash on hand';
    $closingHint = !empty($bs['has_session']) ? 'Expected drawer cash' : 'Till cash on hand';
    $cashInHint = !empty($bs['has_session'])
        ? (!empty($bs['stale_open']) ? 'Cash + collections since open' : 'Cash sales + collections + transfers')
        : 'Cash sales + collections + transfers';
    $cashOutHint = !empty($bs['has_session'])
        ? (!empty($bs['stale_open']) ? 'Refunds + outflows since open' : 'Refunds + transfers + purchases')
        : 'Refunds + transfers + purchases';
@endphp

<div class="space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-[1.35rem] font-bold text-slate-900 tracking-tight">Business Summary</h1>
            <p class="mt-0.5 text-slate-500">
                Welcome back, {{ explode(' ', $user->name)[0] }}!
                Showing
                <span class="font-semibold text-slate-700">{{ $filterLabel ?? ($counter->name ?? 'your shop') }}</span>
                for today.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if(!empty($isAdmin) && isset($counters) && $counters->isNotEmpty())
                <form method="GET" action="{{ route('dashboard') }}" class="inline-flex items-center gap-2">
                    <label for="counter-filter" class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Counter</label>
                    <select
                        id="counter-filter"
                        name="counter"
                        onchange="this.form.submit()"
                        class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm focus:border-indigo-400 focus:ring-indigo-400"
                    >
                        <option value="all" @selected(($selectedCounter ?? 'all') === 'all')>All together</option>
                        @foreach($counters as $c)
                            <option value="{{ $c->id }}" @selected(($selectedCounter ?? '') == (string) $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </form>
            @endif
            @if($retail)
            <span class="inline-flex items-center gap-1.5 rounded-xl px-2.5 py-2 text-[11px] font-semibold {{ $sessionBadge[1] }}">
                {{ $sessionBadge[0] }}
            </span>
            @endif
            <div class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-600 shadow-sm">
                <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                {{ now()->format('M j, Y') }}
            </div>
        </div>
    </div>

    @if($retail && !$user->canAccessPos())
        <div class="flex items-center justify-between rounded-2xl border border-amber-200/80 bg-amber-50 px-4 py-3">
            <div class="flex items-center gap-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100 text-amber-700">!</div>
                <div>
                    <p class="text-xs font-bold text-amber-900">No sales counter assigned</p>
                    <p class="text-xs text-amber-700">Assign a counter before using the POS terminal.</p>
                </div>
            </div>
            @can('manage staff')
                <a href="{{ route('staff.index') }}" class="rounded-lg bg-amber-100 px-3 py-1.5 text-xs font-bold text-amber-800 hover:bg-amber-200">Assign</a>
            @endcan
        </div>
    @endif

    @if($retail && !empty($bs['stale_open']))
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-amber-200/80 bg-amber-50 px-4 py-3">
            <div class="flex items-center gap-3 min-w-0">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700 font-bold">!</div>
                <div class="min-w-0">
                    <p class="text-xs font-bold text-amber-900">Cash session still open from a previous day</p>
                    <p class="text-xs text-amber-700">Close it in Cash Sessions, then open a fresh session for today so drawer figures stay accurate.</p>
                </div>
            </div>
            <a href="{{ route('counters.sessions.index') }}" class="rounded-lg bg-amber-100 px-3 py-1.5 text-xs font-bold text-amber-900 hover:bg-amber-200 shrink-0">Cash Sessions</a>
        </div>
    @endif

    @if(!empty($isAdmin) && ($pendingOnlineOrders ?? 0) > 0)
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-sky-200/80 bg-sky-50 px-4 py-3">
            <div class="flex items-center gap-3 min-w-0">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-sky-100 text-sky-700 font-bold">{{ $pendingOnlineOrders }}</div>
                <div class="min-w-0">
                    <p class="text-xs font-bold text-sky-900">Pending online orders</p>
                    <p class="text-xs text-sky-700">{{ $pendingOnlineOrders }} order{{ $pendingOnlineOrders === 1 ? '' : 's' }} waiting to be processed.</p>
                </div>
            </div>
            <a href="{{ route('online-orders.index') }}" class="rounded-lg bg-sky-100 px-3 py-1.5 text-xs font-bold text-sky-900 hover:bg-sky-200 shrink-0">View orders</a>
        </div>
    @endif

    {{-- All KPIs: 7 + 7 = exactly 2 rows on xl+ --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 {{ $retail ? 'xl:grid-cols-7' : 'xl:grid-cols-6' }} gap-3">
        @if($retail)
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-violet-500 to-indigo-600 p-3.5 text-white shadow-md shadow-indigo-200/60">
            <div class="pointer-events-none absolute -right-4 -top-4 h-20 w-20 rounded-full bg-white/10"></div>
            <div class="relative pr-11">
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-white/80">POS Sales</p>
                    <p class="mt-1.5 text-[13px] sm:text-[15px] font-bold tracking-tight tabular-nums leading-tight break-words">{{ $money($bs['pos_sales'] ?? 0) }}</p>
                    <p class="mt-1 text-[10px] text-white/75">Today (counter)</p>
                </div>
                <div class="absolute right-0 top-0 flex h-9 w-9 items-center justify-center rounded-xl bg-white/15">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
            </div>
        </div>
        @endif

        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-cyan-500 to-blue-700 p-3.5 text-white shadow-md shadow-cyan-200/60">
            <div class="pointer-events-none absolute -right-4 -top-4 h-20 w-20 rounded-full bg-white/10"></div>
            <div class="relative pr-11">
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-white/80">Online Sales</p>
                    <p class="mt-1.5 text-[13px] sm:text-[15px] font-bold tracking-tight tabular-nums leading-tight break-words">{{ $money($bs['online_sales'] ?? 0) }}</p>
                    <p class="mt-1 text-[10px] text-white/75">Today (website)</p>
                </div>
                <div class="absolute right-0 top-0 flex h-9 w-9 items-center justify-center rounded-xl bg-white/15">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                </div>
            </div>
        </div>

        @if($retail)
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-700 p-3.5 text-white shadow-md shadow-emerald-200/60">
            <div class="pointer-events-none absolute -right-4 -top-4 h-20 w-20 rounded-full bg-white/10"></div>
            <div class="relative pr-11">
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-white/80">POS Lifetime</p>
                    <p class="mt-1.5 text-[13px] sm:text-[15px] font-bold tracking-tight tabular-nums leading-tight break-words">{{ $money($bs['pos_lifetime_sales'] ?? 0) }}</p>
                    <p class="mt-1 text-[10px] text-white/75">All-time POS</p>
                </div>
                <div class="absolute right-0 top-0 flex h-9 w-9 items-center justify-center rounded-xl bg-white/15">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
        </div>
        @endif

        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-teal-400 to-emerald-700 p-3.5 text-white shadow-md shadow-teal-200/60">
            <div class="pointer-events-none absolute -right-4 -top-4 h-20 w-20 rounded-full bg-white/10"></div>
            <div class="relative pr-11">
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-white/80">Online Lifetime</p>
                    <p class="mt-1.5 text-[13px] sm:text-[15px] font-bold tracking-tight tabular-nums leading-tight break-words">{{ $money($bs['online_lifetime_sales'] ?? 0) }}</p>
                    <p class="mt-1 text-[10px] text-white/75">All-time website</p>
                </div>
                <div class="absolute right-0 top-0 flex h-9 w-9 items-center justify-center rounded-xl bg-white/15">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </div>
            </div>
        </div>

        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-orange-400 to-rose-500 p-3.5 text-white shadow-md shadow-orange-200/60">
            <div class="pointer-events-none absolute -right-4 -top-4 h-20 w-20 rounded-full bg-white/10"></div>
            <div class="relative pr-11">
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-white/80">Returns</p>
                    <p class="mt-1.5 text-[13px] sm:text-[15px] font-bold tracking-tight tabular-nums leading-tight break-words">{{ $money($bs['returns'] ?? 0) }}</p>
                    <p class="mt-1 text-[10px] text-white/75">Revenue reversed today</p>
                </div>
                <div class="absolute right-0 top-0 flex h-9 w-9 items-center justify-center rounded-xl bg-white/15">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                </div>
            </div>
        </div>

        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-sky-400 to-blue-600 p-3.5 text-white shadow-md shadow-sky-200/60">
            <div class="pointer-events-none absolute -right-4 -top-4 h-20 w-20 rounded-full bg-white/10"></div>
            <div class="relative pr-11">
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-white/80">Expenses</p>
                    <p class="mt-1.5 text-[13px] sm:text-[15px] font-bold tracking-tight tabular-nums leading-tight break-words">{{ $money($bs['expenses'] ?? 0) }}</p>
                    <p class="mt-1 text-[10px] text-white/75">Petty spend + till shortage</p>
                </div>
                <div class="absolute right-0 top-0 flex h-9 w-9 items-center justify-center rounded-xl bg-white/15">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                </div>
            </div>
        </div>

        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-700 to-blue-900 p-3.5 text-white shadow-md shadow-slate-300/50">
            <div class="pointer-events-none absolute -right-4 -top-4 h-20 w-20 rounded-full bg-white/10"></div>
            <div class="relative pr-11">
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-white/80">Net Amount</p>
                    <p class="mt-1.5 text-[13px] sm:text-[15px] font-bold tracking-tight tabular-nums leading-tight break-words">{{ $money($bs['net_amount'] ?? 0) }}</p>
                    <p class="mt-1 text-[10px] text-white/75">Sales − returns − expenses</p>
                </div>
                <div class="absolute right-0 top-0 flex h-9 w-9 items-center justify-center rounded-xl bg-white/15">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </div>
            </div>
        </div>

        @if($retail)
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-amber-400 to-orange-600 p-3.5 text-white shadow-md shadow-amber-200/60">
            @if(!empty($isAdmin))
                <a href="{{ route('customers.baki.index') }}" class="absolute inset-0 z-10" aria-label="Customer baki"></a>
            @endif
            <div class="pointer-events-none absolute -right-4 -top-4 h-20 w-20 rounded-full bg-white/10"></div>
            <div class="relative pr-11">
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-white/80">Baki Collection</p>
                    <p class="mt-1.5 text-[13px] sm:text-[15px] font-bold tracking-tight tabular-nums leading-tight break-words">{{ $money($bs['baki_collected'] ?? 0) }}</p>
                    <p class="mt-1 text-[10px] text-white/75">Credit paid today</p>
                </div>
                <div class="absolute right-0 top-0 flex h-9 w-9 items-center justify-center rounded-xl bg-white/15">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
            </div>
        </div>

        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-fuchsia-500 to-indigo-600 p-3.5 text-white shadow-md shadow-fuchsia-200/60">
            @if(!empty($isAdmin))
                <a href="{{ route('customers.emi.index') }}" class="absolute inset-0 z-10" aria-label="Customer EMI"></a>
            @endif
            <div class="pointer-events-none absolute -right-4 -top-4 h-20 w-20 rounded-full bg-white/10"></div>
            <div class="relative pr-11">
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-white/80">EMI Collection</p>
                    <p class="mt-1.5 text-[13px] sm:text-[15px] font-bold tracking-tight tabular-nums leading-tight break-words">{{ $money($bs['emi_collected'] ?? 0) }}</p>
                    <p class="mt-1 text-[10px] text-white/75">Installments paid today</p>
                </div>
                <div class="absolute right-0 top-0 flex h-9 w-9 items-center justify-center rounded-xl bg-white/15">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
            </div>
        </div>
        @endif

        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-teal-400 to-cyan-600 p-3.5 text-white shadow-md shadow-teal-200/50">
            @if(Auth::user()->isAdminUser())
                <a href="{{ route('accounts.petty-cash') }}" class="absolute inset-0 z-10" aria-label="Petty cash"></a>
            @endif
            <div class="pointer-events-none absolute -right-4 -top-4 h-20 w-20 rounded-full bg-white/10"></div>
            <div class="relative pr-11">
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-white/80">Petty Cash</p>
                    <p class="mt-1.5 text-[13px] sm:text-[15px] font-bold tracking-tight tabular-nums leading-tight break-words">{{ $money($bs['petty_cash'] ?? 0) }}</p>
                    <p class="mt-1 text-[10px] text-white/75">Safe cash on hand</p>
                </div>
                <div class="absolute right-0 top-0 flex h-9 w-9 items-center justify-center rounded-xl bg-white/15">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
        </div>

        @if($retail)
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-amber-400 to-yellow-600 p-3.5 text-white shadow-md shadow-amber-200/50">
            <a href="{{ route('counters.sessions.index') }}" class="absolute inset-0 z-10" aria-label="Cash sessions"></a>
            <div class="pointer-events-none absolute -right-4 -top-4 h-20 w-20 rounded-full bg-white/10"></div>
            <div class="relative pr-11">
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-white/80">Opening Balance</p>
                    <p class="mt-1.5 text-[13px] sm:text-[15px] font-bold tracking-tight tabular-nums leading-tight break-words">{{ $money($bs['opening_balance'] ?? 0) }}</p>
                    <p class="mt-1 text-[10px] text-white/75">{{ $openingHint }}</p>
                </div>
                <div class="absolute right-0 top-0 flex h-9 w-9 items-center justify-center rounded-xl bg-white/15">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                </div>
            </div>
        </div>

        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-lime-400 to-green-600 p-3.5 text-white shadow-md shadow-green-200/50">
            <div class="pointer-events-none absolute -right-4 -top-4 h-20 w-20 rounded-full bg-white/10"></div>
            <div class="relative pr-11">
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-white/80">Cash In</p>
                    <p class="mt-1.5 text-[13px] sm:text-[15px] font-bold tracking-tight tabular-nums leading-tight break-words">{{ $money($bs['cash_in'] ?? 0) }}</p>
                    <p class="mt-1 text-[10px] text-white/75">{{ $cashInHint }}</p>
                </div>
                <div class="absolute right-0 top-0 flex h-9 w-9 items-center justify-center rounded-xl bg-white/15">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                </div>
            </div>
        </div>

        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-pink-400 to-rose-600 p-3.5 text-white shadow-md shadow-rose-200/50">
            <div class="pointer-events-none absolute -right-4 -top-4 h-20 w-20 rounded-full bg-white/10"></div>
            <div class="relative pr-11">
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-white/80">Cash Out</p>
                    <p class="mt-1.5 text-[13px] sm:text-[15px] font-bold tracking-tight tabular-nums leading-tight break-words">{{ $money($bs['cash_out'] ?? 0) }}</p>
                    <p class="mt-1 text-[10px] text-white/75">{{ $cashOutHint }}</p>
                </div>
                <div class="absolute right-0 top-0 flex h-9 w-9 items-center justify-center rounded-xl bg-white/15">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                </div>
            </div>
        </div>

        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-700 p-3.5 text-white shadow-md shadow-emerald-200/50">
            <div class="pointer-events-none absolute -right-4 -top-4 h-20 w-20 rounded-full bg-white/10"></div>
            <div class="relative pr-11">
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-white/80">Closing Balance</p>
                    <p class="mt-1.5 text-[13px] sm:text-[15px] font-bold tracking-tight tabular-nums leading-tight break-words">{{ $money($bs['closing_balance'] ?? 0) }}</p>
                    <p class="mt-1 text-[10px] text-white/75">{{ $closingHint }}</p>
                </div>
                <div class="absolute right-0 top-0 flex h-9 w-9 items-center justify-center rounded-xl bg-white/15">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- Secondary KPIs (week view) --}}
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">{{ !empty($isAdmin) && ($selectedCounter ?? 'all') === 'all' ? 'Week Sales' : 'My Week Sales' }}</p>
                    <p class="mt-2 text-xl font-bold text-slate-900 tracking-tight">{{ $money($weekSales ?? $todaySales) }}</p>
                    <p class="mt-1 text-[11px] font-semibold {{ ($salesChangePct ?? 0) >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                        {{ ($salesChangePct ?? 0) >= 0 ? '↑' : '↓' }} {{ abs($salesChangePct ?? 0) }}% vs last week
                    </p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                </div>
            </div>
            <p class="mt-3 text-[11px] text-slate-400">{{ $dateRangeLabel }}</p>
        </div>

        <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Total Orders</p>
                    <p class="mt-2 text-xl font-bold text-slate-900 tracking-tight">{{ number_format($weekOrders ?? $todayOrdersCount) }}</p>
                    <p class="mt-1 text-[11px] font-semibold {{ ($ordersChangePct ?? 0) >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                        {{ ($ordersChangePct ?? 0) >= 0 ? '↑' : '↓' }} {{ abs($ordersChangePct ?? 0) }}% vs last week
                    </p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
            </div>
            <p class="mt-3 text-[11px] text-slate-400">Today: {{ $todayOrdersCount }} orders</p>
        </div>

        <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">{{ !empty($isAdmin) && ($selectedCounter ?? 'all') === 'all' ? 'Total Customers' : 'My Customers' }}</p>
                    <p class="mt-2 text-xl font-bold text-slate-900 tracking-tight">{{ number_format($totalCustomers) }}</p>
                    <p class="mt-1 text-[11px] text-slate-400">{{ !empty($isAdmin) && ($selectedCounter ?? 'all') === 'all' ? 'Registered in shop' : 'At this counter' }}</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Products</p>
                    <p class="mt-2 text-xl font-bold text-slate-900 tracking-tight">{{ number_format($totalProducts) }}</p>
                    <p class="mt-1 text-[11px] {{ ($lowStockCount ?? 0) > 0 ? 'text-amber-600 font-semibold' : 'text-slate-400' }}">
                        @if(($lowStockCount ?? 0) > 0)
                            <a href="{{ route('reports.low_stock') }}" class="hover:underline">{{ $lowStockCount }} low stock</a> · Inv {{ $money($inventoryValue) }}
                        @else
                            {{ $lowStockCount }} low stock · Inv {{ $money($inventoryValue) }}
                        @endif
                    </p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-orange-50 text-orange-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-4 xl:grid-cols-3">
        <div class="xl:col-span-2 rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
            <div class="mb-4 flex items-center justify-between gap-3">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Sales Overview</h3>
                    <p class="text-[11px] text-slate-400">This week vs previous week</p>
                </div>
                <div class="flex items-center gap-3 text-[11px] font-semibold">
                    <span class="inline-flex items-center gap-1.5 text-slate-600"><span class="h-2 w-2 rounded-full bg-blue-500"></span>This week</span>
                    <span class="inline-flex items-center gap-1.5 text-slate-400"><span class="h-2 w-2 rounded-full bg-slate-300"></span>Last week</span>
                </div>
            </div>
            <div class="h-64">
                <canvas id="salesOverviewChart"></canvas>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-sm font-bold text-slate-900">Sales by Category</h3>
            </div>
            <div class="relative mx-auto h-44 w-44">
                <canvas id="categoryDonutChart"></canvas>
                <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                    <span class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Week</span>
                    <span class="text-sm font-bold text-slate-900">{{ $money($categorySalesTotal) }}</span>
                </div>
            </div>
            <ul class="mt-4 space-y-2">
                @forelse($categorySales as $row)
                    @php $pct = $categorySalesTotal > 0 ? round(($row->revenue / $categorySalesTotal) * 100, 1) : 0; @endphp
                    <li class="flex items-center justify-between text-xs">
                        <span class="font-semibold text-slate-700">{{ $row->category_name }}</span>
                        <span class="text-slate-500">{{ $money($row->revenue) }} · {{ $pct }}%</span>
                    </li>
                @empty
                    <li class="py-6 text-center text-xs text-slate-400">No category sales this week.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="grid gap-4 xl:grid-cols-5">
        <div class="xl:col-span-3 rounded-2xl border border-slate-100 bg-white shadow-sm overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h3 class="text-sm font-bold text-slate-900">Recent Orders</h3>
                @if($retail)
                    @can('view sales ledger')
                        <a href="{{ route('sales.index') }}" class="text-xs font-bold text-blue-600 hover:text-blue-700">View All</a>
                    @endcan
                @elseif(!empty($isAdmin))
                    <a href="{{ route('online-orders.index') }}" class="text-xs font-bold text-blue-600 hover:text-blue-700">View All</a>
                @endif
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead class="bg-slate-50/80 text-[10px] uppercase tracking-wider text-slate-400">
                        <tr>
                            <th class="px-5 py-3 text-left font-semibold">Order ID</th>
                            <th class="px-5 py-3 text-left font-semibold">Customer</th>
                            <th class="px-5 py-3 text-left font-semibold">Status</th>
                            <th class="px-5 py-3 text-right font-semibold">Amount</th>
                            <th class="px-5 py-3 text-right font-semibold">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($recentOrders as $order)
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-5 py-3 font-bold text-slate-800">{{ $order->invoice_no }}</td>
                                <td class="px-5 py-3 text-slate-600">{{ $order->customer->name ?? 'Walk-in' }}</td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold capitalize {{ $statusClass[$order->status] ?? 'bg-slate-100 text-slate-600' }}">
                                        {{ $order->status }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-right font-bold text-slate-900">{{ $money($order->total_amount) }}</td>
                                <td class="px-5 py-3 text-right text-slate-500">{{ $order->created_at->format('M j, Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-10 text-center text-slate-400">No recent orders.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="xl:col-span-2 rounded-2xl border border-slate-100 bg-white shadow-sm overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h3 class="text-sm font-bold text-slate-900">Top Products</h3>
                @can('manage inventory')
                    <a href="{{ route('products.index') }}" class="text-xs font-bold text-blue-600 hover:text-blue-700">View All</a>
                @endcan
            </div>
            <ul class="divide-y divide-slate-100">
                @forelse($topProducts as $row)
                    <li class="flex items-center gap-3 px-5 py-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-slate-100 border border-slate-100">
                            @if($row->product?->image)
                                <img src="{{ public_storage_url($row->product->image) }}" alt="" class="h-full w-full object-cover">
                            @else
                                <span class="text-[10px] font-bold text-slate-400">{{ strtoupper(substr($row->product->name ?? 'P', 0, 2)) }}</span>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-xs font-bold text-slate-800">{{ $row->product->name ?? 'Product' }}</p>
                            <p class="text-[11px] text-slate-400">{{ (int) $row->sold_qty }} sold</p>
                        </div>
                        <div class="text-xs font-bold text-slate-900">{{ $money($row->revenue) }}</div>
                    </li>
                @empty
                    <li class="px-5 py-10 text-center text-xs text-slate-400">No product sales yet.</li>
                @endforelse
            </ul>
        </div>
    </div>

    @if(!empty($isAdmin) && isset($counterBreakdown))
    <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
        <h3 class="mb-4 text-sm font-bold text-slate-900">Today by counter</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs">
                <thead>
                    <tr class="border-b border-slate-100 text-left text-[10px] font-bold uppercase tracking-wider text-slate-400">
                        <th class="pb-2 pr-4">Counter</th>
                        <th class="pb-2 pr-4 text-right">Sales</th>
                        <th class="pb-2 pr-4 text-right">Baki collected</th>
                        <th class="pb-2 pr-4 text-right">EMI collected</th>
                        <th class="pb-2 pr-4 text-right">Orders</th>
                        <th class="pb-2 text-right">Customers</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse($counterBreakdown as $row)
                        <tr class="{{ ($selectedCounter ?? '') == (string) $row->id ? 'bg-indigo-50/60' : 'hover:bg-slate-50' }}">
                            <td class="py-2.5 pr-4 font-semibold text-slate-800">
                                <a href="{{ route('dashboard', ['counter' => $row->id]) }}" class="text-indigo-700 hover:underline">{{ $row->name }}</a>
                            </td>
                            <td class="py-2.5 pr-4 text-right font-bold">{{ $money($row->sales_total) }}</td>
                            <td class="py-2.5 pr-4 text-right font-semibold text-amber-700">{{ $money($row->baki_collected ?? 0) }}</td>
                            <td class="py-2.5 pr-4 text-right font-semibold text-indigo-700">{{ $money($row->emi_collected ?? 0) }}</td>
                            <td class="py-2.5 pr-4 text-right text-slate-600">{{ $row->orders_count }}</td>
                            <td class="py-2.5 text-right text-slate-600">{{ $row->customers_count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-6 text-center text-slate-400">No counters yet.</td></tr>
                    @endforelse
                    @if(!empty($onlineToday) && ($onlineToday->orders_count > 0 || $onlineToday->sales_total > 0))
                        <tr>
                            <td class="py-2.5 pr-4 italic text-slate-500">{{ $onlineToday->name }}</td>
                            <td class="py-2.5 pr-4 text-right font-bold text-slate-700">{{ $money($onlineToday->sales_total) }}</td>
                            <td class="py-2.5 pr-4 text-right text-slate-400">—</td>
                            <td class="py-2.5 pr-4 text-right text-slate-400">—</td>
                            <td class="py-2.5 pr-4 text-right text-slate-500">{{ $onlineToday->orders_count }}</td>
                            <td class="py-2.5 text-right text-slate-500">{{ $onlineToday->customers_count }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        @if($retail && Auth::user()->can('process pos sales'))
        <a href="{{ route('pos.index') }}" onclick="return window.launchPosTerminal(this.href)" class="rounded-2xl border border-blue-100 bg-blue-50 px-4 py-3.5 text-center text-xs font-bold uppercase tracking-wide text-blue-700 hover:bg-blue-600 hover:text-white hover:border-blue-600 transition">Launch POS Terminal</a>
        @endif
        @if(!$retail && !empty($isAdmin))
        <a href="{{ route('online-orders.index') }}" class="rounded-2xl border border-blue-100 bg-blue-50 px-4 py-3.5 text-center text-xs font-bold uppercase tracking-wide text-blue-700 hover:bg-blue-600 hover:text-white hover:border-blue-600 transition">Online Orders</a>
        @endif
        @can('manage inventory')
            <a href="{{ route('products.index') }}" class="rounded-2xl border border-emerald-100 bg-emerald-50 px-4 py-3.5 text-center text-xs font-bold uppercase tracking-wide text-emerald-700 hover:bg-emerald-600 hover:text-white hover:border-emerald-600 transition">Manage Products</a>
        @endcan
        @if($retail)
        <a href="{{ route('counters.sessions.index') }}" class="rounded-2xl border border-amber-100 bg-amber-50 px-4 py-3.5 text-center text-xs font-bold uppercase tracking-wide text-amber-800 hover:bg-amber-600 hover:text-white hover:border-amber-600 transition">Cash Sessions</a>
        @endif
        @if(!$retail && Auth::user()->can('manage website'))
        <a href="{{ route('cms.landing.edit') }}" class="rounded-2xl border border-amber-100 bg-amber-50 px-4 py-3.5 text-center text-xs font-bold uppercase tracking-wide text-amber-800 hover:bg-amber-600 hover:text-white hover:border-amber-600 transition">Edit Storefront</a>
        @endif
        @if(Auth::user()->isAdminUser())
        <a href="{{ route('analytics.overview') }}" class="rounded-2xl border border-violet-100 bg-violet-50 px-4 py-3.5 text-center text-xs font-bold uppercase tracking-wide text-violet-700 hover:bg-violet-600 hover:text-white hover:border-violet-600 transition">Reports</a>
        @endif
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(() => {
    const labels = @json($salesChartLabels);
    const thisWeek = @json($salesChartThisWeek);
    const lastWeek = @json($salesChartLastWeek);
    const catLabels = @json($categorySales->pluck('category_name'));
    const catValues = @json($categorySales->pluck('revenue')->map(fn($v)=>(float)$v));

    const salesEl = document.getElementById('salesOverviewChart');
    if (salesEl) {
        new Chart(salesEl, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    {
                        label: 'This Week',
                        data: thisWeek,
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37,99,235,.12)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 2.5,
                        pointRadius: 3,
                        pointBackgroundColor: '#2563eb',
                    },
                    {
                        label: 'Last Week',
                        data: lastWeek,
                        borderColor: '#cbd5e1',
                        borderDash: [6, 5],
                        fill: false,
                        tension: 0.35,
                        borderWidth: 2,
                        pointRadius: 0,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { mode: 'index', intersect: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 11 } } },
                    y: { grid: { color: '#f1f5f9' }, ticks: { color: '#94a3b8', font: { size: 11 } }, beginAtZero: true },
                },
            },
        });
    }

    const donutEl = document.getElementById('categoryDonutChart');
    if (donutEl) {
        new Chart(donutEl, {
            type: 'doughnut',
            data: {
                labels: catLabels.length ? catLabels : ['No data'],
                datasets: [{
                    data: catValues.length ? catValues : [1],
                    backgroundColor: ['#2563eb', '#14b8a6', '#f59e0b', '#8b5cf6', '#94a3b8'],
                    borderWidth: 0,
                    hoverOffset: 4,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: { legend: { display: false }, tooltip: { enabled: catValues.length > 0 } },
            },
        });
    }
})();
</script>
</x-app-layout>
