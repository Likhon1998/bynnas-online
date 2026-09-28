<x-cms-layout title="Pages" subtitle="Static pages like About, Shipping, Privacy — linked in the website footer when enabled." actionUrl="{{ route('cms.pages.create') }}" actionLabel="+ New page" previewUrl="{{ route('home') }}">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-slate-200">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-slate-900 text-white text-[11px] uppercase tracking-wider">
                <tr>
                    <th class="px-5 py-3 text-left">Title</th>
                    <th class="px-5 py-3">URL</th>
                    <th class="px-5 py-3">Footer</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($pages as $page)
                    <tr>
                        <td class="px-5 py-4 font-semibold text-slate-900">{{ $page->title }}</td>
                        <td class="px-5 py-4 text-center"><a class="text-indigo-600" href="{{ route('website.page', $page->slug) }}" target="_blank">/page/{{ $page->slug }}</a></td>
                        <td class="px-5 py-4 text-center">{{ $page->show_in_footer ? 'Yes' : 'No' }}</td>
                        <td class="px-5 py-4 text-center">
                            <span class="rounded-full px-2 py-1 text-xs font-bold {{ $page->is_published ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $page->is_published ? 'Published' : 'Draft' }}</span>
                        </td>
                        <td class="px-5 py-4 text-right space-x-3">
                            <a href="{{ route('cms.pages.edit', $page) }}" class="font-semibold text-indigo-600">Edit</a>
                            <form action="{{ route('cms.pages.destroy', $page) }}" method="POST" class="inline" data-confirm="Delete page?" data-confirm-title="Delete?" data-confirm-ok="Delete" data-confirm-tone="danger">@csrf @method('DELETE')<button class="font-semibold text-rose-600">Delete</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-12 text-center text-slate-400">No pages yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-cms-layout>
