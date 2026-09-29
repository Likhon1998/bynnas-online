<?php

namespace App\Notifications;

use App\Models\Order;
use App\Support\OrderStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Order updates for the customer: placed, confirmed, shipped, delivered, cancelled, refunded.
 * Mail when an address is known; storefront accounts also get a database copy.
 */
class CustomerOrderUpdate extends Notification
{
    use Queueable;

    public const EVENTS = [
        'placed' => 'Order received',
        OrderStatus::CONFIRMED => 'Order confirmed',
        OrderStatus::SHIPPED => 'Order shipped',
        OrderStatus::DELIVERED => 'Order delivered',
        OrderStatus::CANCELLED => 'Order cancelled',
        OrderStatus::REFUNDED => 'Refund processed',
    ];

    public function __construct(public Order $order, public string $event) {}

    public function via(object $notifiable): array
    {
        $channels = [];
        if ($notifiable instanceof \App\Models\User) {
            $channels[] = 'database';
        }
        if ($this->mailAddress($notifiable)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order;
        $mail = (new MailMessage)
            ->subject(self::EVENTS[$this->event].' · '.$order->invoice_no)
            ->greeting('Hello '.($order->customer?->name ?: 'there').',')
            ->line($this->message());

        if ($this->event === OrderStatus::SHIPPED && $order->shipping_tracking_no) {
            $mail->line('Tracking number: '.$order->shipping_tracking_no);
        }

        return $mail
            ->line('Order total: Tk '.number_format((float) $order->total_amount, 0))
            ->action('Track your order', route('website.track', ['invoice' => $order->invoice_no]));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'order_update',
            'event' => $this->event,
            'title' => self::EVENTS[$this->event],
            'body' => $this->message(),
            'order_id' => $this->order->id,
            'invoice' => $this->order->invoice_no,
        ];
    }

    public function message(): string
    {
        $invoice = $this->order->invoice_no;
        $courier = $this->order->courierService?->name;

        return match ($this->event) {
            'placed' => "We received your order {$invoice}. We will contact you to confirm it.",
            OrderStatus::CONFIRMED => "Your order {$invoice} is confirmed and will be prepared for delivery.",
            OrderStatus::SHIPPED => "Your order {$invoice} has been handed to ".($courier ?: 'our courier').'.',
            OrderStatus::DELIVERED => "Your order {$invoice} has been delivered. Thank you for shopping with us.",
            OrderStatus::CANCELLED => "Your order {$invoice} has been cancelled.",
            OrderStatus::REFUNDED => "A refund for order {$invoice} has been processed.",
            default => "Order {$invoice} was updated.",
        };
    }

    protected function mailAddress(object $notifiable): ?string
    {
        $email = method_exists($notifiable, 'routeNotificationFor')
            ? $notifiable->routeNotificationFor('mail', $this)
            : null;

        if (is_array($email)) {
            $email = array_key_first($email) ?: reset($email);
        }

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }
}
