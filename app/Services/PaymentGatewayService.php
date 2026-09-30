<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Models\PaymentTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Prepaid gateway flow: start a transaction, send the customer to the gateway,
 * verify the callback server-side, then record the payment on the order.
 * Inert until PaymentMethodRegistry::gateway() returns a configured driver.
 */
class PaymentGatewayService
{
    public function __construct(protected PaymentMethodRegistry $registry) {}

    public function isAvailable(string $method): bool
    {
        return $this->registry->gateway($method) !== null;
    }

    /** @return string Redirect URL for the customer. */
    public function start(Order $order, string $method, float $amount): string
    {
        $driver = $this->registry->gateway($method);
        if (! $driver) {
            throw new \RuntimeException('This payment method is not available.');
        }
        if ($amount <= 0.009) {
            throw new \InvalidArgumentException('Payment amount must be greater than zero.');
        }

        $transaction = PaymentTransaction::create([
            'shop_id' => $order->shop_id,
            'order_id' => $order->id,
            'method' => $method,
            'amount' => round($amount, 2),
            'status' => PaymentTransaction::PENDING,
        ]);

        return $driver->initiate($transaction, route('payment.callback', $transaction));
    }

    public function handleCallback(PaymentTransaction $transaction, Request $request): PaymentTransaction
    {
        if (! $transaction->isPending()) {
            return $transaction;
        }

        $driver = $this->registry->gateway($transaction->method);
        if (! $driver) {
            throw new \RuntimeException('This payment method is not available.');
        }

        $result = $driver->verify($transaction, $request);
        $paid = (bool) ($result['paid'] ?? false);
        $amount = round((float) ($result['amount'] ?? 0), 2);

        if ($paid && abs($amount - (float) $transaction->amount) > 0.009) {
            $paid = false;
            $result['reason'] = 'Paid amount does not match the order amount.';
        }

        return DB::transaction(function () use ($transaction, $result, $paid, $amount) {
            $locked = PaymentTransaction::whereKey($transaction->id)->lockForUpdate()->first();
            if (! $locked->isPending()) {
                return $locked;
            }

            $locked->update([
                'status' => $paid ? PaymentTransaction::PAID : PaymentTransaction::FAILED,
                'gateway_reference' => $result['reference'] ?? null,
                'payload' => $result['payload'] ?? null,
                'failure_reason' => $paid ? null : ($result['reason'] ?? 'Payment was not completed.'),
                'verified_at' => now(),
            ]);

            if ($paid) {
                $order = Order::whereKey($locked->order_id)->lockForUpdate()->first();
                $order->update([
                    'paid_amount' => round((float) $order->paid_amount + $amount, 2),
                    'payment_reference' => $result['reference'] ?? $order->payment_reference,
                ]);

                OrderStatusLog::create([
                    'order_id' => $order->id,
                    'status' => $order->status,
                    'label' => 'Payment received',
                    'note' => $this->registry->label($locked->method).' payment of ৳'.format_taka_number($amount).' confirmed.',
                ]);
            }

            return $locked;
        });
    }
}
