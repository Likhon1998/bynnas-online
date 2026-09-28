<x-app-layout>
    <div class="max-w-6xl mx-auto py-8 px-4">
        
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Role Management</h2>
            <a href="{{ route('roles.create') }}" class="bg-indigo-600 text-white px-5 py-2.5 rounded-lg font-bold hover:bg-indigo-700 transition shadow-sm">
                + Create New Role
            </a>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-200 text-green-700 px-4 py-3 rounded-lg font-bold mb-6">
                {{ session('success') }}
            </div>
        @endif
        
        @if(session('error'))
            <div class="bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded-lg font-bold mb-6">
                {{ session('error') }}
            </div>
        @endif

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="p-4 text-sm font-bold text-gray-700">Role Name</th>
                        <th class="p-4 text-sm font-bold text-gray-700">Assigned Permissions</th>
                        <th class="p-4 text-sm font-bold text-gray-700 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($roles as $role)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="p-4 font-bold text-gray-900 text-lg w-1/5">
                            {{ $role->name }}
                        </td>
                        <td class="p-4">
                            <div class="flex flex-wrap gap-2">
                                @foreach($role->permissions as $permission)
                                    <span class="bg-indigo-50 text-indigo-700 border border-indigo-200 px-2 py-1 rounded text-xs font-bold uppercase tracking-wider">
                                        {{ str_replace('_', ' ', $permission->name) }}
                                    </span>
                                @endforeach
                            </div>
                        </td>
                        <td class="p-4 text-right">
                            @if($role->name !== 'Shop Owner')
                                <a href="{{ route('roles.edit', $role->id) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-gray-300 rounded-md text-sm font-bold text-gray-700 hover:bg-gray-50 hover:text-indigo-600 transition shadow-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                    Edit
                                </a>
                            @else
                                <span class="text-xs text-gray-400 font-bold uppercase tracking-wider px-3">System Role</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    </div>
</x-app-layout>