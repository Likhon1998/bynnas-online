<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account extends Model
{
    protected $fillable = [
        'shop_id', 'counter_id', 'code', 'name', 'type',
        'opening_balance', 'is_system', 'is_active',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function counter()
    {
        return $this->belongsTo(Counter::class);
    }

    public function entries()
    {
        return $this->hasMany(AccountEntry::class);
    }
}
