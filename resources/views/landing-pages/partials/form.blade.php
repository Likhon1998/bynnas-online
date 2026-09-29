@php
    $inputCls = 'w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-[13px] text-slate-800 focus:border-indigo-400 focus:ring-1 focus:ring-indigo-200';
    $labelCls = 'block text-[11px] font-semibold uppercase tracking-wide text-slate-500 mb-1';
    $selectedProducts = array_map('intval', old('product_ids', $page->product_ids ?? []));
    $benefits = old('benefits', $page->benefits ?: []);
    $faqs = old('faqs', $page->faqs ?: []);
@endphp

@if($errors->any())
    <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-[12px] text-rose-700">
        @foreach($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<div class="grid gap-4 lg:grid-cols-[1fr_320px]"
     x-data="{ benefits: @js(array_values($benefits)), faqs: @js(array_values($faqs)), search: '' }">
    <div class="min-w-0 space-y-4">
        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm space-y-3">
            <h2 class="text-[13px] font-bold text-slate-900">Hero</h2>
            <div>
                <label class="{{ $labelCls }}">Headline</label>
                <input type="text" name="headline" value="{{ old('headline', $page->headline) }}" required maxlength="200" placeholder="e.g. Premium cotton panjabi — Eid special" class="{{ $inputCls }}">
            </div>
            <div>
                <label class="{{ $labelCls }}">Sub-headline</label>
                <textarea name="subheadline" rows="2" maxlength="500" class="{{ $inputCls }}" placeholder="One or two lines on why this is worth buying">{{ old('subheadline', $page->subheadline) }}</textarea>
            </div>
            <div>
                <label class="{{ $labelCls }}">Hero image</label>
                @if($page->heroImageUrl())
                    <div class="mb-2 flex items-center gap-3">
                        <img src="{{ $page->heroImageUrl() }}" alt="" class="h-16 w-16 rounded-lg object-cover">
                        <label class="flex items-center gap-1.5 text-[12px] text-slate-600"><input type="checkbox" name="remove_hero" value="1" class="rounded border-slate-300"> Remove</label>
                    </div>
                @endif
                <input type="file" name="hero" accept="image/*" class="block w-full text-[12px] text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-[12px] file:font-semibold">
                <p class="mt-1 text-[11px] text-slate-500">Optional — the first product photo is used if empty. Square images look best on phones.</p>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm space-y-3">
            <h2 class="text-[13px] font-bold text-slate-900">Offer</h2>
            <div class="grid gap-3 sm:grid-cols-[180px_1fr]">
                <div>
                    <label class="{{ $labelCls }}">Badge</label>
                    <input type="text" name="offer_badge" value="{{ old('offer_badge', $page->offer_badge) }}" maxlength="60" placeholder="30% OFF" class="{{ $inputCls }}">
                </div>
                <div>
                    <label class="{{ $labelCls }}">Offer line</label>
                    <input type="text" name="offer_text" value="{{ old('offer_text', $page->offer_text) }}" maxlength="500" placeholder="Free delivery on 2 or more — today only" class="{{ $inputCls }}">
                </div>
            </div>
            <div class="sm:w-1/2">
                <label class="{{ $labelCls }}">Countdown ends</label>
                <input type="datetime-local" name="countdown_ends_at" value="{{ old('countdown_ends_at', $page->countdown_ends_at?->format('Y-m-d\TH:i')) }}" class="{{ $inputCls }}">
                <p class="mt-1 text-[11px] text-slate-500">Shows a real timer to this date. Leave empty for no countdown.</p>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="text-[13px] font-bold text-slate-900">Products</h2>
                    <p class="text-[11px] text-slate-500">Visitors can pick any size / colour of the chosen products. Up to 12.</p>
                </div>
                <input type="search" x-model="search" placeholder="Search products" class="w-48 rounded-lg border border-slate-200 px-3 py-1.5 text-[12px]">
            </div>
            <div class="mt-3 max-h-64 space-y-1 overflow-y-auto rounded-lg border border-slate-100 p-2">
                @forelse($products as $product)
                    @php $label = $product->storefrontDisplayName(); @endphp
                    <label class="flex items-center gap-2 rounded-md px-2 py-1.5 text-[13px] hover:bg-slate-50"
                           x-show="!search || @js(\Illuminate\Support\Str::lower($label)).includes(search.toLowerCase())">
                        <input type="checkbox" name="product_ids[]" value="{{ $product->id }}" @checked(in_array($product->id, $selectedProducts, true)) class="rounded border-slate-300 text-indigo-600">
                        <span class="text-slate-800">{{ $label }}</span>
                    </label>
                @empty
                    <p class="px-2 py-3 text-[12px] text-slate-500">No published products yet.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="text-[13px] font-bold text-slate-900">Benefits</h2>
                <button type="button" @click="benefits.length < 8 && benefits.push({ title: '', text: '' })" class="rounded-md border border-slate-200 px-2 py-1 text-[11px] font-semibold text-slate-700 hover:bg-slate-50">+ Add</button>
            </div>
            <div class="mt-3 space-y-2">
                <template x-for="(benefit, i) in benefits" :key="i">
                    <div class="grid gap-2 sm:grid-cols-[200px_1fr_auto]">
                        <input type="text" :name="`benefits[${i}][title]`" x-model="benefit.title" maxlength="80" placeholder="Title" class="{{ $inputCls }}">
                        <input type="text" :name="`benefits[${i}][text]`" x-model="benefit.text" maxlength="200" placeholder="Short explanation" class="{{ $inputCls }}">
                        <button type="button" @click="benefits.splice(i, 1)" class="rounded-lg px-2 text-[12px] text-rose-600 hover:bg-rose-50">Remove</button>
                    </div>
                </template>
                <p x-show="benefits.length === 0" class="text-[12px] text-slate-500">No benefits — the section is hidden.</p>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="text-[13px] font-bold text-slate-900">FAQ</h2>
                <button type="button" @click="faqs.length < 12 && faqs.push({ q: '', a: '' })" class="rounded-md border border-slate-200 px-2 py-1 text-[11px] font-semibold text-slate-700 hover:bg-slate-50">+ Add</button>
            </div>
            <div class="mt-3 space-y-3">
                <template x-for="(faq, i) in faqs" :key="i">
                    <div class="space-y-1.5 rounded-lg border border-slate-100 p-2">
                        <div class="flex gap-2">
                            <input type="text" :name="`faqs[${i}][q]`" x-model="faq.q" maxlength="200" placeholder="Question" class="{{ $inputCls }}">
                            <button type="button" @click="faqs.splice(i, 1)" class="rounded-lg px-2 text-[12px] text-rose-600 hover:bg-rose-50">Remove</button>
                        </div>
                        <textarea :name="`faqs[${i}][a]`" x-model="faq.a" rows="2" maxlength="1000" placeholder="Answer" class="{{ $inputCls }}"></textarea>
                    </div>
                </template>
                <p x-show="faqs.length === 0" class="text-[12px] text-slate-500">No questions — the section is hidden.</p>
            </div>
        </section>
    </div>

    <div class="space-y-4">
        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm space-y-3">
            <h2 class="text-[13px] font-bold text-slate-900">Page</h2>
            <div>
                <label class="{{ $labelCls }}">Internal name</label>
                <input type="text" name="title" value="{{ old('title', $page->title) }}" required maxlength="160" placeholder="Eid panjabi offer" class="{{ $inputCls }}">
            </div>
            <div>
                <label class="{{ $labelCls }}">Web address</label>
                <div class="flex items-center rounded-lg border border-slate-200 bg-slate-50 text-[12px]">
                    <span class="pl-3 text-slate-400">/campaign/</span>
                    <input type="text" name="slug" value="{{ old('slug', $page->slug) }}" maxlength="160" placeholder="auto" class="min-w-0 flex-1 border-0 bg-transparent px-1 py-2 font-mono text-[12px] focus:ring-0">
                </div>
            </div>
            <div>
                <label class="{{ $labelCls }}">Status</label>
                <select name="status" class="{{ $inputCls }}">
                    @foreach(\App\Models\LandingPage::STATUSES as $key => $label)
                        <option value="{{ $key }}" @selected(old('status', $page->status) === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $labelCls }}">Campaign</label>
                <select name="campaign_id" class="{{ $inputCls }}">
                    <option value="">None</option>
                    @foreach($campaigns as $campaign)
                        <option value="{{ $campaign->id }}" @selected((int) old('campaign_id', $page->campaign_id) === $campaign->id)>{{ $campaign->name }}{{ $campaign->status !== 'active' ? ' ('.$campaign->status.')' : '' }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-[11px] text-slate-500">Orders from this page are credited to the campaign when the visitor has no other tracked source.</p>
            </div>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm space-y-3">
            <h2 class="text-[13px] font-bold text-slate-900">Look &amp; call to action</h2>
            <div class="grid grid-cols-[1fr_90px] gap-2">
                <div>
                    <label class="{{ $labelCls }}">Button text</label>
                    <input type="text" name="cta_text" value="{{ old('cta_text', $page->cta_text) }}" maxlength="60" placeholder="Order now" class="{{ $inputCls }}">
                </div>
                <div>
                    <label class="{{ $labelCls }}">Colour</label>
                    <input type="color" name="accent_color" value="{{ old('accent_color', $page->accent()) }}" class="h-[38px] w-full cursor-pointer rounded-lg border border-slate-200 bg-white p-1">
                </div>
            </div>
            <label class="flex items-center gap-2 text-[12px] text-slate-700">
                <input type="hidden" name="show_reviews" value="0">
                <input type="checkbox" name="show_reviews" value="1" @checked(old('show_reviews', $page->show_reviews)) class="rounded border-slate-300 text-indigo-600">
                Show published customer reviews
            </label>
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm space-y-3">
            <h2 class="text-[13px] font-bold text-slate-900">Search &amp; sharing</h2>
            <div>
                <label class="{{ $labelCls }}">SEO title</label>
                <input type="text" name="seo_title" value="{{ old('seo_title', $page->seo_title) }}" maxlength="160" placeholder="Uses the headline if empty" class="{{ $inputCls }}">
            </div>
            <div>
                <label class="{{ $labelCls }}">Description</label>
                <textarea name="seo_description" rows="2" maxlength="300" class="{{ $inputCls }}">{{ old('seo_description', $page->seo_description) }}</textarea>
            </div>
        </section>

        <button type="submit" class="w-full rounded-xl bg-indigo-600 px-4 py-2.5 text-[13px] font-semibold text-white shadow-sm hover:bg-indigo-700">
            {{ $page->exists ? 'Save changes' : 'Create landing page' }}
        </button>
    </div>
</div>
