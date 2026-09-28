<x-app-layout>
    <div class="w-full min-w-0 py-3 sm:py-4"
         x-data="{
            isEditModalOpen: false,
            editName: '',
            editStatus: true,
            editUrl: '',
            newName: @js(old('name', '')),
            openEdit(name, status, url) {
                this.editName = name;
                this.editStatus = status;
                this.editUrl = url;
                this.isEditModalOpen = true;
            }
         }"
         @keydown.escape.window="isEditModalOpen = false">

        @php
            $total = $counters->count();
            $active = $counters->where('is_active', true)->count();
            $unassigned = $counters->where('users_count', 0)->count();
            $openSessions = $counters->sum('sessions_count');
        @endphp

        <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between mb-3">
            <div class="min-w-0">
                <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Manage Counters</h2>
                <p class="text-sm text-slate-500 mt-0.5">Terminals for POS · assign staff · open cash sessions</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('counters.sessions.index') }}"
                   class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                    Cash Sessions
                </a>
                <a href="{{ route('staff.index') }}"
                   class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-indigo-700">
                    Assign Staff
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="mb-3 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-800">{{ session('error') }}</div>
        @endif
        @if(($staffWithoutCounter ?? 0) > 0)
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2">
                <p class="text-sm font-semibold text-amber-900">{{ $staffWithoutCounter }} staff need a counter before POS.</p>
                <a href="{{ route('staff.index') }}" class="text-xs font-bold text-indigo-700 hover:underline">Assign now →</a>
            </div>
        @endif

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-3">
            <div class="rounded-xl border border-slate-200 bg-white px-3 py-2.5">
                <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Total</p>
                <p class="text-lg font-black text-slate-900 leading-tight">{{ $total }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white px-3 py-2.5">
                <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Active</p>
                <p class="text-lg font-black text-emerald-700 leading-tight">{{ $active }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white px-3 py-2.5">
                <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Unassigned</p>
                <p class="text-lg font-black text-indigo-700 leading-tight">{{ $unassigned }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white px-3 py-2.5">
                <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Open sessions</p>
                <p class="text-lg font-black text-slate-900 leading-tight">{{ $openSessions }}</p>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-slate-100 px-3 py-2.5 sm:px-4 bg-slate-50/80">
                <form action="{{ route('counters.store') }}" method="POST" class="flex flex-col sm:flex-row gap-2 sm:items-center">
                    @csrf
                    <label for="counter-name" class="text-[11px] font-bold uppercase tracking-wide text-slate-500 shrink-0 sm:w-24">Add counter</label>
                    <input
                        id="counter-name"
                        type="text"
                        name="name"
                        x-model="newName"
                        required
                        maxlength="255"
                        autocomplete="off"
                        placeholder="e.g. Counter 1, Front Desk"
                        class="flex-1 min-w-0 rounded-lg border-slate-200 bg-white focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2 px-3 font-semibold text-slate-900"
                    >
                    <button type="submit"
                            class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-slate-900 hover:bg-indigo-600 text-white text-xs font-bold px-4 py-2 transition whitespace-nowrap">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        Add
                    </button>
                </form>
                @error('name')
                    <p class="mt-1.5 text-xs font-semibold text-red-600">{{ $message }}</p>
                @enderror
                <div class="mt-2 flex flex-wrap gap-1.5">
                    @foreach(['Counter 1', 'Counter 2', 'Front Desk', 'Drive-Thru'] as $suggestion)
                        <button type="button" @click="newName = '{{ $suggestion }}'"
                                class="rounded-md border border-slate-200 bg-white px-2 py-0.5 text-[11px] font-semibold text-slate-600 hover:border-indigo-300 hover:text-indigo-700">
                            {{ $suggestion }}
                        </button>
                    @endforeach
                </div>
            </div>

            @if($counters->isEmpty())
                <div class="px-4 py-12 text-center">
                    <p class="text-sm font-bold text-slate-800">No counters yet</p>
                    <p class="mt-1 text-xs text-slate-500">Add a terminal above, then assign it from Staff.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[720px] text-sm text-left">
                        <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-500 border-b border-slate-100">
                            <tr>
                                <th class="px-3 sm:px-4 py-2.5 font-bold">Counter</th>
                                <th class="px-3 py-2.5 font-bold">Status</th>
                                <th class="px-3 py-2.5 font-bold">Staff</th>
                                <th class="px-3 py-2.5 font-bold">Session</th>
                                <th class="px-3 sm:px-4 py-2.5 font-bold text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($counters as $counter)
                                <tr class="hover:bg-slate-50/80">
                                    <td class="px-3 sm:px-4 py-2.5">
                                        <div class="flex items-center gap-2.5 min-w-0">
                                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $counter->is_active ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-400' }}">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="font-bold text-slate-900 truncate">{{ $counter->name }}</p>
                                                <p class="text-[11px] text-slate-400">#{{ $counter->id }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2.5">
                                        @if($counter->is_active)
                                            <span class="inline-flex items-center gap-1 rounded-md bg-emerald-50 border border-emerald-100 px-2 py-0.5 text-[10px] font-bold uppercase text-emerald-700">
                                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Active
                                            </span>
                                        @else
                                            <span class="inline-flex rounded-md bg-slate-100 border border-slate-200 px-2 py-0.5 text-[10px] font-bold uppercase text-slate-500">Offline</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2.5">
                                        @if(($counter->users_count ?? 0) === 0)
                                            <span class="text-xs font-semibold text-indigo-600">Unassigned</span>
                                            <span class="block text-[10px] text-slate-400">Admin POS can use</span>
                                        @else
                                            <p class="text-xs font-semibold text-slate-800">
                                                {{ $counter->users->pluck('name')->take(2)->join(', ') }}
                                                @if($counter->users->count() > 2)
                                                    <span class="text-slate-400">+{{ $counter->users->count() - 2 }}</span>
                                                @endif
                                            </p>
                                            <p class="text-[10px] text-slate-400">{{ $counter->users_count }} assigned</p>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2.5">
                                        @if(($counter->sessions_count ?? 0) > 0)
                                            <span class="inline-flex rounded-md bg-indigo-50 border border-indigo-100 px-2 py-0.5 text-[10px] font-bold text-indigo-700">Open</span>
                                        @else
                                            <span class="text-xs text-slate-400">Closed</span>
                                        @endif
                                    </td>
                                    <td class="px-3 sm:px-4 py-2.5 text-right">
                                        <div class="inline-flex items-center gap-1">
                                            <button type="button"
                                                    @click="openEdit(@js($counter->name), {{ $counter->is_active ? 'true' : 'false' }}, @js(route('counters.update', $counter->id)))"
                                                    class="rounded-md px-2 py-1 text-xs font-bold text-indigo-600 hover:bg-indigo-50">
                                                Edit
                                            </button>
                                            <form action="{{ route('counters.destroy', $counter->id) }}" method="POST"
                                                  data-confirm="Delete “{{ addslashes($counter->name) }}”?"
                                                  data-confirm-title="Delete?"
                                                  data-confirm-ok="Delete"
                                                  data-confirm-tone="danger">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="rounded-md px-2 py-1 text-xs font-bold text-rose-600 hover:bg-rose-50 disabled:opacity-40"
                                                        @disabled(($counter->sessions_count ?? 0) > 0)
                                                        title="{{ ($counter->sessions_count ?? 0) > 0 ? 'Close session first' : 'Delete' }}">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div x-show="isEditModalOpen" x-cloak
             class="fixed inset-0 z-[100] flex items-end sm:items-center justify-center p-0 sm:p-4 bg-slate-900/50"
             x-transition.opacity>
            <div class="w-full max-w-md rounded-t-2xl sm:rounded-2xl bg-white shadow-2xl border border-slate-100 overflow-hidden"
                 @click.away="isEditModalOpen = false">
                <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100">
                    <h3 class="text-base font-bold text-slate-900">Edit counter</h3>
                    <button type="button" @click="isEditModalOpen = false" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form :action="editUrl" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="p-4 space-y-3">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-500 mb-1">Name</label>
                            <input type="text" name="name" x-model="editName" required
                                   class="w-full rounded-lg border-slate-200 text-sm py-2 px-3 font-semibold">
                        </div>
                        <label class="flex items-start gap-2.5 rounded-lg border border-slate-200 bg-slate-50 p-3 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" x-model="editStatus"
                                   class="mt-0.5 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <span>
                                <span class="block text-sm font-bold text-slate-900">Active</span>
                                <span class="block text-[11px] text-slate-500">Turn off if this register is unused.</span>
                            </span>
                        </label>
                    </div>
                    <div class="flex justify-end gap-2 px-4 py-3 bg-slate-50 border-t border-slate-100">
                        <button type="button" @click="isEditModalOpen = false"
                                class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600">Cancel</button>
                        <button type="submit" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-indigo-700">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
