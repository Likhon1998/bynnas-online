<x-cms-layout title="{{ $faq->exists ? 'Edit FAQ' : 'New FAQ' }}" subtitle="Published FAQs appear on /faq." previewUrl="{{ route('website.faqs') }}">
    <form method="POST" action="{{ $faq->exists ? route('cms.faqs.update', $faq) : route('cms.faqs.store') }}" class="max-w-3xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
        @csrf
        @if($faq->exists) @method('PUT') @endif
        <div>
            <label class="text-xs font-bold uppercase text-slate-500">Question</label>
            <input name="question" value="{{ old('question', $faq->question) }}" class="mt-1 w-full rounded-xl border-slate-200" required>
        </div>
        <div>
            <label class="text-xs font-bold uppercase text-slate-500">Answer</label>
            <textarea name="answer" rows="6" class="mt-1 w-full rounded-xl border-slate-200" required>{{ old('answer', $faq->answer) }}</textarea>
        </div>
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="text-xs font-bold uppercase text-slate-500">Category</label>
                <select name="category_id" class="mt-1 w-full rounded-xl border-slate-200">
                    <option value="">— Select —</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" @selected((string) old('category_id', $faq->category_id) === (string) $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-[11px] text-slate-400"><a href="{{ route('cms.faq-categories.index') }}" class="text-indigo-600 font-semibold">Manage categories</a></p>
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-slate-500">Sort order</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $faq->sort_order ?? 0) }}" class="mt-1 w-full rounded-xl border-slate-200">
            </div>
        </div>
        <label class="flex items-center gap-2 text-sm font-semibold"><input type="checkbox" name="is_published" value="1" class="rounded" @checked(old('is_published', $faq->is_published))> Published</label>
        <div class="flex justify-end gap-2">
            <a href="{{ route('cms.faqs.index') }}" class="rounded-xl border px-4 py-2.5 text-sm font-bold text-slate-600">Cancel</a>
            <button class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white">Save FAQ</button>
        </div>
    </form>
</x-cms-layout>
