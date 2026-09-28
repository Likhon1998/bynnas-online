<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $fillable = [
        'shop_id', 'product_id', 'user_id', 'type', 'reason',
        'quantity', 'previous_stock', 'current_stock', 'reference',
        'document_type', 'document_id', 'location_id',
    ];

    /** Stock increased (receive, purchase, stock-in adjustment, restock, transfer in). */
    public function isStockIn(): bool
    {
        return $this->type === 'in';
    }

    /** Stock decreased (sale, stock-out, damage, transfer out). */
    public function isStockOut(): bool
    {
        return in_array($this->type, ['out', 'sale'], true);
    }

    /** Signed quantity for display: +5 or -5. Quantity column is always stored positive. */
    public function signedQuantity(): int
    {
        $qty = (int) $this->quantity;

        return $this->isStockIn() ? $qty : -$qty;
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'in' => 'IN (+)',
            'out' => 'OUT (−)',
            'sale' => 'SALE (−)',
            'reserve' => 'RESERVE',
            'release' => 'RELEASE',
            'commit' => 'COMMIT',
            default => strtoupper((string) $this->type),
        };
    }

    public function reasonLabel(): string
    {
        $reason = str_replace('_', ' ', (string) ($this->reason ?: '—'));

        return $reason === '—' ? '—' : ucwords($reason);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function location()
    {
        return $this->belongsTo(StockLocation::class, 'location_id');
    }
}
