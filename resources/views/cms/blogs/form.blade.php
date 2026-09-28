<x-cms-layout title="{{ $blog->exists ? 'Edit blog post' : 'New blog post' }}" subtitle="Posts power the storefront blog page — featured, categories, search, and sidebar." previewUrl="{{ route('website.blogs') }}">
    <form method="POST" action="{{ $blog->exists ? route('cms.blogs.update', $blog) : route('cms.blogs.store') }}" enctype="multipart/form-data" class="max-w-3xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
        @csrf
        @if($blog->exists) @method('PUT') @endif

        <div>
            <label class="text-xs font-bold uppercase text-slate-500">Title</label>
            <input name="title" value="{{ old('title', $blog->title) }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm" required>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="text-xs font-bold uppercase text-slate-500">Slug</label>
                <input name="slug" value="{{ old('slug', $blog->slug) }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm font-mono">
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-slate-500">Author</label>
                <input name="author_name" value="{{ old('author_name', $blog->author_name) }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="text-xs font-bold uppercase text-slate-500">Category</label>
                <select name="category_id" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                    <option value="">No category</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" @selected(old('category_id', $blog->category_id) == $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>
                @if($categories->isEmpty())
                    <p class="mt-1 text-xs text-amber-600 font-medium">No categories yet — <a href="{{ route('cms.blog-categories.index') }}" class="underline">add some here</a>.</p>
                @else
                    <p class="mt-1 text-[11px] text-slate-400">
                        {{ $categories->count() }} available ·
                        <a href="{{ route('cms.blog-categories.index') }}" class="text-indigo-600 font-semibold hover:underline">Manage categories</a>
                    </p>
                @endif
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-slate-500">Publish date</label>
                <input type="datetime-local" name="published_at" value="{{ old('published_at', optional($blog->published_at)->format('Y-m-d\TH:i')) }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                <p class="mt-1 text-[11px] text-slate-400">Leave blank to publish immediately. Use a future date only to schedule.</p>
            </div>
        </div>

        <div>
            <label class="text-xs font-bold uppercase text-slate-500">Reading time (minutes)</label>
            <input type="number" name="reading_time" min="1" max="120" value="{{ old('reading_time', $blog->reading_time) }}" class="mt-1 w-full max-w-xs rounded-xl border-slate-200 text-sm" placeholder="e.g. 5">
            <p class="mt-1 text-[11px] text-slate-400">Shown on cards as “5 min read”. Leave blank to auto-estimate from body.</p>
        </div>

        <div>
            <label class="text-xs font-bold uppercase text-slate-500">Excerpt</label>
            <textarea name="excerpt" rows="2" class="mt-1 w-full rounded-xl border-slate-200 text-sm" placeholder="Short summary shown on cards…">{{ old('excerpt', $blog->excerpt) }}</textarea>
        </div>

        <div>
            <label class="text-xs font-bold uppercase text-slate-500">Body</label>
            <textarea name="body" rows="12" class="mt-1 w-full rounded-xl border-slate-200 text-sm">{{ old('body', $blog->body) }}</textarea>
        </div>

        <div>
            <label class="text-xs font-bold uppercase text-slate-500">Cover image</label>
            <x-image-file-preview
                name="cover"
                accept="image/*"
                :existing="$blog->cover_image ? public_storage_url($blog->cover_image) : null"
                preview-class="h-28 max-w-full rounded-xl object-cover border border-slate-100"
            />
        </div>

        <div class="flex flex-wrap gap-4 pt-1">
            <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                <input type="checkbox" name="is_published" value="1" class="rounded border-slate-300 text-indigo-600" @checked(old('is_published', $blog->is_published ?? true))>
                Published on website
            </label>
            <label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                <input type="checkbox" name="is_featured" value="1" class="rounded border-slate-300 text-indigo-600" @checked(old('is_featured', $blog->is_featured ?? false))>
                Featured / popular priority
            </label>
        </div>

        @if($blog->exists)
            <p class="text-xs text-slate-400">Views: {{ number_format($blog->views_count ?? 0) }}</p>
        @endif

        <div class="flex justify-end gap-2 pt-2">
            <a href="{{ route('cms.blogs.index') }}" class="rounded-xl border px-4 py-2.5 text-sm font-bold text-slate-600">Cancel</a>
            <button class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white">Save post</button>
        </div>
    </form>
</x-cms-layout>
