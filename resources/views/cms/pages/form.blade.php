<x-cms-layout title="{{ $page->exists ? 'Edit page' : 'New page' }}" subtitle="Published pages are available on the public website.">
    <form method="POST" action="{{ $page->exists ? route('cms.pages.update', $page) : route('cms.pages.store') }}" class="max-w-3xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
        @csrf
        @if($page->exists) @method('PUT') @endif
        <div>
            <label class="text-xs font-bold uppercase text-slate-500">Title</label>
            <input name="title" value="{{ old('title', $page->title) }}" class="mt-1 w-full rounded-xl border-slate-200" required>
        </div>
        <div>
            <label class="text-xs font-bold uppercase text-slate-500">Slug</label>
            <input name="slug" value="{{ old('slug', $page->slug) }}" class="mt-1 w-full rounded-xl border-slate-200" placeholder="auto-from-title">
        </div>
        <div>
            <label class="text-xs font-bold uppercase text-slate-500">Excerpt</label>
            <input name="excerpt" value="{{ old('excerpt', $page->excerpt) }}" class="mt-1 w-full rounded-xl border-slate-200">
        </div>
        <div>
            <label class="text-xs font-bold uppercase text-slate-500">Body</label>
            <textarea name="body" rows="10" class="mt-1 w-full rounded-xl border-slate-200">{{ old('body', $page->body) }}</textarea>
        </div>
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="text-xs font-bold uppercase text-slate-500">Meta title</label>
                <input name="meta_title" value="{{ old('meta_title', $page->meta_title) }}" class="mt-1 w-full rounded-xl border-slate-200">
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-slate-500">Sort order</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $page->sort_order ?? 0) }}" class="mt-1 w-full rounded-xl border-slate-200">
            </div>
        </div>
        <div>
            <label class="text-xs font-bold uppercase text-slate-500">Meta description</label>
            <textarea name="meta_description" rows="2" class="mt-1 w-full rounded-xl border-slate-200">{{ old('meta_description', $page->meta_description) }}</textarea>
        </div>
        <div class="flex flex-wrap gap-5">
            <label class="flex items-center gap-2 text-sm font-semibold"><input type="checkbox" name="is_published" value="1" class="rounded" @checked(old('is_published', $page->is_published))> Published</label>
            <label class="flex items-center gap-2 text-sm font-semibold"><input type="checkbox" name="show_in_footer" value="1" class="rounded" @checked(old('show_in_footer', $page->show_in_footer))> Show in website footer</label>
        </div>
        <div class="flex justify-end gap-2">
            <a href="{{ route('cms.pages.index') }}" class="rounded-xl border px-4 py-2.5 text-sm font-bold text-slate-600">Cancel</a>
            <button class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white">Save page</button>
        </div>
    </form>
</x-cms-layout>
