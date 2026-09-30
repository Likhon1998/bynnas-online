<?php

namespace App\Console\Commands;

use App\Models\AbandonedCart;
use App\Services\CommerceAlertService;
use Illuminate\Console\Command;

class ScanCommerceAlerts extends Command
{
    protected $signature = 'commerce:scan-alerts';

    protected $description = 'Alert staff about newly abandoned carts and shipments that are overdue for delivery';

    public function handle(CommerceAlertService $alerts): int
    {
        $carts = $alerts->scanAbandonedCarts();
        $delayed = $alerts->scanDelayedShipments();
        $expired = $this->expireStaleCarts();

        $this->info("Abandoned-cart digests: {$carts}; delayed-shipment alerts: {$delayed}; stale carts closed: {$expired}.");

        return self::SUCCESS;
    }

    protected function expireStaleCarts(): int
    {
        $days = (int) config('commerce.abandoned_cart_expire_days', 30);
        if ($days <= 0) {
            return 0;
        }

        $note = '['.now()->format('d M Y').'] Closed automatically after '.$days.' days without activity.';

        $closed = 0;
        AbandonedCart::whereIn('status', ['active', 'contacted'])
            ->where('last_activity_at', '<', now()->subDays($days))
            ->chunkById(200, function ($carts) use ($note, &$closed) {
                foreach ($carts as $cart) {
                    $cart->update([
                        'status' => 'lost',
                        'notes' => trim(($cart->notes ? $cart->notes.PHP_EOL : '').$note),
                    ]);
                    $closed++;
                }
            });

        return $closed;
    }
}
