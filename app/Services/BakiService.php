<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerBakiEntry;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BakiService
{
    public function __construct(
        protected AccountService $accounts,
    ) {}

    /**
     * Pool math for a BAKI checkout.
     *
     * @return array{
     *   previous: float,
     *   bill: float,
     *   total_due: float,
     *   pay_now: float,
     *   toward_bill: float,
     *   toward_previous: float,
     *   credit: float,
     *   baki_left: float,
     *   change: float
     * }
     */
    public function resolvePool(float $previousBalance, float $billPayable, float $payNow): array
    {
        $previous = max(0, round($previousBalance, 2));
        $bill = max(0, round($billPayable, 2));
        $totalDue = round($previous + $bill, 2);
        $tendered = max(0, round($payNow, 2));
        $change = max(0, round($tendered - $totalDue, 2));
        $payNow = min($tendered, $totalDue);

        $towardBill = min($payNow, $bill);
        $towardPrevious = round($payNow - $towardBill, 2);
        $credit = round($bill - $towardBill, 2);
        $bakiLeft = round($previous + $bill - $payNow, 2);

        return [
            'previous' => $previous,
            'bill' => $bill,
            'total_due' => $totalDue,
            'pay_now' => $payNow,
            'toward_bill' => $towardBill,
            'toward_previous' => $towardPrevious,
            'credit' => $credit,
            'baki_left' => $bakiLeft,
            'change' => $change,
        ];
    }

    /**
     * Take $need from cash, then card, then mobile.
     *
     * @return array{cash: float, card: float, mobile: float, remaining_cash: float, remaining_card: float, remaining_mobile: float}
     */
    public function splitTenders(float $need, float $cash, float $card, float $mobile): array
    {
        $need = max(0, round($need, 2));
        $cash = max(0, round($cash, 2));
        $card = max(0, round($card, 2));
        $mobile = max(0, round($mobile, 2));

        $takeCash = min($cash, $need);
        $need = round($need - $takeCash, 2);
        $takeCard = min($card, $need);
        $need = round($need - $takeCard, 2);
        $takeMobile = min($mobile, $need);

        return [
            'cash' => $takeCash,
            'card' => $takeCard,
            'mobile' => $takeMobile,
            'remaining_cash' => round($cash - $takeCash, 2),
            'remaining_card' => round($card - $takeCard, 2),
            'remaining_mobile' => round($mobile - $takeMobile, 2),
        ];
    }

    public function addSaleCredit(
        Customer $customer,
        Order $order,
        float $amount,
        ?int $userId = null,
    ): ?CustomerBakiEntry {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            return null;
        }

        return DB::transaction(function () use ($customer, $order, $amount, $userId) {
            $customer = Customer::whereKey($customer->id)->lockForUpdate()->firstOrFail();

            $entry = CustomerBakiEntry::create([
                'shop_id' => $customer->shop_id,
                'customer_id' => $customer->id,
                'order_id' => $order->id,
                'user_id' => $userId ?? Auth::id(),
                'type' => 'sale',
                'amount' => $amount,
                'method' => null,
                'note' => 'Baki on sale '.$order->invoice_no,
            ]);

            $customer->increment('baki_balance', $amount);

            return $entry;
        });
    }

    /**
     * Collect payment against customer baki (reduces balance + posts cash → AR).
     */
    public function collectPayment(
        Customer $customer,
        float $amount,
        string $method = 'cash',
        ?string $note = null,
        ?Order $order = null,
        ?int $userId = null,
        ?int $counterId = null,
        ?float $cash = null,
        ?float $card = null,
        ?float $mobile = null,
    ): CustomerBakiEntry {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw new InvalidArgumentException('Payment amount must be greater than zero.');
        }

        return DB::transaction(function () use (
            $customer, $amount, $method, $note, $order, $userId, $counterId, $cash, $card, $mobile
        ) {
            $customer = Customer::whereKey($customer->id)->lockForUpdate()->firstOrFail();
            $balance = round((float) $customer->baki_balance, 2);

            if ($amount > $balance + 0.009) {
                throw new InvalidArgumentException(
                    'Payment cannot exceed baki balance (৳'.format_taka_number($balance).').'
                );
            }

            $entry = CustomerBakiEntry::create([
                'shop_id' => $customer->shop_id,
                'customer_id' => $customer->id,
                'order_id' => $order?->id,
                'user_id' => $userId ?? Auth::id(),
                'type' => 'payment',
                'amount' => -1 * $amount,
                'method' => $method,
                'note' => $note ?? 'Baki payment',
            ]);

            $customer->decrement('baki_balance', $amount);
            // Avoid floating dust
            $customer->refresh();
            if ((float) $customer->baki_balance < 0.005) {
                $customer->update(['baki_balance' => 0]);
            }

            // Apply collection to open baki invoices (oldest first) so reprints show PAID.
            $applyLeft = $amount;
            if ($order) {
                $targets = Order::whereKey($order->id)->lockForUpdate()->get();
            } else {
                $targets = Order::where('shop_id', $customer->shop_id)
                    ->where('customer_id', $customer->id)
                    ->where('is_baki', true)
                    ->where('credit_amount', '>', 0)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();
            }

            foreach ($targets as $target) {
                if ($applyLeft <= 0.009) {
                    break;
                }
                $due = round((float) ($target->credit_amount ?? 0), 2);
                if ($due <= 0.009) {
                    continue;
                }
                $take = min($due, $applyLeft);
                $target->credit_amount = round($due - $take, 2);
                $target->paid_amount = round((float) ($target->paid_amount ?? 0) + $take, 2);
                $target->save();
                if (! $entry->order_id) {
                    $entry->update(['order_id' => $target->id]);
                }
                $applyLeft = round($applyLeft - $take, 2);
            }

            $this->accounts->postBakiPayment(
                customer: $customer,
                entry: $entry,
                amount: $amount,
                method: $method,
                counterId: $counterId,
                cash: $cash,
                card: $card,
                mobile: $mobile,
                userId: $userId ?? Auth::id(),
            );

            return $entry;
        });
    }

    /**
     * Reverse remaining baki from a refunded order (if any credit was recorded).
     */
    public function reverseSaleCredit(Order $order, ?int $userId = null): void
    {
        $credit = round((float) ($order->credit_amount ?? 0), 2);
        if ($credit <= 0 || ! $order->customer_id) {
            return;
        }

        DB::transaction(function () use ($order, $userId) {
            if (CustomerBakiEntry::where('order_id', $order->id)->where('type', 'refund')->exists()) {
                return;
            }

            $saleAmt = (float) (CustomerBakiEntry::where('order_id', $order->id)->where('type', 'sale')->sum('amount') ?? 0);
            $payAmt = (float) (CustomerBakiEntry::where('order_id', $order->id)->where('type', 'payment')->sum('amount') ?? 0);
            $remaining = round(max(0, $saleAmt + $payAmt), 2);
            if ($remaining <= 0) {
                return;
            }

            $customer = Customer::whereKey($order->customer_id)->lockForUpdate()->first();
            if (! $customer) {
                return;
            }

            $reverse = min($remaining, round((float) $customer->baki_balance, 2));
            if ($reverse <= 0) {
                return;
            }

            CustomerBakiEntry::create([
                'shop_id' => $order->shop_id,
                'customer_id' => $customer->id,
                'order_id' => $order->id,
                'user_id' => $userId ?? Auth::id(),
                'type' => 'refund',
                'amount' => -1 * $reverse,
                'method' => null,
                'note' => 'Baki reversed on refund '.$order->invoice_no,
            ]);

            $customer->decrement('baki_balance', $reverse);
            $customer->refresh();
            if ((float) $customer->baki_balance < 0.005) {
                $customer->update(['baki_balance' => 0]);
            }

            $this->accounts->postBakiReversal($order, $reverse, $userId ?? Auth::id());
        });
    }
}
