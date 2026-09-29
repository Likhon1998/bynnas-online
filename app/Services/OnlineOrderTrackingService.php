<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderStatusLog;
use App\Support\OrderStatus;

class OnlineOrderTrackingService
{
    /** Customer-facing tracker steps. */
    public const FLOW_STATUSES = OrderStatus::CUSTOMER_STEPS;

    /** Customer-facing labels, keyed by stored status (legacy aliases included). */
    public function statusLabels(): array
    {
        $labels = OrderStatus::customerLabels();
        foreach (OrderStatus::LEGACY_NEW as $legacy) {
            $labels[$legacy] = $labels[OrderStatus::NEW];
        }

        return $labels;
    }

    public function normalizeFlowStatus(string $status): string
    {
        return OrderStatus::customerStep($status);
    }

    public function log(
        Order $order,
        string $status,
        ?string $note = null,
        ?string $courier = null,
        ?string $tracking = null,
        ?int $userId = null,
    ): OrderStatusLog {
        return OrderStatusLog::create([
            'order_id' => $order->id,
            'status' => $status,
            'label' => OrderStatus::customerLabel($status),
            'note' => $note,
            'courier_name' => $courier,
            'tracking_number' => $tracking,
            'changed_by' => $userId,
        ]);
    }

    public function upsertLatestLog(
        Order $order,
        string $status,
        ?string $note = null,
        ?string $courier = null,
        ?string $tracking = null,
        ?int $userId = null,
    ): OrderStatusLog {
        $latest = $order->statusLogs()->latest('id')->first();

        if ($latest && $latest->status === $status) {
            $latest->update([
                'label' => OrderStatus::customerLabel($status),
                'note' => $note ?? $latest->note,
                'courier_name' => $courier ?? $latest->courier_name,
                'tracking_number' => $tracking ?? $latest->tracking_number,
                'changed_by' => $userId ?? $latest->changed_by,
            ]);

            return $latest->fresh();
        }

        return $this->log($order, $status, $note, $courier, $tracking, $userId);
    }

    public function logInitialPlacement(Order $order): OrderStatusLog
    {
        return $this->log(
            $order,
            $order->status ?: OrderStatus::NEW,
            'Your order has been received and is awaiting confirmation.',
        );
    }

    /** Visual step tracker for customers. */
    public function customerTimeline(Order $order): array
    {
        $logs = $order->relationLoaded('statusLogs')
            ? $order->statusLogs->sortBy('created_at')->values()
            : $order->statusLogs()->orderBy('created_at')->get();

        $current = OrderStatus::normalize($order->status);

        if (OrderStatus::isVoid($current)) {
            $latest = $logs->last();

            return [
                [
                    'key' => $current,
                    'label' => OrderStatus::customerLabel($current),
                    'done' => true,
                    'active' => true,
                    'at' => optional($latest)->created_at?->format('d M Y, h:i A'),
                    'note' => optional($latest)->note,
                ],
            ];
        }

        $statusRank = array_flip(self::FLOW_STATUSES);
        $flowCurrent = $this->normalizeFlowStatus($current);
        $currentRank = $statusRank[$flowCurrent] ?? 0;
        $finalStep = self::FLOW_STATUSES[count(self::FLOW_STATUSES) - 1];
        $timeline = [];

        foreach (self::FLOW_STATUSES as $index => $step) {
            $stepLog = $this->latestLogForStep($logs, $step);
            $isActive = $step === $flowCurrent;
            $isDone = $index < $currentRank;
            $isFirst = $index === 0;
            $timeline[] = [
                'key' => $step,
                'label' => OrderStatus::customerLabel($step),
                'done' => $isDone || ($isActive && $flowCurrent === $finalStep),
                'active' => $isActive,
                'at' => $stepLog?->created_at?->format('d M, h:i A')
                    ?? (($isFirst && ($isDone || $isActive)) ? $order->created_at->format('d M, h:i A') : null),
                'note' => $stepLog?->note
                    ?? (($isFirst && ($isDone || $isActive)) ? $this->defaultStatusNote(OrderStatus::NEW) : null)
                    ?? ($isActive ? $this->defaultStatusNote($step) : null),
                'courier' => $step === OrderStatus::SHIPPED ? ($stepLog?->courier_name ?: $order->shipping_courier) : null,
                'tracking' => $step === OrderStatus::SHIPPED ? ($stepLog?->tracking_number ?: $order->shipping_tracking_no) : null,
            ];
        }

        return $timeline;
    }

    protected function defaultStatusNote(string $status): string
    {
        return match ($status) {
            OrderStatus::NEW => 'Your order has been received and is awaiting confirmation.',
            OrderStatus::CONFIRMED => 'Your order has been confirmed.',
            OrderStatus::PROCESSING => 'Your items are being prepared for dispatch.',
            OrderStatus::SHIPPED => 'Your package is on the way to the delivery address.',
            OrderStatus::DELIVERED => 'Delivery completed successfully.',
            default => '',
        };
    }

    public function trackingPayload(Order $order): array
    {
        $order->loadMissing(['items.product', 'customer', 'statusLogs.changedBy']);

        $timeline = $this->customerTimeline($order);
        $activeStep = collect($timeline)->firstWhere('active', true) ?? $timeline[0] ?? null;

        return [
            'success' => true,
            'order_id' => $order->id,
            'invoice' => $order->invoice_no,
            'status' => $this->normalizeFlowStatus($order->status),
            'status_raw' => $order->status,
            'status_label' => OrderStatus::customerLabel($order->status),
            'message' => 'Order found!',
            'date' => asian_datetime($order->created_at, 'd M Y, h:i A'),
            'total' => number_format((float) $order->total_amount, 2),
            'delivery_address' => $order->delivery_address ?: $order->customer?->address,
            'customer_name' => $order->delivery_name ?: $order->customer?->name,
            'courier' => $order->shipping_courier,
            'tracking_number' => $order->shipping_tracking_no,
            'items' => $order->items->map(function ($item) {
                $product = $item->product;

                return [
                    'name' => $product?->name ?? 'Product',
                    'qty' => (int) $item->quantity,
                    'subtotal' => number_format((float) $item->subtotal, 2),
                    'image' => $product
                        ? app(WebsiteService::class)->productImageUrl($product)
                        : '',
                ];
            })->values()->all(),
            'timeline' => $timeline,
            'updates' => $order->statusLogs->sortByDesc('created_at')->values()->map(fn ($log) => [
                'status' => $log->status,
                'label' => OrderStatus::customerLabel($log->status),
                'note' => $log->note,
                'courier' => $log->courier_name,
                'tracking' => $log->tracking_number,
                'at' => $log->created_at->format('d M Y, h:i A'),
            ])->all(),
            'where_is_product' => $this->whereIsProductMessage($order, $activeStep),
        ];
    }

    protected function whereIsProductMessage(Order $order, ?array $activeStep): string
    {
        return match (OrderStatus::normalize($order->status)) {
            OrderStatus::NEW => 'We have received your order and will confirm it shortly.',
            OrderStatus::CONFIRMED => 'Your order is confirmed and will be prepared soon.',
            OrderStatus::PROCESSING => 'Your order is being prepared for dispatch.',
            OrderStatus::PACKED => 'Your order is packed and waiting for the courier.',
            OrderStatus::SHIPPED => $order->shipping_courier
                ? 'In transit with '.$order->shipping_courier.($order->shipping_tracking_no ? ' · '.$order->shipping_tracking_no : '').'.'
                : 'Your package is in transit to the delivery address.',
            OrderStatus::DELIVERED, OrderStatus::COMPLETED => 'Delivered successfully. Thank you for your purchase.',
            OrderStatus::RETURN_REQUESTED => 'Your return request is being reviewed.',
            OrderStatus::CANCELLED => 'This order has been cancelled.',
            OrderStatus::RETURNED => 'This order has been returned.',
            OrderStatus::REFUNDED => 'This order has been refunded.',
            default => $activeStep['note']
                ?? 'We have received your order and will share updates as it progresses.',
        };
    }

    protected function latestLogForStep($logs, string $step): ?OrderStatusLog
    {
        return $logs
            ->filter(fn ($log) => OrderStatus::customerStep($log->status) === $step)
            ->sortByDesc('created_at')
            ->first();
    }
}
