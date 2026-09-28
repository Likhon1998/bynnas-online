@php
    $iconOptions = ['cart','truck','refresh','shield','lock','tag','help','headset'];
@endphp
<x-cms-layout title="FAQ categories" subtitle="Categories appear in the FAQ sidebar with question counts." actionUrl="{{ route('cms.faqs.index') }}" actionLabel="← Back to FAQs" previewUrl="{{ route('website.faqs') }}">
    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-bold text-slate-900 mb-3">Add category</h3>
            <form method="POST" action="{{ route('cms.faq-categories.store') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="text-[11px] font-bold uppercase text-slate-500">Name</label>
                    <input name="name" required class="mt-1 w-full rounded-xl border-slate-200 text-sm" placeholder="e.g. Orders & Payments">
                </div>
                <div>
                    <label class="text-[11px] font-bold uppercase text-slate-500">Slug (optional)</label>
                    <input name="slug" class="mt-1 w-full rounded-xl border-slate-200 text-sm font-mono" placeholder="auto from name">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-[11px] font-bold uppercase text-slate-500">Icon</label>
                        <select name="icon" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                            @foreach($iconOptions as $icon)
                                <option value="{{ $icon }}">{{ ucfirst($icon) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-[11px] font-bold uppercase text-slate-500">Sort</label>
                        <input type="number" name="sort_order" value="0" min="0" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                    </div>
                </div>
                <button class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-bold text-white">Add category</button>
            </form>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-sm">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-[11px] uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-2.5 text-left">Category</th>
                        <th class="px-4 py-2.5">FAQs</th>
                        <th class="px-4 py-2.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($categories as $cat)
                        <tr>
                            <td class="px-4 py-3">
                                <form method="POST" action="{{ route('cms.faq-categories.update', $cat) }}" class="space-y-2">
                                    @csrf
                                    @method('PUT')
                                    <input name="name" value="{{ $cat->name }}" class="w-full rounded-lg border-slate-200 text-sm font-semibold">
                                    <div class="flex gap-2">
                                        <input name="slug" value="{{ $cat->slug }}" class="flex-1 rounded-lg border-slate-200 text-xs font-mono">
                                        <select name="icon" class="rounded-lg border-slate-200 text-xs">
                                            @foreach($iconOptions as $icon)
                                                <option value="{{ $icon }}" @selected(($cat->icon ?? 'help') === $icon)>{{ $icon }}</option>
                                            @endforeach
                                        </select>
                                        <input type="number" name="sort_order" value="{{ $cat->sort_order }}" class="w-16 rounded-lg border-slate-200 text-xs">
                                    </div>
                                    <label class="flex items-center gap-1.5 text-xs text-slate-600">
                                        <input type="checkbox" name="is_active" value="1" class="rounded" @checked($cat->is_active)> Active
                                    </label>
                                    <button class="text-xs font-bold text-indigo-600">Save</button>
                                </form>
                            </td>
                            <td class="px-4 py-3 text-center text-slate-500">{{ $cat->faqs_count }}</td>
                            <td class="px-4 py-3 text-right">
                                <form method="POST" action="{{ route('cms.faq-categories.destroy', $cat) }}" data-confirm="Delete category?" data-confirm-title="Delete?" data-confirm-ok="Delete" data-confirm-tone="danger">
                                    @csrf @method('DELETE')
                                    <button class="text-xs font-bold text-rose-600">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-4 py-10 text-center text-slate-400">No categories yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-cms-layout>
