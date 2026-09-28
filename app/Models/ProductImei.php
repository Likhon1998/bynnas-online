<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImei extends Model
{
    public const STATUS_AVAILABLE = 'available';

    public const STATUS_SOLD = 'sold';

    public const STATUS_RESERVED = 'reserved';

    protected $fillable = [
        'product_id',
        'imei',
        'status',
        'order_id',
        'order_item_id',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', self::STATUS_AVAILABLE);
    }

    public static function normalize(string $imei): string
    {
        return preg_replace('/\s+/', '', trim($imei)) ?: '';
    }
}
