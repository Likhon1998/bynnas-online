<x-cms-layout title="Navigation" subtitle="Header menu links. Use label “Categories” or “Brands” to enable mega-dropdowns." previewUrl="{{ route('home') }}">
    <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
        Tip: set URL to <code class="rounded bg-white px-1">/shop</code>, <code class="rounded bg-white px-1">/blog</code>, or a full path. Labels <strong>Categories</strong> / <strong>Brands</strong> open dropdowns automatically.
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        @foreach([
            ['key' => 'main_nav', 'title' => 'Main header nav', 'items' => $mainNav],
            ['key' => 'top_bar', 'title' => 'Top bar links (optional)', 'items' => $topBarNav],
        ] as $group)
            <div class="space-y-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-bold text-slate-900 mb-3">Add — {{ $group['title'] }}</h3>
                    <form method="POST" action="{{ route('cms.navigation.store') }}" class="space-y-3">
                        @csrf
                        <input type="hidden" name="location" value="{{ $group['key'] }}">
                        <div>
                            <label class="text-[11px] font-bold uppercase text-slate-500">Label</label>
                            <input name="label" required class="mt-1 w-full rounded-xl border-slate-200 text-sm" placeholder="Shop">
                        </div>
                        <div>
                            <label class="text-[11px] font-bold uppercase text-slate-500">URL</label>
                            <input name="url" required class="mt-1 w-full rounded-xl border-slate-200 text-sm font-mono" placeholder="/shop">
                        </div>
                        <div>
                            <label class="text-[11px] font-bold uppercase text-slate-500">Sort</label>
                            <input type="number" name="sort_order" value="{{ ($group['items']->max('sort_order') ?? 0) + 1 }}" min="0" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                        </div>
                        <label class="flex items-center gap-2 text-xs text-slate-600">
                            <input type="checkbox" name="is_active" value="1" class="rounded" checked> Active
                        </label>
                        <button class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-bold text-white">Add link</button>
                    </form>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-sm">
                    <div class="border-b border-slate-100 px-4 py-3 text-sm font-bold text-slate-900">{{ $group['title'] }}</div>
                    <table class="min-w-full text-sm">
                        <tbody class="divide-y divide-slate-100">
                            @forelse($group['items'] as $link)
                                <tr>
                                    <td class="px-4 py-3">
                                        <form method="POST" action="{{ route('cms.navigation.update', $link) }}" class="space-y-2">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="location" value="{{ $link->location }}">
                                            <input name="label" value="{{ $link->label }}" class="w-full rounded-lg border-slate-200 text-sm font-semibold">
                                            <input name="url" value="{{ $link->url }}" class="w-full rounded-lg border-slate-200 text-xs font-mono">
                                            <div class="flex flex-wrap items-center gap-3">
                                                <input type="number" name="sort_order" value="{{ $link->sort_order }}" class="w-20 rounded-lg border-slate-200 text-xs">
                                                <label class="flex items-center gap-1.5 text-xs text-slate-600">
                                                    <input type="checkbox" name="is_active" value="1" class="rounded" @checked($link->is_active)> Active
                                                </label>
                                                <button class="text-xs font-bold text-indigo-600">Save</button>
                                            </div>
                                        </form>
                                    </td>
                                    <td class="px-4 py-3 text-right align-top">
                                        <form method="POST" action="{{ route('cms.navigation.destroy', $link) }}" data-confirm="Delete this link?" data-confirm-title="Delete?" data-confirm-ok="Delete" data-confirm-tone="danger">
                                            @csrf @method('DELETE')
                                            <button class="text-xs font-bold text-rose-600">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td class="px-4 py-10 text-center text-slate-400">No links yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    </div>
</x-cms-layout>
