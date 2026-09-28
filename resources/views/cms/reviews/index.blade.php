<x-cms-layout title="Reviews" subtitle="Customer testimonials. Featured reviews show on the homepage." actionUrl="{{ route('cms.reviews.create') }}" actionLabel="+ Add review" previewUrl="{{ route('home') }}">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-slate-200">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-slate-900 text-white text-[11px] uppercase tracking-wider">
                <tr>
                    <th class="px-5 py-3 text-left">Customer</th>
                    <th class="px-5 py-3">Rating</th>
                    <th class="px-5 py-3">Featured</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($reviews as $review)
                    <tr>
                        <td class="px-5 py-4">
                            <div class="font-semibold text-slate-900">{{ $review->customer_name }}</div>
                            <div class="text-xs text-slate-500 line-clamp-1">{{ $review->body }}</div>
                        </td>
                        <td class="px-5 py-4 text-center text-amber-500">{{ str_repeat('★', $review->rating) }}</td>
                        <td class="px-5 py-4 text-center">{{ $review->is_featured ? 'Yes' : 'No' }}</td>
                        <td class="px-5 py-4 text-center">
                            <span class="rounded-full px-2 py-1 text-xs font-bold {{ $review->is_published ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $review->is_published ? 'Live' : 'Hidden' }}</span>
                        </td>
                        <td class="px-5 py-4 text-right space-x-3">
                            <a href="{{ route('cms.reviews.edit', $review) }}" class="font-semibold text-indigo-600">Edit</a>
                            <form action="{{ route('cms.reviews.destroy', $review) }}" method="POST" class="inline" data-confirm="Delete review?" data-confirm-title="Delete?" data-confirm-ok="Delete" data-confirm-tone="danger">@csrf @method('DELETE')<button class="font-semibold text-rose-600">Delete</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-12 text-center text-slate-400">No reviews yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $reviews->links() }}</div>
    </div>
</x-cms-layout>
