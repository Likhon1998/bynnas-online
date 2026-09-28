{{-- Shared product form fields — used by create & edit --}}
@php
    $product = $product ?? null;
    $isEdit = $product !== null;
    $defaultVariants = old('variants', [
        ['barcode' => '', 'color' => 'Black', 'color_hex' => '#1e293b', 'ram' => '', 'storage' => '', 'cost_price' => '', 'selling_price' => '', 'stock_quantity' => 10, 'imei_list' => ''],
        ['barcode' => '', 'color' => 'White', 'color_hex' => '#f8fafc', 'ram' => '', 'storage' => '', 'cost_price' => '', 'selling_price' => '', 'stock_quantity' => 10, 'imei_list' => ''],
        ['barcode' => '', 'color' => 'Red', 'color_hex' => '#dc2626', 'ram' => '', 'storage' => '', 'cost_price' => '', 'selling_price' => '', 'stock_quantity' => 5, 'imei_list' => ''],
    ]);
@endphp

<div class="space-y-5"
     x-data="{
        name: @js(old('name', $product?->name ?? '')),
        color: @js(old('color', $product?->color ?? '')),
        colorHex: @js(old('color_hex', $product?->color_hex ?: '#2563eb')),
        storage: @js(old('storage', $product?->storage ?? '')),
        ram: @js(old('ram', $product?->ram ?? '')),
        variantGroup: @js(old('variant_group', $product?->variant_group ?? '')),
        selling: @js(old('selling_price', $product?->selling_price ?? '')),
        autoGroup: true,
        productMode: @js(old('product_mode', $isEdit ? 'simple' : '')),
        requiresImei: @js((bool) old('requires_imei', $product?->requires_imei ?? false)),
        imeiText: @js(old('imei_list', ($isEdit && $product) ? $product->availableImeis()->pluck('imei')->implode("\n") : '')),
        variantUid: {{ count($defaultVariants) }},
        variants: @js(collect($defaultVariants)->values()->map(function ($row, $i) {
            return array_merge($row, ['_key' => 'v'.($i + 1)]);
        })->all()),
        categoryModal: false,
        brandModal: false,
        quickName: '',
        quickLoading: false,
        quickError: '',
        categoryUrl: @js(route('categories.store')),
        brandUrl: @js(route('brands.store')),
        csrf: @js(csrf_token()),
        get hasMode() { return {{ $isEdit ? 'true' : 'false' }} || this.productMode === 'simple' || this.productMode === 'gadget'; },
        get isMulti() { return this.productMode === 'gadget'; },
        get isSimple() { return this.productMode === 'simple' || {{ $isEdit ? 'true' : 'false' }}; },
        chooseMode(mode) {
            this.productMode = mode;
            this.syncGroup();
            this.$nextTick(() => document.getElementById('product_name_input')?.focus());
        },
        slugify(s) {
            return String(s || '').toLowerCase().trim()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '')
                .slice(0, 80);
        },
        syncGroup() {
            if (this.autoGroup && !@js($isEdit && filled($product?->variant_group))) {
                this.variantGroup = this.slugify(this.name);
            }
        },
        pickSwatch(hex, label) {
            this.colorHex = hex;
            if (!this.color) this.color = label;
        },
        addVariantRow() {
            this.variantUid++;
            this.variants.push({
                _key: 'v' + this.variantUid,
                barcode: '', color: '', color_hex: '#2563eb', ram: '', storage: '',
                cost_price: '', selling_price: '', stock_quantity: 1, imei_list: '',
                _files: [],
            });
        },
        removeVariantRow(i) {
            if (this.variants.length <= 1) return;
            const row = this.variants[i];
            (row._files || []).forEach((f) => { if (f?.url) URL.revokeObjectURL(f.url); });
            this.variants.splice(i, 1);
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
        async saveQuickCategory() {
            const name = (this.quickName || '').trim();
            if (!name) { this.quickError = 'Enter a category name.'; return; }
            this.quickLoading = true;
            this.quickError = '';
            try {
                const res = await fetch(this.categoryUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ name }),
                });
                const data = await res.json();
                if (!res.ok) {
                    this.quickError = data.errors?.name?.[0] || data.message || 'Could not create category.';
                    return;
                }
                const select = document.getElementById('category_id');
                const opt = document.createElement('option');
                opt.value = data.category.id;
                opt.textContent = data.category.name;
                opt.selected = true;
                select.appendChild(opt);
                this.categoryModal = false;
            } catch (e) {
                this.quickError = 'Network error. Please try again.';
            } finally {
                this.quickLoading = false;
            }
        },
        async saveQuickBrand() {
            const name = (this.quickName || '').trim();
            if (!name) { this.quickError = 'Enter a brand name.'; return; }
            this.quickLoading = true;
            this.quickError = '';
            try {
                const res = await fetch(this.brandUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ name, is_active: true }),
                });
                const data = await res.json();
                if (!res.ok) {
                    this.quickError = data.errors?.name?.[0] || data.message || 'Could not create brand.';
                    return;
                }
                const select = document.getElementById('brand_id');
                const opt = document.createElement('option');
                opt.value = data.brand.id;
                opt.textContent = data.brand.name;
                opt.selected = true;
                select.appendChild(opt);
                this.brandModal = false;
            } catch (e) {
                this.quickError = 'Network error. Please try again.';
            } finally {
                this.quickLoading = false;
            }
        },
     }"
     x-init="syncGroup()">

    @if(!$isEdit)
    {{-- Step 0: choose type first --}}
    <section class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/80">
            <h3 class="text-sm font-semibold text-slate-800">1. What are you adding?</h3>
            <p class="text-xs text-slate-500 mt-0.5">Choose first — the form below changes to match.</p>
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
                        <span class="block text-sm font-bold text-slate-900">Single item</span>
                        <span class="block text-[12px] text-slate-500 mt-1">One product, one barcode, one stock — e.g. one charger with no color options.</span>
                    </span>
                </button>
                <button type="button" @click="chooseMode('gadget')"
                        class="text-left flex items-start gap-3 rounded-xl border p-4 transition"
                        :class="productMode === 'gadget' ? 'border-orange-500 bg-orange-50/50 ring-2 ring-orange-200' : 'border-slate-200 hover:border-slate-300'">
                    <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2"
                          :class="productMode === 'gadget' ? 'border-orange-500 bg-orange-500' : 'border-slate-300'">
                        <span x-show="productMode === 'gadget'" class="h-2 w-2 rounded-full bg-white"></span>
                    </span>
                    <span>
                        <span class="block text-sm font-bold text-slate-900">Multi-variant</span>
                        <span class="block text-[12px] text-slate-500 mt-1">Same name, several colors/sizes (cable colors, phone memory…). Each has own barcode, stock &amp; photos.</span>
                    </span>
                </button>
            </div>
            <p x-show="!hasMode" class="mt-3 text-xs font-medium text-amber-700 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2" x-cloak>
                Select Single item or Multi-variant to continue.
            </p>
        </div>
    </section>
    @else
        <input type="hidden" name="product_mode" value="simple">
    @endif

    <div x-show="hasMode" x-cloak class="space-y-5">

    {{-- Gallery: single item (or edit) only — multi uses photos per variant --}}
    <template x-if="isSimple">
    <section class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/80">
            <h3 class="text-sm font-semibold text-slate-800">{{ $isEdit ? '1' : '2' }}. Product gallery</h3>
            <p class="text-xs text-slate-500 mt-0.5">Add as many photos as you want (up to 20). First photo is the main thumbnail.</p>
        </div>
        <div class="p-4">
            @include('products.partials.image-uploads', ['product' => $product ?? null])
        </div>
    </section>
    </template>

    {{-- Basic info --}}
    <section class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/80">
            <h3 class="text-sm font-semibold text-slate-800">{{ $isEdit ? '2' : '3' }}. Basic information</h3>
            <p class="text-xs text-slate-500 mt-0.5">Title, brand, and category customers see on the store.</p>
        </div>
        <div class="p-4 space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Product name <span class="text-red-500">*</span></label>
                <input type="text" id="product_name_input" name="name" x-model="name" @input="syncGroup()" value="{{ old('name', $product?->name ?? '') }}"
                       :required="hasMode"
                       class="block w-full rounded-lg border-slate-200 bg-white focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5"
                       placeholder="e.g. USB-C Cable or Pixel 7">
                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
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
                            <option value="{{ $category->id }}" {{ old('category_id', $product?->category_id ?? '') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
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
                            <option value="{{ $brand->id }}" {{ old('brand_id', $product?->brand_id ?? '') == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div x-show="isSimple">
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Barcode <span class="text-red-500">*</span></label>
                    <input type="text" name="barcode" value="{{ old('barcode', $product?->barcode ?? '') }}"
                           :required="isSimple"
                           :disabled="isMulti"
                           class="block w-full rounded-lg border-slate-200 bg-white focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5 font-mono"
                           placeholder="Unique barcode / product code…">
                    <p class="text-[11px] text-slate-400 mt-1">Unique code for this item (like AGL7373).</p>
                    @error('barcode') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">SKU (optional)</label>
                    <input type="text" name="sku" value="{{ old('sku', $product?->sku ?? '') }}"
                           class="block w-full rounded-lg border-slate-200 bg-white focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5 font-mono"
                           placeholder="e.g. IPH15-256-NAT">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1.5">Availability (shop filter)</label>
                    <select name="availability" class="block w-full rounded-lg border-slate-200 bg-white focus:border-blue-500 focus:ring-blue-500 text-sm py-2.5">
                        @php $avail = old('availability', $product?->availability ?? 'in_stock'); @endphp
                        <option value="in_stock" @selected($avail === 'in_stock')>In Stock</option>
                        <option value="pre_order" @selected($avail === 'pre_order')>Pre Order</option>
                        <option value="up_coming" @selected($avail === 'up_coming')>Up Coming</option>
                        <option value="out_of_stock" @selected($avail === 'out_of_stock')>Out of Stock</option>
                    </select>
                    <p class="text-[11px] text-slate-400 mt-1">Used by category sidebar filters on the website.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Pricing --}}
    <section class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/80">
            <h3 class="text-sm font-semibold text-slate-800">{{ $isEdit ? '3' : '4' }}. Pricing</h3>
            <p class="text-xs text-slate-500 mt-0.5">
                <span x-show="isSimple">Cost &amp; selling price for this item.</span>
                <span x-show="isMulti" x-cloak>Default price for all variants. You can override per color/option below.</span>
            </p>
        </div>
        <div class="p-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Cost price (Tk) <span class="text-red-500">*</span></label>
                <input type="number" step="0.01" name="cost_price" value="{{ old('cost_price', $product?->cost_price ?? '') }}" required
                       class="block w-full rounded-lg border-slate-200 text-sm py-2.5">
                @error('cost_price') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Selling price (Tk) <span class="text-red-500">*</span></label>
                <input type="number" step="0.01" name="selling_price" x-model="selling" value="{{ old('selling_price', $product?->selling_price ?? '') }}" required
                       class="block w-full rounded-lg border-slate-200 text-sm py-2.5 font-medium text-blue-600">
                @error('selling_price') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="px-4 pb-4" x-data="{
            dtype: @js(old('pos_discount_type', $product?->pos_discount_type ?? '')),
            dval: @js(old('pos_discount_value', $product?->pos_discount_value ?? '')),
        }">
            <div class="rounded-xl border border-rose-100 bg-rose-50/40 p-4 space-y-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-rose-700">Product discount (always on)</p>
                    <p class="mt-0.5 text-[11px] text-slate-500">Applies on POS and storefront until you clear it. Timed Sale campaigns on the product list can still stack — customer pays the lower price.</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Type</label>
                        <select name="pos_discount_type" x-model="dtype"
                                class="block w-full rounded-lg border-slate-200 text-sm py-2.5">
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
                               :disabled="!dtype"
                               :required="!!dtype"
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

    {{-- Variants --}}
    <section class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/80">
            <h3 class="text-sm font-semibold text-slate-800">{{ $isEdit ? '4' : '5' }}. Color / size options</h3>
            <p class="text-xs text-slate-500 mt-0.5" x-show="isSimple">Optional color or size for this single item.</p>
            <p class="text-xs text-slate-500 mt-0.5" x-show="isMulti" x-cloak>Add one row per color/option. Each needs a unique barcode and can have many pictures.</p>
        </div>
        <div class="p-4 space-y-4">
            <div x-show="isMulti || {{ $isEdit ? 'true' : 'false' }}">
                <div class="flex items-center justify-between gap-2 mb-1.5">
                    <label class="text-xs font-semibold text-slate-600">Variant group key</label>
                    <label class="text-[11px] text-slate-500 inline-flex items-center gap-1.5 cursor-pointer" x-show="!{{ $isEdit ? 'true' : 'false' }}">
                        <input type="checkbox" x-model="autoGroup" @change="syncGroup()" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        Auto from product name
                    </label>
                </div>
                <input type="text" name="variant_group" x-model="variantGroup" @input="autoGroup = false"
                       class="block w-full rounded-lg border-slate-200 text-sm py-2.5 font-mono"
                       placeholder="e.g. usb-c-cable or pixel-7">
                <p class="text-[11px] text-slate-400 mt-1" x-show="isMulti" x-cloak>
                    Links all colors under one store page. Keep the same for every row below.
                </p>
            </div>
            <div x-show="isSimple && !{{ $isEdit ? 'true' : 'false' }}" x-cloak>
                <input type="hidden" name="variant_group" :value="variantGroup">
            </div>

            <div x-show="isSimple" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Color name</label>
                        <input type="text" name="color" x-model="color"
                               class="block w-full rounded-lg border-slate-200 text-sm py-2.5"
                               placeholder="e.g. Black">
                        <div class="flex flex-wrap gap-2 mt-2.5">
                            @foreach([
                                ['#1e293b', 'Black'],
                                ['#f8fafc', 'White'],
                                ['#dc2626', 'Red'],
                                ['#2563eb', 'Blue'],
                                ['#c5c9a0', 'Lemongrass'],
                                ['#16a34a', 'Green'],
                                ['#ca8a04', 'Gold'],
                            ] as [$hex, $label])
                                <button type="button" @click="pickSwatch('{{ $hex }}', '{{ $label }}')"
                                        title="{{ $label }}"
                                        class="w-7 h-7 rounded-full border-2 border-white shadow ring-1 ring-slate-200 hover:ring-blue-400 transition"
                                        style="background: {{ $hex }}"></button>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Swatch color</label>
                        <div class="flex items-center gap-3">
                            <input type="color" x-model="colorHex"
                                   class="h-10 w-14 rounded-lg border border-slate-200 cursor-pointer shrink-0">
                            <input type="text" name="color_hex" x-model="colorHex"
                                   class="flex-1 rounded-lg border-slate-200 text-sm py-2.5 font-mono"
                                   placeholder="#2563eb">
                            <div class="w-10 h-10 rounded-full border-2 border-blue-600 ring-2 ring-blue-100 shrink-0"
                                 :style="'background:' + colorHex" title="Preview"></div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Storage / size (optional)</label>
                        <input type="text" name="storage" x-model="storage" list="storage-presets"
                               class="block w-full rounded-lg border-slate-200 text-sm py-2.5"
                               placeholder="e.g. 128GB or 2m">
                        <datalist id="storage-presets">
                            <option value="64GB"><option value="128GB"><option value="256GB"><option value="512GB"><option value="1TB">
                        </datalist>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">RAM (optional)</label>
                        <input type="text" name="ram" x-model="ram" list="ram-presets"
                               class="block w-full rounded-lg border-slate-200 text-sm py-2.5"
                               placeholder="e.g. 8GB">
                        <datalist id="ram-presets">
                            <option value="4GB"><option value="6GB"><option value="8GB"><option value="12GB"><option value="16GB">
                        </datalist>
                    </div>
                </div>
            </div>

            @if(!$isEdit)
            <div x-show="isMulti" x-cloak class="space-y-3">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-xs font-semibold text-slate-700">Variants <span class="font-normal text-slate-500">(barcode + stock + photos each)</span></p>
                    <button type="button" @click="addVariantRow()"
                            class="text-xs font-semibold text-orange-700 hover:text-orange-800 px-2 py-1 rounded-md border border-orange-200 bg-orange-50">
                        + Add color / option
                    </button>
                </div>
                <template x-for="(row, index) in variants" :key="row._key">
                    <div class="rounded-xl border border-slate-200 p-4 bg-slate-50/60 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide" x-text="'Option ' + (index + 1)"></span>
                            <button type="button" @click="removeVariantRow(index)" x-show="variants.length > 1"
                                    class="text-[11px] text-red-600 hover:text-red-700">Remove</button>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-2">
                            <div class="lg:col-span-2">
                                <label class="block text-[10px] font-semibold text-slate-500 mb-1">Barcode *</label>
                                <input type="text" :name="'variants['+index+'][barcode]'" x-model="row.barcode"
                                       class="block w-full rounded-md border-slate-200 text-sm py-2 font-mono"
                                       placeholder="e.g. CAB-BK-01">
                            </div>
                            <div>
                                <label class="block text-[10px] font-semibold text-slate-500 mb-1">Color</label>
                                <input type="text" :name="'variants['+index+'][color]'" x-model="row.color"
                                       class="block w-full rounded-md border-slate-200 text-sm py-2"
                                       placeholder="Black">
                            </div>
                            <div>
                                <label class="block text-[10px] font-semibold text-slate-500 mb-1">Swatch</label>
                                <input type="color" :name="'variants['+index+'][color_hex]'" x-model="row.color_hex"
                                       class="h-9 w-full rounded-md border border-slate-200 cursor-pointer">
                            </div>
                            <div>
                                <label class="block text-[10px] font-semibold text-slate-500 mb-1">RAM</label>
                                <input type="text" :name="'variants['+index+'][ram]'" x-model="row.ram"
                                       class="block w-full rounded-md border-slate-200 text-sm py-2" placeholder="optional">
                            </div>
                            <div>
                                <label class="block text-[10px] font-semibold text-slate-500 mb-1">Storage</label>
                                <input type="text" :name="'variants['+index+'][storage]'" x-model="row.storage"
                                       class="block w-full rounded-md border-slate-200 text-sm py-2" placeholder="optional">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                            <div>
                                <label class="block text-[10px] font-semibold text-slate-500 mb-1">Cost (Tk)</label>
                                <input type="number" step="0.01" min="0" :name="'variants['+index+'][cost_price]'" x-model="row.cost_price"
                                       class="block w-full rounded-md border-slate-200 text-sm py-2"
                                       placeholder="Same as default">
                            </div>
                            <div>
                                <label class="block text-[10px] font-semibold text-slate-500 mb-1">Selling (Tk)</label>
                                <input type="number" step="0.01" min="0" :name="'variants['+index+'][selling_price]'" x-model="row.selling_price"
                                       class="block w-full rounded-md border-slate-200 text-sm py-2 font-medium text-blue-700"
                                       :placeholder="selling ? ('Default Tk ' + selling) : 'Same as default'">
                            </div>
                            <div>
                                <label class="block text-[10px] font-semibold text-slate-500 mb-1">Opening qty</label>
                                <input type="number" min="0" :name="'variants['+index+'][stock_quantity]'" x-model="row.stock_quantity"
                                       class="block w-full rounded-md border-slate-200 text-sm py-2">
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 mb-1.5">Pictures for this color (add as many as you want)</label>
                            @include('products.partials.variant-image-uploads')
                        </div>
                        <div x-show="requiresImei">
                            <label class="block text-[10px] font-semibold text-slate-500 mb-1">IMEI numbers (one per line)</label>
                            <textarea :name="'variants['+index+'][imei_list]'" x-model="row.imei_list" rows="2"
                                      class="block w-full rounded-md border-slate-200 text-sm font-mono"
                                      placeholder="356938035643809&#10;356938035643810"></textarea>
                        </div>
                    </div>
                </template>
                @error('variants') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
                @error('variants.*.barcode') <p class="text-red-500 text-xs">{{ $message }}</p> @enderror
            </div>
            @endif
        </div>
    </section>

    {{-- IMEI --}}
    <section class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/80">
            <h3 class="text-sm font-semibold text-slate-800">{{ $isEdit ? '4b' : '6' }}. IMEI tracking (optional)</h3>
            <p class="text-xs text-slate-500 mt-0.5">Only for phones. Leave off for cables &amp; accessories.</p>
        </div>
        <div class="p-4 space-y-3">
            <label class="inline-flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                <input type="hidden" name="requires_imei" value="0">
                <input type="checkbox" name="requires_imei" value="1" x-model="requiresImei"
                       class="rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                This product requires an IMEI / serial when selling
            </label>
            <div x-show="requiresImei && isSimple" x-cloak>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Available IMEI list (one per line)</label>
                <textarea name="imei_list" x-model="imeiText" rows="4"
                          class="block w-full rounded-lg border-slate-200 text-sm font-mono"
                          placeholder="356938035643809&#10;356938035643810"></textarea>
                @error('imei_list') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <p class="text-[11px] text-slate-500" x-show="requiresImei && isMulti" x-cloak>
                Enter IMEIs on each variant row above.
            </p>
        </div>
    </section>

    {{-- 5. Store description --}}    {{-- 5. Store description --}}
    <section class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/80">
            <h3 class="text-sm font-semibold text-slate-800">5. Store description & visibility</h3>
            <p class="text-xs text-slate-500 mt-0.5">Shown under Description on the product page.</p>
        </div>
        <div class="p-4 space-y-4">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Short description</label>
                <textarea name="short_description" rows="4"
                          class="block w-full rounded-lg border-slate-200 text-sm"
                          placeholder="About this item — features, condition, what’s included…">{{ old('short_description', $product?->short_description ?? '') }}</textarea>
            </div>

            <div class="flex flex-wrap gap-4">
                <label class="inline-flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                    <input type="checkbox" name="is_published" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                           {{ old('is_published', $isEdit ? ($product?->is_published ?? true) : true) ? 'checked' : '' }}>
                    Publish on website
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                    <input type="checkbox" name="is_new_arrival" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                           {{ old('is_new_arrival', $product?->is_new_arrival ?? false) ? 'checked' : '' }}>
                    New Arrival
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                    <input type="checkbox" name="is_best_seller" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                           {{ old('is_best_seller', $product?->is_best_seller ?? false) ? 'checked' : '' }}>
                    Trending
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                    <input type="checkbox" name="is_featured" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                           {{ old('is_featured', $product?->is_featured ?? false) ? 'checked' : '' }}>
                    Featured
                </label>
            </div>
            <p class="text-[11px] text-slate-400">New Arrival and Trending control which products appear in those homepage sections.</p>

            @if(!$isEdit)
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" x-show="isSimple">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Opening quantity</label>
                        <input type="number" name="stock_quantity" min="0" step="1"
                               value="{{ old('stock_quantity', 0) }}"
                               :disabled="isMulti"
                               class="block w-full rounded-lg border-slate-200 text-sm py-2.5"
                               placeholder="e.g. 10">
                        @error('stock_quantity') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Low stock alert</label>
                        <input type="number" name="alert_quantity" value="{{ old('alert_quantity', 5) }}" min="0"
                               :disabled="isMulti"
                               :required="isSimple"
                               class="block w-full rounded-lg border-slate-200 text-sm py-2.5">
                    </div>
                </div>
                <div x-show="isMulti" x-cloak class="rounded-lg border border-orange-100 bg-orange-50/50 px-3 py-2.5 text-xs text-slate-600">
                    Opening stock is set <strong>per color/option</strong> above. Shared low-stock alert:
                    <input type="number" name="alert_quantity" value="{{ old('alert_quantity', 5) }}" min="0"
                           :disabled="!isMulti"
                           class="inline-block w-20 ml-1 rounded border-slate-200 text-sm py-1">
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Current stock</label>
                        <div class="block w-full rounded-lg border border-slate-200 bg-slate-50 text-sm py-2.5 px-3 font-medium text-slate-900">
                            {{ $product?->stock_quantity ?? 0 }} units
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">
                            Change stock via
                            @if(($product?->stock_quantity ?? 0) === 0)
                                <a href="{{ route('supply.opening-inventory.index') }}" class="text-blue-600 underline">Opening Inventory</a>
                                or
                            @endif
                            <a href="{{ route('supply.adjustments.index') }}" class="text-blue-600 underline">Stock Adjustment</a>.
                        </p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Low stock alert</label>
                        <input type="number" name="alert_quantity" value="{{ old('alert_quantity', $product?->alert_quantity ?? 5) }}" required min="0"
                               class="block w-full rounded-lg border-slate-200 text-sm py-2.5">
                    </div>
                </div>
            @endif
        </div>
    </section>

    </div>{{-- /hasMode --}}

    {{-- Quick add category modal --}}
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
                   placeholder="e.g. Phones"
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

    {{-- Quick add brand modal --}}
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
                   placeholder="e.g. Apple"
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
