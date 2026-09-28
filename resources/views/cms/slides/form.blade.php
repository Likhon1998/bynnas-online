<x-cms-layout
    title="{{ $slide->exists ? 'Edit poster' : 'New homepage poster' }}"
    subtitle="Full-design poster banners on the homepage. Upload the complete designed image — text/layout should be inside the poster art."
    previewUrl="{{ route('home') }}"
>
    <form method="POST"
          action="{{ $slide->exists ? route('cms.slides.update', $slide) : route('cms.slides.store') }}"
          enctype="multipart/form-data"
          class="max-w-3xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4"
          x-data="{ saving: false }"
          @submit="saving = true">
        @csrf
        @if($slide->exists) @method('PUT') @endif

        <div class="rounded-xl bg-slate-50 border border-slate-200 p-4 text-sm text-slate-600 leading-relaxed">
            <p class="font-semibold text-slate-800">Poster size guide</p>
            <ul class="mt-2 list-disc pl-5 space-y-1 text-[13px]">
                <li><strong>Best size:</strong> <strong>1920 × 640</strong> px (ratio <strong>3:1</strong>)</li>
                <li><strong>Also good:</strong> 1536×512, 2400×800 (same 3:1 ratio)</li>
                <li><strong>Format:</strong> JPG or WebP (prefer JPG — large PNGs upload slowly)</li>
                <li><strong>Target file size:</strong> under ~500 KB (auto-optimized on select)</li>
                <li>Design text/layout inside the image — the site shows it edge-to-edge with no crop gaps.</li>
            </ul>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="text-xs font-bold uppercase text-slate-500">Poster image {{ $slide->exists ? '' : '*' }}</label>
                <x-image-file-preview
                    name="image"
                    :existing="$slide->image_path ? public_storage_url($slide->image_path) : null"
                    accept="image/jpeg,image/png,image/webp,image/gif"
                    :required="! $slide->exists"
                    preview-class="h-36 w-full rounded-xl object-cover border border-slate-200 bg-slate-50"
                />
                @error('image') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="md:col-span-2">
                <label class="text-xs font-bold uppercase text-slate-500">Title (admin / alt text)</label>
                <input name="title" value="{{ old('title', $slide->title) }}" class="mt-1 w-full rounded-xl border-slate-200" required placeholder="Summer sale poster">
            </div>
            <div class="md:col-span-2">
                <label class="text-xs font-bold uppercase text-slate-500">Click URL (optional)</label>
                <input name="button_url" value="{{ old('button_url', $slide->button_url) }}" class="mt-1 w-full rounded-xl border-slate-200" placeholder="/shop or https://...">
                <p class="mt-1 text-[11px] text-slate-400">If set, the whole poster is clickable.</p>
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-slate-500">Sort order</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $slide->sort_order ?? 0) }}" class="mt-1 w-full rounded-xl border-slate-200">
            </div>
            <label class="flex items-center gap-2 text-sm font-semibold text-slate-700 self-end pb-2">
                <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300" @checked(old('is_active', $slide->is_active ?? true))>
                Active on website
            </label>
        </div>

        {{-- Keep optional legacy fields hidden so existing rows stay valid --}}
        <input type="hidden" name="badge_text" value="{{ old('badge_text', $slide->badge_text) }}">
        <input type="hidden" name="description" value="{{ old('description', $slide->description) }}">
        <input type="hidden" name="button_text" value="{{ old('button_text', $slide->button_text ?: 'Shop Now') }}">
        <input type="hidden" name="learn_more_text" value="{{ old('learn_more_text', $slide->learn_more_text ?: 'Learn More') }}">
        <input type="hidden" name="learn_more_url" value="{{ old('learn_more_url', $slide->learn_more_url) }}">

        <div class="flex justify-end gap-2 pt-2">
            <a href="{{ route('cms.slides.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-600">Cancel</a>
            <button type="submit" class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white disabled:opacity-60" :disabled="saving">
                <span x-show="!saving">Save poster</span>
                <span x-show="saving" x-cloak>Saving…</span>
            </button>
        </div>
    </form>
</x-cms-layout>
