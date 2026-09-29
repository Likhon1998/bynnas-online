<x-app-layout>
    <div class="w-full min-w-0 pb-6 text-slate-700">
        <div class="mb-4 flex flex-wrap items-center gap-2">
            <div class="mr-auto">
                <h1 class="text-[17px] font-bold tracking-tight text-slate-900">Landing pages</h1>
                <p class="text-[12px] text-slate-500">Offer pages for ads and posts — visitors order in one step, no account needed.</p>
            </div>
            <a href="{{ route('landing-pages.create') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3.5 py-2 text-[12px] font-semibold text-white shadow-sm hover:bg-indigo-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                New landing page
            </a>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            @if($pages->isEmpty())
                <div class="px-6 py-14 text-center">
                    <p class="text-[14px] font-semibold text-slate-800">No landing pages yet</p>
                    <p class="mx-auto mt-1 max-w-sm text-[12px] text-slate-500">Build a page for one offer: hero, products, benefits, reviews, FAQ, countdown and an order form. Share its link in ads and posts.</p>
                    <a href="{{ route('landing-pages.create') }}" class="mt-4 inline-flex rounded-lg bg-indigo-600 px-3.5 py-2 text-[12px] font-semibold text-white hover:bg-indigo-700">Create your first page</a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[720px] text-[12px]">
                        <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wide text-slate-400">
                            <tr>
                                <th class="px-3 py-2 text-left">Page</th>
                                <th class="px-3 py-2 text-left">Campaign</th>
                                <th class="px-3 py-2 text-right">Views</th>
                                <th class="px-3 py-2 text-right">Orders</th>
                                <th class="px-3 py-2 text-right">Revenue</th>
                                <th class="px-3 py-2 text-right">Conv.</th>
                                <th class="px-3 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($pages as $page)
                                @php
                                    $orders = (int) ($orderStats[$page->id]->orders ?? 0);
                                    $revenue = (float) ($orderStats[$page->id]->revenue ?? 0);
                                @endphp
                                <tr class="hover:bg-slate-50/60">
                                    <td class="px-3 py-2.5">
                                        <a href="{{ route('landing-pages.edit', $page) }}" class="font-semibold text-slate-900 hover:text-indigo-600">{{ $page->title }}</a>
                                        <div class="mt-0.5 flex items-center gap-1.5 text-[11px] text-slate-500">
                                            <span class="rounded-full px-1.5 py-0.5 text-[10px] font-semibold ring-1 {{ $page->isPublished() ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-slate-100 text-slate-600 ring-slate-200' }}">{{ \App\Models\LandingPage::STATUSES[$page->status] ?? $page->status }}</span>
                                            <span class="font-mono">/campaign/{{ $page->slug }}</span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2.5">
                                        @if($page->campaign)
                                            <a href="{{ route('campaigns.show', $page->campaign) }}" class="text-slate-700 hover:underline">{{ $page->campaign->name }}</a>
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2.5 text-right tabular-nums">{{ number_format($page->views) }}</td>
                                    <td class="px-3 py-2.5 text-right font-semibold tabular-nums text-slate-900">{{ number_format($orders) }}</td>
                                    <td class="px-3 py-2.5 text-right tabular-nums">৳{{ format_taka_number($revenue) }}</td>
                                    <td class="px-3 py-2.5 text-right tabular-nums">{{ $page->views > 0 ? round($orders / $page->views * 100, 1).'%' : '—' }}</td>
                                    <td class="px-3 py-2.5 text-right whitespace-nowrap" x-data="{ copied: false }">
                                        <a href="{{ $page->publicUrl() }}" target="_blank" rel="noopener" class="rounded-md border border-slate-200 px-2 py-1 text-[11px] font-medium text-slate-700 hover:bg-slate-50">{{ $page->isPublished() ? 'Open' : 'Preview' }}</a>
                                        <button type="button" class="rounded-md border border-slate-200 px-2 py-1 text-[11px] font-medium text-slate-700 hover:bg-slate-50"
                                                @click="navigator.clipboard.writeText(@js($page->publicUrl())); copied = true; setTimeout(() => copied = false, 1500)">
                                            <span x-text="copied ? 'Copied' : 'Copy link'"></span>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 p-3">{{ $pages->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
