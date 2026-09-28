@php
    $tables = [];
    $current = null;
    foreach ($rows as $row) {
        $isEmpty = empty(array_filter($row, fn ($v) => $v !== null && $v !== ''));
        if ($isEmpty) {
            if ($current) {
                $tables[] = $current;
                $current = null;
            }
            continue;
        }
        if ($current === null) {
            $current = ['headers' => $row, 'body' => []];
        } else {
            $current['body'][] = $row;
        }
    }
    if ($current) {
        $tables[] = $current;
    }
@endphp

<div class="report-preview-doc">
    <div class="mb-6 pb-5 border-b border-gray-100">
        <p class="text-[11px] font-bold uppercase tracking-wider text-indigo-600">{{ $shopName }}</p>
        <h3 class="text-2xl font-black text-gray-900 mt-1">{{ $title }}</h3>
        <div class="mt-2 flex flex-wrap gap-4 text-xs text-gray-500">
            <span>Period: <strong class="text-gray-800">{{ $start->format('d M Y') }}</strong> – <strong class="text-gray-800">{{ $end->format('d M Y') }}</strong></span>
            <span>Generated: <strong class="text-gray-800">{{ now()->format('d M Y, h:i A') }}</strong></span>
        </div>
    </div>

    @forelse($tables as $table)
        <div class="mb-6 overflow-x-auto">
            <table class="min-w-full text-sm border border-gray-100 rounded-xl overflow-hidden">
                <thead class="bg-slate-50">
                    <tr>
                        @foreach($table['headers'] as $header)
                            <th class="px-4 py-2.5 text-left text-[11px] font-bold uppercase tracking-wide text-gray-500">{{ $header }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    @forelse($table['body'] as $bodyRow)
                        <tr>
                            @foreach($bodyRow as $i => $cell)
                                <td class="px-4 py-2.5 {{ $i === count($bodyRow) - 1 ? 'text-right font-semibold text-gray-900' : 'text-gray-700' }}">{{ $cell }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($table['headers']) }}" class="px-4 py-8 text-center text-gray-400">No data for this section.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @empty
        <p class="py-12 text-center text-gray-400">No report data available for this period.</p>
    @endforelse

    <div class="pt-4 border-t border-dashed border-gray-200 flex flex-wrap justify-between gap-2 text-[11px] text-gray-400">
        <span>{{ $shopName }} · {{ $title }}</span>
        <span>Confidential — for internal use</span>
    </div>
</div>
