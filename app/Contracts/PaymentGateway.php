<?php

namespace App\Contracts;

use App\Models\Order;
use Illuminate\Http\Request;

/**
 * Driver contract for prepaid online gateways (bKash, Nagad, card).
 * A driver is only used when its credentials are fully configured.
 */
interface PaymentGateway
{
    /** @param  array<string, mixed>  $credentials */
    public function __construct(array $credentials, bool $sandbox);

    /** Start a payment for the order; returns the URL to send the customer to. */
    public function initiate(Order $order, float $amount): string;

    /**
     * Verify the gateway callback server-side.
     *
     * @return array{paid: bool, amount: float, reference: ?string}
     */
    public function verify(Order $order, Request $request): array;
}
