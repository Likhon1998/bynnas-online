<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Brands') }}</h2>
            <a href="{{ route('brands.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 transition">
                + Add Brand
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 border-l-4 border-green-500 text-green-700">{{ session('success') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-100">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Brand</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Products</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($brands as $brand)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <div class="h-10 w-10 rounded-lg bg-gray-50 border border-gray-100 flex items-center justify-center overflow-hidden">
                                                @if($brand->logo_path)
                                                    <img src="{{ public_storage_url($brand->logo_path) }}" alt="{{ $brand->name }}" class="h-full w-full object-contain">
                                                @else
                                                    <span class="text-xs font-bold text-gray-400">{{ strtoupper(substr($brand->name, 0, 2)) }}</span>
                                                @endif
                                            </div>
                                            <span class="text-sm font-medium text-gray-900">{{ $brand->name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $brand->products_count }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($brand->is_active)
                                            <span class="px-2 py-1 text-xs font-bold bg-emerald-50 text-emerald-600 rounded">Active</span>
                                        @else
                                            <span class="px-2 py-1 text-xs font-bold bg-gray-100 text-gray-500 rounded">Hidden</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                                        <a href="{{ route('brands.edit', $brand) }}" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                                        <form action="{{ route('brands.destroy', $brand) }}" method="POST" class="inline-block" data-confirm="Delete this brand?" data-confirm-title="Delete?" data-confirm-ok="Delete" data-confirm-tone="danger">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-10 text-center text-gray-500">No brands yet. Add your first brand!</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
