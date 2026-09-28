<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $customer->name }} — EMI plans</h2>
                <p class="mt-0.5 text-sm text-slate-500">{{ $customer->phone ?: 'No mobile' }} · Outstanding {{ format_taka((float) $customer->emi_balance) }}</p>
            </div>
            <a href="{{ route('customers.emi.index') }}" class="inline-flex rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-bold uppercase tracking-wide text-slate-700 shadow-sm hover:bg-slate-50">
                All EMI
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Invoice</th>
                            <th class="px-4 py-3">Products on EMI</th>
                            <th class="px-4 py-3">Tenure</th>
                            <th class="px-4 py-3 text-right">Remaining</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($plans as $plan)
                            @php
                                $emiItems = $plan->order?->items ?? collect();
                            @endphp
                            <tr>
                                <td class="px-4 py-3 font-semibold align-top">{{ $plan->order->invoice_no ?? '—' }}</td>
                                <td class="px-4 py-3 align-top">
                                    @if($emiItems->isNotEmpty())
                                        <ul class="space-y-2">
                                            @foreach($emiItems as $item)
                                                @php
                                                    $product = $item->product;
                                                    $imgPath = $product ? ($product->image ?: ($product->imagePaths()[0] ?? null)) : null;
                                                    $imgUrl = public_storage_url($imgPath);
                                                    $name = $product->name ?? 'Product';
                                                    $qty = (int) $item->quantity;
                                                @endphp
                                                <li class="flex items-center gap-2.5 min-w-0">
                                                    <div class="h-10 w-10 shrink-0 overflow-hidden rounded-lg border border-slate-200 bg-slate-100">
                                                        @if($imgUrl)
                                                            <img src="{{ $imgUrl }}" alt="{{ $name }}" class="h-full w-full object-cover" loading="lazy">
                                                        @else
                                                            <div class="flex h-full w-full items-center justify-center text-slate-300">
                                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div class="min-w-0">
                                                        <div class="text-sm font-semibold text-slate-800 truncate">{{ $name }}@if($qty > 1) <span class="text-slate-500 font-medium">×{{ $qty }}</span>@endif</div>
                                                    </div>
                                                </li>
                                            @endforeach
                                        </ul>
                                        <p class="mt-1.5 text-[11px] text-slate-400">{{ $emiItems->sum('quantity') }} item(s)</p>
                                    @else
                                        <span class="text-slate-400 text-xs">No products</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 align-top">{{ $plan->months }} mo</td>
                                <td class="px-4 py-3 text-right font-bold text-indigo-700 align-top">{{ format_taka((float) $plan->remaining_amount) }}</td>
                                <td class="px-4 py-3 uppercase text-xs font-bold align-top">{{ $plan->status }}</td>
                                <td class="px-4 py-3 text-right align-top">
                                    <a href="{{ route('customers.emi.show', $plan) }}" class="text-indigo-600 font-semibold hover:underline">Open</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-10 text-center text-slate-500">No EMI plans for this customer.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
