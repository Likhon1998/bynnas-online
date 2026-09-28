<x-cms-layout title="FAQ" subtitle="Q&A accordion, categories, and help box on the public /faq page." actionUrl="{{ route('cms.faqs.create') }}" actionLabel="+ Add FAQ" previewUrl="{{ route('website.faqs') }}">

    <div class="mb-4 flex flex-wrap gap-2">
        <a href="{{ route('cms.faq-categories.index') }}" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Manage categories</a>
    </div>

    <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h3 class="text-sm font-bold text-slate-900">FAQ page settings</h3>
        <p class="text-xs text-slate-500 mt-0.5">Controls the hero and “Still need help?” box on /faq.</p>
        <form method="POST" action="{{ route('cms.faqs.settings') }}" class="mt-4 grid gap-4 md:grid-cols-2">
            @csrf
            <div>
                <label class="text-[11px] font-bold uppercase text-slate-500">Hero title</label>
                <input name="faq_hero_title" value="{{ old('faq_hero_title', $settings->faq_hero_title ?? 'Frequently Asked Questions') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm" placeholder="Frequently Asked Questions">
            </div>
            <div>
                <label class="text-[11px] font-bold uppercase text-slate-500">Help box title</label>
                <input name="faq_help_title" value="{{ old('faq_help_title', $settings->faq_help_title ?? 'Still Need Help?') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
            </div>
            <div class="md:col-span-2">
                <label class="text-[11px] font-bold uppercase text-slate-500">Hero subtitle</label>
                <textarea name="faq_hero_subtitle" rows="2" class="mt-1 w-full rounded-xl border-slate-200 text-sm" placeholder="Find quick answers…">{{ old('faq_hero_subtitle', $settings->faq_hero_subtitle) }}</textarea>
            </div>
            <div>
                <label class="text-[11px] font-bold uppercase text-slate-500">Help box text</label>
                <input name="faq_help_text" value="{{ old('faq_help_text', $settings->faq_help_text ?? "Can't find the answer you're looking for?") }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
            </div>
            <div>
                <label class="text-[11px] font-bold uppercase text-slate-500">Help button label</label>
                <input name="faq_help_button" value="{{ old('faq_help_button', $settings->faq_help_button ?? 'Contact Support') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
            </div>
            <div class="md:col-span-2 flex justify-end">
                <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-xs font-bold text-white hover:bg-slate-800">Save page settings</button>
            </div>
        </form>
    </div>

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-slate-200">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-slate-900 text-white text-[11px] uppercase tracking-wider">
                <tr>
                    <th class="px-5 py-3 text-left">Question</th>
                    <th class="px-5 py-3">Category</th>
                    <th class="px-5 py-3">Order</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($faqs as $faq)
                    <tr>
                        <td class="px-5 py-4 font-semibold text-slate-900">{{ $faq->question }}</td>
                        <td class="px-5 py-4 text-center text-slate-500">{{ $faq->faqCategory?->name ?? ($faq->category ?: '—') }}</td>
                        <td class="px-5 py-4 text-center">{{ $faq->sort_order }}</td>
                        <td class="px-5 py-4 text-center">
                            <span class="rounded-full px-2 py-1 text-xs font-bold {{ $faq->is_published ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $faq->is_published ? 'Live' : 'Hidden' }}</span>
                        </td>
                        <td class="px-5 py-4 text-right space-x-3">
                            <a href="{{ route('cms.faqs.edit', $faq) }}" class="font-semibold text-indigo-600">Edit</a>
                            <form action="{{ route('cms.faqs.destroy', $faq) }}" method="POST" class="inline" data-confirm="Delete FAQ?" data-confirm-title="Delete?" data-confirm-ok="Delete" data-confirm-tone="danger">@csrf @method('DELETE')<button class="font-semibold text-rose-600">Delete</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-12 text-center text-slate-400">No FAQs yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-cms-layout>
