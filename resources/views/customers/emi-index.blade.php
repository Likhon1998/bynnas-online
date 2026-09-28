<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Customer EMI</h2>
                <p class="mt-0.5 text-sm text-slate-500">Active installment plans — collect any amount toward remaining EMI.</p>
            </div>
            <a href="{{ route('customers.index') }}"
               class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold uppercase tracking-wide text-slate-700 shadow-sm transition hover:bg-slate-50">
                All customers
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('success'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800">{{ session('error') }}</div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="rounded-2xl border border-indigo-200 bg-indigo-50 px-5 py-4 shadow-sm">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-indigo-700">Total EMI outstanding</p>
                    <p class="mt-1 text-3xl font-black text-indigo-950">{{ format_taka($totalOutstanding) }}</p>
                    <p class="mt-1 text-xs text-indigo-700/80">{{ $plans->count() }} active plan(s)</p>
                </div>
                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 shadow-sm">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-amber-700">Overdue installments</p>
                    <p class="mt-1 text-3xl font-black text-amber-900">{{ (int) $overdueCount }}</p>
                    <p class="mt-1 text-xs text-amber-700/80">Past due date and still unpaid</p>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Customer</th>
                            <th class="px-4 py-3">Invoice</th>
                            <th class="px-4 py-3">Products on EMI</th>
                            <th class="px-4 py-3">Tenure</th>
                            <th class="px-4 py-3 text-right">Monthly</th>
                            <th class="px-4 py-3 text-right">Remaining</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($plans as $plan)
                            @php
                                $emiItems = $plan->order?->items ?? collect();
                            @endphp
                            <tr class="hover:bg-slate-50/80">
                                <td class="px-4 py-3 align-top">
                                    <div class="font-semibold text-slate-900">{{ $plan->customer->name ?? '—' }}</div>
                                    <div class="text-xs text-slate-500">{{ $plan->customer->phone ?? '' }}</div>
                                </td>
                                <td class="px-4 py-3 font-semibold text-indigo-600 align-top">
                                    {{ $plan->order->invoice_no ?? '—' }}
                                </td>
                                <td class="px-4 py-3 align-top max-w-sm">
                                    @if($emiItems->isNotEmpty())
                                        <ul class="space-y-2">
                                            @foreach($emiItems->take(3) as $item)
                                                @php
                                                    $product = $item->product;
                                                    $imgPath = $product ? ($product->image ?: ($product->imagePaths()[0] ?? null)) : null;
                                                    $imgUrl = public_storage_url($imgPath);
                                                    $name = $product->name ?? 'Product';
                                                    $qty = (int) $item->quantity;
                                                @endphp
                                                <li class="flex items-center gap-2.5 min-w-0">
                                                    <div class="h-9 w-9 shrink-0 overflow-hidden rounded-lg border border-slate-200 bg-slate-100">
                                                        @if($imgUrl)
                                                            <img src="{{ $imgUrl }}" alt="{{ $name }}" class="h-full w-full object-cover" loading="lazy">
                                                        @else
                                                            <div class="flex h-full w-full items-center justify-center text-slate-300">
                                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <span class="text-sm font-semibold text-slate-800 truncate">{{ $name }}@if($qty > 1) <span class="text-slate-500 font-medium">×{{ $qty }}</span>@endif</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                        @if($emiItems->count() > 3)
                                            <p class="mt-1 text-[11px] text-slate-400">+{{ $emiItems->count() - 3 }} more</p>
                                        @endif
                                    @else
                                        <span class="text-xs text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-slate-700 align-top">{{ $plan->months }} months</td>
                                <td class="px-4 py-3 text-right font-semibold align-top">{{ format_taka((float) $plan->installment_amount) }}</td>
                                <td class="px-4 py-3 text-right font-bold text-indigo-700 align-top">{{ format_taka((float) $plan->remaining_amount) }}</td>
                                <td class="px-4 py-3 text-right align-top">
                                    <a href="{{ route('customers.emi.show', $plan) }}"
                                       class="inline-flex rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-indigo-700">
                                        Schedule / Collect
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-10 text-center text-slate-500">No active EMI plans.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
