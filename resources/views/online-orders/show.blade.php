<x-app-layout>
@php
    $statusClass = \App\Support\OrderStatus::badgeClasses()[$currentStatus] ?? 'bg-slate-100 text-slate-700 border-slate-200';
    $statusLabel = $statusLabels[$currentStatus] ?? ucfirst($currentStatus);
@endphp

<div class="space-y-4">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <a href="{{ route('online-orders.index') }}" class="text-[12px] font-semibold text-indigo-600 hover:text-indigo-700">← Back to online orders</a>
            <div class="mt-1.5 flex flex-wrap items-center gap-2.5">
                <h1 class="text-xl font-extrabold tracking-tight text-slate-900">{{ $order->invoice_no }}</h1>
                <span class="inline-flex rounded-full border px-2.5 py-0.5 text-[11px] font-bold {{ $statusClass }}">{{ $statusLabel }}</span>
            </div>
            <p class="mt-0.5 text-[12px] text-slate-500">Placed {{ $order->created_at->format('d M Y, h:i A') }} · {{ str_replace('_', ' ', ucfirst($order->payment_method)) }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" onclick="window.open('{{ route('orders.invoice', $order->id) }}', 'ReceiptWindow', 'width=400,height=620')"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-2 text-[12px] font-bold text-indigo-700 hover:bg-indigo-600 hover:text-white">
                Print invoice
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-[13px] font-medium text-emerald-700">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-[13px] font-medium text-rose-700">{{ session('error') }}</div>
    @endif

    {{-- Tracking progress --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="mb-3 flex items-center justify-between gap-2">
            <h2 class="text-[14px] font-bold text-slate-900">Order tracking</h2>
            <p class="text-[11px] text-slate-500">Customer sees the same progress in My Account</p>
        </div>

        @if(\App\Support\OrderStatus::isVoid($order->status))
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-[13px] font-semibold text-rose-700">
                {{ $statusLabel }}
                @if($order->statusLogs->first()?->note)
                    <span class="font-normal text-rose-600"> — {{ $order->statusLogs->first()->note }}</span>
                @endif
            </div>
        @else
            <div class="grid grid-cols-2 gap-2 md:grid-cols-5">
                @foreach($timeline as $step)
                    <div class="rounded-xl border px-3 py-3
                        @if($step['active']) border-indigo-300 bg-indigo-50
                        @elseif($step['done']) border-emerald-200 bg-emerald-50/70
                        @else border-slate-100 bg-slate-50 @endif">
                        <div class="mb-2 flex items-center gap-2">
                            <span class="flex h-6 w-6 items-center justify-center rounded-full text-[11px] font-bold
                                @if($step['active']) bg-indigo-600 text-white
                                @elseif($step['done']) bg-emerald-500 text-white
                                @else bg-slate-200 text-slate-500 @endif">
                                @if($step['done'] && ! $step['active'])✓@else{{ $loop->iteration }}@endif
                            </span>
                            <p class="text-[12px] font-bold
                                @if($step['active']) text-indigo-800
                                @elseif($step['done']) text-emerald-800
                                @else text-slate-400 @endif">{{ $step['label'] }}</p>
                        </div>
                        @if($step['at'])
                            <p class="text-[10px] text-slate-500">{{ $step['at'] }}</p>
                        @else
                            <p class="text-[10px] text-slate-400">Waiting…</p>
                        @endif
                        @if(!empty($step['note']))
                            <p class="mt-1 line-clamp-2 text-[11px] text-slate-600">{{ $step['note'] }}</p>
                        @endif
                        @if(!empty($step['courier']))
                            <p class="mt-1 text-[11px] font-semibold text-indigo-700">{{ $step['courier'] }}@if(!empty($step['tracking'])) · {{ $step['tracking'] }}@endif</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="grid gap-4 xl:grid-cols-[minmax(0,1.4fr)_340px]">
        <div class="space-y-4">
            <div class="grid gap-4 md:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <h3 class="text-[13px] font-bold text-slate-900">Customer & delivery</h3>
                    <dl class="mt-3 space-y-2 text-[13px]">
                        <div>
                            <dt class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Name</dt>
                            <dd class="font-semibold text-slate-900">{{ $order->delivery_name ?: ($order->customer->name ?? 'Guest') }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Phone</dt>
                            <dd class="font-medium text-slate-700">{{ $order->delivery_phone ?: ($order->customer->phone ?? 'N/A') }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Address</dt>
                            <dd class="text-slate-600 leading-relaxed">{{ $order->delivery_address ?: ($order->customer->address ?? 'No address') }}</dd>
                        </div>
                        @if($order->customer_note)
                            <div>
                                <dt class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Customer note</dt>
                                <dd class="rounded-lg bg-amber-50 px-2 py-1 text-slate-700">{{ $order->customer_note }}</dd>
                            </div>
                        @endif
                    </dl>

                    <div class="mt-4 border-t border-slate-100 pt-3">
                        <h4 class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Order source</h4>
                        @if($order->landingPage)
                            <p class="mt-2 text-[12px] text-slate-500">Ordered from landing page
                                @can('manage campaigns')
                                    <a href="{{ route('landing-pages.edit', $order->landingPage) }}" class="font-semibold text-indigo-600 hover:underline">{{ $order->landingPage->title }}</a>
                                @else
                                    <span class="font-semibold text-slate-800">{{ $order->landingPage->title }}</span>
                                @endcan
                            </p>
                        @endif
                        @if($order->utm_source || $order->campaign_id)
                            <dl class="mt-2 space-y-1 text-[12px]">
                                @if($order->campaign)
                                    <div class="flex justify-between gap-3">
                                        <dt class="text-slate-500">Campaign</dt>
                                        <dd class="text-right">
                                            @can('manage campaigns')
                                                <a href="{{ route('campaigns.show', $order->campaign) }}" class="font-semibold text-indigo-600 hover:underline">{{ $order->campaign->name }}</a>
                                            @else
                                                <span class="font-semibold text-slate-800">{{ $order->campaign->name }}</span>
                                            @endcan
                                        </dd>
                                    </div>
                                @endif
                                @foreach([
                                    'Platform' => $order->utm_source ? (\App\Models\Campaign::SOURCES[$order->utm_source] ?? \Illuminate\Support\Str::headline($order->utm_source)) : null,
                                    'Type' => $order->utm_medium ? (\App\Models\Campaign::MEDIUMS[$order->utm_medium] ?? \Illuminate\Support\Str::headline($order->utm_medium)) : null,
                                    'Campaign tag' => $order->campaign_id ? null : $order->utm_campaign,
                                    'Placement' => $order->utm_content,
                                    'Landing page' => $order->landing_page,
                                    'Referrer' => $order->referrer_host,
                                ] as $label => $value)
                                    @if($value)
                                        <div class="flex justify-between gap-3">
                                            <dt class="text-slate-500">{{ $label }}</dt>
                                            <dd class="min-w-0 truncate text-right font-medium text-slate-700" title="{{ $value }}">{{ $value }}</dd>
                                        </div>
                                    @endif
                                @endforeach
                            </dl>
                        @else
                            <p class="mt-1 text-[12px] text-slate-500">Direct / unknown</p>
                        @endif
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <h3 class="text-[13px] font-bold text-slate-900">Payment summary</h3>
                    <dl class="mt-3 space-y-2 text-[13px]">
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">Items</dt>
                            <dd class="font-semibold text-slate-800">{{ $order->items->sum('quantity') }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">Subtotal (shop)</dt>
                            <dd class="font-semibold text-slate-800">{{ format_taka($order->shopCollectableAmount(), 'Tk ') }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">Delivery (courier keeps)</dt>
                            <dd class="font-semibold text-slate-800">{{ ($order->delivery_charge ?? 0) > 0 ? 'Tk '.number_format((float) $order->delivery_charge, 2) : 'Free' }}</dd>
                        </div>
                        @if(($order->confirmation_charge ?? 0) > 0)
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-500">Confirmation (store keeps)</dt>
                                <dd class="font-semibold text-emerald-700">{{ format_taka((float) $order->confirmation_charge, 'Tk ') }}</dd>
                            </div>
                        @endif
                        @if(filled($order->payment_reference))
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-500" title="Check this against your bKash/Nagad statement before confirming">Transaction ID</dt>
                                <dd class="font-mono text-[12px] font-bold text-slate-900 select-all">{{ $order->payment_reference }}</dd>
                            </div>
                        @endif
                        <div class="flex justify-between gap-3 border-t border-slate-100 pt-2">
                            <dt class="font-bold text-slate-900">Customer total</dt>
                            <dd class="text-[15px] font-extrabold text-slate-900">{{ format_taka((float) $order->total_amount, 'Tk ') }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-slate-500">Paid (recorded)</dt>
                            <dd class="font-semibold text-slate-800">{{ format_taka((float) $order->paid_amount, 'Tk ') }}</dd>
                        </div>
                        @if(($dueFromCourier ?? 0) > 0.009)
                            <div class="mt-2 rounded-xl border border-sky-200 bg-sky-50 px-3 py-2.5">
                                <p class="text-[10px] font-bold uppercase tracking-wide text-sky-600">Due from courier</p>
                                <p class="text-lg font-black text-sky-900">{{ format_taka($dueFromCourier, 'Tk ') }}</p>
                                <p class="text-[11px] text-sky-700">{{ $order->courierService?->name ?: ($order->shipping_courier ?: 'Courier') }} holds this product COD</p>
                            </div>
                        @elseif($order->courier_collected_at)
                            <div class="mt-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2.5">
                                <p class="text-[10px] font-bold uppercase tracking-wide text-emerald-600">Collected from courier</p>
                                <p class="text-sm font-bold text-emerald-900">{{ format_taka((float) ($order->courier_collected_amount ?? 0), 'Tk ') }}</p>
                                <p class="text-[11px] text-emerald-700">{{ $order->courier_collected_at->format('d M Y, h:i A') }}</p>
                            </div>
                        @endif
                    </dl>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <h3 class="text-[13px] font-bold text-slate-900">Items</h3>
                <div class="mt-3 divide-y divide-slate-100">
                    @foreach($order->items as $item)
                        <div class="flex items-start justify-between gap-4 py-2.5 text-[13px]">
                            <div class="min-w-0">
                                <p class="font-semibold text-slate-900">{{ $item->product->name ?? 'Unknown' }}</p>
                                <p class="text-[11px] text-slate-500">Qty {{ $item->quantity }} × {{ format_taka((float) ($item->unit_price ?? 0), 'Tk ') }}</p>
                            </div>
                            <p class="shrink-0 font-bold text-slate-900">{{ format_taka((float) $item->subtotal, 'Tk ') }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <h3 class="text-[13px] font-bold text-slate-900">Status history</h3>
                <p class="mt-0.5 text-[11px] text-slate-500">Updates shown in the customer account</p>
                <div class="relative mt-4 space-y-0">
                    @forelse($order->statusLogs as $log)
                        <div class="relative flex gap-3 pb-4 last:pb-0">
                            @if(! $loop->last)
                                <span class="absolute left-[7px] top-4 bottom-0 w-px bg-slate-200"></span>
                            @endif
                            <span class="relative z-10 mt-1 h-3.5 w-3.5 shrink-0 rounded-full border-2
                                {{ $loop->first ? 'border-indigo-600 bg-indigo-600' : 'border-slate-300 bg-white' }}"></span>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-baseline justify-between gap-2">
                                    <p class="text-[13px] font-bold text-slate-900">{{ $log->label }}</p>
                                    <p class="text-[11px] text-slate-400">{{ $log->created_at->format('d M Y, h:i A') }}</p>
                                </div>
                                @if($log->from_status || $log->changedBy)
                                    <p class="text-[11px] text-slate-400">
                                        @if($log->from_status){{ \App\Support\OrderStatus::label($log->from_status) }} → {{ \App\Support\OrderStatus::label($log->status) }}@endif
                                        @if($log->changedBy) · by {{ $log->changedBy->name }}@endif
                                    </p>
                                @endif
                                @if($log->note)
                                    <p class="mt-0.5 text-[12px] text-slate-600">{{ $log->note }}</p>
                                @endif
                                @if($log->courier_name)
                                    <p class="mt-1 text-[11px] font-semibold text-indigo-700">
                                        {{ $log->courier_name }}
                                        @if($log->tracking_number)<span class="font-mono text-slate-500"> · {{ $log->tracking_number }}</span>@endif
                                    </p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-[13px] text-slate-500">No status updates logged yet.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="space-y-4 xl:sticky xl:top-24 xl:self-start">
            @if($currentStatus === \App\Support\OrderStatus::NEW)
                <div class="rounded-2xl border border-amber-300 bg-amber-50 p-4 shadow-sm">
                    <h3 class="text-[13px] font-bold text-amber-950">Verify this order</h3>
                    <p class="mt-1 text-[12px] text-amber-800 leading-relaxed">
                        Contact the customer to confirm the items, address and payment before processing.
                    </p>
                    <form method="POST" action="{{ route('online-orders.verify', $order) }}" class="mt-3 space-y-2.5">
                        @csrf
                        <div>
                            <label class="text-[10px] font-bold uppercase tracking-wide text-amber-700">Verified by</label>
                            <select name="verification_method" required class="mt-1 w-full rounded-xl border-amber-200 text-[13px] focus:border-amber-400 focus:ring-amber-400">
                                <option value="">How did you verify?</option>
                                @foreach($verificationMethods as $key => $label)
                                    <option value="{{ $key }}" @selected(old('verification_method') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-bold uppercase tracking-wide text-amber-700">Internal notes</label>
                            <textarea name="verification_notes" rows="2" maxlength="500" placeholder="e.g. Spoke to customer, address confirmed"
                                      class="mt-1 w-full rounded-xl border-amber-200 text-[13px] focus:border-amber-400 focus:ring-amber-400">{{ old('verification_notes') }}</textarea>
                        </div>
                        <button type="submit" class="w-full rounded-xl bg-amber-600 px-4 py-2.5 text-[13px] font-bold text-white hover:bg-amber-700">
                            Mark verified · confirm order
                        </button>
                    </form>
                </div>
            @elseif($order->verified_at)
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                    <p class="text-[11px] font-bold uppercase tracking-wide text-emerald-600">Verified</p>
                    <p class="mt-1 text-[13px] font-semibold text-emerald-950">
                        {{ $verificationMethods[$order->verification_method] ?? ucfirst((string) $order->verification_method) }}
                        · {{ $order->verifier?->name ?? 'Staff' }}
                    </p>
                    <p class="text-[11px] text-emerald-700">{{ $order->verified_at->format('d M Y, h:i A') }}</p>
                    @if($order->verification_notes)
                        <p class="mt-1.5 text-[12px] text-emerald-900">{{ $order->verification_notes }}</p>
                    @endif
                </div>
            @endif

            @if($order->return_requested_at)
                <div class="rounded-2xl border border-yellow-300 bg-yellow-50 p-4">
                    <p class="text-[11px] font-bold uppercase tracking-wide text-yellow-700">Return requested</p>
                    <p class="text-[11px] text-yellow-800">{{ $order->return_requested_at->format('d M Y, h:i A') }}</p>
                    @if($order->return_reason)
                        <p class="mt-1.5 text-[12px] text-yellow-900">{{ $order->return_reason }}</p>
                    @endif
                </div>
            @endif

            @if(($dueFromCourier ?? 0) > 0.009 && in_array($currentStatus, ['shipped', 'delivered'], true))
                <div class="rounded-2xl border border-sky-300 bg-sky-50 p-4 shadow-sm">
                    <h3 class="text-[13px] font-bold text-sky-950">Collect cash from courier</h3>
                    <p class="mt-1 text-[12px] text-sky-800 leading-relaxed">
                        {{ $order->courierService?->name ?: 'Courier' }} should bring you
                        <span class="font-extrabold">{{ format_taka($dueFromCourier, 'Tk ') }}</span>
                        (products). Delivery fee stays with them.
                    </p>
                    <form method="POST" action="{{ route('online-orders.collect-from-courier', $order) }}" class="mt-3"
                          data-confirm="Confirm you received {{ format_taka($dueFromCourier, 'Tk ') }} from the courier?"
                          data-confirm-title="Collect from courier"
                          data-confirm-ok="Yes, cash received">
                        @csrf
                        <button type="submit" class="w-full rounded-xl bg-sky-600 px-4 py-2.5 text-[13px] font-bold text-white hover:bg-sky-700">
                            Cash received · mark completed
                        </button>
                    </form>
                </div>
            @endif

            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <h3 class="text-[13px] font-bold text-slate-900">Update order status</h3>
                <p class="mt-0.5 text-[11px] text-slate-500">Saves instantly to the customer account</p>

                <form method="POST" action="{{ route('online-orders.update-status', $order) }}" class="mt-3 space-y-3" x-data="{ status: @js($currentStatus) }">
                    @csrf
                    <div>
                        <label class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Status</label>
                        <select name="status" x-model="status" class="mt-1 w-full rounded-xl border-slate-200 text-[13px] font-semibold focus:border-indigo-400 focus:ring-indigo-400">
                            @php
                                $statusOptionLabels = [
                                    'new' => 'New — Awaiting verification',
                                    'confirmed' => 'Confirmed — Verified with customer',
                                    'processing' => 'Processing — Picking items',
                                    'packed' => 'Packed — Stock deducted, ready for courier',
                                    'shipped' => 'Shipped — Handed to courier',
                                    'delivered' => 'Delivered — Courier holds COD',
                                    'completed' => 'Completed — Cash received from courier',
                                    'return_requested' => 'Return requested',
                                    'cancelled' => 'Cancelled',
                                    'returned' => 'Returned',
                                    'refunded' => 'Refunded',
                                ];
                                $isVoidOption = fn ($s) => \App\Support\OrderStatus::isVoid($s);
                                $forward = array_values(array_filter($allowedNextStatuses ?? [], fn ($s) => $s !== $currentStatus && ! $isVoidOption($s)));
                                $voids = array_values(array_filter($allowedNextStatuses ?? [], $isVoidOption));
                            @endphp
                            <option value="{{ $currentStatus }}">{{ $statusOptionLabels[$currentStatus] ?? ucfirst($currentStatus) }} (current)</option>
                            @foreach($forward as $s)
                                <option value="{{ $s }}">{{ $statusOptionLabels[$s] ?? ucfirst($s) }}</option>
                            @endforeach
                            @if(count($voids))
                                <option disabled>──────────</option>
                                @foreach($voids as $s)
                                    <option value="{{ $s }}">{{ $statusOptionLabels[$s] ?? ucfirst($s) }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div x-show="status === 'confirmed' && @js(! $order->verified_at)" x-cloak class="space-y-2 rounded-xl border border-amber-200 bg-amber-50/60 p-3">
                        <label class="text-[10px] font-bold uppercase tracking-wide text-amber-700">Verified by *</label>
                        <select name="verification_method" class="w-full rounded-xl border-amber-200 text-[13px]">
                            <option value="">How did you verify?</option>
                            @foreach($verificationMethods as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        <textarea name="verification_notes" rows="2" maxlength="500" placeholder="Internal verification notes"
                                  class="w-full rounded-xl border-amber-200 text-[13px]"></textarea>
                    </div>

                    <div x-show="status === 'return_requested'" x-cloak class="rounded-xl border border-yellow-200 bg-yellow-50/60 p-3">
                        <label class="text-[10px] font-bold uppercase tracking-wide text-yellow-700">Return reason</label>
                        <textarea name="return_reason" rows="2" maxlength="500" placeholder="Why does the customer want to return it?"
                                  class="mt-1 w-full rounded-xl border-yellow-200 text-[13px]"></textarea>
                    </div>

                    <div x-show="['shipped', 'delivered'].includes(status)" x-cloak class="space-y-3 rounded-xl border border-indigo-100 bg-indigo-50/50 p-3">
                        <div>
                            <label class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Courier service *</label>
                            @if(($courierServices ?? collect())->isEmpty())
                                <p class="mt-1 text-[12px] text-rose-600 font-semibold">
                                    No active courier services.
                                    <a href="{{ route('cms.couriers.index') }}" class="underline">Add one in CMS</a>
                                </p>
                            @else
                                <select name="courier_service_id" class="mt-1 w-full rounded-xl border-slate-200 text-[13px] focus:border-indigo-400 focus:ring-indigo-400">
                                    <option value="">Select courier…</option>
                                    @foreach($courierServices as $service)
                                        <option value="{{ $service->id }}" @selected((int) old('courier_service_id', $order->courier_service_id) === (int) $service->id)>
                                            {{ $service->name }}{{ $service->phone ? ' · '.$service->phone : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                        <div>
                            <label class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Tracking number</label>
                            <input type="text" name="tracking_number" value="{{ old('tracking_number', $order->shipping_tracking_no) }}" placeholder="Courier tracking ID"
                                   class="mt-1 w-full rounded-xl border-slate-200 text-[13px] focus:border-indigo-400 focus:ring-indigo-400">
                        </div>
                    </div>

                    <div>
                        <label class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Note to customer</label>
                        <textarea name="customer_note" rows="2" placeholder="Optional message for the customer"
                                  class="mt-1 w-full rounded-xl border-slate-200 text-[13px] focus:border-indigo-400 focus:ring-indigo-400"></textarea>
                    </div>

                    <button type="submit" class="w-full rounded-xl bg-indigo-600 px-4 py-2.5 text-[13px] font-bold text-white hover:bg-indigo-700">
                        Save & notify customer
                    </button>
                </form>
            </div>

            @if($order->courierService || $order->shipping_courier)
                <div class="rounded-2xl border border-indigo-200 bg-indigo-50 p-4">
                    <p class="text-[11px] font-bold uppercase tracking-wide text-indigo-500">Live shipment</p>
                    <p class="mt-1 text-[14px] font-bold text-indigo-950">{{ $order->courierService?->name ?: $order->shipping_courier }}</p>
                    @if($order->shipping_tracking_no)
                        <p class="mt-1 font-mono text-[12px] text-indigo-700">{{ $order->shipping_tracking_no }}</p>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
</x-app-layout>
