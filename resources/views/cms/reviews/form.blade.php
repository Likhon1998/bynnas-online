<x-cms-layout title="{{ $review->exists ? 'Edit review' : 'New review' }}" subtitle="Featured + published reviews appear on the homepage." previewUrl="{{ route('home') }}">
    <form method="POST" action="{{ $review->exists ? route('cms.reviews.update', $review) : route('cms.reviews.store') }}" enctype="multipart/form-data" class="max-w-3xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
        @csrf
        @if($review->exists) @method('PUT') @endif
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="text-xs font-bold uppercase text-slate-500">Customer name</label>
                <input name="customer_name" value="{{ old('customer_name', $review->customer_name) }}" class="mt-1 w-full rounded-xl border-slate-200" required>
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-slate-500">Title / role</label>
                <input name="customer_title" value="{{ old('customer_title', $review->customer_title) }}" class="mt-1 w-full rounded-xl border-slate-200" placeholder="Verified buyer">
            </div>
        </div>
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="text-xs font-bold uppercase text-slate-500">Rating</label>
                <select name="rating" class="mt-1 w-full rounded-xl border-slate-200">
                    @for($i=5;$i>=1;$i--)
                        <option value="{{ $i }}" @selected(old('rating', $review->rating) == $i)>{{ $i }} star{{ $i>1?'s':'' }}</option>
                    @endfor
                </select>
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-slate-500">Related product (optional)</label>
                <select name="product_id" class="mt-1 w-full rounded-xl border-slate-200">
                    <option value="">— None —</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" @selected(old('product_id', $review->product_id) == $p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div>
            <label class="text-xs font-bold uppercase text-slate-500">Review</label>
            <textarea name="body" rows="5" class="mt-1 w-full rounded-xl border-slate-200" required>{{ old('body', $review->body) }}</textarea>
        </div>
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="text-xs font-bold uppercase text-slate-500">Avatar</label>
                <x-image-file-preview
                    name="avatar"
                    accept="image/*"
                    :existing="$review->avatar_path ? public_storage_url($review->avatar_path) : null"
                    preview-class="h-16 w-16 rounded-full object-cover border border-slate-200"
                />
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-slate-500">Sort order</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $review->sort_order ?? 0) }}" class="mt-1 w-full rounded-xl border-slate-200">
            </div>
        </div>
        <div class="flex flex-wrap gap-5">
            <label class="flex items-center gap-2 text-sm font-semibold"><input type="checkbox" name="is_published" value="1" class="rounded" @checked(old('is_published', $review->is_published))> Published</label>
            <label class="flex items-center gap-2 text-sm font-semibold"><input type="checkbox" name="is_featured" value="1" class="rounded" @checked(old('is_featured', $review->is_featured))> Featured on homepage</label>
        </div>
        <div class="flex justify-end gap-2">
            <a href="{{ route('cms.reviews.index') }}" class="rounded-xl border px-4 py-2.5 text-sm font-bold text-slate-600">Cancel</a>
            <button class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white">Save review</button>
        </div>
    </form>
</x-cms-layout>
