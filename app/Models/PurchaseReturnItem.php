<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturnItem extends Model
{
    protected $fillable = [
        'purchase_return_id', 'product_id', 'quantity', 'unit_cost', 'subtotal',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function purchaseReturn()
    {
        return $this->belongsTo(PurchaseReturn::class);
    }
}
