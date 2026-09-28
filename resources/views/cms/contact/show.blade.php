<x-cms-layout title="Contact message" subtitle="Inbound message from the public contact form." actionUrl="{{ route('cms.contact.index') }}" actionLabel="← Inbox" previewUrl="{{ route('website.contact') }}">
    <div class="max-w-3xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-bold text-slate-900">{{ $message->subject }}</h2>
                <p class="text-sm text-slate-500 mt-1">From {{ $message->name }} · <a href="mailto:{{ $message->email }}" class="text-indigo-600">{{ $message->email }}</a></p>
                <p class="text-xs text-slate-400 mt-1">{{ $message->created_at->format('M d, Y \a\t H:i') }}</p>
            </div>
            <span class="rounded-full px-2 py-1 text-xs font-bold {{ $message->is_read ? 'bg-slate-100 text-slate-500' : 'bg-emerald-50 text-emerald-700' }}">{{ $message->is_read ? 'Read' : 'New' }}</span>
        </div>
        @if($message->order_number)
            <p class="text-sm text-slate-600"><span class="font-semibold">Order #:</span> {{ $message->order_number }}</p>
        @endif
        <div class="rounded-xl bg-slate-50 border border-slate-100 p-4 text-sm text-slate-700 whitespace-pre-line leading-relaxed">{{ $message->message }}</div>
        <div class="flex flex-wrap gap-2 justify-end">
            <a href="mailto:{{ $message->email }}?subject=Re: {{ urlencode($message->subject) }}" class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-bold text-white">Reply by email</a>
            <form action="{{ route('cms.contact.messages.destroy', $message) }}" method="POST" data-confirm="Delete message?" data-confirm-title="Delete?" data-confirm-ok="Delete" data-confirm-tone="danger">@csrf @method('DELETE')
                <button class="rounded-xl border px-4 py-2 text-sm font-bold text-rose-600">Delete</button>
            </form>
        </div>
    </div>
</x-cms-layout>
