<?php

namespace App\Console\Commands;

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

        $this->info("Abandoned-cart digests: {$carts}; delayed-shipment alerts: {$delayed}.");

        return self::SUCCESS;
    }
}
