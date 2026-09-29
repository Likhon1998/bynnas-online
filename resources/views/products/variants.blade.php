<x-app-layout>
    <div class="w-full min-w-0 pb-6 text-[12px] leading-snug text-slate-700">
        <div class="mb-3 flex flex-wrap items-center gap-2">
            <div class="mr-auto">
                <h1 class="text-[15px] font-semibold tracking-tight text-slate-900">Product Variants</h1>
                <p class="text-[11px] text-slate-500">
                    {{ number_format($families->total()) }} product {{ \Illuminate\Support\Str::plural('family', $families->total()) }} with variants
                    &middot; {{ number_format($singleCount) }} single products
                </p>
            </div>
            <form method="GET" class="flex items-center gap-1.5">
                <input type="search" name="q" value="{{ $search }}" placeholder="Search name, code, SKU…"
                       class="w-56 rounded-md border border-slate-200 bg-white px-2 py-1.5 text-[12px] focus:border-indigo-400 focus:ring-1 focus:ring-indigo-200">
                <button type="submit" class="rounded-md border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-medium text-slate-700 hover:bg-slate-50">Search</button>
            </form>
            <a href="{{ route('attributes.index') }}" class="inline-flex items-center rounded-md border border-slate-200 bg-white px-2.5 py-1.5 text-[11px] font-medium text-slate-700 hover:bg-slate-50">Attributes</a>
            <a href="{{ route('products.create') }}" class="inline-flex items-center rounded-md bg-indigo-600 px-2.5 py-1.5 text-[11px] font-semibold text-white hover:bg-indigo-700">+ Add Product</a>
        </div>

        <div class="space-y-3">
            @forelse($families as $family)
                @php
                    $rows = $variantsByGroup->get($family->variant_group, collect());
                    $first = $rows->first();
                    $available = (int) $family->total_stock - (int) $family->total_reserved;
                    $baseName = $first ? trim(preg_replace('/\s+[-—]\s+.*$/u', '', $first->name)) : $family->variant_group;
                @endphp
                <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 px-3 py-2">
                        <div class="h-10 w-10 shrink-0 overflow-hidden rounded-lg border border-slate-100 bg-slate-50">
                            @if($first?->image)
                                <img src="{{ public_storage_url($first->image) }}" alt="" class="h-full w-full object-cover">
                            @endif
                        </div>
                        <div class="mr-auto min-w-0">
                            <p class="truncate text-[13px] font-semibold text-slate-900">{{ $baseName }}</p>
                            <p class="text-[10px] text-slate-400">
                                {{ $family->variants_count }} variants
                                @if($first?->category) &middot; {{ $first->category->name }} @endif
                                @if($first?->brand) &middot; {{ $first->brand->name }} @endif
                                &middot; key <span class="font-mono">{{ $family->variant_group }}</span>
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-[12px] font-semibold tabular-nums text-slate-900">
                                Tk {{ number_format((float) $family->min_price, 0) }}@if((float) $family->max_price > (float) $family->min_price) – {{ number_format((float) $family->max_price, 0) }}@endif
                            </p>
                            <p class="text-[10px] tabular-nums {{ $available > 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ number_format($available) }} available</p>
                        </div>
                        @if($first)
                            <a href="{{ route('products.create', ['variant_of' => $first->id]) }}" class="rounded-md border border-indigo-200 bg-indigo-50 px-2 py-1 text-[11px] font-semibold text-indigo-700 hover:bg-indigo-100">+ Variant</a>
                        @endif
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-[11px]">
                            <thead class="bg-slate-50/70 text-[10px] uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-3 py-1.5 text-left font-semibold">Variant</th>
                                    <th class="px-3 py-1.5 text-left font-semibold">Code / SKU</th>
                                    <th class="px-3 py-1.5 text-right font-semibold">Price</th>
                                    <th class="px-3 py-1.5 text-right font-semibold">Stock</th>
                                    <th class="px-3 py-1.5 text-right font-semibold"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($rows as $variant)
                                    @php $free = max(0, (int) $variant->stock_quantity - (int) $variant->reserved_stock); @endphp
                                    <tr class="hover:bg-slate-50/60">
                                        <td class="px-3 py-1.5">
                                            <div class="flex flex-wrap items-center gap-1">
                                                @forelse($variant->variantOptions() as $option)
                                                    <span class="inline-flex items-center gap-1 rounded-full border border-slate-200 px-1.5 py-0.5 text-[10px] text-slate-700">
                                                        @if($option['hex'])
                                                            <span class="h-2.5 w-2.5 rounded-full border border-black/10" style="background: {{ $option['hex'] }}"></span>
                                                        @endif
                                                        <span class="text-slate-400">{{ $option['attribute'] }}:</span> {{ $option['value'] }}
                                                    </span>
                                                @empty
                                                    <span class="text-slate-400">No attributes</span>
                                                @endforelse
                                                @if($variant->is_published === false)
                                                    <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] text-slate-500">Hidden</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-3 py-1.5 font-mono text-[10px] text-slate-500">{{ $variant->barcode ?: '—' }}@if($variant->sku) <span class="text-slate-400">/ {{ $variant->sku }}</span>@endif</td>
                                        <td class="px-3 py-1.5 text-right tabular-nums text-slate-800">Tk {{ number_format($variant->currentPrice(), 0) }}</td>
                                        <td class="px-3 py-1.5 text-right tabular-nums {{ $free > 0 ? 'text-slate-800' : 'text-rose-600' }}">{{ number_format($free) }}</td>
                                        <td class="px-3 py-1.5 text-right whitespace-nowrap">
                                            <a href="{{ route('products.edit', $variant) }}" class="font-medium text-indigo-600 hover:text-indigo-800">Edit</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <div class="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center">
                    <p class="text-[13px] font-semibold text-slate-800">No products with variants yet</p>
                    <p class="mt-1 text-[11px] text-slate-500">Create a product and choose “Product with variants” to add sizes, colours and other options.</p>
                </div>
            @endforelse
        </div>

        <div class="mt-3">{{ $families->links() }}</div>
    </div>
</x-app-layout>
