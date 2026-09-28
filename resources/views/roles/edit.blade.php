<x-app-layout>
    <div class="max-w-4xl mx-auto py-8 px-4">
        <h2 class="text-2xl font-bold text-gray-800 mb-6">Edit Role: {{ $role->name }}</h2>

        <form action="{{ route('roles.update', $role->id) }}" method="POST" class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
            @csrf
            @method('PUT') <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-2">Role Name</label>
                <input type="text" name="name" value="{{ old('name', $role->name) }}" required class="w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 font-medium">
                @error('name')
                    <p class="text-red-500 text-xs font-bold mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-3">Assign Permissions</label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50 p-4 rounded-lg border border-gray-100">
                    
                    @foreach($permissions as $permission)
                        <label class="inline-flex items-center p-3 bg-white border border-gray-200 rounded-lg cursor-pointer hover:border-indigo-500 transition-colors shadow-sm">
                            <input type="checkbox" 
                                   name="permissions[]" 
                                   value="{{ $permission->name }}" 
                                   {{ in_array($permission->name, $rolePermissions) ? 'checked' : '' }}
                                   class="rounded text-indigo-600 focus:ring-indigo-500 border-gray-300 w-5 h-5 cursor-pointer">
                            <span class="ml-3 text-sm font-bold text-gray-700 capitalize">{{ str_replace('_', ' ', $permission->name) }}</span>
                        </label>
                    @endforeach

                </div>
                @error('permissions')
                    <p class="text-red-500 text-xs font-bold mt-2">You must select at least one permission.</p>
                @enderror
            </div>

            <div class="flex justify-end gap-3 border-t border-gray-100 pt-6 mt-2">
                <a href="{{ route('roles.index') }}" class="px-6 py-2.5 bg-gray-100 text-gray-700 rounded-lg font-bold hover:bg-gray-200 transition">Cancel</a>
                <button type="submit" class="px-6 py-2.5 bg-indigo-600 text-white rounded-lg font-bold hover:bg-indigo-700 transition shadow-sm">Update Role</button>
            </div>
        </form>
    </div>
</x-app-layout>