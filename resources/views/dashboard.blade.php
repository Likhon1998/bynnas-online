<x-app-layout>
@php
    $user = Auth::user();
    $money = fn ($n) => format_taka($n);
    $statusClass = [
        'completed' => 'bg-emerald-50 text-emerald-700',
        'delivered' => 'bg-emerald-50 text-emerald-700',
        'new' => 'bg-amber-50 text-amber-700',
        'pending' => 'bg-amber-50 text-amber-700',
        'pending_fulfillment' => 'bg-amber-50 text-amber-700',
        'confirmed' => 'bg-cyan-50 text-cyan-700',
        'processing' => 'bg-amber-50 text-amber-700',
        'packed' => 'bg-indigo-50 text-indigo-700',
        'shipped' => 'bg-sky-50 text-sky-700',
        'return_requested' => 'bg-yellow-50 text-yellow-700',
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

@php
    $hour = (int) now()->format('G');
    [$greeting, $greetLottie] = match (true) {
        $hour >= 5 && $hour < 12 => ['Good morning', 'sun'],
        $hour >= 12 && $hour < 17 => ['Good afternoon', 'rocket'],
        $hour >= 17 && $hour < 22 => ['Good evening', 'hug'],
        default => ['Working late', 'sleepy'],
    };
@endphp

<div class="space-y-3">
    <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2">
        <div class="flex items-center gap-2.5 min-w-0">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white shadow-[0_3px_0_#f6d9c8]">
                @include('website.partials.lottie', ['name' => $greetLottie, 'class' => 'h-7 w-7'])
            </span>
            <div class="min-w-0">
                <h1 class="text-[1.2rem] leading-tight text-slate-900 truncate">{{ $greeting }}, {{ explode(' ', $user->name)[0] }}!</h1>
                <p class="text-[12px] text-slate-500 truncate">
                    Business summary for
                    <span class="font-bold text-slate-700">{{ $filterLabel ?? ($counter->name ?? 'your shop') }}</span>
                    · {{ now()->format('D, M j, Y') }}
                </p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if(Auth::user()->can('manage orders') && ($pendingOnlineOrders ?? 0) > 0)
                <a href="{{ route('online-orders.index') }}"
                   class="inline-flex items-center gap-1.5 rounded-full bg-blue-600 py-1.5 pl-1.5 pr-3 text-[12px] font-bold text-white shadow-[0_2px_0_#963d20] hover:bg-blue-700">
                    <span class="flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-white px-1 text-[11px] font-extrabold text-blue-700">{{ $pendingOnlineOrders }}</span>
                    pending online order{{ $pendingOnlineOrders === 1 ? '' : 's' }}
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                </a>
            @endif
            @if(!empty($isAdmin) && isset($counters) && $counters->isNotEmpty())
                <form method="GET" action="{{ route('dashboard') }}" class="inline-flex items-center">
                    <label for="counter-filter" class="sr-only">Counter</label>
                    <select
                        id="counter-filter"
                        name="counter"
                        onchange="this.form.submit()"
                        class="rounded-full border border-slate-200 bg-white py-1.5 pl-3 pr-8 text-xs font-semibold text-slate-700 shadow-sm focus:border-indigo-400 focus:ring-indigo-400"
                    >
                        <option value="all" @selected(($selectedCounter ?? 'all') === 'all')>All counters</option>
                        @foreach($counters as $c)
                            <option value="{{ $c->id }}" @selected(($selectedCounter ?? '') == (string) $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </form>
            @endif
            @if($retail)
                <span class="inline-flex items-center rounded-full px-2.5 py-1.5 text-[11px] font-semibold {{ $sessionBadge[1] }}">
                    {{ $sessionBadge[0] }}
                </span>
            @endif
        </div>
    </div>

    @if($retail && !$user->canAccessPos())
        <div class="flex items-center justify-between rounded-2xl border border-amber-200/80 bg-amber-50 px-4 py-2.5">
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
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-amber-200/80 bg-amber-50 px-4 py-2.5">
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

    {{-- All KPIs: 7 + 7 = exactly 2 rows on xl+ --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 {{ $retail ? 'xl:grid-cols-7' : 'xl:grid-cols-6' }} gap-2.5">
        @if($retail)
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#7657c9] to-[#553a9e] px-3 py-2.5 text-white shadow-md shadow-[#4a3026]/15">
            <div class="pointer-events-none absolute -right-5 -top-6 h-16 w-16 rounded-full bg-white/10"></div>
            <div class="relative pr-8">
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-white/95">POS Sales</p>
                    <p class="mt-0.5 text-[15px] sm:text-[16px] font-bold tracking-tight tabular-nums leading-tight break-words">{{ $money($bs['pos_sales'] ?? 0) }}</p>
                    <p class="mt-0.5 text-[10px] leading-snug text-white/95">Today (counter)</p>
                </div>
                <div class="absolute right-0 top-0 flex h-7 w-7 items-center justify-center rounded-lg bg-white/15">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
            </div>
        </div>
        @endif

        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#b84f2c] to-[#963d20] px-3 py-2.5 text-white shadow-md shadow-[#4a3026]/15">
            <div class="pointer-events-none absolute -right-5 -top-6 h-16 w-16 rounded-full bg-white/10"></div>
            <div class="relative pr-8">
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-white/95">Online Sales</p>
                    <p class="mt-0.5 text-[15px] sm:text-[16px] font-bold tracking-tight tabular-nums leading-tight break-words">{{ $money($bs['online_sales'] ?? 0) }}</p>
                    <p class="mt-0.5 text-[10px] leading-snug text-white/95">Today (website)</p>
                </div>
                <div class="absolute right-0 top-0 flex h-7 w-7 items-center justify-center rounded-lg bg-white/15">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                </div>
            </div>
        </div>

        @if($retail)
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1d7c6f] to-[#145c52] px-3 py-2.5 text-white shadow-md shadow-[#4a3026]/15">
            <div class="pointer-events-none absolute -right-5 -top-6 h-16 w-16 rounded-full bg-white/10"></div>
            <div class="relative pr-8">
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-white/95">POS Lifetime</p>
                    <p class="mt-0.5 text-[15px] sm:text-[16px] font-bold tracking-tight tabular-nums leading-tight break-words">{{ $money($bs['pos_lifetime_sales'] ?? 0) }}</p>
                    <p class="mt-0.5 text-[10px] leading-snug text-white/95">All-time POS</p>
                </div>
                <div class="absolute right-0 top-0 flex h-7 w-7 items-center justify-center rounded-lg bg-white/15">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
        </div>
        @endif

        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#557a41] to-[#3f5c2f] px-3 py-2.5 text-white shadow-md shadow-[#4a3026]/15">
            <div class="pointer-events-none absolute -right-5 -top-6 h-16 w-16 rounded-full bg-white/10"></div>
            <div class="relative pr-8">
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-white/95">Online Lifetime</p>
                    <p class="mt-0.5 text-[15px] sm:text-[16px] font-bold tracking-tight tabular-nums leading-tight break-words">{{ $money($bs['online_lifetime_sales'] ?? 0) }}</p>
                    <p class="mt-0.5 text-[10px] leading-snug text-white/95">All-time website</p>
                </div>
                <div class="absolute right-0 top-0 flex h-7 w-7 items-center justify-center rounded-lg bg-white/15">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </div>
            </div>
        </div>

        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#c43d63] to-[#9c2e4e] px-3 py-2.5 text-white shadow-md shadow-[#4a3026]/15">
            <div class="pointer-events-none absolute -right-5 -top-6 h-16 w-16 rounded-full bg-white/10"></div>
            <div class="relative pr-8">
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-white/95">Returns</p>
                    <p class="mt-0.5 text-[15px] sm:text-[16px] font-bold tracking-tight tabular-nums leading-tight break-words">{{ $money($bs['returns'] ?? 0) }}</p>
                    <p class="mt-0.5 text-[10px] leading-snug text-white/95">Revenue reversed today</p>
                </div>
                <div class="absolute right-0 top-0 flex h-7 w-7 items-center justify-center rounded-lg bg-white/15">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                </div>
            </div>
        </div>

        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#2a74a4] to-[#1d5a82] px-3 py-2.5 text-white shadow-md shadow-[#4a3026]/15">
            <div class="pointer-events-none absolute -right-5 -top-6 h-16 w-16 rounded-full bg-white/10"></div>
            <div class="relative pr-8">
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-white/95">Expenses</p>
                    <p class="mt-0.5 text-[15px] sm:text-[16px] font-bold tracking-tight tabular-nums leading-tight break-words">{{ $money($bs['expenses'] ?? 0) }}</p>
                    <p class="mt-0.5 text-[10px] leading-snug text-white/95">{{ $retail ? 'Petty spend + till shortage' : 'Petty cash spend' }}</p>
                </div>
                <div class="absolute right-0 top-0 flex h-7 w-7 items-center justify-center rounded-lg bg-white/15">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
                </div>
            </div>
        </div>

        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#7a5f53] to-[#4a3830] px-3 py-2.5 text-white shadow-md shadow-[#4a3026]/15">
            <div class="pointer-events-none absolute -right-5 -top-6 h-16 w-16 rounded-full bg-white/10"></div>
            <div class="relative pr-8">
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-white/95">Net Amount</p>
                    <p class="mt-0.5 text-[15px] sm:text-[16px] font-bold tracking-tight tabular-nums leading-tight break-words">{{ $money($bs['net_amount'] ?? 0) }}</p>
                    <p class="mt-0.5 text-[10px] leading-snug text-white/95">Sales − returns − expenses</p>
                </div>
                <div class="absolute right-0 top-0 flex h-7 w-7 items-center justify-center rounded-lg bg-white/15">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </div>
            </div>
        </div>

        @if($retail)
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#a8620c] to-[#7f4a08] px-3 py-2.5 text-white shadow-md shadow-[#4a3026]/15">
            @if(!empty($isAdmin))
                <a href="{{ route('customers.baki.index') }}" class="absolute inset-0 z-10" aria-label="Customer baki"></a>
            @endif
            <div class="pointer-events-none absolute -right-5 -top-6 h-16 w-16 rounded-full bg-white/10"></div>
            <div class="relative pr-8">
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-white/95">Baki Collection</p>
                    <p class="mt-0.5 text-[15px] sm:text-[16px] font-bold tracking-tight tabular-nums leading-tight break-words">{{ $money($bs['baki_collected'] ?? 0) }}</p>
                    <p class="mt-0.5 text-[10px] leading-snug text-white/95">Credit paid today</p>
                </div>
                <div class="absolute right-0 top-0 flex h-7 w-7 items-center justify-center rounded-lg bg-white/15">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
            </div>
        </div>

        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#8a4fc2] to-[#6a3fa3] px-3 py-2.5 text-white shadow-md shadow-[#4a3026]/15">
            @if(!empty($isAdmin))
                <a href="{{ route('customers.emi.index') }}" class="absolute inset-0 z-10" aria-label="Customer EMI"></a>
            @endif
            <div class="pointer-events-none absolute -right-5 -top-6 h-16 w-16 rounded-full bg-white/10"></div>
            <div class="relative pr-8">
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-white/95">EMI Collection</p>
                    <p class="mt-0.5 text-[15px] sm:text-[16px] font-bold tracking-tight tabular-nums leading-tight break-words">{{ $money($bs['emi_collected'] ?? 0) }}</p>
                    <p class="mt-0.5 text-[10px] leading-snug text-white/95">Installments paid today</p>
                </div>
                <div class="absolute right-0 top-0 flex h-7 w-7 items-center justify-center rounded-lg bg-white/15">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
            </div>
        </div>
        @endif

        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1d7c6f] to-[#145c52] px-3 py-2.5 text-white shadow-md shadow-[#4a3026]/15">
            @if(Auth::user()->can('manage accounts'))
                <a href="{{ route('accounts.petty-cash') }}" class="absolute inset-0 z-10" aria-label="Petty cash"></a>
            @endif
            <div class="pointer-events-none absolute -right-5 -top-6 h-16 w-16 rounded-full bg-white/10"></div>
            <div class="relative pr-8">
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-white/95">Petty Cash</p>
                    <p class="mt-0.5 text-[15px] sm:text-[16px] font-bold tracking-tight tabular-nums leading-tight break-words">{{ $money($bs['petty_cash'] ?? 0) }}</p>
                    <p class="mt-0.5 text-[10px] leading-snug text-white/95">Safe cash on hand</p>
                </div>
                <div class="absolute right-0 top-0 flex h-7 w-7 items-center justify-center rounded-lg bg-white/15">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
        </div>

        @if($retail)
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#a8620c] to-[#7f4a08] px-3 py-2.5 text-white shadow-md shadow-[#4a3026]/15">
            <a href="{{ route('counters.sessions.index') }}" class="absolute inset-0 z-10" aria-label="Cash sessions"></a>
            <div class="pointer-events-none absolute -right-5 -top-6 h-16 w-16 rounded-full bg-white/10"></div>
            <div class="relative pr-8">
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-white/95">Opening Balance</p>
                    <p class="mt-0.5 text-[15px] sm:text-[16px] font-bold tracking-tight tabular-nums leading-tight break-words">{{ $money($bs['opening_balance'] ?? 0) }}</p>
                    <p class="mt-0.5 text-[10px] leading-snug text-white/95">{{ $openingHint }}</p>
                </div>
                <div class="absolute right-0 top-0 flex h-7 w-7 items-center justify-center rounded-lg bg-white/15">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                </div>
            </div>
        </div>

        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#557a41] to-[#3f5c2f] px-3 py-2.5 text-white shadow-md shadow-[#4a3026]/15">
            <div class="pointer-events-none absolute -right-5 -top-6 h-16 w-16 rounded-full bg-white/10"></div>
            <div class="relative pr-8">
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-white/95">Cash In</p>
                    <p class="mt-0.5 text-[15px] sm:text-[16px] font-bold tracking-tight tabular-nums leading-tight break-words">{{ $money($bs['cash_in'] ?? 0) }}</p>
                    <p class="mt-0.5 text-[10px] leading-snug text-white/95">{{ $cashInHint }}</p>
                </div>
                <div class="absolute right-0 top-0 flex h-7 w-7 items-center justify-center rounded-lg bg-white/15">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                </div>
            </div>
        </div>

        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#c43d63] to-[#9c2e4e] px-3 py-2.5 text-white shadow-md shadow-[#4a3026]/15">
            <div class="pointer-events-none absolute -right-5 -top-6 h-16 w-16 rounded-full bg-white/10"></div>
            <div class="relative pr-8">
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-white/95">Cash Out</p>
                    <p class="mt-0.5 text-[15px] sm:text-[16px] font-bold tracking-tight tabular-nums leading-tight break-words">{{ $money($bs['cash_out'] ?? 0) }}</p>
                    <p class="mt-0.5 text-[10px] leading-snug text-white/95">{{ $cashOutHint }}</p>
                </div>
                <div class="absolute right-0 top-0 flex h-7 w-7 items-center justify-center rounded-lg bg-white/15">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                </div>
            </div>
        </div>

        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-[#1d7c6f] to-[#145c52] px-3 py-2.5 text-white shadow-md shadow-[#4a3026]/15">
            <div class="pointer-events-none absolute -right-5 -top-6 h-16 w-16 rounded-full bg-white/10"></div>
            <div class="relative pr-8">
                <div class="min-w-0">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-white/95">Closing Balance</p>
                    <p class="mt-0.5 text-[15px] sm:text-[16px] font-bold tracking-tight tabular-nums leading-tight break-words">{{ $money($bs['closing_balance'] ?? 0) }}</p>
                    <p class="mt-0.5 text-[10px] leading-snug text-white/95">{{ $closingHint }}</p>
                </div>
                <div class="absolute right-0 top-0 flex h-7 w-7 items-center justify-center rounded-lg bg-white/15">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                </div>
            </div>
        </div>
        @endif
    </div>

    @php
        $chartThisTotal = collect($salesChartThisWeek)->sum();
        $chartLastTotal = collect($salesChartLastWeek)->sum();
        $chartChange = $chartLastTotal > 0
            ? round((($chartThisTotal - $chartLastTotal) / $chartLastTotal) * 100)
            : ($chartThisTotal > 0 ? 100 : 0);
    @endphp
    {{-- Week view: 2x2 KPIs beside the sales chart --}}
    <div class="grid gap-3 xl:grid-cols-3">
        <div class="grid grid-cols-2 sm:grid-cols-4 xl:grid-cols-2 gap-2.5 xl:order-2">
            <div class="flex flex-col justify-between rounded-2xl border border-slate-100 bg-white p-3 shadow-sm">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-[10.5px] font-bold uppercase tracking-wide text-slate-400">{{ ! $retail || (!empty($isAdmin) && ($selectedCounter ?? 'all') === 'all') ? 'Week Sales' : 'My Week Sales' }}</p>
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    </span>
                </div>
                <p class="mt-1 text-lg font-bold leading-tight text-slate-900 tabular-nums">{{ $money($weekSales ?? $todaySales) }}</p>
                <p class="mt-0.5 truncate text-[11px] font-semibold {{ ($salesChangePct ?? 0) >= 0 ? 'text-emerald-600' : 'text-rose-600' }}" title="{{ $dateRangeLabel }}">
                    {{ ($salesChangePct ?? 0) >= 0 ? '↑' : '↓' }} {{ abs($salesChangePct ?? 0) }}% vs last week
                </p>
            </div>

            <div class="flex flex-col justify-between rounded-2xl border border-slate-100 bg-white p-3 shadow-sm">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-[10.5px] font-bold uppercase tracking-wide text-slate-400">Week Orders</p>
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </span>
                </div>
                <p class="mt-1 text-lg font-bold leading-tight text-slate-900 tabular-nums">{{ number_format($weekOrders ?? $todayOrdersCount) }}</p>
                <p class="mt-0.5 truncate text-[11px] font-semibold {{ ($ordersChangePct ?? 0) >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                    {{ ($ordersChangePct ?? 0) >= 0 ? '↑' : '↓' }} {{ abs($ordersChangePct ?? 0) }}% · <span class="text-slate-500">{{ $todayOrdersCount }} today</span>
                </p>
            </div>

            <div class="flex flex-col justify-between rounded-2xl border border-slate-100 bg-white p-3 shadow-sm">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-[10.5px] font-bold uppercase tracking-wide text-slate-400">{{ ! $retail || (!empty($isAdmin) && ($selectedCounter ?? 'all') === 'all') ? 'Customers' : 'My Customers' }}</p>
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-violet-50 text-violet-600">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </span>
                </div>
                <p class="mt-1 text-lg font-bold leading-tight text-slate-900 tabular-nums">{{ number_format($totalCustomers) }}</p>
                <p class="mt-0.5 truncate text-[11px] text-slate-500">{{ ! $retail || (!empty($isAdmin) && ($selectedCounter ?? 'all') === 'all') ? 'Registered customers' : 'At this counter' }}</p>
            </div>

            <div class="flex flex-col justify-between rounded-2xl border border-slate-100 bg-white p-3 shadow-sm">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-[10.5px] font-bold uppercase tracking-wide text-slate-400">Products</p>
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-orange-50 text-orange-600">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </span>
                </div>
                <p class="mt-1 text-lg font-bold leading-tight text-slate-900 tabular-nums">{{ number_format($totalProducts) }}</p>
                <p class="mt-0.5 text-[11px] leading-snug {{ ($lowStockCount ?? 0) > 0 ? 'text-amber-600 font-semibold' : 'text-slate-500' }}" title="Inventory {{ $money($inventoryValue) }}">
                    @if(($lowStockCount ?? 0) > 0)
                        <a href="{{ route('reports.low_stock') }}" class="hover:underline">{{ $lowStockCount }} low stock</a>
                    @else
                        {{ $lowStockCount }} low stock
                    @endif
                    · Inv {{ $money($inventoryValue) }}
                </p>
            </div>
        </div>

        <div class="xl:col-span-2 xl:order-1 rounded-2xl border border-slate-100 bg-white px-4 pt-3 pb-2 shadow-sm">
            <div class="mb-1 flex flex-wrap items-center justify-between gap-x-4 gap-y-1.5">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Sales Overview</h3>
                    <p class="text-[11px] text-slate-500">{{ $dateRangeLabel }} vs previous week</p>
                </div>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px]">
                    <span class="inline-flex items-center gap-1.5">
                        <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                        <span class="font-semibold text-slate-500">This week</span>
                        <span class="font-extrabold text-slate-900">{{ $money($chartThisTotal) }}</span>
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <span class="h-2 w-2 rounded-full bg-indigo-300"></span>
                        <span class="font-semibold text-slate-500">Last week</span>
                        <span class="font-extrabold text-slate-700">{{ $money($chartLastTotal) }}</span>
                    </span>
                    <span class="rounded-full px-2 py-0.5 font-extrabold {{ $chartChange >= 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                        {{ $chartChange >= 0 ? '↑' : '↓' }} {{ abs($chartChange) }}%
                    </span>
                </div>
            </div>
            <div class="h-36 sm:h-[150px]">
                <canvas id="salesOverviewChart"></canvas>
            </div>
        </div>
    </div>

    <div class="grid gap-3 xl:grid-cols-5">
        <div class="xl:col-span-3 rounded-2xl border border-slate-100 bg-white shadow-sm overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-2.5">
                <h3 class="text-sm font-bold text-slate-900">Recent Orders</h3>
                @if($retail)
                    @can('view sales ledger')
                        <a href="{{ route('sales.index') }}" class="text-xs font-bold text-blue-600 hover:text-blue-700">View All</a>
                    @endcan
                @elseif(Auth::user()->can('manage orders'))
                    <a href="{{ route('online-orders.index') }}" class="text-xs font-bold text-blue-600 hover:text-blue-700">View All</a>
                @endif
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead class="bg-slate-50/80 text-[10px] uppercase tracking-wider text-slate-400">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold">Order ID</th>
                            <th class="px-4 py-2 text-left font-semibold">Customer</th>
                            <th class="px-4 py-2 text-left font-semibold">Status</th>
                            <th class="px-4 py-2 text-right font-semibold">Amount</th>
                            <th class="px-4 py-2 text-right font-semibold">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($recentOrders as $order)
                            <tr class="hover:bg-slate-50/70">
                                <td class="px-4 py-2 font-bold text-slate-800">{{ $order->invoice_no }}</td>
                                <td class="px-4 py-2 text-slate-600">{{ $order->delivery_name ?: ($order->customer->name ?? 'Guest') }}</td>
                                <td class="px-4 py-2">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold capitalize {{ $statusClass[$order->status] ?? 'bg-slate-100 text-slate-600' }}">
                                        {{ \App\Support\OrderStatus::label($order->status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-right font-bold text-slate-900">{{ $money($order->total_amount) }}</td>
                                <td class="px-4 py-2 text-right text-slate-500">{{ $order->created_at->format('M j, Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-10 text-center text-slate-400">No recent orders.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="xl:col-span-2 rounded-2xl border border-slate-100 bg-white shadow-sm overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-2.5">
                <h3 class="text-sm font-bold text-slate-900">Top Products</h3>
                @can('manage inventory')
                    <a href="{{ route('products.index') }}" class="text-xs font-bold text-blue-600 hover:text-blue-700">View All</a>
                @endcan
            </div>
            <ul class="divide-y divide-slate-100">
                @forelse($topProducts as $row)
                    <li class="flex items-center gap-3 px-4 py-2">
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
    <div class="rounded-2xl border border-slate-100 bg-white p-4 shadow-sm">
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
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(() => {
    const labels = @json($salesChartLabels);
    const thisWeek = @json($salesChartThisWeek);
    const lastWeek = @json($salesChartLastWeek);
    if (window.Chart) {
        Chart.defaults.font.family = "'Nunito', ui-sans-serif, system-ui, sans-serif";
        Chart.defaults.color = '#8c776d';
    }

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
                        borderColor: '#ec8560',
                        backgroundColor: 'rgba(236,133,96,.14)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 2.25,
                        pointRadius: 2.5,
                        pointHoverRadius: 5,
                        pointBackgroundColor: '#ec8560',
                    },
                    {
                        label: 'Last Week',
                        data: lastWeek,
                        borderColor: '#c4b2f1',
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
                interaction: { mode: 'index', intersect: false },
                layout: { padding: { top: 6 } },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#3a2a24',
                        titleColor: '#fff',
                        bodyColor: '#f8f1eb',
                        padding: 10,
                        cornerRadius: 10,
                        boxPadding: 4,
                        usePointStyle: true,
                    },
                },
                scales: {
                    x: { grid: { display: false }, border: { display: false }, ticks: { color: '#ae978a', font: { size: 10.5 } } },
                    y: {
                        grid: { color: '#f8f1eb' },
                        border: { display: false },
                        beginAtZero: true,
                        ticks: { color: '#ae978a', font: { size: 10.5 }, maxTicksLimit: 4 },
                    },
                },
            },
        });
    }
})();
</script>
</x-app-layout>
