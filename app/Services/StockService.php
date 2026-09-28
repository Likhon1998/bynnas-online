<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\StockLocation;
use App\Models\StockMovement;
use App\Models\WarehouseStock;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class StockService
{
    public function hasOpeningInventory(Product $product): bool
    {
        return StockMovement::where('shop_id', $product->shop_id)
            ->where('product_id', $product->id)
            ->where(function ($q) {
                $q->where('document_type', 'opening_inventory')
                    ->orWhere('reason', 'opening_inventory');
            })
            ->exists();
    }

    public function apply(
        Product $product,
        string $direction,
        int $quantity,
        string $reference,
        string $reason,
        ?int $userId = null,
        ?string $documentType = null,
        ?int $documentId = null,
        ?int $locationId = null,
    ): StockMovement {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be at least 1.');
        }

        if (! in_array($direction, ['in', 'out'], true)) {
            throw new InvalidArgumentException('Direction must be in or out.');
        }

        return DB::transaction(function () use (
            $product, $direction, $quantity, $reference, $reason, $userId, $documentType, $documentId, $locationId
        ) {
            $userId ??= Auth::id();
            $product = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $previousStock = (int) $product->stock_quantity;
            $available = $product->availableStock();

            if ($direction === 'out' && $quantity > $available) {
                throw new InvalidArgumentException("Insufficient stock for {$product->name}. Available: {$available}");
            }

            $currentStock = $direction === 'in'
                ? $previousStock + $quantity
                : $previousStock - $quantity;

            $locationId ??= $this->defaultStore($product->shop_id)?->id;

            $movement = StockMovement::create([
                'shop_id' => $product->shop_id,
                'product_id' => $product->id,
                'user_id' => $userId,
                'type' => $direction,
                'reason' => $reason,
                'quantity' => $quantity,
                'previous_stock' => $previousStock,
                'current_stock' => $currentStock,
                'reference' => $reference,
                'document_type' => $documentType,
                'document_id' => $documentId,
                'location_id' => $locationId,
            ]);

            $product->update(['stock_quantity' => $currentStock]);

            return $movement;
        });
    }

    /**
     * POS / website sale — deducts sellable stock and logs type "sale".
     */
    public function recordSale(
        Product $product,
        int $quantity,
        string $reference,
        ?int $userId = null,
        ?string $documentType = null,
        ?int $documentId = null,
    ): StockMovement {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be at least 1.');
        }

        return DB::transaction(function () use ($product, $quantity, $reference, $userId, $documentType, $documentId) {
            $userId ??= Auth::id();
            $product = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $previousStock = (int) $product->stock_quantity;
            $available = $product->availableStock();

            if ($quantity > $available) {
                throw new InvalidArgumentException("Insufficient stock for {$product->name}. Available: {$available}");
            }

            $currentStock = $previousStock - $quantity;

            $movement = StockMovement::create([
                'shop_id' => $product->shop_id,
                'product_id' => $product->id,
                'user_id' => $userId,
                'type' => 'sale',
                'reason' => 'sale',
                'quantity' => $quantity,
                'previous_stock' => $previousStock,
                'current_stock' => $currentStock,
                'reference' => $reference,
                'document_type' => $documentType ?? 'order',
                'document_id' => $documentId,
                'location_id' => $this->defaultStore($product->shop_id)?->id,
            ]);

            $product->update(['stock_quantity' => $currentStock]);

            return $movement;
        });
    }

    /**
     * Restore stock from refund / return / cancel — skips if never sold or already restocked.
     */
    public function restockForDocument(
        Product $product,
        int $quantity,
        string $reference,
        string $documentType,
        int $documentId,
        string $reason = 'order_return',
        ?int $userId = null,
    ): ?StockMovement {
        // Only gate web/POS order refunds: skip restock when stock was never committed
        // (deferred COD). Exchange returns use document_type "exchange_return" and must proceed.
        if (str_starts_with($documentType, 'order')) {
            $hadSale = StockMovement::where('shop_id', $product->shop_id)
                ->where('product_id', $product->id)
                ->where('document_type', 'order')
                ->where('document_id', $documentId)
                ->where('type', 'sale')
                ->exists();

            if (! $hadSale) {
                return null;
            }
        }

        $alreadyRestocked = StockMovement::where('shop_id', $product->shop_id)
            ->where('product_id', $product->id)
            ->where('document_type', $documentType)
            ->where('document_id', $documentId)
            ->where('type', 'in')
            ->exists();

        if ($alreadyRestocked) {
            return null;
        }

        return $this->apply(
            $product,
            'in',
            $quantity,
            $reference,
            $reason,
            $userId,
            $documentType,
            $documentId,
            $this->defaultStore($product->shop_id)?->id,
        );
    }

    /** True when sellable stock was already deducted for this order line. */
    public function hasSaleForOrder(Product $product, int $orderId): bool
    {
        return StockMovement::where('shop_id', $product->shop_id)
            ->where('product_id', $product->id)
            ->where('document_type', 'order')
            ->where('document_id', $orderId)
            ->where('type', 'sale')
            ->exists();
    }

    public function hasActiveReservationForOrder(Product $product, int $orderId): bool
    {
        $reserved = StockMovement::where('shop_id', $product->shop_id)
            ->where('product_id', $product->id)
            ->where('document_type', 'order_reserve')
            ->where('document_id', $orderId)
            ->where('type', 'reserve')
            ->sum('quantity');

        $released = StockMovement::where('shop_id', $product->shop_id)
            ->where('product_id', $product->id)
            ->where('document_type', 'order_reserve')
            ->where('document_id', $orderId)
            ->whereIn('type', ['release', 'commit'])
            ->sum('quantity');

        return ((int) $reserved - (int) $released) > 0;
    }

    /**
     * Hold stock for a web COD order at checkout (does not reduce physical stock).
     * Available to sell = physical − reserved.
     */
    public function reserveWebOrderStock(Order $order, ?int $userId = null): void
    {
        $order->loadMissing('items.product');
        $userId ??= Auth::id() ?? $order->user_id;

        DB::transaction(function () use ($order, $userId) {
            foreach ($order->items as $item) {
                $product = $item->product;
                if (! $product) {
                    continue;
                }

                $qty = (int) $item->quantity;
                if ($qty < 1) {
                    continue;
                }

                if ($this->hasActiveReservationForOrder($product, $order->id) || $this->hasSaleForOrder($product, $order->id)) {
                    continue;
                }

                $product = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
                $available = $product->availableStock();

                if ($qty > $available) {
                    throw new InvalidArgumentException(
                        "Insufficient stock for {$product->name}. Available: {$available}"
                    );
                }

                $reserved = $product->reservedStock() + $qty;
                $product->update(['reserved_stock' => $reserved]);

                StockMovement::create([
                    'shop_id' => $product->shop_id,
                    'product_id' => $product->id,
                    'user_id' => $userId,
                    'type' => 'reserve',
                    'reason' => 'web_order_reserve',
                    'quantity' => $qty,
                    'previous_stock' => (int) $product->stock_quantity,
                    'current_stock' => (int) $product->stock_quantity,
                    'reference' => 'Reserve website order - '.$order->invoice_no,
                    'document_type' => 'order_reserve',
                    'document_id' => $order->id,
                    'location_id' => $this->defaultStore($product->shop_id)?->id,
                ]);
            }
        });
    }

    /**
     * Packing / shipping: convert reservation into a real stock out (physical ↓, reserved ↓).
     * Idempotent per product+order.
     */
    public function commitWebOrderStock(Order $order, ?int $userId = null): void
    {
        $order->loadMissing('items.product');
        $userId ??= Auth::id() ?? $order->user_id;

        DB::transaction(function () use ($order, $userId) {
            foreach ($order->items as $item) {
                $product = $item->product;
                if (! $product) {
                    continue;
                }

                $qty = (int) $item->quantity;
                if ($qty < 1) {
                    continue;
                }

                if ($this->hasSaleForOrder($product, $order->id)) {
                    continue;
                }

                $product = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
                $previousStock = (int) $product->stock_quantity;
                $reserved = $product->reservedStock();

                // Prefer consuming reservation; still allow commit if reserve was missed (legacy orders).
                if ($reserved < $qty && $previousStock < $qty) {
                    throw new InvalidArgumentException(
                        "Insufficient stock for {$product->name}. Physical: {$previousStock}, reserved: {$reserved}"
                    );
                }

                if ($previousStock < $qty) {
                    throw new InvalidArgumentException(
                        "Insufficient physical stock for {$product->name}. On hand: {$previousStock}"
                    );
                }

                $newPhysical = $previousStock - $qty;
                $newReserved = max(0, $reserved - $qty);

                $product->update([
                    'stock_quantity' => $newPhysical,
                    'reserved_stock' => $newReserved,
                ]);

                StockMovement::create([
                    'shop_id' => $product->shop_id,
                    'product_id' => $product->id,
                    'user_id' => $userId,
                    'type' => 'sale',
                    'reason' => 'sale',
                    'quantity' => $qty,
                    'previous_stock' => $previousStock,
                    'current_stock' => $newPhysical,
                    'reference' => 'Website order - '.$order->invoice_no,
                    'document_type' => 'order',
                    'document_id' => $order->id,
                    'location_id' => $this->defaultStore($product->shop_id)?->id,
                ]);

                if ($reserved > 0) {
                    StockMovement::create([
                        'shop_id' => $product->shop_id,
                        'product_id' => $product->id,
                        'user_id' => $userId,
                        'type' => 'commit',
                        'reason' => 'web_order_commit',
                        'quantity' => min($qty, $reserved),
                        'previous_stock' => $previousStock,
                        'current_stock' => $newPhysical,
                        'reference' => 'Commit reservation - '.$order->invoice_no,
                        'document_type' => 'order_reserve',
                        'document_id' => $order->id,
                        'location_id' => $this->defaultStore($product->shop_id)?->id,
                    ]);
                }
            }
        });
    }

    /**
     * Cancel / fraud / door reject before packing: drop reservation, return to available pool.
     * If stock was already committed (packed), restock physical instead.
     */
    public function releaseReservedStock(Order $order, ?int $userId = null, string $reason = 'order_cancelled'): void
    {
        $order->loadMissing('items.product');
        $userId ??= Auth::id() ?? $order->user_id;

        DB::transaction(function () use ($order, $userId, $reason) {
            foreach ($order->items as $item) {
                $product = $item->product;
                if (! $product) {
                    continue;
                }

                $qty = (int) $item->quantity;
                if ($qty < 1) {
                    continue;
                }

                if ($this->hasSaleForOrder($product, $order->id)) {
                    $this->restockForDocument(
                        $product,
                        $qty,
                        'Order release - '.$order->invoice_no,
                        'order_refund',
                        $order->id,
                        $reason,
                        $userId,
                    );
                    continue;
                }

                if (! $this->hasActiveReservationForOrder($product, $order->id)) {
                    continue;
                }

                $product = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
                $reserved = $product->reservedStock();
                $releaseQty = min($qty, $reserved);
                if ($releaseQty < 1) {
                    continue;
                }

                $product->update(['reserved_stock' => max(0, $reserved - $releaseQty)]);

                StockMovement::create([
                    'shop_id' => $product->shop_id,
                    'product_id' => $product->id,
                    'user_id' => $userId,
                    'type' => 'release',
                    'reason' => $reason,
                    'quantity' => $releaseQty,
                    'previous_stock' => (int) $product->stock_quantity,
                    'current_stock' => (int) $product->stock_quantity,
                    'reference' => 'Release reservation - '.$order->invoice_no,
                    'document_type' => 'order_reserve',
                    'document_id' => $order->id,
                    'location_id' => $this->defaultStore($product->shop_id)?->id,
                ]);
            }
        });
    }

    public function setOpeningStock(Product $product, int $quantity, ?int $userId = null): StockMovement
    {
        if ($this->hasOpeningInventory($product)) {
            throw new InvalidArgumentException('Opening inventory already recorded. Use Stock Adjustment.');
        }

        if ($product->stock_quantity !== 0) {
            throw new InvalidArgumentException(
                "{$product->name} already has {$product->stock_quantity} units on hand. Use Stock Adjustment instead."
            );
        }

        if ($quantity < 1) {
            throw new InvalidArgumentException('Opening quantity must be at least 1.');
        }

        return $this->apply(
            $product,
            'in',
            $quantity,
            'Opening inventory set to ' . $quantity,
            'opening_inventory',
            $userId,
            'opening_inventory',
            null,
            $this->defaultStore($product->shop_id)?->id,
        );
    }

    public function adjustWarehouseStock(StockLocation $location, Product $product, int $delta): void
    {
        if ($location->type !== 'warehouse') {
            return;
        }

        $row = WarehouseStock::firstOrCreate(
            ['location_id' => $location->id, 'product_id' => $product->id],
            ['quantity' => 0],
        );

        $newQty = $row->quantity + $delta;
        if ($newQty < 0) {
            throw new InvalidArgumentException("Insufficient warehouse stock for {$product->name}.");
        }

        $row->update(['quantity' => $newQty]);
    }

    /**
     * Audit trail for warehouse-only qty changes (sellable stock unchanged).
     */
    public function recordWarehouseMovement(
        Product $product,
        string $direction,
        int $quantity,
        string $reference,
        string $reason,
        int $locationId,
        ?int $userId = null,
        ?string $documentType = null,
        ?int $documentId = null,
    ): StockMovement {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be at least 1.');
        }

        if (! in_array($direction, ['in', 'out'], true)) {
            throw new InvalidArgumentException('Direction must be in or out.');
        }

        $userId ??= Auth::id();
        $sellable = (int) $product->stock_quantity;

        return StockMovement::create([
            'shop_id' => $product->shop_id,
            'product_id' => $product->id,
            'user_id' => $userId,
            'type' => $direction,
            'reason' => $reason,
            'quantity' => $quantity,
            'previous_stock' => $sellable,
            'current_stock' => $sellable,
            'reference' => $reference,
            'document_type' => $documentType,
            'document_id' => $documentId,
            'location_id' => $locationId,
        ]);
    }

    public function transferBetweenLocations(
        StockLocation $from,
        StockLocation $to,
        Product $product,
        int $quantity,
        string $reference,
        ?int $userId = null,
        ?string $documentType = null,
        ?int $documentId = null,
    ): void {
        if ($from->shop_id !== $to->shop_id || $from->id === $to->id) {
            throw new InvalidArgumentException('Invalid transfer locations.');
        }

        $userId ??= Auth::id();

        if ($from->type === 'warehouse' && $to->type === 'store') {
            $this->adjustWarehouseStock($from, $product, -$quantity);
            $this->recordWarehouseMovement(
                $product, 'out', $quantity, $reference, 'stock_transfer', $from->id,
                $userId, $documentType, $documentId
            );
            $this->apply($product, 'in', $quantity, $reference, 'stock_transfer', $userId, $documentType, $documentId, $to->id);
            return;
        }

        if ($from->type === 'store' && $to->type === 'warehouse') {
            $this->apply($product, 'out', $quantity, $reference, 'stock_transfer', $userId, $documentType, $documentId, $from->id);
            $this->adjustWarehouseStock($to, $product, $quantity);
            $this->recordWarehouseMovement(
                $product, 'in', $quantity, $reference, 'stock_transfer', $to->id,
                $userId, $documentType, $documentId
            );
            return;
        }

        if ($from->type === 'warehouse' && $to->type === 'warehouse') {
            $this->adjustWarehouseStock($from, $product, -$quantity);
            $this->adjustWarehouseStock($to, $product, $quantity);
            $this->recordWarehouseMovement(
                $product, 'out', $quantity, $reference, 'stock_transfer', $from->id,
                $userId, $documentType, $documentId
            );
            $this->recordWarehouseMovement(
                $product, 'in', $quantity, $reference, 'stock_transfer', $to->id,
                $userId, $documentType, $documentId
            );
            return;
        }

        throw new InvalidArgumentException('Store-to-store transfers are not supported. Move via a warehouse.');
    }

    public function ensureDefaultLocations(int $shopId): void
    {
        if (! StockLocation::where('shop_id', $shopId)->where('type', 'store')->exists()) {
            StockLocation::create([
                'shop_id' => $shopId,
                'name' => 'Main Store',
                'type' => 'store',
                'is_default' => true,
                'is_active' => true,
            ]);
        }

        if (! StockLocation::where('shop_id', $shopId)->where('type', 'warehouse')->exists()) {
            StockLocation::create([
                'shop_id' => $shopId,
                'name' => 'Main Warehouse',
                'type' => 'warehouse',
                'is_default' => true,
                'is_active' => true,
            ]);
        }
    }

    public function defaultStore(int $shopId): ?StockLocation
    {
        return StockLocation::where('shop_id', $shopId)
            ->where('type', 'store')
            ->orderByDesc('is_default')
            ->first();
    }

    public function defaultWarehouse(int $shopId): ?StockLocation
    {
        return StockLocation::where('shop_id', $shopId)
            ->where('type', 'warehouse')
            ->orderByDesc('is_default')
            ->first();
    }

    public function generateNumber(int $shopId, string $prefix): string
    {
        return $prefix . '-' . $shopId . '-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
    }

    /**
     * Receive PO stock into a store (sellable) or warehouse (held until transfer).
     */
    public function receivePurchaseItem(
        Product $product,
        int $quantity,
        string $poNumber,
        ?int $userId = null,
        ?int $documentId = null,
        ?int $locationId = null,
    ): StockMovement {
        $location = $locationId
            ? StockLocation::where('shop_id', $product->shop_id)->where('is_active', true)->findOrFail($locationId)
            : $this->defaultStore($product->shop_id);

        if (! $location) {
            throw new InvalidArgumentException('No stock location available to receive into.');
        }

        $reference = 'PO received: ' . $poNumber;

        if ($location->type === 'warehouse') {
            $this->adjustWarehouseStock($location, $product, $quantity);

            return $this->recordWarehouseMovement(
                $product,
                'in',
                $quantity,
                $reference,
                'purchase_receive',
                $location->id,
                $userId,
                'purchase_order',
                $documentId,
            );
        }

        return $this->apply(
            $product,
            'in',
            $quantity,
            $reference,
            'purchase_receive',
            $userId,
            'purchase_order',
            $documentId,
            $location->id,
        );
    }

    /**
     * Return stock to supplier from store (sellable) or warehouse.
     */
    public function returnPurchaseItem(
        Product $product,
        int $quantity,
        string $reference,
        ?int $userId = null,
        ?int $documentId = null,
        ?int $locationId = null,
    ): StockMovement {
        $location = $locationId
            ? StockLocation::where('shop_id', $product->shop_id)->where('is_active', true)->findOrFail($locationId)
            : $this->defaultStore($product->shop_id);

        if (! $location) {
            throw new InvalidArgumentException('No stock location available to return from.');
        }

        if ($location->type === 'warehouse') {
            $this->adjustWarehouseStock($location, $product, -$quantity);

            return $this->recordWarehouseMovement(
                $product,
                'out',
                $quantity,
                $reference,
                'purchase_return',
                $location->id,
                $userId,
                'purchase_return',
                $documentId,
            );
        }

        return $this->apply(
            $product,
            'out',
            $quantity,
            $reference,
            'purchase_return',
            $userId,
            'purchase_return',
            $documentId,
            $location->id,
        );
    }

    public function warehouseQuantity(StockLocation $location, Product $product): int
    {
        if ($location->type !== 'warehouse') {
            return (int) $product->stock_quantity;
        }

        return (int) (WarehouseStock::where('location_id', $location->id)
            ->where('product_id', $product->id)
            ->value('quantity') ?? 0);
    }

    public function transaction(callable $callback): mixed
    {
        return DB::transaction($callback);
    }
}
