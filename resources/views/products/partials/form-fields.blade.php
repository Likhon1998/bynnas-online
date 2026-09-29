{{-- Shared product form fields — used by create & edit --}}
@php
    $product = $product ?? null;
    $variantOf = $variantOf ?? null;
    $siblings = $siblings ?? collect();
    $isEdit = $product !== null;
    $prefill = $product ?? $variantOf;
    $showImei = retail_enabled() || (bool) ($product?->requires_imei);

    $currentValues = $product
        ? $product->variantValues->filter(fn ($v) => $v->attributeValue)->keyBy('product_attribute_id')
        : collect();

    $attributePayload = $productAttributes->map(fn ($a) => [
        'id' => $a->id,
        'name' => $a->name,
        'type' => $a->type,
        'values' => $a->values->map(fn ($v) => ['value' => $v->value, 'hex' => $v->swatchHex()])->values(),
    ])->values();

    $colorAttributeId = $productAttributes->firstWhere('slug', 'color')?->id;
    $sizeAttributeId = $productAttributes->firstWhere('slug', 'size')?->id;

    $oldVariants = collect(old('variants', []))->values()->map(function ($row, $i) {
        return [
            '_key' => 'v'.($i + 1),
            'options' => (object) ($row['options'] ?? []),
            'barcode' => $row['barcode'] ?? '',
            'sku' => $row['sku'] ?? '',
            'cost_price' => $row['cost_price'] ?? '',
            'selling_price' => $row['selling_price'] ?? '',
            'stock_quantity' => $row['stock_quantity'] ?? 0,
            'imei_list' => $row['imei_list'] ?? '',
            '_files' => [],
        ];
    });
    $oldSelectedAttrs = collect(old('variants', []))
        ->flatMap(fn ($row) => array_keys(array_filter($row['options'] ?? [], fn ($o) => filled($o['value'] ?? null))))
        ->map(fn ($id) => (int) $id)->unique()->values();
    $selectedAttrs = $oldSelectedAttrs->isNotEmpty()
        ? $oldSelectedAttrs
        : collect([$colorAttributeId, $sizeAttributeId])->filter()->values();

    $defaultMode = $isEdit ? 'simple' : ($variantOf ? 'simple' : '');
@endphp

<div class="space-y-5"
     x-data="{
        name: @js(old('name', $isEdit ? $product->name : ($variantOf?->storefrontDisplayName() ?? ''))),
        variantGroup: @js(old('variant_group', $prefill?->variant_group ?? '')),
        selling: @js(old('selling_price', $prefill?->selling_price ?? '')),
        seoTitle: @js(old('seo_title', $prefill?->seo_title ?? '')),
        metaDescription: @js(old('meta_description', $prefill?->meta_description ?? '')),
        autoGroup: {{ ($isEdit || $variantOf) ? 'false' : 'true' }},
        productMode: @js(old('product_mode', $defaultMode)),
        requiresImei: @js((bool) old('requires_imei', $product?->requires_imei ?? false)),
        imeiText: @js(old('imei_list', ($isEdit && $product) ? $product->availableImeis->pluck('imei')->implode("\n") : '')),
        attributes: @js($attributePayload),
        selectedAttrs: @js($selectedAttrs),
        genValues: {},
        variantUid: {{ max(1, $oldVariants->count()) }},
        variants: @js($oldVariants),
        categoryModal: false,
        brandModal: false,
        quickName: '',
        quickLoading: false,
        quickError: '',
        categoryUrl: @js(route('categories.store')),
        brandUrl: @js(route('brands.store')),
        csrf: @js(csrf_token()),
        get hasMode() { return {{ $isEdit ? 'true' : 'false' }} || this.productMode === 'simple' || this.productMode === 'variable'; },
        get isMulti() { return this.productMode === 'variable'; },
        get isSimple() { return this.productMode === 'simple' || {{ $isEdit ? 'true' : 'false' }}; },
        get chosenAttributes() { return this.attributes.filter(a => this.selectedAttrs.includes(a.id)); },
        chooseMode(mode) {
            this.productMode = mode;
            this.syncGroup();
            this.$nextTick(() => document.getElementById('product_name_input')?.focus());
        },
        slugify(s) {
            return String(s || '').toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '').slice(0, 80);
        },
        syncGroup() {
            if (this.autoGroup) this.variantGroup = this.slugify(this.name);
        },
        toggleAttr(id) {
            this.selectedAttrs = this.selectedAttrs.includes(id)
                ? this.selectedAttrs.filter(x => x !== id)
                : [...this.selectedAttrs, id];
        },
        hexFor(attr, value) {
            const hit = (attr.values || []).find(v => v.value.toLowerCase() === String(value || '').toLowerCase());
            return hit ? hit.hex : '#cbd5e1';
        },
        newRow(options = {}) {
            this.variantUid++;
            return {
                _key: 'v' + this.variantUid, options, barcode: '', sku: '',
                cost_price: '', selling_price: '', stock_quantity: 0, imei_list: '', _files: [],
            };
        },
        addVariantRow() {
            const options = {};
            this.chosenAttributes.forEach(a => { options[a.id] = { value: '', hex: '#cbd5e1' }; });
            this.variants.push(this.newRow(options));
        },
        generateVariants() {
            const lists = this.chosenAttributes
                .map(a => ({ attr: a, values: String(this.genValues[a.id] || '').split(',').map(v => v.trim()).filter(Boolean) }))
                .filter(l => l.values.length);
            if (!lists.length) return;
            let combos = [{}];
            lists.forEach(({ attr, values }) => {
                const next = [];
                combos.forEach(c => values.forEach(v => next.push({ ...c, [attr.id]: { value: v, hex: attr.type === 'color' ? this.hexFor(attr, v) : '' } })));
                combos = next;
            });
            const key = (opts) => Object.keys(opts).sort().map(k => k + ':' + String(opts[k]?.value || '').toLowerCase()).join('|');
            const existing = new Set(this.variants.map(r => key(r.options)));
            combos.slice(0, 100).forEach(opts => {
                if (!existing.has(key(opts))) this.variants.push(this.newRow(opts));
            });
        },
        removeVariantRow(i) {
            const row = this.variants[i];
            (row._files || []).forEach((f) => { if (f?.url) URL.revokeObjectURL(f.url); });
            this.variants.splice(i, 1);
        },
        rowLabel(row) {
            return this.chosenAttributes.map(a => row.options?.[a.id]?.value).filter(Boolean).join(' / ') || 'New variant';
        },
        syncVariantFiles(index) {
            const row = this.variants[index];
            if (!row) return;
            const input = document.getElementById('variant-file-input-' + row._key);
            if (!input) return;
            const dt = new DataTransfer();
            (row._files || []).forEach((item) => dt.items.add(item.file));
            input.files = dt.files;
        },
        pickVariantImages(index, event) {
            const row = this.variants[index];
            if (!row) return;
            if (!row._files) row._files = [];
            const picked = Array.from(event.target.files || []);
            const room = 20 - row._files.length;
            picked.slice(0, room).forEach((file) => {
                row._files.push({ name: file.name, url: URL.createObjectURL(file), file });
            });
            event.target.value = '';
            this.$nextTick(() => this.syncVariantFiles(index));
        },
        removeVariantImage(index, fileIndex) {
            const row = this.variants[index];
            if (!row || !row._files) return;
            const removed = row._files.splice(fileIndex, 1)[0];
            if (removed?.url) URL.revokeObjectURL(removed.url);
            this.$nextTick(() => this.syncVariantFiles(index));
        },
        openCategoryModal() {
            this.quickName = '';
            this.quickError = '';
            this.categoryModal = true;
            this.$nextTick(() => this.$refs.quickCategoryInput?.focus());
        },
        openBrandModal() {
            this.quickName = '';
            this.quickError = '';
            this.brandModal = true;
            this.$nextTick(() => this.$refs.quickBrandInput?.focus());
        },
        async quickCreate(url, payload, selectId, key, fallback) {
            const name = (this.quickName || '').trim();
            if (!name) { this.quickError = 'Enter a name.'; return false; }
            this.quickLoading = true;
            this.quickError = '';
            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf, 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ name, ...payload }),
                });
                const data = await res.json();
                if (!res.ok) { this.quickError = data.errors?.name?.[0] || data.message || fallback; return false; }
                const select = document.getElementById(selectId);
                const opt = document.createElement('option');
                opt.value = data[key].id;
                opt.textContent = data[key].name;
                opt.selected = true;
                select.appendChild(opt);
                return true;
            } catch (e) {
                this.quickError = 'Network error. Please try again.';
                return false;
            } finally {
                this.quickLoading = false;
            }
        },
        async saveQuickCategory() {
            if (await this.quickCreate(this.categoryUrl, {}, 'category_id', 'category', 'Could not create category.')) this.categoryModal = false;
        },
        async saveQuickBrand() {
            if (await this.quickCreate(this.brandUrl, { is_active: true }, 'brand_id', 'brand', 'Could not create brand.')) this.brandModal = false;
        },
     }"
     x-init="syncGroup()">

    @foreach($productAttributes as $attribute)
        <datalist id="attr-values-{{ $attribute->id }}">
            @foreach($attribute->values as $value)
                <option value="{{ $value->value }}">
            @endforeach
        </datalist>
    @endforeach

    @if($variantOf)
        <input type="hidden" name="variant_of" value="{{ $variantOf->id }}">
        <div class="rounded-xl border border-blue-100 bg-blue-50/60 px-4 py-3 text-sm text-slate-700">
            Adding a new variant to <strong>{{ $variantOf->storefrontDisplayName() }}</strong>.
            Set its options below — they are added to the name automatically (e.g. “— Red / XL”).
        </div>
    @endif

    @if(!$isEdit && !$variantOf)
    <section class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/80">
            <h3 class="text-sm font-semibold text-slate-800">What are you adding?</h3>
            <p class="text-xs text-slate-500 mt-0.5">Choose first — the form adapts to the product type.</p>
        </div>
        <div class="p-4">
            <input type="hidden" name="product_mode" :value="productMode">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <button type="button" @click="chooseMode('simple')"
                        class="text-left flex items-start gap-3 rounded-xl border p-4 transition"
                        :class="productMode === 'simple' ? 'border-blue-500 bg-blue-50/60 ring-2 ring-blue-200' : 'border-slate-200 hover:border-slate-300'">
                    <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2"
                          :class="productMode === 'simple' ? 'border-blue-600 bg-blue-600' : 'border-slate-300'">
                        <span x-show="productMode === 'simple'" class="h-2 w-2 rounded-full bg-white"></span>
                    </span>
                    <span>
                        <span class="block text-sm font-bold text-slate-900">Single product</span>
                        <span class="block text-[12px] text-slate-500 mt-1">One item, one price, one stock — e.g. a gift box, a toy, a bottle of serum.</span>
                    </span>
                </button>
                <button type="button" @click="chooseMode('variable')"
                        class="text-left flex items-start gap-3 rounded-xl border p-4 transition"
                        :class="productMode === 'variable' ? 'border-violet-500 bg-violet-50/50 ring-2 ring-violet-200' : 'border-slate-200 hover:border-slate-300'">
                    <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2"
                          :class="productMode === 'variable' ? 'border-violet-500 bg-violet-500' : 'border-slate-300'">
                        <span x-show="productMode === 'variable'" class="h-2 w-2 rounded-full bg-white"></span>
                    </span>
                    <span>
                        <span class="block text-sm font-bold text-slate-900">Product with variants</span>
                        <span class="block text-[12px] text-slate-500 mt-1">Same product in several options — sizes, colors, shades, materials, storage. Each variant has its own price, stock and photos.</span>
                    </span>
                </button>
            </div>
            <p x-show="!hasMode" class="mt-3 text-xs font-medium text-amber-700 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2" x-cloak>
                Select a product type to continue.
            </p>
        </div>
    </section>
    @else
        <input type="hidden" name="product_mode" value="simple">
    @endif

    <div x-show="hasMode" x-cloak class="space-y-5">

    @if($isEdit && ($product->variant_group || $siblings->isNotEmpty()))
    <section class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/80 flex items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-semibold text-slate-800">Variants in this product</h3>
                <p class="text-xs text-slate-500 mt-0.5">All variants share one storefront page. You are editing <strong>{{ $product->variantLabel() ?: 'this variant' }}</strong>.</p>
            </div>
            <a href="{{ route('products.create', ['variant_of' => $product->id]) }}"
               class="shrink-0 inline-flex items-center gap-1 rounded-lg border border-violet-200 bg-violet-50 px-2.5 py-1.5 text-xs font-semibold text-violet-700 hover:bg-violet-100">
                + Add variant
            </a>
        </div>
        @if($siblings->isNotEmpty())
            <div class="divide-y divide-slate-100">
                @foreach($siblings as $sibling)
                    <a href="{{ route('products.edit', $sibling) }}" class="flex items-center justify-between gap-3 px-4 py-2.5 text-sm hover:bg-slate-50">
                        <span class="min-w-0">
                            <span class="font-medium text-slate-800">{{ $sibling->variantLabel() ?: $sibling->name }}</span>
                            <span class="ml-2 font-mono text-[11px] text-slate-400">{{ $sibling->barcode }}</span>
                        </span>
                        <span class="shrink-0 text-xs text-slate-500">
                            {{ format_taka($sibling->selling_price) }} · {{ $sibling->availableStock() }} available
                        </span>
                    </a>
                @endforeach
            </div>
        @endif
    </section>
    @endif

    <template x-if="isSimple">
    <section class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/80">
            <h3 class="text-sm font-semibold text-slate-800">Photos</h3>
            <p class="text-xs text-slate-500 mt-0.5">Up to 20 photos. The first photo is the main thumbnail.@if($variantOf) Leave empty to reuse the photos of the original variant.@endif</p>
        </div>
        <div class="p-4">
            @include('products.partials.image-uploads', ['product' => $product ?? null])
        </div>
    </section>
    </template>

    <section class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/80">
            <h3 class="text-sm font-semibold text-slate-800">Basic information</h3>
            <p class="text-xs text-slate-500 mt-0.5">Title, brand and category customers see on the store.</p>
        </div>
        <div class="p-4 space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Product name <span class="text-red-500">*</span></label>
                <input type="text" id="product_name_input" name="name" x-model="name" @input="syncGroup()"
                       :required="hasMode"
                       class="block w-full rounded-lg border-slate-200 bg-white focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5"
                       placeholder="e.g. Classic Cotton T-Shirt, Matte Lipstick, Wooden Puzzle Set">
                <p class="text-[11px] text-slate-400 mt-1" x-show="isMulti" x-cloak>Each variant is saved as “Name — Option / Option”.</p>
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <div class="mb-1.5 flex items-center justify-between gap-2">
                        <label for="category_id" class="block text-xs font-semibold text-slate-600">Category</label>
                        <button type="button" @click="openCategoryModal()"
                                class="inline-flex h-6 w-6 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-500 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-600"
                                title="Add category">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        </button>
                    </div>
                    <select id="category_id" name="category_id" class="block w-full rounded-lg border-slate-200 bg-white focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5">
                        <option value="">Select category</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id', $prefill?->category_id ?? '') == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <div class="mb-1.5 flex items-center justify-between gap-2">
                        <label for="brand_id" class="block text-xs font-semibold text-slate-600">Brand</label>
                        <button type="button" @click="openBrandModal()"
                                class="inline-flex h-6 w-6 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-500 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-600"
                                title="Add brand">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        </button>
                    </div>
                    <select id="brand_id" name="brand_id" class="block w-full rounded-lg border-slate-200 bg-white focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5">
                        <option value="">No brand</option>
                        @foreach($brands as $brand)
                            <option value="{{ $brand->id }}" @selected(old('brand_id', $prefill?->brand_id ?? '') == $brand->id)>{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div x-show="isSimple">
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Barcode / product code</label>
                    <input type="text" name="barcode" value="{{ old('barcode', $product?->barcode ?? '') }}"
                           :disabled="isMulti"
                           class="block w-full rounded-lg border-slate-200 bg-white focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5 font-mono"
                           placeholder="Auto-generated if empty">
                    @error('barcode') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">SKU</label>
                    <input type="text" name="sku" value="{{ old('sku', $product?->sku ?? '') }}"
                           class="block w-full rounded-lg border-slate-200 bg-white focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5 font-mono"
                           placeholder="Optional">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Availability label</label>
                    <select name="availability" class="block w-full rounded-lg border-slate-200 bg-white focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5">
                        @php $avail = old('availability', $prefill?->availability ?? 'in_stock'); @endphp
                        <option value="in_stock" @selected($avail === 'in_stock')>In Stock</option>
                        <option value="pre_order" @selected($avail === 'pre_order')>Pre Order</option>
                        <option value="up_coming" @selected($avail === 'up_coming')>Coming Soon</option>
                        <option value="out_of_stock" @selected($avail === 'out_of_stock')>Out of Stock</option>
                    </select>
                </div>
            </div>
        </div>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/80">
            <h3 class="text-sm font-semibold text-slate-800">Pricing</h3>
            <p class="text-xs text-slate-500 mt-0.5">
                <span x-show="isSimple">Cost and selling price for this item.</span>
                <span x-show="isMulti" x-cloak>Default price for every variant — override per variant below.</span>
            </p>
        </div>
        <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Cost price (Tk) <span class="text-red-500">*</span></label>
                <input type="number" step="0.01" min="0" name="cost_price" value="{{ old('cost_price', $prefill?->cost_price ?? '') }}" required
                       class="block w-full rounded-lg border-slate-200 text-sm py-2.5">
                @error('cost_price') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Selling price (Tk) <span class="text-red-500">*</span></label>
                <input type="number" step="0.01" min="0" name="selling_price" x-model="selling" required
                       class="block w-full rounded-lg border-slate-200 text-sm py-2.5 font-medium text-blue-600">
                @error('selling_price') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="px-4 pb-4" x-data="{
            dtype: @js(old('pos_discount_type', $prefill?->pos_discount_type ?? '')),
            dval: @js(old('pos_discount_value', $prefill?->pos_discount_value ?? '')),
        }">
            <div class="rounded-xl border border-rose-100 bg-rose-50/40 p-4 space-y-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-rose-700">Always-on discount</p>
                    <p class="mt-0.5 text-[11px] text-slate-500">Applies on the store until you clear it. Timed sales can still apply — the customer always pays the lower price.</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Type</label>
                        <select name="pos_discount_type" x-model="dtype" class="block w-full rounded-lg border-slate-200 text-sm py-2.5">
                            <option value="">No discount</option>
                            <option value="percent">Percent (%)</option>
                            <option value="fixed">Fixed (Tk)</option>
                        </select>
                        @error('pos_discount_type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">
                            <span x-text="dtype === 'percent' ? 'Percent off' : 'Amount off (Tk)'"></span>
                        </label>
                        <input type="number" step="0.01" min="0" name="pos_discount_value" x-model="dval"
                               :disabled="!dtype" :required="!!dtype"
                               class="block w-full rounded-lg border-slate-200 text-sm py-2.5 disabled:bg-slate-100 disabled:text-slate-400">
                        @error('pos_discount_value') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-end">
                        <p class="text-xs text-slate-600 pb-2.5" x-show="dtype && selling && dval" x-cloak>
                            Offer ≈
                            <span class="font-bold text-rose-700"
                                  x-text="'Tk ' + (dtype === 'percent'
                                    ? Math.round(Math.max(0, Number(selling) * (1 - Number(dval)/100))).toLocaleString()
                                    : Math.round(Math.max(0, Number(selling) - Number(dval))).toLocaleString())"></span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Attributes for a single product / edited variant --}}
    <section class="rounded-xl border border-slate-200 bg-white overflow-hidden" x-show="isSimple">
        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/80 flex items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-semibold text-slate-800">Attributes</h3>
                <p class="text-xs text-slate-500 mt-0.5">All optional. Used for store filters, the variant picker and order details.</p>
            </div>
            <a href="{{ route('attributes.index') }}" class="shrink-0 text-xs font-semibold text-blue-600 hover:underline">Manage attributes</a>
        </div>
        <div class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($productAttributes as $attribute)
                @php
                    $current = $currentValues->get($attribute->id)?->attributeValue;
                    $valueOld = old("attributes.{$attribute->id}.value", $current?->value ?? '');
                    $hexOld = old("attributes.{$attribute->id}.hex", $current?->swatchHex() ?? '#cbd5e1');
                @endphp
                <div x-data="{ hex: @js($hexOld) }">
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">{{ $attribute->name }}</label>
                    <div class="flex items-center gap-2">
                        <input type="text" name="attributes[{{ $attribute->id }}][value]" value="{{ $valueOld }}"
                               list="attr-values-{{ $attribute->id }}"
                               class="block w-full rounded-lg border-slate-200 text-sm py-2.5"
                               placeholder="{{ $attribute->isColor() ? 'e.g. Black' : 'Optional' }}">
                        @if($attribute->isColor())
                            <input type="color" name="attributes[{{ $attribute->id }}][hex]" x-model="hex"
                                   class="h-10 w-12 shrink-0 cursor-pointer rounded-lg border border-slate-200" title="Swatch color">
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        <div class="px-4 pb-4" x-show="{{ $isEdit ? 'true' : 'false' }}">
            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Product family key</label>
            <input type="text" name="variant_group" x-model="variantGroup"
                   class="block w-full rounded-lg border-slate-200 text-sm py-2.5 font-mono"
                   placeholder="e.g. classic-cotton-tshirt">
            <p class="text-[11px] text-slate-400 mt-1">Variants with the same key share one store page and a variant picker.</p>
        </div>
        @if(!$isEdit)
            <input type="hidden" name="variant_group" :value="variantGroup" :disabled="isMulti">
        @endif
    </section>

    {{-- Variant builder --}}
    @if(!$isEdit)
    <section class="rounded-xl border border-slate-200 bg-white overflow-hidden" x-show="isMulti" x-cloak>
        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/80 flex items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-semibold text-slate-800">Variants</h3>
                <p class="text-xs text-slate-500 mt-0.5">Pick the attributes that change, list their values, then generate every combination.</p>
            </div>
            <a href="{{ route('attributes.index') }}" class="shrink-0 text-xs font-semibold text-blue-600 hover:underline">Manage attributes</a>
        </div>
        <div class="p-4 space-y-4">
            <input type="hidden" name="variant_group" :value="variantGroup" :disabled="!isMulti">

            <div>
                <p class="text-xs font-semibold text-slate-600 mb-2">1. Which attributes change between variants?</p>
                <div class="flex flex-wrap gap-2">
                    <template x-for="attr in attributes" :key="attr.id">
                        <button type="button" @click="toggleAttr(attr.id)"
                                class="rounded-full border px-3 py-1.5 text-xs font-semibold transition"
                                :class="selectedAttrs.includes(attr.id) ? 'border-violet-500 bg-violet-50 text-violet-700' : 'border-slate-200 text-slate-600 hover:border-slate-300'"
                                x-text="attr.name"></button>
                    </template>
                </div>
            </div>

            <div x-show="chosenAttributes.length" class="space-y-2">
                <p class="text-xs font-semibold text-slate-600">2. Values (comma separated)</p>
                <template x-for="attr in chosenAttributes" :key="'gen-' + attr.id">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2">
                        <span class="w-28 shrink-0 text-xs font-medium text-slate-500" x-text="attr.name"></span>
                        <input type="text" x-model="genValues[attr.id]"
                               class="block w-full rounded-lg border-slate-200 text-sm py-2"
                               :placeholder="attr.values.length ? attr.values.slice(0, 4).map(v => v.value).join(', ') : 'e.g. S, M, L, XL'">
                    </div>
                </template>
                <div class="flex flex-wrap gap-2 pt-1">
                    <button type="button" @click="generateVariants()"
                            class="rounded-lg bg-violet-600 px-3 py-2 text-xs font-bold text-white hover:bg-violet-700">Generate variants</button>
                    <button type="button" @click="addVariantRow()"
                            class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">+ Add one manually</button>
                </div>
            </div>

            <p x-show="!variants.length" class="rounded-lg border border-dashed border-slate-200 px-3 py-4 text-center text-xs text-slate-500">
                No variants yet — generate them above.
            </p>

            <template x-for="(row, index) in variants" :key="row._key">
                <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4 space-y-3">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs font-bold text-slate-700" x-text="rowLabel(row)"></span>
                        <button type="button" @click="removeVariantRow(index)" class="text-[11px] font-medium text-red-600 hover:text-red-700">Remove</button>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2">
                        <template x-for="attr in chosenAttributes" :key="row._key + '-' + attr.id">
                            <div>
                                <label class="block text-[10px] font-semibold text-slate-500 mb-1" x-text="attr.name"></label>
                                <div class="flex items-center gap-1.5">
                                    <input type="text"
                                           :name="'variants[' + index + '][options][' + attr.id + '][value]'"
                                           :list="'attr-values-' + attr.id"
                                           :value="row.options?.[attr.id]?.value || ''"
                                           @input="row.options = { ...row.options, [attr.id]: { ...(row.options?.[attr.id] || {}), value: $event.target.value } }"
                                           class="block w-full rounded-md border-slate-200 text-sm py-2">
                                    <template x-if="attr.type === 'color'">
                                        <input type="color"
                                               :name="'variants[' + index + '][options][' + attr.id + '][hex]'"
                                               :value="row.options?.[attr.id]?.hex || hexFor(attr, row.options?.[attr.id]?.value)"
                                               class="h-9 w-10 shrink-0 cursor-pointer rounded-md border border-slate-200">
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                    <div class="grid grid-cols-2 lg:grid-cols-5 gap-2">
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 mb-1">Cost (Tk)</label>
                            <input type="number" step="0.01" min="0" :name="'variants[' + index + '][cost_price]'" x-model="row.cost_price"
                                   class="block w-full rounded-md border-slate-200 text-sm py-2" placeholder="Default">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 mb-1">Selling (Tk)</label>
                            <input type="number" step="0.01" min="0" :name="'variants[' + index + '][selling_price]'" x-model="row.selling_price"
                                   class="block w-full rounded-md border-slate-200 text-sm py-2 font-medium text-blue-700"
                                   :placeholder="selling ? ('Tk ' + selling) : 'Default'">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 mb-1">Opening qty</label>
                            <input type="number" min="0" :name="'variants[' + index + '][stock_quantity]'" x-model="row.stock_quantity"
                                   class="block w-full rounded-md border-slate-200 text-sm py-2">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 mb-1">Barcode</label>
                            <input type="text" :name="'variants[' + index + '][barcode]'" x-model="row.barcode"
                                   class="block w-full rounded-md border-slate-200 text-sm py-2 font-mono" placeholder="Auto">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 mb-1">SKU</label>
                            <input type="text" :name="'variants[' + index + '][sku]'" x-model="row.sku"
                                   class="block w-full rounded-md border-slate-200 text-sm py-2 font-mono" placeholder="Optional">
                        </div>
                    </div>
                    <details class="group">
                        <summary class="cursor-pointer text-[11px] font-semibold text-slate-500 hover:text-slate-700">Photos for this variant <span x-text="(row._files || []).length ? '(' + row._files.length + ')' : '(optional — reuses the previous variant\'s photos)'"></span></summary>
                        <div class="mt-2">
                            @include('products.partials.variant-image-uploads')
                        </div>
                    </details>
                    @if($showImei)
                        <div x-show="requiresImei">
                            <label class="block text-[10px] font-semibold text-slate-500 mb-1">IMEI / serial numbers (one per line)</label>
                            <textarea :name="'variants[' + index + '][imei_list]'" x-model="row.imei_list" rows="2"
                                      class="block w-full rounded-md border-slate-200 text-sm font-mono"></textarea>
                        </div>
                    @endif
                </div>
            </template>
            @error('variants') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
            @error('variants.*.barcode') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror

            <template x-if="isMulti">
                <div class="rounded-lg border border-dashed border-slate-200 bg-white p-3">
                    <p class="text-[11px] font-semibold text-slate-600 mb-2">Shared photos (used for the first variant if it has none of its own)</p>
                    @include('products.partials.image-uploads', ['product' => null])
                </div>
            </template>
        </div>
    </section>
    @endif

    @if($showImei)
    <section class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/80">
            <h3 class="text-sm font-semibold text-slate-800">Serial / IMEI tracking</h3>
            <p class="text-xs text-slate-500 mt-0.5">Legacy retail feature — only for items sold with a serial number.</p>
        </div>
        <div class="p-4 space-y-3">
            <label class="inline-flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                <input type="hidden" name="requires_imei" value="0">
                <input type="checkbox" name="requires_imei" value="1" x-model="requiresImei"
                       class="rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                Require an IMEI / serial when selling
            </label>
            <div x-show="requiresImei && isSimple" x-cloak>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Available serials (one per line)</label>
                <textarea name="imei_list" x-model="imeiText" rows="4" class="block w-full rounded-lg border-slate-200 text-sm font-mono"></textarea>
                @error('imei_list') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>
    @endif

    <section class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/80">
            <h3 class="text-sm font-semibold text-slate-800">Description &amp; visibility</h3>
            <p class="text-xs text-slate-500 mt-0.5">The short summary appears near the price; the full description appears under the Description tab.</p>
        </div>
        <div class="p-4 space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Short summary</label>
                <textarea name="short_description" rows="2" maxlength="2000"
                          class="block w-full rounded-lg border-slate-200 text-sm"
                          placeholder="One or two lines that sell the product.">{{ old('short_description', $prefill?->short_description ?? '') }}</textarea>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Full description</label>
                <textarea name="description" rows="6"
                          class="block w-full rounded-lg border-slate-200 text-sm"
                          placeholder="Features, materials, sizing guide, care instructions, what’s in the box…">{{ old('description', $prefill?->description ?? '') }}</textarea>
            </div>

            <div class="flex flex-wrap gap-4">
                <label class="inline-flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                    <input type="checkbox" name="is_published" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                           @checked(old('is_published', $isEdit ? ($product?->is_published ?? true) : true))>
                    Publish on website
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                    <input type="checkbox" name="is_new_arrival" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                           @checked(old('is_new_arrival', $prefill?->is_new_arrival ?? false))>
                    New Arrival
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                    <input type="checkbox" name="is_best_seller" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                           @checked(old('is_best_seller', $prefill?->is_best_seller ?? false))>
                    Trending
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                    <input type="checkbox" name="is_featured" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                           @checked(old('is_featured', $prefill?->is_featured ?? false))>
                    Featured
                </label>
            </div>

            <div x-data="{ combo: {{ old('is_combo', $prefill?->is_combo ?? false) ? 'true' : 'false' }} }"
                 class="rounded-xl border border-dashed border-slate-200 p-3.5">
                <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700 cursor-pointer">
                    <input type="checkbox" name="is_combo" value="1" x-model="combo" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    Combo pack
                    <span class="text-xs font-normal text-slate-400">Bundle of items sold together at one price</span>
                </label>
                <div x-show="combo" x-cloak class="mt-3">
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">What’s in the combo <span class="font-normal text-slate-400">(one item per line)</span></label>
                    <textarea name="combo_items" rows="4"
                              class="block w-full rounded-lg border-slate-200 text-sm"
                              placeholder="Baby feeding bottle 250ml&#10;Soft silicone spoon set&#10;Cotton bib (2 pcs)">{{ old('combo_items', $prefill?->combo_items ?? '') }}</textarea>
                    @error('combo_items') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            @if(!$isEdit)
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" x-show="isSimple">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Opening quantity</label>
                        <input type="number" name="stock_quantity" min="0" step="1" value="{{ old('stock_quantity', 0) }}"
                               :disabled="isMulti"
                               class="block w-full rounded-lg border-slate-200 text-sm py-2.5">
                        @error('stock_quantity') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Low stock alert</label>
                        <input type="number" name="alert_quantity" value="{{ old('alert_quantity', 5) }}" min="0"
                               :disabled="isMulti"
                               class="block w-full rounded-lg border-slate-200 text-sm py-2.5">
                    </div>
                </div>
                <div x-show="isMulti" x-cloak class="rounded-lg border border-violet-100 bg-violet-50/50 px-3 py-2.5 text-xs text-slate-600">
                    Opening stock is set per variant. Low stock alert for each variant:
                    <input type="number" name="alert_quantity" value="{{ old('alert_quantity', 5) }}" min="0"
                           :disabled="!isMulti"
                           class="inline-block w-20 ml-1 rounded border-slate-200 text-sm py-1">
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Stock</label>
                        <div class="block w-full rounded-lg border border-slate-200 bg-slate-50 text-sm py-2.5 px-3 font-medium text-slate-900">
                            {{ $product->stock_quantity ?? 0 }} on hand · {{ $product->reservedStock() }} reserved · {{ $product->availableStock() }} available
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">
                            Change stock via
                            @if(($product->stock_quantity ?? 0) === 0)
                                <a href="{{ route('supply.opening-inventory.index') }}" class="text-blue-600 underline">Opening Inventory</a> or
                            @endif
                            <a href="{{ route('supply.adjustments.index') }}" class="text-blue-600 underline">Stock Adjustment</a>.
                        </p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Low stock alert</label>
                        <input type="number" name="alert_quantity" value="{{ old('alert_quantity', $product->alert_quantity ?? 5) }}" min="0"
                               class="block w-full rounded-lg border-slate-200 text-sm py-2.5">
                    </div>
                </div>
            @endif
        </div>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white overflow-hidden" x-data="{ open: {{ ($product?->seo_title || $product?->meta_description || $product?->og_image || $errors->hasAny(['seo_title', 'meta_description', 'og_title', 'og_description', 'og_image_file'])) ? 'true' : 'false' }} }">
        <button type="button" @click="open = !open" class="w-full px-4 py-3 border-b border-slate-100 bg-slate-50/80 flex items-center justify-between gap-3 text-left">
            <span>
                <span class="block text-sm font-semibold text-slate-800">SEO &amp; social sharing</span>
                <span class="block text-xs text-slate-500 mt-0.5">How this product looks on Google and when shared on Facebook, WhatsApp or Messenger.</span>
            </span>
            <svg class="h-4 w-4 shrink-0 text-slate-400 transition" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div class="p-4 space-y-4" x-show="open" x-cloak>
            <div class="rounded-lg border border-slate-200 bg-white p-3">
                <p class="text-[11px] text-slate-400">Search preview</p>
                <p class="mt-1 truncate text-[15px] font-medium text-blue-700" x-text="seoTitle || name || 'Product title'"></p>
                <p class="truncate text-[11px] text-emerald-700">{{ url('/product') }}/…</p>
                <p class="mt-0.5 line-clamp-2 text-xs text-slate-600" x-text="metaDescription || 'Add a meta description to control the text shown under the title.'"></p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">SEO title <span class="font-normal text-slate-400" x-text="'(' + (seoTitle || '').length + '/60)'"></span></label>
                    <input type="text" name="seo_title" x-model="seoTitle" maxlength="255"
                           class="block w-full rounded-lg border-slate-200 text-sm py-2.5" placeholder="Defaults to the product name">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Meta description <span class="font-normal text-slate-400" x-text="'(' + (metaDescription || '').length + '/160)'"></span></label>
                    <textarea name="meta_description" x-model="metaDescription" rows="2" maxlength="500"
                              class="block w-full rounded-lg border-slate-200 text-sm" placeholder="Defaults to the short summary"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Social share title (OG)</label>
                    <input type="text" name="og_title" value="{{ old('og_title', $prefill?->og_title ?? '') }}" maxlength="255"
                           class="block w-full rounded-lg border-slate-200 text-sm py-2.5" placeholder="Defaults to the SEO title">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Social share description (OG)</label>
                    <textarea name="og_description" rows="2" maxlength="500"
                              class="block w-full rounded-lg border-slate-200 text-sm" placeholder="Defaults to the meta description">{{ old('og_description', $prefill?->og_description ?? '') }}</textarea>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Social share image (OG)</label>
                <div class="flex flex-wrap items-center gap-3">
                    @if($product?->og_image)
                        <img src="{{ public_storage_url($product->og_image) }}" alt="" class="h-16 w-28 rounded-lg border border-slate-200 object-cover">
                        <label class="inline-flex items-center gap-1.5 text-xs text-slate-600">
                            <input type="checkbox" name="remove_og_image" value="1" class="rounded border-slate-300 text-rose-600"> Remove
                        </label>
                    @endif
                    <input type="file" name="og_image_file" accept="image/jpeg,image/png,image/webp"
                           class="text-xs text-slate-600 file:mr-2 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold">
                </div>
                <p class="text-[11px] text-slate-400 mt-1">1200×630 recommended. Defaults to the main product photo.</p>
                @error('og_image_file') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
    </section>

    </div>{{-- /hasMode --}}

    <div x-show="categoryModal" x-cloak class="fixed inset-0 z-[80] flex items-center justify-center p-4" @keydown.escape.window="categoryModal = false">
        <div class="absolute inset-0 bg-slate-900/40" @click="categoryModal = false"></div>
        <div class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 shadow-xl" @click.stop>
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-[15px] font-bold text-slate-900">Add category</h3>
                    <p class="mt-0.5 text-[12px] text-slate-500">Create a category without leaving this page.</p>
                </div>
                <button type="button" @click="categoryModal = false" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <label class="mb-1.5 block text-[12px] font-semibold text-slate-700">Category name</label>
            <input type="text" x-ref="quickCategoryInput" x-model="quickName" @keydown.enter.prevent="saveQuickCategory()"
                   placeholder="e.g. Skincare"
                   class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm focus:border-blue-400 focus:ring-blue-100">
            <p x-show="quickError" x-text="quickError" class="mt-2 text-[12px] font-medium text-rose-600"></p>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" @click="categoryModal = false" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                <button type="button" @click="saveQuickCategory()" :disabled="quickLoading"
                        class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-bold text-white hover:bg-blue-700 disabled:opacity-50">
                    <span x-text="quickLoading ? 'Saving…' : 'Add category'"></span>
                </button>
            </div>
        </div>
    </div>

    <div x-show="brandModal" x-cloak class="fixed inset-0 z-[80] flex items-center justify-center p-4" @keydown.escape.window="brandModal = false">
        <div class="absolute inset-0 bg-slate-900/40" @click="brandModal = false"></div>
        <div class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-5 shadow-xl" @click.stop>
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <h3 class="text-[15px] font-bold text-slate-900">Add brand</h3>
                    <p class="mt-0.5 text-[12px] text-slate-500">Create a brand without leaving this page.</p>
                </div>
                <button type="button" @click="brandModal = false" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <label class="mb-1.5 block text-[12px] font-semibold text-slate-700">Brand name</label>
            <input type="text" x-ref="quickBrandInput" x-model="quickName" @keydown.enter.prevent="saveQuickBrand()"
                   placeholder="e.g. Aarong"
                   class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm focus:border-blue-400 focus:ring-blue-100">
            <p x-show="quickError" x-text="quickError" class="mt-2 text-[12px] font-medium text-rose-600"></p>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" @click="brandModal = false" class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                <button type="button" @click="saveQuickBrand()" :disabled="quickLoading"
                        class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-bold text-white hover:bg-blue-700 disabled:opacity-50">
                    <span x-text="quickLoading ? 'Saving…' : 'Add brand'"></span>
                </button>
            </div>
        </div>
    </div>
</div>
