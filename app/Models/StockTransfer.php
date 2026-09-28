<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    protected $fillable = [
        'shop_id', 'from_location_id', 'to_location_id', 'user_id',
        'transfer_number', 'status', 'notes',
    ];

    public function fromLocation()
    {
        return $this->belongsTo(StockLocation::class, 'from_location_id');
    }

    public function toLocation()
    {
        return $this->belongsTo(StockLocation::class, 'to_location_id');
    }

    public function items()
    {
        return $this->hasMany(StockTransferItem::class);
    }
}
