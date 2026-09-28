{{-- Uses parent Alpine scope: row, index, and helper methods pickVariantImages / removeVariantImage / syncVariantFiles --}}
<div class="rounded-lg border border-dashed border-orange-200 bg-white p-3">
    <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
        <p class="text-[11px] text-slate-500">
            Pictures for this color/option (up to 20). First = main thumbnail.
            <span class="font-semibold text-slate-700" x-text="((row._files || []).length) + ' selected'"></span>
        </p>
        <label class="inline-flex cursor-pointer items-center gap-1 rounded-md bg-orange-500 px-2.5 py-1.5 text-[11px] font-bold text-white hover:bg-orange-600"
               :class="(row._files || []).length >= 20 && 'pointer-events-none opacity-50'">
            + Add pictures
            <input type="file" accept="image/jpeg,image/png,image/webp,image/jpg,image/gif,image/*" multiple class="sr-only"
                   :disabled="(row._files || []).length >= 20"
                   @change="pickVariantImages(index, $event)">
        </label>
    </div>
    <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
        <template x-for="(item, fi) in (row._files || [])" :key="item.url + '-' + fi">
            <div class="relative overflow-hidden rounded-lg border border-emerald-200 bg-emerald-50/40">
                <img :src="item.url" alt="" class="h-20 w-full object-contain bg-white p-1">
                <span x-show="fi === 0" class="absolute left-1 top-1 rounded bg-blue-600 px-1 py-0.5 text-[9px] font-bold text-white">Main</span>
                <button type="button" @click="removeVariantImage(index, fi)"
                        class="absolute right-1 top-1 rounded bg-white/95 px-1 py-0.5 text-[9px] font-semibold text-rose-600 shadow-sm">×</button>
            </div>
        </template>
        <label x-show="(row._files || []).length < 20"
               class="flex h-20 cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-slate-200 hover:border-orange-300 hover:bg-orange-50/30">
            <span class="text-[10px] font-semibold text-orange-600">Add</span>
            <input type="file" accept="image/jpeg,image/png,image/webp,image/jpg,image/gif,image/*" multiple class="sr-only"
                   @change="pickVariantImages(index, $event)">
        </label>
    </div>
    <input type="file" :name="'variants[' + index + '][images][]'" multiple
           accept="image/jpeg,image/png,image/webp,image/jpg,image/gif,image/*"
           class="hidden" :id="'variant-file-input-' + row._key">
</div>
