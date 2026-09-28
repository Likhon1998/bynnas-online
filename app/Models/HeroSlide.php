<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HeroSlide extends Model
{
    protected $fillable = [
        'shop_id', 'badge_text', 'title', 'description', 'price_from',
        'image_path', 'button_text', 'button_url', 'learn_more_text', 'learn_more_url',
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
