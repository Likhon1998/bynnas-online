<x-app-layout>
<div class="mx-auto max-w-3xl space-y-4 text-[12px] text-slate-700">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-extrabold tracking-tight text-slate-900">Notifications</h1>
            <p class="mt-0.5 text-[12px] text-slate-500">{{ $unread }} unread. New online orders also appear in the order bell.</p>
        </div>
        @if($unread > 0)
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-[12px] font-bold text-slate-700 hover:bg-slate-50">Mark all read</button>
            </form>
        @endif
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-[13px] font-medium text-emerald-700">{{ session('success') }}</div>
    @endif

    <div class="flex flex-wrap gap-1.5">
        <a href="{{ route('notifications.index') }}" class="rounded-md px-2 py-1 text-[11px] font-semibold {{ ! $kind ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-100' }}">All</a>
        @foreach(\App\Notifications\StaffAlert::KINDS as $key => $label)
            <a href="{{ route('notifications.index', ['kind' => $key]) }}" class="rounded-md px-2 py-1 text-[11px] font-semibold {{ $kind === $key ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-100' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        @forelse($notifications as $notification)
            @php $data = $notification->data; @endphp
            <form method="POST" action="{{ route('notifications.read', $notification->id) }}"
                  class="border-b border-slate-100 last:border-0 {{ $notification->read_at ? 'bg-white' : 'bg-indigo-50/60' }}">
                @csrf
                <button class="flex w-full items-start justify-between gap-3 px-4 py-3 text-left hover:bg-slate-50">
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400">{{ \App\Notifications\StaffAlert::KINDS[$data['kind'] ?? ''] ?? 'Alert' }}</p>
                        <p class="text-[13px] font-semibold {{ $notification->read_at ? 'text-slate-700' : 'text-slate-900' }}">{{ $data['title'] ?? 'Notification' }}</p>
                        @if(!empty($data['body']))<p class="text-[12px] text-slate-500">{{ $data['body'] }}</p>@endif
                    </div>
                    <span class="shrink-0 text-[11px] text-slate-400">{{ $notification->created_at->diffForHumans() }}</span>
                </button>
            </form>
        @empty
            <p class="px-4 py-10 text-center text-[12px] text-slate-400">No notifications yet.</p>
        @endforelse
    </div>

    @if($notifications->hasPages())
        <div>{{ $notifications->links() }}</div>
    @endif
</div>
</x-app-layout>
