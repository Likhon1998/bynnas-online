@php
    $changeBadge = function (float $change) {
        $up = $change >= 0;
        $cls = $up ? 'text-emerald-600 bg-emerald-50' : 'text-rose-600 bg-rose-50';
        $arrow = $up ? '↑' : '↓';

        return '<span class="inline-flex items-center gap-0.5 text-[11px] font-bold px-2 py-0.5 rounded-full '.$cls.'">'.$arrow.' '.abs($change).'%</span>';
    };

    $chartLabels = $chart->pluck('day')->map(fn ($d) => \Carbon\Carbon::parse($d)->format('d M'))->values();
    $chartRevenue = $chart->pluck('revenue')->map(fn ($v) => round((float) $v, 2))->values();
    $prevByDay = $prevChart->keyBy(fn ($r) => \Carbon\Carbon::parse($r->day)->format('m-d'));
    $prevAligned = $chart->map(function ($row) use ($prevByDay) {
        $key = \Carbon\Carbon::parse($row->day)->format('m-d');

        return round((float) ($prevByDay->get($key)?->revenue ?? 0), 2);
    })->values();

    $catLabels = $categorySales->pluck('category')->values();
    $catRevenue = $categorySales->pluck('revenue')->map(fn ($v) => round((float) $v, 2))->values();
    $catTotal = max(1, (float) $categorySales->sum('revenue'));

    $brandLabels = ($brandSales ?? collect())->pluck('brand')->values();
    $brandRevenue = ($brandSales ?? collect())->pluck('revenue')->map(fn ($v) => round((float) $v, 2))->values();
    $brandTotal = max(1, (float) ($brandSales ?? collect())->sum('revenue'));
@endphp

{{-- KPI cards --}}
<div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
    <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-sm">
        <div class="flex items-start justify-between gap-2">
            <div class="h-9 w-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            {!! $changeBadge($kpis['change']['revenue']) !!}
        </div>
        <p class="text-[10px] font-bold text-gray-400 uppercase mt-3">Total Sales</p>
        <p class="text-xl font-black text-gray-900 mt-1">{{ format_taka($kpis['revenue']) }}</p>
    </div>
    <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-sm">
        <div class="flex items-start justify-between gap-2">
            <div class="h-9 w-9 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </div>
            {!! $changeBadge($kpis['change']['orders']) !!}
        </div>
        <p class="text-[10px] font-bold text-gray-400 uppercase mt-3">Total Orders</p>
        <p class="text-xl font-black text-gray-900 mt-1">{{ number_format($kpis['orders']) }}</p>
    </div>
    <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-sm">
        <div class="flex items-start justify-between gap-2">
            <div class="h-9 w-9 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            </div>
            {!! $changeBadge($kpis['change']['aov']) !!}
        </div>
        <p class="text-[10px] font-bold text-gray-400 uppercase mt-3">Avg Order Value</p>
        <p class="text-xl font-black text-gray-900 mt-1">{{ format_taka($kpis['aov']) }}</p>
    </div>
    <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-sm">
        <div class="flex items-start justify-between gap-2">
            <div class="h-9 w-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            {!! $changeBadge($kpis['change']['profit']) !!}
        </div>
        <p class="text-[10px] font-bold text-gray-400 uppercase mt-3">Total Profit</p>
        <p class="text-xl font-black text-gray-900 mt-1">{{ format_taka($kpis['profit']) }}</p>
    </div>
    <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-sm">
        <div class="flex items-start justify-between gap-2">
            <div class="h-9 w-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
            </div>
            {!! $changeBadge($kpis['change']['discounts']) !!}
        </div>
        <p class="text-[10px] font-bold text-gray-400 uppercase mt-3">Total Discounts</p>
        <p class="text-xl font-black text-gray-900 mt-1">{{ format_taka($kpis['discounts']) }}</p>
    </div>
</div>

{{-- Charts --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <div class="lg:col-span-2 bg-white border border-gray-100 rounded-2xl p-5 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="font-bold text-gray-900">Sales Overview</h3>
                <p class="text-xs text-gray-500">This period vs previous period</p>
            </div>
            <div class="flex items-center gap-3 text-[11px] font-bold text-gray-500">
                <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span> This Period</span>
                <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-indigo-200"></span> Last Period</span>
            </div>
        </div>
        <div class="h-64">
            <canvas id="salesOverviewChart"></canvas>
        </div>
    </div>

    <div class="bg-white border border-gray-100 rounded-2xl p-5 shadow-sm">
        <h3 class="font-bold text-gray-900 mb-1">Sales by Category</h3>
        <p class="text-xs text-gray-500 mb-4">Share of revenue</p>
        <div class="h-44 relative">
            <canvas id="salesCategoryChart"></canvas>
            <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                <p class="text-[10px] font-bold text-gray-400 uppercase">Total Sales</p>
                <p class="text-sm font-black text-gray-900">{{ format_taka($kpis['revenue']) }}</p>
            </div>
        </div>
        <div class="mt-4 space-y-2 max-h-40 overflow-y-auto">
            @forelse($categorySales as $cat)
                <div class="flex items-center justify-between text-xs">
                    <span class="font-medium text-gray-700 truncate pr-2">{{ $cat->category }}</span>
                    <span class="font-bold text-gray-900 whitespace-nowrap">{{ format_taka($cat->revenue) }} · {{ $catTotal > 0 ? number_format(($cat->revenue / $catTotal) * 100, 1) : '0' }}%</span>
                </div>
            @empty
                <p class="text-xs text-gray-400 text-center py-4">No category sales in this period.</p>
            @endforelse
        </div>
    </div>
</div>

{{-- Brand breakdown (POS + website) --}}
<div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-2">
        <div>
            <h3 class="font-bold text-gray-900">Sales by Brand</h3>
            <p class="text-xs text-gray-500">Units &amp; revenue — POS and website completed orders</p>
        </div>
        <a href="{{ route('reports.daily_by_brand', request()->only(['start_date', 'end_date', 'all_time', 'today'])) }}"
           class="text-xs font-bold text-cyan-700 hover:text-cyan-600">Full daily report →</a>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-[11px] uppercase font-bold text-gray-500">
                <tr>
                    <th class="px-5 py-3 text-left">Brand</th>
                    <th class="px-5 py-3 text-right">Units</th>
                    <th class="px-5 py-3 text-right">Purchase Cost</th>
                    <th class="px-5 py-3 text-right">Selling Amount</th>
                    <th class="px-5 py-3 text-right">Profit</th>
                    <th class="px-5 py-3 text-right">POS</th>
                    <th class="px-5 py-3 text-right">Website</th>
                    <th class="px-5 py-3 text-right">Share</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse(($brandSales ?? collect()) as $row)
                    <tr class="hover:bg-gray-50/80">
                        <td class="px-5 py-3 font-semibold text-gray-900">{{ $row->brand }}</td>
                        <td class="px-5 py-3 text-right font-medium">{{ number_format($row->sold) }}</td>
                        <td class="px-5 py-3 text-right font-semibold text-amber-700">৳{{ number_format($row->cost) }}</td>
                        <td class="px-5 py-3 text-right font-bold text-indigo-600">{{ format_taka($row->revenue) }}</td>
                        <td class="px-5 py-3 text-right font-bold {{ ($row->profit ?? 0) >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ format_taka($row->profit ?? 0) }}</td>
                        <td class="px-5 py-3 text-right text-gray-600">{{ format_taka($row->pos_revenue) }}</td>
                        <td class="px-5 py-3 text-right text-gray-600">{{ format_taka($row->web_revenue) }}</td>
                        <td class="px-5 py-3 text-right text-gray-500">{{ number_format(($row->revenue / $brandTotal) * 100, 0) }}%</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-5 py-8 text-center text-gray-400">No brand sales in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Tables --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
    <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="font-bold text-gray-900">Top Selling Products</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-[11px] uppercase font-bold text-gray-500">
                    <tr>
                        <th class="px-5 py-3 text-left">Product</th>
                        <th class="px-5 py-3 text-left">Category</th>
                        <th class="px-5 py-3 text-right">Sold</th>
                        <th class="px-5 py-3 text-right">Revenue</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($topProducts as $row)
                        <tr class="hover:bg-gray-50/80">
                            <td class="px-5 py-3 font-semibold text-gray-900">{{ $row->product?->name ?? 'Unknown' }}</td>
                            <td class="px-5 py-3 text-gray-500">{{ $row->product?->category?->name ?? '—' }}</td>
                            <td class="px-5 py-3 text-right font-medium">{{ $row->sold }}</td>
                            <td class="px-5 py-3 text-right font-bold text-indigo-600">{{ format_taka($row->revenue ?? 0) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-8 text-center text-gray-400">No product sales in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 class="font-bold text-gray-900">Sales Summary</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-[11px] uppercase font-bold text-gray-500">
                    <tr>
                        <th class="px-5 py-3 text-left">Metric</th>
                        <th class="px-5 py-3 text-right">This Period</th>
                        <th class="px-5 py-3 text-right">Last Period</th>
                        <th class="px-5 py-3 text-right">Change</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach([
                        ['Total Sales', $kpis['revenue'], $kpis['prev']['revenue'], $kpis['change']['revenue'], true],
                        ['Total Orders', $kpis['orders'], $kpis['prev']['orders'], $kpis['change']['orders'], false],
                        ['Avg Order Value', $kpis['aov'], $kpis['prev']['aov'], $kpis['change']['aov'], true],
                        ['Total Profit', $kpis['profit'], $kpis['prev']['profit'], $kpis['change']['profit'], true],
                        ['Total Discounts', $kpis['discounts'], $kpis['prev']['discounts'], $kpis['change']['discounts'], true],
                    ] as [$label, $cur, $prev, $chg, $money])
                        <tr class="hover:bg-gray-50/80">
                            <td class="px-5 py-3 font-medium text-gray-800">{{ $label }}</td>
                            <td class="px-5 py-3 text-right font-bold">{{ $money ? format_taka($cur) : number_format($cur) }}</td>
                            <td class="px-5 py-3 text-right text-gray-500">{{ $money ? format_taka($prev) : number_format($prev) }}</td>
                            <td class="px-5 py-3 text-right">{!! $changeBadge($chg) !!}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    window.renderSalesCharts = function () {
        if (!window.Chart) return;

        const labels = @js($chartLabels);
        const current = @js($chartRevenue);
        const previous = @js($prevAligned);
        const catLabels = @js($catLabels);
        const catData = @js($catRevenue);

        const lineEl = document.getElementById('salesOverviewChart');
        if (lineEl) {
            if (lineEl._chart) lineEl._chart.destroy();
            lineEl._chart = new Chart(lineEl, {
                type: 'line',
                data: {
                    labels,
                    datasets: [
                        {
                            label: 'This Period',
                            data: current,
                            borderColor: '#4F46E5',
                            backgroundColor: 'rgba(79,70,229,0.08)',
                            fill: true,
                            tension: 0.35,
                            borderWidth: 2.5,
                            pointRadius: 0,
                        },
                        {
                            label: 'Last Period',
                            data: previous,
                            borderColor: '#C7D2FE',
                            borderDash: [6, 4],
                            tension: 0.35,
                            borderWidth: 2,
                            pointRadius: 0,
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false }, ticks: { maxTicksLimit: 8, font: { size: 10 } } },
                        y: { grid: { color: '#F1F5F9' }, ticks: { font: { size: 10 }, callback: v => '৳' + v } },
                    },
                },
            });
        }

        const donutEl = document.getElementById('salesCategoryChart');
        if (donutEl) {
            if (donutEl._chart) donutEl._chart.destroy();
            donutEl._chart = new Chart(donutEl, {
                type: 'doughnut',
                data: {
                    labels: catLabels,
                    datasets: [{
                        data: catData.length ? catData : [1],
                        backgroundColor: ['#4F46E5', '#818CF8', '#A5B4FC', '#C7D2FE', '#E0E7FF', '#EEF2FF'],
                        borderWidth: 0,
                        cutout: '72%',
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                },
            });
        }
    };

    document.addEventListener('DOMContentLoaded', function () {
        if (@js($activeTab === 'sales')) {
            setTimeout(() => window.renderSalesCharts && window.renderSalesCharts(), 50);
        }
    });
</script>
