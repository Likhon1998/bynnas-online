<x-cms-layout
    title="Landing Page"
    subtitle="Store branding, trust features, and homepage promo banners. Changes sync to the public website."
    previewUrl="{{ route('home') }}"
>
    <form method="POST" action="{{ route('cms.landing.update') }}" enctype="multipart/form-data" class="space-y-6"
          x-data="{
            features: @js($features->map(fn($f)=>['id'=>$f->id,'icon'=>$f->icon,'title'=>$f->title,'subtitle'=>$f->subtitle,'sort_order'=>$f->sort_order,'is_active'=>$f->is_active])->values()),
            banners: @js($banners->map(fn($b)=>[
                'id'=>$b->id,
                'title'=>$b->title,
                'subtitle'=>$b->subtitle,
                'badge_text'=>$b->badge_text,
                'highlight_text'=>$b->highlight_text,
                'discount_badge'=>$b->discount_badge,
                'price_from'=>$b->price_from,
                'button_text'=>$b->button_text,
                'button_url'=>$b->button_url,
                'theme'=>$b->theme,
                'sort_order'=>$b->sort_order,
                'is_active'=>$b->is_active,
                'image_path'=>$b->image_path,
                'preview'=> $b->image_path ? public_storage_url($b->image_path) : null,
            ])->values()),
            heroSide: @js(($heroSide ?? collect())->map(fn($b)=>[
                'id'=>$b->id,
                'title'=>$b->title,
                'subtitle'=>$b->subtitle,
                'badge_text'=>$b->badge_text,
                'discount_badge'=>$b->discount_badge,
                'button_text'=>$b->button_text,
                'button_url'=>$b->button_url,
                'theme'=>$b->theme ?: 'dark',
                'sort_order'=>$b->sort_order,
                'is_active'=>$b->is_active,
                'image_path'=>$b->image_path,
                'preview'=> $b->image_path ? public_storage_url($b->image_path) : null,
            ])->values()),
            midPromo: @js(($midPromo ?? collect())->map(fn($b)=>[
                'id'=>$b->id,
                'title'=>$b->title,
                'subtitle'=>$b->subtitle,
                'badge_text'=>$b->badge_text,
                'highlight_text'=>$b->highlight_text,
                'discount_badge'=>$b->discount_badge,
                'price_from'=>$b->price_from,
                'button_text'=>$b->button_text,
                'button_url'=>$b->button_url,
                'theme'=>$b->theme ?: 'dark',
                'sort_order'=>$b->sort_order,
                'is_active'=>$b->is_active,
                'image_path'=>$b->image_path,
                'preview'=> $b->image_path ? public_storage_url($b->image_path) : null,
            ])->values()),
            addFeature(){ this.features.push({id:null,icon:'truck',title:'',subtitle:'',sort_order:this.features.length,is_active:true}) },
            addBanner(){ this.banners.push({id:null,title:'',subtitle:'',badge_text:'',highlight_text:'',discount_badge:'',price_from:'',button_text:'Shop Now',button_url:'/shop',theme:'dark',sort_order:this.banners.length,is_active:true,image_path:null,preview:null}) },
            addHeroSide(){
                if (this.heroSide.length >= 2) return;
                this.heroSide.push({id:null,title:'',subtitle:'',badge_text:'',discount_badge:'',button_text:'Shop',button_url:'/shop',theme:'dark',sort_order:this.heroSide.length,is_active:true,image_path:null,preview:null});
            },
            addMidPromo(){
                if (this.midPromo.length >= 12) return;
                this.midPromo.push({id:null,title:'',subtitle:'',badge_text:'',highlight_text:'',discount_badge:'',price_from:'',button_text:'Shop Now',button_url:'/shop',theme:'dark',sort_order:this.midPromo.length,is_active:true,image_path:null,preview:null});
            }
          }">
        @csrf
        @method('PUT')

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-base font-bold text-slate-900">Store identity</h3>
            <p class="text-sm text-slate-500 mt-1">Shown in the website header, footer, admin sidebar, login page, and browser title/tab.</p>
            <div class="mt-5 grid gap-4 md:grid-cols-2">
                <div>
                    <label class="text-xs font-bold uppercase text-slate-500">Store name</label>
                    <input name="store_name" value="{{ old('store_name', $settings->store_name) }}" class="mt-1 w-full rounded-xl border-slate-200" required>
                </div>
                <div>
                    <label class="text-xs font-bold uppercase text-slate-500">Full logo (with text)</label>
                    <x-image-file-preview
                        name="logo"
                        accept="image/*"
                        :existing="$settings->logo_path ? public_storage_url($settings->logo_path) : null"
                        preview-class="h-14 max-w-full object-contain rounded-lg bg-black p-1 border border-slate-200"
                    />
                    <p class="mt-1 text-[11px] text-slate-400">Header, footer, login, and admin sidebar. Black backgrounds are auto-removed. Preview shows before save.</p>
                </div>
                <div>
                    <label class="text-xs font-bold uppercase text-slate-500">Browser tab icon (favicon)</label>
                    <x-image-file-preview
                        name="favicon"
                        accept="image/png,image/jpeg,image/webp,image/gif,image/svg+xml,image/x-icon,.ico"
                        :existing="($settings->favicon_path ? public_storage_url($settings->favicon_path) : ($settings->logo_path ? public_storage_url($settings->logo_path) : asset('favicon.svg')))"
                        preview-class="h-10 w-10 rounded object-contain border border-slate-200 bg-white p-0.5"
                    />
                    <p class="mt-1 text-[11px] text-slate-400">Text-less icon for the browser tab. Extra black padding is cropped automatically.</p>
                </div>
                <div>
                    <label class="text-xs font-bold uppercase text-slate-500">Currency</label>
                    @php
                        $code = old('currency_code', $settings->currency_code ?: 'BDT');
                        $symbol = old('currency_symbol', $settings->currency_symbol ?: '৳');
                        // Heal phone-code mistakes (880 / +880 / 088) for the form display
                        if (preg_match('/^\+?\d{2,4}$/', trim((string) $symbol))) {
                            $symbol = $code === 'USD' ? '$' : '৳';
                        }
                        $presets = [
                            ['code' => 'BDT', 'symbol' => '৳', 'label' => 'Bangladesh Taka (৳)'],
                            ['code' => 'BDT', 'symbol' => 'Tk', 'label' => 'Bangladesh Taka (Tk)'],
                            ['code' => 'USD', 'symbol' => '$', 'label' => 'US Dollar ($)'],
                            ['code' => 'EUR', 'symbol' => '€', 'label' => 'Euro (€)'],
                            ['code' => 'GBP', 'symbol' => '£', 'label' => 'British Pound (£)'],
                            ['code' => 'INR', 'symbol' => '₹', 'label' => 'Indian Rupee (₹)'],
                        ];
                    @endphp
                    <div class="mt-1 grid gap-2 sm:grid-cols-2" x-data="{
                        code: @js($code),
                        symbol: @js($symbol),
                        apply(code, symbol) { this.code = code; this.symbol = symbol; }
                    }">
                        <div>
                            <label class="text-[10px] font-semibold text-slate-400">Code</label>
                            <input name="currency_code" x-model="code" class="mt-0.5 w-full rounded-xl border-slate-200" required maxlength="10">
                        </div>
                        <div>
                            <label class="text-[10px] font-semibold text-slate-400">Symbol (shown before prices)</label>
                            <input name="currency_symbol" x-model="symbol" class="mt-0.5 w-full rounded-xl border-slate-200" required maxlength="10"
                                   placeholder="৳ — not 880">
                        </div>
                        <div class="sm:col-span-2">
                            <p class="text-[11px] text-slate-500 mb-2">Quick pick — do <span class="font-bold text-rose-600">not</span> use phone codes like 880 / +880.</p>
                            <div class="flex flex-wrap gap-2">
                                @foreach($presets as $preset)
                                    <button type="button"
                                            @click="apply(@js($preset['code']), @js($preset['symbol']))"
                                            class="rounded-lg border px-2.5 py-1.5 text-[11px] font-bold transition"
                                            :class="code === @js($preset['code']) && symbol === @js($preset['symbol']) ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'">
                                        {{ $preset['label'] }}
                                    </button>
                                @endforeach
                            </div>
                            <p class="mt-2 text-[11px] text-slate-500">Preview: <span class="font-bold text-slate-800" x-text="symbol + '1,250.00'"></span></p>
                        </div>
                    </div>
                    @error('currency_symbol')
                        <p class="mt-1 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="hidden">
                    {{-- keep grid layout: removed duplicate currency fields --}}
                </div>
                <div>
                    <label class="text-xs font-bold uppercase text-slate-500">Special offer text</label>
                    <input name="special_offer_text" value="{{ old('special_offer_text', $settings->special_offer_text) }}" class="mt-1 w-full rounded-xl border-slate-200">
                </div>
                <div>
                    <label class="text-xs font-bold uppercase text-slate-500">Trusted-by text</label>
                    <input name="trusted_by_text" value="{{ old('trusted_by_text', $settings->trusted_by_text) }}" class="mt-1 w-full rounded-xl border-slate-200" placeholder="Shown under homepage features">
                </div>
                <div class="md:col-span-2">
                    <label class="text-xs font-bold uppercase text-slate-500">Footer tagline</label>
                    <input name="footer_tagline" value="{{ old('footer_tagline', $settings->footer_tagline) }}" class="mt-1 w-full rounded-xl border-slate-200" placeholder="Your one-stop shop for the latest tech gadgets…">
                </div>
                <div>
                    <label class="text-xs font-bold uppercase text-slate-500">Deals kicker</label>
                    <input name="deals_kicker" value="{{ old('deals_kicker', $settings->deals_kicker) }}" class="mt-1 w-full rounded-xl border-slate-200" placeholder="This week">
                </div>
                <div>
                    <label class="text-xs font-bold uppercase text-slate-500">Deals title</label>
                    <input name="deals_title" value="{{ old('deals_title', $settings->deals_title) }}" class="mt-1 w-full rounded-xl border-slate-200" placeholder="Featured">
                </div>
                <div>
                    <label class="text-xs font-bold uppercase text-slate-500">Deals accent word</label>
                    <input name="deals_title_accent" value="{{ old('deals_title_accent', $settings->deals_title_accent) }}" class="mt-1 w-full rounded-xl border-slate-200" placeholder="Deals">
                </div>
                <div class="md:col-span-2">
                    <label class="text-xs font-bold uppercase text-slate-500">Deals subtitle</label>
                    <input name="deals_subtitle" value="{{ old('deals_subtitle', $settings->deals_subtitle) }}" class="mt-1 w-full rounded-xl border-slate-200" placeholder="Premium gadgets at carefully chosen prices.">
                </div>
                <div class="md:col-span-2 rounded-xl border border-indigo-100 bg-indigo-50/50 px-4 py-3 text-xs text-indigo-800">
                    Contact email, phone &amp; address are managed under <a href="{{ route('cms.contact.index') }}" class="font-bold underline">CMS → Contact</a> so they stay in sync with the /contact page.
                </div>
            </div>
        </div>

        @php $homeCopy = old('home_copy', $settings->home_copy ?? []); @endphp
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-base font-bold text-slate-900">Homepage section titles</h3>
            <p class="text-sm text-slate-500 mt-0.5">Eyebrows, titles, and subtitles for Flash / New / Trending / Brands / Reviews / Blog / Categories.</p>
            <div class="mt-4 grid gap-3 md:grid-cols-2">
                @foreach([
                    'categories_eyebrow' => 'Categories eyebrow',
                    'categories_title' => 'Categories title',
                    'categories_title_accent' => 'Categories accent',
                    'categories_subtitle' => 'Categories subtitle',
                    'flash_eyebrow' => 'Flash eyebrow',
                    'flash_title' => 'Flash title',
                    'flash_title_accent' => 'Flash accent',
                    'flash_subtitle' => 'Flash subtitle',
                    'new_eyebrow' => 'New arrivals eyebrow',
                    'new_title' => 'New arrivals title',
                    'new_title_accent' => 'New arrivals accent',
                    'new_subtitle' => 'New arrivals subtitle',
                    'trending_eyebrow' => 'Trending eyebrow',
                    'trending_title' => 'Trending title',
                    'trending_title_accent' => 'Trending accent',
                    'trending_subtitle' => 'Trending subtitle',
                    'brands_eyebrow' => 'Brands eyebrow',
                    'brands_title' => 'Brands title',
                    'brands_title_accent' => 'Brands accent',
                    'brands_subtitle' => 'Brands subtitle',
                    'reviews_title' => 'Reviews title',
                    'reviews_subtitle' => 'Reviews subtitle',
                    'blog_eyebrow' => 'Blog eyebrow',
                    'blog_title' => 'Blog title',
                    'blog_title_accent' => 'Blog accent',
                    'blog_subtitle' => 'Blog subtitle',
                ] as $key => $label)
                    <div class="{{ str_ends_with($key, 'subtitle') ? 'md:col-span-2' : '' }}">
                        <label class="text-[11px] font-bold uppercase text-slate-500">{{ $label }}</label>
                        <input name="home_copy[{{ $key }}]" value="{{ old('home_copy.'.$key, $homeCopy[$key] ?? '') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
                    </div>
                @endforeach
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Homepage features</h3>
                    <p class="text-sm text-slate-500">Trust bar under the hero. Recommended: <strong>4 items</strong>. Fully CMS-managed — edits show on the storefront.</p>
                </div>
                <button type="button" @click="addFeature()" class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">+ Feature</button>
            </div>
            <div class="mt-4 space-y-3">
                <template x-for="(f, i) in features" :key="i">
                    <div class="grid gap-2 rounded-xl border border-slate-100 bg-slate-50/70 p-3 md:grid-cols-12">
                        <input type="hidden" :name="'features['+i+'][id]'" :value="f.id || ''">
                        <select :name="'features['+i+'][icon]'" x-model="f.icon" class="md:col-span-2 rounded-lg border-slate-200 text-sm">
                            <option value="truck">Truck</option>
                            <option value="return">Return</option>
                            <option value="lock">Lock</option>
                            <option value="shield">Shield</option>
                            <option value="support">Support</option>
                            <option value="shipping">Shipping</option>
                            <option value="payment">Payment</option>
                            <option value="warranty">Warranty</option>
                            <option value="chat">Chat</option>
                        </select>
                        <input :name="'features['+i+'][title]'" x-model="f.title" placeholder="Title" class="md:col-span-3 rounded-lg border-slate-200 text-sm">
                        <input :name="'features['+i+'][subtitle]'" x-model="f.subtitle" placeholder="Subtitle" class="md:col-span-4 rounded-lg border-slate-200 text-sm">
                        <input type="number" :name="'features['+i+'][sort_order]'" x-model="f.sort_order" class="md:col-span-1 rounded-lg border-slate-200 text-sm">
                        <label class="md:col-span-1 flex items-center gap-1 text-xs font-semibold text-slate-600">
                            <input type="checkbox" :name="'features['+i+'][is_active]'" value="1" x-model="f.is_active" class="rounded border-slate-300"> On
                        </label>
                        <button type="button" @click="features.splice(i,1)" class="md:col-span-1 text-xs font-bold text-rose-600">Remove</button>
                    </div>
                </template>
                <p x-show="features.length === 0" class="text-sm text-slate-400 py-2" x-cloak>No features yet. Click “+ Feature” to add items for the homepage trust bar.</p>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Hero side cards</h3>
                    <p class="text-sm text-slate-500">Two fixed cards beside the homepage hero slider. Max 2. Upload image, title, link — shown next to posters.</p>
                </div>
                <button type="button" @click="addHeroSide()" x-bind:disabled="heroSide.length >= 2" class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50 disabled:opacity-40">+ Side card</button>
            </div>
            <div class="mt-4 space-y-4">
                <template x-for="(b, i) in heroSide" :key="'hs-'+i">
                    <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-4 space-y-3">
                        <input type="hidden" :name="'hero_side['+i+'][id]'" :value="b.id || ''">
                        <div class="grid gap-2 md:grid-cols-2">
                            <input :name="'hero_side['+i+'][title]'" x-model="b.title" placeholder="Title" class="rounded-lg border-slate-200 text-sm">
                            <input :name="'hero_side['+i+'][subtitle]'" x-model="b.subtitle" placeholder="Short subtitle" class="rounded-lg border-slate-200 text-sm">
                            <input :name="'hero_side['+i+'][badge_text]'" x-model="b.badge_text" placeholder="Badge (optional)" class="rounded-lg border-slate-200 text-sm">
                            <input :name="'hero_side['+i+'][discount_badge]'" x-model="b.discount_badge" placeholder="Corner label (optional)" class="rounded-lg border-slate-200 text-sm">
                            <input :name="'hero_side['+i+'][button_url]'" x-model="b.button_url" placeholder="Link URL" class="rounded-lg border-slate-200 text-sm">
                            <input :name="'hero_side['+i+'][button_text]'" x-model="b.button_text" placeholder="CTA text" class="rounded-lg border-slate-200 text-sm">
                            <select :name="'hero_side['+i+'][theme]'" x-model="b.theme" class="rounded-lg border-slate-200 text-sm">
                                <option value="dark">Dark card</option>
                                <option value="light">Light card</option>
                            </select>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <template x-if="b.preview">
                                    <img :src="b.preview" alt="" class="h-14 w-14 rounded-lg object-cover border border-slate-200 bg-white">
                                </template>
                                <div>
                                    <label class="text-[11px] font-bold uppercase text-slate-500">Card image</label>
                                    <input type="file" :name="'hero_side['+i+'][image]'" accept="image/jpeg,image/png,image/webp,image/gif" class="mt-1 block text-sm"
                                           @change="
                                                const file = $event.target.files && $event.target.files[0];
                                                if (b._blob) { URL.revokeObjectURL(b._blob); b._blob = null; }
                                                if (file) {
                                                    b._blob = URL.createObjectURL(file);
                                                    b.preview = b._blob;
                                                }
                                           ">
                                </div>
                            </div>
                            <input type="number" :name="'hero_side['+i+'][sort_order]'" x-model="b.sort_order" class="w-20 rounded-lg border-slate-200 text-sm" title="Sort order">
                            <label class="flex items-center gap-1 text-xs font-semibold text-slate-600">
                                <input type="checkbox" :name="'hero_side['+i+'][is_active]'" value="1" x-model="b.is_active" class="rounded border-slate-300"> Active
                            </label>
                            <button type="button" @click="heroSide.splice(i,1)" class="text-xs font-bold text-rose-600">Remove</button>
                        </div>
                    </div>
                </template>
                <p x-show="heroSide.length === 0" class="text-sm text-slate-400 py-2" x-cloak>No hero side cards yet. Add up to 2 cards for the homepage hero.</p>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Mid promo banner</h3>
                    <p class="text-sm text-slate-500">Continuous sliding strip between Flash Sale and New Arrivals. Add at least 3 banners for a smooth loop (up to 12). Image ≈ 900×500 works best.</p>
                </div>
                <button type="button" @click="addMidPromo()" x-bind:disabled="midPromo.length >= 12" class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50 disabled:opacity-40">+ Mid banner</button>
            </div>
            <div class="mt-4 space-y-4">
                <template x-for="(b, i) in midPromo" :key="'mp-'+i">
                    <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-4 space-y-3">
                        <input type="hidden" :name="'mid_promo['+i+'][id]'" :value="b.id || ''">
                        <div class="grid gap-2 md:grid-cols-2">
                            <input :name="'mid_promo['+i+'][title]'" x-model="b.title" placeholder="Headline (required)" class="rounded-lg border-slate-200 text-sm md:col-span-2">
                            <input :name="'mid_promo['+i+'][subtitle]'" x-model="b.subtitle" placeholder="Supporting line" class="rounded-lg border-slate-200 text-sm md:col-span-2">
                            <input :name="'mid_promo['+i+'][badge_text]'" x-model="b.badge_text" placeholder="Badge (e.g. Limited drop)" class="rounded-lg border-slate-200 text-sm">
                            <input :name="'mid_promo['+i+'][highlight_text]'" x-model="b.highlight_text" placeholder="Highlight in subtitle (optional)" class="rounded-lg border-slate-200 text-sm">
                            <input :name="'mid_promo['+i+'][discount_badge]'" x-model="b.discount_badge" placeholder="Corner label (e.g. −40%)" class="rounded-lg border-slate-200 text-sm">
                            <input :name="'mid_promo['+i+'][price_from]'" x-model="b.price_from" type="number" step="0.01" min="0" placeholder="From price (optional)" class="rounded-lg border-slate-200 text-sm">
                            <input :name="'mid_promo['+i+'][button_url]'" x-model="b.button_url" placeholder="Link URL" class="rounded-lg border-slate-200 text-sm">
                            <input :name="'mid_promo['+i+'][button_text]'" x-model="b.button_text" placeholder="CTA text" class="rounded-lg border-slate-200 text-sm">
                            <select :name="'mid_promo['+i+'][theme]'" x-model="b.theme" class="rounded-lg border-slate-200 text-sm">
                                <option value="dark">Dark overlay</option>
                                <option value="light">Light overlay</option>
                            </select>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <template x-if="b.preview">
                                    <img :src="b.preview" alt="" class="h-16 w-28 rounded-lg object-cover border border-slate-200 bg-white">
                                </template>
                                <div>
                                    <label class="text-[11px] font-bold uppercase text-slate-500">Banner image</label>
                                    <input type="file" :name="'mid_promo['+i+'][image]'" accept="image/jpeg,image/png,image/webp,image/gif" class="mt-1 block text-sm"
                                           @change="
                                                const file = $event.target.files && $event.target.files[0];
                                                if (b._blob) { URL.revokeObjectURL(b._blob); b._blob = null; }
                                                if (file) {
                                                    b._blob = URL.createObjectURL(file);
                                                    b.preview = b._blob;
                                                }
                                           ">
                                </div>
                            </div>
                            <input type="number" :name="'mid_promo['+i+'][sort_order]'" x-model="b.sort_order" class="w-20 rounded-lg border-slate-200 text-sm" title="Sort order">
                            <label class="flex items-center gap-1 text-xs font-semibold text-slate-600">
                                <input type="checkbox" :name="'mid_promo['+i+'][is_active]'" value="1" x-model="b.is_active" class="rounded border-slate-300"> Active
                            </label>
                            <button type="button" @click="midPromo.splice(i,1)" class="text-xs font-bold text-rose-600">Remove</button>
                        </div>
                    </div>
                </template>
                <p x-show="midPromo.length === 0" class="text-sm text-slate-400 py-2" x-cloak>No mid promo yet. Click “+ Mid banner” to add cards — they scroll continuously on the homepage.</p>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Promo banners (Deals You’ll Love)</h3>
                    <p class="text-sm text-slate-500">Homepage deal cards. Use Dark / Light theme. Optional badge, highlight text (e.g. 40%), and discount circle (−40%).</p>
                </div>
                <button type="button" @click="addBanner()" class="rounded-xl border border-slate-200 px-3 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">+ Banner</button>
            </div>
            <div class="mt-4 space-y-4">
                <template x-for="(b, i) in banners" :key="i">
                    <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-4 space-y-3">
                        <input type="hidden" :name="'banners['+i+'][id]'" :value="b.id || ''">
                        <div class="grid gap-2 md:grid-cols-2">
                            <input :name="'banners['+i+'][title]'" x-model="b.title" placeholder="Title (e.g. SOMOSTEL B2)" class="rounded-lg border-slate-200 text-sm">
                            <input :name="'banners['+i+'][subtitle]'" x-model="b.subtitle" placeholder="Subtitle (e.g. Up to 40% Off)" class="rounded-lg border-slate-200 text-sm">
                            <input :name="'banners['+i+'][badge_text]'" x-model="b.badge_text" placeholder="Badge (e.g. BEST SELLER)" class="rounded-lg border-slate-200 text-sm">
                            <input :name="'banners['+i+'][highlight_text]'" x-model="b.highlight_text" placeholder="Highlight in subtitle (e.g. 40%)" class="rounded-lg border-slate-200 text-sm">
                            <input :name="'banners['+i+'][discount_badge]'" x-model="b.discount_badge" placeholder="Corner badge (e.g. -40%)" class="rounded-lg border-slate-200 text-sm">
                            <input type="number" step="0.01" :name="'banners['+i+'][price_from]'" x-model="b.price_from" placeholder="Price from" class="rounded-lg border-slate-200 text-sm">
                            <input :name="'banners['+i+'][button_url]'" x-model="b.button_url" placeholder="Button URL" class="rounded-lg border-slate-200 text-sm">
                            <input :name="'banners['+i+'][button_text]'" x-model="b.button_text" placeholder="Button text" class="rounded-lg border-slate-200 text-sm">
                            <select :name="'banners['+i+'][theme]'" x-model="b.theme" class="rounded-lg border-slate-200 text-sm">
                                <option value="dark">Dark (pink CTA)</option>
                                <option value="light">Light (blue CTA)</option>
                            </select>
                        </div>
                        <div class="flex flex-wrap items-center gap-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <template x-if="b.preview">
                                    <img :src="b.preview" alt="" class="h-14 w-14 rounded-lg object-cover border border-slate-200 bg-white">
                                </template>
                                <div>
                                    <label class="text-[11px] font-bold uppercase text-slate-500">Banner image</label>
                                    <input type="file" :name="'banners['+i+'][image]'" accept="image/jpeg,image/png,image/webp,image/gif" class="mt-1 block text-sm"
                                           @change="
                                                const file = $event.target.files && $event.target.files[0];
                                                if (b._blob) { URL.revokeObjectURL(b._blob); b._blob = null; }
                                                if (file) {
                                                    b._blob = URL.createObjectURL(file);
                                                    b.preview = b._blob;
                                                }
                                           ">
                                    <p class="mt-1 text-[10px] text-emerald-700 font-medium" x-show="b.preview" x-cloak>Preview before save</p>
                                </div>
                            </div>
                            <input type="number" :name="'banners['+i+'][sort_order]'" x-model="b.sort_order" class="w-20 rounded-lg border-slate-200 text-sm" title="Sort order">
                            <label class="flex items-center gap-1 text-xs font-semibold text-slate-600">
                                <input type="checkbox" :name="'banners['+i+'][is_active]'" value="1" x-model="b.is_active" class="rounded border-slate-300"> Active
                            </label>
                            <button type="button" @click="banners.splice(i,1)" class="text-xs font-bold text-rose-600">Remove</button>
                        </div>
                    </div>
                </template>
                <p x-show="banners.length === 0" class="text-sm text-slate-400 py-2" x-cloak>No promo banners yet. Click “+ Banner” and upload an image for each card.</p>
            </div>
        </div>

        <div class="flex justify-end">
            <button class="rounded-xl bg-indigo-600 px-6 py-2.5 text-sm font-bold text-white hover:bg-indigo-700">Save landing page</button>
        </div>
    </form>
</x-cms-layout>
