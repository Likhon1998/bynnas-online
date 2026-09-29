<?php

namespace App\Listeners;

use App\Events\OrderStatusChanged;
use App\Notifications\CustomerOrderUpdate;
use App\Services\CommerceAlertService;
use App\Services\CustomerNotifier;
use App\Support\OrderStatus;

class HandleOrderStatusChanged
{
    public function __construct(
        protected CommerceAlertService $alerts,
        protected CustomerNotifier $customers,
    ) {}

    public function handle(OrderStatusChanged $event): void
    {
        $order = $event->order;

        try {
            $this->alerts->orderStatusIssue($order, $event->from, $event->to);
        } catch (\Throwable $e) {
            report($e);
        }

        // Declining a return puts the order back to delivered; the customer was already told it arrived.
        $redelivered = $event->to === OrderStatus::DELIVERED && $event->from === OrderStatus::RETURN_REQUESTED;

        if (array_key_exists($event->to, CustomerOrderUpdate::EVENTS) && ! $redelivered) {
            try {
                $this->customers->orderUpdate($order, $event->to);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }
}
