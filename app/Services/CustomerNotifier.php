<?php

namespace App\Services;

use App\Models\Order;
use App\Notifications\CustomerOrderUpdate;
use Illuminate\Support\Facades\Notification;

/**
 * Customer-facing order updates for online orders only (POS receipts are handled at the counter).
 * Storefront accounts get an in-account copy plus mail; guests get mail when an address is on file.
 */
class CustomerNotifier
{
    public function orderUpdate(Order $order, string $event): bool
    {
        if (! array_key_exists($event, CustomerOrderUpdate::EVENTS) || ! $this->isOnlineOrder($order)) {
            return false;
        }

        $order->loadMissing(['customer.user', 'courierService']);
        $notification = new CustomerOrderUpdate($order, $event);

        $user = $order->customer?->user;
        if ($user && $user->isStorefrontCustomer()) {
            $user->notify($notification);

            return true;
        }

        $email = $order->customer?->email;
        if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Notification::route('mail', $email)->notify($notification);

            return true;
        }

        return false;
    }

    protected function isOnlineOrder(Order $order): bool
    {
        return $order->counter_id === null && str_starts_with((string) $order->invoice_no, 'WEB-');
    }
}
