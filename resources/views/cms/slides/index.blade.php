<x-cms-layout
    title="Home Posters"
    subtitle="Compact full-design banners on the homepage. Add multiple posters — they rotate automatically."
    actionUrl="{{ route('cms.slides.create') }}"
    actionLabel="+ Add poster"
    previewUrl="{{ route('home') }}"
>
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-slate-200">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-slate-900 text-white text-[11px] uppercase tracking-wider">
                <tr>
                    <th class="px-5 py-3 text-left">Poster</th>
                    <th class="px-5 py-3">Order</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($slides as $slide)
                    <tr class="hover:bg-slate-50">
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-3">
                                <div class="h-14 w-36 rounded-lg bg-slate-100 overflow-hidden border">
                                    @if($slide->image_path)
                                        <img src="{{ public_storage_url($slide->image_path) }}" class="h-full w-full object-cover" alt="">
                                    @endif
                                </div>
                                <div>
                                    <div class="font-semibold text-slate-900">{{ $slide->title }}</div>
                                    @if($slide->button_url)
                                        <div class="text-xs text-slate-400 truncate max-w-[220px]">{{ $slide->button_url }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-center">{{ $slide->sort_order }}</td>
                        <td class="px-5 py-4 text-center">
                            @if($slide->is_active)
                                <span class="rounded-full bg-emerald-50 px-2 py-1 text-xs font-bold text-emerald-700">Active</span>
                            @else
                                <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-bold text-slate-500">Hidden</span>
                            @endif
                        </td>
                        <td class="px-5 py-4 text-right space-x-3">
                            <a href="{{ route('cms.slides.edit', $slide) }}" class="font-semibold text-indigo-600">Edit</a>
                            <form action="{{ route('cms.slides.destroy', $slide) }}" method="POST" class="inline" data-confirm="Delete this poster?" data-confirm-title="Delete?" data-confirm-ok="Delete" data-confirm-tone="danger">
                                @csrf @method('DELETE')
                                <button class="font-semibold text-rose-600">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-12 text-center text-slate-400">No posters yet. Upload a full-design banner image to show on the homepage.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-cms-layout>
