<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center gap-3 flex-wrap"
             x-data
             x-bind:data-channel="document.getElementById('sales-ledger-root')?.__x?.$data?.channel">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Sales Ledger') }}</h2>
            <div id="sales-header-actions" class="flex items-center gap-2"></div>
        </div>
    </x-slot>

    <div class="py-4"
         id="sales-ledger-root"
         x-data="salesLedger(@js([
             'channel' => $channel ?? 'physical',
             'canViewOnline' => (bool) ($canViewOnline ?? false),
             'physical' => $physicalPayload ?? [],
             'online' => $onlinePayload ?? [],
             'physicalStats' => $physicalStats ?? ['cancelled' => 0, 'returned' => 0, 'refunded' => 0],
             'onlineStats' => $onlineStats ?? [],
             'physicalCount' => $physicalCount ?? 0,
             'onlineCount' => $onlineCount ?? 0,
             'onlineHubUrl' => route('online-orders.index'),
             'posUrl' => route('pos.index'),
             'csrf' => $csrfToken ?? csrf_token(),
         ]))"
         x-cloak>
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" class="bg-green-500 text-white p-3 rounded-lg shadow-sm font-bold flex justify-between items-center text-sm">
                    {{ session('success') }}
                    <button @click="show = false" class="text-white hover:text-green-200 text-xl leading-none">&times;</button>
                </div>
            @endif
            @if (session('error'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 8000)" class="bg-red-500 text-white p-3 rounded-lg shadow-sm font-bold flex justify-between items-center text-sm">
                    {{ session('error') }}
                    <button @click="show = false" class="text-white hover:text-red-200 text-xl leading-none">&times;</button>
                </div>
            @endif

            {{-- Instant tabs --}}
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-1.5 inline-flex gap-1 w-full sm:w-auto">
                <button type="button" @click="setChannel('physical')"
                        class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-sm font-bold transition"
                        :class="channel === 'physical' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-50'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    Physical (POS)
                    <span class="text-[10px] font-black px-1.5 py-0.5 rounded-full"
                          :class="channel === 'physical' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-500'"
                          x-text="physicalCount"></span>
                </button>
                <button type="button" @click="setChannel('online')"
                        x-show="canViewOnline"
                        class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-sm font-bold transition"
                        :class="channel === 'online' ? 'bg-indigo-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-50'">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                    Online Orders
                    <span class="text-[10px] font-black px-1.5 py-0.5 rounded-full"
                          :class="channel === 'online' ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-500'"
                          x-text="onlineCount"></span>
                </button>
            </div>

            {{-- Physical stats --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4" x-show="channel === 'physical'">
                <div class="bg-orange-50 border border-orange-200 p-4 rounded-xl flex justify-between items-center shadow-sm">
                    <div>
                        <p class="text-[10px] font-black text-orange-600 uppercase tracking-widest">Cancelled Orders</p>
                        <h3 class="text-2xl font-black text-orange-900" x-text="physicalStats.cancelled"></h3>
                    </div>
                    <div class="w-10 h-10 bg-orange-100 text-orange-500 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                    </div>
                </div>
                <div class="bg-red-50 border border-red-200 p-4 rounded-xl flex justify-between items-center shadow-sm">
                    <div>
                        <p class="text-[10px] font-black text-red-600 uppercase tracking-widest">Returned Orders</p>
                        <h3 class="text-2xl font-black text-red-900" x-text="physicalStats.returned"></h3>
                    </div>
                    <div class="w-10 h-10 bg-red-100 text-red-500 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 15v-1a4 4 0 00-4-4H8m0 0l3 3m-3-3l3-3m9 14V5a2 2 0 00-2-2H6a2 2 0 00-2 2v16l4-2 4 2 4-2 4 2z"/></svg>
                    </div>
                </div>
                <div class="bg-rose-50 border border-rose-200 p-4 rounded-xl flex justify-between items-center shadow-sm">
                    <div>
                        <p class="text-[10px] font-black text-rose-600 uppercase tracking-widest">Refunded Orders</p>
                        <h3 class="text-2xl font-black text-rose-900" x-text="physicalStats.refunded"></h3>
                    </div>
                    <div class="w-10 h-10 bg-rose-100 text-rose-500 rounded-full flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                    </div>
                </div>
            </div>

            {{-- Online stats + status chips --}}
            <div x-show="channel === 'online'" class="space-y-3">
                <div class="grid grid-cols-2 xl:grid-cols-5 gap-3">
                    <div class="bg-amber-50 border border-amber-200 p-4 rounded-xl shadow-sm">
                        <p class="text-[10px] font-black text-amber-600 uppercase tracking-widest">Needs packing</p>
                        <h3 class="text-2xl font-black text-amber-900 mt-1" x-text="onlineStats.pending"></h3>
                    </div>
                    <div class="bg-blue-50 border border-blue-200 p-4 rounded-xl shadow-sm">
                        <p class="text-[10px] font-black text-blue-600 uppercase tracking-widest">Packing now</p>
                        <h3 class="text-2xl font-black text-blue-900 mt-1" x-text="onlineStats.processing"></h3>
                    </div>
                    <div class="bg-purple-50 border border-purple-200 p-4 rounded-xl shadow-sm">
                        <p class="text-[10px] font-black text-purple-600 uppercase tracking-widest">Out for delivery</p>
                        <h3 class="text-2xl font-black text-purple-900 mt-1" x-text="onlineStats.shipped"></h3>
                    </div>
                    <div class="bg-emerald-50 border border-emerald-200 p-4 rounded-xl shadow-sm">
                        <p class="text-[10px] font-black text-emerald-600 uppercase tracking-widest">Delivered</p>
                        <h3 class="text-2xl font-black text-emerald-900 mt-1" x-text="onlineStats.completed"></h3>
                    </div>
                    <div class="bg-sky-50 border border-sky-200 p-4 rounded-xl shadow-sm col-span-2 xl:col-span-1">
                        <p class="text-[10px] font-black text-sky-600 uppercase tracking-widest">COD with courier</p>
                        <h3 class="text-xl font-black text-sky-900 mt-1" x-text="'৳' + Math.round(Number(onlineStats.cod_outstanding) || 0).toLocaleString()"></h3>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <template x-for="tab in statusTabs" :key="tab.key">
                        <button type="button" @click="status = tab.key"
                                class="px-3 py-1.5 rounded-lg text-xs font-bold border"
                                :class="status === tab.key ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'"
                                x-text="tab.label"></button>
                    </template>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-100">
                <div class="p-3 border-b border-gray-100 bg-slate-50 flex flex-col md:flex-row justify-between items-center gap-3">
                    <h3 class="font-bold text-gray-700 text-sm" x-text="channel === 'online' ? 'Online Order History' : 'POS Transaction History'"></h3>
                    <div class="flex w-full md:w-auto gap-2">
                        <div class="relative flex-1 md:flex-none">
                            <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none">
                                <svg class="h-3.5 w-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </div>
                            <input type="text" x-model="search" x-ref="search"
                                   :placeholder="channel === 'online' ? 'Invoice, phone, tracking…' : 'Invoice or Mobile...'"
                                   autocomplete="off"
                                   class="pl-8 w-full md:w-56 border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 text-sm py-1.5">
                        </div>
                        <button type="button" x-show="search || (channel === 'online' && status !== 'all')" @click="search = ''; status = 'all'"
                                class="px-3 py-1.5 bg-gray-100 text-gray-600 rounded-lg text-xs font-bold hover:bg-gray-200 border border-gray-200">Clear</button>
                    </div>
                </div>

                {{-- PHYSICAL TABLE --}}
                <div class="overflow-x-auto" x-show="channel === 'physical'">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider">Date & Time</th>
                                <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider">Invoice No</th>
                                <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider">Customer Details</th>
                                <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider">Product Revenue</th>
                                <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider">Status/Payment</th>
                                <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider">Cashier</th>
                                <th class="px-4 py-2.5 text-right text-[11px] font-bold text-gray-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <template x-for="order in filteredPhysical" :key="'p-' + order.id">
                                <tr class="hover:bg-gray-50 transition" :class="order.is_voided ? 'bg-red-50/30' : ''">
                                    <td class="px-4 py-2.5 whitespace-nowrap text-xs text-gray-500 font-medium" x-text="order.created_at"></td>
                                    <td class="px-4 py-2.5 whitespace-nowrap text-sm font-black tracking-tight" :class="order.is_voided ? 'text-red-500 line-through opacity-70' : 'text-indigo-600'">
                                        <span x-text="order.invoice"></span>
                                        <template x-if="order.is_exchange_receipt">
                                            <br><span class="text-[9px] text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded uppercase tracking-widest mt-1 inline-block">Exchange Receipt</span>
                                        </template>
                                    </td>
                                    <td class="px-4 py-2.5 whitespace-nowrap">
                                        <template x-if="order.customer_name">
                                            <div>
                                                <div class="text-xs font-bold text-gray-900" x-text="order.customer_name"></div>
                                                <div class="text-[11px] text-gray-500 mt-0.5 font-mono" x-text="order.customer_phone"></div>
                                            </div>
                                        </template>
                                        <template x-if="!order.customer_name">
                                            <span class="text-xs text-gray-400 italic font-medium">Guest Customer</span>
                                        </template>
                                    </td>
                                    <td class="px-4 py-2.5 whitespace-nowrap">
                                        <div class="text-sm font-black tracking-tight" :class="order.is_voided ? 'text-red-500 line-through opacity-70' : 'text-gray-900'">
                                            <span class="text-gray-400 text-xs mr-0.5">৳</span><span x-text="order.product_revenue"></span>
                                        </div>
                                        <template x-if="order.discount_amount > 0">
                                            <div class="text-[9px] text-amber-600 font-bold mt-0.5" x-text="'− ৳' + order.discount_amount_fmt + ' discount'"></div>
                                        </template>
                                        <template x-if="order.credit_amount > 0">
                                            <div class="text-[9px] text-orange-700 font-bold mt-0.5" x-text="'BAKI ৳' + order.credit_amount_fmt + ' due'"></div>
                                        </template>
                                        <template x-if="order.delivery_charge > 0">
                                            <div class="text-[9px] text-indigo-500 font-bold mt-0.5" x-text="'+ ৳' + order.delivery_charge_fmt + ' Courier'"></div>
                                        </template>
                                    </td>
                                    <td class="px-4 py-2.5 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider border"
                                              :class="physicalBadgeClass(order)"
                                              x-text="physicalBadgeLabel(order)"></span>
                                    </td>
                                    <td class="px-4 py-2.5 whitespace-nowrap text-xs text-gray-500 font-medium" x-text="order.cashier"></td>
                                    <td class="px-4 py-2.5 whitespace-nowrap text-right">
                                        <div class="inline-flex justify-end gap-2">
                                            <button type="button" @click="window.open(order.receipt_url, 'ReceiptWindow', 'width=400,height=600')"
                                                    class="inline-flex items-center gap-1.5 text-indigo-600 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 border border-indigo-100 px-2.5 py-1 rounded text-xs font-bold">Receipt</button>
                                            <template x-if="order.can_return">
                                                <button type="button" @click="openReturnModal(order)"
                                                        class="inline-flex items-center gap-1.5 text-amber-600 hover:text-amber-900 bg-amber-50 hover:bg-amber-100 border border-amber-100 px-2.5 py-1 rounded text-xs font-bold">Return / Exchange</button>
                                            </template>
                                            <template x-if="order.is_exchange_receipt && !order.is_voided">
                                                <span class="inline-flex items-center gap-1 text-gray-400 bg-gray-50 border border-gray-100 px-2.5 py-1 rounded text-xs font-bold">Final Sale</span>
                                            </template>
                                            <template x-if="order.return_expired">
                                                <span class="inline-flex items-center gap-1 text-gray-400 bg-gray-50 border border-gray-100 px-2.5 py-1 rounded text-xs font-bold">Expired</span>
                                            </template>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="filteredPhysical.length === 0">
                                <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-500">No POS sales found matching your criteria.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- ONLINE TABLE --}}
                <div class="overflow-x-auto" x-show="channel === 'online'">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider">Order</th>
                                <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider">Customer</th>
                                <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider">Revenue</th>
                                <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider">Fulfillment</th>
                                <th class="px-4 py-2.5 text-left text-[11px] font-bold text-gray-500 uppercase tracking-wider">Payment</th>
                                <th class="px-4 py-2.5 text-right text-[11px] font-bold text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <template x-for="order in filteredOnline" :key="'o-' + order.id">
                                <tr class="hover:bg-gray-50 transition" :class="order.is_voided ? 'bg-red-50/30' : ''">
                                    <td class="px-4 py-2.5 whitespace-nowrap text-xs text-gray-500 font-medium" x-text="order.created_at"></td>
                                    <td class="px-4 py-2.5">
                                        <a :href="order.show_url" class="text-sm font-black tracking-tight" :class="order.is_voided ? 'text-red-500 line-through opacity-70' : 'text-indigo-600 hover:underline'" x-text="order.invoice"></a>
                                        <div class="mt-1 text-[11px] text-gray-500 space-y-0.5">
                                            <template x-for="(item, idx) in order.items.slice(0, 2)" :key="order.id + '-i-' + idx">
                                                <div class="truncate max-w-[200px]" x-text="'▪ ' + item.qty + '× ' + item.name"></div>
                                            </template>
                                            <div class="text-gray-400" x-show="order.items.length > 2" x-text="'+' + (order.items.length - 2) + ' more'"></div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <div class="text-xs font-bold text-gray-900" x-text="order.customer_name"></div>
                                        <div class="text-[11px] text-gray-500 mt-0.5 font-mono" x-text="order.customer_phone"></div>
                                        <div class="text-[10px] text-gray-400 mt-1 max-w-[180px] truncate" x-show="order.customer_address" :title="order.customer_address" x-text="order.customer_address"></div>
                                    </td>
                                    <td class="px-4 py-2.5 whitespace-nowrap">
                                        <div class="text-sm font-black" :class="order.is_voided ? 'text-red-500 line-through opacity-70' : 'text-gray-900'" x-text="'৳' + order.product_revenue"></div>
                                        <template x-if="order.discount_amount > 0">
                                            <div class="text-[9px] text-amber-600 font-bold mt-0.5" x-text="'− ৳' + order.discount_amount_fmt + ' discount'"></div>
                                        </template>
                                        <div class="text-[9px] font-bold mt-0.5" :class="order.delivery_charge > 0 ? 'text-indigo-500' : 'text-gray-400'"
                                             x-text="order.delivery_charge > 0 ? ('+ ৳' + order.delivery_charge_fmt + ' delivery') : 'Free delivery'"></div>
                                    </td>
                                    <td class="px-4 py-2.5 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold border" :class="onlineStatusClass(order.status)" x-text="onlineStatusLabel(order.status)"></span>
                                        <p class="text-[10px] text-purple-700 font-bold mt-1" x-show="order.status === 'shipped' && order.shipping_courier" x-text="order.shipping_courier"></p>
                                        <p class="text-[10px] text-gray-500 font-mono" x-show="order.shipping_tracking_no" x-text="order.shipping_tracking_no"></p>
                                    </td>
                                    <td class="px-4 py-2.5 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-gray-100 text-gray-700 border border-gray-200" x-text="order.payment_method"></span>
                                    </td>
                                    <td class="px-4 py-2.5 whitespace-nowrap text-right">
                                        <div class="inline-flex flex-col sm:flex-row gap-1.5 justify-end">
                                            <a :href="order.show_url" class="inline-flex items-center justify-center text-white bg-indigo-600 hover:bg-indigo-700 px-2.5 py-1 rounded text-xs font-bold">Manage & track</a>
                                            <button type="button" @click="window.open(order.receipt_url, 'ReceiptWindow', 'width=400,height=600')"
                                                    class="inline-flex items-center justify-center text-indigo-600 bg-indigo-50 hover:bg-indigo-100 border border-indigo-100 px-2.5 py-1 rounded text-xs font-bold">Receipt</button>
                                            <template x-if="order.can_mark_returned">
                                                <form method="POST" :action="order.return_url"
                                                      :data-confirm="'Mark ' + order.invoice + ' as Returned? Stock will come back. No cash refund (COD not collected).'"
                                                      data-confirm-title="Mark returned?"
                                                      data-confirm-ok="Mark returned"
                                                      data-confirm-tone="warning">
                                                    <input type="hidden" name="_token" :value="csrf">
                                                    <button type="submit" class="inline-flex w-full items-center justify-center text-amber-700 bg-amber-50 hover:bg-amber-100 border border-amber-200 px-2.5 py-1 rounded text-xs font-bold">Returned</button>
                                                </form>
                                            </template>
                                            <template x-if="order.can_refund">
                                                <form method="POST" :action="order.refund_url"
                                                      :data-confirm="'Refund ' + order.invoice + '? Money was collected — cash will be reversed and stock restored.'"
                                                      data-confirm-title="Refund?"
                                                      data-confirm-ok="Refund"
                                                      data-confirm-tone="danger">
                                                    <input type="hidden" name="_token" :value="csrf">
                                                    <button type="submit" class="inline-flex w-full items-center justify-center text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-100 px-2.5 py-1 rounded text-xs font-bold">Refund</button>
                                                </form>
                                            </template>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="filteredOnline.length === 0">
                                <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-500">No online orders found matching your criteria.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="px-4 py-2.5 border-t border-gray-50 text-[11px] text-gray-400"
                     x-text="'Showing ' + (channel === 'online' ? filteredOnline.length : filteredPhysical.length) + ' of ' + (channel === 'online' ? online.length : physical.length) + ' loaded · instant filter'">
                </div>
            </div>
        </div>

        {{-- Return / Exchange modal (physical only) --}}
        <div x-show="modalOpen" x-cloak
             class="fixed inset-0 z-50 bg-slate-900/80 backdrop-blur-sm flex items-center justify-center p-4"
             @keydown.escape.window="closeReturnModal()">
            <div class="bg-white rounded-[24px] shadow-2xl w-full max-w-2xl overflow-hidden flex flex-col max-h-[90vh]" @click.outside="closeReturnModal()">
                <div class="bg-slate-900 p-6 flex flex-col gap-5 text-white">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-2xl font-black tracking-tight">Process Transaction</h3>
                            <p class="text-slate-400 text-sm mt-1">Modifying Order #<span class="font-bold text-indigo-400" x-text="modalOrderId"></span></p>
                        </div>
                        <button type="button" @click="closeReturnModal()" class="w-8 h-8 bg-white/10 hover:bg-rose-500 rounded-full flex items-center justify-center">&times;</button>
                    </div>
                    <div class="flex gap-2 bg-slate-800 p-1.5 rounded-xl border border-white/5">
                        <button type="button" @click="modalTab = 'refund'" class="flex-1 py-2 text-sm font-black rounded-lg transition-all"
                                :class="modalTab === 'refund' ? 'bg-rose-500 text-white shadow-sm' : 'text-slate-400 hover:text-white'">Full Refund</button>
                        <button type="button" @click="modalTab = 'exchange'" class="flex-1 py-2 text-sm font-black rounded-lg transition-all"
                                :class="modalTab === 'exchange' ? 'bg-indigo-500 text-white shadow-sm' : 'text-slate-400 hover:text-white'">Product Exchange</button>
                    </div>
                </div>
                <div class="p-8 overflow-y-auto bg-slate-50 flex-1" x-show="modalTab === 'refund'">
                    <div class="bg-rose-100/50 border border-rose-200 p-5 rounded-2xl mb-6">
                        <p class="text-sm font-black text-rose-900">Warning: Destructive Action</p>
                        <p class="text-xs text-rose-700 mt-1 font-medium">This will refund the entire order and restore stock. Cannot be undone.</p>
                    </div>
                    <form method="POST" :action="'/sales/' + modalOrderId + '/refund'">
                        <input type="hidden" name="_token" :value="csrf">
                        <div class="flex justify-end gap-3">
                            <button type="button" @click="closeReturnModal()" class="px-6 py-3 bg-white border border-slate-200 text-slate-600 font-bold rounded-xl">Cancel</button>
                            <button type="submit" class="px-8 py-3 bg-rose-600 text-white font-black rounded-xl">Confirm Full Refund</button>
                        </div>
                    </form>
                </div>
                <div class="p-8 overflow-y-auto bg-slate-50 flex-1" x-show="modalTab === 'exchange'">
                    <div class="mb-6 bg-indigo-100/50 border border-indigo-200 p-4 rounded-2xl">
                        <p class="text-sm font-black text-indigo-900">Exchange via POS Terminal</p>
                        <p class="text-xs text-indigo-700 mt-1 font-medium">Select the item to return, then continue on POS with credit applied.</p>
                    </div>
                    <form method="POST" :action="'/orders/' + modalOrderId + '/exchange'">
                        <input type="hidden" name="_token" :value="csrf">
                        <div class="bg-white p-5 rounded-2xl border border-slate-200 mb-6 space-y-3">
                            <label class="block text-xs font-bold text-slate-500">Select Item to Return</label>
                            <select name="return_product_id" x-model="exchangeProductId" required class="w-full bg-slate-50 border border-slate-200 text-sm font-bold rounded-xl p-2.5">
                                <option value="">-- Choose Purchased Item --</option>
                                <template x-for="item in modalItems" :key="item.id + '-' + item.name">
                                    <option :value="item.id" x-text="'📦 ' + item.name + ' (৳' + item.price + ' - Bought: ' + item.qty + ')'"></option>
                                </template>
                            </select>
                            <label class="block text-xs font-bold text-slate-500">Quantity Returned</label>
                            <input type="number" name="return_qty" x-model.number="exchangeQty" min="1" :max="exchangeMaxQty" required class="w-full bg-slate-50 border border-slate-200 text-sm font-black rounded-xl p-2.5">
                            <p class="text-[10px] text-slate-400" x-text="exchangeMaxQty ? ('Maximum quantity allowed: ' + exchangeMaxQty) : 'Select an item to see purchased quantity.'"></p>
                        </div>
                        <div class="flex justify-end gap-3">
                            <button type="button" @click="closeReturnModal()" class="px-6 py-3 bg-white border border-slate-200 text-slate-600 font-bold rounded-xl">Cancel</button>
                            <button type="submit" class="px-8 py-3 bg-indigo-600 text-white font-black rounded-xl">Proceed to POS</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function salesLedger(cfg) {
            return {
                channel: cfg.channel || 'physical',
                canViewOnline: !!cfg.canViewOnline,
                physical: cfg.physical || [],
                online: cfg.online || [],
                physicalStats: cfg.physicalStats || {},
                onlineStats: cfg.onlineStats || {},
                physicalCount: cfg.physicalCount || 0,
                onlineCount: cfg.onlineCount || 0,
                onlineHubUrl: cfg.onlineHubUrl,
                posUrl: cfg.posUrl,
                csrf: cfg.csrf,
                search: '',
                status: 'all',
                statusTabs: [
                    { key: 'all', label: 'All' },
                    { key: 'pending', label: 'Pending' },
                    { key: 'processing', label: 'Packing' },
                    { key: 'shipped', label: 'Shipped' },
                    { key: 'completed', label: 'Delivered' },
                    { key: 'cancelled', label: 'Cancelled' },
                    { key: 'returned', label: 'Returned' },
                    { key: 'refunded', label: 'Refunded' },
                ],
                modalOpen: false,
                modalTab: 'refund',
                modalOrderId: null,
                modalItems: [],
                exchangeProductId: '',
                exchangeQty: 1,
                get exchangeMaxQty() {
                    const item = this.modalItems.find(i => String(i.id) === String(this.exchangeProductId));
                    return item ? item.qty : null;
                },
                get filteredPhysical() {
                    const q = (this.search || '').trim().toLowerCase();
                    return this.physical.filter(o => !q || (o.search_blob || '').includes(q));
                },
                get filteredOnline() {
                    const q = (this.search || '').trim().toLowerCase();
                    return this.online.filter(o => {
                        if (this.status !== 'all' && o.status !== this.status) return false;
                        if (!q) return true;
                        return (o.search_blob || '').includes(q);
                    });
                },
                setChannel(ch) {
                    if (ch === 'online' && !this.canViewOnline) {
                        return;
                    }
                    this.channel = ch;
                    this.search = '';
                    this.status = 'all';
                    const url = new URL(window.location.href);
                    url.searchParams.set('channel', ch);
                    url.searchParams.delete('search');
                    url.searchParams.delete('status');
                    history.replaceState({}, '', url);
                    this.syncHeaderAction();
                },
                syncHeaderAction() {
                    const slot = document.getElementById('sales-header-actions');
                    if (!slot) return;
                    if (this.channel === 'online' && this.canViewOnline) {
                        slot.innerHTML = `<a href="${this.onlineHubUrl}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs py-2 px-4 rounded-lg shadow-sm transition-all inline-flex items-center gap-2">Open Online Orders Hub</a>`;
                    } else {
                        slot.innerHTML = `<a href="${this.posUrl}" onclick="return window.launchPosTerminal(this.href)" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs py-2 px-4 rounded-lg shadow-sm transition-all inline-flex items-center gap-2">Open POS Terminal</a>`;
                    }
                },
                init() {
                    this.syncHeaderAction();
                },
                physicalBadgeLabel(order) {
                    if (order.status === 'refunded') return 'Refunded';
                    if (order.status === 'returned') return 'Returned';
                    if (order.status === 'cancelled') return 'Cancelled';
                    const m = (order.payment_method || '').toLowerCase();
                    if (m === 'cash') return 'Cash';
                    if (m === 'card') return 'Card';
                    if (m === 'bkash') return 'bKash';
                    return order.payment_method || 'Paid';
                },
                physicalBadgeClass(order) {
                    if (order.status === 'refunded') return 'bg-rose-100 text-rose-800 border-rose-300 line-through decoration-rose-600 decoration-2 font-black';
                    if (order.status === 'returned') return 'bg-red-100 text-red-800 border-red-300 line-through decoration-red-600 decoration-2 font-black';
                    if (order.status === 'cancelled') return 'bg-orange-100 text-orange-800 border-orange-300 line-through decoration-orange-600 decoration-2 font-black';
                    const m = (order.payment_method || '').toLowerCase();
                    if (m === 'cash') return 'bg-green-100 text-green-800 border-green-200';
                    if (m === 'card') return 'bg-blue-100 text-blue-800 border-blue-200';
                    if (m === 'bkash') return 'bg-pink-100 text-pink-800 border-pink-200';
                    return 'bg-gray-100 text-gray-800 border-gray-200';
                },
                onlineStatusLabel(status) {
                    const map = { pending: 'Pending', processing: 'Packing', shipped: 'Shipped', completed: 'Delivered', cancelled: 'Cancelled', refunded: 'Refunded', returned: 'Returned' };
                    return map[status] || status;
                },
                onlineStatusClass(status) {
                    const map = {
                        pending: 'bg-amber-100 text-amber-800 border-amber-200',
                        processing: 'bg-blue-100 text-blue-800 border-blue-200',
                        shipped: 'bg-purple-100 text-purple-800 border-purple-200',
                        completed: 'bg-emerald-100 text-emerald-800 border-emerald-200',
                        cancelled: 'bg-orange-100 text-orange-800 border-orange-300 line-through font-black',
                        refunded: 'bg-rose-100 text-rose-800 border-rose-300 line-through font-black',
                        returned: 'bg-red-100 text-red-800 border-red-300 line-through font-black',
                    };
                    return map[status] || 'bg-gray-100 text-gray-800 border-gray-200';
                },
                openReturnModal(order) {
                    this.modalOrderId = order.id;
                    this.modalItems = order.items || [];
                    this.exchangeProductId = '';
                    this.exchangeQty = 1;
                    this.modalTab = 'refund';
                    this.modalOpen = true;
                },
                closeReturnModal() {
                    this.modalOpen = false;
                    this.modalOrderId = null;
                    this.modalItems = [];
                },
            };
        }
    </script>
</x-app-layout>
