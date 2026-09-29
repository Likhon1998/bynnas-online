<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;

class OrderPlaced
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $context  e.g. cart_token, lead_id from checkout
     */
    public function __construct(
        public Order $order,
        public array $context = [],
    ) {}
}
