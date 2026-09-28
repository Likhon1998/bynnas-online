{{-- Shared admin UI for category shop filters --}}
@php
    $filterConfig = $filterConfig ?? $filterDefaults ?? \App\Support\CategoryFilterConfig::defaults();
@endphp

<div class="mt-8 border-t border-gray-100 pt-6"
     x-data="categoryFilters(@js($filterConfig))">
    <div class="flex items-start justify-between gap-4 mb-4">
        <div>
            <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Shop sidebar filters</h3>
            <p class="text-xs text-gray-500 mt-1">When customers open this category (e.g. Phones), these options appear on the left.</p>
        </div>
        <label class="inline-flex items-center gap-2 text-sm font-semibold text-gray-700">
            <input type="hidden" name="filter_enabled" value="0">
            <input type="checkbox" name="filter_enabled" value="1" x-model="enabled" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
            Show sidebar
        </label>
    </div>

    <div x-show="enabled" x-cloak class="space-y-5">
        <label class="flex items-center gap-2 text-sm text-gray-700">
            <input type="hidden" name="price_enabled" value="0">
            <input type="checkbox" name="price_enabled" value="1" x-model="priceEnabled" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
            Enable price range filter
        </label>

        <template x-for="(group, gIndex) in groups" :key="gIndex">
            <div class="rounded-xl border border-gray-200 bg-slate-50 p-4 space-y-3">
                <div class="flex flex-wrap items-center gap-3">
                    <input type="hidden" :name="'filter_groups['+gIndex+'][enabled]'" value="0">
                    <label class="inline-flex items-center gap-2 text-xs font-bold text-gray-600">
                        <input type="checkbox" :name="'filter_groups['+gIndex+'][enabled]'" value="1" x-model="group.enabled" class="rounded border-gray-300 text-indigo-600">
                        On
                    </label>
                    <input type="text" :name="'filter_groups['+gIndex+'][label]'" x-model="group.label" placeholder="Filter title" class="rounded-lg border-gray-300 text-sm flex-1 min-w-[140px]">
                    <select :name="'filter_groups['+gIndex+'][type]'" x-model="group.type" class="rounded-lg border-gray-300 text-sm">
                        <option value="availability">Availability</option>
                        <option value="brand">Brand (from products)</option>
                        <option value="storage">Storage / ROM (from products)</option>
                        <option value="ram">RAM (from products)</option>
                        <option value="color">Color (from products)</option>
                        <option value="custom">Custom options</option>
                    </select>
                    <input type="hidden" :name="'filter_groups['+gIndex+'][key]'" :value="group.key">
                    <button type="button" @click="groups.splice(gIndex, 1)" class="text-xs font-bold text-rose-600 hover:underline">Remove</button>
                </div>

                <div class="space-y-2" x-show="group.type === 'availability' || group.type === 'custom'">
                    <p class="text-[11px] font-bold uppercase tracking-wide text-gray-400">Options customers can select</p>
                    <template x-for="(opt, oIndex) in group.options" :key="oIndex">
                        <div class="flex gap-2">
                            <input type="text" :name="'filter_groups['+gIndex+'][options]['+oIndex+'][label]'" x-model="opt.label" placeholder="Label e.g. In Stock" class="rounded-lg border-gray-300 text-sm flex-1">
                            <input type="text" :name="'filter_groups['+gIndex+'][options]['+oIndex+'][value]'" x-model="opt.value" placeholder="Value key" class="rounded-lg border-gray-300 text-sm w-36">
                            <button type="button" @click="group.options.splice(oIndex, 1)" class="text-xs text-rose-500 px-2">×</button>
                        </div>
                    </template>
                    <button type="button" @click="group.options.push({label:'', value:''})" class="text-xs font-bold text-indigo-600 hover:underline">+ Add option</button>
                </div>

                <p class="text-[11px] text-gray-500" x-show="group.type === 'brand' || group.type === 'storage' || group.type === 'ram' || group.type === 'color'">
                    Options are filled automatically from products in this category.
                </p>
            </div>
        </template>

        <button type="button" @click="addGroup()" class="inline-flex items-center gap-1 text-sm font-bold text-indigo-600 hover:underline">
            + Add filter group
        </button>
    </div>
</div>

<script>
function categoryFilters(initial) {
    return {
        enabled: !!(initial?.enabled ?? true),
        priceEnabled: !!(initial?.price_enabled ?? true),
        groups: Array.isArray(initial?.groups) ? initial.groups.map(g => ({
            key: g.key || '',
            label: g.label || '',
            type: g.type || 'custom',
            enabled: g.enabled !== false,
            options: Array.isArray(g.options) ? g.options.map(o => ({ label: o.label || '', value: o.value || '' })) : [],
        })) : [],
        addGroup() {
            this.groups.push({
                key: 'filter_' + (this.groups.length + 1),
                label: 'New filter',
                type: 'custom',
                enabled: true,
                options: [{ label: '', value: '' }],
            });
        },
    };
}
</script>
