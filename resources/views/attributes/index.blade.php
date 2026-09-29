<x-app-layout>
    @php
        $inputCls = 'w-full rounded-md border border-slate-200 bg-white px-2 py-1.5 text-[12px] text-slate-800 focus:border-indigo-400 focus:ring-1 focus:ring-indigo-200';
        $labelCls = 'block text-[10px] font-semibold uppercase tracking-wide text-slate-500 mb-1';
    @endphp

    <div class="w-full min-w-0 pb-6 text-[12px] leading-snug text-slate-700" x-data="{ editing: null, editingValue: null }">
        <div class="mb-3 flex flex-wrap items-center gap-2">
            <div class="mr-auto">
                <h1 class="text-[15px] font-semibold tracking-tight text-slate-900">Product Attributes</h1>
                <p class="text-[11px] text-slate-500">Define options such as Color, Size or Material. Variants of a product are told apart by these values.</p>
            </div>
            <a href="{{ route('products.variants') }}" class="inline-flex items-center gap-1 rounded-md border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-medium text-slate-700 hover:bg-slate-50">Variants</a>
            <a href="{{ route('products.index') }}" class="inline-flex items-center gap-1 rounded-md border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-medium text-slate-700 hover:bg-slate-50">Products</a>
        </div>

        @if($errors->any())
            <div class="mb-3 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-[11px] text-rose-700">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="grid gap-3 lg:grid-cols-[300px_1fr]">
            {{-- New attribute --}}
            <form method="POST" action="{{ route('attributes.store') }}" class="h-fit rounded-xl border border-slate-200 bg-white p-3 shadow-sm space-y-2.5">
                @csrf
                <h2 class="text-[12px] font-semibold text-slate-900">New attribute</h2>
                <div>
                    <label class="{{ $labelCls }}">Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" required maxlength="60" placeholder="e.g. Fabric, Scent, Flavor" class="{{ $inputCls }}">
                </div>
                <div>
                    <label class="{{ $labelCls }}">Display as</label>
                    <select name="type" class="{{ $inputCls }}">
                        @foreach(\App\Models\ProductAttribute::TYPES as $key => $label)
                            <option value="{{ $key }}" @selected(old('type') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $labelCls }}">Values <span class="normal-case font-normal text-slate-400">(comma separated, optional)</span></label>
                    <textarea name="values" rows="2" placeholder="Cotton, Linen, Silk" class="{{ $inputCls }}">{{ old('values') }}</textarea>
                </div>
                <div class="flex items-center justify-between gap-2">
                    <label class="inline-flex items-center gap-1.5 text-[11px] text-slate-600">
                        <input type="hidden" name="is_filterable" value="0">
                        <input type="checkbox" name="is_filterable" value="1" checked class="rounded border-slate-300 text-indigo-600">
                        Show as shop filter
                    </label>
                    <input type="number" name="sort_order" min="0" placeholder="Order" class="{{ $inputCls }} !w-20">
                </div>
                <button type="submit" class="w-full rounded-md bg-indigo-600 px-3 py-1.5 text-[11px] font-semibold text-white hover:bg-indigo-700">Create attribute</button>
            </form>

            {{-- Attribute list --}}
            <div class="space-y-3">
                @forelse($productAttributes as $attribute)
                    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                        <div class="flex flex-wrap items-center gap-2 border-b border-slate-100 px-3 py-2">
                            <div class="mr-auto min-w-0">
                                <p class="text-[13px] font-semibold text-slate-900">
                                    {{ $attribute->name }}
                                    <span class="ml-1 rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-500">{{ \App\Models\ProductAttribute::TYPES[$attribute->type] ?? $attribute->type }}</span>
                                    @if($attribute->is_filterable)
                                        <span class="ml-1 rounded bg-emerald-50 px-1.5 py-0.5 text-[10px] font-medium text-emerald-700">Filter</span>
                                    @endif
                                </p>
                                <p class="text-[10px] text-slate-400">{{ $attribute->values->count() }} values &middot; used by {{ $attribute->variant_values_count }} product(s)</p>
                            </div>
                            <button type="button" @click="editing = editing === {{ $attribute->id }} ? null : {{ $attribute->id }}" class="rounded-md border border-slate-200 px-2 py-1 text-[11px] font-medium text-slate-600 hover:bg-slate-50">Edit</button>
                            <form method="POST" action="{{ route('attributes.destroy', $attribute) }}" data-confirm="Delete the {{ $attribute->name }} attribute and its unused values?" data-confirm-title="Delete attribute?" data-confirm-ok="Delete" data-confirm-tone="danger">
                                @csrf
                                @method('DELETE')
                                <button type="submit" @disabled($attribute->variant_values_count > 0)
                                        title="{{ $attribute->variant_values_count > 0 ? 'In use by products' : 'Delete' }}"
                                        class="rounded-md border border-rose-200 px-2 py-1 text-[11px] font-medium text-rose-600 hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-40">Delete</button>
                            </form>
                        </div>

                        <form method="POST" action="{{ route('attributes.update', $attribute) }}" x-show="editing === {{ $attribute->id }}" x-cloak
                              class="grid gap-2 border-b border-slate-100 bg-slate-50/60 px-3 py-2.5 sm:grid-cols-[1fr_160px_80px_auto_auto] sm:items-end">
                            @csrf
                            @method('PATCH')
                            <div>
                                <label class="{{ $labelCls }}">Name</label>
                                <input type="text" name="name" value="{{ $attribute->name }}" required maxlength="60" class="{{ $inputCls }}">
                            </div>
                            <div>
                                <label class="{{ $labelCls }}">Display as</label>
                                <select name="type" class="{{ $inputCls }}">
                                    @foreach(\App\Models\ProductAttribute::TYPES as $key => $label)
                                        <option value="{{ $key }}" @selected($attribute->type === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="{{ $labelCls }}">Order</label>
                                <input type="number" name="sort_order" min="0" value="{{ $attribute->sort_order }}" class="{{ $inputCls }}">
                            </div>
                            <label class="inline-flex items-center gap-1.5 pb-1.5 text-[11px] text-slate-600">
                                <input type="checkbox" name="is_filterable" value="1" @checked($attribute->is_filterable) class="rounded border-slate-300 text-indigo-600">
                                Filter
                            </label>
                            <button type="submit" class="rounded-md bg-indigo-600 px-3 py-1.5 text-[11px] font-semibold text-white hover:bg-indigo-700">Save</button>
                        </form>

                        <div class="px-3 py-2.5">
                            <div class="flex flex-wrap gap-1.5">
                                @forelse($attribute->values as $value)
                                    <div class="group relative">
                                        <button type="button" @click="editingValue = editingValue === {{ $value->id }} ? null : {{ $value->id }}"
                                                class="inline-flex items-center gap-1.5 rounded-full border border-slate-200 bg-white py-0.5 pl-1.5 pr-2 text-[11px] text-slate-700 hover:border-indigo-300"
                                                :class="editingValue === {{ $value->id }} && 'border-indigo-400 ring-1 ring-indigo-200'">
                                            @if($attribute->isColor())
                                                <span class="h-3.5 w-3.5 rounded-full border border-black/10" style="background: {{ $value->swatchHex() }}"></span>
                                            @endif
                                            {{ $value->value }}
                                            <span class="text-[10px] text-slate-400">{{ $value->variant_values_count }}</span>
                                        </button>
                                    </div>
                                @empty
                                    <p class="text-[11px] text-slate-400">No values yet. Values are also created automatically when you save a product.</p>
                                @endforelse
                            </div>

                            @foreach($attribute->values as $value)
                                <div x-show="editingValue === {{ $value->id }}" x-cloak class="mt-2 flex flex-wrap items-end gap-2 rounded-lg border border-slate-200 bg-slate-50/60 p-2">
                                    <form method="POST" action="{{ route('attributes.values.update', $value) }}" class="flex flex-wrap items-end gap-2">
                                        @csrf
                                        @method('PATCH')
                                        <div>
                                            <label class="{{ $labelCls }}">Value</label>
                                            <input type="text" name="value" value="{{ $value->value }}" required maxlength="80" class="{{ $inputCls }} !w-40">
                                        </div>
                                        @if($attribute->isColor())
                                            <div>
                                                <label class="{{ $labelCls }}">Swatch</label>
                                                <input type="color" name="color_hex" value="{{ $value->swatchHex() }}" class="h-[30px] w-12 cursor-pointer rounded border border-slate-200 bg-white p-0.5">
                                            </div>
                                        @endif
                                        <div>
                                            <label class="{{ $labelCls }}">Order</label>
                                            <input type="number" name="sort_order" min="0" value="{{ $value->sort_order }}" class="{{ $inputCls }} !w-20">
                                        </div>
                                        <button type="submit" class="rounded-md bg-indigo-600 px-3 py-1.5 text-[11px] font-semibold text-white hover:bg-indigo-700">Save</button>
                                    </form>
                                    <form method="POST" action="{{ route('attributes.values.destroy', $value) }}" data-confirm="Delete value {{ $value->value }}?" data-confirm-title="Delete value?" data-confirm-ok="Delete" data-confirm-tone="danger">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" @disabled($value->variant_values_count > 0)
                                                title="{{ $value->variant_values_count > 0 ? 'In use by products' : 'Delete' }}"
                                                class="rounded-md border border-rose-200 bg-white px-2.5 py-1.5 text-[11px] font-medium text-rose-600 hover:bg-rose-50 disabled:cursor-not-allowed disabled:opacity-40">Delete</button>
                                    </form>
                                </div>
                            @endforeach

                            <form method="POST" action="{{ route('attributes.values.store', $attribute) }}" class="mt-2.5 flex flex-wrap items-center gap-2" x-data="{ pickedHex: false }">
                                @csrf
                                <input type="text" name="values" required placeholder="Add values, comma separated" class="{{ $inputCls }} !w-auto min-w-[220px] flex-1">
                                @if($attribute->isColor())
                                    <input type="color" :name="pickedHex ? 'color_hex' : null" @input="pickedHex = true" value="#94a3b8"
                                           title="Optional swatch for a single value (otherwise matched from the colour name)"
                                           class="h-[30px] w-12 cursor-pointer rounded border border-slate-200 bg-white p-0.5">
                                @endif
                                <button type="submit" class="rounded-md border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-[11px] font-semibold text-indigo-700 hover:bg-indigo-100">Add</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center text-slate-500">No attributes yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
