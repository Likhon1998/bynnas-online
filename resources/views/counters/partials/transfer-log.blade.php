@php $transferLog = $transferLog ?? []; @endphp
@if(count($transferLog))
    <div class="{{ $class ?? 'mt-4' }} rounded-2xl border border-indigo-100 bg-indigo-50/40 overflow-hidden">
        <div class="px-4 py-3 border-b border-indigo-100 flex items-center justify-between gap-2">
            <div>
                <p class="text-sm font-bold text-slate-900">Counter cash transfers</p>
                <p class="text-[11px] text-slate-500">Justification for money moved between tills this session</p>
            </div>
            <span class="text-[10px] font-bold uppercase tracking-wide text-indigo-700 bg-white border border-indigo-100 rounded-full px-2.5 py-1">
                {{ count($transferLog) }} move{{ count($transferLog) === 1 ? '' : 's' }}
            </span>
        </div>
        <div class="divide-y divide-indigo-100/80">
            @foreach($transferLog as $row)
                <div class="px-4 py-3 text-sm flex flex-col sm:flex-row sm:items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="font-semibold text-slate-900">
                            @if($row['direction'] === 'in')
                                <span class="text-emerald-700">Received from</span> {{ $row['counterpart'] }}
                            @else
                                <span class="text-amber-700">Sent to</span> {{ $row['counterpart'] }}
                            @endif
                        </p>
                        <p class="text-xs text-slate-600 mt-0.5">
                            <span class="font-semibold text-slate-700">Reason:</span> {{ $row['reason'] }}
                        </p>
                        <p class="text-[11px] text-slate-400 mt-1">
                            {{ $row['at'] }}
                            @if($row['by']) · by {{ $row['by'] }} @endif
                            @if($row['txn_no']) · {{ $row['txn_no'] }} @endif
                        </p>
                    </div>
                    <p class="font-black whitespace-nowrap {{ $row['direction'] === 'in' ? 'text-emerald-700' : 'text-amber-700' }}">
                        {{ $row['direction'] === 'in' ? '+' : '−' }}{{ format_taka($row['amount']) }}
                    </p>
                </div>
            @endforeach
        </div>
    </div>
@endif
