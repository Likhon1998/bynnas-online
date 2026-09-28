<x-app-layout>
    @php
        $stats = $stats ?? [
            'total_products' => 0,
            'total_stock' => 0,
            'total_value' => 0,
            'out_of_stock' => 0,
            'on_sale' => 0,
        ];
        $activeBrandSales = $activeBrandSales ?? collect();
        $pageIds = $products->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
    @endphp

    <div class="w-full min-w-0 pb-4 text-[12px] leading-snug text-slate-700"
         x-data="productSales(@js($pageIds), @js(route('products.barcodes.print')))"
         style="--pl-blue:#4F46E5;--pl-sale:#EF4444;--pl-green:#10B981;--pl-amber:#F59E0B;--pl-purple:#8B5CF6;--pl-sky:#3B82F6;">

        {{-- Compact top bar --}}
        <div class="mb-2 flex flex-wrap items-center gap-1.5">
            <h1 class="mr-1 text-[15px] font-semibold tracking-tight text-slate-900">Product List</h1>

            <a href="{{ route('products.create') }}"
               class="inline-flex items-center gap-1 rounded-md bg-[var(--pl-blue)] px-2.5 py-1 text-[11px] font-semibold text-white shadow-sm hover:bg-indigo-700">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M12 4v16m8-8H4"/></svg>
                Add Product
            </a>

            <div class="ml-auto flex flex-wrap items-center gap-1">
                <button type="button" @click="openBrandSale()"
                        class="inline-flex items-center gap-1 rounded-md border border-rose-200 bg-white px-2 py-1 text-[11px] font-medium text-[var(--pl-sale)] hover:bg-rose-50">
                    Brand sale
                </button>
                <button type="button" @click="openBrandEndSale()"
                        class="inline-flex items-center gap-1 rounded-md border border-slate-200 bg-white px-2 py-1 text-[11px] font-medium text-slate-600 hover:bg-slate-50">
                    End sale
                </button>
                <a href="{{ route('supply.opening-inventory.index') }}"
                   class="inline-flex items-center gap-1 rounded-md border border-emerald-200 bg-white px-2 py-1 text-[11px] font-medium text-emerald-700 hover:bg-emerald-50">
                    Opening Stock
                </a>
                <a href="{{ route('supply.adjustments.index') }}"
                   class="inline-flex items-center gap-1 rounded-md border border-amber-200 bg-white px-2 py-1 text-[11px] font-medium text-amber-700 hover:bg-amber-50">
                    Adjust
                </a>
                <a href="{{ route('products.barcodes') }}"
                   class="inline-flex items-center gap-1 rounded-md border border-sky-200 bg-white px-2 py-1 text-[11px] font-medium text-sky-700 hover:bg-sky-50">
                    Barcodes
                </a>
                <a href="{{ route('products.import') }}"
                   class="inline-flex items-center gap-1 rounded-md border border-violet-200 bg-white px-2 py-1 text-[11px] font-medium text-violet-700 hover:bg-violet-50">
                    Import
                </a>
            </div>
        </div>

        {{-- Compact KPI strip --}}
        <div class="mb-2 flex flex-wrap items-center gap-x-3 gap-y-1 rounded-lg border border-slate-200/80 bg-white px-2.5 py-1.5 text-[11px] shadow-sm">
            <span class="inline-flex items-center gap-1 text-slate-600">
                <span class="font-medium text-slate-400">Products</span>
                <span class="font-semibold tabular-nums text-slate-900">{{ number_format($stats['total_products']) }}</span>
            </span>
            <span class="hidden h-3 w-px bg-slate-200 sm:block" aria-hidden="true"></span>
            <span class="inline-flex items-center gap-1 text-slate-600">
                <span class="font-medium text-slate-400">Stock</span>
                <span class="font-semibold tabular-nums text-slate-900">{{ number_format($stats['total_stock']) }}</span>
            </span>
            <span class="hidden h-3 w-px bg-slate-200 sm:block" aria-hidden="true"></span>
            <span class="inline-flex items-center gap-1 text-slate-600">
                <span class="font-medium text-slate-400">Value</span>
                <span class="font-semibold tabular-nums text-slate-900">Tk {{ number_format($stats['total_value'], 0) }}</span>
            </span>
            <span class="hidden h-3 w-px bg-slate-200 sm:block" aria-hidden="true"></span>
            <span class="inline-flex items-center gap-1 {{ $stats['out_of_stock'] > 0 ? 'text-[var(--pl-sale)]' : 'text-slate-600' }}">
                <span class="font-medium text-slate-400">Out</span>
                <span class="font-semibold tabular-nums">{{ number_format($stats['out_of_stock']) }}</span>
            </span>
            <span class="hidden h-3 w-px bg-slate-200 sm:block" aria-hidden="true"></span>
            <span class="inline-flex items-center gap-1 {{ ($stats['on_sale'] ?? 0) > 0 ? 'text-[var(--pl-sale)]' : 'text-slate-600' }}">
                <span class="font-medium text-slate-400">On sale</span>
                <span class="font-semibold tabular-nums">{{ number_format($stats['on_sale'] ?? 0) }}</span>
            </span>
        </div>

        @if (session('success'))
            <div data-admin-flash-banner x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                 class="mb-2 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 flex items-center justify-between">
                <span class="text-[12px] font-medium text-emerald-800">{{ session('success') }}</span>
                <button type="button" @click="show = false" class="text-emerald-600 text-sm leading-none">&times;</button>
            </div>
        @endif
        @if ($errors->any())
            <div class="mb-2 rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-[12px] text-rose-800">
                <ul class="list-disc pl-4 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        @if(session('import_errors') && count(session('import_errors')))
            <div class="mb-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-[12px] text-amber-900">
                <p class="font-semibold mb-1">Some CSV rows were skipped:</p>
                <ul class="list-disc pl-4 space-y-0.5 text-[11px] max-h-36 overflow-y-auto">
                    @foreach(session('import_errors') as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($activeBrandSales->isNotEmpty())
            <div class="mb-2 rounded-lg border border-rose-200 bg-rose-50/50 px-2.5 py-1.5">
                <div class="flex flex-wrap items-center gap-2">
                    <p class="text-[11px] font-semibold text-rose-800">
                        {{ $activeBrandSales->count() }} brand sale{{ $activeBrandSales->count() === 1 ? '' : 's' }} active
                        · {{ number_format($stats['on_sale'] ?? 0) }} product{{ ($stats['on_sale'] ?? 0) === 1 ? '' : 's' }}
                    </p>
                    <div class="ml-auto flex items-center gap-1">
                        <a href="{{ route('products.index', ['status' => 'sale']) }}"
                           data-no-progress
                           @click.prevent="softLoad(@js(route('products.index', ['status' => 'sale'])))"
                           class="rounded-md border border-rose-200 bg-white px-2 py-0.5 text-[10px] font-semibold text-rose-700 hover:bg-rose-50">
                            View sale
                        </a>
                        <button type="button" @click="openBrandEndSale()"
                                class="rounded-md border border-slate-200 bg-white px-2 py-0.5 text-[10px] font-medium text-slate-600 hover:bg-slate-50">
                            End sale
                        </button>
                    </div>
                </div>
                <div class="mt-1.5 flex flex-wrap gap-1.5">
                    @foreach($activeBrandSales as $campaign)
                        @php
                            $endsAt = $campaign['ends_at'] ?? null;
                            $endsCarbon = $endsAt ? \Illuminate\Support\Carbon::parse($endsAt) : null;
                            $endsLabel = $endsCarbon
                                ? $endsCarbon->timezone(config('app.display_timezone', config('app.timezone')))->format('d M')
                                : '—';
                        @endphp
                        <a href="{{ route('products.index', ['status' => 'sale', 'q' => $campaign['brand_name']]) }}"
                           data-no-progress
                           @click.prevent="softLoad(@js(route('products.index', ['status' => 'sale', 'q' => $campaign['brand_name']])))"
                           class="inline-flex items-center gap-1 rounded-md border border-rose-100 bg-white px-2 py-0.5 text-[10px] text-slate-700 hover:border-rose-300">
                            <span class="font-semibold">{{ $campaign['brand_name'] }}</span>
                            <span class="text-slate-400">{{ number_format($campaign['product_count']) }} · ends {{ $endsLabel }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Table card --}}
        <div id="products-table-panel"
             class="rounded-xl border border-slate-200/80 bg-white shadow-sm overflow-hidden"
             data-page-ids='@json($pageIds)'>
            <form method="GET"
                  action="{{ route('products.index') }}"
                  id="product-filters"
                  data-no-progress
                  @submit.prevent="softFilter($event.target)"
                  class="px-2.5 py-2 border-b border-slate-100">
                <div class="flex flex-col xl:flex-row xl:items-center gap-1.5">
                    <div class="relative flex-1 min-w-0">
                        <svg class="pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"/>
                        </svg>
                        <input type="search" name="q" value="{{ $search ?? request('q') }}"
                               placeholder="Search name, brand, SKU, barcode…"
                               @input.debounce.350ms="softFilter($event.target.form)"
                               class="w-full rounded-lg border-slate-200 bg-slate-50/70 text-[12px] py-1.5 pl-8 pr-2.5 text-slate-700 placeholder:text-slate-400 focus:border-indigo-400 focus:ring-indigo-400 focus:bg-white">
                    </div>
                    <div class="flex flex-wrap items-center gap-1.5">
                        <select name="category_id"
                                @change="softFilter($event.target.form)"
                                class="rounded-lg border-slate-200 bg-white text-[12px] py-1.5 min-w-[130px] text-slate-600 focus:border-indigo-400 focus:ring-indigo-400">
                            <option value="">All Categories</option>
                            @foreach(($categories ?? collect()) as $category)
                                <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <select name="status"
                                @change="softFilter($event.target.form)"
                                class="rounded-lg border-slate-200 bg-white text-[12px] py-1.5 min-w-[110px] text-slate-600 focus:border-indigo-400 focus:ring-indigo-400">
                            <option value="">All Status</option>
                            <option value="ok" @selected(($status ?? request('status')) === 'ok')>OK</option>
                            <option value="low" @selected(($status ?? request('status')) === 'low')>Low stock</option>
                            <option value="out" @selected(($status ?? request('status')) === 'out')>Out of stock</option>
                            <option value="sale" @selected(($status ?? request('status')) === 'sale')>On sale</option>
                            <option value="hidden" @selected(($status ?? request('status')) === 'hidden')>Hidden</option>
                        </select>
                        @if(filled($search ?? null) || request()->filled('category_id') || filled($status ?? null) || request()->filled('status') || request()->filled('q'))
                            <a href="{{ route('products.index') }}"
                               data-no-progress
                               @click.prevent="softFilter(null)"
                               class="text-[11px] font-medium text-slate-500 hover:text-slate-700 px-1.5">Clear</a>
                        @endif
                    </div>
                </div>
                @if(($status ?? request('status')) === 'sale')
                    <p class="mt-1.5 text-[11px] text-rose-600 font-medium">Showing products with an active timed sale only.</p>
                @endif
            </form>

            {{-- Selection bar --}}
            <div x-show="selected.length > 0" x-cloak
                 class="px-3 py-2 border-b border-indigo-100 bg-indigo-50/70 flex flex-wrap items-center justify-between gap-2">
                <p class="text-[11px] font-medium text-indigo-800">
                    <span class="font-semibold tabular-nums" x-text="selected.length"></span>
                    selected on this page
                </p>
                <div class="flex items-center gap-1.5">
                    <button type="button" @click="printSelected()"
                            class="inline-flex items-center gap-1 rounded-lg bg-white border border-sky-200 px-2.5 py-1 text-[11px] font-medium text-sky-700 hover:bg-sky-50">
                        Print barcodes
                    </button>
                    <button type="button" @click="clearSelection()"
                            class="inline-flex items-center rounded-lg px-2.5 py-1 text-[11px] font-medium text-slate-600 hover:bg-white/80">
                        Clear
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[1040px]">
                    <thead>
                        <tr class="bg-[#F8FAFC] border-b border-slate-200 text-slate-500">
                            <th class="pl-3 pr-1 py-2 w-9">
                                <input type="checkbox"
                                       id="select-all-products"
                                       class="h-3.5 w-3.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
                                       :checked="allSelected"
                                       @change="toggleAll($event.target.checked)"
                                       :aria-checked="someSelected ? 'mixed' : (allSelected ? 'true' : 'false')"
                                       title="Select all on this page">
                            </th>
                            <th class="px-2.5 py-2 text-[10px] font-semibold uppercase tracking-wider">Product</th>
                            <th class="px-2.5 py-2 text-[10px] font-semibold uppercase tracking-wider">Added</th>
                            <th class="px-2.5 py-2 text-[10px] font-semibold uppercase tracking-wider">Sell</th>
                            <th class="px-2.5 py-2 text-[10px] font-semibold uppercase tracking-wider">Cost</th>
                            <th class="px-2.5 py-2 text-[10px] font-semibold uppercase tracking-wider">Stock</th>
                            <th class="px-2.5 py-2 text-[10px] font-semibold uppercase tracking-wider">Value</th>
                            <th class="px-2.5 py-2 text-[10px] font-semibold uppercase tracking-wider text-center" title="Show in homepage New Arrivals">New</th>
                            <th class="px-2.5 py-2 text-[10px] font-semibold uppercase tracking-wider text-center" title="Show in homepage Trending">Trend</th>
                            <th class="px-2.5 py-2 text-[10px] font-semibold uppercase tracking-wider text-center">Status</th>
                            <th class="px-3 py-2 text-[10px] font-semibold uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($products as $product)
                            @php
                                $onSale = $product->isOnSale();
                                $isLow = $product->stock_quantity <= ($product->alert_quantity ?? 5);
                                $isOut = $product->stock_quantity <= 0;
                                $salePayload = [
                                    'id' => $product->id,
                                    'name' => $product->name,
                                    'selling_price' => (float) $product->selling_price,
                                    'sale_price' => $product->sale_price !== null ? (float) $product->sale_price : null,
                                    'sale_starts_at' => optional($product->sale_starts_at)->format('Y-m-d\\TH:i'),
                                    'sale_ends_at' => optional($product->sale_ends_at)->format('Y-m-d\\TH:i'),
                                    'on_sale' => $onSale,
                                    'sale_url' => route('products.sale', $product),
                                    'clear_url' => route('products.sale.clear', $product),
                                ];
                            @endphp
                            <tr class="hover:bg-slate-50/70 transition-colors {{ $onSale ? 'bg-rose-50/30' : '' }}"
                                :class="isSelected({{ $product->id }}) && 'bg-indigo-50/40'"
                                @if($onSale) style="box-shadow: inset 3px 0 0 #EF4444" @endif>
                                <td class="pl-3 pr-1 py-2.5 align-middle">
                                    <input type="checkbox"
                                           class="h-3.5 w-3.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
                                           value="{{ $product->id }}"
                                           :checked="isSelected({{ $product->id }})"
                                           @change="toggleOne({{ $product->id }}, $event.target.checked)">
                                </td>
                                <td class="px-2.5 py-2.5 align-middle">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <div class="h-9 w-9 shrink-0 rounded-lg bg-slate-50 border border-slate-100 overflow-hidden flex items-center justify-center {{ $onSale ? 'ring-1 ring-rose-200' : '' }}">
                                            @if($product->image)
                                                <img src="{{ public_storage_url($product->image) }}" alt="" class="h-full w-full object-cover">
                                            @else
                                                <svg class="w-3.5 h-3.5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-[12px] font-medium text-slate-900 truncate max-w-[240px]" title="{{ $product->name }}">{{ $product->name }}</p>
                                            <p class="text-[10px] text-slate-500 mt-0.5 truncate max-w-[240px]">
                                                {{ $product->category->name ?? 'Uncategorized' }}
                                                @if($product->brand_name || $product->brand)
                                                    · {{ $product->brand_name ?? $product->brand?->name }}
                                                @endif
                                                · <span class="font-mono text-slate-400">{{ $product->sku ?: $product->barcode }}</span>
                                            </p>
                                            <div class="mt-1 flex flex-wrap items-center gap-1">
                                                @if($onSale)
                                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[9px] font-bold uppercase tracking-wide bg-[var(--pl-sale)] text-white">
                                                        −{{ $product->discountPercent() }}% sale
                                                    </span>
                                                    @if($product->sale_ends_at)
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[9px] font-semibold bg-rose-100 text-rose-700 border border-rose-200">
                                                            Ends {{ $product->sale_ends_at->timezone(config('app.display_timezone', config('app.timezone')))->format('d M, h:i A') }}
                                                        </span>
                                                    @endif
                                                @elseif($product->sale_price !== null && $product->sale_ends_at && $product->sale_ends_at->isFuture())
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[9px] font-bold uppercase tracking-wide bg-amber-500 text-white">Scheduled</span>
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[9px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                                        From {{ $product->sale_starts_at?->timezone(config('app.display_timezone', config('app.timezone')))->format('d M') }}
                                                    </span>
                                                @endif
                                                @if($product->is_published === false)
                                                    <span class="inline-flex items-center px-1 py-px rounded text-[9px] font-bold uppercase tracking-wide bg-slate-100 text-slate-500">Hidden</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-2.5 py-2.5 align-middle whitespace-nowrap">
                                    <div class="text-[11px] text-slate-700">{{ $product->created_at->format('d M Y') }}</div>
                                    <div class="text-[10px] text-slate-400">{{ $product->created_at->format('h:i A') }}</div>
                                </td>
                                <td class="px-2.5 py-2.5 align-middle whitespace-nowrap">
                                    @if($onSale)
                                        <div class="text-[12px] font-semibold text-[var(--pl-sale)] tabular-nums">{{ format_taka($product->currentPrice(), 'Tk ') }}</div>
                                        <div class="text-[10px] text-slate-400 line-through tabular-nums">{{ format_taka($product->selling_price, 'Tk ') }}</div>
                                    @else
                                        <div class="text-[12px] font-medium text-slate-900 tabular-nums">{{ format_taka($product->selling_price, 'Tk ') }}</div>
                                    @endif
                                </td>
                                <td class="px-2.5 py-2.5 align-middle whitespace-nowrap text-[11px] text-slate-500 tabular-nums">{{ format_taka($product->cost_price, 'Tk ') }}</td>
                                <td class="px-2.5 py-2.5 align-middle whitespace-nowrap text-[12px] font-medium text-slate-800 tabular-nums">{{ $product->stock_quantity }}</td>
                                <td class="px-2.5 py-2.5 align-middle whitespace-nowrap text-[11px] text-slate-700 tabular-nums">{{ format_taka($product->cost_price * $product->stock_quantity, 'Tk ') }}</td>
                                <td class="px-2.5 py-2.5 align-middle text-center">
                                    <input type="checkbox"
                                           class="h-3.5 w-3.5 rounded border-emerald-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer"
                                           title="New Arrival on homepage"
                                           @checked($product->is_new_arrival)
                                           @change="toggleHomepageFlag({{ $product->id }}, 'is_new_arrival', $event.target.checked, $event.target)">
                                </td>
                                <td class="px-2.5 py-2.5 align-middle text-center">
                                    <input type="checkbox"
                                           class="h-3.5 w-3.5 rounded border-violet-300 text-violet-600 focus:ring-violet-500 cursor-pointer"
                                           title="Trending on homepage"
                                           @checked($product->is_best_seller)
                                           @change="toggleHomepageFlag({{ $product->id }}, 'is_best_seller', $event.target.checked, $event.target)">
                                </td>
                                <td class="px-2.5 py-2.5 align-middle text-center">
                                    @if($isOut)
                                        <span class="inline-flex px-2 py-px text-[10px] font-semibold rounded-full bg-rose-50 text-[var(--pl-sale)] border border-rose-100">Out</span>
                                    @elseif($isLow)
                                        <span class="inline-flex px-2 py-px text-[10px] font-semibold rounded-full bg-amber-50 text-amber-700 border border-amber-100">Low</span>
                                    @else
                                        <span class="inline-flex px-2 py-px text-[10px] font-semibold rounded-full bg-emerald-50 text-emerald-700 border border-emerald-100">OK</span>
                                    @endif
                                </td>
                                <td class="px-3 py-2.5 align-middle text-right whitespace-nowrap">
                                    <div class="inline-flex items-center justify-end gap-0.5">
                                        <a href="{{ route('products.barcodes.print', ['product_ids' => $product->id]) }}"
                                           class="p-1 text-slate-400 hover:text-sky-600 hover:bg-sky-50 rounded-md transition" title="Print barcode">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                        </a>
                                        <button type="button"
                                                @click="openSale(@js($salePayload))"
                                                class="px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-[var(--pl-sale)] hover:bg-rose-50 rounded-md transition"
                                                title="Set sale">
                                            Sale
                                        </button>
                                        <a href="{{ route('products.edit', $product) }}"
                                           class="p-1 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-md transition" title="Edit">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </a>
                                        <form action="{{ route('products.destroy', $product) }}" method="POST" class="m-0 inline" data-confirm="Delete this product permanently?" data-confirm-title="Delete?" data-confirm-ok="Delete" data-confirm-tone="danger">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1 text-slate-400 hover:text-[var(--pl-sale)] hover:bg-rose-50 rounded-md transition" title="Delete">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="px-4 py-12 text-center">
                                    <p class="text-[12px] font-medium text-slate-800">No products found</p>
                                    <p class="text-[11px] text-slate-500 mt-1 mb-2">Try clearing filters or add a new product.</p>
                                    <a href="{{ route('products.create') }}" class="text-[12px] font-medium text-indigo-600 hover:text-indigo-700">Add your first product →</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($products->total() > 0)
                <div class="border-t border-slate-100 px-3 py-2.5 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-2 bg-[#F8FAFC]/70">
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-slate-500">
                        <p>
                            Showing
                            <span class="font-semibold text-slate-700">{{ $products->firstItem() }}</span>
                            to
                            <span class="font-semibold text-slate-700">{{ $products->lastItem() }}</span>
                            of
                            <span class="font-semibold text-slate-700">{{ $products->total() }}</span>
                        </p>
                        <p class="text-slate-300 hidden sm:inline">·</p>
                        <p>
                            Page value
                            <span class="font-semibold text-slate-800 tabular-nums">{{ format_taka($products->sum(fn ($p) => $p->cost_price * $p->stock_quantity), 'Tk ') }}</span>
                        </p>
                    </div>
                    <div class="pl-products-pager text-[11px]">
                        {{ $products->links() }}
                    </div>
                </div>
            @endif
        </div>

        {{-- Sale modal --}}
        <div x-show="saleOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="saleOpen = false">
            <div class="absolute inset-0 bg-slate-900/40" @click="saleOpen = false"></div>
            <div class="relative w-full max-w-sm rounded-xl bg-white shadow-xl border border-slate-200 p-4" @click.stop>
                <div class="flex items-start justify-between gap-2 mb-1">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-[var(--pl-sale)] mb-0.5">Timed sale</p>
                        <h3 class="text-[14px] font-semibold text-slate-900">Set sale</h3>
                        <p class="text-[11px] text-slate-500 mt-0.5" x-text="saleProduct?.name"></p>
                    </div>
                    <button type="button" @click="saleOpen = false" class="p-1 rounded-md text-slate-400 hover:bg-slate-100 text-sm leading-none">&times;</button>
                </div>
                <p class="text-[11px] text-slate-500 mt-2.5 rounded-lg bg-slate-50 border border-slate-100 px-2.5 py-2">
                    Normal price:
                    <span class="font-semibold text-slate-800" x-text="saleProduct ? ('Tk ' + Number(saleProduct.selling_price).toLocaleString(undefined, {minimumFractionDigits: 2})) : ''"></span>
                </p>
                <form method="POST" :action="saleProduct?.sale_url" class="mt-3 space-y-2.5">
                    @csrf
                    <input type="hidden" name="discount_type" :value="saleForm.discount_type">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-600 mb-1.5">Discount as</label>
                        <div class="grid grid-cols-2 gap-1.5 rounded-lg bg-slate-100 p-1">
                            <button type="button" @click="saleForm.discount_type = 'percent'"
                                    :class="saleForm.discount_type === 'percent' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                                    class="rounded-md px-2 py-1.5 text-[11px] font-semibold transition">Percentage %</button>
                            <button type="button" @click="saleForm.discount_type = 'tk'"
                                    :class="saleForm.discount_type === 'tk' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                                    class="rounded-md px-2 py-1.5 text-[11px] font-semibold transition">Offer price (Tk)</button>
                        </div>
                    </div>
                    <div x-show="saleForm.discount_type === 'percent'">
                        <label class="block text-[11px] font-medium text-slate-600 mb-1">Discount %</label>
                        <input type="number" step="0.01" min="0.01" max="99.99" name="percent" x-model="saleForm.percent"
                               :required="saleForm.discount_type === 'percent'"
                               :disabled="saleForm.discount_type !== 'percent'"
                               class="block w-full rounded-lg border-slate-200 text-[12px] py-1.5"
                               placeholder="e.g. 10">
                        <p class="mt-1 text-[10px] text-slate-400" x-show="saleProduct && saleForm.percent">
                            Offer ≈
                            <span class="font-semibold text-slate-700"
                                  x-text="'Tk ' + Math.round(Math.max(0, Number(saleProduct.selling_price) * (1 - (Number(saleForm.percent || 0) / 100)))).toLocaleString()"></span>
                        </p>
                    </div>
                    <div x-show="saleForm.discount_type === 'tk'">
                        <label class="block text-[11px] font-medium text-slate-600 mb-1">Offer price (Tk)</label>
                        <input type="number" step="0.01" min="0" name="sale_price" x-model="saleForm.sale_price"
                               :required="saleForm.discount_type === 'tk'"
                               :disabled="saleForm.discount_type !== 'tk'"
                               class="block w-full rounded-lg border-slate-200 text-[12px] py-1.5"
                               placeholder="e.g. 4500">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] font-medium text-slate-600 mb-1">Starts</label>
                            <input type="datetime-local" name="sale_starts_at" required x-model="saleForm.sale_starts_at"
                                   class="block w-full rounded-lg border-slate-200 text-[11px] py-1.5">
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-slate-600 mb-1">Ends</label>
                            <input type="datetime-local" name="sale_ends_at" required x-model="saleForm.sale_ends_at"
                                   class="block w-full rounded-lg border-slate-200 text-[11px] py-1.5">
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-2 pt-1">
                        <button type="button" @click="saleOpen = false" class="px-2.5 py-1.5 text-[11px] font-medium text-slate-600 hover:bg-slate-50 rounded-lg">Cancel</button>
                        <div class="flex items-center gap-1.5">
                            <template x-if="saleProduct?.sale_price !== null && saleProduct?.sale_price !== undefined">
                                <button type="button" form="clear-sale-form"
                                        class="px-2.5 py-1.5 text-[11px] font-medium text-slate-600 border border-slate-200 hover:bg-slate-50 rounded-lg"
                                        data-confirm-click="Remove this sale?"
                                        data-confirm-title="End sale?"
                                        data-confirm-ok="End sale"
                                        data-confirm-tone="warning">End sale</button>
                            </template>
                            <button type="submit" class="px-3 py-1.5 text-[11px] font-semibold text-white bg-[var(--pl-sale)] hover:bg-rose-600 rounded-lg">Save sale</button>
                        </div>
                    </div>
                </form>
                <form id="clear-sale-form" method="POST" :action="saleProduct?.clear_url" class="hidden">@csrf @method('DELETE')</form>
            </div>
        </div>

        {{-- Brand sale modal --}}
        <div x-show="brandOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="brandOpen = false">
            <div class="absolute inset-0 bg-slate-900/40" @click="brandOpen = false"></div>
            <div class="relative w-full max-w-sm rounded-xl bg-white shadow-xl border border-slate-200 p-4" @click.stop>
                <div class="flex items-start justify-between gap-2 mb-1">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-[var(--pl-sale)] mb-0.5">Brand campaign</p>
                        <h3 class="text-[14px] font-semibold text-slate-900">Brand sale</h3>
                        <p class="text-[11px] text-slate-500 mt-0.5">Apply % or Tk off to every product of a brand.</p>
                    </div>
                    <button type="button" @click="brandOpen = false" class="p-1 rounded-md text-slate-400 hover:bg-slate-100 text-sm leading-none">&times;</button>
                </div>
                <form method="POST" action="{{ route('products.brand-sale') }}" class="mt-3 space-y-2.5">
                    @csrf
                    <input type="hidden" name="discount_type" :value="brandForm.discount_type">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-600 mb-1">Brand</label>
                        <select name="brand_id" required class="block w-full rounded-lg border-slate-200 text-[12px] py-1.5">
                            <option value="">Select brand</option>
                            @foreach(($brands ?? collect()) as $brand)
                                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-slate-600 mb-1.5">Discount as</label>
                        <div class="grid grid-cols-2 gap-1.5 rounded-lg bg-slate-100 p-1">
                            <button type="button" @click="brandForm.discount_type = 'percent'"
                                    :class="brandForm.discount_type === 'percent' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                                    class="rounded-md px-2 py-1.5 text-[11px] font-semibold transition">Percentage %</button>
                            <button type="button" @click="brandForm.discount_type = 'tk'"
                                    :class="brandForm.discount_type === 'tk' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                                    class="rounded-md px-2 py-1.5 text-[11px] font-semibold transition">Amount (Tk)</button>
                        </div>
                    </div>
                    <div x-show="brandForm.discount_type === 'percent'">
                        <label class="block text-[11px] font-medium text-slate-600 mb-1">Discount %</label>
                        <input type="number" name="percent" min="0.01" max="99.99" step="0.01" x-model="brandForm.percent"
                               :required="brandForm.discount_type === 'percent'"
                               :disabled="brandForm.discount_type !== 'percent'"
                               class="block w-full rounded-lg border-slate-200 text-[12px] py-1.5"
                               placeholder="e.g. 10">
                    </div>
                    <div x-show="brandForm.discount_type === 'tk'">
                        <label class="block text-[11px] font-medium text-slate-600 mb-1">Discount amount (Tk)</label>
                        <input type="number" name="amount" min="0.01" step="0.01" x-model="brandForm.amount"
                               :required="brandForm.discount_type === 'tk'"
                               :disabled="brandForm.discount_type !== 'tk'"
                               class="block w-full rounded-lg border-slate-200 text-[12px] py-1.5"
                               placeholder="e.g. 500">
                        <p class="mt-1 text-[10px] text-slate-400">Same Tk amount is subtracted from each product’s price.</p>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] font-medium text-slate-600 mb-1">Starts</label>
                            <input type="datetime-local" name="sale_starts_at" required x-model="brandForm.sale_starts_at"
                                   class="block w-full rounded-lg border-slate-200 text-[11px] py-1.5">
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-slate-600 mb-1">Ends</label>
                            <input type="datetime-local" name="sale_ends_at" required x-model="brandForm.sale_ends_at"
                                   class="block w-full rounded-lg border-slate-200 text-[11px] py-1.5">
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-1.5 pt-1">
                        <button type="button" @click="brandOpen = false" class="px-2.5 py-1.5 text-[11px] font-medium text-slate-600 hover:bg-slate-50 rounded-lg">Cancel</button>
                        <button type="submit" class="px-3 py-1.5 text-[11px] font-semibold text-white bg-[var(--pl-sale)] hover:bg-rose-600 rounded-lg">Apply to brand</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- End brand sale modal --}}
        <div x-show="brandEndOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="brandEndOpen = false">
            <div class="absolute inset-0 bg-slate-900/40" @click="brandEndOpen = false"></div>
            <div class="relative w-full max-w-sm rounded-xl bg-white shadow-xl border border-slate-200 p-4" @click.stop>
                <div class="flex items-start justify-between gap-2 mb-1">
                    <div>
                        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-0.5">Brand campaign</p>
                        <h3 class="text-[14px] font-semibold text-slate-900">End brand sale</h3>
                        <p class="text-[11px] text-slate-500 mt-0.5">Remove sale pricing from every product of a brand.</p>
                    </div>
                    <button type="button" @click="brandEndOpen = false" class="p-1 rounded-md text-slate-400 hover:bg-slate-100 text-sm leading-none">&times;</button>
                </div>
                <form method="POST" action="{{ route('products.brand-sale.clear') }}" class="mt-3 space-y-2.5"
                      data-confirm="End sale on all products of this brand?"
                      data-confirm-title="End brand sale?"
                      data-confirm-ok="End sale"
                      data-confirm-tone="warning">
                    @csrf
                    @method('DELETE')
                    <div>
                        <label class="block text-[11px] font-medium text-slate-600 mb-1">Brand</label>
                        <select name="brand_id" required class="block w-full rounded-lg border-slate-200 text-[12px] py-1.5">
                            <option value="">Select brand</option>
                            @php
                                $saleBrandIds = collect($activeBrandSales ?? [])->pluck('brand_id')->filter()->map(fn ($id) => (int) $id)->all();
                            @endphp
                            @foreach(($brands ?? collect()) as $brand)
                                <option value="{{ $brand->id }}">
                                    {{ $brand->name }}{{ in_array((int) $brand->id, $saleBrandIds, true) ? ' · ON SALE' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @if(count($saleBrandIds) > 0)
                            <p class="mt-1 text-[10px] text-rose-600 font-medium">Brands marked “ON SALE” have an active campaign.</p>
                        @endif
                    </div>
                    <div class="flex items-center justify-end gap-1.5 pt-1">
                        <button type="button" @click="brandEndOpen = false" class="px-2.5 py-1.5 text-[11px] font-medium text-slate-600 hover:bg-slate-50 rounded-lg">Cancel</button>
                        <button type="submit" class="px-3 py-1.5 text-[11px] font-semibold text-white bg-slate-800 hover:bg-slate-900 rounded-lg">End sale</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
        .pl-products-pager nav { justify-content: flex-end; }
        .pl-products-pager span, .pl-products-pager a { font-size: 11px !important; }
        .pl-products-pager [aria-current="page"] span,
        .pl-products-pager span[aria-current="page"] {
            background: #4F46E5 !important;
            border-color: #4F46E5 !important;
            color: #fff !important;
        }
    </style>

    <script>
        function productSales(pageIds, printUrl) {
            const pad = (n) => String(n).padStart(2, '0');
            const toLocal = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
            const now = new Date();
            const week = new Date(now.getTime() + 7 * 24 * 60 * 60 * 1000);
            const ids = (pageIds || []).map((id) => Number(id));
            const indexUrl = @js(route('products.index'));

            return {
                pageIds: ids,
                selected: [],
                printUrl: printUrl || '',
                filtering: false,
                saleOpen: false,
                brandOpen: false,
                brandEndOpen: false,
                saleProduct: null,
                saleForm: { discount_type: 'tk', percent: '', sale_price: '', sale_starts_at: '', sale_ends_at: '' },
                brandForm: { discount_type: 'percent', percent: '10', amount: '', sale_starts_at: toLocal(now), sale_ends_at: toLocal(week) },
                get allSelected() {
                    return this.pageIds.length > 0 && this.pageIds.every((id) => this.selected.includes(id));
                },
                get someSelected() {
                    return this.selected.length > 0 && !this.allSelected;
                },
                init() {
                    this.$watch('selected', () => this.syncSelectAll());
                    this.$nextTick(() => this.syncSelectAll());
                    this.$el.addEventListener('click', (e) => {
                        const link = e.target.closest?.('.pl-products-pager a[href]');
                        if (!link || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
                        e.preventDefault();
                        this.softLoad(link.href);
                    });
                    window.addEventListener('popstate', () => {
                        this.softLoad(window.location.href, false);
                    });
                },
                syncSelectAll() {
                    const el = document.getElementById('select-all-products');
                    if (el) {
                        el.indeterminate = this.someSelected;
                    }
                },
                isSelected(id) {
                    return this.selected.includes(Number(id));
                },
                toggleOne(id, checked) {
                    const value = Number(id);
                    if (checked) {
                        if (!this.selected.includes(value)) {
                            this.selected = [...this.selected, value];
                        }
                    } else {
                        this.selected = this.selected.filter((item) => item !== value);
                    }
                },
                toggleAll(checked) {
                    this.selected = checked ? [...this.pageIds] : [];
                },
                clearSelection() {
                    this.selected = [];
                },
                printSelected() {
                    if (!this.selected.length || !this.printUrl) return;
                    const url = new URL(this.printUrl, window.location.origin);
                    url.searchParams.set('product_ids', this.selected.join(','));
                    window.open(url.toString(), '_blank');
                },
                softFilter(form) {
                    if (!form) {
                        this.softLoad(indexUrl);
                        return;
                    }
                    const url = new URL(form.action || indexUrl, window.location.origin);
                    const data = new FormData(form);
                    url.search = '';
                    for (const [key, value] of data.entries()) {
                        if (String(value).trim() !== '') {
                            url.searchParams.set(key, value);
                        }
                    }
                    this.softLoad(url.toString());
                },
                async softLoad(url, push = true) {
                    if (this.filtering) return;
                    this.filtering = true;
                    try {
                        const res = await fetch(url, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'text/html',
                            },
                            credentials: 'same-origin',
                        });
                        if (!res.ok) {
                            window.location.href = url;
                            return;
                        }
                        const html = await res.text();
                        const doc = new DOMParser().parseFromString(html, 'text/html');
                        const next = doc.querySelector('#products-table-panel');
                        const current = document.querySelector('#products-table-panel');
                        if (!next || !current) {
                            window.location.href = url;
                            return;
                        }

                        current.innerHTML = next.innerHTML;
                        current.dataset.pageIds = next.dataset.pageIds || '[]';

                        try {
                            this.pageIds = JSON.parse(current.dataset.pageIds || '[]').map(Number);
                        } catch (e) {
                            this.pageIds = [];
                        }
                        this.selected = [];

                        if (window.Alpine?.initTree) {
                            window.Alpine.initTree(current);
                        }

                        if (push) {
                            history.pushState({}, '', url);
                        }

                        this.$nextTick(() => this.syncSelectAll());
                    } catch (e) {
                        window.location.href = url;
                    } finally {
                        this.filtering = false;
                    }
                },
                openSale(product) {
                    this.saleProduct = product;
                    this.saleForm = {
                        discount_type: 'tk',
                        percent: '',
                        sale_price: product.sale_price ?? '',
                        sale_starts_at: product.sale_starts_at || toLocal(now),
                        sale_ends_at: product.sale_ends_at || toLocal(week),
                    };
                    this.saleOpen = true;
                },
                openBrandSale() {
                    this.brandForm = {
                        discount_type: 'percent',
                        percent: '10',
                        amount: '',
                        sale_starts_at: toLocal(now),
                        sale_ends_at: toLocal(week),
                    };
                    this.brandEndOpen = false;
                    this.brandOpen = true;
                },
                openBrandEndSale() {
                    this.brandOpen = false;
                    this.brandEndOpen = true;
                },
                async toggleHomepageFlag(productId, flag, value, checkbox) {
                    const previous = !value;
                    checkbox.disabled = true;
                    try {
                        const token = document.querySelector('meta[name="csrf-token"]')?.content;
                        const url = @js(url('/products'));
                        const res = await fetch(`${url}/${productId}/homepage-flags`, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token || '',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                            body: JSON.stringify({ flag, value: value ? 1 : 0 }),
                        });
                        if (!res.ok) {
                            checkbox.checked = previous;
                            return;
                        }
                    } catch (e) {
                        checkbox.checked = previous;
                    } finally {
                        checkbox.disabled = false;
                    }
                },
            };
        }
    </script>
</x-app-layout>
