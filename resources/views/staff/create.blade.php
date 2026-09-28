<x-app-layout>
    <div class="max-w-3xl mx-auto pt-6 pb-12 px-4 sm:px-6">

        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <a href="{{ route('staff.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-indigo-600 hover:text-indigo-800 mb-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Back to staff
                </a>
                <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Add staff</h2>
                <p class="mt-1 text-sm text-slate-500">
                    @if(retail_enabled())
                        Create a login and assign a counter. Staff must enter opening cash each day before POS.
                    @else
                        Create a login and choose the role that controls what this team member can access.
                    @endif
                </p>
            </div>
        </div>

        @if (session('error'))
            <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
            <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-4">
                <p class="text-sm font-bold text-red-900 mb-2">Please fix the following:</p>
                <ul class="list-disc pl-5 text-sm font-medium text-red-700 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @php $needsCounter = retail_enabled(); @endphp

        @if($needsCounter && $counters->isEmpty())
            <div class="mb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3">
                <p class="text-sm font-semibold text-amber-900">Add at least one counter before creating staff.</p>
                <a href="{{ route('counters.index') }}" class="text-sm font-bold text-indigo-700 hover:underline whitespace-nowrap">Manage counters →</a>
            </div>
        @endif

        <form action="{{ route('staff.store') }}" method="POST" class="space-y-5">
            @csrf
            <input type="hidden" name="shop_id" value="{{ $shop->id }}">

            {{-- Shop (locked) --}}
            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-400 shrink-0">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path></svg>
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-400">Shop</p>
                    <p class="text-sm font-bold text-slate-800">{{ $shop->name }}</p>
                </div>
            </div>

            {{-- Profile --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-sm">
                <h3 class="text-sm font-bold text-slate-900 mb-1">Profile</h3>
                <p class="text-xs text-slate-500 mb-5">Login details this employee will use.</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label for="name" class="block text-xs font-bold text-slate-500 mb-1.5">Full name *</label>
                        <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                               placeholder="e.g. Karim Hossain"
                               class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2.5 px-3 font-semibold {{ $errors->has('name') ? 'border-red-400 bg-red-50' : '' }}">
                    </div>
                    <div>
                        <label for="email" class="block text-xs font-bold text-slate-500 mb-1.5">Email *</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" required
                               placeholder="staff@shop.com"
                               class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2.5 px-3 font-semibold {{ $errors->has('email') ? 'border-red-400 bg-red-50' : '' }}">
                    </div>
                    <div>
                        <label for="password" class="block text-xs font-bold text-slate-500 mb-1.5">Password *</label>
                        <input id="password" type="password" name="password" required minlength="8"
                               placeholder="Min. 8 characters"
                               class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2.5 px-3 font-semibold {{ $errors->has('password') ? 'border-red-400 bg-red-50' : '' }}">
                    </div>
                </div>
            </div>

            {{-- Access --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5 sm:p-6 shadow-sm">
                <h3 class="text-sm font-bold text-slate-900 mb-1">{{ $needsCounter ? 'POS access' : 'Access' }}</h3>
                <p class="text-xs text-slate-500 mb-5">{{ $needsCounter ? 'Role and counter.' : 'Role.' }} Admin accounts are not created here.</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="role" class="block text-xs font-bold text-slate-500 mb-1.5">Role *</label>
                        <select id="role" name="role" required
                                class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2.5 px-3 font-semibold {{ $errors->has('role') ? 'border-red-400 bg-red-50' : '' }}">
                            <option value="">Select role…</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}" @selected(old('role') == $role->name)>{{ ucfirst($role->name) }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if($needsCounter)
                    <div>
                        <label for="counter_id" class="block text-xs font-bold text-slate-500 mb-1.5">Assigned counter *</label>
                        <select id="counter_id" name="counter_id" required @disabled($counters->isEmpty())
                                class="w-full rounded-xl border-slate-200 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2.5 px-3 font-semibold disabled:opacity-50 {{ $errors->has('counter_id') ? 'border-red-400 bg-red-50' : '' }}">
                            <option value="">Select counter…</option>
                            @foreach($counters as $counter)
                                <option value="{{ $counter->id }}" @selected(old('counter_id') == $counter->id)>{{ $counter->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                </div>

                @if($needsCounter)
                <div class="mt-4 rounded-xl bg-indigo-50 border border-indigo-100 px-4 py-3 text-xs text-indigo-900 font-medium leading-relaxed">
                    After login, this staff member must enter the <strong>opening cash</strong> for their counter before using the POS that day.
                </div>
                @endif
            </div>

            <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-3 pt-1">
                <a href="{{ route('staff.index') }}"
                   class="inline-flex justify-center rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-50">
                    Cancel
                </a>
                <button type="submit"
                        @disabled($needsCounter && $counters->isEmpty())
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold px-6 py-2.5 shadow-sm transition disabled:opacity-50 disabled:pointer-events-none">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                    Create staff
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
