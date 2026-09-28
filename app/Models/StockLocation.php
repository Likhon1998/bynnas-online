<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class StockLocation extends Model
{
    protected $fillable = [
        'shop_id', 'name', 'type', 'address', 'is_default', 'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (StockLocation $location) {
            if (! config('store.single_shop_mode', true)) {
                return;
            }

            $max = (int) config('store.max_locations_per_type', 1);
            $count = static::where('shop_id', $location->shop_id)
                ->where('type', $location->type)
                ->count();

            if ($count >= $max) {
                throw new RuntimeException('Only one ' . $location->type . ' is allowed for this business.');
            }
        });
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function warehouseStocks()
    {
        return $this->hasMany(WarehouseStock::class, 'location_id');
    }
}
