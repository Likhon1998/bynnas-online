<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromoBanner extends Model
{
    protected $fillable = [
        'shop_id', 'title', 'subtitle', 'price_from', 'image_path',
        'button_text', 'button_url', 'theme', 'placement',
        'badge_text', 'highlight_text', 'discount_badge',
        'sort_order', 'is_active',
    ];

    protected $casts = [
        'price_from' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
}
