@php
    $isWarehouse = str_contains(request()->route()->getName(), 'warehouses');
    $label = $isWarehouse ? 'Warehouse' : 'Store';
    $storeRoute = $isWarehouse ? 'supply.warehouses.store' : 'supply.stores.store';
    $updateRoute = $isWarehouse ? 'supply.warehouses.update' : 'supply.stores.update';
@endphp
<x-supply-layout :title="(isset($location) ? 'Edit ' : 'Add ') . $label" subtitle="Single-store mode: one {{ strtolower($label) }} per business.">
    <form method="POST" action="{{ isset($location) ? route($updateRoute, $location) : route($storeRoute) }}" class="bg-white rounded-2xl border p-6 space-y-4 max-w-2xl">
        @csrf @if(isset($location)) @method('PUT') @endif
        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Name</label>
            <input name="name" value="{{ old('name', $location->name ?? '') }}" class="w-full rounded-xl border-gray-200" required>
        </div>
        <div>
            <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Address</label>
            <textarea name="address" class="w-full rounded-xl border-gray-200" rows="2">{{ old('address', $location->address ?? '') }}</textarea>
        </div>
        @if(isset($location))
            <label class="flex items-center gap-2 text-sm font-semibold"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $location->is_active))> Active</label>
        @endif
        <button class="bg-indigo-600 text-white px-6 py-2.5 rounded-xl font-bold">Save</button>
    </form>
</x-supply-layout>
