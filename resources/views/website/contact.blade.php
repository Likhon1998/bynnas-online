@extends('website.layout')
@php
    $settings = $settings ?? (object) [];
    $kicker = data_get($settings, 'contact_hero_kicker') ?: 'CONTACT US';
    $heroTitle = data_get($settings, 'contact_hero_title') ?: "We're Here to Help!";
    $heroSub = data_get($settings, 'contact_hero_subtitle')
        ?: 'Have a question about an order, product, or return? Our support team is ready to assist you.';
    $email = trim((string) data_get($settings, 'contact_email'));
    $phone = trim((string) data_get($settings, 'contact_phone'));
    $address = trim((string) data_get($settings, 'contact_address'));
    $website = data_get($settings, 'contact_website_url') ?: url('/');
    $websiteLabel = preg_replace('#^https?://#', '', rtrim($website, '/'));
    $mapEmbed = normalize_map_embed_url(data_get($settings, 'contact_map_embed'));
    $social = data_get($settings, 'social_links') ?: [];
    $newsletterTitle = data_get($settings, 'contact_newsletter_title') ?: 'Stay in the loop';
    $newsletterText = data_get($settings, 'contact_newsletter_text') ?: 'Subscribe for deals and product updates.';
@endphp
@section('title', 'Contact — '.($settings->store_name ?? config('app.name', 'Bynnas Social')))
@section('content')

<section class="relative overflow-hidden border-b border-slate-100 bg-gradient-to-br from-slate-50 via-blue-50/50 to-white">
    <div class="max-w-7xl mx-auto px-4 py-12 sm:py-14">
        <div class="grid lg:grid-cols-12 gap-8 items-center">
            <div class="lg:col-span-7">
                <p class="text-xs font-bold tracking-[0.2em] uppercase text-blue-600">{{ $kicker }}</p>
                <h1 class="mt-2 text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">{{ $heroTitle }}</h1>
                <p class="mt-3 max-w-xl text-sm sm:text-base text-slate-600 leading-relaxed">{{ $heroSub }}</p>
            </div>
            <div class="lg:col-span-5 hidden sm:flex justify-end">
                <div class="relative w-48 h-40">
                    <div class="absolute right-8 top-2 w-28 h-28 rounded-full bg-blue-600 shadow-xl shadow-blue-600/30 flex items-center justify-center">
                        <svg class="w-14 h-14 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M3 18v-6a9 9 0 0118 0v6M3 18a2 2 0 002 2h1a2 2 0 002-2v-3a2 2 0 00-2-2H5a2 2 0 00-2 2v3zm18 0a2 2 0 01-2 2h-1a2 2 0 01-2-2v-3a2 2 0 012-2h1a2 2 0 012 2v3z"/></svg>
                    </div>
                    <div class="absolute left-4 bottom-4 w-16 h-14 rounded-2xl bg-white border border-slate-100 shadow-md flex items-center justify-center text-blue-600 text-2xl font-black">…</div>
                    <div class="absolute right-0 bottom-8 w-12 h-10 rounded-xl bg-blue-100 border border-blue-50"></div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="max-w-7xl mx-auto px-4 -mt-2 relative z-10">
    <div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <a href="#contact-form" class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm hover:border-blue-200 transition block">
            <div class="w-11 h-11 rounded-xl bg-blue-50 text-blue-600 inline-flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
            </div>
            <h3 class="mt-3 text-sm font-bold text-slate-900">{{ data_get($settings, 'contact_chat_title') ?: 'Message Us' }}</h3>
            <p class="mt-1 text-xs text-slate-500 leading-relaxed">{{ data_get($settings, 'contact_chat_text') ?: 'Send a message through the contact form below.' }}</p>
            <p class="mt-2 text-xs font-semibold text-blue-600">{{ data_get($settings, 'contact_chat_status') ?: 'Use the form below' }}</p>
        </a>
        @if($email !== '')
        <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
            <div class="w-11 h-11 rounded-xl bg-violet-50 text-violet-600 inline-flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </div>
            <h3 class="mt-3 text-sm font-bold text-slate-900">{{ data_get($settings, 'contact_email_card_title') ?: 'Email Us' }}</h3>
            @if(filled(data_get($settings, 'contact_email_card_text')))
                <p class="mt-1 text-xs text-slate-500 leading-relaxed">{{ data_get($settings, 'contact_email_card_text') }}</p>
            @endif
            <a href="mailto:{{ $email }}" class="mt-2 block text-xs font-semibold text-blue-600 break-all">{{ $email }}</a>
        </div>
        @endif
        @if($phone !== '')
        <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
            <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 inline-flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
            </div>
            <h3 class="mt-3 text-sm font-bold text-slate-900">{{ data_get($settings, 'contact_phone_card_title') ?: 'Call Us' }}</h3>
            @if(filled(data_get($settings, 'contact_phone_card_text')))
                <p class="mt-1 text-xs text-slate-500 leading-relaxed">{{ data_get($settings, 'contact_phone_card_text') }}</p>
            @endif
            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="mt-2 block text-xs font-semibold text-blue-600">{{ $phone }}</a>
        </div>
        @endif
        @if(filled(data_get($settings, 'contact_hours_weekday')) || filled(data_get($settings, 'contact_hours_weekend')))
        <div class="rounded-2xl border border-slate-100 bg-white p-5 shadow-sm">
            <div class="w-11 h-11 rounded-xl bg-amber-50 text-amber-600 inline-flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h3 class="mt-3 text-sm font-bold text-slate-900">{{ data_get($settings, 'contact_hours_title') ?: 'Working Hours' }}</h3>
            <p class="mt-1 text-xs text-slate-600">{{ data_get($settings, 'contact_hours_weekday') }}</p>
            <p class="mt-0.5 text-xs text-slate-500">{{ data_get($settings, 'contact_hours_weekend') }}</p>
        </div>
        @endif
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 py-12">
    <div class="grid lg:grid-cols-12 gap-8 items-start">
        <div class="lg:col-span-7 rounded-2xl border border-slate-100 bg-white shadow-sm p-6 sm:p-8">
            <h2 class="text-xl font-bold text-slate-900">{{ data_get($settings, 'contact_form_title') ?: 'Send Us a Message' }}</h2>
            <p class="mt-1 text-sm text-slate-500">{{ data_get($settings, 'contact_form_subtitle') ?: 'Fill out the form and our team will respond shortly.' }}</p>

            @if(session('contact_success'))
                <div class="mt-4 rounded-xl bg-emerald-50 border border-emerald-100 px-4 py-3 text-sm text-emerald-700">{{ session('contact_success') }}</div>
            @endif
            @if($errors->any())
                <div class="mt-4 rounded-xl bg-rose-50 border border-rose-100 px-4 py-3 text-sm text-rose-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <form id="contact-form" method="POST" action="{{ route('website.contact.submit') }}" class="mt-6 space-y-4">
                @csrf
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Your Name</label>
                        <input name="name" value="{{ old('name') }}" required class="mt-1 w-full rounded-xl border-slate-200 text-sm" placeholder="John Doe">
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-600">Your Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" required class="mt-1 w-full rounded-xl border-slate-200 text-sm" placeholder="john@example.com">
                    </div>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">Subject</label>
                    <input name="subject" value="{{ old('subject') }}" required class="mt-1 w-full rounded-xl border-slate-200 text-sm" placeholder="How can we help you?">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">Order Number <span class="font-normal text-slate-400">(Optional)</span></label>
                    <input name="order_number" value="{{ old('order_number') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm" placeholder="#ORD-12345">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">Message</label>
                    <textarea name="message" rows="5" required class="mt-1 w-full rounded-xl border-slate-200 text-sm" placeholder="Tell us more about your inquiry…">{{ old('message') }}</textarea>
                </div>
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    Send Message
                </button>
            </form>
        </div>

        <div class="lg:col-span-5 space-y-4">
            <div class="rounded-2xl border border-slate-100 bg-white shadow-sm overflow-hidden">
                @if($mapEmbed)
                    <iframe src="{{ $mapEmbed }}" class="w-full h-56 border-0" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                @else
                    <div class="h-56 bg-gradient-to-br from-slate-100 to-blue-50 flex items-center justify-center text-slate-400 text-sm">
                        <div class="text-center px-4">
                            <svg class="w-10 h-10 mx-auto text-blue-500 mb-2" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 110-5 2.5 2.5 0 010 5z"/></svg>
                            <p class="font-semibold text-slate-600">{{ $settings->store_name ?? config('app.name', 'Bynnas Social') }}</p>
                            @if(filled($settings->contact_address ?? null))
                                <p class="text-xs mt-1">{{ $settings->contact_address }}</p>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
            <div class="rounded-2xl border border-slate-100 bg-white shadow-sm p-5 space-y-4">
                @if($address !== '')
                <div class="flex gap-3">
                    <span class="w-9 h-9 rounded-lg bg-blue-50 text-blue-600 inline-flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </span>
                    <div>
                        <p class="text-xs font-bold text-slate-900">Our Office</p>
                        <p class="text-xs text-slate-500 mt-0.5 leading-relaxed">{{ $address }}</p>
                    </div>
                </div>
                @endif
                @if($phone !== '')
                <div class="flex gap-3">
                    <span class="w-9 h-9 rounded-lg bg-blue-50 text-blue-600 inline-flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    </span>
                    <div>
                        <p class="text-xs font-bold text-slate-900">Phone</p>
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" class="text-xs text-slate-500 hover:text-blue-600">{{ $phone }}</a>
                    </div>
                </div>
                @endif
                @if($email !== '')
                <div class="flex gap-3">
                    <span class="w-9 h-9 rounded-lg bg-blue-50 text-blue-600 inline-flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </span>
                    <div>
                        <p class="text-xs font-bold text-slate-900">Email</p>
                        <a href="mailto:{{ $email }}" class="text-xs text-slate-500 hover:text-blue-600 break-all">{{ $email }}</a>
                    </div>
                </div>
                @endif
                <div class="flex gap-3">
                    <span class="w-9 h-9 rounded-lg bg-blue-50 text-blue-600 inline-flex items-center justify-center shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9"/></svg>
                    </span>
                    <div>
                        <p class="text-xs font-bold text-slate-900">Website</p>
                        <a href="{{ $website }}" class="text-xs text-slate-500 hover:text-blue-600" target="_blank" rel="noopener">{{ $websiteLabel }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<section class="border-y border-slate-100 bg-slate-50/80">
    <div class="max-w-7xl mx-auto px-4 py-8 flex flex-col lg:flex-row lg:items-center gap-6">
        <div class="lg:w-64 shrink-0">
            <h3 class="text-base font-bold text-slate-900">{{ $newsletterTitle }}</h3>
            <p class="text-xs text-slate-500 mt-0.5">{{ $newsletterText }}</p>
        </div>
        <form method="POST" action="{{ route('website.newsletter') }}" class="flex-1 flex flex-col sm:flex-row gap-2">
            @csrf
            <input type="email" name="email" required placeholder="Enter your email" class="flex-1 rounded-xl border-slate-200 text-sm bg-white">
            <button class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Subscribe</button>
        </form>
        @if(session('newsletter_success'))
            <p class="text-xs text-emerald-600">{{ session('newsletter_success') }}</p>
        @endif
        <div class="flex items-center gap-2 lg:ml-auto">
            @foreach(\App\Models\SiteSetting::socialProfiles($social ?? []) as $profile)
                <a href="{{ $profile['url'] }}" target="_blank" rel="noopener" class="w-9 h-9 rounded-full border border-slate-200 bg-white text-slate-500 hover:text-blue-600 hover:border-blue-200 inline-flex items-center justify-center" aria-label="{{ $profile['label'] }}">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="{{ $profile['path'] }}"/></svg>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endsection
