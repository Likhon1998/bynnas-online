<x-cms-layout title="Courier services" subtitle="Add Pathao, Steadfast, or any delivery partner. Online orders pick one when shipping — then you can see how much cash each service owes you." actionUrl="{{ route('cms.delivery.edit') }}" actionLabel="← Delivery settings">
    @if(session('success'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="mb-3 text-sm font-bold text-slate-900">Add courier service</h3>
            <form method="POST" action="{{ route('cms.couriers.store') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="text-[11px] font-bold uppercase text-slate-500">Name *</label>
                    <input name="name" required value="{{ old('name') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm" placeholder="e.g. Pathao, Steadfast">
                    <x-input-error class="mt-1" :messages="$errors->get('name')" />
                </div>
                <div>
                    <label class="text-[11px] font-bold uppercase text-slate-500">Phone / contact</label>
                    <input name="phone" value="{{ old('phone') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm" placeholder="Optional">
                </div>
                <div>
                    <label class="text-[11px] font-bold uppercase text-slate-500">Notes</label>
                    <input name="notes" value="{{ old('notes') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm" placeholder="Optional">
                </div>
                <div>
                    <label class="text-[11px] font-bold uppercase text-slate-500">Sort</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                </div>
                <button class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-bold text-white">Add service</button>
            </form>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-[11px] uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-2.5 text-left">Service</th>
                        <th class="px-4 py-2.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($services as $service)
                        <tr class="{{ $service->is_active ? '' : 'bg-slate-50 opacity-70' }}">
                            <td class="px-4 py-3">
                                <form method="POST" action="{{ route('cms.couriers.update', $service) }}" class="space-y-2">
                                    @csrf
                                    @method('PUT')
                                    <input name="name" value="{{ $service->name }}" class="w-full rounded-lg border-slate-200 text-sm font-semibold">
                                    <div class="grid grid-cols-2 gap-2">
                                        <input name="phone" value="{{ $service->phone }}" placeholder="Phone" class="rounded-lg border-slate-200 text-xs">
                                        <input type="number" name="sort_order" value="{{ $service->sort_order }}" class="rounded-lg border-slate-200 text-xs">
                                    </div>
                                    <input name="notes" value="{{ $service->notes }}" placeholder="Notes" class="w-full rounded-lg border-slate-200 text-xs">
                                    <label class="flex items-center gap-1.5 text-xs text-slate-600">
                                        <input type="checkbox" name="is_active" value="1" class="rounded" @checked($service->is_active)> Active
                                    </label>
                                    <button class="text-xs font-bold text-indigo-600">Save</button>
                                </form>
                            </td>
                            <td class="px-4 py-3 text-right align-top">
                                <form method="POST" action="{{ route('cms.couriers.destroy', $service) }}" data-confirm="Remove this courier service?" data-confirm-title="Remove?" data-confirm-ok="Remove" data-confirm-tone="danger">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-xs font-bold text-rose-600">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="px-4 py-10 text-center text-slate-400">No courier services yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-cms-layout>
