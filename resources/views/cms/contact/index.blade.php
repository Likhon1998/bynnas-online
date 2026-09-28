<x-cms-layout title="Contact" subtitle="Public /contact page content, info cards, map, and inbound messages — all managed here." previewUrl="{{ route('website.contact') }}">

    @php
        $social = $settings->social_links ?? [];
    @endphp

    <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h3 class="text-sm font-bold text-slate-900">Contact page settings</h3>
        <p class="text-xs text-slate-500 mt-0.5">Everything on /contact — hero, cards, hours, form labels, map, newsletter & social.</p>
        <form method="POST" action="{{ route('cms.contact.settings') }}" class="mt-4 grid gap-4 md:grid-cols-2">
            @csrf

            <div class="md:col-span-2"><p class="text-xs font-bold uppercase tracking-wider text-indigo-600">Store contact info</p></div>
            <div>
                <label class="text-[11px] font-bold uppercase text-slate-500">Email</label>
                <input name="contact_email" type="email" value="{{ old('contact_email', $settings->contact_email) }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm" placeholder="support@gagetstore.com">
            </div>
            <div>
                <label class="text-[11px] font-bold uppercase text-slate-500">Phone</label>
                <input name="contact_phone" value="{{ old('contact_phone', $settings->contact_phone) }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm" placeholder="+1 (800) 123-4567">
            </div>
            <div class="md:col-span-2">
                <label class="text-[11px] font-bold uppercase text-slate-500">Office address</label>
                <textarea name="contact_address" rows="2" class="mt-1 w-full rounded-xl border-slate-200 text-sm">{{ old('contact_address', $settings->contact_address) }}</textarea>
            </div>
            <div>
                <label class="text-[11px] font-bold uppercase text-slate-500">Website URL</label>
                <input name="contact_website_url" value="{{ old('contact_website_url', $settings->contact_website_url ?? url('/')) }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm" placeholder="https://…">
            </div>
            <div class="md:col-span-2">
                <label class="text-[11px] font-bold uppercase text-slate-500">Map embed (Google Maps)</label>
                <textarea name="contact_map_embed" rows="3" class="mt-1 w-full rounded-xl border-slate-200 text-sm font-mono text-[12px]" placeholder="Paste Google Maps embed code OR the iframe src URL">{{ old('contact_map_embed', $settings->contact_map_embed) }}</textarea>
                <p class="mt-1 text-[11px] text-slate-400">
                    Google Maps → <strong>Share</strong> → <strong>Embed a map</strong> → Copy HTML (full iframe is OK), or paste only the <code>src</code> link.
                    Share links like <code>maps.app.goo.gl</code> / place URLs are also accepted.
                </p>
                @php $mapPreview = normalize_map_embed_url(old('contact_map_embed', $settings->contact_map_embed)); @endphp
                @if($mapPreview)
                    <div class="mt-3 overflow-hidden rounded-xl border border-slate-200">
                        <iframe src="{{ $mapPreview }}" class="w-full h-40 border-0" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
                    </div>
                @endif
            </div>

            <div class="md:col-span-2 pt-2"><p class="text-xs font-bold uppercase tracking-wider text-indigo-600">Hero</p></div>
            <div>
                <label class="text-[11px] font-bold uppercase text-slate-500">Kicker</label>
                <input name="contact_hero_kicker" value="{{ old('contact_hero_kicker', $settings->contact_hero_kicker ?? 'CONTACT US') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
            </div>
            <div>
                <label class="text-[11px] font-bold uppercase text-slate-500">Title</label>
                <input name="contact_hero_title" value="{{ old('contact_hero_title', $settings->contact_hero_title ?? "We're Here to Help!") }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
            </div>
            <div class="md:col-span-2">
                <label class="text-[11px] font-bold uppercase text-slate-500">Subtitle</label>
                <textarea name="contact_hero_subtitle" rows="2" class="mt-1 w-full rounded-xl border-slate-200 text-sm">{{ old('contact_hero_subtitle', $settings->contact_hero_subtitle) }}</textarea>
            </div>

            <div class="md:col-span-2 pt-2"><p class="text-xs font-bold uppercase tracking-wider text-indigo-600">Info cards</p></div>
            <div>
                <label class="text-[11px] font-bold uppercase text-slate-500">Live chat title</label>
                <input name="contact_chat_title" value="{{ old('contact_chat_title', $settings->contact_chat_title ?? 'Live Chat') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
            </div>
            <div>
                <label class="text-[11px] font-bold uppercase text-slate-500">Live chat status</label>
                <input name="contact_chat_status" value="{{ old('contact_chat_status', $settings->contact_chat_status ?? 'Available 24/7') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
            </div>
            <div class="md:col-span-2">
                <label class="text-[11px] font-bold uppercase text-slate-500">Live chat text</label>
                <input name="contact_chat_text" value="{{ old('contact_chat_text', $settings->contact_chat_text ?? 'Chat with our support team in real-time.') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
            </div>
            <div>
                <label class="text-[11px] font-bold uppercase text-slate-500">Email card title</label>
                <input name="contact_email_card_title" value="{{ old('contact_email_card_title', $settings->contact_email_card_title ?? 'Email Support') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
            </div>
            <div>
                <label class="text-[11px] font-bold uppercase text-slate-500">Email card text</label>
                <input name="contact_email_card_text" value="{{ old('contact_email_card_text', $settings->contact_email_card_text ?? "Send us an email anytime. We'll get back to you.") }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
            </div>
            <div>
                <label class="text-[11px] font-bold uppercase text-slate-500">Phone card title</label>
                <input name="contact_phone_card_title" value="{{ old('contact_phone_card_title', $settings->contact_phone_card_title ?? 'Call Us') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
            </div>
            <div>
                <label class="text-[11px] font-bold uppercase text-slate-500">Phone card text</label>
                <input name="contact_phone_card_text" value="{{ old('contact_phone_card_text', $settings->contact_phone_card_text ?? 'Speak with our experts directly.') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
            </div>
            <div>
                <label class="text-[11px] font-bold uppercase text-slate-500">Hours title</label>
                <input name="contact_hours_title" value="{{ old('contact_hours_title', $settings->contact_hours_title ?? 'Working Hours') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
            </div>
            <div>
                <label class="text-[11px] font-bold uppercase text-slate-500">Weekday hours</label>
                <input name="contact_hours_weekday" value="{{ old('contact_hours_weekday', $settings->contact_hours_weekday ?? 'Sat - Thu: 10:00 AM - 8:00 PM (BDT)') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
            </div>
            <div class="md:col-span-2">
                <label class="text-[11px] font-bold uppercase text-slate-500">Weekend hours</label>
                <input name="contact_hours_weekend" value="{{ old('contact_hours_weekend', $settings->contact_hours_weekend ?? 'Fri: 3:00 PM - 8:00 PM (BDT)') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
            </div>

            <div class="md:col-span-2 pt-2"><p class="text-xs font-bold uppercase tracking-wider text-indigo-600">Form & newsletter</p></div>
            <div>
                <label class="text-[11px] font-bold uppercase text-slate-500">Form title</label>
                <input name="contact_form_title" value="{{ old('contact_form_title', $settings->contact_form_title ?? 'Send Us a Message') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
            </div>
            <div>
                <label class="text-[11px] font-bold uppercase text-slate-500">Form subtitle</label>
                <input name="contact_form_subtitle" value="{{ old('contact_form_subtitle', $settings->contact_form_subtitle ?? 'Fill out the form and our team will respond shortly.') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
            </div>
            <div>
                <label class="text-[11px] font-bold uppercase text-slate-500">Newsletter title</label>
                <input name="contact_newsletter_title" value="{{ old('contact_newsletter_title', $settings->contact_newsletter_title ?? 'Stay in the loop') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
            </div>
            <div>
                <label class="text-[11px] font-bold uppercase text-slate-500">Newsletter text</label>
                <input name="contact_newsletter_text" value="{{ old('contact_newsletter_text', $settings->contact_newsletter_text ?? 'Subscribe for deals and product updates.') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
            </div>

            <div class="md:col-span-2 pt-2"><p class="text-xs font-bold uppercase tracking-wider text-indigo-600">Social links</p></div>
            <div>
                <label class="text-[11px] font-bold uppercase text-slate-500">Facebook</label>
                <input name="social_facebook" value="{{ old('social_facebook', $social['facebook'] ?? '') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm" placeholder="https://facebook.com/…">
            </div>
            <div>
                <label class="text-[11px] font-bold uppercase text-slate-500">X / Twitter</label>
                <input name="social_twitter" value="{{ old('social_twitter', $social['twitter'] ?? '') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm" placeholder="https://x.com/…">
            </div>
            <div>
                <label class="text-[11px] font-bold uppercase text-slate-500">Instagram</label>
                <input name="social_instagram" value="{{ old('social_instagram', $social['instagram'] ?? '') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
            </div>
            <div>
                <label class="text-[11px] font-bold uppercase text-slate-500">YouTube</label>
                <input name="social_youtube" value="{{ old('social_youtube', $social['youtube'] ?? '') }}" class="mt-1 w-full rounded-xl border-slate-200 text-sm">
            </div>

            <div class="md:col-span-2 flex justify-end">
                <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-xs font-bold text-white hover:bg-slate-800">Save contact settings</button>
            </div>
        </form>
    </div>

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-slate-200">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
            <h3 class="text-sm font-bold text-slate-900">Inbox @if($unread)<span class="ml-2 rounded-full bg-rose-100 text-rose-700 px-2 py-0.5 text-[10px] font-bold">{{ $unread }} new</span>@endif</h3>
            <a href="{{ route('website.contact') }}" target="_blank" class="text-xs font-semibold text-indigo-600">View public form</a>
        </div>
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-slate-900 text-white text-[11px] uppercase tracking-wider">
                <tr>
                    <th class="px-5 py-3 text-left">From</th>
                    <th class="px-5 py-3">Subject</th>
                    <th class="px-5 py-3">Received</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($messages as $msg)
                    <tr class="{{ $msg->is_read ? '' : 'bg-blue-50/40' }}">
                        <td class="px-5 py-4">
                            <div class="font-semibold text-slate-900">{{ $msg->name }}</div>
                            <div class="text-xs text-slate-500">{{ $msg->email }}</div>
                        </td>
                        <td class="px-5 py-4 text-center text-slate-700">{{ $msg->subject }}</td>
                        <td class="px-5 py-4 text-center text-slate-500">{{ $msg->created_at->format('M d, Y H:i') }}</td>
                        <td class="px-5 py-4 text-center">
                            <span class="rounded-full px-2 py-1 text-xs font-bold {{ $msg->is_read ? 'bg-slate-100 text-slate-500' : 'bg-emerald-50 text-emerald-700' }}">{{ $msg->is_read ? 'Read' : 'New' }}</span>
                        </td>
                        <td class="px-5 py-4 text-right space-x-3">
                            <a href="{{ route('cms.contact.messages.show', $msg) }}" class="font-semibold text-indigo-600">View</a>
                            <form action="{{ route('cms.contact.messages.destroy', $msg) }}" method="POST" class="inline" data-confirm="Delete message?" data-confirm-title="Delete?" data-confirm-ok="Delete" data-confirm-tone="danger">@csrf @method('DELETE')<button class="font-semibold text-rose-600">Delete</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-12 text-center text-slate-400">No messages yet. They appear when visitors submit the contact form.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $messages->links() }}</div>
    </div>

    <div class="mt-6 bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-slate-200">
        <div class="border-b border-slate-100 px-5 py-4">
            <h3 class="text-sm font-bold text-slate-900">Newsletter subscribers</h3>
            <p class="text-xs text-slate-500 mt-0.5">Emails captured from blog / contact newsletter forms.</p>
        </div>
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-slate-50 text-[11px] uppercase tracking-wider text-slate-500">
                <tr>
                    <th class="px-5 py-3 text-left">Email</th>
                    <th class="px-5 py-3 text-right">Subscribed</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($subscribers as $sub)
                    <tr>
                        <td class="px-5 py-3 font-medium text-slate-800">{{ $sub->email }}</td>
                        <td class="px-5 py-3 text-right text-slate-500">{{ $sub->created_at?->format('M d, Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="px-5 py-10 text-center text-slate-400">No subscribers yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $subscribers->links() }}</div>
    </div>
</x-cms-layout>
