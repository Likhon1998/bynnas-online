<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Staff Management') }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">Manage your employees, roles, and system access.</p>
            </div>
            
            <div class="flex items-center gap-3">
                <a href="{{ route('staff.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs py-2 px-4 rounded-lg shadow-sm transition-all flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Add New Staff
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6" x-data="{ show: true }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('success'))
                <div x-show="show" x-init="setTimeout(() => show = false, 5000)" class="bg-green-500 text-white p-3 rounded-lg shadow-sm font-bold flex justify-between items-center text-sm">
                    {{ session('success') }}
                    <button @click="show = false" class="text-white hover:text-green-200 text-xl leading-none">&times;</button>
                </div>
            @endif

            @if (session('error'))
                <div x-show="show" x-init="setTimeout(() => show = false, 8000)" class="bg-red-500 text-white p-3 rounded-lg shadow-sm font-bold flex justify-between items-center text-sm">
                    {{ session('error') }}
                    <button @click="show = false" class="text-white hover:text-red-200 text-xl leading-none">&times;</button>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-100">
                <div class="p-4 border-b border-gray-100 bg-slate-50 flex items-center justify-between">
                    <h3 class="font-bold text-gray-700 text-sm flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        Employee Directory ({{ $shop->name ?? 'Your Shop' }})
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider">Employee Profile</th>
                                <th class="px-6 py-3 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider">Role & Permissions</th>
                                @if(retail_enabled())
                                <th class="px-6 py-3 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider">Assigned Counter</th>
                                @endif
                                <th class="px-6 py-3 text-center text-[11px] font-bold text-gray-500 uppercase tracking-wider">System Access</th>
                                <th class="px-6 py-3 text-right text-[11px] font-bold text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            @forelse($staff as $member)
                            <tr class="hover:bg-gray-50 transition-colors {{ $member->is_suspended ? 'bg-red-50/20' : '' }}">
                                
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-9 w-9 rounded-full bg-slate-100 flex items-center justify-center text-slate-600 font-bold text-sm border border-slate-200">
                                            {{ substr($member->name, 0, 1) }}
                                        </div>
                                        <div class="ml-3">
                                            <div class="text-sm font-bold text-gray-900 flex items-center gap-2">
                                                {{ $member->name }}
                                                @if($member->id === Auth::id())
                                                    <span class="bg-indigo-100 text-indigo-700 text-[9px] px-1.5 py-0.5 rounded font-black uppercase tracking-widest">You</span>
                                                @endif
                                            </div>
                                            <div class="text-[11px] text-gray-500">{{ $member->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $roleName = $member->roles->pluck('name')->first() ?? $member->role ?? 'Unassigned';
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold 
                                        {{ $roleName === 'Shop Owner' || $roleName === 'Admin' ? 'bg-purple-100 text-purple-800 border border-purple-200' : 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                                        @if($roleName === 'Shop Owner' || $roleName === 'Admin')
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                        @endif
                                        {{ $roleName }}
                                    </span>
                                </td>
                                
                                @if(retail_enabled())
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($member->isAdminUser())
                                        <span class="text-xs font-bold text-violet-700 bg-violet-50 px-2.5 py-1 rounded border border-violet-100 inline-flex items-center gap-1.5">
                                            No counter (admin)
                                        </span>
                                    @elseif($member->counter)
                                        <div class="text-xs font-bold text-indigo-700 bg-indigo-50 px-2.5 py-1 rounded border border-indigo-100 inline-flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                            {{ $member->counter->name }}
                                        </div>
                                    @else
                                        <span class="text-xs font-bold text-amber-700 bg-amber-50 px-2.5 py-1 rounded border border-amber-100 inline-flex items-center gap-1">
                                            POS Locked — assign counter
                                        </span>
                                    @endif
                                </td>
                                @endif
                                
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @if($member->is_suspended)
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-red-100 text-red-700 border border-red-200">
                                            <span class="w-1.5 h-1.5 bg-red-500 rounded-full mr-1.5"></span>
                                            Suspended
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-widest bg-emerald-100 text-emerald-700 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full mr-1.5 animate-pulse"></span>
                                            Active
                                        </span>
                                    @endif
                                </td>
                                
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end gap-2 items-center">
                                        
                                        <a href="{{ route('staff.edit', $member->id) }}" class="text-slate-500 hover:text-indigo-600 bg-white hover:bg-indigo-50 border border-slate-200 hover:border-indigo-200 px-3 py-1.5 rounded text-xs font-bold transition flex items-center gap-1.5 shadow-sm">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                            Edit
                                        </a>
                                        
                                        @if($member->id !== Auth::id() && !$member->isAdminUser())
                                            
                                            <form action="{{ route('staff.toggle-suspend', $member->id) }}" method="POST" class="inline"
                                                  data-confirm="Are you sure you want to {{ $member->is_suspended ? 'reactivate' : 'suspend' }} this employee?"
                                                  data-confirm-title="{{ $member->is_suspended ? 'Reactivate?' : 'Suspend?' }}"
                                                  data-confirm-ok="{{ $member->is_suspended ? 'Reactivate' : 'Suspend' }}"
                                                  data-confirm-tone="{{ $member->is_suspended ? 'primary' : 'warning' }}">
                                                @csrf
                                                @if($member->is_suspended)
                                                    <button type="submit" class="text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 px-3 py-1.5 rounded text-xs font-bold transition inline-flex items-center gap-1.5 shadow-sm" title="Restore system access">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                                        Activate
                                                    </button>
                                                @else
                                                    <button type="submit" class="text-amber-700 bg-amber-50 hover:bg-amber-100 border border-amber-200 px-3 py-1.5 rounded text-xs font-bold transition inline-flex items-center gap-1.5 shadow-sm" title="Temporarily block system access">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                                        Suspend
                                                    </button>
                                                @endif
                                            </form>

                                            <form action="{{ route('staff.destroy', $member->id) }}" method="POST" class="inline" data-confirm="WARNING: This will permanently delete this staff member. Are you absolutely sure?" data-confirm-title="Delete?" data-confirm-ok="Delete" data-confirm-tone="danger">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-100 hover:border-rose-200 px-3 py-1.5 rounded text-xs font-bold transition flex items-center justify-center shadow-sm" title="Permanently delete account">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </form>
                                            
                                        @else
                                            <span class="inline-flex items-center gap-1.5 text-[10px] font-bold text-slate-400 bg-slate-50 px-3 py-1.5 rounded border border-slate-100 cursor-not-allowed">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                                Protected
                                            </span>
                                        @endif

                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-3">
                                            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                        </div>
                                        <p class="font-bold text-gray-600">No staff members found</p>
                                        <p class="text-xs text-gray-400 mt-1">Click 'Add New Staff' to create employee accounts.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            
        </div>
    </div>
</x-app-layout>