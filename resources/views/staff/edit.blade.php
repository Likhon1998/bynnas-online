<x-app-layout>
    <div class="max-w-4xl mx-auto pt-8 pb-12 px-4 sm:px-6 lg:px-8">
        
        <div class="mb-8 bg-white rounded-2xl p-6 shadow-sm border border-gray-100 flex items-center justify-between">
            <div>
                <a href="{{ route('staff.index') }}" class="text-sm font-bold text-indigo-500 hover:text-indigo-600 mb-2 inline-flex items-center gap-1 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Back to Staff
                </a>
                <h2 class="text-2xl font-black text-gray-900 tracking-tight flex items-center gap-3">
                    <div class="w-10 h-10 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center text-lg uppercase shadow-inner">
                        {{ substr($staff->name, 0, 1) }}
                    </div>
                    Edit Access: {{ $staff->name }}
                </h2>
            </div>
            <span class="bg-gray-100 text-gray-600 text-xs font-black px-3 py-1.5 rounded-lg uppercase tracking-widest border border-gray-200">
                {{ $staff->email }}
            </span>
        </div>

        @if (session('success'))
            <div class="mb-6 bg-emerald-50 border border-emerald-100 text-emerald-800 px-5 py-3 rounded-xl text-sm font-bold">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-5 py-3 rounded-xl text-sm font-bold">{{ session('error') }}</div>
        @endif

        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden ring-1 ring-gray-50 p-8">
            @if($staff->isAdminUser())
                <div class="space-y-6">
                    <div class="bg-violet-50 border border-violet-100 rounded-2xl p-5">
                        <p class="text-sm font-black text-violet-900 mb-1">Admin / Shop Owner</p>
                        <p class="text-sm text-violet-800 font-medium">
                            @if(retail_enabled())
                                This account cannot be assigned to a counter. Admins oversee all terminals and use POS without a fixed till.
                            @else
                                This account has full access. Its role cannot be changed here.
                            @endif
                        </p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <label class="block text-xs font-black text-gray-700 uppercase tracking-wider mb-2">System Role</label>
                            <div class="w-full bg-gray-100 border border-gray-200 text-gray-500 text-sm rounded-xl p-3.5 font-medium">
                                {{ $staff->roles->pluck('name')->first() ?? $staff->role }}
                            </div>
                        </div>
                        @if(retail_enabled())
                        <div>
                            <label class="block text-xs font-black text-gray-700 uppercase tracking-wider mb-2">Assigned Terminal</label>
                            <div class="w-full bg-gray-100 border border-gray-200 text-gray-500 text-sm rounded-xl p-3.5 font-medium italic">
                                None — not allowed for Admin
                            </div>
                        </div>
                        @endif
                    </div>
                    <div class="pt-4 border-t border-gray-100">
                        <a href="{{ route('staff.index') }}" class="px-6 py-3 rounded-xl font-bold text-gray-600 bg-gray-50 hover:bg-gray-100 transition border border-gray-200 inline-block">
                            Back to Staff
                        </a>
                    </div>
                </div>
            @else
            <form action="{{ route('staff.update', $staff->id) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                    
                    <div>
                        <label for="role" class="block text-xs font-black text-gray-700 uppercase tracking-wider mb-2">System Role</label>
                        <select name="role" id="role" class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-indigo-500 focus:border-indigo-500 block p-3.5 shadow-sm font-medium transition-colors" required>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}" {{ $staff->hasRole($role->name) || $staff->role === $role->name ? 'selected' : '' }}>
                                    {{ ucfirst($role->name) }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-2 text-xs text-gray-500 font-medium">Admin / Shop Owner are not assignable here.</p>
                    </div>

                    @if(retail_enabled())
                    <div>
                        <label for="counter_id" class="block text-xs font-black text-gray-700 uppercase tracking-wider mb-2">Assigned Terminal *</label>
                        <select name="counter_id" id="counter_id" required class="w-full bg-gray-50 border border-gray-200 text-gray-900 text-sm rounded-xl focus:ring-indigo-500 focus:border-indigo-500 block p-3.5 shadow-sm font-medium transition-colors">
                            <option value="">-- Select Counter --</option>
                            @foreach($counters as $counter)
                                <option value="{{ $counter->id }}" {{ old('counter_id', $staff->counter_id) == $counter->id ? 'selected' : '' }}>
                                    {{ $counter->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-2 text-xs text-indigo-600 font-bold">Required for POS access. Staff without a counter are locked out of the terminal.</p>
                    </div>
                    @endif
                </div>

                <div class="flex items-center justify-end gap-4 pt-6 border-t border-gray-100">
                    <a href="{{ route('staff.index') }}" class="px-6 py-3 rounded-xl font-bold text-gray-600 bg-gray-50 hover:bg-gray-100 transition border border-gray-200">
                        Cancel
                    </a>
                    <button type="submit" class="bg-indigo-600 text-white px-8 py-3 rounded-xl font-black text-sm hover:bg-indigo-500 transition shadow-lg shadow-indigo-200 active:scale-95">
                        Save Changes
                    </button>
                </div>
            </form>
            @endif
        </div>

    </div>
</x-app-layout>
