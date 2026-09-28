<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'shop_id', 'supplier_id', 'user_id', 'po_number', 'status',
        'order_date', 'expected_date', 'notes', 'total_amount', 'paid_amount',
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_date' => 'date',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
    ];

    public function remainingPayable(): float
    {
        return max(0, round((float) $this->total_amount - (float) $this->paid_amount, 2));
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function returns()
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    /**
     * Per-product qty still returnable to the supplier for this PO
     * (received − already returned on completed returns).
     *
     * @return array<int, array{product_id:int, received:int, returned:int, returnable:int, unit_cost:float, name:string}>
     */
    public function returnableLines(): array
    {
        $this->loadMissing('items.product');

        $alreadyReturned = PurchaseReturnItem::query()
            ->whereHas('purchaseReturn', function ($q) {
                $q->where('purchase_order_id', $this->id)
                    ->where('status', '!=', 'cancelled');
            })
            ->selectRaw('product_id, SUM(quantity) as qty')
            ->groupBy('product_id')
            ->pluck('qty', 'product_id');

        $lines = [];
        foreach ($this->items as $item) {
            $received = (int) $item->received_quantity;
            if ($received < 1) {
                continue;
            }
            $returned = (int) ($alreadyReturned[$item->product_id] ?? 0);
            $returnable = max(0, $received - $returned);
            if ($returnable < 1) {
                continue;
            }
            $lines[(int) $item->product_id] = [
                'product_id' => (int) $item->product_id,
                'name' => $item->product->name ?? 'Product #'.$item->product_id,
                'received' => $received,
                'returned' => $returned,
                'returnable' => $returnable,
                'unit_cost' => (float) $item->unit_cost,
            ];
        }

        return $lines;
    }
}
