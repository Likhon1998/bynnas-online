<?php

namespace App\Services;

use App\Models\AbandonedCart;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shop;
use App\Support\OrderStatus;

/**
 * Staff alerts derived from real order / stock / cart data.
 * Every alert is de-duplicated by a subject ref so repeated events or scans stay quiet.
 */
class CommerceAlertService
{
    public function __construct(protected StaffNotifier $notifier) {}

    /** Alert when products on this order dropped to (or below) their alert quantity. */
    public function checkLowStock(Order $order): int
    {
        $productIds = $order->items()->pluck('product_id')->filter()->unique();
        if ($productIds->isEmpty()) {
            return 0;
        }

        $sent = 0;
        Product::query()->whereIn('id', $productIds)->where('shop_id', $order->shop_id)->get()
            ->each(function (Product $product) use (&$sent) {
                $threshold = (int) ($product->alert_quantity ?? 0);
                $available = $product->availableStock();
                if ($threshold <= 0 || $available > $threshold) {
                    return;
                }

                $ref = 'low_stock:'.$product->id;
                if ($this->notifier->recentlySent($product->shop_id, $ref, now()->subDay())) {
                    return;
                }

                $name = $product->storefrontDisplayName();
                $this->notifier->send(
                    $product->shop_id,
                    'low_stock',
                    $available === 0 ? "Out of stock: {$name}" : "Low stock: {$name}",
                    "{$available} available to sell (alert level {$threshold}).",
                    route('reports.low_stock'),
                    $ref,
                    'manage inventory',
                );
                $sent++;
            });

        return $sent;
    }

    /** Courier return requests / returns after dispatch. */
    public function orderStatusIssue(Order $order, string $from, string $to): bool
    {
        if (! in_array($to, [OrderStatus::RETURN_REQUESTED, OrderStatus::RETURNED], true)) {
            return false;
        }
        if ($to === OrderStatus::RETURNED && ! in_array($from, [OrderStatus::SHIPPED, OrderStatus::DELIVERED, OrderStatus::RETURN_REQUESTED], true)) {
            return false;
        }

        $title = $to === OrderStatus::RETURN_REQUESTED
            ? "Return requested: {$order->invoice_no}"
            : "Returned: {$order->invoice_no}";
        $body = $to === OrderStatus::RETURN_REQUESTED
            ? ($order->return_reason ?: 'Customer asked to return this order.')
            : 'Order came back'.($order->courierService ? ' via '.$order->courierService->name : '').'.';

        return $this->notifier->send(
            $order->shop_id,
            'delivery_issue',
            $title,
            $body,
            route('online-orders.show', $order),
            'order_'.$to.':'.$order->id,
        ) > 0;
    }

    /** Shipped orders not delivered after the configured number of days (one alert per order). */
    public function scanDelayedShipments(): int
    {
        $days = max(1, (int) config('commerce.delivery_issue_days', 5));
        $sent = 0;

        Order::query()
            ->onlineOrders()
            ->where('status', OrderStatus::SHIPPED)
            ->whereNotNull('shipped_at')
            ->where('shipped_at', '<=', now()->subDays($days))
            ->with('courierService:id,name')
            ->orderBy('id')
            ->each(function (Order $order) use ($days, &$sent) {
                $ref = 'delivery_delay:'.$order->id;
                if ($this->notifier->recentlySent($order->shop_id, $ref)) {
                    return;
                }

                $this->notifier->send(
                    $order->shop_id,
                    'delivery_issue',
                    "Delivery delayed: {$order->invoice_no}",
                    'Shipped '.$order->shipped_at->diffForHumans()
                        .($order->courierService ? ' with '.$order->courierService->name : '')
                        .($order->shipping_tracking_no ? " (tracking {$order->shipping_tracking_no})" : '')
                        ." and not delivered after {$days} days.",
                    route('online-orders.show', $order),
                    $ref,
                );
                $sent++;
            });

        return $sent;
    }

    /** One digest per shop for carts that became abandoned since the last scan. */
    public function scanAbandonedCarts(): int
    {
        $sent = 0;

        Shop::query()->pluck('id')->each(function (int $shopId) use (&$sent) {
            $carts = AbandonedCart::forShop($shopId)
                ->abandoned()
                ->whereNull('notified_at')
                ->get();

            if ($carts->isEmpty()) {
                return;
            }

            $withContact = $carts->filter->hasContact()->count();
            $value = (float) $carts->sum('subtotal');

            $this->notifier->send(
                $shopId,
                'abandoned_cart',
                $carts->count() === 1 ? '1 abandoned cart' : $carts->count().' abandoned carts',
                'Worth Tk '.number_format($value, 0).'; '.$withContact.' with phone or customer details to follow up.',
                route('abandoned-carts.index'),
                'abandoned_carts:'.now()->format('YmdHi'),
                'manage leads',
            );

            AbandonedCart::whereIn('id', $carts->pluck('id'))->update(['notified_at' => now()]);
            $sent++;
        });

        return $sent;
    }
}
