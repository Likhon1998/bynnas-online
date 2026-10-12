<x-cms-layout
    title="Offer Popup"
    subtitle="Promotional image that pops up when shoppers visit the storefront. Only one active popup shows at a time."
    actionUrl="{{ route('cms.promo-popups.create') }}"
    actionLabel="+ Add offer"
    previewUrl="{{ route('home') }}"
>
    <div class="mb-4 rounded-2xl border border-indigo-100 bg-indigo-50/70 px-4 py-3 text-sm text-indigo-900">
        Upload a designed offer image, turn <strong>Active</strong> on, then open the storefront — the popup appears as an overlay. Visitors can close it with ×.
    </div>

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-slate-200">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-slate-900 text-white text-[11px] uppercase tracking-wider">
                <tr>
                    <th class="px-5 py-3 text-left">Offer</th>
                    <th class="px-5 py-3">Order</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($banners as $banner)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="h-20 w-28 rounded-xl bg-slate-100 overflow-hidden border border-slate-200 shrink-0">
                                    @if($banner->image_path)
                                        <img src="{{ public_storage_url($banner->image_path) }}" class="h-full w-full object-cover" alt="">
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <div class="font-semibold text-slate-900">{{ $banner->title }}</div>
                                    @if($banner->button_url)
                                        <div class="text-xs text-slate-400 truncate max-w-[260px]">{{ $banner->button_url }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-center">{{ $banner->sort_order }}</td>
                        <td class="px-5 py-4 text-center">
                            <form action="{{ route('cms.promo-popups.toggle', $banner) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit"
                                        class="rounded-full px-2.5 py-1 text-xs font-bold {{ $banner->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                    {{ $banner->is_active ? 'Active' : 'Hidden' }}
                                </button>
                            </form>
                        </td>
                        <td class="px-5 py-4 text-right space-x-3 whitespace-nowrap">
                            <a href="{{ route('cms.promo-popups.edit', $banner) }}" class="font-semibold text-indigo-600">Edit</a>
                            <form action="{{ route('cms.promo-popups.destroy', $banner) }}" method="POST" class="inline"
                                  data-confirm="Delete this offer popup?"
                                  data-confirm-title="Delete?"
                                  data-confirm-ok="Delete"
                                  data-confirm-tone="danger">
                                @csrf @method('DELETE')
                                <button class="font-semibold text-rose-600">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-5 py-12 text-center text-slate-400">
                            No offer popups yet. Upload an advertisement image to show when visitors open the site.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-cms-layout>
