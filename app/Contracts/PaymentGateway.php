<?php

namespace App\Contracts;

use App\Models\PaymentTransaction;
use Illuminate\Http\Request;

/**
 * Driver contract for prepaid online gateways (bKash, Nagad, card).
 * A driver is only used when its credentials are fully configured.
 */
interface PaymentGateway
{
    /** @param  array<string, mixed>  $credentials */
    public function __construct(array $credentials, bool $sandbox);

    /**
     * Start a payment for the transaction; returns the URL to send the customer to.
     * The gateway must return the customer to $callbackUrl.
     */
    public function initiate(PaymentTransaction $transaction, string $callbackUrl): string;

    /**
     * Verify the gateway callback server-side (never trust query parameters alone).
     *
     * @return array{paid: bool, amount: float, reference: ?string, payload?: array<string, mixed>, reason?: ?string}
     */
    public function verify(PaymentTransaction $transaction, Request $request): array;
}
