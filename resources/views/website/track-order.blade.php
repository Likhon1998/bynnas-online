@extends('website.layout')

@section('title', 'Track Order — '.($settings->store_name ?? config('app.name', 'Bynnas Social')))

@section('content')
@php
    $currencySymbol = $settings->currency_symbol ?? '৳';
    $normalizedStatus = \App\Support\OrderStatus::normalize($tracking['status_raw'] ?? null);
    $statusTone = match (true) {
        \App\Support\OrderStatus::isVoid($normalizedStatus) => 'is-void',
        in_array($normalizedStatus, [\App\Support\OrderStatus::DELIVERED, \App\Support\OrderStatus::COMPLETED], true) => 'is-done',
        default => 'is-live',
    };
@endphp
<div class="bb-track" x-data="trackOrderPage(@js($invoiceNo ?? ''), @js(old('phone', $phone ?? '')), @js(old('lookup_mode', !empty($phoneOrders) ? 'phone' : 'id')))">
    <div class="bb-track-wrap">
        <header class="bb-track-head">
            <span class="bb-track-kicker">Order tracking</span>
            <h1 class="bb-track-title">Where’s my order?</h1>
            <p class="bb-track-sub">Track with your Order ID and phone number — or, if you forgot the Order ID, find all your orders with just your phone number. No account needed.</p>
        </header>

        <form method="POST" action="{{ route('website.track.lookup') }}" class="bb-track-form" x-ref="form" @submit="submitting = true">
            @csrf
            <input type="hidden" name="lookup_mode" :value="mode">

            <div class="bb-track-tabs" role="tablist">
                <button type="button" role="tab" class="bb-track-tab" :class="{ 'is-active': mode === 'id' }" :aria-selected="mode === 'id'" @click="mode = 'id'">
                    Order ID + phone
                </button>
                <button type="button" role="tab" class="bb-track-tab" :class="{ 'is-active': mode === 'phone' }" :aria-selected="mode === 'phone'" @click="mode = 'phone'">
                    Forgot Order ID? Use phone
                </button>
                <span class="bb-track-tabs__pill" :style="mode === 'phone' ? 'transform: translateX(100%)' : ''" aria-hidden="true"></span>
            </div>

            @if(session('error'))
                <div class="bb-track-alert" role="alert">{{ session('error') }}</div>
            @endif
            <div class="bb-track-fields" :class="{ 'is-single': mode === 'phone' }">
                <label class="bb-track-field" x-show="mode === 'id'">
                    <span>Order ID</span>
                    <input name="invoice_no" x-model="invoice" :required="mode === 'id'" :disabled="mode !== 'id'" maxlength="64" autocomplete="off" placeholder="WEB-1-2026-00001">
                    @error('invoice_no') <em>{{ $message }}</em> @enderror
                </label>
                <label class="bb-track-field">
                    <span>Phone number used at checkout</span>
                    <input name="phone" x-model="phone" required maxlength="32" type="tel" autocomplete="tel" placeholder="01XXXXXXXXX">
                    @error('phone') <em>{{ $message }}</em> @enderror
                </label>
            </div>
            <p class="bb-track-hint" x-show="mode === 'phone'" x-cloak>We’ll show every order placed with this number. For privacy, delivery name and address stay partly hidden.</p>
            <button type="submit" class="bb-track-submit" :disabled="submitting">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <span x-text="submitting ? 'Finding your orders…' : (mode === 'phone' ? 'Find my orders' : 'Track order')">Track order</span>
            </button>

            <template x-if="recent.length">
                <div class="bb-track-recent">
                    <span class="bb-track-recent__label">Recent orders on this device</span>
                    <div class="bb-track-recent__list">
                        <template x-for="order in recent" :key="order.invoice">
                            <button type="button" class="bb-track-recent__chip" @click="useRecent(order)">
                                <span x-text="order.invoice"></span>
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M5 12h14m-6-6 6 6-6 6"/></svg>
                            </button>
                        </template>
                    </div>
                </div>
            </template>
        </form>

        @if(!empty($phoneOrders))
            <section class="bb-track-found" aria-live="polite">
                <h2 class="bb-track-found__title">
                    {{ count($phoneOrders) }} {{ \Illuminate\Support\Str::plural('order', count($phoneOrders)) }} found
                </h2>
                <ul class="bb-track-found__list">
                    @foreach($phoneOrders as $found)
                        <li>
                            <a href="{{ $found['url'] }}" class="bb-track-found__card" style="--i: {{ $loop->index }}">
                                <span class="bb-track-found__top">
                                    <span class="bb-track-found__id">{{ $found['invoice'] }}</span>
                                    <span class="bb-track-badge {{ $found['tone'] }}">{{ $found['status_label'] }}</span>
                                </span>
                                <span class="bb-track-found__items">{{ $found['items'] }}</span>
                                <span class="bb-track-found__foot">
                                    <span>{{ $found['date'] }} · <strong>{{ $currencySymbol }}{{ $found['total'] }}</strong></span>
                                    <span class="bb-track-found__go">
                                        View status
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M5 12h14m-6-6 6 6-6 6"/></svg>
                                    </span>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
                <p class="bb-track-found__tip">Tip: note your Order ID — it’s the quickest way to track next time.</p>
            </section>
        @endif

        @if(!empty($tracking))
            <section class="bb-track-result" aria-live="polite">
                <div class="bb-track-hero {{ $statusTone }}">
                    <div class="min-w-0">
                        <p class="bb-track-hero__id">{{ $tracking['invoice'] }}</p>
                        <h2 class="bb-track-hero__status">{{ $tracking['status_label'] }}</h2>
                        <p class="bb-track-hero__msg">{{ $tracking['where_is_product'] ?? '' }}</p>
                    </div>
                    <div class="bb-track-hero__meta">
                        <span>Placed {{ $tracking['date'] }}</span>
                        <strong>{{ $currencySymbol }}{{ $tracking['total'] }}</strong>
                    </div>
                </div>

                @if(!empty($tracking['timeline']))
                    <ol class="bb-track-steps" style="--steps: {{ count($tracking['timeline']) }}">
                        @foreach($tracking['timeline'] as $step)
                            <li class="bb-track-step {{ !empty($step['done']) ? 'is-done' : '' }} {{ !empty($step['active']) ? 'is-active' : '' }}">
                                <span class="bb-track-step__dot" aria-hidden="true">
                                    @if(!empty($step['done']) && empty($step['active']))
                                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    @else
                                        {{ $loop->iteration }}
                                    @endif
                                </span>
                                <span class="bb-track-step__body">
                                    <span class="bb-track-step__label">{{ $step['label'] ?? 'Update' }}</span>
                                    @if(!empty($step['at']))
                                        <span class="bb-track-step__at">{{ $step['at'] }}</span>
                                    @endif
                                    @if(!empty($step['active']) && !empty($step['note']))
                                        <span class="bb-track-step__note">{{ $step['note'] }}</span>
                                    @endif
                                </span>
                            </li>
                        @endforeach
                    </ol>
                @endif

                @if(!empty($tracking['courier']) || !empty($tracking['tracking_number']))
                    <div class="bb-track-courier">
                        <span class="bb-track-courier__icon" aria-hidden="true">
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zm10 0a2 2 0 11-4 0 2 2 0 014 0zM13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1"/></svg>
                        </span>
                        <div class="min-w-0">
                            <p class="bb-track-courier__name">{{ $tracking['courier'] ?: 'Courier' }}</p>
                            @if(!empty($tracking['tracking_number']))
                                <p class="bb-track-courier__no">Tracking # <strong>{{ $tracking['tracking_number'] }}</strong></p>
                            @endif
                        </div>
                    </div>
                @endif

                <div class="bb-track-grid">
                    <div class="bb-track-card">
                        <h3 class="bb-track-card__title">Items</h3>
                        <ul class="bb-track-items">
                            @foreach($tracking['items'] ?? [] as $item)
                                <li>
                                    @if(!empty($item['image']))
                                        <img src="{{ $item['image'] }}" alt="" class="bb-track-items__img" loading="lazy">
                                    @endif
                                    <span class="bb-track-items__name">{{ $item['name'] }}</span>
                                    <span class="bb-track-items__qty">× {{ $item['qty'] }}</span>
                                    <span class="bb-track-items__price">{{ $currencySymbol }}{{ $item['subtotal'] }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="bb-track-card">
                        <h3 class="bb-track-card__title">Delivery to</h3>
                        <p class="bb-track-card__strong">{{ $tracking['customer_name'] ?: '—' }}</p>
                        <p class="bb-track-card__text">{{ $tracking['delivery_address'] ?: '—' }}</p>
                        @if(!empty($trackingMasked))
                            <p class="bb-track-card__privacy">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                Partly hidden for privacy.
                            </p>
                        @endif
                    </div>
                </div>

                @if(count($tracking['updates'] ?? []) > 1)
                    <details class="bb-track-card bb-track-history">
                        <summary>Full update history</summary>
                        <ul>
                            @foreach($tracking['updates'] as $update)
                                <li>
                                    <strong>{{ $update['label'] }}</strong>
                                    <span>{{ $update['at'] }}</span>
                                    @if(!empty($update['note']))<p>{{ $update['note'] }}</p>@endif
                                </li>
                            @endforeach
                        </ul>
                    </details>
                @endif
            </section>
        @endif

        <p class="bb-track-foot">
            @auth('web')
                <a href="{{ route('website.account') }}#recent-orders">View all orders in My Account</a>
            @else
                Have an account? <button type="button" @click="openSignIn('login')">Sign in</button> to see all your orders.
            @endauth
        </p>
    </div>
</div>

<script>
    window.trackOrderPage = function trackOrderPage(invoice, phone, mode) {
        let recent = [];
        try {
            recent = JSON.parse(localStorage.getItem('gaget_recent_orders') || '[]').filter((o) => o && o.invoice);
        } catch (e) {}
        return {
            invoice: invoice || '',
            phone: phone || (recent.find((o) => o.invoice === invoice) || {}).phone || '',
            recent,
            mode: mode === 'phone' ? 'phone' : 'id',
            submitting: false,
            useRecent(order) {
                this.mode = 'id';
                this.invoice = order.invoice;
                this.phone = order.phone || this.phone;
                if (!this.phone) return;
                this.submitting = true;
                this.$nextTick(() => this.$refs.form.submit());
            },
        };
    };
</script>
@endsection
