<x-supply-layout title="Suppliers" subtitle="Manage vendors for purchase orders." :action-url="route('supply.suppliers.create')" action-label="+ Add Supplier">
    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left min-w-[720px]">
                <thead class="bg-slate-900 text-white text-[11px] uppercase tracking-widest">
                    <tr>
                        <th class="p-4">Supplier</th>
                        <th class="p-4">Contact</th>
                        <th class="p-4">Phone</th>
                        <th class="p-4">City</th>
                        <th class="p-4">Status</th>
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 text-[13px]">
                    @forelse($suppliers as $supplier)
                        <tr class="hover:bg-slate-50/70">
                            <td class="p-4">
                                <p class="font-bold text-slate-900">{{ $supplier->name }}</p>
                                @if($supplier->business_type)
                                    <p class="text-[11px] text-slate-400 font-semibold mt-0.5">
                                        {{ \App\Models\Supplier::BUSINESS_TYPES[$supplier->business_type] ?? $supplier->business_type }}
                                    </p>
                                @endif
                            </td>
                            <td class="p-4 text-slate-600">
                                <p>{{ $supplier->contact_person ?: ($supplier->company ?: '—') }}</p>
                                @if($supplier->email)
                                    <p class="text-[11px] text-slate-400 mt-0.5">{{ $supplier->email }}</p>
                                @endif
                            </td>
                            <td class="p-4 text-slate-700 font-semibold">{{ $supplier->formattedPhone() ?: '—' }}</td>
                            <td class="p-4 text-slate-500">{{ $supplier->city ?: '—' }}</td>
                            <td class="p-4">
                                @if($supplier->is_active)
                                    <span class="inline-flex rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100 px-2.5 py-0.5 text-[10px] font-bold uppercase">Active</span>
                                @else
                                    <span class="inline-flex rounded-full bg-slate-100 text-slate-500 border border-slate-200 px-2.5 py-0.5 text-[10px] font-bold uppercase">Inactive</span>
                                @endif
                            </td>
                            <td class="p-4 text-right space-x-3">
                                <a href="{{ route('supply.suppliers.edit', $supplier) }}" class="text-[#1d68ff] font-bold text-[12px]">Edit</a>
                                <form action="{{ route('supply.suppliers.destroy', $supplier) }}" method="POST" class="inline" data-confirm="Delete supplier?" data-confirm-title="Delete?" data-confirm-ok="Delete" data-confirm-tone="danger">
                                    @csrf @method('DELETE')
                                    <button class="text-rose-600 font-bold text-[12px]">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-10 text-center text-gray-400 text-sm">
                                No suppliers yet.
                                <a href="{{ route('supply.suppliers.create') }}" class="text-[#1d68ff] font-bold ml-1">Add your first supplier</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-supply-layout>
