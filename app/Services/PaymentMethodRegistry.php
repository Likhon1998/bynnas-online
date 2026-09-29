<?php

namespace App\Services;

use App\Contracts\PaymentGateway;
use App\Models\SiteSetting;

/**
 * Online payment methods. COD and the confirmation charge are driven by delivery settings;
 * gateways (bKash, Nagad, card) are only offered once credentials and a driver exist.
 */
class PaymentMethodRegistry
{
    public const COD = 'cash_on_delivery';

    public const CONFIRMATION = 'confirmation_charge';

    /** @return array<string, array<string, mixed>> */
    public function definitions(): array
    {
        return (array) config('payments.methods', []);
    }

    /**
     * Every method with its availability and the reason when unavailable.
     *
     * @return list<array{key: string, type: string, label: string, description: string, available: bool, reason: ?string}>
     */
    public function all(?SiteSetting $settings = null): array
    {
        $settings ??= SiteSetting::current();
        $rows = [];

        foreach ($this->definitions() as $key => $definition) {
            [$available, $reason] = $this->availability($key, $definition, $settings);
            $rows[] = [
                'key' => $key,
                'type' => (string) ($definition['type'] ?? 'offline'),
                'label' => (string) ($definition['label'] ?? $key),
                'description' => (string) ($definition['description'] ?? ''),
                'available' => $available,
                'reason' => $reason,
            ];
        }

        return $rows;
    }

    /** @return list<string> Keys a customer may choose at checkout right now. */
    public function checkoutKeys(?SiteSetting $settings = null): array
    {
        // Gateways need a redirect + verified callback flow before they can be offered to customers.
        $keys = array_column(array_filter(
            $this->all($settings),
            fn ($m) => $m['available'] && $m['type'] === 'offline'
        ), 'key');

        // Fail-safe so checkout is never completely blocked by misconfiguration.
        return $keys === [] ? [self::COD] : array_values($keys);
    }

    public function isGateway(string $key): bool
    {
        return ($this->definitions()[$key]['type'] ?? null) === 'gateway';
    }

    public function label(?string $key): string
    {
        return (string) ($this->definitions()[(string) $key]['label'] ?? ucfirst(str_replace('_', ' ', (string) $key)));
    }

    public function gateway(string $key): ?PaymentGateway
    {
        $definition = $this->definitions()[$key] ?? null;
        if (! $definition || ! $this->gatewayConfigured($definition)) {
            return null;
        }

        return new $definition['driver']((array) $definition['credentials'], (bool) ($definition['sandbox'] ?? true));
    }

    /** @return array{0: bool, 1: ?string} */
    protected function availability(string $key, array $definition, SiteSetting $settings): array
    {
        if ($key === self::COD) {
            return ($settings->delivery_cod_enabled ?? true)
                ? [true, null]
                : [false, 'Turned off in delivery settings'];
        }

        if ($key === self::CONFIRMATION) {
            $enabled = (bool) ($settings->delivery_confirmation_enabled ?? false);
            $amount = (float) ($settings->delivery_confirmation_amount ?? 0);

            if (! $enabled) {
                return [false, 'Turned off in delivery settings'];
            }

            return $amount > 0.009 ? [true, null] : [false, 'Confirmation amount is 0'];
        }

        if (($definition['type'] ?? null) === 'gateway') {
            $missing = array_keys(array_filter((array) ($definition['credentials'] ?? []), fn ($v) => blank($v)));
            if ($missing !== []) {
                return [false, 'Missing credentials: '.implode(', ', $missing)];
            }
            if (! $this->gatewayConfigured($definition)) {
                return [false, 'No payment driver installed'];
            }

            return [true, null];
        }

        return [false, 'Unknown payment type'];
    }

    protected function gatewayConfigured(array $definition): bool
    {
        $driver = $definition['driver'] ?? null;
        $credentials = (array) ($definition['credentials'] ?? []);

        return $credentials !== []
            && ! in_array(true, array_map('blank', $credentials), true)
            && is_string($driver)
            && class_exists($driver)
            && is_subclass_of($driver, PaymentGateway::class);
    }
}
