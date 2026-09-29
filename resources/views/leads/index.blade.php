<x-app-layout>
@php
    $statusFilter = $filters['status'] ?? null;
    $tabs = ['' => 'All', 'open' => 'Open', 'due' => 'Follow-up due'] + \App\Models\Lead::STATUSES;
@endphp

<div class="space-y-4 text-[12px] text-slate-700">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-xl font-extrabold tracking-tight text-slate-900">Leads &amp; inquiries</h1>
            <p class="mt-0.5 text-[12px] text-slate-500">Social and website inquiries. Conversations stay on the platform; log what happened here.</p>
        </div>
        <a href="{{ route('leads.create') }}" class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-3.5 py-2 text-[12px] font-bold text-white hover:bg-indigo-700">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 4v16m8-8H4"/></svg>
            Add lead
        </a>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-[13px] font-medium text-emerald-700">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-[13px] font-medium text-rose-700">{{ session('error') }}</div>
    @endif

    <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-3.5 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Open leads</p>
            <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ $stats['open'] }}</p>
            <p class="text-[11px] text-slate-500">{{ $stats['new'] }} not contacted yet</p>
        </div>
        <a href="{{ route('leads.index', ['status' => 'due']) }}" class="rounded-2xl border border-violet-200 bg-violet-50 p-3.5 shadow-sm hover:border-violet-300">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-violet-700">Follow-ups due</p>
            <p class="mt-1 text-2xl font-extrabold text-violet-800">{{ $stats['due'] }}</p>
            <p class="text-[11px] text-violet-700">Scheduled time has passed</p>
        </a>
        <div class="rounded-2xl border border-slate-200 bg-white p-3.5 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Converted this month</p>
            <p class="mt-1 text-2xl font-extrabold text-emerald-700">{{ $stats['converted_month'] }}</p>
            <p class="text-[11px] text-slate-500">Leads that placed an order</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-3.5 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Conversion (month)</p>
            <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ $stats['conversion_rate'] !== null ? $stats['conversion_rate'].'%' : '—' }}</p>
            <p class="text-[11px] text-slate-500">Converted ÷ new leads this month</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center gap-1.5 border-b border-slate-100 px-3 py-2">
            @foreach($tabs as $key => $label)
                @php
                    $active = (string) $statusFilter === (string) $key;
                    $count = match ($key) {
                        '' => null, 'open' => $stats['open'], 'due' => $stats['due'],
                        default => (int) ($counts[$key] ?? 0),
                    };
                @endphp
                <a href="{{ route('leads.index', array_filter(array_merge($filters, ['status' => $key ?: null]))) }}"
                   class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-[11px] font-semibold transition {{ $active ? 'bg-indigo-600 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
                    {{ $label }}
                    @if($count !== null)<span class="tabular-nums {{ $active ? 'text-indigo-100' : 'text-slate-400' }}">{{ $count }}</span>@endif
                </a>
            @endforeach
        </div>

        <form method="GET" class="flex flex-wrap items-center gap-2 border-b border-slate-100 bg-slate-50/60 px-3 py-2">
            @if($statusFilter)<input type="hidden" name="status" value="{{ $statusFilter }}">@endif
            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Name, phone or conversation ref…"
                   class="w-full rounded-lg border-slate-200 py-1.5 text-[12px] sm:w-64">
            <select name="source" class="rounded-lg border-slate-200 py-1.5 text-[12px]">
                <option value="">All sources</option>
                @foreach(\App\Models\Lead::SOURCES as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['source'] ?? '') === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="assigned" class="rounded-lg border-slate-200 py-1.5 text-[12px]">
                <option value="">Anyone</option>
                <option value="me" @selected(($filters['assigned'] ?? '') === 'me')>Assigned to me</option>
                <option value="none" @selected(($filters['assigned'] ?? '') === 'none')>Unassigned</option>
                @foreach($staff as $member)
                    <option value="{{ $member->id }}" @selected(($filters['assigned'] ?? '') === (string) $member->id)>{{ $member->name }}</option>
                @endforeach
            </select>
            <button class="rounded-lg bg-slate-800 px-3 py-1.5 text-[12px] font-semibold text-white hover:bg-slate-900">Filter</button>
            @if(array_filter($filters))
                <a href="{{ route('leads.index') }}" class="text-[12px] font-semibold text-slate-500 hover:text-slate-800">Clear</a>
            @endif
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/80 text-left text-[10px] font-semibold uppercase tracking-wider text-slate-500">
                        <th class="px-3 py-2">Lead</th>
                        <th class="px-3 py-2">Source</th>
                        <th class="px-3 py-2 hidden md:table-cell">Interest</th>
                        <th class="px-3 py-2">Status</th>
                        <th class="px-3 py-2">Follow-up</th>
                        <th class="px-3 py-2 hidden md:table-cell">Assigned</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($leads as $lead)
                        <tr class="hover:bg-slate-50/80">
                            <td class="px-3 py-2">
                                <a href="{{ route('leads.show', $lead) }}" class="text-[13px] font-semibold text-slate-900 hover:text-indigo-700">{{ $lead->name }}</a>
                                <p class="text-[11px] text-slate-500">{{ $lead->phone ?: 'No phone' }} · {{ $lead->created_at->diffForHumans() }}</p>
                            </td>
                            <td class="px-3 py-2">
                                <span class="font-semibold text-slate-700">{{ $lead->sourceLabel() }}</span>
                                @if($lead->conversation_ref)
                                    <p class="max-w-[160px] truncate text-[10px] text-slate-400" title="{{ $lead->conversation_ref }}">{{ $lead->conversation_ref }}</p>
                                @endif
                            </td>
                            <td class="px-3 py-2 hidden md:table-cell">
                                <p class="max-w-[200px] truncate">{{ $lead->product?->name ?? '—' }}</p>
                                @if($lead->campaign)<p class="text-[10px] text-slate-400">{{ $lead->campaign->name }}</p>@endif
                            </td>
                            <td class="px-3 py-2">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold {{ \App\Models\Lead::statusBadge($lead->status) }}">{{ $lead->statusLabel() }}</span>
                                @if($lead->order)
                                    <p class="mt-0.5 text-[10px] text-emerald-700">{{ $lead->order->invoice_no }}</p>
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                @if($lead->follow_up_at && ! $lead->isClosed())
                                    <span class="{{ $lead->isFollowUpDue() ? 'font-bold text-rose-600' : 'text-slate-600' }}">{{ $lead->follow_up_at->format('d M, h:i A') }}</span>
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 hidden md:table-cell">{{ $lead->assignee?->name ?? 'Unassigned' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center">
                            <p class="text-[13px] font-semibold text-slate-700">No leads found</p>
                            <p class="mt-0.5 text-[11px] text-slate-400">Add inquiries from Facebook, Instagram, TikTok, WhatsApp or Messenger as they come in.</p>
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($leads->hasPages())
            <div class="border-t border-slate-100 px-3 py-2">{{ $leads->links() }}</div>
        @endif
    </div>
</div>
</x-app-layout>
