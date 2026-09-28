<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturn extends Model
{
    protected $fillable = [
        'shop_id', 'supplier_id', 'purchase_order_id', 'user_id',
        'return_number', 'status', 'total_amount', 'notes',
    ];

    protected $casts = ['total_amount' => 'decimal:2'];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }
}
