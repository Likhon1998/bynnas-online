<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourierService extends Model
{
    protected $fillable = [
        'shop_id',
        'name',
        'phone',
        'notes',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForShop($query, int $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    /** Shipping requires a courier, so a new shop starts with the common local services. */
    public static function ensureDefaults(int $shopId): void
    {
        if (static::forShop($shopId)->exists()) {
            return;
        }

        foreach (['Pathao', 'Steadfast', 'RedX', 'Paperfly'] as $i => $name) {
            static::create([
                'shop_id' => $shopId,
                'name' => $name,
                'sort_order' => $i + 1,
                'is_active' => true,
            ]);
        }
    }
}
