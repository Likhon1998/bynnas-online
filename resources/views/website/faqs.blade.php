@extends('website.layout')
@php
    $settings = $settings ?? (object) [];
    $heroTitle = data_get($settings, 'faq_hero_title') ?: 'Frequently Asked Questions';
    $heroSub = data_get($settings, 'faq_hero_subtitle')
        ?: 'Find quick answers to common questions about orders, shipping, returns, products and more.';
    $helpTitle = data_get($settings, 'faq_help_title') ?: 'Still Need Help?';
    $helpText = data_get($settings, 'faq_help_text') ?: "Can't find the answer you're looking for?";
    $helpButton = data_get($settings, 'faq_help_button') ?: 'Contact Support';
    $contactHref = route('website.contact');
    $faqCategories = $faqCategories ?? collect();
    $faqSearch = $faqSearch ?? null;
    $activeFaqCategory = $activeFaqCategory ?? null;
    $faqs = $faqs ?? collect();

    $iconPaths = [
        'cart' => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z',
        'truck' => 'M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10m10 0h6m-6 0h-4m10-4h2.586a1 1 0 00.707-.293l1.414-1.414A1 1 0 0021 9.586V8a1 1 0 00-1-1h-4',
        'refresh' => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
        'shield' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
        'lock' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z',
        'tag' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z',
        'help' => 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'headset' => 'M3 18v-6a9 9 0 0118 0v6M3 18a2 2 0 002 2h1a2 2 0 002-2v-3a2 2 0 00-2-2H5a2 2 0 00-2 2v3zm18 0a2 2 0 01-2 2h-1a2 2 0 01-2-2v-3a2 2 0 012-2h1a2 2 0 012 2v3z',
    ];
@endphp
@section('title', 'FAQ — '.($settings->store_name ?? config('app.name', 'Maks Gadget')))
@section('content')

{{-- Hero --}}
<section class="relative overflow-hidden border-b border-slate-100 bg-gradient-to-br from-slate-50 via-blue-50/40 to-white">
    <div class="max-w-7xl mx-auto px-4 py-10 sm:py-12">
        <nav class="text-xs text-slate-400 mb-4">
            <a href="{{ route('home') }}" class="hover:text-blue-600">Home</a>
            <span class="mx-1.5">›</span>
            <a href="{{ route('website.faqs') }}" class="hover:text-blue-600">Help Center</a>
            <span class="mx-1.5">›</span>
            <span class="text-slate-600">FAQ</span>
        </nav>
        <div class="grid lg:grid-cols-12 gap-8 items-center">
            <div class="lg:col-span-7">
                <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">{{ $heroTitle }}</h1>
                <p class="mt-3 max-w-xl text-sm sm:text-base text-slate-600 leading-relaxed">{{ $heroSub }}</p>
            </div>
            <div class="lg:col-span-5 hidden sm:flex justify-end">
                <div class="relative w-44 h-44">
                    <div class="absolute inset-4 rounded-[2rem] bg-blue-100/80 rotate-6"></div>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="w-28 h-28 rounded-[1.75rem] bg-blue-600 shadow-xl shadow-blue-600/25 flex items-center justify-center text-white">
                            <span class="text-5xl font-black leading-none">?</span>
                        </div>
                    </div>
                    <div class="absolute -top-1 right-6 w-10 h-8 rounded-2xl bg-white border border-slate-100 shadow-sm"></div>
                    <div class="absolute bottom-6 left-2 w-8 h-7 rounded-xl bg-white border border-slate-100 shadow-sm"></div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="max-w-7xl mx-auto px-4 py-10">
    <div class="grid lg:grid-cols-12 gap-8 items-start">
        {{-- Sidebar --}}
        <aside class="lg:col-span-4 xl:col-span-3 space-y-5">
            <div class="rounded-2xl border border-slate-100 bg-white shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100">
                    <h2 class="text-sm font-bold text-slate-900">FAQ Categories</h2>
                </div>
                <ul class="p-2">
                    <li>
                        <a href="{{ route('website.faqs', array_filter(['q' => $faqSearch])) }}"
                           class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition {{ !$activeFaqCategory ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:bg-slate-50' }}">
                            <span class="w-8 h-8 rounded-lg {{ !$activeFaqCategory ? 'bg-blue-100 text-blue-600' : 'bg-slate-100 text-slate-500' }} inline-flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconPaths['help'] }}"/></svg>
                            </span>
                            <span class="flex-1">All questions</span>
                            <span class="text-xs tabular-nums opacity-70">({{ $faqCategories->sum('faqs_count') }})</span>
                        </a>
                    </li>
                    @foreach($faqCategories as $cat)
                        @php $active = $activeFaqCategory === $cat->slug; $icon = $iconPaths[$cat->icon ?? 'help'] ?? $iconPaths['help']; @endphp
                        <li>
                            <a href="{{ route('website.faqs', array_filter(['category' => $cat->slug, 'q' => $faqSearch])) }}"
                               class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition {{ $active ? 'bg-blue-50 text-blue-700 font-semibold' : 'text-slate-600 hover:bg-slate-50' }}">
                                <span class="w-8 h-8 rounded-lg {{ $active ? 'bg-blue-100 text-blue-600' : 'bg-slate-100 text-slate-500' }} inline-flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"/></svg>
                                </span>
                                <span class="flex-1 truncate">{{ $cat->name }}</span>
                                <span class="text-xs tabular-nums opacity-70">({{ $cat->faqs_count }})</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="rounded-2xl border border-slate-100 bg-white shadow-sm p-5 text-center">
                <h3 class="text-base font-bold text-slate-900">{{ $helpTitle }}</h3>
                <p class="mt-1.5 text-sm text-slate-500">{{ $helpText }}</p>
                <a href="{{ $contactHref }}"
                   class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-xl border-2 border-blue-600 px-4 py-2.5 text-sm font-semibold text-blue-600 hover:bg-blue-50 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconPaths['headset'] }}"/></svg>
                    {{ $helpButton }}
                </a>
                <p class="mt-3 text-xs text-slate-400">We're here to help you 24/7.</p>
            </div>
        </aside>

        {{-- FAQ list --}}
        <div class="lg:col-span-8 xl:col-span-9 space-y-4">
            <form method="GET" action="{{ route('website.faqs') }}" class="relative">
                @if($activeFaqCategory)
                    <input type="hidden" name="category" value="{{ $activeFaqCategory }}">
                @endif
                <input type="search" name="q" value="{{ $faqSearch }}" placeholder="Search FAQ..."
                       class="w-full rounded-xl border border-slate-200 bg-white pl-4 pr-12 py-3 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-blue-600" aria-label="Search">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </button>
            </form>

            @if($faqSearch || $activeFaqCategory)
                <div class="flex items-center justify-between gap-3 text-sm">
                    <p class="text-slate-600">
                        @if($faqSearch) Results for “<strong>{{ $faqSearch }}</strong>” @endif
                        @if($activeFaqCategory)
                            @php $activeCat = $faqCategories->firstWhere('slug', $activeFaqCategory); @endphp
                            @if($faqSearch)<span class="mx-1">·</span>@endif
                            {{ $activeCat->name ?? $activeFaqCategory }}
                        @endif
                    </p>
                    <a href="{{ route('website.faqs') }}" class="text-xs font-semibold text-blue-600">Clear</a>
                </div>
            @endif

            <div class="rounded-2xl border border-slate-100 bg-white shadow-sm overflow-hidden divide-y divide-slate-100" x-data="{ open: 0 }">
                @forelse($faqs as $i => $faq)
                    <div>
                        <button type="button"
                                class="w-full flex items-center justify-between gap-4 px-5 py-4 text-left transition"
                                :class="open === {{ $i }} ? 'text-blue-600' : 'text-slate-800 hover:bg-slate-50/80'"
                                @click="open = open === {{ $i }} ? null : {{ $i }}">
                            <span class="text-sm sm:text-[15px] font-semibold leading-snug">{{ $faq->question }}</span>
                            <span class="shrink-0 w-7 h-7 rounded-full border border-slate-200 inline-flex items-center justify-center text-lg leading-none"
                                  :class="open === {{ $i }} ? 'border-blue-200 text-blue-600 bg-blue-50' : 'text-slate-400'">
                                <span x-text="open === {{ $i }} ? '−' : '+'"></span>
                            </span>
                        </button>
                        <div x-show="open === {{ $i }}" x-cloak class="px-5 pb-4">
                            <p class="text-sm text-slate-500 leading-relaxed whitespace-pre-line border-t border-slate-50 pt-3">{{ $faq->answer }}</p>
                        </div>
                    </div>
                @empty
                    <p class="px-5 py-16 text-center text-slate-400 text-sm">No FAQs found. Try another category or search.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- Value props --}}
<section class="border-t border-slate-100 bg-slate-50/80">
    <div class="max-w-7xl mx-auto px-4 py-10 grid sm:grid-cols-2 lg:grid-cols-4 gap-8">
        @foreach([
            ['title' => 'Fast Shipping', 'text' => 'Get your gadgets fast with our reliable shipping.', 'icon' => 'truck'],
            ['title' => 'Easy Returns', 'text' => '30-day easy returns on most products.', 'icon' => 'refresh'],
            ['title' => 'Secure Payments', 'text' => '100% secure payments with trusted methods.', 'icon' => 'shield'],
            ['title' => '24/7 Support', 'text' => 'Our support team is always here to help.', 'icon' => 'headset'],
        ] as $item)
            <div class="flex gap-3">
                <span class="w-11 h-11 rounded-full bg-blue-50 text-blue-600 inline-flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconPaths[$item['icon']] }}"/></svg>
                </span>
                <div>
                    <h3 class="text-sm font-bold text-slate-900">{{ $item['title'] }}</h3>
                    <p class="mt-0.5 text-xs text-slate-500 leading-relaxed">{{ $item['text'] }}</p>
                </div>
            </div>
        @endforeach
    </div>
</section>
@endsection
