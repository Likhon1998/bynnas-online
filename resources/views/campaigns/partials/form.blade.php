@php
    $inputCls = 'w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-[13px] text-slate-800 focus:border-indigo-400 focus:ring-1 focus:ring-indigo-200';
    $labelCls = 'block text-[11px] font-semibold uppercase tracking-wide text-slate-500 mb-1';
@endphp

@if($errors->any())
    <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-[12px] text-rose-700">
        @foreach($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<div class="grid gap-4 lg:grid-cols-[1fr_320px]" x-data="{ landing: @js(old('landing_type', $campaign->landing_type ?: 'home')) }">
    <div class="space-y-4">
        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm space-y-3">
            <h2 class="text-[13px] font-bold text-slate-900">Campaign</h2>
            <div>
                <label class="{{ $labelCls }}">Name</label>
                <input type="text" name="name" value="{{ old('name', $campaign->name) }}" required maxlength="120" placeholder="e.g. Eid Denim Sale — Facebook Ads" class="{{ $inputCls }}">
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="{{ $labelCls }}">Platform (source)</label>
                    <select name="source" class="{{ $inputCls }}">
                        @foreach(\App\Models\Campaign::SOURCES as $key => $label)
                            <option value="{{ $key }}" @selected(old('source', $campaign->source) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="{{ $labelCls }}">Placement type (medium)</label>
                    <select name="medium" class="{{ $inputCls }}">
                        @foreach(\App\Models\Campaign::MEDIUMS as $key => $label)
                            <option value="{{ $key }}" @selected(old('medium', $campaign->medium) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="{{ $labelCls }}">Campaign tag (utm_campaign)</label>
                <input type="text" name="utm_campaign" value="{{ old('utm_campaign', $campaign->utm_campaign) }}" maxlength="120" placeholder="Leave empty to build it from the name" class="{{ $inputCls }} font-mono">
                <p class="mt-1 text-[11px] text-slate-500">Used in links and ad manager URLs. Lowercase, no spaces — e.g. <span class="font-mono">eid_denim_sale</span>.</p>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm space-y-3">
            <h2 class="text-[13px] font-bold text-slate-900">Where the link goes</h2>
            <div>
                <label class="{{ $labelCls }}">Landing page</label>
                <select name="landing_type" x-model="landing" class="{{ $inputCls }}">
                    @foreach(\App\Models\Campaign::LANDING_TYPES as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <p x-show="landing === 'landing'" x-cloak class="rounded-lg bg-indigo-50 px-3 py-2 text-[11px] text-indigo-700">
                Opens the published landing page linked to this campaign. Create one under
                <a href="{{ route('landing-pages.create') }}" class="font-semibold underline">Landing pages</a> and pick this campaign there.
            </p>
            <div x-show="landing === 'product'" x-cloak>
                <label class="{{ $labelCls }}">Product</label>
                <select name="product_id" class="{{ $inputCls }}">
                    <option value="">Choose a product…</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" @selected((int) old('product_id', $campaign->product_id) === $product->id)>{{ $product->storefrontDisplayName() }}</option>
                    @endforeach
                </select>
            </div>
            <div x-show="landing === 'category'" x-cloak>
                <label class="{{ $labelCls }}">Category</label>
                <select name="category_id" class="{{ $inputCls }}">
                    <option value="">Choose a category…</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((int) old('category_id', $campaign->category_id) === $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div x-show="landing === 'url'" x-cloak>
                <label class="{{ $labelCls }}">Page path</label>
                <input type="text" name="landing_url" value="{{ old('landing_url', $campaign->landing_url) }}" maxlength="500" placeholder="/shop?filter=deals" class="{{ $inputCls }} font-mono">
                <p class="mt-1 text-[11px] text-slate-500">Only pages on this website are allowed.</p>
            </div>
        </section>
    </div>

    <div class="space-y-4">
        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm space-y-3">
            <h2 class="text-[13px] font-bold text-slate-900">Schedule</h2>
            <div>
                <label class="{{ $labelCls }}">Status</label>
                <select name="status" class="{{ $inputCls }}">
                    @foreach(\App\Models\Campaign::STATUSES as $key => $label)
                        <option value="{{ $key }}" @selected(old('status', $campaign->status) === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="{{ $labelCls }}">Starts</label>
                    <input type="date" name="starts_on" value="{{ old('starts_on', $campaign->starts_on?->toDateString()) }}" class="{{ $inputCls }}">
                </div>
                <div>
                    <label class="{{ $labelCls }}">Ends</label>
                    <input type="date" name="ends_on" value="{{ old('ends_on', $campaign->ends_on?->toDateString()) }}" class="{{ $inputCls }}">
                </div>
            </div>
            <p class="text-[11px] text-slate-500">Clicks are counted only while the campaign is active and within these dates. The link always keeps working.</p>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm space-y-3">
            <h2 class="text-[13px] font-bold text-slate-900">Ad spend</h2>
            <div>
                <label class="{{ $labelCls }}">Total spent (৳)</label>
                <input type="number" name="spend" step="0.01" min="0" value="{{ old('spend', $campaign->spend) }}" placeholder="Optional" class="{{ $inputCls }}">
                <p class="mt-1 text-[11px] text-slate-500">Enter what you actually paid (from Ads Manager) to see cost per order and return on spend.</p>
            </div>
            <div>
                <label class="{{ $labelCls }}">Notes</label>
                <textarea name="notes" rows="3" maxlength="2000" class="{{ $inputCls }}" placeholder="Audience, creative, offer…">{{ old('notes', $campaign->notes) }}</textarea>
            </div>
        </section>

        <button type="submit" class="w-full rounded-xl bg-indigo-600 px-4 py-2.5 text-[13px] font-semibold text-white shadow-sm hover:bg-indigo-700">
            {{ $campaign->exists ? 'Save changes' : 'Create campaign' }}
        </button>
    </div>
</div>
