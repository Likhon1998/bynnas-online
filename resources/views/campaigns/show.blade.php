<x-app-layout>
    @php
        $statusCls = ['active' => 'bg-emerald-50 text-emerald-700 ring-emerald-200', 'paused' => 'bg-amber-50 text-amber-700 ring-amber-200', 'ended' => 'bg-slate-100 text-slate-600 ring-slate-200'];
        $spend = $campaign->spend !== null ? (float) $campaign->spend : null;
        $costPerOrder = $spend !== null && $stats['orders'] > 0 ? $spend / $stats['orders'] : null;
        $roas = $spend !== null && $spend > 0 ? $stats['revenue'] / $spend : null;
        $maxDay = max(1, (int) $days->max('clicks'));
    @endphp

    <div class="w-full min-w-0 pb-6 text-slate-700">
        <div class="mb-4 flex flex-wrap items-start gap-2">
            <div class="mr-auto min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-[17px] font-bold tracking-tight text-slate-900">{{ $campaign->name }}</h1>
                    <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold ring-1 {{ $statusCls[$campaign->status] ?? $statusCls['ended'] }}">{{ \App\Models\Campaign::STATUSES[$campaign->status] ?? $campaign->status }}</span>
                    @if($campaign->status === 'active' && ! $campaign->isTracking())
                        <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700 ring-1 ring-amber-200">Outside date range — clicks not counted</span>
                    @endif
                </div>
                <p class="text-[12px] text-slate-500">{{ $campaign->sourceLabel() }} · {{ $campaign->mediumLabel() }} · <span class="font-mono">{{ $campaign->utm_campaign }}</span></p>
            </div>
            <a href="{{ route('campaigns.index') }}" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-[12px] font-medium text-slate-700 hover:bg-slate-50">All campaigns</a>
            <a href="{{ route('campaigns.edit', $campaign) }}" class="rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-[12px] font-medium text-slate-700 hover:bg-slate-50">Edit</a>
            <form method="POST" action="{{ route('campaigns.destroy', $campaign) }}" onsubmit="return confirm('Delete this campaign? Click history is removed; orders keep their source details.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded-lg border border-rose-200 bg-white px-3 py-1.5 text-[12px] font-medium text-rose-600 hover:bg-rose-50">Delete</button>
            </form>
        </div>

        {{-- Tracking link + builder --}}
        <section class="mb-4 rounded-2xl border border-indigo-100 bg-gradient-to-br from-indigo-50 to-white p-4 shadow-sm"
                 x-data="{ base: @js($campaign->trackingUrl()), placement: '', copied: '',
                           get link() { const p = this.placement.trim().toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, ''); return p ? this.base + '?c=' + p : this.base; },
                           copy(text, key) { navigator.clipboard.writeText(text); this.copied = key; setTimeout(() => this.copied = '', 1500); } }">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="text-[13px] font-bold text-slate-900">Tracking link</h2>
                    <p class="text-[11px] text-slate-500">Opens {{ $campaign->landingLabel() }}. Share it in your post, ad, bio or message.</p>
                </div>
            </div>
            <div class="mt-3 flex flex-col gap-2 sm:flex-row">
                <input type="text" readonly value="{{ $campaign->trackingUrl() }}" :value="link" class="min-w-0 flex-1 rounded-lg border border-slate-200 bg-white px-3 py-2 font-mono text-[12px] text-slate-800">
                <button type="button" @click="copy(link, 'link')" class="rounded-lg bg-indigo-600 px-4 py-2 text-[12px] font-semibold text-white hover:bg-indigo-700">
                    <span x-text="copied === 'link' ? 'Copied!' : 'Copy link'"></span>
                </button>
            </div>
            <div class="mt-2 flex flex-col gap-2 sm:flex-row sm:items-center">
                <label class="text-[11px] font-semibold text-slate-600">Label this placement (optional):</label>
                <input type="text" x-model="placement" maxlength="60" placeholder="e.g. reel-1, story, influencer-rima" class="min-w-0 flex-1 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-[12px]">
            </div>
            <p class="mt-1 text-[11px] text-slate-500">Different labels for each post let you compare them below. Link previews from Facebook, WhatsApp and bots are not counted as clicks.</p>
        </section>

        <div class="mb-4 grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
            @foreach([
                ['Link clicks', number_format($stats['clicks'])],
                ['Unique visitors', number_format($stats['visitors'])],
                ['Orders', number_format($stats['orders']).($stats['voided'] ? ' ('.$stats['voided'].' void)' : '')],
                ['Revenue', '৳'.format_taka_number($stats['revenue'])],
                ['Conversion', $stats['conversion'] !== null ? $stats['conversion'].'%' : '—'],
                ['Cost / order', $costPerOrder !== null ? '৳'.format_taka_number($costPerOrder) : '—'],
            ] as [$label, $value])
                <div class="rounded-2xl border border-slate-200 bg-white p-3.5 shadow-sm">
                    <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">{{ $label }}</p>
                    <p class="mt-1 text-[18px] font-bold text-slate-900">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_320px]">
            <div class="min-w-0 space-y-4">
                <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <h2 class="text-[13px] font-bold text-slate-900">Last 14 days</h2>
                    <div class="mt-4 flex h-36 items-end gap-1.5">
                        @foreach($days as $day)
                            <div class="flex h-full flex-1 flex-col items-center justify-end gap-1" title="{{ \Illuminate\Support\Carbon::parse($day['day'])->format('d M') }}: {{ $day['clicks'] }} clicks, {{ $day['orders'] }} orders">
                                @if($day['orders'] > 0)
                                    <span class="text-[9px] font-bold text-emerald-600">{{ $day['orders'] }}</span>
                                @endif
                                <div class="w-full rounded-t bg-indigo-400/80" style="height: {{ max(2, round($day['clicks'] / $maxDay * 100)) }}%"></div>
                                <span class="text-[9px] text-slate-400">{{ \Illuminate\Support\Carbon::parse($day['day'])->format('d') }}</span>
                            </div>
                        @endforeach
                    </div>
                    <p class="mt-2 text-[11px] text-slate-500">Bars = link clicks per day · green numbers = orders placed that day.</p>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <h2 class="border-b border-slate-100 px-4 py-3 text-[13px] font-bold text-slate-900">Orders from this campaign</h2>
                    @if($orders->isEmpty())
                        <p class="px-4 py-8 text-center text-[12px] text-slate-500">No orders yet. Orders placed after someone opens the tracking link appear here.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-[520px] text-[12px]">
                                <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wide text-slate-400">
                                    <tr><th class="px-4 py-2 text-left">Order</th><th class="px-4 py-2 text-left">Customer</th><th class="px-4 py-2 text-left">Placement</th><th class="px-4 py-2 text-left">Status</th><th class="px-4 py-2 text-right">Total</th></tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($orders as $order)
                                        <tr>
                                            <td class="px-4 py-2"><a href="{{ route('online-orders.show', $order) }}" class="font-semibold text-indigo-600 hover:underline">{{ $order->invoice_no }}</a><div class="text-[11px] text-slate-400">{{ $order->created_at?->format('d M, h:i A') }}</div></td>
                                            <td class="px-4 py-2">{{ $order->customer->name ?? 'Guest' }}</td>
                                            <td class="px-4 py-2 font-mono text-[11px]">{{ $order->utm_content ?: '—' }}</td>
                                            <td class="px-4 py-2">{{ \Illuminate\Support\Str::headline((string) $order->status) }}</td>
                                            <td class="px-4 py-2 text-right tabular-nums">৳{{ format_taka_number($order->total_amount) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>
            </div>

            <div class="space-y-4">
                <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <h2 class="text-[13px] font-bold text-slate-900">By placement</h2>
                    @if($placements->isEmpty())
                        <p class="mt-3 text-[12px] text-slate-500">No clicks yet.</p>
                    @else
                        <table class="mt-2 w-full text-[12px]">
                            <thead class="text-[10px] font-bold uppercase tracking-wide text-slate-400"><tr><th class="py-1 text-left">Label</th><th class="py-1 text-right">Clicks</th><th class="py-1 text-right">Orders</th></tr></thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($placements as $row)
                                    <tr><td class="py-1.5 font-mono text-[11px]">{{ $row['label'] }}</td><td class="py-1.5 text-right tabular-nums">{{ $row['clicks'] }}</td><td class="py-1.5 text-right tabular-nums">{{ $row['orders'] }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <h2 class="text-[13px] font-bold text-slate-900">Details</h2>
                    <dl class="mt-2 space-y-1.5 text-[12px]">
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Landing</dt><dd class="text-right font-medium text-slate-800">{{ $campaign->landingLabel() }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Dates</dt><dd class="text-right text-slate-800">{{ $campaign->starts_on?->format('d M Y') ?? 'Any' }} – {{ $campaign->ends_on?->format('d M Y') ?? 'Open' }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Ad spend</dt><dd class="text-right text-slate-800">{{ $spend !== null ? '৳'.format_taka_number($spend) : 'Not entered' }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Return on spend</dt><dd class="text-right text-slate-800">{{ $roas !== null ? number_format($roas, 2).'×' : '—' }}</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-slate-500">Created by</dt><dd class="text-right text-slate-800">{{ $campaign->creator->name ?? '—' }}</dd></div>
                    </dl>
                    @if($campaign->notes)
                        <p class="mt-3 whitespace-pre-line rounded-lg bg-slate-50 p-2.5 text-[12px] text-slate-600">{{ $campaign->notes }}</p>
                    @endif
                    <div class="mt-3 rounded-lg bg-slate-50 p-2.5 text-[11px] text-slate-500">
                        <p class="font-semibold text-slate-600">For Ads Manager URL parameters:</p>
                        <p class="mt-1 break-all font-mono">utm_source={{ $campaign->source }}&amp;utm_medium={{ $campaign->medium }}&amp;utm_campaign={{ $campaign->utm_campaign }}</p>
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
