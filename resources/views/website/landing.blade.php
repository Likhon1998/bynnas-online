@php
    $storeName = $settings->store_name ?? config('app.name', 'Bynnas Social');
    $accent = $page->accent();
    $cta = $page->cta_text ?: 'Order now';
    $currency = $deliveryConfig['currency_symbol'] ?? '৳';

    $offers = $families->map(function ($family, $selectedId) use ($ws) {
        $lead = $family->firstWhere('id', $selectedId) ?? $family->first();

        return [
            'key' => (string) $selectedId,
            'name' => $lead->storefrontDisplayName(),
            'image' => $ws->productImageUrl($lead),
            'variants' => $family->map(fn ($p) => [
                'id' => $p->id,
                'label' => $p->variantLabel() ?: $p->storefrontDisplayName(),
                'price' => round($p->currentPrice(), 2),
                'compare' => $p->compareAtPrice(),
                'stock' => $p->availableStock(),
                'image' => $ws->productImageUrl($p),
            ])->values()->all(),
        ];
    })->values();

    $oldProduct = (int) old('product_id');
    $initialKey = $offers->first(fn ($o) => collect($o['variants'])->contains('id', $oldProduct))['key'] ?? ($offers->first()['key'] ?? null);
    $done = session('landing_order');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $page->seo_title ?: $page->headline }} · {{ $storeName }}</title>
    <meta name="description" content="{{ $page->seo_description ?: \Illuminate\Support\Str::limit((string) ($page->subheadline ?: $page->offer_text), 160) }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $page->seo_title ?: $page->headline }}">
    <meta property="og:url" content="{{ $page->publicUrl() }}">
    @if($page->heroImageUrl() || ($offers->first()['image'] ?? null))
        <meta property="og:image" content="{{ $page->heroImageUrl() ?: $offers->first()['image'] }}">
    @endif
    @if($isPreview)
        <meta name="robots" content="noindex">
    @endif
    @include('partials.favicon', ['settings' => $settings])
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root { --lp-accent: {{ $accent }}; }
        .lp-accent-bg { background-color: var(--lp-accent); }
        .lp-accent-text { color: var(--lp-accent); }
        .lp-accent-border { border-color: var(--lp-accent); }
        .lp-accent-ring:focus { outline: 2px solid var(--lp-accent); outline-offset: 1px; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-slate-50 font-sans text-slate-800 antialiased" data-alpine-now>
<div x-data="landingPage()" class="pb-24 md:pb-0">
    @if($isPreview)
        <div class="bg-amber-400 px-4 py-2 text-center text-[13px] font-semibold text-amber-950">Draft preview — only staff can see this page. Publish it to share.</div>
    @endif

    <header class="sticky top-0 z-30 border-b border-slate-200/70 bg-white/90 backdrop-blur">
        <div class="mx-auto flex max-w-5xl items-center justify-between px-4 py-3">
            <a href="{{ route('home') }}" class="flex items-center gap-2 font-extrabold text-slate-900">
                @if($settings->logo_path ?? null)
                    <img src="{{ asset('storage/'.$settings->logo_path) }}" alt="{{ $storeName }}" class="h-8 w-auto">
                @else
                    <span>{{ $storeName }}</span>
                @endif
            </a>
            <a href="#order" class="lp-accent-bg rounded-full px-4 py-2 text-[13px] font-bold text-white shadow-sm">{{ $cta }}</a>
        </div>
    </header>

    {{-- Hero --}}
    <section class="mx-auto max-w-5xl px-4 pt-6 md:grid md:grid-cols-2 md:items-center md:gap-10 md:pt-12">
        <div>
            @if($page->offer_badge)
                <span class="lp-accent-bg inline-block rounded-full px-3 py-1 text-[12px] font-extrabold uppercase tracking-wide text-white">{{ $page->offer_badge }}</span>
            @endif
            <h1 class="mt-3 text-[28px] font-extrabold leading-tight text-slate-900 md:text-[42px]">{{ $page->headline }}</h1>
            @if($page->subheadline)
                <p class="mt-3 text-[15px] leading-relaxed text-slate-600 md:text-[17px]">{{ $page->subheadline }}</p>
            @endif
            @if($offers->isNotEmpty())
                @php $fromPrice = $offers->flatMap(fn ($o) => collect($o['variants'])->pluck('price'))->min(); @endphp
                <p class="mt-4 text-[15px] text-slate-600">From <span class="lp-accent-text text-[26px] font-extrabold">{{ $currency }}{{ format_taka_number($fromPrice) }}</span></p>
            @endif
            <div class="mt-5 hidden md:block">
                <a href="#order" class="lp-accent-bg inline-flex rounded-xl px-7 py-3.5 text-[15px] font-bold text-white shadow-lg">{{ $cta }}</a>
                @if($deliveryConfig['cod_enabled'] ?? true)
                    <span class="ml-3 text-[13px] text-slate-500">Cash on delivery available</span>
                @endif
            </div>
        </div>
        <div class="mt-6 md:mt-0">
            @if($page->heroImageUrl() || ($offers->first()['image'] ?? null))
                <img src="{{ $page->heroImageUrl() ?: $offers->first()['image'] }}" alt="{{ $page->headline }}" class="aspect-square w-full rounded-3xl bg-white object-cover shadow-xl md:aspect-[4/5]">
            @endif
        </div>
    </section>

    {{-- Offer + countdown --}}
    @if($page->offer_text || $page->countdownActive())
        <section class="mx-auto mt-8 max-w-5xl px-4">
            <div class="lp-accent-border rounded-2xl border-2 border-dashed bg-white p-5 text-center shadow-sm">
                @if($page->offer_text)
                    <p class="text-[16px] font-bold text-slate-900 md:text-[18px]">{{ $page->offer_text }}</p>
                @endif
                @if($page->countdownActive())
                    <div class="mt-3" x-show="!expired">
                        <p class="text-[12px] font-semibold uppercase tracking-wide text-slate-500">Offer ends in</p>
                        <div class="mt-2 flex justify-center gap-2">
                            <template x-for="unit in [['d', 'Days'], ['h', 'Hours'], ['m', 'Min'], ['s', 'Sec']]" :key="unit[0]">
                                <div class="w-16 rounded-xl bg-slate-900 py-2 text-white">
                                    <div class="text-[22px] font-extrabold tabular-nums" x-text="String(countdown[unit[0]]).padStart(2, '0')"></div>
                                    <div class="text-[10px] uppercase tracking-wide text-slate-300" x-text="unit[1]"></div>
                                </div>
                            </template>
                        </div>
                    </div>
                    <p x-show="expired" x-cloak class="mt-2 text-[13px] font-semibold text-rose-600">This offer has ended.</p>
                @endif
            </div>
        </section>
    @endif

    {{-- Products --}}
    @if($offers->count() > 1)
        <section class="mx-auto mt-10 max-w-5xl px-4">
            <h2 class="text-center text-[22px] font-extrabold text-slate-900">Choose your pick</h2>
            <div class="mt-5 grid grid-cols-2 gap-3 md:grid-cols-3">
                @foreach($offers as $offer)
                    @php $first = $offer['variants'][0]; @endphp
                    <div class="flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        @if($offer['image'])
                            <img src="{{ $offer['image'] }}" alt="{{ $offer['name'] }}" class="aspect-square w-full object-cover" loading="lazy">
                        @endif
                        <div class="flex flex-1 flex-col p-3">
                            <p class="text-[14px] font-bold leading-snug text-slate-900">{{ $offer['name'] }}</p>
                            <p class="mt-1 text-[15px] font-extrabold lp-accent-text">{{ $currency }}{{ format_taka_number($first['price']) }}
                                @if($first['compare'])
                                    <span class="ml-1 text-[12px] font-medium text-slate-400 line-through">{{ $currency }}{{ format_taka_number($first['compare']) }}</span>
                                @endif
                            </p>
                            <button type="button" @click="pick(@js($offer['key']))" class="lp-accent-bg mt-auto rounded-lg px-3 py-2 text-[13px] font-bold text-white" style="margin-top: 0.75rem">Buy now</button>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Benefits --}}
    @if(! empty($page->benefits))
        <section class="mx-auto mt-10 max-w-5xl px-4">
            <div class="grid gap-3 sm:grid-cols-2 md:grid-cols-3">
                @foreach($page->benefits as $benefit)
                    <div class="flex gap-3 rounded-2xl bg-white p-4 shadow-sm">
                        <span class="lp-accent-bg flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-white">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        </span>
                        <div>
                            <p class="text-[14px] font-bold text-slate-900">{{ $benefit['title'] }}</p>
                            @if(! empty($benefit['text']))
                                <p class="mt-0.5 text-[13px] text-slate-600">{{ $benefit['text'] }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Reviews --}}
    @if($reviews->isNotEmpty())
        <section class="mx-auto mt-10 max-w-5xl px-4">
            <h2 class="text-center text-[22px] font-extrabold text-slate-900">What customers say</h2>
            <div class="mt-5 grid gap-3 md:grid-cols-3">
                @foreach($reviews as $review)
                    <figure class="rounded-2xl bg-white p-4 shadow-sm">
                        <div class="text-amber-400" aria-label="{{ $review->rating }} out of 5">{{ str_repeat('★', max(1, min(5, (int) $review->rating))) }}<span class="text-slate-200">{{ str_repeat('★', 5 - max(1, min(5, (int) $review->rating))) }}</span></div>
                        <blockquote class="mt-2 text-[14px] leading-relaxed text-slate-700">“{{ $review->body }}”</blockquote>
                        <figcaption class="mt-3 text-[13px] font-semibold text-slate-900">{{ $review->customer_name }}
                            @if($review->customer_title)<span class="font-normal text-slate-500"> · {{ $review->customer_title }}</span>@endif
                        </figcaption>
                    </figure>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Order form --}}
    <section id="order" class="mx-auto mt-10 max-w-xl scroll-mt-20 px-4">
        @if($done)
            <div class="rounded-3xl border border-emerald-200 bg-white p-6 text-center shadow-lg">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-emerald-600">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                </div>
                <h2 class="mt-3 text-[22px] font-extrabold text-slate-900">Thank you! Order received.</h2>
                <p class="mt-1 text-[14px] text-slate-600">Order ID <span class="font-bold text-slate-900">{{ $done['invoice'] }}</span> · Total {{ $currency }}{{ format_taka_number($done['total']) }}</p>
                <p class="mt-2 text-[13px] text-slate-500">We will call you shortly to confirm. Keep your phone nearby.</p>
                <a href="{{ route('website.track') }}" class="mt-4 inline-block text-[13px] font-semibold lp-accent-text underline">Track your order</a>
            </div>
        @elseif($offers->isEmpty())
            <div class="rounded-3xl bg-white p-6 text-center text-[14px] text-slate-500 shadow-sm">This offer is not available right now.</div>
        @else
            <form method="POST" action="{{ route('website.landing.order', $page->slug) }}" @submit="submitting = true"
                  class="rounded-3xl border border-slate-200 bg-white p-5 shadow-lg md:p-6">
                @csrf
                <h2 class="text-[22px] font-extrabold text-slate-900">Place your order</h2>
                <p class="text-[13px] text-slate-500">No account needed. Fill in your details and we will deliver.</p>

                @if($errors->any())
                    <div class="mt-4 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-[13px] text-rose-700">
                        @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                    </div>
                @endif

                <div class="absolute -left-[9999px]" aria-hidden="true">
                    <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                </div>

                @if($offers->count() > 1)
                    <label class="mt-4 block text-[12px] font-bold uppercase tracking-wide text-slate-500">Product</label>
                    <div class="mt-1.5 grid gap-2">
                        <template x-for="offer in offers" :key="offer.key">
                            <button type="button" @click="pick(offer.key, false)"
                                    class="flex items-center gap-3 rounded-xl border-2 p-2 text-left"
                                    :class="offerKey === offer.key ? 'lp-accent-border bg-slate-50' : 'border-slate-200'">
                                <img :src="offer.image" alt="" class="h-12 w-12 rounded-lg object-cover" x-show="offer.image">
                                <span class="flex-1 text-[14px] font-semibold text-slate-900" x-text="offer.name"></span>
                                <span class="text-[14px] font-bold lp-accent-text" x-text="money(offer.variants[0].price)"></span>
                            </button>
                        </template>
                    </div>
                @endif

                <div x-show="current && current.variants.length > 1" x-cloak>
                    <label class="mt-4 block text-[12px] font-bold uppercase tracking-wide text-slate-500">Option</label>
                    <div class="mt-1.5 flex flex-wrap gap-2">
                        <template x-for="v in (current ? current.variants : [])" :key="v.id">
                            <button type="button" @click="v.stock > 0 && (variantId = v.id)" :disabled="v.stock < 1"
                                    class="rounded-lg border-2 px-3 py-2 text-[13px] font-semibold"
                                    :class="variantId === v.id ? 'lp-accent-border lp-accent-text bg-slate-50' : (v.stock < 1 ? 'border-slate-100 text-slate-300 line-through' : 'border-slate-200 text-slate-700')"
                                    x-text="v.label"></button>
                        </template>
                    </div>
                </div>
                <input type="hidden" name="product_id" :value="variantId">

                <div class="mt-4 flex items-center justify-between">
                    <label class="text-[12px] font-bold uppercase tracking-wide text-slate-500">Quantity</label>
                    <div class="flex items-center rounded-xl border border-slate-200">
                        <button type="button" class="px-4 py-2 text-[18px] font-bold" @click="qty = Math.max(1, qty - 1)">−</button>
                        <input type="number" name="qty" x-model.number="qty" min="1" :max="maxQty" class="w-12 border-0 text-center text-[15px] font-bold focus:ring-0" readonly>
                        <button type="button" class="px-4 py-2 text-[18px] font-bold" @click="qty = Math.min(maxQty, qty + 1)">+</button>
                    </div>
                </div>
                <p x-show="variant && variant.stock < 1" x-cloak class="mt-1 text-[12px] font-semibold text-rose-600">Out of stock</p>

                <div class="mt-4 space-y-3">
                    <div>
                        <label for="lp-name" class="block text-[12px] font-bold uppercase tracking-wide text-slate-500">Your name</label>
                        <input id="lp-name" type="text" name="customer_name" value="{{ old('customer_name') }}" required maxlength="120" autocomplete="name" class="lp-accent-ring mt-1 w-full rounded-xl border-slate-200 px-3 py-3 text-[15px]">
                    </div>
                    <div>
                        <label for="lp-phone" class="block text-[12px] font-bold uppercase tracking-wide text-slate-500">Phone number</label>
                        <input id="lp-phone" type="tel" name="customer_phone" value="{{ old('customer_phone') }}" required maxlength="20" inputmode="tel" autocomplete="tel" placeholder="01XXXXXXXXX" class="lp-accent-ring mt-1 w-full rounded-xl border-slate-200 px-3 py-3 text-[15px]">
                    </div>
                    <div>
                        <label for="lp-address" class="block text-[12px] font-bold uppercase tracking-wide text-slate-500">Full delivery address</label>
                        <textarea id="lp-address" name="customer_address" rows="2" required maxlength="1000" autocomplete="street-address" placeholder="House, road, area, district" class="lp-accent-ring mt-1 w-full rounded-xl border-slate-200 px-3 py-3 text-[15px]">{{ old('customer_address') }}</textarea>
                    </div>
                    <div>
                        <span class="block text-[12px] font-bold uppercase tracking-wide text-slate-500">Delivery area</span>
                        <div class="mt-1.5 grid grid-cols-2 gap-2">
                            @foreach(['inside_dhaka' => 'Inside Dhaka', 'outside_dhaka' => 'Outside Dhaka'] as $zoneKey => $zoneLabel)
                                <label class="cursor-pointer rounded-xl border-2 p-3 text-center" :class="zone === '{{ $zoneKey }}' ? 'lp-accent-border bg-slate-50' : 'border-slate-200'">
                                    <input type="radio" name="delivery_zone" value="{{ $zoneKey }}" x-model="zone" class="sr-only">
                                    <span class="block text-[14px] font-bold text-slate-900">{{ $zoneLabel }}</span>
                                    <span class="block text-[12px] text-slate-500">{{ $currency }}{{ format_taka_number($deliveryConfig[$zoneKey] ?? 0) }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    @if(($deliveryConfig['cod_enabled'] ?? true) && ($deliveryConfig['confirmation_enabled'] ?? false) && ($deliveryConfig['confirmation_amount'] ?? 0) > 0)
                        <div>
                            <span class="block text-[12px] font-bold uppercase tracking-wide text-slate-500">Payment</span>
                            <div class="mt-1.5 space-y-2">
                                <label class="flex items-center gap-2 text-[14px]"><input type="radio" name="payment_method" value="cash_on_delivery" x-model="payment"> Cash on delivery</label>
                                <label class="flex items-center gap-2 text-[14px]"><input type="radio" name="payment_method" value="confirmation_charge" x-model="payment"> Pay {{ $currency }}{{ format_taka_number($deliveryConfig['confirmation_amount']) }} now to confirm, rest on delivery</label>
                            </div>
                        </div>
                    @else
                        <input type="hidden" name="payment_method" :value="payment">
                    @endif
                    <div>
                        <label for="lp-note" class="block text-[12px] font-bold uppercase tracking-wide text-slate-500">Note (optional)</label>
                        <input id="lp-note" type="text" name="note" value="{{ old('note') }}" maxlength="500" placeholder="Size, colour or delivery instructions" class="lp-accent-ring mt-1 w-full rounded-xl border-slate-200 px-3 py-3 text-[15px]">
                    </div>
                </div>

                <dl class="mt-5 space-y-1.5 rounded-2xl bg-slate-50 p-4 text-[14px]">
                    <div class="flex justify-between"><dt class="text-slate-500">Subtotal</dt><dd class="font-semibold" x-text="money(subtotal)"></dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Delivery</dt><dd class="font-semibold" x-text="deliveryFee === 0 ? 'Free' : money(deliveryFee)"></dd></div>
                    <div class="flex justify-between border-t border-slate-200 pt-2 text-[16px]"><dt class="font-bold text-slate-900">Total</dt><dd class="font-extrabold text-slate-900" x-text="money(subtotal + deliveryFee)"></dd></div>
                </dl>

                <button type="submit" :disabled="submitting || !variant || variant.stock < 1"
                        class="lp-accent-bg mt-4 w-full rounded-2xl px-5 py-4 text-[16px] font-extrabold text-white shadow-lg disabled:opacity-60">
                    <span x-show="!submitting">{{ $cta }} · <span x-text="money(subtotal + deliveryFee)"></span></span>
                    <span x-show="submitting" x-cloak>Placing your order…</span>
                </button>
                @if($deliveryConfig['cod_enabled'] ?? true)
                    <p class="mt-2 text-center text-[12px] text-slate-500">Pay cash when you receive the parcel.</p>
                @endif
            </form>
        @endif
    </section>

    {{-- FAQ --}}
    @if(! empty($page->faqs))
        <section class="mx-auto mt-10 max-w-xl px-4">
            <h2 class="text-center text-[22px] font-extrabold text-slate-900">Questions</h2>
            <div class="mt-4 space-y-2">
                @foreach($page->faqs as $i => $faq)
                    <div class="rounded-2xl bg-white shadow-sm" x-data="{ open: {{ $i === 0 ? 'true' : 'false' }} }">
                        <button type="button" @click="open = !open" class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left text-[14px] font-bold text-slate-900">
                            {{ $faq['q'] }}
                            <span class="text-[18px] text-slate-400" x-text="open ? '−' : '+'"></span>
                        </button>
                        <p x-show="open" x-cloak class="px-4 pb-4 text-[14px] leading-relaxed text-slate-600">{{ $faq['a'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <footer class="mx-auto mt-12 max-w-5xl px-4 py-8 text-center text-[12px] text-slate-400">
        © {{ now()->year }} {{ $storeName }} · <a href="{{ route('home') }}" class="underline">Visit our shop</a>
    </footer>

    @if(! $done && $offers->isNotEmpty())
        <div class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 p-3 backdrop-blur md:hidden">
            <a href="#order" class="lp-accent-bg block rounded-xl py-3.5 text-center text-[15px] font-extrabold text-white shadow-lg">{{ $cta }} · <span x-text="money(subtotal + deliveryFee)"></span></a>
        </div>
    @endif
</div>

<script>
    function landingPage() {
        const offers = @json($offers);
        const cfg = @json($deliveryConfig);
        const currency = @json($currency);
        return {
            offers,
            offerKey: @json($initialKey),
            variantId: null,
            qty: Math.max(1, Number(@json((int) old('qty', 1))) || 1),
            zone: @json(old('delivery_zone', 'inside_dhaka')),
            payment: @json(old('payment_method', ($deliveryConfig['cod_enabled'] ?? true) ? 'cash_on_delivery' : 'confirmation_charge')),
            submitting: false,
            expired: false,
            countdown: { d: 0, h: 0, m: 0, s: 0 },
            init() {
                const wanted = Number(@json($oldProduct));
                this.selectVariant(wanted);
                const ends = @json($page->countdownActive() ? $page->countdown_ends_at->toIso8601String() : null);
                if (ends) {
                    const tick = () => {
                        const left = Math.max(0, new Date(ends).getTime() - Date.now());
                        this.expired = left === 0;
                        this.countdown = { d: Math.floor(left / 864e5), h: Math.floor(left / 36e5) % 24, m: Math.floor(left / 6e4) % 60, s: Math.floor(left / 1e3) % 60 };
                    };
                    tick();
                    setInterval(tick, 1000);
                }
            },
            get current() { return this.offers.find((o) => o.key === this.offerKey) || null; },
            get variant() { return this.current ? this.current.variants.find((v) => v.id === this.variantId) || null : null; },
            get maxQty() { return Math.max(1, Math.min(20, this.variant ? this.variant.stock : 1)); },
            get subtotal() { return this.variant ? this.variant.price * this.qty : 0; },
            get deliveryFee() {
                if (cfg.free_enabled && this.subtotal >= Number(cfg.free_min_amount)) return 0;
                return Number(this.zone === 'outside_dhaka' ? cfg.outside_dhaka : cfg.inside_dhaka) || 0;
            },
            selectVariant(preferred) {
                const list = this.current ? this.current.variants : [];
                const match = list.find((v) => v.id === preferred) || list.find((v) => v.stock > 0) || list[0];
                this.variantId = match ? match.id : null;
                this.qty = Math.min(this.qty, this.maxQty);
            },
            pick(key, scroll = true) {
                this.offerKey = key;
                this.selectVariant(null);
                if (scroll) document.getElementById('order')?.scrollIntoView({ behavior: 'smooth' });
            },
            money(v) { return currency + Math.round(Number(v) || 0).toLocaleString(); },
        };
    }
</script>
</body>
</html>
