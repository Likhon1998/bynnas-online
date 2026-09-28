@php
    $activeTab = $activeTab ?? 'sales';

    $tabs = [
        'sales' => ['route' => 'analytics.overview', 'label' => 'Sales Report'],
        'orders' => ['route' => 'analytics.orders', 'label' => 'Orders Report'],
        'products' => ['route' => 'analytics.products', 'label' => 'Products Report'],
        'customers' => ['route' => 'analytics.customers', 'label' => 'Customers Report'],
        'stock' => ['route' => 'analytics.stock', 'label' => 'Stock Report'],
        'tax' => ['route' => 'analytics.tax', 'label' => 'Tax Report'],
        'discount' => ['route' => 'analytics.discount', 'label' => 'Discount Report'],
    ];

    $dateParams = request()->only(['start_date', 'end_date', 'all_time']);
    $previewBase = route('analytics.preview', $dateParams);
    $exportBase = route('analytics.export', $dateParams);
@endphp

<x-app-layout>
    <div
        class="max-w-[1400px] mx-auto pt-0 pb-12 px-4 sm:px-6 lg:px-8 space-y-6"
        x-data="{
            tab: @js($activeTab),
            previewOpen: false,
            previewLoading: false,
            previewHtml: '',
            previewTitle: 'Report Preview',
            csvUrl: '',
            previewBase: @js($previewBase),
            exportBase: @js($exportBase),
            setTab(name, url) {
                this.tab = name;
                if (url && window.history.replaceState) {
                    window.history.replaceState({}, '', url);
                }
            },
            previewUrl(name) {
                const sep = this.previewBase.includes('?') ? '&' : '?';
                return this.previewBase + sep + 'tab=' + encodeURIComponent(name) + '&modal=1';
            },
            buildCsvUrl(name) {
                const sep = this.exportBase.includes('?') ? '&' : '?';
                return this.exportBase + sep + 'tab=' + encodeURIComponent(name);
            },
            async openPreview(name = null) {
                const active = name || this.tab;
                this.previewOpen = true;
                this.previewLoading = true;
                this.previewHtml = '';
                this.csvUrl = this.buildCsvUrl(active);
                this.previewTitle = {
                    sales: 'Sales Report',
                    orders: 'Orders Report',
                    products: 'Products Report',
                    customers: 'Customers Report',
                    stock: 'Stock Report',
                    tax: 'Tax Report',
                    discount: 'Discount Report',
                }[active] || 'Report Preview';

                try {
                    const res = await fetch(this.previewUrl(active), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html',
                        },
                    });
                    if (!res.ok) throw new Error('Failed to load preview');
                    this.previewHtml = await res.text();
                } catch (e) {
                    this.previewHtml = '<p class=\'py-10 text-center text-rose-600 font-medium\'>Could not load report preview. Please try again.</p>';
                } finally {
                    this.previewLoading = false;
                }
            },
            printPreview() {
                const content = document.getElementById('report-preview-content');
                if (!content) return;

                const frame = document.createElement('iframe');
                frame.style.position = 'fixed';
                frame.style.right = '0';
                frame.style.bottom = '0';
                frame.style.width = '0';
                frame.style.height = '0';
                frame.style.border = '0';
                document.body.appendChild(frame);

                const doc = frame.contentWindow.document;
                doc.open();
                doc.write(`<!DOCTYPE html><html><head><title>${this.previewTitle}</title>
                    <style>
                        body { font-family: Segoe UI, system-ui, sans-serif; color: #0f172a; font-size: 12px; margin: 18px; }
                        table { width: 100%; border-collapse: collapse; margin: 14px 0; }
                        th, td { border-bottom: 1px solid #e2e8f0; padding: 8px 10px; text-align: left; }
                        th { font-size: 10px; text-transform: uppercase; color: #64748b; background: #f8fafc; }
                        h3 { margin: 4px 0 8px; font-size: 22px; }
                        .text-indigo-600 { color: #4f46e5; }
                        .text-gray-500 { color: #64748b; }
                        .text-gray-800, .text-gray-900 { color: #0f172a; }
                        .font-black { font-weight: 800; }
                        .font-bold, .font-semibold { font-weight: 700; }
                        .text-right { text-align: right; }
                        .border-b { border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 12px; }
                        .uppercase { text-transform: uppercase; letter-spacing: .04em; }
                        .text-\\[11px\\] { font-size: 11px; }
                        .text-xs { font-size: 12px; }
                        .text-2xl { font-size: 22px; }
                    </style>
                </head><body>${content.innerHTML}</body></html>`);
                doc.close();

                setTimeout(() => {
                    frame.contentWindow.focus();
                    frame.contentWindow.print();
                    setTimeout(() => document.body.removeChild(frame), 800);
                }, 250);
            }
        }"
        @keydown.escape.window="previewOpen = false"
        @open-report-preview.window="openPreview($event.detail?.tab || tab)"
    >
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl font-black text-gray-900 tracking-tight">Reports</h2>
                <p class="text-sm text-gray-500 mt-1">Analyze your store performance and generate insightful reports.</p>
            </div>
            <button
                type="button"
                @click="openPreview()"
                class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold px-4 py-2.5 rounded-xl shadow-sm transition-all"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Export Report
            </button>
        </div>

        <div class="flex flex-wrap gap-2">
            @foreach($tabs as $key => $tab)
                <button
                    type="button"
                    @click="setTab(@js($key), @js(route($tab['route'], $dateParams)))"
                    :class="tab === @js($key)
                        ? 'bg-indigo-600 text-white shadow-md border-indigo-600'
                        : 'bg-white border border-gray-200 text-gray-600 hover:border-indigo-300 hover:text-indigo-600'"
                    class="px-4 py-2 rounded-xl text-xs font-bold transition-all border"
                >
                    {{ $tab['label'] }}
                </button>
            @endforeach
        </div>

        <form action="{{ url()->current() }}" method="GET" class="flex flex-wrap items-end gap-3 bg-white border border-gray-100 rounded-2xl p-4 shadow-sm">
            <div>
                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">From</label>
                <input type="date" name="start_date" value="{{ request('start_date', $start->format('Y-m-d')) }}" class="border-gray-200 rounded-lg text-sm px-3 py-2">
            </div>
            <div>
                <label class="block text-[10px] font-bold text-gray-400 uppercase mb-1">To</label>
                <input type="date" name="end_date" value="{{ request('end_date', $end->format('Y-m-d')) }}" class="border-gray-200 rounded-lg text-sm px-3 py-2">
            </div>
            <button type="submit" class="bg-slate-900 text-white text-xs font-bold px-4 py-2.5 rounded-lg hover:bg-indigo-600">Apply</button>
            <a href="{{ route(request()->route()->getName(), ['all_time' => 1]) }}" class="bg-gray-100 text-gray-700 text-xs font-bold px-4 py-2.5 rounded-lg hover:bg-gray-200">All Time</a>
            <div class="ml-auto text-xs text-gray-500 self-center">
                Comparing to {{ $prevStart->format('d M Y') }} – {{ $prevEnd->format('d M Y') }}
            </div>
        </form>

        <div x-show="tab === 'sales'" x-cloak x-effect="if (tab === 'sales') $nextTick(() => window.renderSalesCharts && window.renderSalesCharts())">@include('analytics.partials.tabs.sales')</div>
        <div x-show="tab === 'orders'" x-cloak>@include('analytics.partials.tabs.orders')</div>
        <div x-show="tab === 'products'" x-cloak>@include('analytics.partials.tabs.products')</div>
        <div x-show="tab === 'customers'" x-cloak>@include('analytics.partials.tabs.customers')</div>
        <div x-show="tab === 'stock'" x-cloak>@include('analytics.partials.tabs.stock')</div>
        <div x-show="tab === 'tax'" x-cloak>@include('analytics.partials.tabs.tax')</div>
        <div x-show="tab === 'discount'" x-cloak>@include('analytics.partials.tabs.discount')</div>

        {{-- Report Preview Modal --}}
        <div
            x-show="previewOpen"
            x-cloak
            class="fixed inset-0 z-[80] flex items-center justify-center p-4"
            @keydown.escape.window="previewOpen = false"
        >
            <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="previewOpen = false"></div>

            <div
                x-show="previewOpen"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-3 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                class="relative w-full max-w-4xl max-h-[90vh] bg-white rounded-2xl shadow-2xl border border-gray-100 flex flex-col overflow-hidden"
                @click.stop
            >
                <div class="px-5 py-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3 shrink-0">
                    <div>
                        <h3 class="text-base font-bold text-gray-900" x-text="previewTitle"></h3>
                        <p class="text-xs text-gray-500">Preview the report, then print or download.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <a
                            :href="csvUrl"
                            class="inline-flex items-center gap-1.5 border border-gray-200 text-gray-700 text-xs font-bold px-3 py-2 rounded-lg hover:bg-gray-50"
                        >
                            Download CSV
                        </a>
                        <button
                            type="button"
                            @click="printPreview()"
                            :disabled="previewLoading || !previewHtml"
                            class="inline-flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white text-xs font-bold px-3 py-2 rounded-lg"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            Print / Save PDF
                        </button>
                        <button type="button" @click="previewOpen = false" class="text-gray-400 hover:text-gray-600 text-xl leading-none px-1">&times;</button>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto p-5 sm:p-6 bg-slate-50/40">
                    <div class="bg-white border border-gray-100 rounded-xl shadow-sm p-5 sm:p-6">
                        <template x-if="previewLoading">
                            <div class="py-16 text-center">
                                <div class="inline-block h-8 w-8 rounded-full border-2 border-indigo-600 border-t-transparent animate-spin"></div>
                                <p class="mt-3 text-sm text-gray-500 font-medium">Preparing report preview…</p>
                            </div>
                        </template>
                        <div id="report-preview-content" x-show="!previewLoading" x-html="previewHtml"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
