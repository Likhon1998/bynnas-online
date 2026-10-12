<x-cms-layout
    title="{{ $banner->exists ? 'Edit offer popup' : 'New offer popup' }}"
    subtitle="Upload the full promotional image. Shoppers see it as an overlay; they can close it with ×."
    previewUrl="{{ route('home') }}"
>
    <form method="POST"
          action="{{ $banner->exists ? route('cms.promo-popups.update', $banner) : route('cms.promo-popups.store') }}"
          enctype="multipart/form-data"
          class="max-w-3xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4"
          x-data="{ saving: false }"
          @submit="saving = true">
        @csrf
        @if($banner->exists) @method('PUT') @endif

        <div class="rounded-xl bg-slate-50 border border-slate-200 p-4 text-sm text-slate-600 leading-relaxed">
            <p class="font-semibold text-slate-800">Image size guide</p>
            <ul class="mt-2 list-disc pl-5 space-y-1 text-[13px]">
                <li><strong>Best size:</strong> <strong>900 × 1100</strong> px (portrait) or <strong>1000 × 1000</strong> (square)</li>
                <li><strong>Also good:</strong> 800×1000, 1080×1350</li>
                <li><strong>Format:</strong> JPG or WebP under ~600 KB</li>
                <li>Put the offer text inside the image — the popup shows the artwork with a close button.</li>
                <li>Only one popup can be <strong>Active</strong> at a time; activating this one hides the others.</li>
            </ul>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <div class="md:col-span-2">
                <label class="text-xs font-bold uppercase text-slate-500">Offer image {{ $banner->exists ? '' : '*' }}</label>
                <x-image-file-preview
                    name="image"
                    :existing="$banner->image_path ? public_storage_url($banner->image_path) : null"
                    accept="image/jpeg,image/png,image/webp,image/gif"
                    :required="! $banner->exists"
                    preview-class="mx-auto h-64 w-auto max-w-full rounded-xl object-contain border border-slate-200 bg-slate-50"
                />
                @error('image') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="md:col-span-2">
                <label class="text-xs font-bold uppercase text-slate-500">Title (admin label / alt text)</label>
                <input name="title" value="{{ old('title', $banner->title) }}" class="mt-1 w-full rounded-xl border-slate-200" required placeholder="Eid mega sale">
            </div>

            <div>
                <label class="text-xs font-bold uppercase text-slate-500">Button label (optional)</label>
                <input name="button_text" value="{{ old('button_text', $banner->button_text ?: 'Shop Now') }}" class="mt-1 w-full rounded-xl border-slate-200" placeholder="Shop Now">
            </div>
            <div>
                <label class="text-xs font-bold uppercase text-slate-500">Sort order</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $banner->sort_order ?? 0) }}" class="mt-1 w-full rounded-xl border-slate-200">
            </div>

            <div class="md:col-span-2">
                <label class="text-xs font-bold uppercase text-slate-500">Click URL (optional)</label>
                <input name="button_url" value="{{ old('button_url', $banner->button_url) }}" class="mt-1 w-full rounded-xl border-slate-200" placeholder="/shop?filter=deals or https://...">
                <p class="mt-1 text-[11px] text-slate-400">If set, the image (and button) open this link. Closing with × never redirects.</p>
            </div>

            <label class="flex items-center gap-2 text-sm font-semibold text-slate-700 self-end pb-2 md:col-span-2">
                <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300" @checked(old('is_active', $banner->is_active ?? true))>
                Active on website
            </label>
        </div>

        <div class="flex justify-end gap-2 pt-2">
            <a href="{{ route('cms.promo-popups.index') }}" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-600">Cancel</a>
            <button type="submit" class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white disabled:opacity-60" :disabled="saving">
                <span x-show="!saving">Save offer</span>
                <span x-show="saving" x-cloak>Saving…</span>
            </button>
        </div>
    </form>
</x-cms-layout>
