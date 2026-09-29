<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Services\AbandonedCartService;
use App\Services\CommerceAlertService;
use App\Services\CustomerNotifier;
use App\Services\LeadService;

/**
 * Follow-up work after an online order is committed. Each step is isolated:
 * a failure here is reported but never undoes or blocks the order.
 */
class HandleOrderPlaced
{
    public function __construct(
        protected LeadService $leads,
        protected AbandonedCartService $carts,
        protected CommerceAlertService $alerts,
        protected CustomerNotifier $customers,
    ) {}

    public function handle(OrderPlaced $event): void
    {
        $order = $event->order;
        $context = $event->context;

        $this->safely(fn () => $this->leads->linkOrder($order, isset($context['lead_id']) ? (int) $context['lead_id'] : null));
        $this->safely(fn () => $this->carts->markOrdered($order, $context['cart_token'] ?? null));
        $this->safely(fn () => $this->alerts->checkLowStock($order));
        $this->safely(fn () => $this->customers->orderUpdate($order, 'placed'));
    }

    protected function safely(callable $step): void
    {
        try {
            $step();
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
