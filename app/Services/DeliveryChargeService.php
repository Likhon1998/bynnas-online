<?php

namespace App\Services;

use App\Models\DeliveryZone;
use App\Models\SiteSetting;
use Illuminate\Support\Str;

class DeliveryChargeService
{
    /** Legacy zone codes (still stored on older orders and seeded as the first two zones). */
    public const ZONE_INSIDE = 'inside_dhaka';

    public const ZONE_OUTSIDE = 'outside_dhaka';

    public const PAY_COD = PaymentMethodRegistry::COD;

    public const PAY_CONFIRMATION = PaymentMethodRegistry::CONFIRMATION;

    public function __construct(
        protected PaymentMethodRegistry $payments,
    ) {}

    public function settings(?SiteSetting $settings = null): SiteSetting
    {
        return $settings ?? SiteSetting::current();
    }

    protected function resolveShopId(?int $shopId): ?int
    {
        if ($shopId) {
            return $shopId;
        }

        try {
            return app(WebsiteService::class)->shopId();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Active zones for checkout, in display order.
     * Falls back to the legacy Inside/Outside Dhaka settings when a shop has no zones yet.
     *
     * @return list<array{code: string, name: string, fee: float, note: ?string, is_default: bool}>
     */
    public function zones(?int $shopId = null, ?SiteSetting $settings = null): array
    {
        $shopId = $this->resolveShopId($shopId);

        $zones = $shopId
            ? DeliveryZone::forShop($shopId)->active()->ordered()->get()
            : collect();

        if ($zones->isEmpty()) {
            $s = $this->settings($settings);

            return [
                ['code' => self::ZONE_INSIDE, 'name' => 'Inside Dhaka', 'fee' => (float) ($s->delivery_inside_dhaka ?? 60), 'note' => null, 'is_default' => true],
                ['code' => self::ZONE_OUTSIDE, 'name' => 'Outside Dhaka', 'fee' => (float) ($s->delivery_outside_dhaka ?? 120), 'note' => null, 'is_default' => false],
            ];
        }

        $hasDefault = $zones->contains('is_default', true);

        return $zones->values()->map(fn (DeliveryZone $zone, int $i) => [
            'code' => $zone->code,
            'name' => $zone->name,
            'fee' => (float) $zone->fee,
            'note' => $zone->note,
            'is_default' => $hasDefault ? (bool) $zone->is_default : $i === 0,
        ])->all();
    }

    /** @return list<string> */
    public function zoneCodes(?int $shopId = null): array
    {
        return array_column($this->zones($shopId), 'code');
    }

    public function defaultZoneCode(?int $shopId = null): string
    {
        $zones = $this->zones($shopId);
        foreach ($zones as $zone) {
            if ($zone['is_default']) {
                return $zone['code'];
            }
        }

        return $zones[0]['code'] ?? self::ZONE_INSIDE;
    }

    /** Human label for a stored zone code (including zones that were later removed). */
    public function zoneLabel(?string $code, ?int $shopId = null): string
    {
        $code = (string) $code;
        if ($code === '') {
            return '';
        }

        $shopId = $this->resolveShopId($shopId);
        $name = $shopId ? DeliveryZone::forShop($shopId)->where('code', $code)->value('name') : null;

        return $name ?: match ($code) {
            self::ZONE_INSIDE => 'Inside Dhaka',
            self::ZONE_OUTSIDE => 'Outside Dhaka',
            default => Str::headline($code),
        };
    }

    /** Public config for storefront checkout (safe to expose). */
    public function publicConfig(?SiteSetting $settings = null, ?int $shopId = null): array
    {
        $s = $this->settings($settings);
        $zones = $this->zones($shopId, $s);
        $fees = array_column($zones, 'fee', 'code');

        return [
            'zones' => $zones,
            'default_zone' => $this->defaultZoneCode($shopId),
            // Legacy keys kept for any cached storefront script still reading them.
            'inside_dhaka' => (float) ($fees[self::ZONE_INSIDE] ?? $s->delivery_inside_dhaka ?? 60),
            'outside_dhaka' => (float) ($fees[self::ZONE_OUTSIDE] ?? $s->delivery_outside_dhaka ?? 120),
            'free_enabled' => (bool) ($s->delivery_free_enabled ?? false),
            'free_min_amount' => (float) ($s->delivery_free_min_amount ?? 0),
            'cod_enabled' => (bool) ($s->delivery_cod_enabled ?? true),
            'confirmation_enabled' => (bool) ($s->delivery_confirmation_enabled ?? false),
            'confirmation_amount' => (float) ($s->delivery_confirmation_amount ?? 0),
            'confirmation_instructions' => (string) ($s->delivery_confirmation_instructions ?? ''),
            'payment_methods' => $this->allowedPaymentMethods($s),
            'currency_symbol' => $s->currency_symbol ?: '৳',
        ];
    }

    public function normalizeZone(?string $zone, ?int $shopId = null): string
    {
        $zone = strtolower(trim((string) $zone));

        return in_array($zone, $this->zoneCodes($shopId), true) ? $zone : $this->defaultZoneCode($shopId);
    }

    public function normalizePaymentMethod(?string $method, ?SiteSetting $settings = null): string
    {
        $method = strtolower(trim((string) $method));

        $allowed = $this->allowedPaymentMethods($settings);
        if (in_array($method, $allowed, true)) {
            return $method;
        }

        return $allowed[0] ?? self::PAY_COD;
    }

    /** @return list<string> */
    public function allowedPaymentMethods(?SiteSetting $settings = null): array
    {
        return $this->payments->checkoutKeys($this->settings($settings));
    }

    /**
     * @return array{
     *   zone: string,
     *   zone_label: string,
     *   subtotal: float,
     *   base_fee: float,
     *   delivery_fee: float,
     *   is_free: bool,
     *   free_reason: string|null,
     *   confirmation_amount: float,
     *   payment_method: string,
     *   amount_paid_now: float,
     *   amount_due_later: float,
     *   grand_total: float
     * }
     */
    public function quote(float $subtotal, ?string $zone, ?string $paymentMethod = null, ?SiteSetting $settings = null, ?int $shopId = null): array
    {
        $s = $this->settings($settings);
        $cfg = $this->publicConfig($s, $shopId);
        $zone = $this->normalizeZone($zone, $shopId);
        $zoneRow = collect($cfg['zones'])->firstWhere('code', $zone);
        $subtotal = max(0, round($subtotal, 2));

        $baseFee = max(0, round((float) ($zoneRow['fee'] ?? 0), 2));

        $isFree = $cfg['free_enabled'] && $subtotal + 0.009 >= (float) $cfg['free_min_amount'];
        $deliveryFee = $isFree ? 0.0 : $baseFee;
        $grandTotal = round($subtotal + $deliveryFee, 2);

        $paymentMethod = $this->normalizePaymentMethod($paymentMethod, $s);
        $confirmationAmount = 0.0;
        $paidNow = 0.0;

        if ($paymentMethod === self::PAY_CONFIRMATION) {
            $confirmationAmount = min($grandTotal, max(0, round((float) $cfg['confirmation_amount'], 2)));
            $paidNow = $confirmationAmount;
        }

        $dueLater = max(0, round($grandTotal - $paidNow, 2));

        return [
            'zone' => $zone,
            'zone_label' => $zoneRow['name'] ?? $this->zoneLabel($zone, $shopId),
            'subtotal' => $subtotal,
            'base_fee' => $baseFee,
            'delivery_fee' => $deliveryFee,
            'is_free' => $isFree,
            'free_reason' => $isFree
                ? 'Free delivery on orders over '.$cfg['currency_symbol'].number_format((float) $cfg['free_min_amount'], 0)
                : null,
            'confirmation_amount' => $confirmationAmount,
            'payment_method' => $paymentMethod,
            'amount_paid_now' => $paidNow,
            'amount_due_later' => $dueLater,
            'grand_total' => $grandTotal,
            'cod_enabled' => in_array(self::PAY_COD, $cfg['payment_methods'], true),
            'confirmation_enabled' => in_array(self::PAY_CONFIRMATION, $cfg['payment_methods'], true),
        ];
    }
}
