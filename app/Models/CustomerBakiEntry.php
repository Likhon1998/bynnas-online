<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerBakiEntry extends Model
{
    protected $fillable = [
        'shop_id',
        'customer_id',
        'order_id',
        'user_id',
        'type',
        'amount',
        'method',
        'note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
}
