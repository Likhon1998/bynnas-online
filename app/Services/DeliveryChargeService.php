<?php

namespace App\Services;

use App\Models\SiteSetting;

class DeliveryChargeService
{
    public const ZONE_INSIDE = 'inside_dhaka';

    public const ZONE_OUTSIDE = 'outside_dhaka';

    public const PAY_COD = 'cash_on_delivery';

    public const PAY_CONFIRMATION = 'confirmation_charge';

    public function settings(?SiteSetting $settings = null): SiteSetting
    {
        return $settings ?? SiteSetting::current();
    }

    /** Public config for storefront checkout (safe to expose). */
    public function publicConfig(?SiteSetting $settings = null): array
    {
        $s = $this->settings($settings);

        return [
            'inside_dhaka' => (float) ($s->delivery_inside_dhaka ?? 60),
            'outside_dhaka' => (float) ($s->delivery_outside_dhaka ?? 120),
            'free_enabled' => (bool) ($s->delivery_free_enabled ?? true),
            'free_min_amount' => (float) ($s->delivery_free_min_amount ?? 10000),
            'cod_enabled' => (bool) ($s->delivery_cod_enabled ?? true),
            'confirmation_enabled' => (bool) ($s->delivery_confirmation_enabled ?? false),
            'confirmation_amount' => (float) ($s->delivery_confirmation_amount ?? 0),
            'currency_symbol' => $s->currency_symbol ?: '৳',
        ];
    }

    public function normalizeZone(?string $zone): string
    {
        $zone = strtolower(trim((string) $zone));

        return $zone === self::ZONE_OUTSIDE ? self::ZONE_OUTSIDE : self::ZONE_INSIDE;
    }

    public function normalizePaymentMethod(?string $method, ?SiteSetting $settings = null): string
    {
        $cfg = $this->publicConfig($settings);
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
        $cfg = $this->publicConfig($settings);
        $methods = [];

        if ($cfg['cod_enabled']) {
            $methods[] = self::PAY_COD;
        }

        if ($cfg['confirmation_enabled'] && $cfg['confirmation_amount'] > 0.009) {
            $methods[] = self::PAY_CONFIRMATION;
        }

        if ($methods === []) {
            // Fail-safe so checkout is never completely blocked by misconfiguration.
            $methods[] = self::PAY_COD;
        }

        return $methods;
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
    public function quote(float $subtotal, ?string $zone, ?string $paymentMethod = null, ?SiteSetting $settings = null): array
    {
        $cfg = $this->publicConfig($settings);
        $zone = $this->normalizeZone($zone);
        $subtotal = max(0, round($subtotal, 2));

        $baseFee = $zone === self::ZONE_OUTSIDE
            ? (float) $cfg['outside_dhaka']
            : (float) $cfg['inside_dhaka'];
        $baseFee = max(0, round($baseFee, 2));

        $isFree = $cfg['free_enabled'] && $subtotal + 0.009 >= (float) $cfg['free_min_amount'];
        $deliveryFee = $isFree ? 0.0 : $baseFee;
        $grandTotal = round($subtotal + $deliveryFee, 2);

        $paymentMethod = $this->normalizePaymentMethod($paymentMethod, $settings);
        $confirmationAmount = 0.0;
        $paidNow = 0.0;

        if ($paymentMethod === self::PAY_CONFIRMATION) {
            $confirmationAmount = min($grandTotal, max(0, round((float) $cfg['confirmation_amount'], 2)));
            $paidNow = $confirmationAmount;
        }

        $dueLater = max(0, round($grandTotal - $paidNow, 2));

        return [
            'zone' => $zone,
            'zone_label' => $zone === self::ZONE_OUTSIDE ? 'Outside Dhaka' : 'Inside Dhaka',
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
            'cod_enabled' => $cfg['cod_enabled'],
            'confirmation_enabled' => $cfg['confirmation_enabled'] && $cfg['confirmation_amount'] > 0.009,
        ];
    }
}
