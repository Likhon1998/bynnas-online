<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NavigationLink extends Model
{
    protected $fillable = [
        'shop_id', 'label', 'url', 'location', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
}
