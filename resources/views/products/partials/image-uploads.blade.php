{{-- Unlimited product gallery uploads (max 20). Optional $product when editing. --}}
@php
    $existingImages = isset($product)
        ? $product->galleryImages->map(fn ($img) => [
            'id' => $img->id,
            'url' => public_storage_url($img->path),
        ])->values()->all()
        : [];
@endphp
<div
    x-data="{
        existing: @js($existingImages),
        files: [],
        removeIds: [],
        max: 20,
        get total() { return this.existing.length + this.files.length; },
        get canAdd() { return this.total < this.max; },
        syncInput() {
            const dt = new DataTransfer();
            this.files.forEach((item) => dt.items.add(item.file));
            this.$refs.fileInput.files = dt.files;
        },
        onPick(event) {
            const picked = Array.from(event.target.files || []);
            const room = this.max - this.total;
            picked.slice(0, room).forEach((file) => {
                this.files.push({
                    name: file.name,
                    url: URL.createObjectURL(file),
                    file,
                });
            });
            event.target.value = '';
            this.syncInput();
        },
        removeFile(index) {
            const removed = this.files.splice(index, 1)[0];
            if (removed?.url) URL.revokeObjectURL(removed.url);
            this.syncInput();
        },
        markRemove(id) {
            this.removeIds.push(id);
            this.existing = this.existing.filter((img) => img.id !== id);
        }
    }"
>
    <template x-for="id in removeIds" :key="'rm-' + id">
        <input type="hidden" name="remove_images[]" :value="id">
    </template>

    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
        <p class="text-xs text-slate-500">
            Add pictures and <span class="font-semibold text-emerald-700">see them instantly before saving</span> (up to <span x-text="max"></span>).
            First photo is the main thumbnail.
            <span class="font-semibold text-slate-700" x-text="total + ' selected'"></span>
        </p>
        <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-2 text-xs font-bold text-white hover:bg-blue-700"
               :class="!canAdd && 'pointer-events-none opacity-50'">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add pictures
            <input type="file" accept="image/jpeg,image/png,image/webp,image/jpg,image/gif,image/*" multiple class="sr-only"
                   :disabled="!canAdd"
                   @change="onPick($event)">
        </label>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
        <template x-for="(img, index) in existing" :key="'ex-' + img.id">
            <div class="relative overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
                <img :src="img.url" alt="" class="h-32 w-full object-contain bg-white p-2">
                <span x-show="index === 0 && files.length === 0" class="absolute left-2 top-2 rounded-md bg-blue-600 px-1.5 py-0.5 text-[10px] font-bold text-white">Main</span>
                <button type="button" @click="markRemove(img.id)"
                        class="absolute right-2 top-2 rounded-md bg-white/95 px-1.5 py-1 text-[10px] font-semibold text-rose-600 shadow-sm ring-1 ring-slate-200 hover:bg-rose-50">
                    Remove
                </button>
            </div>
        </template>

        <template x-for="(item, index) in files" :key="'new-' + index + '-' + item.name">
            <div class="relative overflow-hidden rounded-xl border border-emerald-300 bg-emerald-50/50 ring-1 ring-emerald-100">
                <img :src="item.url" alt="" class="h-32 w-full object-contain bg-white p-2">
                <span class="absolute left-2 top-2 rounded-md bg-emerald-600 px-1.5 py-0.5 text-[10px] font-bold text-white">Preview</span>
                <span x-show="existing.length === 0 && index === 0" class="absolute left-2 bottom-2 rounded-md bg-blue-600 px-1.5 py-0.5 text-[10px] font-bold text-white">Main</span>
                <button type="button" @click="removeFile(index)"
                        class="absolute right-2 top-2 rounded-md bg-white/95 px-1.5 py-1 text-[10px] font-semibold text-rose-600 shadow-sm ring-1 ring-slate-200 hover:bg-rose-50">
                    Remove
                </button>
            </div>
        </template>

        <label x-show="canAdd" class="flex min-h-[8rem] cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-200 bg-white text-center hover:border-blue-400 hover:bg-blue-50/30">
            <svg class="h-7 w-7 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v16m8-8H4"/></svg>
            <span class="mt-1.5 text-xs font-semibold text-blue-600">Add pic</span>
            <span class="mt-0.5 text-[10px] text-slate-400">JPG, PNG, WebP, GIF</span>
            <input type="file" accept="image/jpeg,image/png,image/webp,image/jpg,image/gif,image/*" multiple class="sr-only" @change="onPick($event)">
        </label>
    </div>

    <input type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp,image/jpg,image/gif,image/*" class="hidden" x-ref="fileInput">

    <p class="mt-2 text-[11px] text-emerald-700 font-medium" x-show="files.length > 0" x-cloak>Green “Preview” tiles are not saved yet — click Save Product to keep them.</p>
    <p class="mt-2 text-[11px] text-slate-400" x-show="!canAdd">Maximum of <span x-text="max"></span> pictures reached.</p>
    @error('images') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
    @error('images.*') <p class="mt-1 text-xs text-red-500">{{ $message }}</p> @enderror
</div>
