<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * In-app alert for shop staff (database channel only).
 * `ref` identifies the subject (e.g. "low_stock:12") so repeat alerts can be de-duplicated.
 */
class StaffAlert extends Notification
{
    use Queueable;

    public const KINDS = [
        'order' => 'Order',
        'lead' => 'Lead',
        'low_stock' => 'Low stock',
        'abandoned_cart' => 'Abandoned cart',
        'delivery_issue' => 'Delivery issue',
    ];

    public function __construct(
        public string $kind,
        public string $title,
        public string $body,
        public ?string $url = null,
        public ?string $ref = null,
        public int $shopId = 0,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'kind' => $this->kind,
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->url,
            'ref' => $this->ref,
            'shop_id' => $this->shopId,
        ];
    }
}
