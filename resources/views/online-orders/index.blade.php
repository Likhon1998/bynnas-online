<x-app-layout>
    <div class="py-3 sm:py-4"
         x-data="onlineOrdersHub(@js($ordersPayload ?? []), @js($statusFilter ?? 'all'), @js($search ?? ''), @js($filterDate ?? ''))">
        <div class="w-full min-w-0 space-y-3">

            <div class="flex flex-col lg:flex-row justify-between items-stretch lg:items-center gap-3">
                <div class="min-w-0">
                    <h2 class="text-xl sm:text-2xl font-bold text-gray-900 flex items-center gap-2">
                        <svg class="w-5 h-5 sm:w-6 sm:h-6 text-indigo-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m-9 9a9 9 0 019-9"/></svg>
                        Online Orders Hub
                    </h2>
                    <p class="text-sm text-gray-500 mt-0.5">Manage web orders, track courier receivables, and process refunds.</p>
                </div>

                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 bg-white p-2 rounded-xl border border-gray-200 shadow-sm w-full lg:w-auto lg:min-w-[420px]">
                    <div class="relative flex-1 min-w-0">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </div>
                        <input type="text"
                               x-model="search"
                               x-ref="search"
                               placeholder="Search Phone or Invoice..."
                               autocomplete="off"
                               class="pl-9 border-gray-300 rounded-lg shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500 py-1.5 w-full">
                    </div>
                    <div class="hidden sm:block w-px h-6 bg-gray-200 shrink-0"></div>
                    <div class="flex items-center gap-2 shrink-0">
                        <label for="date" class="text-xs font-bold text-gray-500 uppercase tracking-wider whitespace-nowrap">Date:</label>
                        <input type="date" id="date" x-model="date" @change="applyDate()"
                               class="border-gray-300 rounded-lg shadow-sm text-sm font-bold text-indigo-700 focus:ring-indigo-500 focus:border-indigo-500 py-1.5 cursor-pointer w-full sm:w-auto">
                    </div>
                    <div class="flex gap-2 shrink-0" x-show="search || date || status !== 'all'" x-cloak>
                        <button type="button" @click="clearAll()"
                                class="px-3 py-1.5 bg-gray-100 hover:bg-red-100 text-gray-600 hover:text-red-600 text-xs font-bold rounded-lg transition-colors text-center whitespace-nowrap">
                            Clear All
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap gap-1.5">
                <template x-for="tab in statusTabs" :key="tab.key">
                    <button type="button"
                            @click="status = tab.key"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold border"
                            :class="status === tab.key ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'"
                            x-text="tab.label"></button>
                </template>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-2.5 sm:gap-3">
                <div class="bg-white px-3.5 py-3 rounded-xl shadow-sm border border-gray-200 flex items-center justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-0.5">Needs packing</p>
                        <h3 class="text-xl font-black text-gray-900 leading-none">{{ $pendingCount ?? 0 }}</h3>
                    </div>
                    <div class="w-9 h-9 bg-amber-50 text-amber-500 rounded-full flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                </div>
                <div class="bg-white px-3.5 py-3 rounded-xl shadow-sm border border-gray-200 flex items-center justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-0.5">Packing now</p>
                        <h3 class="text-xl font-black text-gray-900 leading-none">{{ $processingCount ?? 0 }}</h3>
                    </div>
                    <div class="w-9 h-9 bg-blue-50 text-blue-500 rounded-full flex items-center justify-center shrink-0 text-base">📦</div>
                </div>
                <div class="bg-blue-50 px-3.5 py-3 rounded-xl shadow-sm border border-blue-200 flex items-center justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold text-blue-600 uppercase tracking-widest mb-0.5">Out for delivery</p>
                        <h3 class="text-xl font-black text-blue-900 leading-none">{{ $shippedCount ?? 0 }}</h3>
                    </div>
                    <div class="w-9 h-9 bg-blue-100 text-blue-500 rounded-full flex items-center justify-center shrink-0 text-base">🚚</div>
                </div>
                <div class="bg-sky-50 px-3.5 py-3 rounded-xl shadow-sm border border-sky-200 flex items-center justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold text-sky-600 uppercase tracking-widest mb-0.5">Due from couriers</p>
                        <h3 class="text-lg sm:text-xl font-black text-sky-900 leading-none truncate">{{ format_taka($courierReceivables ?? 0) }}</h3>
                    </div>
                    <div class="w-9 h-9 bg-sky-100 text-sky-500 rounded-full flex items-center justify-center shrink-0 text-base">💸</div>
                </div>
                <div class="bg-green-50 px-3.5 py-3 rounded-xl shadow-sm border border-green-200 flex items-center justify-between gap-2 col-span-2 md:col-span-1">
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold text-green-600 uppercase tracking-widest mb-0.5">Settled revenue</p>
                        <h3 class="text-lg sm:text-xl font-black text-green-900 leading-none truncate">{{ format_taka($settledRevenue ?? 0) }}</h3>
                    </div>
                    <div class="w-9 h-9 bg-green-100 text-green-500 rounded-full flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
            </div>

            @if(!empty($dueByCourier))
                <div class="rounded-xl border border-sky-200 bg-white shadow-sm overflow-hidden">
                    <div class="px-4 py-2.5 border-b border-sky-100 bg-sky-50/80 flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <p class="text-[12px] font-bold text-sky-900">Cash to collect by courier service</p>
                            <p class="text-[11px] text-sky-700/80">Product COD only — delivery fee stays with the courier</p>
                        </div>
                        <a href="{{ route('cms.couriers.index') }}" class="text-[11px] font-bold text-indigo-600 hover:underline">Manage courier services →</a>
                    </div>
                    <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 divide-y sm:divide-y-0 sm:divide-x divide-slate-100">
                        @foreach($dueByCourier as $row)
                            <div class="px-4 py-3">
                                <p class="text-[13px] font-bold text-slate-900">{{ $row['name'] }}</p>
                                <p class="mt-0.5 text-lg font-black text-sky-800">৳{{ $row['amount_fmt'] }}</p>
                                <p class="text-[11px] text-slate-500">{{ $row['orders'] }} {{ $row['orders'] === 1 ? 'order' : 'orders' }} shipped</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-2.5 rounded-lg shadow-sm text-sm">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-2.5 rounded-lg shadow-sm text-sm">{{ session('error') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm border border-gray-200 rounded-xl min-h-[calc(100vh-22rem)] flex flex-col">
                <div class="overflow-x-auto flex-1">
                    <table class="w-full min-w-[960px] text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b border-gray-200 sticky top-0 z-10">
                            <tr>
                                <th scope="col" class="px-4 py-3 font-bold w-[28%]">Order Details</th>
                                <th scope="col" class="px-4 py-3 font-bold w-[24%]">Customer Info</th>
                                <th scope="col" class="px-4 py-3 font-bold w-[16%]">Product Revenue</th>
                                <th scope="col" class="px-4 py-3 font-bold w-[14%]">Status</th>
                                <th scope="col" class="px-4 py-3 font-bold text-right w-[18%]">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="order in filteredOrders" :key="order.id">
                                <tr class="bg-white border-b hover:bg-gray-50 transition-colors" :class="order.is_voided ? 'bg-red-50/10' : ''">
                                    <td class="px-4 py-3 align-top">
                                        <a :href="order.show_url" class="font-bold text-indigo-600 hover:underline mb-0.5 block" x-text="order.invoice"></a>
                                        <div class="text-xs text-gray-500 mb-1.5" x-text="order.created_at"></div>
                                        <div class="text-xs text-gray-600 space-y-0.5">
                                            <template x-for="(item, idx) in order.items" :key="order.id + '-' + idx">
                                                <div class="truncate max-w-[320px]" :title="item.qty + 'x ' + item.name" x-text="'▪ ' + item.qty + 'x ' + item.name"></div>
                                            </template>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        <div class="font-bold text-gray-900" x-text="order.customer_name"></div>
                                        <div class="text-gray-500" x-text="order.customer_phone"></div>
                                        <div class="text-xs text-gray-400 mt-1 max-w-[280px] truncate" :title="order.customer_address" x-text="order.customer_address"></div>
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        <div class="font-black text-gray-900 text-base">
                                            <span :class="order.is_voided ? 'line-through text-red-500 opacity-60' : ''" x-text="'৳' + order.product_revenue"></span>
                                        </div>
                                        <template x-if="order.delivery_charge > 0">
                                            <div class="text-[10px] text-gray-400 font-bold mb-1">
                                                <span class="text-indigo-500" x-text="'+ ৳' + order.delivery_charge_fmt + ' Courier'"></span> (Excluded)
                                            </div>
                                        </template>
                                        <template x-if="order.delivery_charge <= 0">
                                            <div class="text-[10px] text-gray-500 font-bold mb-1">Free Delivery</div>
                                        </template>
                                        <span class="bg-gray-100 text-gray-600 text-[10px] font-bold px-2 py-0.5 rounded uppercase tracking-wider border border-gray-200" x-text="order.payment_method"></span>
                                    </td>
                                    <td class="px-4 py-3 align-top">
                                        <span class="text-xs font-bold px-2.5 py-1 rounded-full border inline-block"
                                              :class="statusBadgeClass(order.status)"
                                              x-text="statusLabel(order.status)"></span>
                                        <template x-if="order.status === 'shipped' && order.due_from_courier > 0">
                                            <div class="text-[10px] font-bold text-sky-700 mt-1" x-text="'Due from courier ৳' + order.due_from_courier_fmt"></div>
                                        </template>
                                        <template x-if="order.status === 'shipped' && order.shipping_courier">
                                            <div>
                                                <p class="text-[10px] text-purple-700 font-bold mt-1" x-text="order.shipping_courier"></p>
                                                <p class="text-[10px] text-gray-500 font-mono" x-show="order.shipping_tracking_no" x-text="order.shipping_tracking_no"></p>
                                            </div>
                                        </template>
                                    </td>
                                    <td class="px-4 py-3 text-right align-top">
                                        <div class="flex flex-col gap-1.5 justify-end w-full min-w-[120px] max-w-[160px] ml-auto">
                                            <a :href="order.show_url" class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 text-xs font-bold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">
                                                Manage & track
                                            </a>
                                            <button type="button"
                                                    @click="window.open(order.receipt_url, 'ReceiptWindow', 'width=400,height=620')"
                                                    class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 text-xs font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 rounded-lg hover:bg-indigo-600 hover:text-white transition-all shadow-sm">
                                                Print receipt
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="filteredOrders.length === 0">
                                <td colspan="5" class="px-4 py-20 text-center align-middle">
                                    <p class="text-gray-500 font-medium">No online orders match your search.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="px-4 py-2.5 border-t border-gray-100 text-[11px] text-gray-400 bg-gray-50/60" x-show="allOrders.length">
                    Showing <span class="font-semibold text-gray-600" x-text="filteredOrders.length"></span>
                    of <span class="font-semibold text-gray-600" x-text="allOrders.length"></span> loaded orders
                    <span x-show="search"> · filtered instantly</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        function onlineOrdersHub(orders, initialStatus, initialSearch, initialDate) {
            return {
                allOrders: orders || [],
                search: initialSearch || '',
                status: initialStatus && initialStatus !== '' ? initialStatus : 'all',
                date: initialDate || '',
                statusTabs: [
                    { key: 'all', label: 'All' },
                    { key: 'pending', label: 'Pending' },
                    { key: 'processing', label: 'Packing' },
                    { key: 'shipped', label: 'Shipped' },
                    { key: 'completed', label: 'Delivered' },
                ],
                get filteredOrders() {
                    const q = (this.search || '').trim().toLowerCase();
                    return this.allOrders.filter((order) => {
                        if (this.status === 'pending') {
                            if (!['pending', 'pending_fulfillment'].includes(order.status)) return false;
                        } else if (this.status !== 'all' && order.status !== this.status) {
                            return false;
                        }
                        if (!q) return true;
                        return (order.search_blob || '').includes(q);
                    });
                },
                statusLabel(status) {
                    const map = {
                        pending: 'Pending',
                        pending_fulfillment: 'Pending',
                        processing: 'Processing',
                        shipped: 'Shipped',
                        completed: 'Completed',
                        cancelled: 'Cancelled',
                        returned: 'Returned',
                        refunded: 'Refunded',
                    };
                    return map[status] || (status ? status.charAt(0).toUpperCase() + status.slice(1) : '');
                },
                statusBadgeClass(status) {
                    const map = {
                        pending: 'bg-amber-100 text-amber-800 border-amber-200',
                        pending_fulfillment: 'bg-amber-100 text-amber-800 border-amber-200',
                        processing: 'bg-blue-100 text-blue-800 border-blue-200',
                        shipped: 'bg-purple-100 text-purple-800 border-purple-200',
                        completed: 'bg-emerald-100 text-emerald-800 border-emerald-200',
                        cancelled: 'bg-orange-100 text-orange-800 border-orange-300 line-through decoration-orange-600 decoration-2 font-black',
                        returned: 'bg-red-100 text-red-800 border-red-300 line-through decoration-red-600 decoration-2 font-black',
                        refunded: 'bg-rose-100 text-rose-800 border-rose-300 line-through decoration-rose-600 decoration-2 font-black',
                    };
                    return map[status] || 'bg-gray-100 text-gray-800 border-gray-200';
                },
                applyDate() {
                    const params = new URLSearchParams();
                    if (this.date) params.set('date', this.date);
                    if (this.search) params.set('search', this.search);
                    if (this.status && this.status !== 'all') params.set('status', this.status);
                    window.location.href = @json(route('online-orders.index')) + (params.toString() ? ('?' + params.toString()) : '');
                },
                clearAll() {
                    window.location.href = @json(route('online-orders.index'));
                },
            };
        }
    </script>
</x-app-layout>
