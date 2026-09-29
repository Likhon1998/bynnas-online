<?php

namespace App\Services;

use App\Models\AbandonedCart;
use App\Models\Campaign;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/**
 * Records storefront carts from the existing /cart/sync endpoint so staff can follow up
 * on carts that were left without ordering. Only real cart contents and contact details
 * the shopper entered (or has on their account) are stored.
 */
class AbandonedCartService
{
    public const COOKIE = 'bs_cart';

    public const COOKIE_DAYS = 30;

    public function __construct(
        protected CampaignAttributionService $attribution,
        protected OrderCreationService $orders,
    ) {}

    public function token(Request $request): ?string
    {
        $token = (string) $request->cookie(self::COOKIE);

        return preg_match('/^[A-Za-z0-9]{32,64}$/', $token) ? $token : null;
    }

    /**
     * @param  list<array{id: int, name: string, price: float, qty: int}>  $lines  server-priced cart lines
     * @param  array{name?: ?string, phone?: ?string}  $contact
     */
    public function record(Request $request, int $shopId, array $lines, float $subtotal, array $contact = []): ?AbandonedCart
    {
        if ($this->attribution->isBot($request)) {
            return null;
        }

        $token = $this->token($request);
        $cart = $token ? AbandonedCart::forShop($shopId)->where('token', $token)->first() : null;

        if ($lines === []) {
            if ($cart && ! in_array($cart->status, AbandonedCart::CLOSED_STATUSES, true)) {
                $cart->update(['items' => [], 'item_count' => 0, 'subtotal' => 0, 'last_activity_at' => now()]);
            }

            return $cart;
        }

        // A finished cart (ordered / written off) never gets new items; start a fresh one.
        if (! $cart || in_array($cart->status, AbandonedCart::CLOSED_STATUSES, true)) {
            $token = Str::random(40);
            $cart = new AbandonedCart(['shop_id' => $shopId, 'token' => $token, 'status' => 'active']);
        }
        Cookie::queue(self::COOKIE, $token, self::COOKIE_DAYS * 24 * 60);

        $user = $request->user('web');
        $customer = $user?->isStorefrontCustomer() ? $user->customerProfile : null;
        $phone = $this->cleanPhone($contact['phone'] ?? null) ?? $customer?->phone ?? $cart->phone;
        $name = $this->clean($contact['name'] ?? null, 120) ?? $customer?->name ?? $user?->name ?? $cart->name;

        $customerId = $customer?->id ?? $cart->customer_id;
        if (! $customerId && $phone) {
            $customerId = $this->orders->findByPhone($shopId, $phone)?->id;
        }

        $cart->fill([
            'user_id' => $user?->isStorefrontCustomer() ? $user->id : $cart->user_id,
            'customer_id' => $customerId,
            'name' => $name,
            'phone' => $phone,
            'items' => array_map(fn ($l) => [
                'id' => (int) $l['id'],
                'name' => (string) $l['name'],
                'price' => (float) $l['price'],
                'qty' => (int) $l['qty'],
            ], $lines),
            'item_count' => array_sum(array_column($lines, 'qty')),
            'subtotal' => round($subtotal, 2),
            'last_activity_at' => now(),
            'notified_at' => null,
        ]);

        if (! $cart->exists || ! $cart->utm_source) {
            $cart->fill($this->attributionColumns($request, $shopId));
        }

        $cart->save();

        return $cart;
    }

    /**
     * Mark the cart behind a new order as converted or recovered.
     * Uses the checkout cookie when present, otherwise an open cart with the same customer / phone.
     */
    public function markOrdered(Order $order, ?string $token = null): ?AbandonedCart
    {
        $open = AbandonedCart::forShop($order->shop_id)->whereNotIn('status', AbandonedCart::CLOSED_STATUSES);

        $cart = $token ? (clone $open)->where('token', $token)->first() : null;

        if (! $cart && $order->customer_id) {
            $cart = (clone $open)->where('customer_id', $order->customer_id)->latest('last_activity_at')->first();
        }

        if (! $cart && $order->delivery_phone) {
            $normalized = Customer::normalizePhone($order->delivery_phone);
            if (strlen($normalized) >= 8) {
                $cart = (clone $open)->where('phone', 'like', '%'.substr($normalized, -6))
                    ->latest('last_activity_at')->get()
                    ->first(fn (AbandonedCart $c) => Customer::normalizePhone($c->phone) === $normalized);
            }
        }

        if (! $cart) {
            return null;
        }

        $wasFollowedUp = $cart->status === 'contacted' || $cart->isAbandoned();

        $cart->update([
            'status' => $wasFollowedUp ? 'recovered' : 'converted',
            'order_id' => $order->id,
            'recovered_at' => now(),
            'customer_id' => $cart->customer_id ?? $order->customer_id,
        ]);

        return $cart;
    }

    protected function attributionColumns(Request $request, int $shopId): array
    {
        $touch = $this->attribution->current($request);
        if (! $touch) {
            return [];
        }

        return [
            'campaign_id' => isset($touch['campaign_id'])
                ? Campaign::where('shop_id', $shopId)->whereKey((int) $touch['campaign_id'])->value('id')
                : null,
            'utm_source' => $this->clean($touch['utm_source'] ?? null, 60),
            'utm_medium' => $this->clean($touch['utm_medium'] ?? null, 60),
            'utm_campaign' => $this->clean($touch['utm_campaign'] ?? null, 120),
            'landing_page' => $this->clean($touch['landing_page'] ?? null, 255),
        ];
    }

    protected function cleanPhone(?string $phone): ?string
    {
        $phone = trim((string) $phone);

        return preg_match('/^\+?[0-9\s\-]{8,20}$/', $phone) ? $phone : null;
    }

    protected function clean($value, int $max): ?string
    {
        $value = trim(strip_tags((string) $value));

        return $value === '' ? null : Str::limit($value, $max, '');
    }
}
