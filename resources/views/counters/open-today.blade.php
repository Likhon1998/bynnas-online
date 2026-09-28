<x-app-layout>
    @php
        $isAdminOpen = !empty($isAdminOpen);
        $freeCounters = $freeCounters ?? collect();
    @endphp

    <div class="w-full min-w-0 py-3 sm:py-4">
        <div class="max-w-xl mx-auto">
            <div class="mb-3 flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-xl font-bold text-slate-900 tracking-tight">Opening balance</h2>
                    <p class="text-sm text-slate-500 mt-0.5">
                        @if($isAdminOpen)
                            Unassigned counter · enter drawer cash · start POS
                        @else
                            Count drawer cash, then start your day
                        @endif
                    </p>
                </div>
                <p class="text-xs font-semibold text-slate-500 whitespace-nowrap pt-1">{{ now()->format('M j, Y') }}</p>
            </div>

            @if(session('success'))
                <div class="mb-3 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-800">{{ session('error') }}</div>
            @endif
            @if($errors->any())
                <div class="mb-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-800">{{ $errors->first() }}</div>
            @endif

            <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                @if($isAdminOpen)
                    <div class="px-4 py-2.5 border-b border-slate-100 bg-slate-50 flex items-center justify-between gap-2">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Admin till</p>
                            <p class="text-xs font-semibold text-slate-700">Only counters with no staff assigned</p>
                        </div>
                        <a href="{{ route('counters.sessions.index') }}" class="text-[11px] font-bold text-indigo-600 hover:underline">Sessions</a>
                    </div>

                    @if($freeCounters->isEmpty())
                        <div class="p-4 space-y-3">
                            <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2.5 text-sm text-amber-900 font-medium">
                                No unassigned counters. Create one in Manage Counters, or leave a till without staff assigned.
                            </div>
                            <div class="flex gap-2">
                                <a href="{{ route('counters.index') }}" class="flex-1 inline-flex items-center justify-center rounded-lg bg-slate-900 text-white text-xs font-bold py-2.5">Manage Counters</a>
                                <a href="{{ route('dashboard') }}" class="flex-1 inline-flex items-center justify-center rounded-lg border border-slate-200 text-slate-700 text-xs font-bold py-2.5">Dashboard</a>
                            </div>
                        </div>
                    @else
                        <form method="POST" action="{{ route('counters.sessions.open-today.store') }}" class="p-4 space-y-3">
                            @csrf
                            <div>
                                <label for="counter_id" class="block text-[11px] font-bold text-slate-500 mb-1">Unassigned counter *</label>
                                <select id="counter_id" name="counter_id" required
                                        class="w-full rounded-lg border-slate-200 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2 px-3 font-bold text-slate-900">
                                    @foreach($freeCounters as $free)
                                        <option value="{{ $free->id }}" @selected((string) old('counter_id', $counter?->id) === (string) $free->id)>{{ $free->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="opening_cash" class="block text-[11px] font-bold text-slate-500 mb-1">Opening cash (৳) *</label>
                                <input id="opening_cash" type="number" name="opening_cash" step="0.01" min="0" required autofocus
                                       value="{{ old('opening_cash', '0') }}"
                                       class="w-full rounded-lg border-slate-200 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500 text-base py-2.5 px-3 font-black text-slate-900">
                            </div>
                            <div>
                                <label for="notes" class="block text-[11px] font-bold text-slate-500 mb-1">Notes</label>
                                <input id="notes" type="text" name="notes" value="{{ old('notes') }}" placeholder="Optional"
                                       class="w-full rounded-lg border-slate-200 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2 px-3">
                            </div>
                            <button type="submit" class="w-full rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold py-2.5">
                                Open till &amp; start POS
                            </button>
                        </form>
                    @endif
                @else
                    <div class="px-4 py-2.5 border-b border-slate-100 bg-slate-50 flex items-center justify-between gap-3">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Your counter</p>
                            <p class="text-sm font-bold text-slate-900">{{ $counter->name }}</p>
                        </div>
                    </div>

                    @if($staleSession)
                        <div class="p-4 space-y-3">
                            <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2.5 text-sm text-amber-900 font-medium">
                                Session from <strong>{{ $staleSession->opened_at->format('M j, g:i A') }}</strong> is still open. Close it, then enter today’s opening cash.
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-sm">
                                <div class="rounded-lg bg-slate-50 border border-slate-100 px-3 py-2">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase">Started with</p>
                                    <p class="font-bold text-slate-800">{{ format_taka($staleSession->opening_cash) }}</p>
                                </div>
                                <div class="rounded-lg bg-slate-50 border border-slate-100 px-3 py-2">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase">Expected now</p>
                                    <p class="font-bold text-emerald-700">{{ format_taka($expected) }}</p>
                                </div>
                            </div>
                            <form method="POST" action="{{ route('counters.sessions.close', $staleSession) }}" class="space-y-3"
                                  data-confirm="Confirm you counted the drawer."
                                  data-confirm-title="Confirm count?"
                                  data-confirm-ok="Confirm"
                                  data-confirm-tone="warning">
                                @csrf
                                @include('counters.partials.transfer-log', ['transferLog' => $transferLog ?? [], 'class' => ''])
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-500 mb-1">Counted cash (৳) *</label>
                                    <input type="number" name="closing_cash" step="0.01" min="0" required value="{{ old('closing_cash') }}"
                                           class="w-full rounded-lg border-slate-200 bg-slate-50 focus:bg-white text-sm py-2 px-3 font-bold">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold text-slate-500 mb-1">Notes</label>
                                    <input type="text" name="notes" value="{{ old('notes') }}" placeholder="Required if ≠ expected"
                                           class="w-full rounded-lg border-slate-200 bg-slate-50 focus:bg-white text-sm py-2 px-3">
                                </div>
                                <label class="flex items-start gap-2 text-xs text-slate-600">
                                    <input type="checkbox" name="counted_confirm" value="1" required class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span>I counted the drawer.</span>
                                </label>
                                <button type="submit" class="w-full rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-sm font-bold py-2.5">
                                    Close previous session
                                </button>
                            </form>
                        </div>
                    @else
                        <form method="POST" action="{{ route('counters.sessions.open-today.store') }}" class="p-4 space-y-3">
                            @csrf
                            <div>
                                <label for="opening_cash" class="block text-[11px] font-bold text-slate-500 mb-1">Opening cash (৳) *</label>
                                <input id="opening_cash" type="number" name="opening_cash" step="0.01" min="0" required autofocus
                                       value="{{ old('opening_cash', '0') }}"
                                       class="w-full rounded-lg border-slate-200 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500 text-base py-2.5 px-3 font-black text-slate-900">
                                <p class="mt-1 text-[11px] text-slate-500">Exact cash in drawer before first sale.</p>
                            </div>
                            <div>
                                <label for="notes" class="block text-[11px] font-bold text-slate-500 mb-1">Notes</label>
                                <input id="notes" type="text" name="notes" value="{{ old('notes') }}" placeholder="Optional"
                                       class="w-full rounded-lg border-slate-200 bg-slate-50 focus:bg-white text-sm py-2 px-3">
                            </div>
                            <button type="submit" class="w-full rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold py-2.5">
                                Start day &amp; open POS
                            </button>
                        </form>
                    @endif
                @endif
            </div>

            <form method="POST" action="{{ route('logout') }}" class="mt-3 text-center">
                @csrf
                <button type="submit" class="text-xs font-semibold text-slate-400 hover:text-slate-600">Sign out</button>
            </form>
        </div>
    </div>
</x-app-layout>
