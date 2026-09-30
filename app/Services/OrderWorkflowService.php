<?php

namespace App\Services;

use App\Events\OrderStatusChanged;
use App\Models\CourierService;
use App\Models\Order;
use App\Support\OrderStatus;
use Illuminate\Support\Facades\DB;

/**
 * Single entry point for online order status changes.
 * Owns the stock (reserve → commit → release) and ledger side effects of each step.
 */
class OrderWorkflowService
{
    public function __construct(
        protected StockService $stock,
        protected AccountService $accounts,
        protected OnlineOrderTrackingService $tracking,
    ) {}

    /**
     * @param  array{note?: ?string, courier_service_id?: ?int, tracking_number?: ?string, verification_method?: ?string, verification_notes?: ?string, return_reason?: ?string, stock_reason?: ?string}  $data
     *
     * @throws OrderWorkflowException
     */
    public function transition(Order $order, string $to, array $data = [], ?int $userId = null): Order
    {
        if (! $order->isOnlineOrder()) {
            throw new OrderWorkflowException('Only online orders use this workflow.');
        }

        $from = $order->workflowStatus();
        $to = OrderStatus::normalize($to);

        if (! in_array($to, OrderStatus::all(), true)) {
            throw new OrderWorkflowException("Unknown status: {$to}.");
        }

        if ($from === $to) {
            return $this->updateDetails($order, $data, $userId);
        }

        if (! OrderStatus::canTransition($from, $to)) {
            throw new OrderWorkflowException(
                'Cannot change status from '.OrderStatus::label($from).' to '.OrderStatus::label($to).'.'
            );
        }

        $courier = $this->resolveCourier($order, $data);
        $this->guard($order, $from, $to, $data, $courier);

        DB::transaction(function () use ($order, $from, $to, $data, $userId, $courier) {
            // Two staff acting on the same order must not both apply stock/ledger side effects.
            $current = Order::whereKey($order->id)->lockForUpdate()->value('status');
            if (OrderStatus::normalize((string) $current) !== $from) {
                throw new OrderWorkflowException('This order was just updated by someone else. Refresh the page and try again.');
            }

            $updates = ['status' => $to];

            if ($courier) {
                $updates['courier_service_id'] = $courier->id;
                $updates['shipping_courier'] = $courier->name;
            }
            if (! empty($data['tracking_number'])) {
                $updates['shipping_tracking_no'] = $data['tracking_number'];
            }

            if ($to === OrderStatus::CONFIRMED && ! $order->verified_at) {
                $updates['verified_at'] = now();
                $updates['verified_by'] = $userId;
                $updates['verification_method'] = $data['verification_method'];
                $updates['verification_notes'] = $data['verification_notes'] ?? null;
            }

            if ($to === OrderStatus::SHIPPED) {
                $updates['shipped_at'] = now();
            }

            if (in_array($to, [OrderStatus::DELIVERED, OrderStatus::COMPLETED], true) && ! $order->delivered_at) {
                $updates['delivered_at'] = now();
            }

            if ($to === OrderStatus::COMPLETED) {
                $updates['paid_amount'] = $order->netPayable();
                if (! $order->courier_collected_at) {
                    $updates['courier_collected_at'] = now();
                    $updates['courier_collected_amount'] = max(0, round($order->shopCollectableAmount() - $order->shopAdvancePaid(), 2));
                }
            }

            if ($to === OrderStatus::RETURN_REQUESTED) {
                $updates['return_requested_at'] = now();
                $updates['return_reason'] = $data['return_reason'] ?? ($data['note'] ?? null);
            }

            if (OrderStatus::isVoid($to)) {
                $updates['paid_amount'] = 0;
            }

            $order->update($updates);

            $this->tracking->log(
                $order,
                $to,
                ($data['note'] ?? null) ?: $this->defaultNote($order, $from, $to),
                $order->shipping_courier,
                $order->shipping_tracking_no,
                $userId,
                $from,
            );

            if (in_array($to, [OrderStatus::PACKED, OrderStatus::SHIPPED, OrderStatus::DELIVERED, OrderStatus::COMPLETED], true)) {
                $order->load('items.product');
                $this->stock->commitWebOrderStock($order, $userId);
            }

            if ($to === OrderStatus::COMPLETED) {
                $this->accounts->postWebSettlement($order);
            }

            if (OrderStatus::isVoid($to) && ! OrderStatus::isVoid($from)) {
                $order->load('items.product', 'counter');
                $this->stock->releaseReservedStock($order, $userId, $data['stock_reason'] ?? 'order_'.$to);
                $this->accounts->postOrderRefund($order);
            }
        });

        $order->refresh();

        event(new OrderStatusChanged($order, $from, $to, $userId));

        return $order;
    }

    /** Record verification on a NEW order and confirm it in one step. */
    public function verify(Order $order, string $method, ?string $notes, ?int $userId, ?string $customerNote = null): Order
    {
        return $this->transition($order, OrderStatus::CONFIRMED, [
            'verification_method' => $method,
            'verification_notes' => $notes,
            'note' => $customerNote,
        ], $userId);
    }

    /**
     * Courier remitted the COD cash: shipped/delivered → completed.
     * A shipped order passes through delivered so the timeline stays accurate.
     */
    public function collectFromCourier(Order $order, ?int $userId = null): Order
    {
        if (! in_array($order->status, [OrderStatus::SHIPPED, OrderStatus::DELIVERED], true)) {
            throw new OrderWorkflowException('Only shipped or delivered orders can be collected from the courier.');
        }

        if ($order->courier_collected_at) {
            throw new OrderWorkflowException('Cash from this courier was already recorded.');
        }

        $due = $order->amountDueFromCourier();

        return DB::transaction(function () use ($order, $userId, $due) {
            if ($order->status === OrderStatus::SHIPPED) {
                $this->transition($order, OrderStatus::DELIVERED, [], $userId);
            }

            return $this->transition($order, OrderStatus::COMPLETED, [
                'note' => $due > 0.009
                    ? 'Delivered. Collected ৳'.format_taka_number($due).' product COD from courier (delivery fee stays with courier).'
                    : 'Order delivered successfully.',
            ], $userId);
        });
    }

    /** Same-status save: courier / tracking / note edits only. */
    protected function updateDetails(Order $order, array $data, ?int $userId): Order
    {
        $courier = $this->resolveCourier($order, $data);
        $hasNote = ! empty($data['note']);
        $hasTracking = ! empty($data['tracking_number']);

        if (! $courier && ! $hasNote && ! $hasTracking) {
            return $order;
        }

        DB::transaction(function () use ($order, $data, $userId, $courier, $hasNote, $hasTracking) {
            $updates = [];
            if ($courier) {
                $updates['courier_service_id'] = $courier->id;
                $updates['shipping_courier'] = $courier->name;
            }
            if ($hasTracking) {
                $updates['shipping_tracking_no'] = $data['tracking_number'];
            }
            if ($updates) {
                $order->update($updates);
            }

            $this->tracking->upsertLatestLog(
                $order,
                $order->status,
                $hasNote ? $data['note'] : null,
                $order->shipping_courier,
                $order->shipping_tracking_no,
                $userId,
            );
        });

        return $order->refresh();
    }

    protected function resolveCourier(Order $order, array $data): ?CourierService
    {
        if (empty($data['courier_service_id'])) {
            return null;
        }

        $courier = CourierService::forShop($order->shop_id)->active()->find((int) $data['courier_service_id']);
        if (! $courier) {
            throw new OrderWorkflowException('Select an active courier service from this shop.');
        }

        return $courier;
    }

    protected function guard(Order $order, string $from, string $to, array $data, ?CourierService $courier): void
    {
        if ($to === OrderStatus::CONFIRMED && ! $order->verified_at) {
            $method = $data['verification_method'] ?? null;
            if (! $method || ! array_key_exists($method, OrderStatus::VERIFICATION_METHODS)) {
                throw new OrderWorkflowException('Verify the order with the customer (choose how it was verified) before confirming.');
            }
        }

        if ($to === OrderStatus::SHIPPED && ! $courier && ! $order->courier_service_id) {
            throw new OrderWorkflowException('Select a courier service when marking as shipped. Add services under CMS → Courier Services.');
        }

        if ($from === OrderStatus::RETURN_REQUESTED && $to === OrderStatus::DELIVERED && $order->courier_collected_at) {
            throw new OrderWorkflowException('Payment for this order is already settled. Decline the return by moving it back to Completed.');
        }

        if ($to === OrderStatus::REFUNDED && $from === OrderStatus::RETURNED && ! $this->moneyWasCollected($order)) {
            throw new OrderWorkflowException('No payment was collected for this order, so there is nothing to refund.');
        }
    }

    public function moneyWasCollected(Order $order): bool
    {
        return $order->courier_collected_at !== null
            || (float) ($order->confirmation_charge ?? 0) > 0.009
            || (float) ($order->paid_amount ?? 0) > 0.009
            || $order->status === OrderStatus::COMPLETED;
    }

    protected function defaultNote(Order $order, string $from, string $to): ?string
    {
        return match ($to) {
            OrderStatus::CONFIRMED => 'Your order has been confirmed.',
            OrderStatus::PROCESSING => 'We are preparing your items.',
            OrderStatus::PACKED => 'Your order is packed and ready for the courier.',
            OrderStatus::SHIPPED => 'Your package is on the way to your delivery address.',
            OrderStatus::DELIVERED => 'Your package has been delivered.',
            OrderStatus::COMPLETED => $from === OrderStatus::RETURN_REQUESTED
                ? 'Return request closed. Order completed.'
                : 'Order delivered and payment settled.',
            OrderStatus::CANCELLED => 'This order was cancelled.',
            OrderStatus::RETURN_REQUESTED => 'Return request received. We will contact you shortly.',
            OrderStatus::RETURNED => $order->isCashOnDelivery() && ! $this->moneyWasCollected($order)
                ? 'Order returned. COD was not collected — no customer refund.'
                : 'This order was returned to our store.',
            OrderStatus::REFUNDED => 'This order was refunded.',
            default => null,
        };
    }
}
