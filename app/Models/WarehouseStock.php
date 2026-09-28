<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WarehouseStock extends Model
{
    protected $fillable = ['location_id', 'product_id', 'quantity'];

    public function location()
    {
        return $this->belongsTo(StockLocation::class, 'location_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
