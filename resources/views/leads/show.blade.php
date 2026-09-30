<x-app-layout>
@php
    $input = 'mt-1 block w-full rounded-lg border-slate-200 text-[12px] focus:border-indigo-400 focus:ring-indigo-200';
    $label = 'block text-[10px] font-bold uppercase tracking-wide text-slate-500';
    $productOptions = $products->map(fn ($p) => [
        'id' => $p->id,
        'name' => $p->storefrontDisplayName(),
        'price' => (float) $p->currentPrice(),
        'stock' => $p->availableStock(),
    ])->values();
    $zones = collect($deliveryConfig['zones'] ?? []);
    $orderStatusLabels = \App\Support\OrderStatus::labels();
@endphp

<div class="space-y-4 text-[12px] text-slate-700">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <a href="{{ route('leads.index') }}" class="text-[12px] font-semibold text-indigo-600 hover:text-indigo-700">← Back to leads</a>
            <div class="mt-1.5 flex flex-wrap items-center gap-2.5">
                <h1 class="text-xl font-extrabold tracking-tight text-slate-900">{{ $lead->name }}</h1>
                <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ \App\Models\Lead::statusBadge($lead->status) }}">{{ $lead->statusLabel() }}</span>
                @if($lead->isFollowUpDue())
                    <span class="inline-flex rounded-full bg-rose-100 px-2.5 py-0.5 text-[11px] font-bold text-rose-700">Follow-up due</span>
                @endif
            </div>
            <p class="mt-0.5 text-[12px] text-slate-500">
                {{ $lead->sourceLabel() }} · added {{ $lead->created_at->format('d M Y, h:i A') }}{{ $lead->creator ? ' by '.$lead->creator->name : '' }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('leads.edit', $lead) }}" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-[12px] font-bold text-slate-700 hover:bg-slate-50">Edit</a>
            @if(auth()->user()->isAdminUser())
                <form method="POST" action="{{ route('leads.destroy', $lead) }}" onsubmit="return confirm('Delete this lead and its activity log?')">
                    @csrf @method('DELETE')
                    <button class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-[12px] font-bold text-rose-700 hover:bg-rose-100">Delete</button>
                </form>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-[13px] font-medium text-emerald-700">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-[13px] font-medium text-rose-700">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-[12px] text-rose-700">
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            {{-- Details --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="mb-3 text-[14px] font-bold text-slate-900">Inquiry</h2>
                <dl class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <div><dt class="{{ $label }}">Phone</dt><dd class="mt-0.5 text-[13px] font-semibold">{{ $lead->phone ?: '—' }}</dd></div>
                    <div><dt class="{{ $label }}">Email</dt><dd class="mt-0.5 text-[13px]">{{ $lead->email ?: '—' }}</dd></div>
                    <div class="md:col-span-2"><dt class="{{ $label }}">Conversation</dt>
                        <dd class="mt-0.5 break-all text-[13px]">
                            @if($lead->conversation_ref && filter_var($lead->conversation_ref, FILTER_VALIDATE_URL))
                                <a href="{{ $lead->conversation_ref }}" target="_blank" rel="noopener noreferrer" class="text-indigo-600 hover:underline">{{ $lead->conversation_ref }}</a>
                            @else
                                {{ $lead->conversation_ref ?: '—' }}
                            @endif
                        </dd>
                    </div>
                    <div><dt class="{{ $label }}">Product</dt><dd class="mt-0.5 text-[13px]">{{ $lead->product?->storefrontDisplayName() ?? '—' }}</dd></div>
                    <div><dt class="{{ $label }}">Campaign</dt><dd class="mt-0.5 text-[13px]">{{ $lead->campaign?->name ?? '—' }}</dd></div>
                    <div><dt class="{{ $label }}">Landing page</dt><dd class="mt-0.5 text-[13px]">{{ $lead->landingPage?->title ?? '—' }}</dd></div>
                    <div><dt class="{{ $label }}">Last contacted</dt><dd class="mt-0.5 text-[13px]">{{ $lead->last_contacted_at?->format('d M Y, h:i A') ?? 'Not yet' }}</dd></div>
                    @if($lead->lost_reason)
                        <div class="md:col-span-2"><dt class="{{ $label }}">Lost reason</dt><dd class="mt-0.5 text-[13px]">{{ $lead->lost_reason }}</dd></div>
                    @endif
                    @if($lead->notes)
                        <div class="md:col-span-2"><dt class="{{ $label }}">Notes</dt><dd class="mt-0.5 whitespace-pre-line text-[13px]">{{ $lead->notes }}</dd></div>
                    @endif
                </dl>
            </div>

            {{-- Log contact --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="mb-3 text-[14px] font-bold text-slate-900">Log contact</h2>
                <form method="POST" action="{{ route('leads.activity', $lead) }}" class="space-y-3">
                    @csrf
                    <div class="flex flex-wrap gap-2">
                        @foreach(['call' => 'Phone call', 'message' => 'Message sent', 'note' => 'Note'] as $type => $name)
                            <label class="inline-flex cursor-pointer items-center gap-1.5 rounded-lg border border-slate-200 px-2.5 py-1.5 text-[12px] font-semibold has-[:checked]:border-indigo-400 has-[:checked]:bg-indigo-50">
                                <input type="radio" name="type" value="{{ $type }}" @checked(old('type', 'call') === $type)> {{ $name }}
                            </label>
                        @endforeach
                    </div>
                    <textarea name="body" rows="2" maxlength="2000" class="{{ $input }}" placeholder="What was discussed?">{{ old('body') }}</textarea>
                    <div class="flex flex-wrap items-end gap-3">
                        <div>
                            <label class="{{ $label }}">Next follow-up (optional)</label>
                            <input type="datetime-local" name="follow_up_at" value="{{ old('follow_up_at') }}" class="{{ $input }}">
                        </div>
                        <button class="rounded-lg bg-indigo-600 px-4 py-2 text-[12px] font-bold text-white hover:bg-indigo-700">Save</button>
                    </div>
                </form>
            </div>

            {{-- Activity --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="mb-3 text-[14px] font-bold text-slate-900">Activity</h2>
                <ol class="space-y-3">
                    @forelse($lead->activities as $activity)
                        <li class="flex gap-3">
                            <span class="mt-1 h-2 w-2 shrink-0 rounded-full {{ in_array($activity->type, ['conversion']) ? 'bg-emerald-500' : (in_array($activity->type, ['call', 'message']) ? 'bg-indigo-500' : 'bg-slate-300') }}"></span>
                            <div class="min-w-0">
                                <p class="text-[12px] font-semibold text-slate-800">{{ $activity->typeLabel() }}
                                    <span class="font-normal text-slate-400">· {{ $activity->user?->name ?? 'System' }} · {{ $activity->created_at->format('d M, h:i A') }}</span>
                                </p>
                                @if($activity->body)<p class="whitespace-pre-line text-[12px] text-slate-600">{{ $activity->body }}</p>@endif
                            </div>
                        </li>
                    @empty
                        <li class="text-[12px] text-slate-400">No activity yet.</li>
                    @endforelse
                </ol>
            </div>
        </div>

        <div class="space-y-4">
            {{-- Status --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="mb-3 text-[14px] font-bold text-slate-900">Status</h2>
                @if($lead->status === 'converted')
                    <p class="text-[12px] text-emerald-700">Converted {{ $lead->converted_at?->format('d M Y') }}{{ $lead->order ? ' — order '.$lead->order->invoice_no : '' }}.</p>
                @else
                    <form method="POST" action="{{ route('leads.status', $lead) }}" class="space-y-2" x-data="{ status: @js(old('status', $lead->status)) }">
                        @csrf
                        <select name="status" x-model="status" class="{{ $input }}">
                            @foreach(['new', 'contacted', 'interested', 'follow_up', 'lost'] as $key)
                                <option value="{{ $key }}">{{ \App\Models\Lead::STATUSES[$key] }}</option>
                            @endforeach
                        </select>
                        <input x-show="status === 'lost'" x-cloak name="lost_reason" maxlength="255" placeholder="Why was it lost?" class="{{ $input }}">
                        <input name="note" maxlength="1000" placeholder="Note (optional)" class="{{ $input }}">
                        <button class="w-full rounded-lg bg-slate-800 px-3 py-2 text-[12px] font-bold text-white hover:bg-slate-900">Update status</button>
                        <p class="text-[10px] text-slate-400">Converted is set automatically when an order is placed for this lead.</p>
                    </form>
                @endif
            </div>

            {{-- Assignment --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="mb-3 text-[14px] font-bold text-slate-900">Assigned to</h2>
                <form method="POST" action="{{ route('leads.assign', $lead) }}" class="flex gap-2">
                    @csrf
                    <select name="assigned_to" class="{{ $input }} mt-0">
                        <option value="">Unassigned</option>
                        @foreach($staff as $member)
                            <option value="{{ $member->id }}" @selected((int) $lead->assigned_to === $member->id)>{{ $member->name }}</option>
                        @endforeach
                    </select>
                    <button class="shrink-0 rounded-lg border border-slate-200 bg-white px-3 text-[12px] font-bold text-slate-700 hover:bg-slate-50">Save</button>
                </form>
                @if($lead->follow_up_at && ! $lead->isClosed())
                    <p class="mt-2 text-[11px] {{ $lead->isFollowUpDue() ? 'font-bold text-rose-600' : 'text-slate-500' }}">Follow-up: {{ $lead->follow_up_at->format('d M Y, h:i A') }}</p>
                @endif
            </div>

            {{-- Customer --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="mb-3 text-[14px] font-bold text-slate-900">Customer</h2>
                @if($lead->customer)
                    <p class="text-[13px] font-semibold text-slate-900">{{ $lead->customer->name }}</p>
                    <p class="text-[11px] text-slate-500">{{ $lead->customer->phone }}{{ $lead->customer->address ? ' · '.$lead->customer->address : '' }}</p>
                    @if($orders->isNotEmpty())
                        <ul class="mt-3 divide-y divide-slate-100 border-t border-slate-100">
                            @foreach($orders as $order)
                                <li class="flex items-center justify-between py-1.5">
                                    <a href="{{ route('online-orders.show', $order) }}" class="font-semibold text-indigo-600 hover:underline">{{ $order->invoice_no }}</a>
                                    <span class="text-[11px] text-slate-500">{{ $orderStatusLabels[\App\Support\OrderStatus::normalize($order->status)] ?? $order->status }} · Tk {{ format_taka_number((float) $order->total_amount) }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                @elseif($lead->phone)
                    <form method="POST" action="{{ route('leads.convert-customer', $lead) }}" class="space-y-2">
                        @csrf
                        <textarea name="address" rows="2" maxlength="1000" class="{{ $input }}" placeholder="Address (optional)"></textarea>
                        <button class="w-full rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-[12px] font-bold text-indigo-700 hover:bg-indigo-100">Create / link customer</button>
                        <p class="text-[10px] text-slate-400">Links an existing customer with the same phone, or creates one.</p>
                    </form>
                @else
                    <p class="text-[12px] text-slate-500">Add a phone number to create a customer.</p>
                @endif
            </div>

            {{-- Order --}}
            @if(! in_array($lead->status, ['converted', 'lost'], true) && $lead->phone)
                <div class="rounded-2xl border border-emerald-200 bg-white p-4 shadow-sm"
                     x-data="leadOrderForm(@js($productOptions), @js($lead->product_id), @js(old('items')))">
                    <h2 class="mb-1 text-[14px] font-bold text-slate-900">Create order</h2>
                    <p class="mb-3 text-[11px] text-slate-500">Uses the normal online checkout: stock is reserved and the order appears in Online orders as New.</p>
                    <form method="POST" action="{{ route('leads.convert-order', $lead) }}" class="space-y-2">
                        @csrf
                        <template x-for="(line, index) in lines" :key="index">
                            <div class="flex gap-2">
                                <select :name="`items[${index}][id]`" x-model.number="line.id" class="{{ $input }} mt-0 min-w-0 flex-1" required>
                                    <option value="">Select product…</option>
                                    <template x-for="p in products" :key="p.id">
                                        <option :value="p.id" :selected="p.id === line.id" :disabled="p.stock < 1" x-text="`${p.name} · Tk ${Math.round(p.price)}${p.stock < 1 ? ' (out of stock)' : ''}`"></option>
                                    </template>
                                </select>
                                <input type="number" min="1" :name="`items[${index}][qty]`" x-model.number="line.qty" class="{{ $input }} mt-0 w-16" required>
                                <button type="button" x-show="lines.length > 1" @click="lines.splice(index, 1)" class="text-[14px] font-bold text-rose-500">&times;</button>
                            </div>
                        </template>
                        <button type="button" @click="lines.push({ id: '', qty: 1 })" class="text-[11px] font-semibold text-indigo-600 hover:underline">+ Add product</button>
                        <p class="text-[11px] text-slate-500">Subtotal: <span class="font-bold text-slate-800" x-text="'Tk ' + subtotal.toLocaleString()"></span> (delivery added by zone)</p>

                        <textarea name="address" rows="2" required maxlength="1000" class="{{ $input }}" placeholder="Delivery address">{{ old('address', $lead->customer?->address) }}</textarea>
                        <div class="grid grid-cols-2 gap-2">
                            <select name="delivery_zone" class="{{ $input }}">
                                @foreach($zones as $zone)
                                    <option value="{{ $zone['code'] }}" @selected(old('delivery_zone', $deliveryConfig['default_zone'] ?? null) === $zone['code'])>{{ $zone['name'] }} · Tk {{ (int) $zone['fee'] }}</option>
                                @endforeach
                            </select>
                            <select name="payment_method" class="{{ $input }}">
                                @foreach($paymentMethods as $method)
                                    <option value="{{ $method }}" @selected(old('payment_method') === $method)>{{ app(\App\Services\PaymentMethodRegistry::class)->label($method) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <input name="note" maxlength="500" value="{{ old('note') }}" placeholder="Order note (optional)" class="{{ $input }}">
                        <button class="w-full rounded-lg bg-emerald-600 px-3 py-2 text-[12px] font-bold text-white hover:bg-emerald-700">Place order for {{ $lead->name }}</button>
                    </form>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
function leadOrderForm(products, defaultProductId, oldItems) {
    const initial = Array.isArray(oldItems) && oldItems.length
        ? oldItems.map((l) => ({ id: Number(l.id) || '', qty: Number(l.qty) || 1 }))
        : [{ id: defaultProductId || '', qty: 1 }];
    return {
        products,
        lines: initial,
        get subtotal() {
            return Math.round(this.lines.reduce((sum, line) => {
                const p = this.products.find((x) => x.id === Number(line.id));
                return sum + (p ? p.price * (Number(line.qty) || 0) : 0);
            }, 0));
        },
    };
}
</script>
</x-app-layout>
