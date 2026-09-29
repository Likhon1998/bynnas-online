<x-app-layout>
    @php
        $statusCls = ['active' => 'bg-emerald-50 text-emerald-700 ring-emerald-200', 'paused' => 'bg-amber-50 text-amber-700 ring-amber-200', 'ended' => 'bg-slate-100 text-slate-600 ring-slate-200'];
        $sourceNames = \App\Models\Campaign::SOURCES + ['referral' => 'Referral', 'twitter' => 'X / Twitter', 'linkedin' => 'LinkedIn', 'pinterest' => 'Pinterest'];
    @endphp

    <div class="w-full min-w-0 pb-6 text-slate-700">
        <div class="mb-4 flex flex-wrap items-center gap-2">
            <div class="mr-auto">
                <h1 class="text-[17px] font-bold tracking-tight text-slate-900">Campaigns</h1>
                <p class="text-[12px] text-slate-500">Trackable links for social posts, ads and influencers — see which ones bring real orders.</p>
            </div>
            <a href="{{ route('campaigns.create') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3.5 py-2 text-[12px] font-semibold text-white shadow-sm hover:bg-indigo-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                New campaign
            </a>
        </div>

        <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach([
                ['Active campaigns', number_format($totals['active']), 'text-indigo-600'],
                ['Link clicks · 30 days', number_format($totals['clicks']), 'text-sky-600'],
                ['Campaign orders · 30 days', number_format($totals['orders']), 'text-emerald-600'],
                ['Campaign revenue · 30 days', '৳'.format_taka_number($totals['revenue']), 'text-violet-600'],
            ] as [$label, $value, $tone])
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">{{ $label }}</p>
                    <p class="mt-1 text-[22px] font-bold {{ $tone }}">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_320px]">
            <div class="min-w-0 rounded-2xl border border-slate-200 bg-white shadow-sm">
                <form method="GET" class="flex flex-wrap items-center gap-2 border-b border-slate-100 p-3">
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Search name or tag" class="min-w-[160px] flex-1 rounded-lg border border-slate-200 px-3 py-1.5 text-[12px]">
                    <select name="source" class="rounded-lg border border-slate-200 px-2 py-1.5 text-[12px]" onchange="this.form.submit()">
                        <option value="">All platforms</option>
                        @foreach(\App\Models\Campaign::SOURCES as $key => $label)
                            <option value="{{ $key }}" @selected(request('source') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="status" class="rounded-lg border border-slate-200 px-2 py-1.5 text-[12px]" onchange="this.form.submit()">
                        <option value="">Any status</option>
                        @foreach(\App\Models\Campaign::STATUSES as $key => $label)
                            <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>

                @if($campaigns->isEmpty())
                    <div class="px-6 py-14 text-center">
                        <p class="text-[14px] font-semibold text-slate-800">No campaigns yet</p>
                        <p class="mx-auto mt-1 max-w-sm text-[12px] text-slate-500">Create a campaign for each post, ad or influencer. Share its link and orders from that link are credited to it automatically.</p>
                        <a href="{{ route('campaigns.create') }}" class="mt-4 inline-flex rounded-lg bg-indigo-600 px-3.5 py-2 text-[12px] font-semibold text-white hover:bg-indigo-700">Create your first campaign</a>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[760px] text-[12px]">
                            <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wide text-slate-400">
                                <tr>
                                    <th class="px-3 py-2 text-left">Campaign</th>
                                    <th class="px-3 py-2 text-left">Platform</th>
                                    <th class="px-3 py-2 text-right">Clicks</th>
                                    <th class="px-3 py-2 text-right">Visitors</th>
                                    <th class="px-3 py-2 text-right">Orders</th>
                                    <th class="px-3 py-2 text-right">Revenue</th>
                                    <th class="px-3 py-2 text-right">Conv.</th>
                                    <th class="px-3 py-2"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($campaigns as $campaign)
                                    @php $s = $stats[$campaign->id] ?? ['clicks' => 0, 'visitors' => 0, 'orders' => 0, 'revenue' => 0, 'conversion' => null]; @endphp
                                    <tr class="hover:bg-slate-50/60">
                                        <td class="px-3 py-2.5">
                                            <a href="{{ route('campaigns.show', $campaign) }}" class="font-semibold text-slate-900 hover:text-indigo-600">{{ $campaign->name }}</a>
                                            <div class="mt-0.5 flex flex-wrap items-center gap-1.5 text-[11px] text-slate-500">
                                                <span class="rounded-full px-1.5 py-0.5 text-[10px] font-semibold ring-1 {{ $statusCls[$campaign->status] ?? $statusCls['ended'] }}">{{ \App\Models\Campaign::STATUSES[$campaign->status] ?? $campaign->status }}</span>
                                                <span class="font-mono">{{ $campaign->utm_campaign }}</span>
                                                <span>· {{ $campaign->landingLabel() }}</span>
                                            </div>
                                        </td>
                                        <td class="px-3 py-2.5">
                                            <div class="font-medium text-slate-800">{{ $campaign->sourceLabel() }}</div>
                                            <div class="text-[11px] text-slate-500">{{ $campaign->mediumLabel() }}</div>
                                        </td>
                                        <td class="px-3 py-2.5 text-right tabular-nums">{{ number_format($s['clicks']) }}</td>
                                        <td class="px-3 py-2.5 text-right tabular-nums">{{ number_format($s['visitors']) }}</td>
                                        <td class="px-3 py-2.5 text-right font-semibold tabular-nums text-slate-900">{{ number_format($s['orders']) }}</td>
                                        <td class="px-3 py-2.5 text-right tabular-nums">৳{{ format_taka_number($s['revenue']) }}</td>
                                        <td class="px-3 py-2.5 text-right tabular-nums">{{ $s['conversion'] !== null ? $s['conversion'].'%' : '—' }}</td>
                                        <td class="px-3 py-2.5 text-right" x-data="{ copied: false }">
                                            <button type="button" class="rounded-md border border-slate-200 px-2 py-1 text-[11px] font-medium text-slate-700 hover:bg-slate-50"
                                                    @click="navigator.clipboard.writeText(@js($campaign->trackingUrl())); copied = true; setTimeout(() => copied = false, 1500)">
                                                <span x-text="copied ? 'Copied' : 'Copy link'"></span>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="border-t border-slate-100 p-3">{{ $campaigns->links() }}</div>
                @endif
            </div>

            <aside class="h-fit rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="text-[13px] font-bold text-slate-900">Online orders by source</h2>
                <p class="text-[11px] text-slate-500">Last 30 days · cancelled, returned and refunded orders excluded.</p>
                @if($sources->isEmpty())
                    <p class="mt-4 rounded-lg bg-slate-50 px-3 py-4 text-center text-[12px] text-slate-500">No online orders in the last 30 days.</p>
                @else
                    @php $maxOrders = max(1, (int) $sources->max('orders')); @endphp
                    <ul class="mt-3 space-y-2.5">
                        @foreach($sources as $row)
                            <li>
                                <div class="flex items-center justify-between gap-2 text-[12px]">
                                    <span class="font-medium text-slate-800">
                                        {{ $row->source !== '' ? ($sourceNames[$row->source] ?? \Illuminate\Support\Str::headline($row->source)) : 'Direct / unknown' }}
                                        @if($row->medium !== '')
                                            <span class="text-[11px] font-normal text-slate-400">· {{ \App\Models\Campaign::MEDIUMS[$row->medium] ?? \Illuminate\Support\Str::headline($row->medium) }}</span>
                                        @endif
                                    </span>
                                    <span class="tabular-nums text-slate-600">{{ $row->orders }} · ৳{{ format_taka_number($row->revenue) }}</span>
                                </div>
                                <div class="mt-1 h-1.5 rounded-full bg-slate-100">
                                    <div class="h-1.5 rounded-full bg-indigo-500" style="width: {{ round($row->orders / $maxOrders * 100) }}%"></div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </aside>
        </div>
    </div>
</x-app-layout>
